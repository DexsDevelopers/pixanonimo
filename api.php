<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$amount = (float)($input['amount'] ?? 0);

if ($amount < 10) {
    echo json_encode(['error' => 'O valor mínimo para gerar Pix é R$ 10,00.']);
    exit;
}

// Buscar dados do usuário (wallet e comissão)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user['status'] != 'approved') {
    echo json_encode(['error' => 'Sua conta ainda não foi aprovada pelo administrador.']);
    exit;
}

$wallet = $user['pix_key'];
if (!$wallet) {
    echo json_encode(['error' => 'Configure sua chave PIX no perfil antes de gerar um Pix.']);
    exit;
}

// Lógica Anti-Colisão (Varredura de centavos)
// Se já existir uma transação pendente com o mesmo valor EXATO, adicionamos 0.01 até ficar único.
// Isso evita que o PixGo bloqueie QR Codes repetidos.
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

// Lógica de Comissionamento (Recalcular sobre o valor final)
$pixgoFeeRate = 2.0; // Taxa padrão do PixGo.org
$platformFeeRate = (float)$user['commission_rate']; // Sua taxa configurada no admin

$totalFeesRate = $pixgoFeeRate + $platformFeeRate;
$netAmount = $amount * (1 - ($totalFeesRate / 100));

// Chamada para a API do PixGo.org
$url = 'https://pixgo.org/v2/orders/pix'; 

$baseUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$webhookUrl = $baseUrl . "/webhook.php";

$data = [
    'amount' => $amount,
    'description' => 'Recarga de Saldo - Ghost Pix',
    'webhook_url' => $webhookUrl,
    'external_id' => 'user_' . $userId . '_' . time()
];

// Simulação de resposta se não houver API KEY
if (PIXGO_API_KEY === 'SUA_API_KEY_AQUI') {
    $pixId = 'sim_' . time();
    $qrCode = '000201...';
    $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TESTE';
    
    // Salvar transação no banco
    try {
        $pixCode = '00020126360014br.gov.bcb.pix0114000000000000005204000053039865802BR5913GHOSTPIX6009SAOPAULO62070503***6304ABCD';
        $ins = $pdo->prepare("INSERT INTO transactions (user_id, amount_brl, amount_net_brl, pix_id, status, pix_code, qr_image) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
        $ins->execute([$userId, $amount, $netAmount, $pixId, $pixCode, $qrImage]);

        echo json_encode([
            'status' => 'success',
            'success' => true,
            'qr_image' => $qrImage,
            'pix_code' => $pixCode,
            'amount' => $amount,
            'pix_id' => $pixId,
            'message' => 'Simulação ativa.'
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao salvar no banco: ' . $e->getMessage()]);
        exit;
    }
}

// Chamada Real via CURL para API V1
$ch = curl_init('https://pixgo.org/api/v1/payment/create');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-api-key: ' . PIXGO_API_KEY,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$res = json_decode($response, true);
if ($httpCode >= 200 && $httpCode < 300 && isset($res['success']) && $res['success']) {
    // O PixGo V1 retorna os dados dentro da chave 'data'
    $pixData = $res['data'] ?? [];
    $pixId = $pixData['payment_id'] ?? '';
    $qrImage = $pixData['qr_image_url'] ?? '';

    // Salvar transação no banco (Produção)
    try {
        // Tentar encontrar o código Copia e Cola em várias chaves comuns
        $pixCode = $pixData['pix_code'] ?? ($pixData['payload'] ?? ($pixData['qr_code'] ?? ($pixData['qrcodepix'] ?? '')));
        
        $ins = $pdo->prepare("INSERT INTO transactions (user_id, amount_brl, amount_net_brl, pix_id, status, pix_code, qr_image) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
        $ins->execute([$userId, $amount, $netAmount, $pixId, $pixCode, $qrImage]);

        echo json_encode([
            'success' => true,
            'pix_id' => $pixId,
            'qr_image' => $qrImage,
            'pix_code' => $pixCode,
            'qr_code' => $pixCode, // Campo extra para compatibilidade
            'amount' => $amount,
            'debug_raw' => $pixData // Retornar dados para debug se necessário
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao salvar: ' . $e->getMessage()]);
    }
} else {
    echo json_encode([
        'error' => 'Erro na API PixGo',
        'detail' => $res,
        'code' => $httpCode
    ]);
}
?>
