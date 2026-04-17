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
    
    // Also grab external_id sent back by PixGo (always reliable regardless of API account)
    $externalId = $pixData['external_id'] ?? ($data['external_id'] ?? '');

    if (($status === 'completed' || $status === 'paid' || $status === 'PAID') && (!empty($pixId) || !empty($externalId))) {
        write_log('INFO', 'Webhook Identificado para Processamento', ['pix_id' => $pixId, 'external_id' => $externalId, 'status' => $status]);

    // Buscar a transação pendente — tenta pix_id primeiro, depois external_id como fallback
    $transaction = null;
    if (!empty($pixId)) {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE pix_id = ? AND status = 'pending'");
        $stmt->execute([$pixId]);
        $transaction = $stmt->fetch() ?: null;
    }
    if (!$transaction && !empty($externalId)) {
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE external_id = ? AND status = 'pending'");
        $stmt->execute([$externalId]);
        $transaction = $stmt->fetch() ?: null;
        if ($transaction && !empty($pixId)) {
            // Atualiza o pix_id na transação para futuras referências
            $pdo->prepare("UPDATE transactions SET pix_id = ? WHERE id = ?")->execute([$pixId, $transaction['id']]);
        }
    }
    write_log('INFO', 'Transação Lookup', ['found' => (bool)$transaction, 'via_pix_id' => !empty($pixId), 'via_external_id' => !empty($externalId)]);

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
            adjustBalance(
                (int)$transaction['user_id'],
                (float)$transaction['amount_net_brl'],
                'sale',
                'tx_' . $transaction['id'],
                'Venda confirmada PIX #' . $transaction['id'] . ' — R$ ' . number_format($transaction['amount_brl'], 2, ',', '.')
            );

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
                    adjustBalance(
                        (int)$userAff['affiliate_id'],
                        $affiliateCommission,
                        'affiliate',
                        'tx_' . $transaction['id'],
                        'Comissão afiliado — venda #' . $transaction['id']
                    );
                }

                $adminProfit = $platformGrossProfit - $affiliateCommission;
                
                if ($adminProfit > 0) {
                    // Creditar ao primeiro admin encontrado
                    $adminStmt = $pdo->query("SELECT id FROM users WHERE is_admin = 1 LIMIT 1");
                    $admin = $adminStmt->fetch();
                    if ($admin) {
                        adjustBalance(
                            (int)$admin['id'],
                            $adminProfit,
                            'admin_profit',
                            'tx_' . $transaction['id'],
                            'Lucro plataforma — venda #' . $transaction['id']
                        );
                    }
                }
            }

            $pdo->commit();
            write_log('INFO', 'Transação Confirmada', ['transaction_id' => $transaction['id'], 'user_id' => $transaction['user_id']]);

            // === AUTO-DELIVERY: assign stock item to product order ===
            try {
                $orderStmt = $pdo->prepare("SELECT o.*, p.delivery_method, p.delivery_info FROM orders o JOIN products p ON p.id = o.product_id WHERE o.transaction_id = ? AND o.status = 'pending' LIMIT 1");
                $orderStmt->execute([$transaction['id']]);
                $order = $orderStmt->fetch();

                if ($order) {
                    // Try to pick an available stock item
                    $pdo->beginTransaction();
                    $stockItem = $pdo->prepare("SELECT id, content FROM product_stock_items WHERE product_id = ? AND status = 'available' ORDER BY id ASC LIMIT 1 FOR UPDATE");
                    $stockItem->execute([$order['product_id']]);
                    $item = $stockItem->fetch();

                    $deliveredContent = null;
                    if ($item) {
                        // Mark stock item as used
                        $pdo->prepare("UPDATE product_stock_items SET status = 'used', order_id = ?, used_at = NOW() WHERE id = ?")->execute([$order['id'], $item['id']]);
                        // Update product stock count
                        $pdo->prepare("UPDATE products SET stock = (SELECT COUNT(*) FROM product_stock_items WHERE product_id = ? AND status = 'available'), orders_count = orders_count + 1 WHERE id = ?")->execute([$order['product_id'], $order['product_id']]);
                        $deliveredContent = $item['content'];
                    } else {
                        // No stock items — use delivery_info as fallback
                        $pdo->prepare("UPDATE products SET orders_count = orders_count + 1 WHERE id = ?")->execute([$order['product_id']]);
                        $deliveredContent = $order['delivery_info'];
                    }

                    // Update order status and delivered_content
                    $pdo->prepare("UPDATE orders SET status = 'paid', delivered_content = ? WHERE id = ?")->execute([$deliveredContent, $order['id']]);
                    $pdo->commit();

                    // Notify seller of new sale
                    try {
                        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')")
                            ->execute([$order['seller_id'], '🛒 Novo Pedido!', 'Você vendeu 1x produto #' . $order['product_id'] . ' por R$ ' . number_format($order['amount'], 2, ',', '.') . '.']);
                    } catch (Throwable $e) {}

                    write_log('INFO', 'Auto-Delivery Processado', ['order_id' => $order['id'], 'has_stock_item' => (bool)$item]);
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                write_log('ERROR', 'Auto-Delivery Falhou', ['error' => $e->getMessage()]);
            }
            // === END AUTO-DELIVERY ===
            
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
                    (float)$transaction['amount_net_brl'],
                    $realPayerName ?: ($transaction['customer_name'] ?? 'Sem nome'),
                    $userData['full_name'] ?? 'N/A',
                    (int)$transaction['id']
                );
            } catch (Throwable $e) {}

            // Notificar Usuário via Telegram User Bot (se vinculado)
            try {
                if (!empty($userData['telegram_chat_id']) && defined('TELEGRAM_USER_BOT_TOKEN') && TELEGRAM_USER_BOT_TOKEN) {
                    $tgMsg = "💰 <b>Venda Confirmada!</b>\n━━━━━━━━━━━━━━━━━━━━\n\n"
                           . "💵 Valor: <b>R$ " . number_format($transaction['amount_brl'], 2, ',', '.') . "</b>\n"
                           . "💎 Líquido: R$ " . number_format($transaction['amount_net_brl'], 2, ',', '.') . "\n"
                           . "👤 Pagador: " . ($realPayerName ?: ($transaction['customer_name'] ?? 'N/A')) . "\n"
                           . "🆔 TX: <code>#" . $transaction['id'] . "</code>\n\n"
                           . "✅ Valor creditado no seu saldo!";
                    $tgCh = curl_init("https://api.telegram.org/bot" . TELEGRAM_USER_BOT_TOKEN . "/sendMessage");
                    curl_setopt_array($tgCh, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode([
                            'chat_id' => $userData['telegram_chat_id'],
                            'text' => $tgMsg,
                            'parse_mode' => 'HTML'
                        ]),
                        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                        CURLOPT_TIMEOUT => 5,
                    ]);
                    curl_exec($tgCh);
                    curl_close($tgCh);
                }
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

