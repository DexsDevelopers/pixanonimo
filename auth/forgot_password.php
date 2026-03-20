<?php
require_once '../includes/db.php';
require_once '../includes/MailService.php';

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
check_csrf($csrfToken);

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Informe seu e-mail.']);
    exit;
}

// Auto-criar coluna se não existir
try { $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(64) DEFAULT NULL"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN password_reset_expires DATETIME DEFAULT NULL"); } catch (PDOException $e) {}

// Buscar usuário
$stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Sempre retornar sucesso para não revelar se o email existe
if (!$user) {
    write_log('INFO', 'Forgot password - email não encontrado', ['email' => $email]);
    echo json_encode(['success' => true, 'message' => 'Se este e-mail estiver cadastrado, você receberá um link para redefinir sua senha.']);
    exit;
}

// Gerar token e expiração (1 hora)
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

$pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?")
    ->execute([$token, $expires, $user['id']]);

// Montar link de reset
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$resetLink = $baseUrl . '/reset-password/' . $token;

// Enviar email
$emailBody = "
    <h2 style='color: #fff; margin-bottom: 10px;'>Olá, {$user['full_name']}!</h2>
    <p style='color: #ccc;'>Recebemos uma solicitação para redefinir sua senha.</p>
    <p style='color: #ccc;'>Clique no botão abaixo para criar uma nova senha:</p>
    <div style='text-align: center; margin: 30px 0;'>
        <a href='{$resetLink}' style='display: inline-block; background: #4ade80; color: #000; font-weight: bold; padding: 14px 40px; border-radius: 50px; text-decoration: none; font-size: 16px;'>
            Redefinir Minha Senha
        </a>
    </div>
    <p style='color: #777; font-size: 13px;'>Este link expira em <strong>1 hora</strong>.</p>
    <p style='color: #777; font-size: 13px;'>Se você não solicitou esta alteração, ignore este e-mail.</p>
    <p style='color: #555; font-size: 11px; margin-top: 20px;'>Link direto: {$resetLink}</p>
";

$sent = MailService::send($user['email'], 'Ghost Pix - Redefinir Senha', $emailBody);

write_log('INFO', 'Forgot password - token gerado', [
    'user_id' => $user['id'],
    'email' => $user['email'],
    'email_sent' => $sent
]);

echo json_encode(['success' => true, 'message' => 'Se este e-mail estiver cadastrado, você receberá um link para redefinir sua senha.']);
