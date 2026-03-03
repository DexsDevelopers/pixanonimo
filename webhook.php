<?php
require_once 'includes/db.php';
require_once 'includes/notify.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

write_log('INFO', 'Webhook PixGo Recebido', ['payload' => $data]);

// O PixGo V1 envia um campo 'event' e os dados em 'data'
if (isset($data['event']) && $data['event'] === 'payment.completed') {
    $pixData = $data['data'] ?? [];
    $pixId = $pixData['payment_id'] ?? '';
    $status = $pixData['status'] ?? '';
    
    if ($status === 'completed' && !empty($pixId)) {

    // Buscar a transação pendente
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE pix_id = ? AND status = 'pending'");
    $stmt->execute([$pixId]);
    $transaction = $stmt->fetch();

    if ($transaction) {
        $pdo->beginTransaction();
        try {
            // 1. Atualizar status da transação
            $upd = $pdo->prepare("UPDATE transactions SET status = 'paid' WHERE id = ?");
            $upd->execute([$transaction['id']]);

            // 2. Adicionar valor ao saldo do usuário (valor líquido calculado no api.php)
            $balanceUpd = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $balanceUpd->execute([$transaction['amount_net_brl'], $transaction['user_id']]);

            // 3. Calcular e Credit lucro do Admin
            // Lucro plataforma = Valor Bruto - Valor Líquido - Taxa PixGo (2%)
            $pixgoFee = $transaction['amount_brl'] * 0.02;
            $adminProfit = $transaction['amount_brl'] - $transaction['amount_net_brl'] - $pixgoFee;
            
            if ($adminProfit > 0) {
                // Creditar ao primeiro admin encontrado
                $adminStmt = $pdo->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
                $admin = $adminStmt->fetch();
                if ($admin) {
                    $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$adminProfit, $admin['id']]);
                    write_log('INFO', 'Lucro Admin Creditado', ['profit' => $adminProfit, 'admin_id' => $admin['id']]);
                }
            }

            $pdo->commit();
            write_log('INFO', 'Transação Confirmada', ['transaction_id' => $transaction['id'], 'user_id' => $transaction['user_id']]);
            notify_new_payment($transaction['amount_brl'], $transaction['user_id']);

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
echo json_encode(['status' => 'received']);
?>
