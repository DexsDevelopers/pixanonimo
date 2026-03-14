<?php
ob_start();
set_time_limit(60);
require_once 'includes/db.php';
require_once 'includes/PushService.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Autenticação Híbrida 
$userId = null;
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $headers = getallheaders();
    $csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');
    check_csrf($csrfToken);
} else {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');
    if (preg_match('/Bearer\s+(ghost_\S+)/', $authHeader, $matches)) {
        $apiKey = $matches[1];
        $stmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ? AND status = 'approved'");
        $stmt->execute([$apiKey]);
        $userAuth = $stmt->fetch();
        if ($userAuth) $userId = $userAuth['id'];
    }
}

if (!$userId) {
    Response::error('Não autorizado.', 401);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $callbackUrl = $input['callback_url'] ?? null;
    $amount = (float)($input['amount'] ?? 0);

    // Anti-bot: Rate Limit check
    if (!checkRateLimit($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) {
        throw new Exception('Limite de geração excedido. Tente novamente em 1 minuto.');
    }

    if ($amount < 10) throw new Exception('Mínimo R$ 10,00.');

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] != 'approved') throw new Exception('Conta não aprovada.');
    if (!$user['pix_key']) throw new Exception('Configure sua chave PIX no perfil.');

    // Rotação de API
    $currentPixGoKey = getActivePixGoKey();

    if ($currentPixGoKey === 'SUA_API_KEY_AQUI' || empty($currentPixGoKey)) {
        $pixId = 'sim_' . time();
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TESTE';
        $pixCode = '00020126360014br.gov.bcb.pix0114000000000000005204000053039865802BR5913GHOSTPIX6009SAOPAULO62070503***6304ABCD';
        $netAmount = $amount * (1 - ($user['commission_rate'] / 100));
        saveTransaction($userId, $amount, $netAmount, $pixId, $pixCode, $qrImage, $callbackUrl);

        // Notificar via Push
        PushService::notifyUser($userId, '⚡ PIX Gerado!', 'Uma nova cobrança de R$ ' . number_format($amount, 2, ',', '.') . ' foi gerada.');

        Response::success([
            'qr_image' => $qrImage, 
            'pix_code' => $pixCode, 
            'amount' => $amount, 
            'pix_id' => $pixId
        ]);
    }

    // Chamada Real
    $data = [
        'amount' => $amount,
        'description' => 'Recarga Ghost Pix',
        'webhook_url' => (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/webhook.php",
        'external_id' => 'user_' . $userId . '_' . time()
    ];

    $ch = curl_init('https://pixgo.org/api/v1/payment/create');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-api-key: ' . $currentPixGoKey, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 segundos de timeout

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception("Falha na conexão com PixGo: " . $curlError);
    }

    $res = json_decode($response, true);
    if ($httpCode >= 200 && $httpCode < 300 && isset($res['success']) && $res['success']) {
        $pixData = $res['data'] ?? [];
        $pixId = $pixData['payment_id'] ?? '';
        $qrImage = $pixData['qr_image_url'] ?? '';
        $pixCode = $pixData['pix_code'] ?? ($pixData['payload'] ?? ($pixData['qr_code'] ?? ($pixData['qrcodepix'] ?? '')));
        
        $netAmount = $amount * (1 - ($user['commission_rate'] / 100));
        saveTransaction($userId, $amount, $netAmount, $pixId, $pixCode, $qrImage, $callbackUrl);

        // Notificar via Push
        PushService::notifyUser($userId, '⚡ PIX Gerado!', 'Uma nova cobrança de R$ ' . number_format($amount, 2, ',', '.') . ' foi gerada.');

        Response::success([
            'pix_id' => $pixId, 
            'qr_image' => $qrImage, 
            'pix_code' => $pixCode, 
            'amount' => $amount
        ]);
    } else {
        write_log('error', 'Resposta Inválida PixGo: ' . $response);
        throw new Exception('Erro PixGo: ' . ($res['message'] ?? 'Resposta inesperada') . ' (CS: ' . $httpCode . ')');
    }

} catch (Exception $e) {
    write_log('error', 'Falha API: ' . $e->getMessage());
    Response::error($e->getMessage());
}
?>
