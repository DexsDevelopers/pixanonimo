<?php
/**
 * Migração: Tabelas de Produtos, Pedidos e Avaliações
 * Acesse uma vez: https://pixghost.site/migrate_products.php
 */
require_once 'includes/db.php';

$results = [];

$tables = [
    'products' => "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        image_url VARCHAR(500),
        category VARCHAR(100) DEFAULT 'Digital',
        type ENUM('digital','physical','service') DEFAULT 'digital',
        delivery_info TEXT,
        vitrine TINYINT(1) DEFAULT 0,
        status ENUM('active','inactive','pending') DEFAULT 'active',
        stock INT DEFAULT -1,
        orders_count INT DEFAULT 0,
        avg_rating DECIMAL(3,2) DEFAULT 0.00,
        review_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'product_reviews' => "CREATE TABLE IF NOT EXISTS product_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT,
        buyer_name VARCHAR(255),
        rating TINYINT NOT NULL DEFAULT 5,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'orders' => "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        seller_id INT NOT NULL,
        buyer_name VARCHAR(255),
        buyer_document VARCHAR(20),
        reseller_id INT DEFAULT NULL,
        quantity INT DEFAULT 1,
        amount DECIMAL(10,2) NOT NULL,
        transaction_id INT DEFAULT NULL,
        status ENUM('pending','paid','delivered','cancelled') DEFAULT 'pending',
        delivery_data TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id),
        FOREIGN KEY (seller_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'store_settings' => "CREATE TABLE IF NOT EXISTS store_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL UNIQUE,
        store_name VARCHAR(255),
        store_description TEXT,
        store_banner VARCHAR(500),
        slug VARCHAR(100) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['table' => $name, 'status' => 'ok', 'msg' => 'Criada/verificada com sucesso'];
    } catch (PDOException $e) {
        $results[] = ['table' => $name, 'status' => 'error', 'msg' => $e->getMessage()];
    }
}

echo "<pre style='background:#0d0d0d;color:#4ade80;padding:2rem;font-family:monospace;min-height:100vh;margin:0;'>";
echo "=== MIGRATE PRODUCTS ===\n\n";
foreach ($results as $r) {
    $icon = $r['status'] === 'ok' ? '✅' : '❌';
    echo "{$icon} [{$r['table']}] {$r['msg']}\n";
}
$allOk = !array_filter($results, fn($r) => $r['status'] === 'error');
echo "\n" . ($allOk ? "✅ Migração concluída com sucesso!" : "⚠️ Algumas tabelas falharam.") . "\n";
echo "\n⚠️ Você pode excluir este arquivo após rodar.\n";
echo "</pre>";
