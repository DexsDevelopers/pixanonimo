<?php
require_once 'includes/db.php';
require_once 'includes/PushService.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    PushService::notifyUser(
        $userId,
        '🎉 Notificações Ativadas!',
        'Tudo certo! Você receberá alertas de pagamentos, saques e avisos importantes em tempo real.',
        'success'
    );
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
