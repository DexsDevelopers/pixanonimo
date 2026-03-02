<?php
require_once 'includes/db.php';

// 1. Renomear coluna na tabela users (liquid_address -> pix_key)
try {
    $pdo->exec("ALTER TABLE users CHANGE COLUMN liquid_address pix_key VARCHAR(255)");
    echo "Tabela 'users' (pix_key) atualizada!<br>";
} catch (Exception $e) {
    echo "Aviso: Coluna 'pix_key' já existe ou 'liquid_address' não encontrada.<br>";
}

// 2. Adicionar coluna full_name em users
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN full_name VARCHAR(255) AFTER password");
    echo "Coluna 'full_name' adicionada com sucesso!<br>";
} catch (Exception $e) {
    echo "Aviso: Coluna 'full_name' já existe.<br>";
}

// 3. Renomear coluna na tabela withdrawals
try {
    $pdo->exec("ALTER TABLE withdrawals CHANGE COLUMN liquid_address pix_key VARCHAR(255)");
    echo "Tabela 'withdrawals' (pix_key) atualizada!<br>";
} catch (Exception $e) {
    echo "Aviso: Coluna 'pix_key' em withdrawals já existe.<br>";
}

// 4. Adicionar coluna full_name em withdrawals (opcional, mas bom pra histórico)
try {
    $pdo->exec("ALTER TABLE withdrawals ADD COLUMN full_name VARCHAR(255) AFTER user_id");
} catch (Exception $e) {}

echo "<b>Migração concluída!</b>";
} catch (Exception $e) {
    echo "Erro Geral: " . $e->getMessage();
}
?>
