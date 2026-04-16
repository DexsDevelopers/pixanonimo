<?php
/**
 * Diagnóstico do Bot de Usuários
 * Acesse: https://pixghost.site/test_bot.php
 * DELETE APÓS USAR!
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background:#111;color:#0f0;padding:2rem;font-family:monospace;font-size:14px;'>";
echo "=== DIAGNÓSTICO BOT TELEGRAM ===\n\n";

// 1. Config
echo "1. Config.php...\n";
try {
    require_once __DIR__ . '/config.php';
    echo "   ✅ config.php carregado\n";
} catch (Throwable $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n";
}

// 2. Constantes
echo "\n2. Constantes Telegram...\n";
$token = defined('TELEGRAM_USER_BOT_TOKEN') ? TELEGRAM_USER_BOT_TOKEN : '';
$secret = defined('TELEGRAM_USER_BOT_SECRET') ? TELEGRAM_USER_BOT_SECRET : '';
$username = defined('TELEGRAM_USER_BOT_USERNAME') ? TELEGRAM_USER_BOT_USERNAME : '';
echo "   TOKEN: " . ($token ? substr($token, 0, 10) . '...' : '❌ VAZIO') . "\n";
echo "   SECRET: " . ($secret ? '✅ definido' : '⚠️ vazio') . "\n";
echo "   USERNAME: " . ($username ?: '⚠️ vazio') . "\n";

// 3. DB
echo "\n3. Banco de dados...\n";
try {
    require_once __DIR__ . '/includes/db.php';
    echo "   ✅ Conexão OK\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    echo "   ✅ Users: " . $stmt->fetchColumn() . " registros\n";
    
    // Check telegram columns
    $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'telegram_%'")->fetchAll();
    echo "   Colunas telegram: " . count($cols) . " encontradas\n";
    foreach ($cols as $c) echo "     - {$c['Field']} ({$c['Type']})\n";
} catch (Throwable $e) {
    echo "   ❌ ERRO DB: " . $e->getMessage() . "\n";
}

// 4. Syntax check
echo "\n4. Syntax check telegram_user_bot.php...\n";
$output = [];
$code = 0;
exec('php -l ' . escapeshellarg(__DIR__ . '/telegram_user_bot.php') . ' 2>&1', $output, $code);
foreach ($output as $line) echo "   {$line}\n";
echo "   " . ($code === 0 ? '✅ Sem erros de sintaxe' : '❌ ERRO DE SINTAXE!') . "\n";

// 5. Webhook info
echo "\n5. Webhook info...\n";
if ($token) {
    $ch = curl_init("https://api.telegram.org/bot{$token}/getWebhookInfo");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $res = json_decode(curl_exec($ch) ?: '{}', true);
    curl_close($ch);
    if (!empty($res['ok'])) {
        $info = $res['result'];
        echo "   URL: " . ($info['url'] ?: '❌ NÃO DEFINIDO') . "\n";
        echo "   Pending updates: " . ($info['pending_update_count'] ?? 0) . "\n";
        echo "   Last error: " . ($info['last_error_message'] ?? 'nenhum') . "\n";
        echo "   Last error date: " . (!empty($info['last_error_date']) ? date('Y-m-d H:i:s', $info['last_error_date']) : 'n/a') . "\n";
        echo "   Has secret: " . (!empty($info['has_custom_certificate']) ? 'sim' : ($info['url'] ? 'verificar' : 'n/a')) . "\n";
    } else {
        echo "   ❌ Falha ao obter info: " . json_encode($res) . "\n";
    }
} else {
    echo "   ⚠️ Token vazio, pulando\n";
}

// 6. Try include bot file
echo "\n6. Tentando incluir telegram_user_bot.php (sem executar)...\n";
try {
    $botCode = file_get_contents(__DIR__ . '/telegram_user_bot.php');
    echo "   ✅ Arquivo existe (" . strlen($botCode) . " bytes)\n";
    
    // Check for common issues
    if (strpos($botCode, 'match(') !== false) {
        echo "   ⚠️ Usa 'match' expression (requer PHP 8.0+)\n";
        echo "   PHP version: " . PHP_VERSION . "\n";
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            echo "   ❌ PHP < 8.0! 'match' não é suportado!\n";
        } else {
            echo "   ✅ PHP >= 8.0, match suportado\n";
        }
    }
    
    // Check for nullable return type
    if (strpos($botCode, '): ?int') !== false || strpos($botCode, '): ?array') !== false) {
        echo "   ℹ️ Usa nullable types (requer PHP 7.1+)\n";
    }
    
} catch (Throwable $e) {
    echo "   ❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
echo "</pre>";
