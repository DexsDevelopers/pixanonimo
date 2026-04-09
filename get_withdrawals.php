<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT id, amount, pix_key, status, tx_hash, created_at
     FROM withdrawals
     WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 50"
);
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

$statusLabels = [
    'pending'   => 'Pendente',
    'completed' => 'Pago',
    'rejected'  => 'Rejeitado',
    'fake'      => 'Pago',
];
$statusBadge = [
    'pending'   => 'pending',
    'completed' => 'approved',
    'rejected'  => 'rejected',
    'fake'      => 'approved',
];

$formatted = [];
foreach ($rows as $w) {
    $s = $w['status'];
    $formatted[] = [
        'id'        => $w['id'],
        'amount'    => number_format($w['amount'], 2, ',', '.'),
        'pix_key'   => $w['pix_key'] ?? '',
        'tx_hash'   => $w['tx_hash'] ?? '',
        'status'    => $statusLabels[$s] ?? ucfirst($s),
        'badge'     => $statusBadge[$s] ?? 'pending',
        'date'      => date('d/m/Y H:i', strtotime($w['created_at'])),
    ];
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'withdrawals' => $formatted]);
