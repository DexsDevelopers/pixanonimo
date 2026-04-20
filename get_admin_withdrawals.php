<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

try {
    $status = $_GET['status'] ?? 'all';
    $search = trim($_GET['search'] ?? '');

    // Stats
    $pendingCount  = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending'")->fetchColumn();
    $pendingAmount = (float)($pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status = 'pending'")->fetchColumn() ?: 0);
    $paidAmount    = (float)($pdo->query("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status = 'completed'")->fetchColumn() ?: 0);
    $paidCount     = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'completed'")->fetchColumn();
    $rejectedCount = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'rejected'")->fetchColumn();
    $todayPending  = (int)$pdo->query("SELECT COUNT(*) FROM withdrawals WHERE status = 'pending' AND DATE(created_at) = CURDATE()")->fetchColumn();

    // Main query - use w.* like get_admin_data.php does
    $sql = "SELECT w.*, u.email, u.full_name AS user_full_name, u.pix_key AS user_pix_key
            FROM withdrawals w
            JOIN users u ON w.user_id = u.id
            WHERE 1=1";
    $params = [];

    if ($status !== 'all') {
        $sql .= " AND w.status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR COALESCE(w.pix_key, u.pix_key) LIKE ?)";
        $like = "%$search%";
        array_push($params, $like, $like, $like);
    }

    $sql .= " ORDER BY w.created_at DESC LIMIT 200";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normalize data - fill missing fields from users table
    $withdrawals = array_map(function($w) {
        $w['full_name'] = $w['full_name'] ?: $w['user_full_name'];
        $w['pix_key']   = $w['pix_key'] ?: $w['user_pix_key'];
        unset($w['user_full_name'], $w['user_pix_key']);
        return $w;
    }, $rows);

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
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
