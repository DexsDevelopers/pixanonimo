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
        'platform_profit' => $totalProfit,
        'affiliate_rate' => $currentAffRate,
        'default_tax' => $currentDefTax
    ],
    'users' => $users,
    'withdrawals' => $withdrawals,
    'apis' => $apis
]);
