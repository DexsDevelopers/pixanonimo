<?php
require_once 'config.php';

header('Content-Type: application/json');

// Receber dados do frontend
$input = json_decode(file_get_contents('php://input'), true);
$amount = $input['amount'] ?? 0;
$wallet = $input['wallet'] ?? DEFAULT_LIQUID_ADDRESS;

if ($amount <= 0) {
    echo json_encode(['error' => 'Valor inválido']);
    exit;
}

// Preparar chamada para a API do PixGo.org
// Nota: Os endpoints exatos podem variar dependendo da versão da API do PixGo
$url = 'https://pixgo.org/v2/orders/pix'; 

$data = [
    'totalAmount' => (float)$amount,
    'paymentMethod' => 'pix',
    'liquidAddress' => $wallet, // Informação crucial para o PixGo saber para onde enviar o DEPIX
    'items' => [
        [
            'title' => 'Recebimento via Sistema Externo',
            'quantity' => 1,
            'unitPrice' => (float)$amount
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . PIXGO_API_KEY,
    'Content-Type: application/json'
]);

// Para fins de teste/demo, se não houver API Key, vamos simular uma resposta
if (PIXGO_API_KEY === 'SUA_API_KEY_AQUI') {
    echo json_encode([
        'status' => 'success',
        'id' => 'simulated_' . time(),
        'qrCode' => '00020101021126580014br.gov.bcb.pix013666d6c90c-99d1-4d7a-8777-666666666666...',
        'qrCodeImage' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PIX_SIMULADO',
        'message' => 'AVISO: Configure sua API KEY no config.php para funcionar realmente.'
    ]);
    exit;
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo $response;
} else {
    echo json_encode([
        'error' => 'Erro na API PixGo',
        'detail' => json_decode($response, true),
        'code' => $httpCode
    ]);
}
?>
