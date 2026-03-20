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
$withdrawMethod = strip_tags(trim($input['withdraw_method'] ?? 'pix'));
$cryptoAddress = strip_tags(trim($input['crypto_address'] ?? ''));
$cryptoNetwork = strip_tags(trim($input['crypto_network'] ?? ''));
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

// Validar método
if (!in_array($withdrawMethod, ['pix', 'btc', 'usdt'])) {
    $withdrawMethod = 'pix';
}

if (empty($fullName)) {
    echo json_encode(['error' => 'Nome é obrigatório.']);
    exit;
}

// Validar campos conforme método
if ($withdrawMethod === 'pix' && empty($pixKey)) {
    echo json_encode(['error' => 'Chave PIX é obrigatória para recebimento via PIX.']);
    exit;
}
if (($withdrawMethod === 'btc' || $withdrawMethod === 'usdt') && empty($cryptoAddress)) {
    echo json_encode(['error' => 'Endereço de criptomoeda é obrigatório.']);
    exit;
}
if (($withdrawMethod === 'btc' || $withdrawMethod === 'usdt') && empty($cryptoNetwork)) {
    echo json_encode(['error' => 'Selecione a rede (network) da criptomoeda.']);
    exit;
}

// Auto-criar colunas de crypto se não existirem
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN withdraw_method VARCHAR(10) DEFAULT 'pix'");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN crypto_address VARCHAR(255) DEFAULT ''");
} catch (PDOException $e) {}
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN crypto_network VARCHAR(20) DEFAULT ''");
} catch (PDOException $e) {}

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
    // Atualizar dados básicos + método de saque
    $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, pix_key = ?, withdraw_method = ?, crypto_address = ?, crypto_network = ? WHERE id = ?");
    $updateStmt->execute([$fullName, $pixKey, $withdrawMethod, $cryptoAddress, $cryptoNetwork, $userId]);

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
        
    // Atualizar sessão
    $_SESSION['full_name'] = $fullName;
    error_log("Profile Update Success for User $userId: Name=$fullName");

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Profile Update Error (User ID: $userId): " . $e->getMessage());
    echo json_encode(['error' => 'Erro ao salvar no banco: ' . $e->getMessage()]);
}
?>
