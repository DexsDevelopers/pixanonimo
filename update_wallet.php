<?php
session_start();
require_once 'includes/db.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validação CSRF
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');
check_csrf($csrfToken);

$wallet = $data['wallet'] ?? '';

if (empty($wallet)) {
    echo json_encode(['error' => 'Endereço inválido']);
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("UPDATE users SET pix_key = ? WHERE id = ?");
if ($stmt->execute([$wallet, $userId])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Erro ao atualizar chave PIX.']);
}
?>

