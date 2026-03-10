<?php
require_once 'includes/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS checkouts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            primary_color VARCHAR(20) DEFAULT '#00ff88',
            secondary_color VARCHAR(20) DEFAULT '#111111',
            custom_html_head TEXT,
            custom_html_body TEXT,
            checkout_banner_url VARCHAR(255),
            active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS checkout_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            checkout_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (checkout_id) REFERENCES checkouts(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Tabelas 'checkouts' e 'checkout_items' criadas com sucesso!\n";
} catch (PDOException $e) {
    die("Erro ao criar tabelas: " . $e->getMessage() . "\n");
}
