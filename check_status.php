<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$pixId = $_GET['pix_id'] ?? '';

if (empty($pixId)) {
    echo json_encode(['error' => 'ID do Pix não informado']);
    exit;
}

$stmt = $pdo->prepare("SELECT status FROM transactions WHERE pix_id = ? AND user_id = ?");
$stmt->execute([$pixId, $_SESSION['user_id']]);
$transaction = $stmt->fetch();

if ($transaction) {
    echo json_encode(['status' => $transaction['status']]);
} else {
    echo json_encode(['status' => 'not_found']);
}

