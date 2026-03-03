<?php
require_once 'includes/db.php';

try {
    // Criar tabela se não existir
    $pdo->exec("CREATE TABLE IF NOT EXISTS pixgo_apis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        api_key VARCHAR(255) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Tabela 'pixgo_apis' criada ou já existente.<br>";

    // Verificar se já existe a chave atual para migrar
    $stmt = $pdo->query("SELECT COUNT(*) FROM pixgo_apis");
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO pixgo_apis (name, api_key, status) VALUES (?, ?, 'active')");
        $stmt->execute(['Chave Principal', PIXGO_API_KEY]);
        echo "Chave antiga migrada com sucesso.<br>";
    }

    echo "Migração concluída!";
} catch (PDOException $e) {
    die("Erro na migração: " . $e->getMessage());
}
?>
