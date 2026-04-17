<?php
if (session_status() === PHP_SESSION_NONE) {
    // Configuração de Sessão Persistente (30 dias)
    $sessionLifetime = 30 * 24 * 60 * 60;
    ini_set('session.gc_maxlifetime', $sessionLifetime);
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Polyfill para getallheaders() caso não exista (comum em Nginx/FPM)
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET time_zone = '-03:00'");

    // Auto-Migração: Adicionar coluna is_demo se não existir
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_demo TINYINT(1) DEFAULT 0");
    } catch (PDOException $e) {}

    // Auto-Migração: Adicionar ip nas transações para anti-bot
    try {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN customer_ip VARCHAR(45) AFTER user_id");
    } catch (PDOException $e) {}

    // Auto-Migração: Adicionar customer_name nas transações
    try {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN customer_name VARCHAR(255) AFTER customer_ip");
    } catch (PDOException $e) {}

    // Auto-Migração: Adicionar external_id nas transações
    try {
        $pdo->exec("ALTER TABLE transactions ADD COLUMN external_id VARCHAR(100) AFTER customer_name");
    } catch (PDOException $e) {}

    // Auto-Migração: Criar tabela de cupons de desconto
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            user_id     INT NOT NULL,
            code        VARCHAR(50) NOT NULL,
            type        ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
            value       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            scope       ENUM('store','product') NOT NULL DEFAULT 'store',
            product_id  INT NULL,
            min_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            max_uses    INT NULL,
            uses_count  INT NOT NULL DEFAULT 0,
            expires_at  DATETIME NULL,
            active      TINYINT(1) NOT NULL DEFAULT 1,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_coupon_code (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {}

    // Auto-Migração: Adicionar coupon_id e discount_amount nas orders
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN coupon_id INT NULL AFTER status");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER coupon_id");
    } catch (PDOException $e) {}

    // Auto-Migração: Tabela para rastrear visitas diárias ao site
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS daily_stats (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            stat_date   DATE NOT NULL,
            stat_key    VARCHAR(50) NOT NULL,
            stat_value  INT NOT NULL DEFAULT 0,
            UNIQUE KEY uq_daily_stat (stat_date, stat_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {}

    // Auto-Migração: Tabela de auditoria de saldo
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS balance_log (
            id              BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id         INT NOT NULL,
            amount          DECIMAL(12,2) NOT NULL COMMENT 'Valor da operação (+ ou -)',
            balance_before  DECIMAL(12,2) NOT NULL,
            balance_after   DECIMAL(12,2) NOT NULL,
            origin          VARCHAR(50) NOT NULL COMMENT 'sale, withdraw_debit, withdraw_refund, affiliate, admin_profit, admin_adjust, bot_withdraw',
            reference_id    VARCHAR(100) NULL COMMENT 'ID da transação/saque/etc',
            description     VARCHAR(255) NULL,
            ip_address      VARCHAR(45) NULL,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_bl_user (user_id),
            INDEX idx_bl_origin (origin),
            INDEX idx_bl_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) {}

    // Auto-Migração: Colunas para vincular conta Telegram do usuário
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN telegram_chat_id VARCHAR(50) NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN telegram_link_token VARCHAR(64) NULL");
    } catch (PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN telegram_link_expires DATETIME NULL");
    } catch (PDOException $e) {}

} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Lógica de Auto-Login (Remember Me)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['is_admin'] = $user['is_admin'];
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function redirect($path) {
    header("Location: $path");
    exit;
}

// CSRF Protection Functions
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Erro de segurança: Token CSRF inválido. Recarregue a página.']);
        exit;
    }
    return true;
}

// Structured Logging Function
function write_log($level, $message, $data = []) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0777, true);
    }
    
    $file = $logDir . '/' . date('Y-m-d') . '.log';
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => strtoupper($level), // INFO, ERROR, WARNING, SECURITY
        'message' => $message,
        'user_id' => $_SESSION['user_id'] ?? 'GUEST',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        'data' => $data
    ];
    
    file_put_contents($file, json_encode($logEntry) . PHP_EOL, FILE_APPEND);
}

function getUser($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Ajusta o saldo de um usuário de forma ATÔMICA com auditoria completa.
 *
 * @param int    $userId      ID do usuário
 * @param float  $amount      Valor da operação (positivo para crédito, negativo para débito)
 * @param string $origin      Origem: sale, withdraw_debit, withdraw_refund, affiliate, admin_profit, admin_adjust, bot_withdraw
 * @param string $referenceId ID de referência (transaction_id, withdrawal_id, etc.)
 * @param string $description Descrição legível da operação
 * @param bool   $allowNegative Permitir saldo negativo? (default: false)
 * @return array ['success' => bool, 'balance_before' => float, 'balance_after' => float, 'error' => string|null]
 */
function adjustBalance(int $userId, float $amount, string $origin, string $referenceId = '', string $description = '', bool $allowNegative = false): array {
    global $pdo;

    if ($amount == 0) {
        return ['success' => false, 'error' => 'Valor zero não permitido'];
    }

    $ownTransaction = !$pdo->inTransaction();
    if ($ownTransaction) $pdo->beginTransaction();

    try {
        // SELECT FOR UPDATE = row-level lock, previne race conditions
        $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            if ($ownTransaction) $pdo->rollBack();
            return ['success' => false, 'error' => 'Usuário não encontrado'];
        }

        $balanceBefore = round((float)$row['balance'], 2);
        $balanceAfter  = round($balanceBefore + $amount, 2);

        // Bloqueia saldo negativo (exceto se explicitamente permitido)
        if (!$allowNegative && $balanceAfter < 0) {
            if ($ownTransaction) $pdo->rollBack();
            return [
                'success' => false,
                'balance_before' => $balanceBefore,
                'error' => "Saldo insuficiente: {$balanceBefore} + {$amount} = {$balanceAfter}"
            ];
        }

        // Atualizar saldo
        $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?")
            ->execute([$balanceAfter, $userId]);

        // Registrar no log de auditoria
        $pdo->prepare("INSERT INTO balance_log (user_id, amount, balance_before, balance_after, origin, reference_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                $userId,
                round($amount, 2),
                $balanceBefore,
                $balanceAfter,
                $origin,
                $referenceId ?: null,
                $description ?: null,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);

        if ($ownTransaction) $pdo->commit();

        write_log('INFO', 'Balance Adjusted', [
            'user_id' => $userId,
            'amount' => $amount,
            'before' => $balanceBefore,
            'after' => $balanceAfter,
            'origin' => $origin,
            'ref' => $referenceId,
        ]);

        return [
            'success' => true,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'error' => null,
        ];
    } catch (Throwable $e) {
        if ($ownTransaction && $pdo->inTransaction()) $pdo->rollBack();
        write_log('ERROR', 'adjustBalance FAILED', [
            'user_id' => $userId, 'amount' => $amount, 'origin' => $origin,
            'error' => $e->getMessage(),
        ]);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Retorna uma chave de API PixGo ativa aleatoriamente do banco de dados.
 */
function getActivePixGoKey($adminOnly = false) {
    global $pdo;
    try {
        if ($adminOnly) {
            // Prefer admin-only APIs; fall back to any active API if none exist
            $stmt = $pdo->query("SELECT api_key FROM pixgo_apis WHERE status = 'active' AND is_admin_only = 1 ORDER BY RAND() LIMIT 1");
            $key = $stmt->fetchColumn();
            if ($key) return $key;
        }
        // For users: only non-admin-only APIs
        $stmt = $pdo->query("SELECT api_key FROM pixgo_apis WHERE status = 'active' AND is_admin_only = 0 ORDER BY RAND() LIMIT 1");
        $key = $stmt->fetchColumn();
        if ($key) return $key;

        // Final fallback: any active API
        $stmt = $pdo->query("SELECT api_key FROM pixgo_apis WHERE status = 'active' ORDER BY RAND() LIMIT 1");
        $key = $stmt->fetchColumn();
        if ($key) return $key;
    } catch (PDOException $e) {
        write_log('error', 'Erro ao buscar chaves de API: ' . $e->getMessage());
    }
    
    return defined('PIXGO_API_KEY') ? PIXGO_API_KEY : '';
}

/**
 * Verifica se um IP atingiu o limite de geração de PIX (3 por minuto)
 */
function checkRateLimit($ip) {
    global $pdo;
    if (empty($ip) || $ip === '0.0.0.0') return true;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE customer_ip = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
    $stmt->execute([$ip]);
    $count = (int)$stmt->fetchColumn();

    if ($count >= 3) {
        write_log('security', 'Rate limit atingido pelo IP: ' . $ip);
        return false;
    }
    return true;
}

/**
 * Salva transação de forma resiliente e performática.
 */
function saveTransaction($userId, $amount, $netAmount, $pixId, $pixCode, $qrImage, $callbackUrl = null, $customerName = null, $externalId = null, $type = 'pix') {
    global $pdo;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Log da tentativa
    write_log('info', "Gerando PIX R$ $amount para User $userId (IP: $ip)");

    // Tenta o insert completo primeiro (padrão atual com IP, Name e ExternalID)
    try {
        $sql = "INSERT INTO transactions (user_id, customer_ip, customer_name, external_id, amount_brl, amount_net_brl, pix_id, status, pix_code, qr_image, callback_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$userId, $ip, $customerName, $externalId, $amount, $netAmount, $pixId, 'pending', $pixCode, $qrImage, $callbackUrl]);
    } catch (PDOException $e) {
        // Fallback sem o campo de external_id se der erro
        try {
            $sql = "INSERT INTO transactions (user_id, customer_ip, customer_name, amount_brl, amount_net_brl, pix_id, status, pix_code, qr_image, callback_url) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$userId, $ip, $customerName, $amount, $netAmount, $pixId, $pixCode, $qrImage, $callbackUrl]);
        } catch (PDOException $e_orig) {
            // Se falhar (provavelmente coluna callback_url ausente), tenta o fallback v2
            try {
                $sql = "INSERT INTO transactions (user_id, amount_brl, amount_net_brl, pix_id, status, pix_code, qr_image) 
                        VALUES (?, ?, ?, ?, 'pending', ?, ?)";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([$userId, $amount, $netAmount, $pixId, $pixCode, $qrImage]);
            } catch (PDOException $e2) {
                // Se falhar de novo (provavelmente colunas pix_code/qr_image ausentes no legado extremo)
                try {
                    $sql = "INSERT INTO transactions (user_id, amount_brl, amount_net_brl, pix_id, status) 
                            VALUES (?, ?, ?, ?, 'pending')";
                    $stmt = $pdo->prepare($sql);
                    return $stmt->execute([$userId, $amount, $netAmount, $pixId]);
                } catch (PDOException $e3) {
                    write_log('error', 'Falha crítica ao salvar transação: ' . $e3->getMessage());
                    return false;
                }
            }
        }
    }
}
class Response {
    public static function json($data, $status = 200) {
        ob_clean();
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function error($message, $status = 400, $code = null) {
        self::json([
            'success' => false,
            'error' => $message,
            'code' => $code
        ], $status);
    }

    public static function success($data = []) {
        self::json(array_merge(['success' => true], $data));
    }
}

/**
 * Gera uma URL completa (absoluta) de forma robusta,
 * detectando protocolo e subpastas automaticamente.
 */
function getFullUrl($path = '') {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // dirname($_SERVER['PHP_SELF']) pode retornar '\' em Windows ou '/' em Linux no root.
    // Uniformizamos para '/' e removemos barras extras.
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
    $scriptDir = rtrim($scriptDir, '/');
    
    // Se o path já começa com barra, removemos para evitar "//"
    $path = ltrim($path, '/');
    
    return $protocol . '://' . $host . $scriptDir . '/' . $path;
}
?>

