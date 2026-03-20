<?php
require_once '../includes/db.php';

$isJsonRequest = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
if ($isJsonRequest) {
    error_reporting(0);
    ini_set('display_errors', 0);
    header('Content-Type: application/json');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validação CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    check_csrf($csrfToken);

    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Debug log para diagnóstico de login
    write_log('DEBUG', 'Tentativa de login', [
        'email' => $email,
        'user_found' => $user ? true : false,
        'password_length' => strlen($password),
        'hash_exists' => $user ? (!empty($user['password'])) : false,
        'hash_prefix' => $user ? substr($user['password'], 0, 7) : 'N/A',
        'verify_result' => $user ? password_verify($password, $user['password']) : false
    ]);

    // Verificar se precisa resetar a senha (antes de verificar senha)
    if ($user && ($user['must_reset_password'] ?? 0)) {
        if ($isJsonRequest) {
            // Gerar token temporário para o reset
            $resetToken = bin2hex(random_bytes(16));
            $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$resetToken, $user['id']]);
            echo json_encode(['success' => false, 'must_reset_password' => true, 'reset_token' => $resetToken, 'error' => 'Sua senha foi resetada. Crie uma nova senha.']);
            exit;
        }
    }

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] == 'blocked') {
            if ($isJsonRequest) {
                echo json_encode(['success' => false, 'error' => 'Sua conta está bloqueada.']);
                exit;
            }
            die("Sua conta está bloqueada.");
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['is_admin'] = $user['is_admin'];

        // Gerar token de "Lembrar-me" (Cookie de 30 dias)
        $token = bin2hex(random_bytes(32));
        $updateToken = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $updateToken->execute([$token, $user['id']]);
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
        
        if ($isJsonRequest) {
            echo json_encode(['success' => true]);
            exit;
        }
        redirect('../dashboard.php');
    } else {
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            echo json_encode(['success' => false, 'error' => 'Email ou senha incorretos.']);
            exit;
        }
        header("Location: login.php?error=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <link rel="stylesheet" href="../style.css?v=125.0">
    <title>Ghost Pix - Login</title>
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; background-color: var(--bg); background-image: radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.05) 0%, transparent 70%);">
    <div class="card" style="width: 100%; max-width: 420px; padding: 3rem 2rem; border: 1px solid var(--border); background: var(--bg-card); backdrop-filter: blur(24px); border-radius: var(--r-lg); animation: fadeInUp 0.6s var(--ease);">
        <div style="text-align: center; margin-bottom: 2.5rem;">
            <img src="../logo_premium.png?v=107.0" style="width: 90px; height: 90px; border-radius: 22px; margin-bottom: 1.5rem; object-fit: cover; border: 1px solid var(--border-h); box-shadow: 0 12px 40px rgba(0,0,0,0.6);">
            <h2 style="font-size: 2.4rem; font-weight: 800; letter-spacing: -1.5px; margin-bottom: 0.2rem;">Ghost Pix</h2>
            <p style="color: var(--text-2); font-size: 0.95rem; font-weight: 500;">Acesse sua carteira blindada</p>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--red); padding: 0.8rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.85rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-exclamation-circle"></i> Email ou senha incorretos.
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['registered'])): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--green); padding: 0.8rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.85rem; text-align: center; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-check-circle"></i> Cadastro realizado! Faça login.
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-2); margin-bottom: 0.6rem; margin-left: 0.2rem;">Email</label>
                <div style="position: relative;">
                    <i class="fas fa-envelope" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-2); font-size: 0.9rem;"></i>
                    <input type="email" name="email" required placeholder="seu@email.com" 
                           style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 1rem 1rem 1rem 2.8rem; border-radius: 12px; color: var(--text); font-size: 1rem; font-family: var(--font); transition: all 0.2s;">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-2); margin-bottom: 0.6rem; margin-left: 0.2rem;">Senha</label>
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-2); font-size: 0.9rem;"></i>
                    <input type="password" name="password" required placeholder="••••••••" 
                           style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 1rem 1rem 1rem 2.8rem; border-radius: 12px; color: var(--text); font-size: 1rem; font-family: var(--font); transition: all 0.2s;">
                </div>
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 0.5rem;">
                Entrar na Conta <i class="fas fa-arrow-right" style="font-size: 0.8rem; margin-left: 0.3rem;"></i>
            </button>
        </form>

        <div style="margin-top: 2rem; text-align: center; border-top: 1px solid var(--border); padding-top: 1.5rem;">
            <p style="color: var(--text-2); font-size: 0.9rem;">
                Não tem uma conta? <a href="register.php" style="color: var(--text); font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--border-h);">Cadastre-se</a>
            </p>
        </div>
    </div>
</body>
</html>

