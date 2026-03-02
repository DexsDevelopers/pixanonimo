<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$fullName = $input['full_name'] ?? '';
$pixKey = $input['pix_key'] ?? '';
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (empty($fullName) || empty($pixKey) || empty($currentPassword)) {
    echo json_encode(['error' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

// Verificar senha atual
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password'])) {
    echo json_encode(['error' => 'Senha atual incorreta.']);
    exit;
}

try {
    // Iniciar Transação
    $pdo->beginTransaction();

    // Atualizar dados básicos
    $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, pix_key = ? WHERE id = ?");
    $updateStmt->execute([$fullName, $pixKey, $userId]);

    // Atualizar senha se fornecida
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 6) {
            echo json_encode(['error' => 'A nova senha deve ter pelo menos 6 caracteres.']);
            exit;
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $passStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $passStmt->execute([$hash, $userId]);
    }

    $pdo->commit();
    
    // Atualizar sessão
    $_SESSION['full_name'] = $fullName;

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['error' => 'Erro interno ao salvar: ' . $e->getMessage()]);
}
