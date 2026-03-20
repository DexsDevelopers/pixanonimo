<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/MailService.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Buscar dados do usuário
$stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Usuário não encontrado']);
    exit;
}

// Rate limit: máximo 1 reset a cada 2 minutos
$cacheKey = 'pwd_reset_' . $userId;
if (isset($_SESSION[$cacheKey]) && (time() - $_SESSION[$cacheKey]) < 120) {
    $remaining = 120 - (time() - $_SESSION[$cacheKey]);
    echo json_encode(['success' => false, 'error' => "Aguarde {$remaining}s antes de solicitar novamente."]);
    exit;
}

// Gerar token de reset (válido por 30 minutos)
$token = bin2hex(random_bytes(32));
$expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

// Salvar token no banco
try {
    // Verificar se coluna existe, se não criar
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(255) NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_expires DATETIME NULL");
    } catch (Exception $e) {
        // Colunas já existem, continuar
    }

    $stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
    $stmt->execute([$token, $expiry, $userId]);
} catch (Exception $e) {
    write_log('ERROR', 'Erro ao salvar token de reset', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'error' => 'Erro interno. Tente novamente.']);
    exit;
}

// Montar link de reset
$resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/auth/reset_password.php?token=" . $token;

// Enviar email
$emailBody = "
    <h3 style='color: #fff; margin-bottom: 15px;'>Olá, {$user['full_name']}!</h3>
    <p style='color: #ccc;'>Você solicitou a alteração da sua senha na Ghost Pix.</p>
    <p style='color: #ccc;'>Clique no botão abaixo para definir uma nova senha:</p>
    <div style='text-align: center; margin: 30px 0;'>
        <a href='{$resetLink}' style='display: inline-block; background: #4ade80; color: #000; font-weight: bold; padding: 14px 32px; border-radius: 12px; text-decoration: none; font-size: 16px;'>
            Redefinir Minha Senha
        </a>
    </div>
    <p style='color: #999; font-size: 13px;'>Este link expira em <strong>30 minutos</strong>.</p>
    <p style='color: #999; font-size: 13px;'>Se você não solicitou esta alteração, ignore este e-mail.</p>
    <p style='color: #555; font-size: 11px; margin-top: 20px;'>Link direto: {$resetLink}</p>
";

$sent = MailService::send($user['email'], 'Ghost Pix — Redefinição de Senha', $emailBody);

if ($sent) {
    $_SESSION[$cacheKey] = time();
    write_log('INFO', 'Email de reset de senha enviado', ['user_id' => $userId, 'email' => $user['email']]);
    echo json_encode([
        'success' => true,
        'message' => 'E-mail enviado para ' . substr($user['email'], 0, 3) . '***' . substr($user['email'], strpos($user['email'], '@'))
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Falha ao enviar e-mail. Verifique se seu e-mail está correto.']);
}
