<?php
require_once 'includes/db.php';
try {
    require_once 'includes/PushService.php';
} catch (Throwable $e) {}
require_once 'includes/MailService.php';
require_once 'includes/TelegramService.php';

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
            // Lucro plataforma = Valor Bruto - Valor Líquido - Taxa PixGo (2% + R$1 se < R$50)
            $pixgoFee = $transaction['amount_brl'] * 0.02;
            if ($transaction['amount_brl'] < 50) $pixgoFee += 1.00;
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
            $userData = getUser($transaction['user_id']);
            $notifMsg = 'Você recebeu R$ ' . number_format($transaction['amount_brl'], 2, ',', '.') . ' via Pix.';
            if (class_exists('PushService')) {
                try {
                    PushService::notifyUser($transaction['user_id'], '💰 Venda Confirmada!', $notifMsg, 'success');
                } catch (Throwable $e) {}
                
                // Notificar Admin (Push + In-App)
                try {
                    PushService::notifyAdmins('💰 Venda Confirmada #' . $transaction['id'], 'R$ ' . number_format($transaction['amount_brl'], 2, ',', '.') . ' — Lojista: ' . ($userData['full_name'] ?? 'N/A'), 'success');
                } catch (Throwable $e) {}
            }
            
            // Enviar e-mail para o usuário
            if ($userData && !empty($userData['email'])) {
                MailService::notifySale($userData['email'], $userData['full_name'], $transaction['amount_brl']);
            }

            // Notificar Admin via Telegram
            try {
                TelegramService::notifySale(
                    (float)$transaction['amount_brl'],
                    $realPayerName ?: ($transaction['customer_name'] ?? 'Sem nome'),
                    $userData['full_name'] ?? 'N/A',
                    (int)$transaction['id']
                );
            } catch (Throwable $e) {}

            // 4. Disparar Webhook Externo para o Lojista (callback_url da transação)
            $webhookPayload = [
                'event' => 'payment.completed',
                'transaction_id' => $transaction['id'],
                'pix_id' => $transaction['pix_id'],
                'amount' => (float)$transaction['amount_brl'],
                'amount_net' => (float)$transaction['amount_net_brl'],
                'customer_name' => $realPayerName ?: ($transaction['customer_name'] ?? ''),
                'status' => 'paid',
                'external_id' => $transaction['external_id'] ?? '',
                'timestamp' => date('Y-m-d H:i:s')
            ];

            if (!empty($transaction['callback_url'])) {
                $ch = curl_init($transaction['callback_url']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookPayload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'User-Agent: GhostPix-Webhook/1.0', 'X-GhostPix-Event: payment.completed']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $out = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                write_log('INFO', 'Webhook Externo Disparado (callback_url)', [
                    'url' => $transaction['callback_url'],
                    'http_code' => $code,
                    'response' => $out
                ]);
            }

            // 5. Disparar TODOS os webhooks configurados pelo usuário
            try {
                $whStmt = $pdo->prepare("SELECT id, url FROM user_webhooks WHERE user_id = ? AND active = 1");
                $whStmt->execute([$transaction['user_id']]);
                $userWebhooks = $whStmt->fetchAll();

                foreach ($userWebhooks as $wh) {
                    $ch = curl_init($wh['url']);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookPayload));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'User-Agent: GhostPix-Webhook/1.0', 'X-GhostPix-Event: payment.completed']);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

                    $out = curl_exec($ch);
                    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    // Atualizar status do webhook
                    $pdo->prepare("UPDATE user_webhooks SET last_status = ?, last_triggered_at = NOW() WHERE id = ?")->execute([$code, $wh['id']]);

                    write_log('INFO', 'User Webhook Disparado', [
                        'webhook_id' => $wh['id'],
                        'url' => $wh['url'],
                        'http_code' => $code,
                        'user_id' => $transaction['user_id']
                    ]);
                }
            } catch (PDOException $e) {
                write_log('ERROR', 'Erro ao disparar user webhooks', ['error' => $e->getMessage()]);
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

