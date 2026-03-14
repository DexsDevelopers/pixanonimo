<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

// --- 1. DADOS DO USUÁRIO (SALDO) ---
$stmt = $pdo->prepare("SELECT balance, commission_rate, pix_key, status, is_demo FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'Usuário não encontrado']);
    exit;
}

// --- 2. ESTATÍSTICAS ---
// Para admin: usar lucro da plataforma como saldo
$displayBalance = $user['balance'];
if (isAdmin()) {
    $stmtProfit = $pdo->query("SELECT SUM((amount_brl - amount_net_brl) - (amount_brl * 0.02)) as total FROM transactions WHERE status = 'paid'");
    $displayBalance = $stmtProfit->fetchColumn() ?: 0;
}

$stats = [
    'balance_fmt' => number_format($displayBalance, 2, ',', '.'),
    'today_volume' => 0,
    'month_volume' => 0,
    'total_paid' => 0,
    'pending_count' => 0
];

if ($user['is_demo'] == 1) {
    // Lógica Demo: Receita Total = Saldo Atual + Saques Concluídos
    $stmtW = $pdo->prepare("SELECT SUM(amount) as total FROM withdrawals WHERE user_id = ? AND status = 'completed'");
    $stmtW->execute([$userId]);
    $totalWithdrawn = $stmtW->fetch()['total'] ?? 0;
    
    $totalPaidVal = $user['balance'] + $totalWithdrawn;
    $monthVolVal = $totalPaidVal * 0.82;
    $todayVolVal = $totalPaidVal * 0.14;
    $totalOrdersCount = floor($totalPaidVal / 42) + 7;
    $pendingCount = floor($totalOrdersCount * 0.3);

    $stats['total_paid'] = number_format($totalPaidVal, 2, ',', '.');
    $stats['month_volume'] = number_format($monthVolVal, 2, ',', '.');
    $stats['today_volume'] = number_format($todayVolVal, 2, ',', '.');
    $stats['pending_count'] = $pendingCount;
} else {
    // Volume Hoje (24h)
    $stmtToday = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $stmtToday->execute([$userId]);
    $stats['today_volume'] = number_format($stmtToday->fetch()['vol'] ?? 0, 2, ',', '.');

    // Volume Mês Atual
    $stmtMonth = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $stmtMonth->execute([$userId]);
    $stats['month_volume'] = number_format($stmtMonth->fetch()['vol'] ?? 0, 2, ',', '.');

    // Total Acumulado (Pago)
    $stmtTotal = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid'");
    $stmtTotal->execute([$userId]);
    $stats['total_paid'] = number_format($stmtTotal->fetch()['vol'] ?? 0, 2, ',', '.');

    // Cobranças Pendentes
    $stmtPending = $pdo->prepare("SELECT COUNT(*) as qtd FROM transactions WHERE user_id = ? AND status = 'pending'");
    $stmtPending->execute([$userId]);
    $stats['pending_count'] = $stmtPending->fetch()['qtd'] ?? 0;
}

// --- 3. ÚLTIMAS TRANSAÇÕES ---
$stmtRows = $pdo->prepare("SELECT *, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at)) as seconds_old FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmtRows->execute([$userId]);
$rows = $stmtRows->fetchAll();

$formattedRows = [];
foreach ($rows as $t) {
    // Lógica de status
    $status = $t['status'];
    $displayStatus = 'Pendente';
    $badgeClass = 'pending';

    if ($status == 'paid') {
        $displayStatus = 'Pago';
        $badgeClass = 'approved';
    } elseif ($status == 'pending') {
        if ($t['seconds_old'] > 1200) {
            $displayStatus = 'Expirado';
            $badgeClass = 'expired';
        }
    } elseif ($status == 'rejected') {
        $displayStatus = 'Rejeitado';
        $badgeClass = 'rejected';
    }

    $formattedRows[] = [
        'id' => $t['id'],
        'date' => date('d/m/Y H:i', strtotime($t['created_at'])),
        'amount_brl' => number_format($t['amount_brl'], 2, ',', '.'),
        'amount_net_brl' => number_format($t['amount_net_brl'], 2, ',', '.'),
        'status' => $displayStatus,
        'badge' => $badgeClass,
        'qr_image' => $t['qr_image'] ?? '',
        'pix_code' => $t['pix_code'] ?? ''
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'balance' => $stats['balance_fmt'],
    'stats' => $stats,
    'transactions' => $formattedRows
]);

