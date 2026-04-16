<?php
/**
 * Simula uma chamada webhook para ver o erro real.
 * Acesse: https://pixghost.site/test_bot.php
 * DELETE APÓS USAR!
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background:#111;color:#0f0;padding:2rem;font-family:monospace;font-size:14px;'>";
echo "=== SIMULAÇÃO WEBHOOK BOT ===\n\n";

// Simula um /start de chat_id fictício
$fakeUpdate = json_encode([
    'update_id' => 999999,
    'message' => [
        'message_id' => 1,
        'from' => ['id' => 12345, 'is_bot' => false, 'first_name' => 'Teste'],
        'chat' => ['id' => 12345, 'first_name' => 'Teste', 'type' => 'private'],
        'date' => time(),
        'text' => '/start',
    ],
]);

// Faz uma requisição HTTP real ao próprio endpoint
$secret = '';
try {
    require_once __DIR__ . '/config.php';
    $secret = defined('TELEGRAM_USER_BOT_SECRET') ? TELEGRAM_USER_BOT_SECRET : '';
} catch (Throwable $e) {}

$url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/telegram_user_bot.php';

echo "URL: {$url}\n";
echo "Secret: " . ($secret ? 'sim' : 'não') . "\n\n";

$headers = ['Content-Type: application/json'];
if ($secret) {
    $headers[] = "X-Telegram-Bot-Api-Secret-Token: {$secret}";
}

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $fakeUpdate,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HEADER         => true,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$error = curl_error($ch);
curl_close($ch);

$responseHeaders = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);

echo "HTTP Code: {$httpCode}\n";
if ($error) echo "cURL Error: {$error}\n";
echo "\n--- Response Headers ---\n{$responseHeaders}\n";
echo "--- Response Body ---\n{$responseBody}\n";

// Também tenta pegar o erro direto do PHP error log
echo "\n--- Últimas linhas do error_log (se existir) ---\n";
$errorLogPaths = [
    __DIR__ . '/bot_errors.log',
    __DIR__ . '/error_log',
    __DIR__ . '/php_errors.log',
    '/tmp/php_errors.log',
];
foreach ($errorLogPaths as $logPath) {
    if (file_exists($logPath)) {
        echo "Arquivo: {$logPath}\n";
        $lines = file($logPath);
        $last = array_slice($lines, -15);
        foreach ($last as $l) echo $l;
        break;
    }
}

echo "\n=== FIM ===\n";
echo "</pre>";
