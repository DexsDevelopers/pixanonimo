<?php
require_once 'includes/db.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$resetToken = $input['reset_token'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (empty($resetToken) || empty($newPassword)) {
    echo json_encode(['success' => false, 'error' => 'Token e nova senha são obrigatórios.']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'error' => 'A senha deve ter pelo menos 6 caracteres.']);
    exit;
}

// Buscar usuário pelo token
$stmt = $pdo->prepare("SELECT id, email FROM users WHERE remember_token = ? AND must_reset_password = 1");
$stmt->execute([$resetToken]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Token inválido ou expirado. Tente fazer login novamente.']);
    exit;
}

// Atualizar senha e limpar flag
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password = ?, must_reset_password = 0, remember_token = NULL WHERE id = ?")
    ->execute([$hash, $user['id']]);

write_log('INFO', 'Senha redefinida via force reset', ['user_id' => $user['id'], 'email' => $user['email']]);

echo json_encode(['success' => true, 'message' => 'Senha atualizada com sucesso! Faça login com sua nova senha.']);
