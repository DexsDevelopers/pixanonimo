<?php
/**
 * Webhook for MedusaPay (HopySplit) card payment callbacks.
 * Receives status updates and marks orders as paid, triggering auto-delivery.
 */
require_once 'includes/db.php';
try { require_once 'includes/PushService.php'; } catch (Throwable $e) {}
require_once 'includes/MailService.php';
require_once 'includes/TelegramService.php';

header('Content-Type: text/plain');

$rawInput = file_get_contents('php://input');
$body     = json_decode($rawInput, true);
if (!is_array($body)) $body = [];

$orderId = (int)($_GET['order_id'] ?? 0);
$txId    = (int)($_GET['tx_id'] ?? 0);

write_log('INFO', 'MedusaPay Webhook Hit', [
    'order_id' => $orderId,
    'tx_id'    => $txId,
    'body'     => $rawInput,
    'ip'       => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
]);

if ($orderId <= 0 || $txId <= 0) {
    http_response_code(400);
    echo 'missing_ids';
    exit;
}

// Map MedusaPay status
$status = strtolower(trim((string)($body['status'] ?? '')));
$isPaid = in_array($status, ['paid', 'authorized']);

if (!$isPaid) {
    // Not a payment confirmation, just log and acknowledge
    write_log('INFO', 'MedusaPay Webhook Status (not paid)', ['status' => $status, 'order_id' => $orderId]);
    echo 'OK';
    exit;
}

// Verify order exists and is still pending
$orderStmt = $pdo->prepare("SELECT o.*, p.delivery_method, p.delivery_info FROM orders o JOIN products p ON p.id = o.product_id WHERE o.id = ? AND o.status = 'pending' LIMIT 1");
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    write_log('WARNING', 'MedusaPay Webhook: Order not found or already processed', ['order_id' => $orderId]);
    echo 'OK';
    exit;
}

// Update transaction status
$pdo->prepare("UPDATE transactions SET status = 'paid' WHERE id = ?")->execute([$txId]);

// Fetch transaction for balance update
$txStmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
$txStmt->execute([$txId]);
$transaction = $txStmt->fetch();

if ($transaction) {
    // Credit seller balance
    $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")
        ->execute([$transaction['amount_net_brl'], $transaction['user_id']]);

    // Insert notification for seller
    try {
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')")
            ->execute([$order['seller_id'], '💳 Venda por Cartão!', 'Você vendeu 1x produto #' . $order['product_id'] . ' por R$ ' . number_format($order['amount'], 2, ',', '.') . ' (cartão).']);
    } catch (Throwable $e) {}
}

// === AUTO-DELIVERY (same logic as webhook.php) ===
try {
    $pdo->beginTransaction();
    $stockItem = $pdo->prepare("SELECT id, content FROM product_stock_items WHERE product_id = ? AND status = 'available' ORDER BY id ASC LIMIT 1 FOR UPDATE");
    $stockItem->execute([$order['product_id']]);
    $item = $stockItem->fetch();

    $deliveredContent = null;
    if ($item) {
        $pdo->prepare("UPDATE product_stock_items SET status = 'used', order_id = ?, used_at = NOW() WHERE id = ?")->execute([$order['id'], $item['id']]);
        $pdo->prepare("UPDATE products SET stock = (SELECT COUNT(*) FROM product_stock_items WHERE product_id = ? AND status = 'available'), orders_count = orders_count + 1 WHERE id = ?")->execute([$order['product_id'], $order['product_id']]);
        $deliveredContent = $item['content'];
    } else {
        $pdo->prepare("UPDATE products SET orders_count = orders_count + 1 WHERE id = ?")->execute([$order['product_id']]);
        $deliveredContent = $order['delivery_info'];
    }

    // Mark order as paid
    $pdo->prepare("UPDATE orders SET status = 'paid', delivered_content = ? WHERE id = ?")->execute([$deliveredContent, $order['id']]);
    $pdo->commit();

    // Auto-create chat room
    try {
        $chatToken = bin2hex(random_bytes(16));
        $pdo->prepare("INSERT INTO chat_rooms (order_id, product_id, seller_id, buyer_name, buyer_email, chat_token, last_message_at) VALUES (?, ?, ?, ?, ?, ?, NOW())")
            ->execute([$order['id'], $order['product_id'], $order['seller_id'], $order['buyer_name'], null, $chatToken]);
    } catch (Throwable $chatErr) {
        write_log('WARNING', 'MedusaPay Chat room creation failed', ['order_id' => $order['id'], 'error' => $chatErr->getMessage()]);
    }

    write_log('INFO', 'MedusaPay Card Payment Processed', ['order_id' => $order['id'], 'tx_id' => $txId]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    write_log('ERROR', 'MedusaPay Auto-Delivery Failed', ['error' => $e->getMessage()]);
}

// === NOTIFICATIONS ===
if ($transaction) {
    $userData = getUser($transaction['user_id']);
    $notifMsg = 'Você recebeu R$ ' . number_format($transaction['amount_brl'], 2, ',', '.') . ' via Cartão.';

    if (class_exists('PushService')) {
        try { PushService::notifyUser($transaction['user_id'], '💳 Venda Cartão!', $notifMsg, 'success'); } catch (Throwable $e) {}
        try { PushService::notifyAdmins('💳 Venda Cartão #' . $transaction['id'], 'R$ ' . number_format($transaction['amount_brl'], 2, ',', '.') . ' — ' . ($userData['full_name'] ?? 'N/A'), 'success'); } catch (Throwable $e) {}
    }

    if ($userData && !empty($userData['email'])) {
        try { MailService::notifySale($userData['email'], $userData['full_name'], $transaction['amount_brl']); } catch (Throwable $e) {}
    }

    try { TelegramService::notifySale($transaction['amount_brl'], $userData['full_name'] ?? 'N/A', $transaction['id']); } catch (Throwable $e) {}
}

echo 'OK';
