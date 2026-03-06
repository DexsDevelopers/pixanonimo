<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];

// Filtros
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';

$query = "SELECT *, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at)) as seconds_old 
          FROM transactions 
          WHERE user_id = ?";
$params = [$userId];

if ($statusFilter) {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

if ($dateFilter) {
    if ($dateFilter == 'today') {
        $query .= " AND created_at >= CURDATE()";
    } elseif ($dateFilter == 'yesterday') {
        $query .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND created_at < CURDATE()";
    } elseif ($dateFilter == '7days') {
        $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($dateFilter == '30days') {
        $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Estatísticas Rápidas para a página de vendas
$stmtStats = $pdo->prepare("SELECT 
    COUNT(*) as total_count,
    SUM(CASE WHEN status = 'paid' THEN amount_brl ELSE 0 END) as total_paid_amount,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
    FROM transactions WHERE user_id = ?");
$stmtStats->execute([$userId]);
$pageStats = $stmtStats->fetch();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#000000">
    <title>Vendas - Ghost Pix</title>
    <link rel="stylesheet" href="style.css?v=117.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="top-header">
            <div>
                <h1>Minhas Vendas 💰</h1>
                <p>Acompanhe e gerencie todas as suas transações em tempo real.</p>
            </div>
            <div class="wallet-status">
                <span class="status-indicator"></span>
                Total de Vendas: <strong><?php echo $pageStats['total_count']; ?></strong>
            </div>
        </header>

        <!-- Filtros Rápidos -->
        <div style="display: flex; gap: 1rem; margin-bottom: 2rem; overflow-x: auto; padding-bottom: 5px;">
            <a href="vendas.php" class="badge <?php echo !$statusFilter ? 'paid' : ''; ?>" style="text-decoration: none; padding: 10px 20px; border: 1px solid var(--border);">Todas</a>
            <a href="vendas.php?status=paid" class="badge <?php echo $statusFilter == 'paid' ? 'paid' : ''; ?>" style="text-decoration: none; padding: 10px 20px; border: 1px solid var(--border);">Pagas</a>
            <a href="vendas.php?status=pending" class="badge <?php echo $statusFilter == 'pending' ? 'pending' : ''; ?>" style="text-decoration: none; padding: 10px 20px; border: 1px solid var(--border);">Pendentes</a>
            <a href="vendas.php?status=expired" class="badge <?php echo $statusFilter == 'expired' ? 'expired' : ''; ?>" style="text-decoration: none; padding: 10px 20px; border: 1px solid var(--border);">Expiradas</a>
        </div>

        <div class="analytics-grid" style="margin-bottom: 2.5rem;">
            <div class="stat-card ghost-green">
                <div class="stat-icon"><i class="fas fa-check-double"></i></div>
                <span class="stat-label">Total Pago</span>
                <div class="stat-value">R$ <?php echo number_format($pageStats['total_paid_amount'] ?? 0, 2, ',', '.'); ?></div>
                <div class="stat-sub">Volume convertido</div>
            </div>
            <div class="stat-card ghost-blue">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <span class="stat-label">Pendentes</span>
                <div class="stat-value"><?php echo $pageStats['pending_count']; ?></div>
                <div class="stat-sub">Aguardando pagamento</div>
            </div>
        </div>

        <div class="card glass full-width">
            <div class="card-header">
                <div class="card-title-group">
                    <div class="card-icon"><i class="fas fa-list-ul"></i></div>
                    <h3 class="card-title">Extrato de Transações</h3>
                </div>
                <div style="display: flex; gap: 10px;">
                    <select onchange="window.location.href='vendas.php?date=' + this.value" style="background: var(--bg-card); border: 1px solid var(--border); color: #fff; padding: 5px 10px; border-radius: 8px; font-size: 0.85rem; outline: none; cursor: pointer;">
                        <option value="">Período: Sempre</option>
                        <option value="today" <?php echo $dateFilter == 'today' ? 'selected' : ''; ?>>Hoje</option>
                        <option value="yesterday" <?php echo $dateFilter == 'yesterday' ? 'selected' : ''; ?>>Ontem</option>
                        <option value="7days" <?php echo $dateFilter == '7days' ? 'selected' : ''; ?>>Últimos 7 dias</option>
                        <option value="30days" <?php echo $dateFilter == '30days' ? 'selected' : ''; ?>>Últimos 30 dias</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produto / Referência</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 3rem; color: var(--text-3);">
                                    <i class="fas fa-box-open" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                                    Nenhuma transação encontrada com os filtros selecionados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($transactions as $t): ?>
                            <tr>
                                <td>#<?php echo $t['id']; ?></td>
                                <td>
                                    <strong><?php echo $t['external_id'] ?: 'Pagamento Pix'; ?></strong>
                                    <br><small style="color: var(--text-3); font-size: 0.75rem;"><?php echo $t['customer_name'] ?: 'Sem nome'; ?></small>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #fff;">R$ <?php echo number_format($t['amount_brl'], 2, ',', '.'); ?></span>
                                </td>
                                <td><span class="badge <?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span></td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($t['created_at'])); ?>
                                    <br><small style="color: var(--text-3);"><?php echo date('H:i', strtotime($t['created_at'])); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="script.js?v=110.0"></script>
</body>
</html>
