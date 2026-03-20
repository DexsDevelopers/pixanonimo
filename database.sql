-- Script de Criação do Banco de Dados - PixAnônimo

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    pix_key VARCHAR(255),
    status ENUM('pending', 'approved', 'blocked') DEFAULT 'pending',
    commission_rate DECIMAL(5,2) DEFAULT 0.00, -- Porcentagem de comissão para este usuário
    balance DECIMAL(15,2) DEFAULT 0.00, -- Saldo virtual acumulado (já limpo de taxas)
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount_brl DECIMAL(10,2) NOT NULL,
    amount_net_brl DECIMAL(10,2) NOT NULL, -- Valor após a comissão da plataforma
    depix_amount DECIMAL(20,8) DEFAULT 0.00,
    pix_id VARCHAR(100), -- ID retornado pelo PixGo
    status ENUM('pending', 'paid', 'expired', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Configurações Iniciais
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('global_commission', '2.0');
INSERT IGNORE INTO settings (`key`, `value`) VALUES ('pixgo_api_key', 'YOUR_KEY_HERE');

-- Criar Admin Inicial (Senha: admin123 - Use password_hash para produção)
-- Sugestão: Alterar imediatamente após o primeiro login
INSERT IGNORE INTO users (email, password, is_admin, status) 
VALUES ('admin@pixanonimo.com', '$2y$10$8W9w7zQrk7y2.N8K1Yd6q.H2e3M/Tz9oFjN3.5v9h7f4G7k7q7G7u', TRUE, 'approved');

-- Tabela de Saques
CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    pix_key VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    tx_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Checkouts Customizados
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

-- Itens do Checkout
CREATE TABLE IF NOT EXISTS checkout_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    checkout_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (checkout_id) REFERENCES checkouts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Domínios Permitidos para Checkout Transparente
CREATE TABLE IF NOT EXISTS merchant_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    domain VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_domain (user_id, domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
