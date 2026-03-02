// Ativar exibição de erros para debug (Opcional: Remover após resolver)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    // Caso a migração não tenha sido feita ou as colunas faltem
    die("Erro no Banco de Dados: As colunas necessárias não foram encontradas. <br>Certifique-se de acessar <b>" . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/migrate_v2.php</b> para atualizar o sistema.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Saque - PixAnônimo</title>
    <link rel="stylesheet" href="style.css?v=1.5">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="mobile-header">
        <div class="logo" style="margin-bottom: 0;">
            <span class="logo-icon">⚡</span>
            <span class="logo-text" style="font-size: 1.2rem;">Pix<span>Anônimo</span></span>
        </div>
        <button class="menu-toggle" id="menu-toggle">☰</button>
    </div>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <span class="logo-icon">⚡</span>
                <span class="logo-text">Pix<span>Anônimo</span></span>
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item">📊 Dashboard</a>
                <a href="#" class="nav-item active">💸 Sacar</a>
                <a href="perfil.php" class="nav-item">👤 Perfil</a>
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

    <script>
    document.getElementById('btn-confirm-withdraw').addEventListener('click', async () => {
        const amount = document.getElementById('withdraw-amount').value;
        
        if (!amount || parseFloat(amount) < 50) {
            alert('O valor mínimo para saque é R$ 50,00.');
            return;
        }

        const btn = document.getElementById('btn-confirm-withdraw');
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
                alert(data.message);
                window.location.href = 'index.php';
            } else {
                alert(data.error || 'Erro ao processar saque.');
            }
        } catch (err) {
            alert('Erro de conexão.');
        } finally {
            btn.innerText = 'Confirmar Saque';
            btn.disabled = false;
        }
    });
    </script>
    <script src="script.js?v=1.5"></script>
</body>
</html>
