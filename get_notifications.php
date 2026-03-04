<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Buscar notificações específicas do usuário OU globais (NULL) que não foram lidas
    $stmt = $pdo->prepare("
        SELECT id, title, message, type 
        FROM notifications 
        WHERE (user_id = ? OR user_id IS NULL) 
        AND is_read = 0 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();

    echo json_encode(['success' => true, 'notifications' => $notifications]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
