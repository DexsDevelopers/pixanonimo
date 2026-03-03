<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

// Validação CSRF
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');
check_csrf($csrfToken);

// Sanitização de Entradas
$fullName = strip_tags(trim($input['full_name'] ?? ''));
$pixKey = strip_tags(trim($input['pix_key'] ?? ''));
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (empty($fullName) || empty($pixKey)) {
    echo json_encode(['error' => 'Nome e Chave PIX são obrigatórios.']);
    exit;
}

// Buscar dados atuais do usuário
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'Usuário não encontrado.']);
    exit;
}

// Se o usuário quer mudar a senha, a senha atual é obrigatória
if (!empty($newPassword)) {
    if (empty($currentPassword)) {
        echo json_encode(['error' => 'Para mudar a senha, você deve informar a senha atual.']);
        exit;
    }
    if (!password_verify($currentPassword, $user['password'])) {
        echo json_encode(['error' => 'Senha atual incorreta.']);
        exit;
    }
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
            if ($pdo->inTransaction()) $pdo->rollBack();
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
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    // Logar o erro internamente para o desenvolvedor
    error_log("Erro no perfil (User ID: $userId): " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao salvar no banco: ' . $e->getMessage()]);
}
