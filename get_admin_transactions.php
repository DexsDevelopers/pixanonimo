<?php
require_once 'includes/db.php';

if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json');

// Filters
$statusFilter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = "1=1";
$params = [];

if ($statusFilter === 'paid') {
    $where .= " AND t.status = 'paid'";
} elseif ($statusFilter === 'pending') {
    $where .= " AND t.status = 'pending' AND t.created_at >= DATE_SUB(NOW(), INTERVAL 20 MINUTE)";
} elseif ($statusFilter === 'expired') {
    $where .= " AND t.status = 'pending' AND t.created_at < DATE_SUB(NOW(), INTERVAL 20 MINUTE)";
} elseif ($statusFilter === 'failed') {
    $where .= " AND t.status IN ('failed', 'rejected')";
}

if (!empty($search)) {
    $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR t.customer_name LIKE ? OR t.pix_id LIKE ?)";
    $searchParam = "%{$search}%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

// Count total
$countSql = "SELECT COUNT(*) FROM transactions t LEFT JOIN users u ON t.user_id = u.id WHERE {$where}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetchColumn();

// Fetch transactions
$sql = "SELECT t.*, u.full_name as user_name, u.email as user_email,
        (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(t.created_at)) as seconds_old
        FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE {$where} 
        ORDER BY t.created_at DESC 
        LIMIT {$limit} OFFSET {$offset}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$transactions = [];
foreach ($rows as $t) {
    $status = $t['status'];
    $displayStatus = 'Pendente';
    $badge = 'pending';

    if ($status === 'paid') {
        $displayStatus = 'Pago';
        $badge = 'approved';
    } elseif ($status === 'pending') {
        if ($t['seconds_old'] > 1200) {
            $displayStatus = 'Expirado';
            $badge = 'expired';
        }
    } elseif ($status === 'failed' || $status === 'rejected') {
        $displayStatus = 'Falhou';
        $badge = 'failed';
    }

    $transactions[] = [
        'id' => (int)$t['id'],
        'pix_id' => $t['pix_id'] ?? '',
        'user_id' => (int)$t['user_id'],
        'user_name' => $t['user_name'] ?? 'Desconhecido',
        'user_email' => $t['user_email'] ?? '',
        'customer_name' => $t['customer_name'] ?? 'Sem nome',
        'amount_brl' => number_format((float)$t['amount_brl'], 2, ',', '.'),
        'amount_net_brl' => number_format((float)$t['amount_net_brl'], 2, ',', '.'),
        'amount_raw' => (float)$t['amount_brl'],
        'amount_net_raw' => (float)$t['amount_net_brl'],
        'status' => $displayStatus,
        'badge' => $badge,
        'date' => date('d/m/Y H:i', strtotime($t['created_at'])),
        'created_at' => $t['created_at'],
        'seconds_old' => (int)$t['seconds_old']
    ];
}

// Stats
$statsSQL = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
    SUM(CASE WHEN status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 20 MINUTE) THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 20 MINUTE) THEN 1 ELSE 0 END) as expired_count,
    SUM(CASE WHEN status = 'paid' THEN amount_brl ELSE 0 END) as total_paid_volume,
    SUM(CASE WHEN status = 'paid' THEN amount_net_brl ELSE 0 END) as total_net_volume,
    SUM(CASE WHEN status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN amount_brl ELSE 0 END) as today_volume,
    SUM(CASE WHEN status = 'paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) as today_count
    FROM transactions";
$statsRow = $pdo->query($statsSQL)->fetch();

echo json_encode([
    'success' => true,
    'transactions' => $transactions,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $totalCount,
        'pages' => ceil($totalCount / $limit)
    ],
    'stats' => [
        'total' => (int)$statsRow['total'],
        'paid_count' => (int)$statsRow['paid_count'],
        'pending_count' => (int)$statsRow['pending_count'],
        'expired_count' => (int)$statsRow['expired_count'],
        'total_paid_volume' => number_format((float)$statsRow['total_paid_volume'], 2, ',', '.'),
        'total_net_volume' => number_format((float)$statsRow['total_net_volume'], 2, ',', '.'),
        'today_volume' => number_format((float)$statsRow['today_volume'], 2, ',', '.'),
        'today_count' => (int)$statsRow['today_count']
    ]
]);
