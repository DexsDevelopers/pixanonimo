<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#000000">
    <link rel="manifest" href="manifest.json">
    <title>Ghost Pix - Perfil</title>
    <link rel="stylesheet" href="style.css?v=9.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="mobile-header">
        <div class="logo">
            <img src="logo_premium.png?v=8.0" class="logo-img" alt="Ghost Logo">
            <span class="logo-text">Ghost<span> Pix</span></span>
        </div>
        <button class="menu-toggle" id="menu-toggle">☰</button>
    </div>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="logo_premium.png?v=8.0" class="logo-img" alt="Ghost Logo">
                <span class="logo-text">Ghost<span> Pix</span></span>
            </div>
            <nav class="nav-menu">
                <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
                <a href="sacar.php" class="nav-item">💸 Sacar</a>
                <a href="perfil.php" class="nav-item active">👤 Perfil</a>
                <a href="suporte.php" class="nav-item">🎧 Suporte</a>
                <?php if(isAdmin()): ?>
                    <a href="admin/index.php" class="nav-item">🛡️ Admin</a>
                <?php endif; ?>
                <a href="auth/logout.php" class="nav-item">🚪 Sair</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="avatar" style="overflow: hidden; border: 1.5px solid var(--border-h);">
                        <img src="logo_premium.png?v=8.0" class="avatar-img" alt="Avatar">
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Usuário'); ?></span>
                        <span class="user-status">Conta Ativa</span>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <h1>Meu Perfil</h1>
                <a href="dashboard.php" class="badge sent" style="text-decoration:none">Voltar</a>
            </header>

            <div style="max-width: 600px; margin: 0 auto;">
                <div class="card">
                    <form id="profile-form">
                        <div class="card-header" style="margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                            <div class="card-title-group">
                                <div class="card-icon"><i class="fas fa-user-edit"></i></div>
                                <h3 class="card-title">Dados Pessoais</h3>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Email (Protegido)</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled 
                                   style="width: 100%; background: rgba(255,255,255,0.02); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text-2); cursor: not-allowed; font-family: var(--font);">
                        </div>

                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Nome Completo</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required
                                   style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text); font-family: var(--font); transition: border-color 0.2s;">
                        </div>

                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Chave PIX</label>
                            <input type="text" name="pix_key" value="<?php echo htmlspecialchars($user['pix_key']); ?>" required
                                   style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text); font-family: 'SF Mono', monospace; transition: border-color 0.2s;">
                        </div>

                        <div class="card-header" style="margin-top: 3rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
                            <div class="card-title-group">
                                <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                                <h3 class="card-title">Segurança</h3>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Senha Atual</label>
                            <input type="password" name="current_password" placeholder="Para validar alterações"
                                   style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text); font-family: var(--font);">
                        </div>

                        <div style="margin-bottom: 1.2rem;">
                            <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Nova Senha (Opcional)</label>
                            <input type="password" name="new_password" placeholder="Mínimo 6 caracteres"
                                   style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem 1rem; border-radius: 10px; color: var(--text); font-family: var(--font);">
                        </div>

                        <button type="submit" class="btn-primary" id="btn-save-profile" style="margin-top: 2rem;">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav">
        <a href="dashboard.php" class="mobile-nav-item">
            <i class="fas fa-th-large"></i>
            <span>Home</span>
        </a>
        <a href="sacar.php" class="mobile-nav-item">
            <i class="fas fa-wallet"></i>
            <span>Sacar</span>
        </a>
        <a href="perfil.php" class="mobile-nav-item active">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
        <a href="suporte.php" class="mobile-nav-item">
            <i class="fas fa-headset"></i>
            <span>Suporte</span>
        </a>
        <a href="auth/logout.php" class="mobile-nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Sair</span>
        </a>
    </nav>

    <script src="script.js?v=5.1"></script>
    <script>
    document.getElementById('profile-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = document.getElementById('btn-save-profile');
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        // A senha atual só será validada no backend se o usuário tentar mudar a senha
        btn.innerText = 'Salvando...';
        btn.disabled = true;

        try {
            const res = await fetch('update_profile.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            });

            const text = await res.text();
            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                throw new Error('Resposta inválida do servidor: ' + text.substring(0, 100));
            }

            if (result.success) {
                alert('Perfil atualizado com sucesso!');
                location.reload();
            } else {
                alert(result.error || 'Erro ao atualizar perfil.');
            }
        } catch (err) {
            alert('Erro: ' + err.message);
        } finally {
            btn.innerText = 'Salvar Alterações';
            btn.disabled = false;
        }
    });
    </script>
    <script src="script.js?v=5.1"></script>
</body>
</html>
