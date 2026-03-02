<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../includes/db.php';

if (isLoggedIn()) {
    header("Location: ../dashboard.php");
    exit;
}

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Ghost Pix - Cadastro</title>
    <link rel="stylesheet" href="../style.css?v=9.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; background-color: var(--bg); background-image: radial-gradient(circle at 50% 50%, rgba(16, 185, 129, 0.05) 0%, transparent 70%);">
    <div class="card" style="width: 100%; max-width: 480px; padding: 2.5rem; border: 1px solid var(--border); background: var(--bg-card); backdrop-filter: blur(24px); border-radius: var(--r-lg); animation: fadeInUp 0.6s var(--ease);">
        <div style="text-align: center; margin-bottom: 2rem;">
            <img src="../logo_premium.png?v=8.0" style="width: 80px; height: 80px; border-radius: 20px; margin: 0 auto 1.2rem; display: block; object-fit: cover; border: 1px solid var(--border-h); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <h2 style="font-size: 2rem; font-weight: 800; letter-spacing: -1.2px; margin-bottom: 0.3rem;">Criar Conta</h2>
            <p style="color: var(--text-2); font-size: 0.9rem; font-weight: 500;">Junte-se à rede Ghost Pix</p>
        </div>

        <?php if(isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--red); padding: 0.8rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.85rem; text-align: center;">
                <i class="fas fa-exclamation-circle"></i> Este email já está cadastrado.
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-2); margin-bottom: 0.5rem; margin-left: 0.2rem;">Nome Completo</label>
                <div style="position: relative;">
                    <i class="fas fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-2); font-size: 0.85rem;"></i>
                    <input type="text" name="full_name" required placeholder="Como no seu banco" 
                           style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.9rem 1rem 0.9rem 2.6rem; border-radius: 11px; color: var(--text); font-size: 0.95rem; font-family: var(--font); transition: all 0.2s;">
                </div>
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-2); margin-bottom: 0.5rem; margin-left: 0.2rem;">Email</label>
                <div style="position: relative;">
                    <i class="fas fa-envelope" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-2); font-size: 0.85rem;"></i>
                    <input type="email" name="email" required placeholder="seu@email.com" 
                           style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.9rem 1rem 0.9rem 2.6rem; border-radius: 11px; color: var(--text); font-size: 0.95rem; font-family: var(--font); transition: all 0.2s;">
                </div>
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-2); margin-bottom: 0.5rem; margin-left: 0.2rem;">Chave PIX (CPF/Email)</label>
                <div style="position: relative;">
                    <i class="fas fa-key" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-2); font-size: 0.85rem;"></i>
                    <input type="text" name="pix_key" required placeholder="Sua chave para saques" 
                           style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.9rem 1rem 0.9rem 2.6rem; border-radius: 11px; color: var(--text); font-size: 0.95rem; font-family: var(--font); transition: all 0.2s;">
                </div>
            </div>

            <div style="margin-bottom: 1.8rem;">
                <label style="display: block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-2); margin-bottom: 0.5rem; margin-left: 0.2rem;">Senha</label>
                <div style="position: relative;">
                    <i class="fas fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-2); font-size: 0.85rem;"></i>
                    <input type="password" name="password" required placeholder="Mínimo 6 caracteres" 
                           style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.9rem 1rem 0.9rem 2.6rem; border-radius: 11px; color: var(--text); font-size: 0.95rem; font-family: var(--font); transition: all 0.2s;">
                </div>
            </div>

            <button type="submit" class="btn-primary">
                Criar Minha Conta <i class="fas fa-paper-plane" style="font-size: 0.8rem; margin-left: 0.4rem;"></i>
            </button>
        </form>

        <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid var(--border); padding-top: 1.25rem;">
            <p style="color: var(--text-2); font-size: 0.88rem;">
                Já tem uma conta? <a href="login.php" style="color: var(--text); font-weight: 700; text-decoration: none; border-bottom: 1px solid var(--border-h);">Entre aqui</a>
            </p>
        </div>
    </div>
</body>
</html>
