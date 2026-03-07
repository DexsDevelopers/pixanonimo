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

$totalOrdersCount = 0;
if ($user['is_demo'] == 1) {
    // Lógica Demo: Receita Total = Saldo Atual + Saques Concluídos
    $stmtW = $pdo->prepare("SELECT SUM(amount) as total FROM withdrawals WHERE user_id = ? AND status = 'completed'");
    $stmtW->execute([$userId]);
    $totalWithdrawn = $stmtW->fetch()['total'] ?? 0;
    
    $stats['total_paid'] = $user['balance'] + $totalWithdrawn;
    $stats['month_volume'] = $stats['total_paid'] * 0.82;
    $stats['today_volume'] = $stats['total_paid'] * 0.14;
    $totalOrdersCount = floor($stats['total_paid'] / 42) + 7; // Média de R$ 42 por pedido + offset
    $stats['pending_count'] = floor($totalOrdersCount * 0.3);
} else {
    $stmtOrders = $pdo->prepare("SELECT COUNT(*) as qtd FROM transactions WHERE user_id = ? AND status = 'paid'");
    $stmtOrders->execute([$userId]);
    $totalOrdersCount = $stmtOrders->fetch()['qtd'] ?? 0;
}

$transactions = $pdo->prepare("SELECT *, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at)) as seconds_old FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$transactions->execute([$userId]);
$rows = $transactions->fetchAll();

// Cálculo Real de Receita por Método (Pago)
$percPix = 0; $percCard = 0; $percBoleto = 0;

try {
    $stmtMethod = $pdo->prepare("SELECT 
        SUM(CASE WHEN method = 'pix' THEN amount_brl ELSE 0 END) as pix_vol,
        SUM(CASE WHEN method = 'card' THEN amount_brl ELSE 0 END) as card_vol,
        SUM(CASE WHEN method = 'boleto' THEN amount_brl ELSE 0 END) as boleto_vol
        FROM transactions WHERE user_id = ? AND status = 'paid'");
    $stmtMethod->execute([$userId]);
    $methodVol = $stmtMethod->fetch();

    if ($methodVol) {
        $totalPaidVol = ($methodVol['pix_vol'] + $methodVol['card_vol'] + $methodVol['boleto_vol']) ?: 0;
        
        if ($totalPaidVol > 0) {
            $percPix = round(($methodVol['pix_vol'] / $totalPaidVol) * 100, 1);
            $percCard = round(($methodVol['card_vol'] / $totalPaidVol) * 100, 1);
            $percBoleto = round(($methodVol['boleto_vol'] / $totalPaidVol) * 100, 1);
        }
    }
} catch (PDOException $e) {
    // Se a coluna 'method' não existir ou houver outro erro, fallback para 100% Pix se houver vendas totais
    if ($stats['total_paid'] > 0) {
        $percPix = 100;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#000000">
    <link rel="manifest" href="manifest.json">
    <title>Ghost Pix - Dashboard Premium</title>
    <link rel="stylesheet" href="style.css?v=124.0">
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

            <!-- Notification Control Card (PWA) -->
            <div id="push-control-card" class="card glass" style="margin-bottom: 2rem; border: 1px solid rgba(99, 102, 241, 0.2); background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), transparent); display: none;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 45px; height: 45px; border-radius: 12px; background: var(--bg-card); color: var(--accent); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; border: 1px solid var(--border);">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div>
                            <h3 style="font-size: 1.1rem; margin-bottom: 2px;">Alertas de Vendas no Celular</h3>
                            <p id="push-status-text" style="color: var(--text-2); font-size: 0.85rem;">Ative para receber notificações fora do site (iPhone/Android).</p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button id="btn-activate-push" class="btn-primary" style="padding: 10px 20px; font-size: 0.85rem; background: var(--accent);">
                            <i class="fas fa-mobile-screen-button"></i> ATIVAR AGORA
                        </button>
                        <button id="btn-test-push" class="btn-secondary" style="padding: 10px 20px; font-size: 0.85rem; display: none; background: rgba(255,255,255,0.05); border: 1px solid var(--border);">
                            <i class="fas fa-paper-plane"></i> TESTAR NOTIFICAÇÃO
                        </button>
                    </div>
                </div>
            </div>

            <!-- Premium Analytics Grid -->
            <div class="analytics-grid">
                <!-- Receita Total -->
                <div class="stat-card ghost-green">
                    <div class="stat-icon"><i class="fas fa-sack-dollar"></i></div>
                    <span class="stat-label">Receita Total</span>
                    <div class="stat-value">R$ <?php echo number_format($stats['total_paid'], 2, ',', '.'); ?></div>
                    <div class="stat-sub positive"><i class="fas fa-arrow-up"></i> +<?php echo $stats['total_paid'] > 0 ? '4.5' : '0'; ?>%</div>
                </div>

                <!-- Pedidos Pagos -->
                <div class="stat-card ghost-purple">
                    <div class="stat-icon"><i class="fas fa-cart-check"></i></div>
                    <span class="stat-label">Pedidos Pagos</span>
                    <div class="stat-value"><?php echo $totalOrdersCount; ?></div>
                    <div class="stat-sub positive"><i class="fas fa-arrow-up"></i> +<?php echo $user['is_demo'] ? '1.2' : '0'; ?>%</div>
                </div>

                <!-- Ticket Médio -->
                <div class="stat-card ghost-yellow">
                    <div class="stat-icon"><i class="fas fa-chart-bar"></i></div>
                    <span class="stat-label">Ticket Médio</span>
                    <div class="stat-value">R$ <?php 
                        echo ($stats['total_paid'] > 0 && $totalOrdersCount > 0) ? number_format($stats['total_paid'] / $totalOrdersCount, 2, ',', '.') : '0,00';
                    ?></div>
                    <div class="stat-sub positive"><i class="fas fa-arrow-up"></i> +4.5%</div>
                </div>

                <!-- Pendentes -->
                <div class="stat-card ghost-blue">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <span class="stat-label">Aguardando</span>
                    <div class="stat-value"><?php echo $stats['pending_count']; ?></div>
                    <div class="stat-sub">Vendas Pendentes</div>
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
                    
                    <div class="revenue-by-method">
                        <h4 style="font-size: 0.85rem; color: var(--text-3); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 1px;">Receita por método</h4>
                        
                        <div class="progress-group">
                            <div class="progress-info">
                                <span>Pix</span>
                                <strong><?php echo $percPix; ?>%</strong>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar green" style="width: <?php echo $percPix; ?>%"></div>
                            </div>
                        </div>

                        <div class="progress-group">
                            <div class="progress-info">
                                <span>Cartão</span>
                                <strong><?php echo $percCard; ?>%</strong>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar purple" style="width: <?php echo $percCard; ?>%"></div>
                            </div>
                        </div>

                        <div class="progress-group">
                            <div class="progress-info">
                                <span>Boleto</span>
                                <strong><?php echo $percBoleto; ?>%</strong>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar yellow" style="width: <?php echo $percBoleto; ?>%"></div>
                            </div>
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
                        <div style="display: flex; gap: 8px;">
                            <button id="btn-edit-wallet" class="btn-edit"> Editar </button>
                            <button id="btn-save-wallet" class="btn-edit hidden" style="background: var(--primary); color: black; border: none; font-weight: 700;"> Salvar </button>
                        </div>
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

    <!-- MODAIS MODERNOS v34.0 -->
    <div id="modal-qr" class="modal hidden">
        <div class="modal-content glass-premium">
            <button class="close-modal"><i class="fas fa-times"></i></button>
            
            <div class="modal-header-v2">
                <div class="pix-logo-badge">
                    <img src="https://logopng.com.br/logos/pix-106.png" alt="Pix" class="pix-img-status">
                </div>
                <h3>Pagamento via Pix</h3>
                <div class="status-badge pending-pulse">
                    <span class="pulse-dot"></span>
                    Aguardando Pagamento
                </div>
            </div>
            
            <div id="modal-amount" class="modal-amount-v2">R$ 0,00</div>
            
            <div class="timer-container">
                <div class="timer-label">Expira em:</div>
                <div id="pix-countdown" class="timer-value">20:00</div>
            </div>

            <div class="qr-wrapper-v2">
                <div class="qr-placeholder" id="qr-placeholder-v2">
                    <div class="spinner"></div>
                </div>
            </div>
            
            <div class="copy-section-v2">
                <label class="input-label">Copia e Cola:</label>
                <div class="copy-input-group">
                    <input type="text" id="pix-code-text" readonly value="Carregando...">
                    <button id="btn-copy-pix" class="btn-copy-v2" title="Copiar código">
                        <i class="far fa-copy"></i>
                    </button>
                </div>
            </div>
            
            <div class="modal-footer-v2">
                <p><i class="fas fa-shield-alt"></i> Pagamento 100% Seguro</p>
                <p><i class="fas fa-bolt"></i> Saldo liberado na hora</p>
            </div>
        </div>
    </div>

    <div id="modal-confirm" class="modal hidden">
        <div class="modal-content glass" style="max-width: 400px; text-align: center;">
            <div style="font-size: 3rem; color: var(--danger); margin-bottom: 1.5rem;"><i class="fas fa-exclamation-triangle"></i></div>
            <h3 style="margin-bottom: 1rem;">Confirmar Exclusão</h3>
            <p style="color: var(--text-dim); margin-bottom: 2rem;">Tem certeza que deseja excluir esta transação do seu histórico? Esta ação é irreversível.</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <button id="btn-confirm-cancel" class="btn-lp-outline" style="border-radius: 12px; height: 48px;">Cancelar</button>
                <button id="btn-confirm-delete" class="btn-primary" style="background: var(--danger); color: white; border-radius: 12px; height: 48px;">Excluir</button>
            </div>
        </div>
    </div>

    <script src="script.js?v=124.0"></script>
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

