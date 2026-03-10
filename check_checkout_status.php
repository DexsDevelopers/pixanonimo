<?php
require_once 'includes/db.php';

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$pixId = $_GET['pix_id'] ?? '';

if (empty($pixId)) {
    echo json_encode(['error' => 'ID do Pix não informado']);
    exit;
}

$stmt = $pdo->prepare("SELECT status FROM transactions WHERE pix_id = ?");
$stmt->execute([$pixId]);
$transaction = $stmt->fetch();

if ($transaction) {
    echo json_encode(['status' => $transaction['status']]);
} else {
    echo json_encode(['status' => 'not_found']);
}
