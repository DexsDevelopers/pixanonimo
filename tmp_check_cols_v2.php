<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM transactions");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cols, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo $e->getMessage();
}
