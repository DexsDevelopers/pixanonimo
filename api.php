<?php
session_start();
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

// Lógica de Comissionamento do Sistema
$commissionRate = (float)$user['commission_rate'];
$commissionAmount = ($amount * $commissionRate) / 100;
$netAmount = $amount - $commissionAmount;

// Chamada para a API do PixGo.org
$url = 'https://pixgo.org/v2/orders/pix'; 

$data = [
    'totalAmount' => $amount,
    'paymentMethod' => 'pix',
    'liquidAddress' => $wallet, 
    'items' => [
        [
            'title' => 'Pagamento Pix Anônimo',
            'quantity' => 1,
            'unitPrice' => $amount
        ]
    ]
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

// Chamada Real via CURL (Omitida por brevidade, mas segue o mesmo padrão anterior)
?>
