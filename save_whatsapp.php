<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$phone = preg_replace('/\D/', '', trim($data['whatsapp'] ?? ''));

if (strlen($phone) < 10 || strlen($phone) > 15) {
    echo json_encode(['success' => false, 'error' => 'Número inválido. Informe DDD + número.']);
    exit;
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN whatsapp VARCHAR(20) DEFAULT NULL");
} catch (PDOException $e) {}

$stmt = $pdo->prepare("UPDATE users SET whatsapp = ? WHERE id = ?");
$stmt->execute([$phone, $_SESSION['user_id']]);

write_log('INFO', 'WhatsApp salvo', ['user_id' => $_SESSION['user_id'], 'phone' => $phone]);

echo json_encode(['success' => true]);
