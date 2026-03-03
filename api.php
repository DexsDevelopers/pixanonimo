<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

// Autenticação Híbrida (Sessão ou API Key)
$userId = null;
$externalRequest = false;

if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    
    // Se for via Painel (Sessão), exige CSRF
    $headers = getallheaders();
    $csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');
    check_csrf($csrfToken);
} else {
    // Tenta autenticar via Header Bearer Token
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
    
    if (preg_match('/Bearer\s+(ghost_\S+)/', $authHeader, $matches)) {
        $apiKey = $matches[1];
        $stmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ? AND status = 'approved'");
        $stmt->execute([$apiKey]);
        $userAuth = $stmt->fetch();
        
        if ($userAuth) {
            $userId = $userAuth['id'];
            $externalRequest = true;
        }
    }
}

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado. Use Bearer <API_KEY> ou faça login.']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $callbackUrl = $input['callback_url'] ?? null;
    $amount = (float)($input['amount'] ?? 0);

    if ($amount < 10) {
        throw new Exception('O valor mínimo para gerar Pix é R$ 10,00.');
    }

    // Buscar dados do usuário (wallet e comissão)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] != 'approved') {
        throw new Exception('Sua conta ainda não foi aprovada pelo administrador.');
    }

    $wallet = $user['pix_key'];
    if (!$wallet) {
        throw new Exception('Configure sua chave PIX no perfil antes de gerar um Pix.');
    }

    // Lógica Anti-Colisão (Varredura de centavos)
    $attempts = 0;
    while ($attempts < 10) {
        $check = $pdo->prepare("SELECT id FROM transactions WHERE amount_brl = ? AND status = 'pending'");
        $check->execute([$amount]);
        if ($check->fetch()) {
            $amount += 0.01;
            $attempts++;
        } else {
            break;
        }
    }

    // Lógica de Comissionamento
    $pixgoFeeRate = 2.0; 
    $platformFeeRate = (float)$user['commission_rate']; 
    $totalFeesRate = $pixgoFeeRate + $platformFeeRate;
    $netAmount = $amount * (1 - ($totalFeesRate / 100));

    // Obter chave de API ativa (Rotação)
    $currentPixGoKey = getActivePixGoKey();

    // Simulação ou Chamada Real
    if ($currentPixGoKey === 'SUA_API_KEY_AQUI' || empty($currentPixGoKey)) {
        $pixId = 'sim_' . time();
        $qrCode = '000201...';
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TESTE';
        $pixCode = '00020126360014br.gov.bcb.pix0114000000000000005204000053039865802BR5913GHOSTPIX6009SAOPAULO62070503***6304ABCD';
        
        saveTransaction($userId, $amount, $netAmount, $pixId, $pixCode, $qrImage, $callbackUrl);

        echo json_encode([
            'success' => true,
            'qr_image' => $qrImage,
            'pix_code' => $pixCode,
            'amount' => $amount,
            'pix_id' => $pixId,
            'message' => 'Simulação ativa.'
        ]);
        exit;
    }

    // Chamada Real
    $baseUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $webhookUrl = $baseUrl . "/webhook.php";
    $data = [
        'amount' => $amount,
        'description' => 'Recarga de Saldo - Ghost Pix',
        'webhook_url' => $webhookUrl,
        'external_id' => 'user_' . $userId . '_' . time()
    ];

    $ch = curl_init('https://pixgo.org/api/v1/payment/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-api-key: ' . $currentPixGoKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new Exception("Erro de conexão (CURL): " . $error);
    }

    $res = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && isset($res['success']) && $res['success']) {
        $pixData = $res['data'] ?? [];
        $pixId = $pixData['payment_id'] ?? '';
        $qrImage = $pixData['qr_image_url'] ?? '';
        $pixCode = $pixData['pix_code'] ?? ($pixData['payload'] ?? ($pixData['qr_code'] ?? ($pixData['qrcodepix'] ?? '')));
        
        saveTransaction($userId, $amount, $netAmount, $pixId, $pixCode, $qrImage, $callbackUrl);

        echo json_encode([
            'success' => true,
            'pix_id' => $pixId,
            'qr_image' => $qrImage,
            'pix_code' => $pixCode,
            'qr_code' => $pixCode,
            'amount' => $amount
        ]);
    } else {
        throw new Exception('Erro na API PixGo: ' . ($res['message'] ?? 'Resposta inválida') . ' (Code: ' . $httpCode . ')');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    write_log('error', 'Falha no api.php: ' . $e->getMessage());
}
?>
