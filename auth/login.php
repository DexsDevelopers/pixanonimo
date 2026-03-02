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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css?v=2.2">
    <title>Ghost Pix - Login</title>
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem;">
    <div class="card glass" style="width: 100%; max-width: 400px; text-align: center;">
        <img src="../ghost.jfif" style="width: 80px; height: 80px; border-radius: 12px; margin-bottom: 1.5rem; object-fit: cover;">
        <h2>Login</h2>
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
