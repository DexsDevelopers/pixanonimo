<?php
session_start();
require_once '../includes/db.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Validar token
if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id, full_name, password_reset_expires FROM users WHERE password_reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Link inválido ou expirado.';
    } elseif (strtotime($user['password_reset_expires']) < time()) {
        $error = 'Este link expirou. Solicite um novo na página de configurações.';
        // Limpar token expirado
        $pdo->prepare("UPDATE users SET password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?")->execute([$user['id']]);
    }
} else {
    $error = 'Token não fornecido.';
}

// Processar nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 6) {
        $error = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'As senhas não coincidem.';
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);
        
        write_log('INFO', 'Senha alterada via reset', ['user_id' => $user['id']]);
        $success = 'Senha alterada com sucesso! Você já pode fazer login.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Pix — Redefinir Senha</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #050505;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 440px;
            background: #111;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 32px;
            padding: 48px 40px;
            position: relative;
            overflow: hidden;
        }
        .container::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 300px;
            height: 300px;
            background: rgba(74, 222, 128, 0.05);
            border-radius: 50%;
            filter: blur(80px);
            pointer-events: none;
        }
        .logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .logo span {
            font-weight: 900;
            font-size: 24px;
            letter-spacing: -0.5px;
        }
        .logo .accent { color: #4ade80; font-style: italic; }
        h2 {
            font-size: 22px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 8px;
        }
        .subtitle {
            text-align: center;
            color: rgba(255,255,255,0.35);
            font-size: 13px;
            margin-bottom: 32px;
        }
        label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            color: rgba(255,255,255,0.25);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            margin-left: 4px;
        }
        input[type="password"] {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 16px;
            padding: 16px 20px;
            color: #fff;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 600;
            outline: none;
            transition: border-color 0.3s;
            margin-bottom: 20px;
        }
        input[type="password"]:focus {
            border-color: rgba(74, 222, 128, 0.4);
        }
        .btn {
            width: 100%;
            padding: 16px;
            background: #4ade80;
            color: #000;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            border: none;
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover { filter: brightness(1.1); transform: scale(1.02); }
        .btn:active { transform: scale(0.98); }
        .btn-outline {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
        }
        .btn-outline:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 14px 20px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 24px;
        }
        .success {
            background: rgba(74, 222, 128, 0.1);
            border: 1px solid rgba(74, 222, 128, 0.2);
            color: #4ade80;
            padding: 14px 20px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 24px;
        }
        .link { display: block; text-align: center; margin-top: 20px; }
        .link a {
            color: rgba(255,255,255,0.3);
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .link a:hover { color: #4ade80; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span>GHOST<span class="accent">PIX</span></span>
        </div>

        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
            <a href="/login" class="btn btn-outline" style="display:block; text-align:center; text-decoration:none; margin-top:16px;">Ir para Login</a>

        <?php elseif (!empty($error) && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
            <h2>Link Inválido</h2>
            <p class="subtitle"><?php echo htmlspecialchars($error); ?></p>
            <a href="/config" class="btn btn-outline" style="display:block; text-align:center; text-decoration:none;">Voltar às Configurações</a>

        <?php else: ?>
            <h2>Redefinir Senha</h2>
            <p class="subtitle">Olá, <?php echo htmlspecialchars($user['full_name']); ?>! Escolha sua nova senha.</p>

            <?php if (!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="?token=<?php echo htmlspecialchars($token); ?>">
                <label>Nova Senha</label>
                <input type="password" name="new_password" placeholder="Mínimo 6 caracteres" required minlength="6" autofocus>

                <label>Confirmar Senha</label>
                <input type="password" name="confirm_password" placeholder="Repita a nova senha" required minlength="6">

                <button type="submit" class="btn">Salvar Nova Senha</button>
            </form>

            <div class="link">
                <a href="/login">Voltar ao Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
