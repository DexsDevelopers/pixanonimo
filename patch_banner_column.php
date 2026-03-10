<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE checkouts ADD COLUMN checkout_banner_url VARCHAR(255) AFTER custom_html_body;");
    echo "Coluna checkout_banner_url adicionada com sucesso ou já existente.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
         echo "A coluna checkout_banner_url já existe.\n";
    } else {
         die("Erro ao alterar tabela: " . $e->getMessage() . "\n");
    }
}
