<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $liquid = $_POST['liquid_address'] ?? '';

    // Verificar se o email já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: register.php?error=exists");
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password, liquid_address, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$email, $hash, $liquid]);

    header("Location: login.php?registered=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - PixAnônimo</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; height: 100vh;">
    <div class="card glass" style="width: 100%; max-width: 450px;">
        <h2>Criar Conta</h2>
        <?php if(isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
            <p style="color: var(--danger);">Este email já está cadastrado.</p>
        <?php endif; ?>
        <form action="register.php" method="POST">
            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="input-group">
                <label>Senha</label>
                <input type="password" name="password" required>
            </div>
            <div class="input-group">
                <label>Endereço Liquid (Opcional agora)</label>
                <input type="text" name="liquid_address" placeholder="lq1q...">
            </div>
            <button type="submit" class="btn-primary">Cadastrar</button>
        </form>
        <p style="margin-top: 1rem; text-align: center; color: var(--text-dim);">
            Já tem conta? <a href="login.php" style="color: var(--primary);">Entre aqui</a>
        </p>
    </div>
</body>
</html>
