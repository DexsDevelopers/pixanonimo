<?php
require_once 'includes/db.php';

if (!isAdmin()) {
    die("Apenas administradores podem executar este script.");
}

echo "<h1>🔧 Corretor de Banco de Dados</h1>";

try {
    // 1. Verificar Colunas na tabela 'transactions'
    $columns = $pdo->query("DESCRIBE transactions")->fetchAll(PDO::FETCH_COLUMN);
    
    $toAdd = [
        'pix_code' => "ALTER TABLE transactions ADD COLUMN pix_code TEXT AFTER status",
        'qr_image' => "ALTER TABLE transactions ADD COLUMN qr_image TEXT AFTER pix_code"
    ];

    foreach ($toAdd as $col => $sql) {
        if (!in_array($col, $columns)) {
            $pdo->exec($sql);
            echo "✅ Coluna '$col' adicionada com sucesso.<br>";
        } else {
            echo "ℹ️ Coluna '$col' já existe.<br>";
        }
    }

    // 2. Garantir que a tabela 'withdrawals' existe e está correta
    $pdo->exec("CREATE TABLE IF NOT EXISTS withdrawals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        pix_key VARCHAR(255) NOT NULL,
        status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
        tx_hash VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ Tabela 'withdrawals' verificada/criada.<br>";

    echo "<br><b>Tudo pronto! O banco de dados está atualizado.</b>";
    echo "<br><a href='index.php'>Voltar ao Dashboard</a>";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
