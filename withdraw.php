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

$stmt = $pdo->prepare("SELECT balance, pix_key FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($amount <= 0 || $amount > $user['balance']) {
    echo json_encode(['error' => 'Saldo insuficiente ou valor inválido.']);
    exit;
}

if (!$user['pix_key']) {
    echo json_encode(['error' => 'Configure sua chave PIX antes de sacar.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Debitar do saldo virtual
    $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $stmt->execute([$amount, $userId]);

    // 2. Registrar pedido de saque
    $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, pix_key, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$userId, $amount, $user['pix_key']]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Solicitação de saque enviada ao administrador!']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Erro ao processar saque: ' . $e->getMessage()]);
}
?>
