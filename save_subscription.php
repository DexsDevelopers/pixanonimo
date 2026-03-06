<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['endpoint'])) {
    $userId = $_SESSION['user_id'];
    $endpoint = $data['endpoint'];
    $p256dh = $data['keys']['p256dh'] ?? '';
    $auth = $data['keys']['auth'] ?? '';

    // Evitar duplicidade
    $stmt = $pdo->prepare("SELECT id FROM push_subscriptions WHERE endpoint = ? AND user_id = ?");
    $stmt->execute([$endpoint, $userId]);
    
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
        $ins->execute([$userId, $endpoint, $p256dh, $auth]);
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Assinatura inválida']);
}
?>
