<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE transactions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Colunas na tabela transactions: " . implode(', ', $columns);
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
