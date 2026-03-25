<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

echo json_encode([
    'success' => true,
    'publicKey' => VAPID_PUBLIC_KEY
]);
