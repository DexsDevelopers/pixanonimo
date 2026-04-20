<?php
require_once 'includes/db.php';

if (!isAdmin()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$status = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// Stats
$pendingCount  = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
$pendingAmount = (float)($pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status = 'pending'")->fetchColumn() ?: 0);
$paidAmount    = (float)($pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status = 'completed'")->fetchColumn() ?: 0);
$paidCount     = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'completed'")->fetchColumn();
$rejectedCount = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'rejected'")->fetchColumn();
$todayPending  = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending' AND DATE(created_at) = CURDATE()")->fetchColumn();

// Main query
$sql = "SELECT w.id, w.user_id, w.amount, COALESCE(w.pix_key, u.pix_key) AS pix_key, w.status, w.tx_hash, COALESCE(w.full_name, u.full_name) AS full_name, w.type, w.created_at,
               u.email
        FROM withdrawals w
        JOIN users u ON w.user_id = u.id
        WHERE 1=1";
$params = [];

if ($status !== 'all') {
    $sql .= " AND w.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $sql .= " AND (w.full_name LIKE ? OR u.email LIKE ? OR w.pix_key LIKE ?)";
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}

$sql .= " ORDER BY w.created_at DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'stats' => [
        'pending_count'  => $pendingCount,
        'pending_amount' => $pendingAmount,
        'paid_amount'    => $paidAmount,
        'paid_count'     => $paidCount,
        'rejected_count' => $rejectedCount,
        'today_pending'  => $todayPending,
    ],
    'withdrawals' => $withdrawals,
]);
