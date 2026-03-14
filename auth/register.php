<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../includes/db.php';
require_once '../includes/MailService.php';

if (isLoggedIn()) {
    header("Location: ../dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validação CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    check_csrf($csrfToken);

    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $full_name = strip_tags(trim($_POST['full_name'] ?? ''));
    $pix_key = strip_tags(trim($_POST['pix_key'] ?? ''));

    // Verificar se o email já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header("Location: register.php?error=exists");
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check for affiliate cookie
    $affiliateId = null;
    if (isset($_COOKIE['ghost_pix_ref'])) {
        $refToken = $_COOKIE['ghost_pix_ref'];
        $stmtRef = $pdo->prepare("SELECT id FROM users WHERE referral_token = ?");
        $stmtRef->execute([$refToken]);
        $ref = $stmtRef->fetch();
        if ($ref) {
            $affiliateId = $ref['id'];
        }
    }

    // Buscar taxa padrão
    $defTaxStmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'default_user_tax'");
    $defaultTax = (float)($defTaxStmt->fetchColumn() ?: '4.0');

    $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, pix_key, status, affiliate_id, referral_token, commission_rate) VALUES (?, ?, ?, ?, 'approved', ?, ?, ?)");
    $stmt->execute([$email, $hash, $full_name, $pix_key, $affiliateId, bin2hex(random_bytes(8)), $defaultTax]);
    $newUserId = $pdo->lastInsertId();

    // Notificação Interna de Aprovação
    try {
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Conta Aprovada! ✅', 'Sua conta foi verificada e aprovada automaticamente. Já pode começar a operar!', 'success')")
            ->execute([$newUserId]);
    } catch (PDOException $e) {
        write_log('error', 'Falha ao inserir notificação automática no registro: ' . $e->getMessage());
    }

    // Enviar E-mail de Aprovação
    MailService::notifyApproval($email, $full_name);

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
    <link rel="stylesheet" href="../style.css?v=125.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .lp-body { background: #000; font-family: 'Outfit', sans-serif; overflow-x: hidden; }
        .glass-card { background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(20px) saturate(180%); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; }
        .input-glass { background: rgba(255, 255, 255, 0.05) !important; border: 1px solid rgba(255, 255, 255, 0.1) !important; color: #fff !important; transition: all 0.3s ease; }
        .input-glass:focus { border-color: #4ade80 !important; box-shadow: 0 0 15px rgba(74, 222, 128, 0.2); outline: none; }
    </style>
</head>
<body class="lp-body" style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem;">
    <div class="glass-card" style="width: 100%; max-width: 480px; padding: 2.5rem; animation: fadeInUp 0.8s ease-out;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <img src="../logo_premium.png?v=107.0" style="width: 80px; height: 80px; border-radius: 20px; margin: 0 auto 1.2rem; display: block; object-fit: cover; border: 1px solid var(--border-h); box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <h2 style="font-size: 2rem; font-weight: 800; letter-spacing: -1.2px; margin-bottom: 0.3rem;">Criar Conta</h2>
            <p style="color: var(--text-2); font-size: 0.9rem; font-weight: 500;">Junte-se à rede Ghost Pix</p>
        </div>

        <?php if(isset($_GET['error']) && $_GET['error'] == 'exists'): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--red); padding: 0.8rem; border-radius: 10px; margin-bottom: 1.5rem; font-size: 0.85rem; text-align: center;">
                <i class="fas fa-exclamation-circle"></i> Este email já está cadastrado.
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
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
                <label style="display: block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: rgba(255,255,255,0.6); margin-bottom: 0.5rem;">Chave PIX (Qualquer Tipo)</label>
                <div style="position: relative;">
                    <i class="fas fa-key" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.4); font-size: 0.85rem;"></i>
                    <input type="text" name="pix_key" id="pix_key" required placeholder="E-mail, CPF, Telefone ou Chave Aleatória" class="input-glass"
                           style="width: 100%; padding: 0.9rem 1rem 0.9rem 2.6rem; border-radius: 12px; font-size: 0.95rem;">
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

        <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.25rem;">
            <p style="color: rgba(255,255,255,0.6); font-size: 0.88rem;">
                Já tem uma conta? <a href="login.php" style="color: #fff; font-weight: 700; text-decoration: none;">Entre aqui</a>
            </p>
        </div>
    </div>
    <!-- Filtro removido para aceitar todos os tipos de chaves PIX (E-mail, CPF, Telefone, Aleatória) -->
</body>
</html>

