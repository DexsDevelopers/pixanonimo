-- Tabela de Saques
CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    liquid_address VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    tx_hash VARCHAR(255), -- Hash da transação na rede Liquid após pagamento
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
