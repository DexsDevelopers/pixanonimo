<?php
require_once 'includes/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS merchant_domains (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            domain VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_domain (user_id, domain)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    echo "Tabela 'merchant_domains' criada com sucesso!\n";
} catch (PDOException $e) {
    die("Erro ao criar tabela 'merchant_domains': " . $e->getMessage() . "\n");
}
