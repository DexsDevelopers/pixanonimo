<?php
require_once 'includes/db.php';

echo "<h1>Ghost Pix - Migração de Banco de Dados V3</h1>";

try {
    // 1. Adicionar api_key na tabela users
    echo "Verificando tabela 'users'...<br>";
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS api_key VARCHAR(64) UNIQUE AFTER pix_key");
    echo "✅ Coluna 'api_key' ok!<br>";

    // 2. Adicionar callback_url na tabela transactions
    echo "Verificando tabela 'transactions'...<br>";
    $pdo->exec("ALTER TABLE transactions ADD COLUMN IF NOT EXISTS callback_url TEXT AFTER qr_image");
    echo "✅ Coluna 'callback_url' ok!<br>";

    echo "<h3>🚀 Migração concluída com sucesso!</h3>";
    echo "<p>Você já pode fechar esta página e tentar gerar sua API Key novamente.</p>";
    
    // Auto-delete (opcional por segurança)
    // unlink(__FILE__); 

} catch (Exception $e) {
    echo "<h3 style='color:red'>❌ Erro na migração:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    
    // Fallback caso o IF NOT EXISTS falhe em versões antigas do MySQL
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "<p>A coluna já existe. Tudo certo!</p>";
    }
}
?>

