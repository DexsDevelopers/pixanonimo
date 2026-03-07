<?php
require 'includes/db.php';
$stmt = $pdo->query("SELECT status, count(id) from users group by status");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
