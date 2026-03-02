<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT balance, pix_key, full_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Erro no Banco de Dados: " . $e->getMessage() . " <br><br>Certifique-se de acessar <b>" . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/migrate_v2.php</b> para atualizar o sistema.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Saque - Ghost Pix</title>
    <link rel="stylesheet" href="style.css?v=2.2">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="mobile-header">
        <div class="logo" style="margin-bottom: 0;">
            <img src="ghost.jfif" class="logo-img" style="height: 24px;" alt="Ghost Logo">
            <span class="logo-text" style="font-size: 1.2rem;">Ghost<span> Pix</span></span>
        </div>
        <button class="menu-toggle" id="menu-toggle">☰</button>
    </div>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <img src="ghost.jfif" class="logo-img" alt="Ghost Logo">
                <span class="logo-text">Ghost<span> Pix</span></span>
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item">📊 Dashboard</a>
                <a href="sacar.php" class="nav-item active">💸 Sacar</a>
                <a href="perfil.php" class="nav-item">👤 Perfil</a>
                <a href="suporte.php" class="nav-item">🎧 Suporte</a>
                <?php if(isAdmin()): ?>
                    <a href="admin/index.php" class="nav-item">🛡️ Admin</a>
                <?php endif; ?>
                <a href="auth/logout.php" class="nav-item">🚪 Sair</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <h1>Solicitar Saque</h1>
                <a href="index.php" class="badge sent" style="text-decoration:none">Voltar ao Dashboard</a>
            </header>

            <div style="max-width: 600px; margin: 0 auto;">
                <div class="card glass" style="border-color: var(--primary);">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <h3 style="color: var(--text-dim); margin-bottom: 0.5rem;">Saldo Disponível</h3>
                        <div style="font-size: 3rem; font-weight: 800; color: var(--primary);">
                            R$ <?php echo number_format($user['balance'], 2, ',', '.'); ?>
                        </div>
                    </div>

                    <div style="background: rgba(0,0,0,0.2); padding: 1.5rem; border-radius: 16px; margin-bottom: 2rem; border: 1px solid var(--glass-border);">
                        <p style="font-size: 0.9rem; color: var(--text-dim); margin-bottom: 1rem;">Destino do Pagamento:</p>
                        <div style="display:flex; flex-direction:column; gap: 0.5rem;">
                            <span style="font-weight: 600; font-size: 1.1rem; color: #fff;"><?php echo htmlspecialchars($user['full_name'] ?? 'Nome não configurado'); ?></span>
                            <span style="font-family: monospace; color: var(--primary); background: rgba(0,255,136,0.1); padding: 0.5rem; border-radius: 8px;"><?php echo htmlspecialchars($user['pix_key'] ?? 'Chave não configurada'); ?></span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Valor para Saque (Mínimo R$ 50,00)</label>
                        <input type="number" id="withdraw-amount" placeholder="0,00" step="0.01" style="font-size: 1.5rem; text-align: center;">
                    </div>

                    <div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                        <p style="font-size: 0.85rem; color: #ff9999; margin: 0;">
                            <strong>Aviso:</strong> O processamento pode levar até <strong>2 dias úteis</strong>. Certifique-se de que os dados acima estão corretos.
                        </p>
                    </div>

                    <button id="btn-confirm-withdraw" class="btn-primary" style="padding: 1.2rem; font-size: 1.1rem;">Confirmar Saque</button>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Sucesso -->
    <div class="modal hidden" id="modal-success">
        <div class="modal-content">
            <div class="success-icon">✓</div>
            <h2 style="color: #fff; margin-bottom: 1rem;">Solicitação Enviada!</h2>
            <p style="color: var(--text-dim); line-height: 1.6; margin-bottom: 2rem;">
                Seu saque de <strong id="success-amount" style="color: var(--primary);">R$ 0,00</strong> foi registrado com sucesso.<br><br>
                <strong>Importante:</strong> O prazo para processamento é de até <strong>2 dias úteis</strong>.
            </p>
            <button class="btn-primary" onclick="window.location.href='index.php'">Entendido</button>
        </div>
    </div>

    <!-- Modal de Erro -->
    <div class="modal hidden" id="modal-error">
        <div class="modal-content">
            <div class="error-icon">✕</div>
            <h2 style="color: #fff; margin-bottom: 1rem;">Ops! Algo deu errado</h2>
            <p id="error-message" style="color: var(--text-dim); line-height: 1.6; margin-bottom: 2rem;">
                Não foi possível processar sua solicitação.
            </p>
            <button class="btn-primary" style="background: var(--danger);" onclick="document.getElementById('modal-error').classList.add('hidden')">Tentar Novamente</button>
        </div>
    </div>

    <script>
    function showError(msg) {
        document.getElementById('error-message').innerText = msg;
        document.getElementById('modal-error').classList.remove('hidden');
    }

    document.getElementById('btn-confirm-withdraw').addEventListener('click', async () => {
        const amountInput = document.getElementById('withdraw-amount');
        const amount = amountInput.value;
        const balance = <?php echo (float)$user['balance']; ?>;
        
        if (!amount || parseFloat(amount) < 50) {
            showError('O valor mínimo para saque é R$ 50,00.');
            return;
        }

        if (parseFloat(amount) > balance) {
            showError('Saldo Insuficiente! Seu saldo atual é R$ ' + balance.toLocaleString('pt-BR', {minimumFractionDigits: 2}));
            return;
        }

        const btn = document.getElementById('btn-confirm-withdraw');
        const originalText = btn.innerText;
        btn.innerText = 'Processando...';
        btn.disabled = true;

        try {
            const res = await fetch('withdraw.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amount })
            });

            const data = await res.json();
            if (data.status === 'success') {
                document.getElementById('success-amount').innerText = 'R$ ' + parseFloat(amount).toLocaleString('pt-BR', {minimumFractionDigits: 2});
                document.getElementById('modal-success').classList.remove('hidden');
            } else {
                showError(data.error || 'Erro ao processar saque.');
            }
        } catch (err) {
            showError('Erro de conexão ao processar saque.');
        } finally {
            btn.innerText = originalText;
            btn.disabled = false;
        }
    });
    </script>
    <script src="script.js?v=2.0"></script>
</body>
</html>
