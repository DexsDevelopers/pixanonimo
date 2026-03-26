<?php
/**
 * Setup Telegram - Adiciona as constantes no config.php
 * Acesse uma vez: https://pixghost.site/setup_telegram.php
 * Depois delete este arquivo.
 */
$configPath = __DIR__ . '/config.php';

if (!file_exists($configPath)) {
    die('config.php não encontrado.');
}

$config = file_get_contents($configPath);

$token = '8649065126:AAFT2tc1uCioXaN6tDulC2mTSEL-qM6rF5Y';
$chatId = '7244348747';

$changes = [];

// Add or update TELEGRAM_BOT_TOKEN
if (strpos($config, 'TELEGRAM_BOT_TOKEN') !== false) {
    $config = preg_replace("/define\('TELEGRAM_BOT_TOKEN',\s*'[^']*'\);/", "define('TELEGRAM_BOT_TOKEN', '{$token}');", $config);
    $changes[] = 'TELEGRAM_BOT_TOKEN atualizado';
} else {
    $config = str_replace('?>', "define('TELEGRAM_BOT_TOKEN', '{$token}');\n?>", $config);
    if (strpos($config, '?>') === false) {
        $config .= "\ndefine('TELEGRAM_BOT_TOKEN', '{$token}');\n";
    }
    $changes[] = 'TELEGRAM_BOT_TOKEN adicionado';
}

// Add or update TELEGRAM_CHAT_ID
if (strpos($config, 'TELEGRAM_CHAT_ID') !== false) {
    $config = preg_replace("/define\('TELEGRAM_CHAT_ID',\s*'[^']*'\);/", "define('TELEGRAM_CHAT_ID', '{$chatId}');", $config);
    $changes[] = 'TELEGRAM_CHAT_ID atualizado';
} else {
    $config = str_replace('?>', "define('TELEGRAM_CHAT_ID', '{$chatId}');\n?>", $config);
    if (strpos($config, '?>') === false) {
        $config .= "\ndefine('TELEGRAM_CHAT_ID', '{$chatId}');\n";
    }
    $changes[] = 'TELEGRAM_CHAT_ID adicionado';
}

file_put_contents($configPath, $config);

// Test sending
require_once 'includes/TelegramService.php';
require_once $configPath;

$testResult = TelegramService::send("✅ <b>Ghost Pix Telegram conectado!</b>\n\nNotificações ativas para:\n💰 Vendas confirmadas\n👤 Novos cadastros\n🏦 Saques solicitados");

echo "<pre style='background:#111;color:#0f0;padding:2rem;font-family:monospace;'>";
echo "=== SETUP TELEGRAM ===\n\n";
foreach ($changes as $c) echo "✅ {$c}\n";
echo "\n";
echo $testResult ? "✅ Mensagem de teste enviada com sucesso!\n" : "❌ Falha ao enviar mensagem de teste.\n";
echo "\n⚠️ DELETE ESTE ARQUIVO DO SERVIDOR APÓS USAR.\n";
echo "</pre>";
