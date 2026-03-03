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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#000000">
    <link rel="manifest" href="manifest.json">
    <title>Solicitar Saque - Ghost Pix</title>
    <link rel="stylesheet" href="style.css?v=31.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-header">
                <h1>Solicitar Saque</h1>
                <a href="dashboard.php" class="badge sent" style="text-decoration:none">Voltar ao Dashboard</a>
            </header>

            <div style="max-width: 600px; margin: 0 auto;">
            <div style="max-width: 600px; margin: 0 auto;">
                <div class="card">
                    <div style="text-align: center; margin-bottom: 2.2rem;">
                        <span class="stat-label">Saldo Disponível</span>
                        <div class="balance-display" style="font-size: 3rem; margin-top: 0.5rem; letter-spacing: -2px;">
                            <span class="currency">R$</span><?php echo number_format($user['balance'], 2, ',', '.'); ?>
                        </div>
                        <p class="card-hint center" style="color:var(--green); font-weight:600; margin-top: 0.5rem;">
                            <i class="fas fa-shield-halved"></i> Saldo Protegido por Ghost Pix
                        </p>
                    </div>

                    <div style="background: rgba(0,0,0,0.3); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid var(--border);">
                        <p class="stat-label" style="margin-bottom: 0.8rem; font-size: 0.65rem;">Destino do Pagamento</p>
                        <div style="display:flex; flex-direction:column; gap: 0.4rem;">
                            <span style="font-weight: 700; font-size: 1.15rem; color: var(--text);"><?php echo htmlspecialchars($user['full_name'] ?? 'Nome não configurado'); ?></span>
                            <span style="font-family: 'SF Mono', monospace; color: var(--blue); font-size: 0.9rem; letter-spacing: 0.5px;"><?php echo htmlspecialchars($user['pix_key'] ?? 'Chave não configurada'); ?></span>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label class="stat-label" style="display:block; margin-bottom: 0.6rem;">Valor para Saque (Mínimo R$ 50,00)</label>
                        <div class="amount-input-wrap">
                            <span class="amount-prefix">R$</span>
                            <input type="number" id="withdraw-amount" class="amount-input" placeholder="0,00" step="0.01">
                        </div>
                    </div>

                    <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.15); padding: 1rem; border-radius: 10px; margin-bottom: 2.2rem;">
                        <p style="font-size: 0.75rem; color: var(--red); margin: 0; line-height: 1.5; font-weight: 500;">
                            <i class="fas fa-triangle-exclamation"></i> <strong>Aviso:</strong> O processamento pode levar até <strong>2 dias úteis</strong>. Certifique-se de que os dados acima estão corretos.
                        </p>
                    </div>

                    <button id="btn-confirm-withdraw" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Confirmar Saque
                    </button>
                    <p class="card-hint center" style="margin-top: 1.2rem;">
                        <i class="fas fa-lock"></i> Transferência via PIX Instantâneo
                    </p>
                </div>
            </div>
            </div>
        </main>
    </div>

    <script src="script.js?v=31.0"></script>

    <!-- Modal de Sucesso -->
    <div class="modal hidden" id="modal-success">
        <div class="modal-content">
            <div class="success-icon">✓</div>
            <h2 style="color: #fff; margin-bottom: 1rem;">Solicitação Enviada!</h2>
            <p style="color: var(--text-dim); line-height: 1.6; margin-bottom: 2rem;">
                Seu saque de <strong id="success-amount" style="color: var(--primary);">R$ 0,00</strong> foi registrado com sucesso.<br><br>
                <strong>Importante:</strong> O prazo para processamento é de até <strong>2 dias úteis</strong>.
            </p>
            <button class="btn-primary" onclick="window.location.href='dashboard.php'">Entendido</button>
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
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
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
    <script src="script.js?v=31.0"></script>
</body>
</html>
