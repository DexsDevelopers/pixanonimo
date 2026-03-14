<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// --- Período selecionado ---
$period = $_GET['p'] ?? '7d';
if (!in_array($period, ['today', '7d', '30d', 'all'])) $period = '7d';

$periodSQL = '';
$periodLabel = 'Todos';
$chartDays = 7;
switch ($period) {
    case 'today':
        $periodSQL = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        $periodLabel = 'Hoje';
        $chartDays = 1;
        break;
    case '7d':
        $periodSQL = " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $periodLabel = 'Últimos 7 dias';
        $chartDays = 7;
        break;
    case '30d':
        $periodSQL = " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $periodLabel = 'Últimos 30 dias';
        $chartDays = 30;
        break;
    case 'all':
        $periodSQL = '';
        $periodLabel = 'Todo período';
        $chartDays = 30;
        break;
}

// Estatísticas do Usuário (filtradas por período)
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

// Volume no Período Selecionado
$stmtMonth = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid'" . $periodSQL);
$stmtMonth->execute([$userId]);
$stats['month_volume'] = $stmtMonth->fetch()['vol'] ?? 0;

// Total Acumulado no Período
$stmtTotal = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid'" . $periodSQL);
$stmtTotal->execute([$userId]);
$stats['total_paid'] = $stmtTotal->fetch()['vol'] ?? 0;

// Cobranças Pendentes
$stmtPending = $pdo->prepare("SELECT COUNT(*) as qtd FROM transactions WHERE user_id = ? AND status = 'pending'" . $periodSQL);
$stmtPending->execute([$userId]);
$stats['pending_count'] = $stmtPending->fetch()['qtd'] ?? 0;

$totalOrdersCount = 0;
if ($user['is_demo'] == 1) {
    $stmtW = $pdo->prepare("SELECT SUM(amount) as total FROM withdrawals WHERE user_id = ? AND status = 'completed'");
    $stmtW->execute([$userId]);
    $totalWithdrawn = $stmtW->fetch()['total'] ?? 0;
    
    $stats['total_paid'] = $user['balance'] + $totalWithdrawn;
    $stats['month_volume'] = $stats['total_paid'] * 0.82;
    $stats['today_volume'] = $stats['total_paid'] * 0.14;
    $totalOrdersCount = floor($stats['total_paid'] / 42) + 7;
    $stats['pending_count'] = floor($totalOrdersCount * 0.3);
} else {
    $stmtOrders = $pdo->prepare("SELECT COUNT(*) as qtd FROM transactions WHERE user_id = ? AND status = 'paid'" . $periodSQL);
    $stmtOrders->execute([$userId]);
    $totalOrdersCount = $stmtOrders->fetch()['qtd'] ?? 0;
}

// Para admin: calcular lucro da plataforma
$displayBalance = $user['balance'];
if ($user['is_admin']) {
    $stmtProfit = $pdo->query("SELECT SUM((amount_brl - amount_net_brl) - (amount_brl * 0.02)) as total FROM transactions WHERE status = 'paid'" . $periodSQL);
    $displayBalance = $stmtProfit->fetchColumn() ?: 0;
}

$transactions = $pdo->prepare("SELECT *, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at)) as seconds_old FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$transactions->execute([$userId]);
$rows = $transactions->fetchAll();

// Receita por Método
$percPix = 0; $percCard = 0; $percBoleto = 0;
try {
    $stmtMethod = $pdo->prepare("SELECT 
        SUM(CASE WHEN method = 'pix' THEN amount_brl ELSE 0 END) as pix_vol,
        SUM(CASE WHEN method = 'card' THEN amount_brl ELSE 0 END) as card_vol,
        SUM(CASE WHEN method = 'boleto' THEN amount_brl ELSE 0 END) as boleto_vol
        FROM transactions WHERE user_id = ? AND status = 'paid'" . $periodSQL);
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
    if ($stats['total_paid'] > 0) $percPix = 100;
}

// --- Gráfico de Vendas ---
$chartLabels = [];
$chartValues = [];
for ($i = $chartDays - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = $chartDays <= 7 ? date('D d', strtotime($date)) : date('d/m', strtotime($date));
    
    if ($user['is_demo'] == 1) {
        $chartValues[] = round(($stats['total_paid'] / max($chartDays, 1)) * (0.7 + (rand(0, 60) / 100)), 2);
    } else {
        $stmtChart = $pdo->prepare("SELECT COALESCE(SUM(amount_brl), 0) as vol FROM transactions WHERE user_id = ? AND status = 'paid' AND DATE(created_at) = ?");
        $stmtChart->execute([$userId, $date]);
        $chartValues[] = (float)$stmtChart->fetchColumn();
    }
}

// --- Taxa de Aprovação ---
$approvalRate = 0;
if ($user['is_demo'] == 1) {
    $approvalRate = 78.5;
} else {
    $stmtTotalTx = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?" . $periodSQL);
    $stmtTotalTx->execute([$userId]);
    $totalTx = (int)$stmtTotalTx->fetchColumn();
    
    $stmtPaidTx = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'paid'" . $periodSQL);
    $stmtPaidTx->execute([$userId]);
    $paidTx = (int)$stmtPaidTx->fetchColumn();
    
    $approvalRate = $totalTx > 0 ? round(($paidTx / $totalTx) * 100, 1) : 0;
}

$approvalBadgeClass = 'ghost-red';
if ($approvalRate >= 70) $approvalBadgeClass = 'ghost-green';
elseif ($approvalRate >= 50) $approvalBadgeClass = 'ghost-yellow';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#000000">
    <link rel="manifest" href="manifest.json">
    <title>Ghost Pix - Dashboard Premium</title>
    <link rel="stylesheet" href="style.css?v=125.2">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- WhatsApp Channel Announcement -->
            <a href="https://whatsapp.com/channel/0029VbC56v0GZNComh5KQ73J" target="_blank" class="card glass" style="display: flex; align-items: center; gap: 1rem; padding: 0.8rem 1.25rem; margin-bottom: 1.5rem; text-decoration: none; border: 1px solid rgba(37, 211, 102, 0.2); background: linear-gradient(90deg, rgba(37, 211, 102, 0.05), transparent); transition: all 0.3s ease;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(37, 211, 102, 0.1); color: #25d366; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div style="flex: 1;">
                    <h4 style="font-size: 0.85rem; font-weight: 700; color: #fff; margin: 0;">Canal de Novidades</h4>
                    <p style="font-size: 0.75rem; color: var(--text-2); margin: 0;">Entre no nosso canal e fique por dentro de todas as atualizações! 🚀</p>
                </div>
                <i class="fas fa-chevron-right" style="font-size: 0.8rem; color: var(--text-3);"></i>
            </a>

            <header class="top-header">
                <div>
                    <h1>Olá, <?php echo explode(' ', $_SESSION['full_name'] ?? 'Usuário')[0]; ?> 👋</h1>
                    <p>Bem-vindo ao seu painel Ghost Pix.</p>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <div class="period-filter" style="display: inline-flex; gap: 0; background: rgba(15, 15, 15, 0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 4px; backdrop-filter: blur(10px);">
                        <a href="?p=today" class="period-btn <?php echo $period=='today'?'active':''; ?>">Hoje</a>
                        <a href="?p=7d" class="period-btn <?php echo $period=='7d'?'active':''; ?>">7 dias</a>
                        <a href="?p=30d" class="period-btn <?php echo $period=='30d'?'active':''; ?>">30 dias</a>
                        <a href="?p=all" class="period-btn <?php echo $period=='all'?'active':''; ?>">Todos</a>
                    </div>
                    <div class="wallet-status">
                        <span class="status-indicator"></span>
                        Taxa: <strong><?php echo $user['commission_rate']; ?>%</strong>
                    </div>
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
                    <div class="stat-icon"><i class="fas fa-cart-shopping"></i></div>
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

                <!-- Taxa de Aprovação -->
                <div class="stat-card <?php echo $approvalBadgeClass; ?>">
                    <div class="stat-icon"><i class="fas fa-chart-pie"></i></div>
                    <span class="stat-label">Aprovação</span>
                    <div class="stat-value"><?php echo $approvalRate; ?>%</div>
                    <div class="stat-sub"><?php echo $approvalRate >= 70 ? 'Excelente' : ($approvalRate >= 50 ? 'Regular' : 'Baixa'); ?></div>
                </div>
            </div>

            <!-- Gráfico de Vendas (Últimos 7 dias) -->
            <div class="card" style="margin-bottom: 0; padding: 1.5rem; border: 1px solid rgba(16, 185, 129, 0.1);">
                <div class="card-header" style="margin-bottom: 1rem;">
                    <div class="card-title-group">
                        <div class="card-icon" style="background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.2); color: #10b981;"><i class="fas fa-chart-line"></i></div>
                        <h3 class="card-title">Performance de Vendas</h3>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 0.8rem; color: var(--text-3);"><?php echo $periodLabel; ?></span>
                        <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--green); animation: pulse-dot 2s infinite;"></div>
                    </div>
                </div>
                <div style="position: relative; height: 220px; width: 100%;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Main Balance Card -->
                <div class="card">
                    <div class="card-header">
                <div class="card-title-group">
                            <div class="card-icon" style="background: rgba(34, 197, 94, 0.12); border-color: rgba(34, 197, 94, 0.2); color: #4ade80;"><i class="fas fa-wallet"></i></div>
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
                        <span style="font-size: 2.8rem; font-weight: 800; letter-spacing: -1px;"><?php echo number_format($displayBalance, 2, ',', '.'); ?></span>
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
                        <input type="text" id="wallet-input" value="<?php echo htmlspecialchars($user['pix_key'] ?? 'Não configurada'); ?>" readonly class="pix-key-input">
                        <button id="btn-copy-wallet" class="btn-icon-sm" title="Copiar chave"><i class="far fa-copy"></i></button>
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

                <!-- Recent Activity Feed -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title-group">
                            <div class="card-icon" style="background: rgba(249, 115, 22, 0.12); border-color: rgba(249, 115, 22, 0.2); color: #f97316;"><i class="fas fa-bolt-lightning"></i></div>
                            <h3 class="card-title">Atividade Recente</h3>
                        </div>
                    </div>
                    <div id="activity-feed" class="activity-feed-list" style="margin-top: 1rem;">
                        <!-- JS Dynamic Content -->
                        <div class="feed-placeholder" style="text-align: center; padding: 2rem; opacity: 0.5;">
                            <i class="fas fa-circle-notch fa-spin"></i> Carregando...
                        </div>
                    </div>
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
                            <tr class="responsive-row">
                                <td data-label="Data"><?php echo date('d/m H:i', strtotime($t['created_at'])); ?></td>
                                <td data-label="Bruto">R$ <?php echo number_format($t['amount_brl'], 2, ',', '.'); ?></td>
                                <td data-label="Líquido">R$ <?php echo number_format($t['amount_net_brl'], 2, ',', '.'); ?></td>
                                <td data-label="Status">
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
                                <td class="actions-cell" data-label="Ações">
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

    <script src="script.js?v=125.2"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('SW Registered'))
                    .catch(err => console.log('SW Error', err));
            });
        }

        // --- Chart.js: Gráfico de Vendas ---
        (function() {
            const ctx = document.getElementById('salesChart');
            if (!ctx) return;

            const chartLabels = <?php echo json_encode($chartLabels); ?>;
            const chartValues = <?php echo json_encode($chartValues); ?>;

            const gradient = ctx.getContext('2d');
            const bg = gradient.createLinearGradient(0, 0, 0, 220);
            bg.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
            bg.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Vendas (R$)',
                        data: chartValues,
                        borderColor: '#10b981',
                        backgroundColor: bg,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#000',
                        pointBorderWidth: 2,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: '#10b981',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.85)',
                            titleColor: '#fff',
                            bodyColor: '#10b981',
                            bodyFont: { weight: '700', size: 14 },
                            borderColor: 'rgba(16, 185, 129, 0.3)',
                            borderWidth: 1,
                            cornerRadius: 10,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                            ticks: { color: 'rgba(255,255,255,0.35)', font: { size: 11, family: 'Outfit' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                            ticks: {
                                color: 'rgba(255,255,255,0.35)',
                                font: { size: 11, family: 'Outfit' },
                                callback: function(value) { return 'R$ ' + value; }
                            }
                        }
                    }
                }
            });
        })();

    </script>
    <style>
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.5); }
        }
        .analytics-grid {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) !important;
        }

        /* Period Filter Buttons */
        .period-btn {
            padding: 7px 16px;
            border-radius: 9px;
            border: none;
            background: transparent;
            color: rgba(255,255,255,0.4);
            font-size: 0.78rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            font-family: inherit;
            white-space: nowrap;
        }
        .period-btn:hover {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.8);
        }
        .period-btn.active {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #000;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3), inset 0 1px 0 rgba(255,255,255,0.15);
        }

        /* Ghost Red variant overrides */
        .ghost-red .stat-icon { color: #f87171 !important; }
        .ghost-red .stat-value { color: #f87171 !important; }
        .ghost-red .stat-sub { color: #f87171 !important; }

        @media (max-width: 768px) {
            .period-filter { order: -1; }
            .top-header { flex-direction: column; gap: 1rem; }
        }
    </style>
</body>
</html>

