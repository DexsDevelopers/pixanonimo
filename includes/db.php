<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
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
?>
