<?php
require_once 'includes/db.php';

try {
    // 1. Renomear coluna na tabela users
    $pdo->exec("ALTER TABLE users CHANGE COLUMN liquid_address pix_key VARCHAR(255)");
    echo "Tabela 'users' atualizada com sucesso!<br>";

    // 2. Renomear coluna na tabela withdrawals
    $pdo->exec("ALTER TABLE withdrawals CHANGE COLUMN liquid_address pix_key VARCHAR(255)");
    echo "Tabela 'withdrawals' atualizada com sucesso!<br>";

    echo "Migração concluída com sucesso!";
} catch (Exception $e) {
    echo "Erro na migração: " . $e->getMessage();
}
?>
