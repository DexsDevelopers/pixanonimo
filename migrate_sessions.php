<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_token VARCHAR(255) NULL");
    echo "Coluna 'remember_token' preparada!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
