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
 * Retorna uma chave de API PixGo ativa aleatoriamente do banco de dados.
 */
function getActivePixGoKey() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT api_key FROM pixgo_apis WHERE status = 'active' ORDER BY RAND() LIMIT 1");
        $key = $stmt->fetchColumn();
        
        if ($key) {
            return $key;
        }
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

