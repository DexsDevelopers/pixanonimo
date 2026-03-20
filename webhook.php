<?php
require_once 'includes/db.php';
try {
    require_once 'includes/PushService.php';
} catch (Throwable $e) {}
require_once 'includes/MailService.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Log imediato de depuração para qualquer hit no webhook
write_log('INFO', 'Webhook Hit Recebido', [
    'input_raw' => $input,
    'headers' => getallheaders(),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
]);

// O PixGo V1 envia um campo 'event' e os dados em 'data'
if (isset($data['event']) && ($data['event'] === 'payment.completed' || $data['event'] === 'payment.paid')) {
    $pixData = $data['data'] ?? [];
    
    // Suporte a diferentes chaves de ID (payment_id ou id)
    $pixId = $pixData['payment_id'] ?? ($pixData['id'] ?? ($data['id'] ?? ''));
    
    // Suporte a diferentes nomes de status (completed ou paid)
    $status = $pixData['status'] ?? ($data['status'] ?? '');
    
    if (($status === 'completed' || $status === 'paid' || $status === 'PAID') && !empty($pixId)) {
        write_log('INFO', 'Webhook Identificado para Processamento', ['pix_id' => $pixId, 'status' => $status]);

    // Buscar a transação pendente
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE pix_id = ? AND status = 'pending'");
    $stmt->execute([$pixId]);
    $transaction = $stmt->fetch();

    if ($transaction) {
        $pdo->beginTransaction();
        try {
            // Tentar extrair o nome real do pagador enviado pelo PixGo
            $realPayerName = $pixData['payer']['name'] ?? ($pixData['payer_name'] ?? ($pixData['customer_name'] ?? null));

            // 1. Atualizar status da transação e nome do pagador (se disponível)
            if ($realPayerName) {
                $upd = $pdo->prepare("UPDATE transactions SET status = 'paid', customer_name = ? WHERE id = ?");
                $upd->execute([$realPayerName, $transaction['id']]);
            } else {
                $upd = $pdo->prepare("UPDATE transactions SET status = 'paid' WHERE id = ?");
                $upd->execute([$transaction['id']]);
            }

            // 2. Adicionar valor ao saldo do usuário (valor líquido calculado no api.php)
            $balanceUpd = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $balanceUpd->execute([$transaction['amount_net_brl'], $transaction['user_id']]);

            // 3. Calcular e Credit lucro do Admin e Afiliado
            // Lucro plataforma = Valor Bruto - Valor Líquido - Taxa PixGo (2%)
            $pixgoFee = $transaction['amount_brl'] * 0.02;
            $platformGrossProfit = $transaction['amount_brl'] - $transaction['amount_net_brl'] - $pixgoFee;
            
            if ($platformGrossProfit > 0) {
                // Verificar se o usuário da transação possui um afiliado
                $userAffStmt = $pdo->prepare("SELECT affiliate_id FROM users WHERE id = ?");
                $userAffStmt->execute([$transaction['user_id']]);
                $userAff = $userAffStmt->fetch();

                $affiliateCommission = 0;
                if ($userAff && !empty($userAff['affiliate_id'])) {
                    // Buscar taxa de comissão de afiliados
                    $affRateStmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'affiliate_commission_rate'");
                    $affRate = (float)$affRateStmt->fetchColumn();
                    
                    $affiliateCommission = $platformGrossProfit * ($affRate / 100);
                    
                    // Creditar ao afiliado
                    $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$affiliateCommission, $userAff['affiliate_id']]);
                    write_log('INFO', 'Comissão de Afiliado Creditada', ['amount' => $affiliateCommission, 'affiliate_id' => $userAff['affiliate_id']]);
                }

                $adminProfit = $platformGrossProfit - $affiliateCommission;
                
                if ($adminProfit > 0) {
                    // Creditar ao primeiro admin encontrado
                    $adminStmt = $pdo->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
                    $admin = $adminStmt->fetch();
                    if ($admin) {
                        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$adminProfit, $admin['id']]);
                        write_log('INFO', 'Lucro Admin Creditado', ['profit' => $adminProfit, 'admin_id' => $admin['id']]);
                    }
                }
            }

            $pdo->commit();
            write_log('INFO', 'Transação Confirmada', ['transaction_id' => $transaction['id'], 'user_id' => $transaction['user_id']]);
            // 3.5 Enviar Notificações
            $notifMsg = 'Você recebeu R$ ' . number_format($transaction['amount_brl'], 2, ',', '.') . ' via Pix.';
            if (class_exists('PushService')) {
                try {
                    PushService::notifyUser($transaction['user_id'], '💰 Venda Confirmada!', $notifMsg, 'success');
                } catch (Throwable $e) {}
            }
            
            // Enviar e-mail para o usuário
            $userData = getUser($transaction['user_id']);
            if ($userData && !empty($userData['email'])) {
                MailService::notifySale($userData['email'], $userData['full_name'], $transaction['amount_brl']);
            }

            // 4. Disparar Webhook Externo para o Lojista (se houver)
            if (!empty($transaction['callback_url'])) {
                $externalPayload = [
                    'event' => 'payment.completed',
                    'transaction_id' => $transaction['id'],
                    'pix_id' => $transaction['pix_id'],
                    'amount' => $transaction['amount_brl'],
                    'status' => 'paid',
                    'timestamp' => date('Y-m-d H:i:s')
                ];

                $ch = curl_init($transaction['callback_url']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($externalPayload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $out = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                write_log('INFO', 'Webhook Externo Disparado', [
                    'url' => $transaction['callback_url'],
                    'http_code' => $code,
                    'response' => $out
                ]);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            write_log('ERROR', 'Falha no processamento Webhook', ['error' => $e->getMessage(), 'pix_id' => $pixId]);
        }
        }
    }
}

// Retornar 200 para o PixGo não reenviar o webhook
http_response_code(200);
Response::json(['status' => 'received']);
?>

