<?php
require_once 'includes/db.php';
require_once 'includes/TelegramService.php';
try { require_once 'includes/PushService.php'; } catch (Throwable $e) {}

header('Content-Type: application/json');

try {
    $input        = json_decode(file_get_contents('php://input'), true);
    $productId    = (int)($input['product_id'] ?? 0);
    $customerName = trim($input['customer_name'] ?? '');
    $customerDoc  = trim($input['customer_document'] ?? '');

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
    if (empty($product['pix_key'])) throw new Exception('Vendedor sem chave PIX configurada.');

    $amount = (float)$product['price'];
    if ($amount < 10) throw new Exception('Valor mínimo é R$ 10,00.');

    // Check if product has stock items (for auto-delivery)
    if ($product['stock'] !== -1 && $product['stock'] <= 0) {
        throw new Exception('Produto sem estoque disponível.');
    }

    // Generate unique delivery token for this order
    $deliveryToken = bin2hex(random_bytes(24));
    $externalId    = 'prod_' . $productId . '_' . time();

    $sellerId = $product['user_id'];
    $currentPixGoKey = getActivePixGoKey();

    if ($currentPixGoKey === 'SUA_API_KEY_AQUI' || empty($currentPixGoKey)) {
        // Simulation mode
        $pixId   = 'sim_prod_' . time();
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=GHOSTPIX_PRODUCT';
        $pixCode = '00020126360014br.gov.bcb.pix0114000000000000005204000053039865802BR5913GHOSTPIX6009SAOPAULO62070503***6304ABCD';

        $pixgoFee    = $amount * 0.02 + ($amount < 50 ? 1.00 : 0);
        $platformFee = $amount * ($product['commission_rate'] / 100);
        $netAmount   = $amount - $pixgoFee - $platformFee;

        saveTransaction($sellerId, $amount, $netAmount, $pixId, $pixCode, $qrImage, null, $customerName, $externalId, 'pix');
        $txId = (int)$pdo->lastInsertId();

        // Create order
        $pdo->prepare("INSERT INTO orders (product_id, seller_id, buyer_name, buyer_document, amount, transaction_id, status, delivery_token) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)")
            ->execute([$productId, $sellerId, $customerName, $customerDoc, $amount, $txId, $deliveryToken]);

        try { TelegramService::notifyNewCharge($amount, $product['seller_name'], $txId); } catch (Throwable $e) {}
        if (class_exists('PushService')) {
            try { PushService::notifyAdmins('⚡ Produto #' . $txId, 'R$ ' . number_format($amount, 2, ',', '.') . ' — ' . $product['seller_name'], 'info'); } catch (Throwable $e) {}
        }

        echo json_encode(['success' => true, 'qr_image' => $qrImage, 'pix_code' => $pixCode, 'amount' => $amount, 'delivery_token' => $deliveryToken]);
        exit;
    }

    // Real PixGo call
    $data = [
        'amount'      => $amount,
        'description' => 'Compra: ' . mb_substr($product['name'], 0, 40),
        'webhook_url' => getFullUrl('webhook.php'),
        'external_id' => $externalId,
        'payer'       => ['name' => $customerName]
    ];
    if ($customerDoc) $data['payer']['document'] = preg_replace('/[^0-9]/', '', $customerDoc);

    $ch = curl_init('https://pixgo.org/api/v1/payment/create');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['x-api-key: ' . $currentPixGoKey, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) throw new Exception('Falha na conexão com gateway.');

    $res = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && !empty($res['success'])) {
        $pixData = $res['data'] ?? [];
        $pixId   = $pixData['payment_id'] ?? '';
        $qrImage = $pixData['qr_image_url'] ?? '';
        $pixCode = $pixData['pix_code'] ?? ($pixData['payload'] ?? ($pixData['qr_code'] ?? ''));

        $pixgoFee    = $amount * 0.02 + ($amount < 50 ? 1.00 : 0);
        $platformFee = $amount * ($product['commission_rate'] / 100);
        $netAmount   = $amount - $pixgoFee - $platformFee;

        saveTransaction($sellerId, $amount, $netAmount, $pixId, $pixCode, $qrImage, null, $customerName, $externalId, 'pix');
        $txId = (int)$pdo->lastInsertId();

        // Create order with delivery token
        $pdo->prepare("INSERT INTO orders (product_id, seller_id, buyer_name, buyer_document, amount, transaction_id, status, delivery_token) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)")
            ->execute([$productId, $sellerId, $customerName, $customerDoc, $amount, $txId, $deliveryToken]);

        try { TelegramService::notifyNewCharge($amount, $product['seller_name'], $txId); } catch (Throwable $e) {}
        if (class_exists('PushService')) {
            try { PushService::notifyAdmins('⚡ Produto #' . $txId, 'R$ ' . number_format($amount, 2, ',', '.') . ' — ' . $product['seller_name'], 'info'); } catch (Throwable $e) {}
        }

        echo json_encode(['success' => true, 'pix_id' => $pixId, 'qr_image' => $qrImage, 'pix_code' => $pixCode, 'amount' => $amount, 'delivery_token' => $deliveryToken]);
        exit;
    }

    throw new Exception('Erro no gateway de pagamento.');

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
