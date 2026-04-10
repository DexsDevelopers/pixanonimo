<?php
require_once 'includes/db.php';

if (!isAdmin()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// 1. Configurações Globais
$affRateStmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'affiliate_commission_rate'");
$currentAffRate = (float)($affRateStmt->fetchColumn() ?: '10');

$defTaxStmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'default_user_tax'");
$currentDefTax = (float)($defTaxStmt->fetchColumn() ?: '4.0');

$stmtProfit = $pdo->query("SELECT SUM((amount_brl - amount_net_brl) - (amount_brl * 0.02)) as total FROM transactions WHERE status = 'paid'");
$totalProfit = (float)($stmtProfit->fetchColumn() ?: 0);

// ── Dashboard Metrics ──────────────────────────────────────────────────────────
$today    = date('Y-m-d');
$weekAgo  = date('Y-m-d', strtotime('-7 days'));
$monthAgo = date('Y-m-d', strtotime('-30 days'));

// Users
$totalUsers       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$usersToday       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND DATE(created_at) = '$today'")->fetchColumn();
$usersThisWeek    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND DATE(created_at) >= '$weekAgo'")->fetchColumn();
$usersThisMonth   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND DATE(created_at) >= '$monthAgo'")->fetchColumn();
$approvedUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND status = 'approved'")->fetchColumn();
$pendingUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND status = 'pending'")->fetchColumn();
$blockedUsers     = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND status = 'blocked'")->fetchColumn();
$demoUsers        = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE is_demo = 1")->fetchColumn();

// Transactions / Revenue
$txToday          = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'paid' AND DATE(created_at) = '$today'")->fetchColumn();
$txThisWeek       = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'paid' AND DATE(created_at) >= '$weekAgo'")->fetchColumn();
$revenueToday     = (float)($pdo->query("SELECT COALESCE(SUM(amount_brl),0) FROM transactions WHERE status = 'paid' AND DATE(created_at) = '$today'")->fetchColumn() ?: 0);
$revenueThisWeek  = (float)($pdo->query("SELECT COALESCE(SUM(amount_brl),0) FROM transactions WHERE status = 'paid' AND DATE(created_at) >= '$weekAgo'")->fetchColumn() ?: 0);
$revenueThisMonth = (float)($pdo->query("SELECT COALESCE(SUM(amount_brl),0) FROM transactions WHERE status = 'paid' AND DATE(created_at) >= '$monthAgo'")->fetchColumn() ?: 0);
$revenueTotal     = (float)($pdo->query("SELECT COALESCE(SUM(amount_brl),0) FROM transactions WHERE status = 'paid'")->fetchColumn() ?: 0);
$pendingTx        = (int)$pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 20 MINUTE)")->fetchColumn();

// Products & Orders
$totalProducts    = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$activeProducts   = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$pendingProducts  = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'pending'")->fetchColumn();

// Withdrawals
$pendingWithdraws = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
$withdrawsTotal   = (float)($pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status = 'paid'")->fetchColumn() ?: 0);

// New registrations chart (last 7 days)
$registrationChart = [];
for ($i = 6; $i >= 0; $i--) {
    $day   = date('Y-m-d', strtotime("-$i days"));
    $label = date('d/m', strtotime("-$i days"));
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = '$day' AND is_admin = 0")->fetchColumn();
    $registrationChart[] = ['day' => $label, 'count' => $count];
}

// 2. Lista de Usuários (Filtros)
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

$sql = "SELECT id, full_name, email, pix_key, balance, commission_rate, status, is_demo, created_at FROM users WHERE is_admin = 0";
$params = [];

if (!empty($search)) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR pix_key LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($status_filter === 'pending') {
    $sql .= " AND status = 'pending'";
} elseif ($status_filter === 'active') {
    $sql .= " AND status = 'approved'";
} elseif ($status_filter === 'blocked') {
    $sql .= " AND status = 'blocked'";
} elseif ($status_filter === 'demo') {
    $sql .= " AND is_demo = 1";
}

$sql .= " ORDER BY created_at DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Solicitações de Saque
$stmtW = $pdo->query("SELECT w.*, u.email, u.pix_key FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'pending' ORDER BY w.created_at DESC");
$withdrawals = $stmtW->fetchAll(PDO::FETCH_ASSOC);

// 3b. Últimas transações da plataforma (todas as vendas)
$stmtTx = $pdo->query(
    "SELECT t.id, t.amount_brl, t.amount_net_brl, t.status, t.created_at,
            t.customer_name, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(t.created_at)) as seconds_old,
            u.full_name as seller_name
     FROM transactions t
     JOIN users u ON t.user_id = u.id
     ORDER BY t.created_at DESC LIMIT 40"
);
$rawTx = $stmtTx->fetchAll(PDO::FETCH_ASSOC);
$allTransactions = [];
foreach ($rawTx as $t) {
    $status = $t['status'];
    $displayStatus = 'Pendente'; $badgeClass = 'pending';
    if ($status === 'paid')    { $displayStatus = 'Pago';      $badgeClass = 'approved'; }
    elseif ($status === 'pending' && $t['seconds_old'] > 1200) { $displayStatus = 'Expirado'; $badgeClass = 'expired'; }
    elseif ($status === 'rejected') { $displayStatus = 'Rejeitado'; $badgeClass = 'rejected'; }
    $allTransactions[] = [
        'id'           => $t['id'],
        'date'         => date('d/m/Y H:i', strtotime($t['created_at'])),
        'amount_brl'   => number_format($t['amount_brl'], 2, ',', '.'),
        'amount_net_brl' => number_format($t['amount_net_brl'], 2, ',', '.'),
        'status'       => $displayStatus,
        'badge'        => $badgeClass,
        'customer_name'=> $t['customer_name'] ?? 'Sem nome',
        'seller_name'  => $t['seller_name'],
        'seconds_old'  => (int)$t['seconds_old'],
    ];
}

// 4. APIs PixGo
try {
    $apis = $pdo->query("SELECT * FROM pixgo_apis ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $apis = [];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'stats' => [
        'platform_profit'   => $totalProfit,
        'affiliate_rate'    => $currentAffRate,
        'default_tax'       => $currentDefTax,
        // Users
        'total_users'       => $totalUsers,
        'users_today'       => $usersToday,
        'users_this_week'   => $usersThisWeek,
        'users_this_month'  => $usersThisMonth,
        'approved_users'    => $approvedUsers,
        'pending_users'     => $pendingUsers,
        'blocked_users'     => $blockedUsers,
        'demo_users'        => $demoUsers,
        // Transactions
        'tx_today'          => $txToday,
        'tx_this_week'      => $txThisWeek,
        'revenue_today'     => $revenueToday,
        'revenue_this_week' => $revenueThisWeek,
        'revenue_this_month'=> $revenueThisMonth,
        'revenue_total'     => $revenueTotal,
        'pending_tx'        => $pendingTx,
        // Products
        'total_products'    => $totalProducts,
        'active_products'   => $activeProducts,
        'pending_products'  => $pendingProducts,
        // Withdrawals
        'pending_withdrawals' => $pendingWithdraws,
        'withdrawals_total'   => $withdrawsTotal,
        // Chart
        'registration_chart'  => $registrationChart,
    ],
    'users'            => $users,
    'withdrawals'      => $withdrawals,
    'all_transactions' => $allTransactions,
    'apis'             => $apis
]);
