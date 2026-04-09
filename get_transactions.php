<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$limit  = min((int)($_GET['limit'] ?? 200), 500);
$offset = max((int)($_GET['offset'] ?? 0), 0);

$stmt = $pdo->prepare(
    "SELECT *, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at)) as seconds_old
     FROM transactions
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT {$limit} OFFSET {$offset}"
);
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
$stmtTotal->execute([$userId]);
$total = (int)$stmtTotal->fetchColumn();

$formatted = [];
foreach ($rows as $t) {
    $status = $t['status'];
    $displayStatus = 'Pendente';
    $badgeClass = 'pending';

    if ($status === 'paid') {
        $displayStatus = 'Pago';
        $badgeClass = 'approved';
    } elseif ($status === 'pending') {
        if ($t['seconds_old'] > 1200) {
            $displayStatus = 'Expirado';
            $badgeClass = 'expired';
        }
    } elseif ($status === 'rejected' || $status === 'failed') {
        $displayStatus = 'Rejeitado';
        $badgeClass = 'rejected';
    } elseif ($status === 'expired') {
        $displayStatus = 'Expirado';
        $badgeClass = 'expired';
    }

    $formatted[] = [
        'id'             => $t['id'],
        'pix_id'         => $t['pix_id'] ?? '',
        'date'           => date('d/m/Y H:i', strtotime($t['created_at'])),
        'amount_brl'     => number_format($t['amount_brl'], 2, ',', '.'),
        'amount_net_brl' => number_format($t['amount_net_brl'], 2, ',', '.'),
        'status'         => $displayStatus,
        'badge'          => $badgeClass,
        'customer_name'  => $t['customer_name'] ?? 'Sem nome',
        'qr_image'       => $t['qr_image'] ?? '',
        'pix_code'       => $t['pix_code'] ?? '',
        'seconds_old'    => (int)$t['seconds_old'],
    ];
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'transactions' => $formatted, 'total' => $total]);
