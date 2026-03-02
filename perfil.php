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
    <title>Ghost Pix - Perfil</title>
    <link rel="stylesheet" href="style.css?v=5.1">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="mobile-header">
        <div class="logo" style="margin-bottom: 0;">
            <span class="logo-icon">⚡</span>
            <span class="logo-text" style="font-size: 1.2rem;">Ghost<span> Pix</span></span>
        </div>
        <button class="menu-toggle" id="menu-toggle">☰</button>
    </div>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="ghost.jpg?v=5.0" class="logo-img" alt="Ghost Logo">
                <span class="logo-text">Ghost<span> Pix</span></span>
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item">📊 Dashboard</a>
                <a href="sacar.php" class="nav-item">💸 Sacar</a>
                <a href="perfil.php" class="nav-item active">👤 Perfil</a>
                <a href="suporte.php" class="nav-item">🎧 Suporte</a>
                <?php if(isAdmin()): ?>
                    <a href="admin/index.php" class="nav-item">🛡️ Admin</a>
                <?php endif; ?>
                <a href="auth/logout.php" class="nav-item">🚪 Sair</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <h1>Meu Perfil</h1>
                <a href="index.php" class="badge sent" style="text-decoration:none">Voltar</a>
            </header>

            <div style="max-width: 600px; margin: 0 auto;">
                <div class="card glass">
                    <form id="profile-form">
                        <h3 style="margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">Dados Pessoais</h3>
                        
                        <div class="input-group">
                            <label>Email (Não pode ser alterado)</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity: 0.6; cursor: not-allowed;">
                        </div>

                        <div class="input-group">
                            <label>Nome Completo (Como no Banco)</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>

                        <div class="input-group">
                            <label>Chave PIX (Para Recebimento)</label>
                            <input type="text" name="pix_key" value="<?php echo htmlspecialchars($user['pix_key']); ?>" required>
                        </div>

                        <h3 style="margin-top: 3rem; margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">Alterar Senha (Opcional)</h3>
                        
                        <div class="input-group">
                            <label>Senha Atual (Necessária apenas para mudar a senha)</label>
                            <input type="password" name="current_password" placeholder="Digite sua senha atual">
                        </div>

                        <div class="input-group">
                            <label>Nova Senha (Deixe em branco para não alterar)</label>
                            <input type="password" name="new_password" placeholder="Mínimo 6 caracteres">
                        </div>

                        <button type="submit" class="btn-primary" id="btn-save-profile" style="margin-top: 2rem;">Salvar Alterações</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav">
        <a href="index.php" class="mobile-nav-item">
            <i class="fas fa-th-large"></i>
            <span>Home</span>
        </a>
        <a href="sacar.php" class="mobile-nav-item">
            <i class="fas fa-wallet"></i>
            <span>Sacar</span>
        </a>
        <a href="perfil.php" class="mobile-nav-item active">
            <i class="fas fa-user-circle"></i>
            <span>Perfil</span>
        </a>
        <a href="suporte.php" class="mobile-nav-item">
            <i class="fas fa-headset"></i>
            <span>Suporte</span>
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
                headers: { 'Content-Type': 'application/json' },
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
