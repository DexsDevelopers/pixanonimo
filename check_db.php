<?php
require_once 'includes/db.php';

try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Colunas na tabela 'users':<br>";
    foreach ($columns as $col) {
        echo "- $col<br>";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
