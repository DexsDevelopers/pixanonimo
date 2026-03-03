<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

// Validação CSRF
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');
check_csrf($csrfToken);

$transactionId = $input['id'] ?? 0;

if (!$transactionId) {
    echo json_encode(['error' => 'ID da transação inválido']);
    exit;
}

// Verificar se a transação pertence ao usuário
$stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
$result = $stmt->execute([$transactionId, $userId]);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Não foi possível excluir a transação']);
}
?>
