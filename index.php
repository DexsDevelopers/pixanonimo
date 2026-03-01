<?php
session_start();
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$transactions = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$transactions->execute([$userId]);
$rows = $transactions->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PixAnônimo - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <span class="logo-icon">⚡</span>
                <span class="logo-text">Pix<span>Anônimo</span></span>
            </div>
            <nav class="nav-menu">
                <a href="#" class="nav-item active">📊 Dashboard</a>
                <?php if(isAdmin()): ?>
                    <a href="admin/index.php" class="nav-item" style="color: var(--primary);">🛡️ Administração</a>
                <?php endif; ?>
                <a href="auth/logout.php" class="nav-item">🚪 Sair</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="avatar"><?php echo strtoupper(substr($user['email'], 0, 1)); ?></div>
                    <div class="user-info">
                        <span class="user-name"><?php echo explode('@', $user['email'])[0]; ?></span>
                        <span class="user-status"><?php echo ucfirst($user['status']); ?></span>
                    </div>
                </div>
            </div>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <h1>Painel de Controle</h1>
                <div class="wallet-status">
                    <span class="status-indicator"></span>
                    Taxa do Sistema: <strong><?php echo $user['commission_rate']; ?>%</strong>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="card wallet-card glass">
                    <h3>Sua Carteira Liquid</h3>
                    <div class="wallet-address-box">
                        <span id="wallet-address"><?php echo $user['liquid_address'] ?: 'Não configurada'; ?></span>
                        <button class="btn-icon">📋</button>
                    </div>
                    <?php if($user['status'] == 'pending'): ?>
                        <p style="color: var(--accent);">Aguardando aprovação do admin para gerar Pix.</p>
                    <?php endif; ?>
                </div>

                <div class="card generate-card glass">
                    <h3>Gerar Nova Cobrança</h3>
                    <div class="input-group">
                        <label>Valor (BRL)</label>
                        <input type="number" id="amount" placeholder="0,00" step="0.01" <?php echo $user['status'] != 'approved' ? 'disabled' : ''; ?>>
                    </div>
                    <button id="btn-generate" class="btn-primary" <?php echo $user['status'] != 'approved' ? 'disabled' : ''; ?>>
                        <?php echo $user['status'] == 'approved' ? 'Gerar QR Code Pix' : 'Conta Pendente'; ?>
                    </button>
                </div>

                <div class="card history-card glass full-width">
                    <h3>Histórico Recente</h3>
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Valor Bruto</th>
                                <th>Líquido (DEPIX)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rows as $t): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></td>
                                <td>R$ <?php echo number_format($t['amount_brl'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($t['amount_net_brl'], 2, ',', '.'); ?></td>
                                <td><span class="badge <?php echo $t['status'] == 'paid' ? 'paid' : 'sent'; ?>"><?php echo ucfirst($t['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal QR Code -->
    <div id="modal-qr" class="modal hidden">
        <div class="modal-content glass">
            <span class="close-modal">&times;</span>
            <h2>Escaneie o Pix</h2>
            <div class="qr-placeholder"></div>
            <p class="qr-value">Valor: <strong id="modal-amount">R$ 0,00</strong></p>
            <p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 10px;">O DEPIX será enviado para sua carteira após o pagamento.</p>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
