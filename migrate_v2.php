<?php
// Ativar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php';

echo "<h1>🛠️ Iniciando Migração de Banco de Dados V2</h1>";
echo "<p>Verificando estrutura das tabelas...</p>";

try {
    // 1. Verificar colunas na tabela 'users'
    $stmt = $pdo->query("DESCRIBE users");
    $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Lista de colunas desejadas e comandos para criá-las
    $checks = [
        'full_name' => "ALTER TABLE users ADD COLUMN full_name VARCHAR(255) AFTER password",
        'pix_key' => "ALTER TABLE users ADD COLUMN pix_key VARCHAR(255) AFTER full_name",
        'balance' => "ALTER TABLE users ADD COLUMN balance DECIMAL(10,2) DEFAULT 0.00 AFTER email"
    ];

    foreach ($checks as $col => $sql) {
        if (!in_array($col, $existingColumns)) {
            echo "🆕 Criando coluna '$col'... ";
            // Especial: Se estivermos criando pix_key, tentar migrar de liquid_address
            if ($col === 'pix_key' && in_array('liquid_address', $existingColumns)) {
                $pdo->exec("ALTER TABLE users CHANGE COLUMN liquid_address pix_key VARCHAR(255)");
                echo "✅ (Migrado de liquid_address)<br>";
            } else {
                $pdo->exec($sql);
                echo "✅ Sucesso!<br>";
            }
        } else {
            echo "ℹ️ Coluna '$col' já existe.<br>";
        }
    }

    // 2. Tabela 'withdrawals'
    echo "<h3>Verificando tabela 'withdrawals'...</h3>";
    $stmt = $pdo->query("DESCRIBE withdrawals");
    $existingWColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('pix_key', $existingWColumns)) {
        if (in_array('liquid_address', $existingWColumns)) {
            $pdo->exec("ALTER TABLE withdrawals CHANGE COLUMN liquid_address pix_key VARCHAR(255)");
            echo "✅ 'liquid_address' renomeado para 'pix_key' na tabela withdrawals.<br>";
        } else {
            $pdo->exec("ALTER TABLE withdrawals ADD COLUMN pix_key VARCHAR(255)");
            echo "✅ Coluna 'pix_key' adicionada em withdrawals.<br>";
        }
    } else {
        echo "ℹ️ Coluna 'pix_key' já existe em withdrawals.<br>";
    }

    if (!in_array('full_name', $existingWColumns)) {
        $pdo->exec("ALTER TABLE withdrawals ADD COLUMN full_name VARCHAR(255) AFTER user_id");
        echo "✅ Coluna 'full_name' adicionada em withdrawals.<br>";
    }

    echo "<br><h2 style='color: green;'>✅ Migração Concluída com Sucesso!</h2>";
    echo "<p><a href='sacar.php' style='padding: 10px 20px; background: cyan; color: black; text-decoration: none; border-radius: 5px;'>Voltar para Saques</a></p>";

} catch (Exception $e) {
    echo "<br><h2 style='color: red;'>❌ Erro Durante a Migração:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
