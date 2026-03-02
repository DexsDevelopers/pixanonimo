<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $pix_key = $_POST['pix_key'] ?? '';

    // Verificar se o email já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: register.php?error=exists");
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, pix_key, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$email, $hash, $full_name, $pix_key]);

    header("Location: login.php?registered=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Pix - Cadastro</title>
    <link rel="stylesheet" href="../style.css?v=2.2">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem;">
    <div class="card glass" style="width: 100%; max-width: 450px; text-align: center;">
        <img src="../ghost.jfif" style="width: 80px; height: 80px; border-radius: 12px; margin-bottom: 1.5rem; object-fit: cover;">
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
                <label>Nome do Recebedor (Como no Banco)</label>
                <input type="text" name="full_name" required placeholder="Ex: João Silva">
            </div>
            <div class="input-group">
                <label>Chave PIX (Para receber saques)</label>
                <input type="text" name="pix_key" placeholder="CPF, Email ou Aleatória" required>
            </div>
            <button type="submit" class="btn-primary">Cadastrar</button>
        </form>
        <p style="margin-top: 1rem; text-align: center; color: var(--text-dim);">
            Já tem conta? <a href="login.php" style="color: var(--primary);">Entre aqui</a>
        </p>
    </div>
</body>
</html>
