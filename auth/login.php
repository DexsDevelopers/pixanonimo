<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] == 'blocked') {
            die("Sua conta está bloqueada.");
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        redirect('../index.php');
    } else {
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
    <link rel="stylesheet" href="../style.css?v=5.1">
    <title>Ghost Pix - Login</title>
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; background-color: #000; background-image: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.05) 0%, transparent 70%);">
    <div class="card glass" style="width: 100%; max-width: 420px; padding: 3rem 2rem; border: 1px solid var(--glass-border); background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px);">
        <img src="../ghost.jpg?v=5.0" style="width: 100px; height: 100px; border-radius: 20px; margin-bottom: 2rem; object-fit: cover; border: 1px solid var(--glass-border);">
        <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">Ghost Pix</h2>
        <p style="color: var(--text-dim); margin-bottom: 2rem;">Acesse sua carteira blindada</p>
        <?php if(isset($_GET['error'])): ?>
            <p style="color: var(--danger);">Email ou senha incorretos.</p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-primary">Entrar</button>
        </form>
        <p style="margin-top: 1rem; text-align: center; color: var(--text-dim);">
            Não tem uma conta? <a href="register.php" style="color: var(--primary);">Cadastre-se</a>
        </p>
    </div>
</body>
</html>
