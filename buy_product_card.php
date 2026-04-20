<?php
/**
 * Generates a MedusaPay credit card checkout link for a product purchase.
 * Similar to buy_product.php but uses MedusaPay card checkout instead of PIX.
 */
require_once 'includes/db.php';
require_once 'includes/MedusaPayService.php';
require_once 'includes/TelegramService.php';
try { require_once 'includes/PushService.php'; } catch (Throwable $e) {}

header('Content-Type: application/json');

try {
    $input        = json_decode(file_get_contents('php://input'), true);
    $productId    = (int)($input['product_id'] ?? 0);
    $customerName = trim($input['customer_name'] ?? '');
    $customerDoc  = trim($input['customer_document'] ?? '');
    $couponCode   = strtoupper(trim($input['coupon_code'] ?? ''));

    if (!$productId || !$customerName) {
        throw new Exception('Produto e nome são obrigatórios.');
    }

    if (!checkRateLimit($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) {
        throw new Exception('Limite de geração excedido. Tente novamente em 1 minuto.');
    }

    // Fetch product
    $stmt = $pdo->prepare("SELECT p.*, u.pix_key, u.status AS user_status, u.commission_rate, u.full_name AS seller_name FROM products p JOIN users u ON u.id = p.user_id WHERE p.id = ? AND p.status = 'active' AND p.vitrine = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) throw new Exception('Produto não disponível.');
    if ($product['user_status'] !== 'approved') throw new Exception('Vendedor não autorizado.');

    $amount         = (float)$product['price'];
    $originalAmount = $amount;
    $discountAmount = 0;
    $couponId       = null;

    // Aplicar cupom se fornecido
    if ($couponCode) {
        $cStmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND active = 1");
        $cStmt->execute([$couponCode]);
        $coupon = $cStmt->fetch();

        if ($coupon && (int)$coupon['user_id'] === (int)$product['user_id']) {
            $validScope = ($coupon['scope'] === 'store') ||
                          ($coupon['scope'] === 'product' && (int)$coupon['product_id'] === $productId);
            $notExpired = !$coupon['expires_at'] || strtotime($coupon['expires_at']) >= time();
            $hasUses    = $coupon['max_uses'] === null || (int)$coupon['uses_count'] < (int)$coupon['max_uses'];
            $minOk      = $amount >= (float)$coupon['min_amount'];

            if ($validScope && $notExpired && $hasUses && $minOk) {
                $discountAmount = $coupon['type'] === 'percent'
                    ? round($amount * ((float)$coupon['value'] / 100), 2)
                    : min((float)$coupon['value'], $amount);
                $couponId = $coupon['id'];
                $amount   = max(5, $amount - $discountAmount);
            }
        }
    }

    if ($amount < 5) throw new Exception('Valor mínimo para cartão é R$ 5,00.');

    // Check stock
    if ($product['stock'] !== -1 && $product['stock'] <= 0) {
        throw new Exception('Produto sem estoque disponível.');
    }

    $deliveryToken = bin2hex(random_bytes(24));
    $sellerId      = $product['user_id'];

    // Create a pending transaction record for card payment
    $externalId = 'card_' . $productId . '_' . time();
    $netAmount  = $amount * (1 - ($product['commission_rate'] / 100));

    saveTransaction($sellerId, $amount, $netAmount, $externalId, '', '', null, $customerName, $externalId, 'card');
    $txId = (int)$pdo->lastInsertId();

    // Create order
    $buyerUserId = $_SESSION['user_id'] ?? null;
    $pdo->prepare("INSERT INTO orders (product_id, seller_id, buyer_name, buyer_document, buyer_user_id, amount, transaction_id, status, delivery_token, coupon_id, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)")
        ->execute([$productId, $sellerId, $customerName, $customerDoc, $buyerUserId, $amount, $txId, $deliveryToken, $couponId, $discountAmount]);
    $orderId = (int)$pdo->lastInsertId();

    if ($couponId) {
        $pdo->prepare("UPDATE coupons SET uses_count = uses_count + 1 WHERE id = ?")->execute([$couponId]);
    }

    // Build postback URL
    $postbackUrl = getFullUrl('medusa_webhook.php') . '?order_id=' . $orderId . '&tx_id=' . $txId;

    // Create MedusaPay card checkout
    $result = MedusaPayService::createCardCheckout(
        $amount,
        'Compra: ' . mb_substr($product['name'], 0, 50),
        $postbackUrl
    );

    if (!$result['ok']) {
        throw new Exception($result['error']);
    }

    // Save reference
    $pdo->prepare("UPDATE transactions SET pix_id = ?, pix_code = 'card_checkout' WHERE id = ?")
        ->execute([$result['reference'], $txId]);

    try { TelegramService::notifyNewCharge($amount, $product['seller_name'], $txId); } catch (Throwable $e) {}
    if (class_exists('PushService')) {
        try { PushService::notifyAdmins('💳 Cartão #' . $txId, 'R$ ' . number_format($amount, 2, ',', '.') . ' — ' . $product['seller_name'], 'info'); } catch (Throwable $e) {}
    }

    echo json_encode([
        'success'         => true,
        'checkout_url'    => $result['checkout_url'],
        'amount'          => $amount,
        'original_amount' => $originalAmount,
        'discount_amount' => $discountAmount,
        'delivery_token'  => $deliveryToken,
        'payment_method'  => 'card',
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
