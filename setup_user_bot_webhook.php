<?php
/**
 * setup_user_bot_webhook.php — Registrar webhook do bot de usuários
 *
 * Acesse uma vez pelo navegador: https://pixghost.site/setup_user_bot_webhook.php
 * Depois pode deletar este arquivo.
 */

require_once 'config.php';

$botToken = defined('TELEGRAM_USER_BOT_TOKEN') ? TELEGRAM_USER_BOT_TOKEN : '';
$secret   = defined('TELEGRAM_USER_BOT_SECRET') ? TELEGRAM_USER_BOT_SECRET : '';

if (!$botToken) {
    die('<h2>❌ TELEGRAM_USER_BOT_TOKEN não definido no config.php</h2>
    <p>Adicione ao seu config.php:</p>
    <pre>
define(\'TELEGRAM_USER_BOT_TOKEN\', \'SEU_TOKEN_AQUI\');
define(\'TELEGRAM_USER_BOT_SECRET\', \'um_secret_aleatorio\');
define(\'TELEGRAM_USER_BOT_USERNAME\', \'NomeDoBot\');
    </pre>');
}

$webhookUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/telegram_user_bot.php';

$params = [
    'url' => $webhookUrl,
    'allowed_updates' => json_encode(['message', 'callback_query']),
    'drop_pending_updates' => true,
];

if ($secret) {
    $params['secret_token'] = $secret;
}

$ch = curl_init("https://api.telegram.org/bot{$botToken}/setWebhook");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $params,
    CURLOPT_TIMEOUT        => 10,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

echo "<h2>Setup Webhook - Bot de Usuários</h2>";
echo "<p><b>URL:</b> {$webhookUrl}</p>";
echo "<p><b>HTTP:</b> {$httpCode}</p>";
echo "<p><b>Resposta:</b></p><pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";

if (!empty($data['ok'])) {
    echo "<p style='color:green; font-size:1.5em'>✅ Webhook configurado com sucesso!</p>";
    echo "<p>Agora adicione ao config.php (se ainda não adicionou):</p>";
    echo "<pre>define('TELEGRAM_USER_BOT_USERNAME', 'NomeDoSeuBot');</pre>";
} else {
    echo "<p style='color:red; font-size:1.5em'>❌ Erro ao configurar webhook</p>";
}
