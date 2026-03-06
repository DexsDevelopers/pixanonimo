<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN is_demo TINYINT(1) DEFAULT 0");
    echo "Coluna is_demo adicionada com sucesso!\n";
} catch (PDOException $e) {
    echo "Erro ou coluna já existe: " . $e->getMessage() . "\n";
}
unlink(__FILE__);
?>
