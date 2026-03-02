<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$transactions = $pdo->prepare("SELECT *, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at)) as seconds_old FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$transactions->execute([$userId]);
$rows = $transactions->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Pix - Dashboard</title>
    <link rel="stylesheet" href="style.css?v=3.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <span class="logo-icon">⚡</span>
                <span class="logo-text">Ghost<span> Pix</span></span>
            </div>
            <nav class="nav-menu">
                <a href="#" class="nav-item active">📊 Dashboard</a>
                <a href="sacar.php" class="nav-item">💸 Sacar</a>
                <a href="perfil.php" class="nav-item">👤 Perfil</a>
                <?php if(isAdmin()): ?>
                    <a href="admin/index.php" class="nav-item">🛡️ Admin</a>
                <?php endif; ?>
                <a href="auth/logout.php" class="nav-item">🚪 Sair</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-profile">
                    <div class="avatar"><?php echo strtoupper(substr($user['email'], 0, 1)); ?></div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Usuário'); ?></span>
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
                <!-- Balance Card -->
                <div class="card wallet-card glass" style="border-color: var(--primary);">
                    <h3>Seu Saldo Disponível</h3>
                    <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem;">
                        R$ <?php echo number_format($user['balance'], 2, ',', '.'); ?>
                    </div>
                    <?php if($user['balance'] > 0): ?>
                        <a href="sacar.php" class="btn-primary" style="display:block; text-decoration:none; text-align:center; padding: 0.8rem 1.5rem; width: 100%; margin-top: 1rem; background: var(--primary); color: var(--bg-dark); border-radius: 12px; font-weight:700;">Solicitar Saque</a>
                        <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 10px; text-align: center;">
                            <i class="fas fa-info-circle"></i> O mínimo para saque é R$ 50,00.
                        </p>
                    <?php endif; ?>
                </div>

                <div class="card wallet-card glass">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h3 style="margin: 0;">Sua Chave PIX</h3>
                        <button id="btn-edit-wallet" class="badge sent" style="border:none; cursor:pointer; font-size: 0.7rem;">Editar</button>
                    </div>
                    <div class="wallet-address-box">
                        <input type="text" id="wallet-input" value="<?php echo htmlspecialchars($user['pix_key'] ?? ''); ?>" 
                               placeholder="Sua Chave PIX" 
                               style="background: transparent; border: none; color: white; width: 100%; font-family: inherit; font-size: 0.9rem;" 
                               readonly>
                        <button id="btn-copy-wallet" class="btn-icon" title="Copiar">📋</button>
                        <button id="btn-save-wallet" class="btn-icon hidden" title="Salvar" style="color: var(--primary);">💾</button>
                    </div>
                    <?php if($user['status'] == 'pending'): ?>
                        <p style="color: var(--accent); font-size: 0.8rem; margin-top: 10px;">Aguardando aprovação do admin para gerar Pix.</p>
                    <?php endif; ?>
                </div>

                <div class="card generate-card glass">
                    <h3>Gerar Nova Cobrança</h3>
                    <div class="input-group">
                        <label>Valor (BRL)</label>
                        <input type="number" id="amount" placeholder="0,00" step="0.01" min="10" <?php echo $user['status'] != 'approved' ? 'disabled' : ''; ?>>
                        <p style="font-size: 0.7rem; color: var(--text-dim); margin-top: 5px;">Mínimo: R$ 10,00</p>
                    </div>
                    <button id="btn-generate" class="btn-primary" <?php echo $user['status'] != 'approved' ? 'disabled' : ''; ?>>
                        <?php echo $user['status'] == 'approved' ? 'Gerar QR Code Pix' : 'Conta Pendente'; ?>
                    </button>
                    <p style="font-size: 0.75rem; color: var(--text-dim); margin-top: 10px; text-align: center;">
                        Importante: O valor pago será creditado em seu saldo após confirmação.
                    </p>
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
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rows as $t): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($t['created_at'])); ?></td>
                                <td>R$ <?php echo number_format($t['amount_brl'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($t['amount_net_brl'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    $status = $t['status'];
                                    $displayStatus = ucfirst($status);
                                    $badgeClass = 'pending'; // Padrão para pendente (âmbar)
                                    
                                    if ($status == 'paid') {
                                        $badgeClass = 'paid'; // Verde
                                    } elseif ($status == 'pending') {
                                        if ($t['seconds_old'] > (20 * 60)) {
                                            $displayStatus = 'Expirado';
                                            $badgeClass = 'expired'; // Laranja
                                        }
                                    } elseif ($status == 'rejected') {
                                        $badgeClass = 'rejected'; // Vermelho
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $displayStatus; ?></span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                        <button class="btn-history-action btn-view-qr" 
                                                data-qr="<?php echo htmlspecialchars($t['qr_image'] ?? ''); ?>" 
                                                data-code="<?php echo htmlspecialchars($t['pix_code'] ?? ''); ?>"
                                                data-amount="<?php echo number_format($t['amount_brl'], 2, ',', '.'); ?>"
                                                title="Ver QR Code">
                                            <i class="fas fa-qrcode"></i>
                                        </button>
                                        <button class="btn-history-action btn-copy-pix-row" 
                                                data-code="<?php echo htmlspecialchars($t['pix_code'] ?? ''); ?>"
                                                title="Copiar Pix">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                        <button class="btn-history-action btn-delete-row" 
                                                data-id="<?php echo $t['id']; ?>"
                                                title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
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
            
            <div class="pix-copy-area" style="margin-top: 1.5rem; background: rgba(255,255,255,0.05); padding: 1rem; border-radius: 12px; border: 1px solid var(--glass-border);">
                <p style="font-size: 0.75rem; color: var(--text-dim); margin-bottom: 0.5rem; text-align: left;">Copia e Cola:</p>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <textarea id="pix-code-text" readonly style="flex: 1 1 200px; background: rgba(0,0,0,0.2); border: none; color: white; font-size: 0.75rem; resize: none; height: 60px; font-family: monospace; padding: 0.5rem; border-radius: 8px;"></textarea>
                    <button id="btn-copy-pix" class="btn-primary" style="flex: 1 1 100px; padding: 0.8rem; font-size: 0.8rem; white-space: nowrap;">Copiar</button>
                </div>
            </div>

            <div class="pix-warning" style="margin-top: 1.5rem; background: rgba(245, 158, 11, 0.1); padding: 1rem; border-radius: 12px; border: 1px solid rgba(245, 158, 11, 0.2); text-align: left;">
                <p style="font-size: 0.8rem; color: #f59e0b; font-weight: 600; margin-bottom: 0.4rem;">⚠️ Atenção: Evite valores repetidos</p>
                <p style="font-size: 0.75rem; color: var(--text-dim); line-height: 1.4;">
                    Para sua segurança, não gere dois Pix com o mesmo valor exato em menos de 20 min. 
                    <strong>Altere os centavos</strong> se precisar de uma nova cobrança. <br>
                    <em>Exemplo: R$ 50,00 e R$ 50,01</em>
                </p>
            </div>

            <p class="qr-value" style="margin-top: 1.5rem;">Valor: <strong id="modal-amount">R$ 0,00</strong></p>
            <p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 10px;">O DEPIX será enviado para sua carteira após o pagamento.</p>
        </div>
    </div>

    <script src="script.js?v=3.4"></script>
</body>
</html>
