<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$email = $_GET['email'] ?? '';
if (empty($email)) {
    echo json_encode(['error' => 'Informe ?email=xxx']);
    exit;
}

// Verificar definição da coluna password
$colInfo = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'password'")->fetch();

// Buscar usuário
$stmt = $pdo->prepare("SELECT id, email, password, LENGTH(password) as pwd_len FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'Usuário não encontrado', 'column_type' => $colInfo['Type']]);
    exit;
}

echo json_encode([
    'user_id' => $user['id'],
    'email' => $user['email'],
    'column_type' => $colInfo['Type'],
    'hash_length' => $user['pwd_len'],
    'hash_prefix' => substr($user['password'], 0, 10),
    'hash_looks_valid' => (substr($user['password'], 0, 4) === '$2y$' && strlen($user['password']) === 60),
    'full_hash_length_php' => strlen($user['password'])
]);
