<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Estatísticas do Usuário
$stats = [
    'today_volume' => 0,
    'month_volume' => 0,
    'total_paid' => 0,
    'pending_count' => 0
];

// Volume Hoje (24h)
$stmtToday = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
$stmtToday->execute([$userId]);
$stats['today_volume'] = $stmtToday->fetch()['vol'] ?? 0;

// Volume Mês Atual
$stmtMonth = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$stmtMonth->execute([$userId]);
$stats['month_volume'] = $stmtMonth->fetch()['vol'] ?? 0;

// Total Acumulado (Pago)
$stmtTotal = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid'");
$stmtTotal->execute([$userId]);
$stats['total_paid'] = $stmtTotal->fetch()['vol'] ?? 0;

// Cobranças Pendentes
$stmtPending = $pdo->prepare("SELECT COUNT(*) as qtd FROM transactions WHERE user_id = ? AND status = 'pending'");
$stmtPending->execute([$userId]);
$stats['pending_count'] = $stmtPending->fetch()['qtd'] ?? 0;

$transactions = $pdo->prepare("SELECT *, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at)) as seconds_old FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$transactions->execute([$userId]);
$rows = $transactions->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#000000">
    <link rel="manifest" href="manifest.json">
    <title>Ghost Pix - Dashboard Premium</title>
    <link rel="stylesheet" href="style.css?v=31.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-header">
                <div>
                    <h1>Olá, <?php echo explode(' ', $_SESSION['full_name'] ?? 'Usuário')[0]; ?> 👋</h1>
                    <p>Bem-vindo ao seu painel Ghost Pix.</p>
                </div>
                <div class="wallet-status">
                    <span class="status-indicator"></span>
                    Taxa do Sistema: <strong><?php echo $user['commission_rate']; ?>%</strong>
                </div>
            </header>

            <?php if ($user['status'] == 'pending'): ?>
            <!-- Pending Approval Alert -->
            <div class="card" style="background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); margin-bottom: 2rem; padding: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                            <i class="fas fa-clock-rotate-left"></i>
                        </div>
                        <div>
                            <h3 style="color: #f59e0b; font-size: 1rem; margin-bottom: 2px;">Sua conta está aguardando aprovação</h3>
                            <p style="color: var(--text-2); font-size: 0.85rem;">Para começar a movimentar e gerar cobranças, sua conta precisa ser verificada.</p>
                        </div>
                    </div>
                    <a href="https://wa.me/5551996148568?text=Ol%C3%A1%2C%20gostaria%20de%20ativar%20minha%20conta%20na%20Ghost%20Pix!%20Meu%20email%3A%20<?php echo urlencode($user['email']); ?>" 
                       target="_blank" 
                       class="badge paid" 
                       style="display: flex; align-items: center; gap: 8px; text-decoration: none; padding: 10px 18px; font-weight: 600; background: #25d366; color: #000; border: none; font-size: 0.85rem;">
                        <i class="fab fa-whatsapp" style="font-size: 1.1rem;"></i> ATIVAR VIA WHATSAPP
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Compact Analytics Grid -->
            <div class="analytics-grid">
                <!-- Volume Hoje -->
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <span class="stat-label">Volume Hoje</span>
                    <div class="stat-value">R$ <?php echo number_format($stats['today_volume'], 2, ',', '.'); ?></div>
                    <div class="stat-sub positive"><i class="fas fa-arrow-up"></i> +<?php echo $stats['today_volume'] > 0 ? '12' : '0'; ?>%</div>
                </div>

                <!-- Volume Mensal -->
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <span class="stat-label">Volume Mensal</span>
                    <div class="stat-value">R$ <?php echo number_format($stats['month_volume'], 2, ',', '.'); ?></div>
                    <div class="stat-sub">Mês <?php echo date('M'); ?></div>
                </div>

                <!-- Total Vitalício -->
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    <span class="stat-label">Aprovado Total</span>
                    <div class="stat-value">R$ <?php echo number_format($stats['total_paid'], 2, ',', '.'); ?></div>
                    <div class="stat-sub">Desde o início</div>
                </div>

                <!-- Pendentes -->
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <span class="stat-label">Pendentes</span>
                    <div class="stat-value" style="color:var(--amber);"><?php echo $stats['pending_count']; ?></div>
                    <div class="stat-sub">Aguardando PIX</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Main Balance Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title-group">
                            <div class="card-icon"><i class="fas fa-wallet"></i></div>
                            <h3 class="card-title">Saldo Disponível</h3>
                        </div>
                        <div class="wallet-status">
                            <span class="status-indicator"></span>
                            Taxa: <?php echo $user['commission_rate']; ?>%
                        </div>
                    </div>
                    
                    <div class="balance-display" style="margin: 1.5rem 0;">
                        <span style="font-size: 1.5rem; opacity: 0.5; font-weight: 500;">R$</span> 
                        <span style="font-size: 2.8rem; font-weight: 800; letter-spacing: -1px;"><?php echo number_format($user['balance'], 2, ',', '.'); ?></span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <?php if($user['balance'] > 0): ?>
                            <a href="sacar.php" class="btn-primary" style="padding: 0.7rem 1.5rem; font-size: 0.9rem;">
                                <i class="fas fa-arrow-up-right-from-square"></i> Efetuar Saque
                            </a>
                        <?php endif; ?>
                        <p class="card-hint" style="color:var(--green); margin:0;">
                            <i class="fas fa-shield-check"></i> Proteção Ghost Pix Ativa
                        </p>
                    </div>
                </div>

                <!-- Fast Actions / PIX Key -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title-group">
                            <div class="card-icon"><i class="fas fa-key"></i></div>
                            <h3 class="card-title">Configuração de Recebimento</h3>
                        </div>
                        <button id="btn-edit-wallet" class="btn-edit"> Editar </button>
                    </div>
                    
                    <p class="card-hint" style="margin: 1rem 0 0.5rem;">Chave PIX configurada:</p>
                    <div class="pix-key-box">
                        <input type="text" id="wallet-input" value="<?php echo htmlspecialchars($user['pix_key'] ?? 'Não configurada'); ?>" readonly class="pix-key-input" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border);">
                        <button id="btn-copy-wallet" class="btn-icon-sm"><i class="far fa-copy"></i></button>
                    </div>
                </div>

                <!-- Generate Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title-group">
                            <div class="card-icon"><i class="fas fa-qrcode"></i></div>
                            <h3 class="card-title">Gerar Cobrança</h3>
                        </div>
                    </div>
                    <div class="amount-input-wrap">
                        <span class="amount-prefix">R$</span>
                        <input type="number" id="amount" class="amount-input" placeholder="0,00" step="0.01" min="10"
                               <?php echo $user['status'] != 'approved' ? 'disabled' : ''; ?>>
                    </div>
                    <p class="card-hint" style="margin-bottom:.75rem;">Mínimo: R$ 10,00</p>
                    <button id="btn-generate" class="btn-primary"
                            <?php echo $user['status'] != 'approved' ? 'disabled' : ''; ?>>
                        <i class="fas fa-bolt"></i>
                        <?php echo $user['status'] == 'approved' ? 'Gerar QR Code Pix' : 'Conta Pendente'; ?>
                    </button>
                    <p class="card-hint center" style="margin-top:1rem;">
                        <i class="fas fa-check-circle"></i> Verificado pelo Banco Central
                    </p>
                    <p class="card-hint center">Crédito imediato após confirmação.</p>
                </div>

                <!-- History Card -->
                <div class="card full-width">
                    <div class="card-header">
                        <div class="card-title-group">
                            <div class="card-icon"><i class="fas fa-history"></i></div>
                            <h3 class="card-title">Histórico Recente</h3>
                        </div>
                    </div>
                    <div class="table-wrap">
                    <table class="transaction-table" id="transactions-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Bruto</th>
                                <th>Líquido</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rows as $t): ?>
                            <tr>
                                <td><?php echo date('d/m H:i', strtotime($t['created_at'])); ?></td>
                                <td>R$ <?php echo number_format($t['amount_brl'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($t['amount_net_brl'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    $status = $t['status'];
                                    $displayStatus = ucfirst($status);
                                    $badgeClass = 'pending';
                                    
                                    if ($status == 'paid') {
                                        $badgeClass = 'paid';
                                    } elseif ($status == 'pending') {
                                        if ($t['seconds_old'] > (20 * 60)) {
                                            $displayStatus = 'Expirado';
                                            $badgeClass = 'expired';
                                        }
                                    } elseif ($status == 'rejected') {
                                        $badgeClass = 'rejected';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $displayStatus; ?></span>
                                </td>
                                <td>
                                    <div class="action-row">
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
            </div>
        </main>
    </div>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav">
        <a href="dashboard.php" class="mobile-nav-item active">
            <i class="fas fa-th-large"></i>
            <span>Home</span>
        </a>
        <a href="sacar.php" class="mobile-nav-item">
            <i class="fas fa-wallet"></i>
            <span>Sacar</span>
        </a>
        <a href="perfil.php" class="mobile-nav-item">
            <i class="fas fa-user-circle"></i>
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

    <!-- Modal de Confirmação Customizado -->
    <div id="modal-confirm" class="modal hidden">
        <div class="modal-content glass" style="max-width: 400px; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">🗑️</div>
            <h2 style="margin-bottom: 0.5rem;">Confirmação</h2>
            <p style="color: var(--text-dim); margin-bottom: 2rem;">Deseja realmente excluir esta transação? Esta ação não pode ser desfeita.</p>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div id="modal-confirm" class="modal hidden">
        <div class="modal-content glass" style="max-width: 400px; text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem; color: var(--danger);">⚠️</div>
            <h3 style="margin-bottom: 1rem;">Confirmar Exclusão?</h3>
            <p style="color: var(--text-2); margin-bottom: 2rem;">Esta ação não pode ser desfeita. A transação será removida permanentemente do histórico.</p>
            <div style="display: flex; gap: 1rem;">
                <button id="btn-confirm-cancel" class="btn-primary" style="background: rgba(255,255,255,0.1); flex: 1;">Cancelar</button>
                <button id="btn-confirm-delete" class="btn-primary" style="background: var(--danger); flex: 1;">Excluir</button>
            </div>
        </div>
    </div>

        </main>
    </div> <!-- Fechamento app-container do sidebar.php -->

    <script src="script.js?v=30.0"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('SW Registered'))
                    .catch(err => console.log('SW Error', err));
            });
        }
    </script>

</body>
</html>
