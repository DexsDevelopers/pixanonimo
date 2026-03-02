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

// Buscar dados do usuário (wallet e comissão)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user['status'] != 'approved') {
    echo json_encode(['error' => 'Sua conta ainda não foi aprovada pelo administrador.']);
    exit;
}

$wallet = $user['liquid_address'];
if (!$wallet) {
    echo json_encode(['error' => 'Configure sua carteira Liquid no perfil antes de gerar um Pix.']);
    exit;
}

// Lógica de Comissionamento
$pixgoFeeRate = 2.0; // Taxa padrão do PixGo.org
$platformFeeRate = (float)$user['commission_rate']; // Sua taxa configurada no admin

$totalFeesRate = $pixgoFeeRate + $platformFeeRate;
$netAmount = $amount * (1 - ($totalFeesRate / 100));

// O PixGo.org processa o Pix pelo valor total ($amount).
// Ele já desconta os 2% dele.
// Os seus X% ficarão como "saldo" ou serão lidados via split se a API permitir.
// No modelo atual de carteira direta, o DEPIX que chega na wallet já vem com a taxa do PixGo descontada.
// Se você quer cobrar algo a mais, o valor que o usuário final recebe será o valor pago menos as duas taxas.

// Chamada para a API do PixGo.org
$url = 'https://pixgo.org/v2/orders/pix'; 

$baseUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$webhookUrl = $baseUrl . "/webhook.php";

$data = [
    'amount' => $amount,
    'description' => 'Recarga de Saldo - Pix Anônimo',
    'webhook_url' => $webhookUrl,
    'external_id' => 'user_' . $userId . '_' . time()
];

// Simulação de resposta se não houver API KEY
if (PIXGO_API_KEY === 'SUA_API_KEY_AQUI') {
    $pixId = 'sim_' . time();
    $qrCode = '000201...';
    $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TESTE';
    
    // Salvar transação no banco
    $ins = $pdo->prepare("INSERT INTO transactions (user_id, amount_brl, amount_net_brl, pix_id, status) VALUES (?, ?, ?, ?, 'pending')");
    $ins->execute([$userId, $amount, $netAmount, $pixId]);

    echo json_encode([
        'status' => 'success',
        'qrCodeImage' => $qrImage,
        'message' => 'Simulação ativa. Configure a API KEY real para processar.'
    ]);
    exit;
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
    $ins = $pdo->prepare("INSERT INTO transactions (user_id, amount_brl, amount_net_brl, pix_id, status) VALUES (?, ?, ?, ?, 'pending')");
    $ins->execute([$userId, $amount, $netAmount, $pixId]);

    echo json_encode([
        'success' => true,
        'pix_id' => $pixId,
        'qrCodeImage' => $qrImage
    ]);
} else {
    echo json_encode([
        'error' => 'Erro na API PixGo',
        'detail' => $res,
        'code' => $httpCode
    ]);
}
?>
