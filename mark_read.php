<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$notifId = (int)($input['id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$notifId) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    // Marcar como lida (garantindo que pertence ao usuário ou é global)
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
    $stmt->execute([$notifId, $userId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
