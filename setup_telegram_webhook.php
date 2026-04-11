<?php
/**
 * setup_telegram_webhook.php
 * Acesse UMA vez para registrar o webhook do bot.
 * Depois delete ou proteja este arquivo.
 *
 * URL: https://pixghost.site/setup_telegram_webhook.php?admin=1
 */
require_once __DIR__ . '/includes/db.php';

if (($_GET['admin'] ?? '') !== '1') {
    http_response_code(403);
    echo 'Acesso negado. Use ?admin=1';
    exit;
}

if (!defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN)) {
    echo '❌ TELEGRAM_BOT_TOKEN não definido em config.php';
    exit;
}

$token  = TELEGRAM_BOT_TOKEN;
$secret = defined('TELEGRAM_WEBHOOK_SECRET') ? TELEGRAM_WEBHOOK_SECRET : '';
$url    = 'https://pixghost.site/telegram_bot.php' . ($secret ? "?secret={$secret}" : '');

$ch = curl_init("https://api.telegram.org/bot{$token}/setWebhook");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'url'             => $url,
        'allowed_updates' => ['message', 'callback_query'],
        'drop_pending_updates' => true,
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT    => 15,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($res, true);

echo '<pre>';
echo "HTTP {$code}\n";
echo "Webhook URL: {$url}\n\n";
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo '</pre>';
echo $data['ok'] ? '<h2>✅ Webhook registrado com sucesso!</h2>' : '<h2>❌ Erro ao registrar. Veja o JSON acima.</h2>';
