<?php
require_once '../includes/db.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Validar token
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        echo json_encode(['valid' => false, 'error' => 'Token não informado.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, email, full_name, password_reset_expires FROM users WHERE password_reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['valid' => false, 'error' => 'Link inválido ou já utilizado.']);
        exit;
    }

    if (strtotime($user['password_reset_expires']) < time()) {
        echo json_encode(['valid' => false, 'error' => 'Este link expirou. Solicite um novo.']);
        exit;
    }

    echo json_encode(['valid' => true, 'name' => $user['full_name']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? '';
    $newPassword = $input['new_password'] ?? '';

    if (empty($token) || empty($newPassword)) {
        echo json_encode(['success' => false, 'error' => 'Token e nova senha são obrigatórios.']);
        exit;
    }

    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'error' => 'A senha deve ter pelo menos 6 caracteres.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, email, password_reset_expires FROM users WHERE password_reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Link inválido ou já utilizado.']);
        exit;
    }

    if (strtotime($user['password_reset_expires']) < time()) {
        echo json_encode(['success' => false, 'error' => 'Este link expirou. Solicite um novo.']);
        exit;
    }

    // Atualizar senha e limpar token
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL, must_reset_password = 0 WHERE id = ?")
        ->execute([$hash, $user['id']]);

    write_log('INFO', 'Senha redefinida via email', ['user_id' => $user['id'], 'email' => $user['email']]);

    echo json_encode(['success' => true, 'message' => 'Senha atualizada com sucesso!']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Método não permitido']);
