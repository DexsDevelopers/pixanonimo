<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');
check_csrf($csrfToken);

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'generate';

if ($action === 'status') {
    $stmt = $pdo->prepare("SELECT telegram_chat_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    echo json_encode([
        'success' => true,
        'connected' => !empty($user['telegram_chat_id']),
        'chat_id' => $user['telegram_chat_id'] ?? null
    ]);
    exit;
}

if ($action === 'disconnect') {
    $pdo->prepare("UPDATE users SET telegram_chat_id = NULL, telegram_link_token = NULL, telegram_link_expires = NULL WHERE id = ?")->execute([$userId]);
    echo json_encode(['success' => true, 'message' => 'Telegram desconectado com sucesso.']);
    exit;
}

// Generate link token
$token = bin2hex(random_bytes(16));
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$pdo->prepare("UPDATE users SET telegram_link_token = ?, telegram_link_expires = ? WHERE id = ?")
    ->execute([$token, $expires, $userId]);

$botUsername = defined('TELEGRAM_USER_BOT_USERNAME') ? TELEGRAM_USER_BOT_USERNAME : 'GhostPixUserBot';

echo json_encode([
    'success' => true,
    'token' => $token,
    'expires_in' => 600,
    'bot_url' => "https://t.me/{$botUsername}?start={$token}",
    'bot_username' => $botUsername
]);
