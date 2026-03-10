<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $checkoutId = (int)($input['checkout_id'] ?? 0);
    $customerName = trim($input['customer_name'] ?? '');
    $customerDocument = trim($input['customer_document'] ?? ''); // CPF opcional

    if ($checkoutId <= 0) {
        throw new Exception('Checkout inválido.');
    }

    // Buscar Checkout
    $stmt = $pdo->prepare("SELECT * FROM checkouts WHERE id = ? AND active = 1");
    $stmt->execute([$checkoutId]);
    $checkout = $stmt->fetch();

    if (!$checkout) {
        throw new Exception('Checkout não encontrado ou inativo.');
    }

    // Buscar Itens e calcular total
    $stmt = $pdo->prepare("SELECT SUM(price) as total_amount FROM checkout_items WHERE checkout_id = ?");
    $stmt->execute([$checkoutId]);
    $totalAmount = (float)$stmt->fetchColumn();

    if ($totalAmount < 10) {
        throw new Exception('O valor mínimo de transação é R$ 10,00.');
    }

    // Buscar User / Lojista
    $userId = $checkout['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] != 'approved') {
        throw new Exception('O recebedor não está apto a receber pagamentos no momento.');
    }
    if (empty($user['pix_key'])) {
        throw new Exception('O recebedor não possui chave PIX configurada.');
    }

    $currentPixGoKey = getActivePixGoKey();

    // Ambiente de Simulação / Teste
    if ($currentPixGoKey === 'SUA_API_KEY_AQUI' || empty($currentPixGoKey)) {
        $pixId = 'sim_chk_' . time();
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TESTE';
        $pixCode = '00020126360014br.gov.bcb.pix0114000000000000005204000053039865802BR5913GHOSTPIX6009SAOPAULO62070503***6304ABCD';
        $netAmount = $totalAmount * (1 - ($user['commission_rate'] / 100));
        
        saveTransaction($userId, $totalAmount, $netAmount, $pixId, $pixCode, $qrImage, null, 'pix');

        echo json_encode([
            'success' => true,
            'qr_image' => $qrImage, 
            'pix_code' => $pixCode, 
            'amount' => $totalAmount, 
            'pix_id' => $pixId
        ]);
        exit;
    }

    // Chamada Real PixGo
    $data = [
        'amount' => $totalAmount,
        'description' => 'Pedido em ' . mb_substr($checkout['title'], 0, 30),
        'webhook_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/webhook.php",
        'external_id' => 'chk_' . $checkoutId . '_' . time(),
        'payer' => [
            'name' => empty($customerName) ? 'Cliente Checkout' : $customerName
        ]
    ];

    if (!empty($customerDocument)) {
        $data['payer']['document'] = preg_replace('/[^0-9]/', '', $customerDocument);
    }

    $ch = curl_init('https://pixgo.org/api/v1/payment/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: ' . $currentPixGoKey, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception("Falha na conexão com gateway de pagamento.");
    }

    $res = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && isset($res['success']) && $res['success']) {
        $pixData = $res['data'] ?? [];
        $pixId = $pixData['payment_id'] ?? '';
        $qrImage = $pixData['qr_image_url'] ?? '';
        $pixCode = $pixData['pix_code'] ?? ($pixData['payload'] ?? ($pixData['qr_code'] ?? ($pixData['qrcodepix'] ?? '')));
        
        $netAmount = $totalAmount * (1 - ($user['commission_rate'] / 100));
        saveTransaction($userId, $totalAmount, $netAmount, $pixId, $pixCode, $qrImage, null, 'pix');

        echo json_encode([
            'success' => true,
            'pix_id' => $pixId, 
            'qr_image' => $qrImage, 
            'pix_code' => $pixCode, 
            'amount' => $totalAmount
        ]);
        exit;
    } else {
        write_log('error', 'Resposta Inválida PixGo no Checkout: ' . $response);
        throw new Exception('Não foi possível gerar a cobrança devido a um erro no gateway.');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
