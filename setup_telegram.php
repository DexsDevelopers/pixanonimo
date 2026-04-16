<?php
/**
 * Setup Telegram — Configurar bots Admin + Usuário
 * Acesse: https://seusite.com/setup_telegram.php
 * ⚠️ DELETE ESTE ARQUIVO APÓS USAR!
 */
$configPath = __DIR__ . '/config.php';
$results = [];

// ── PROCESSAR FORMULÁRIO ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!file_exists($configPath)) {
        die('config.php não encontrado. Copie config.example.php para config.php primeiro.');
    }

    $config = file_get_contents($configPath);

    $fields = [
        'TELEGRAM_BOT_TOKEN'        => trim($_POST['admin_token'] ?? ''),
        'TELEGRAM_CHAT_ID'          => trim($_POST['admin_chat_id'] ?? ''),
        'TELEGRAM_USER_BOT_TOKEN'   => trim($_POST['user_token'] ?? ''),
        'TELEGRAM_USER_BOT_SECRET'  => trim($_POST['user_secret'] ?? ''),
        'TELEGRAM_USER_BOT_USERNAME'=> trim($_POST['user_username'] ?? ''),
    ];

    foreach ($fields as $key => $value) {
        if ($value === '') continue;
        $escaped = addslashes($value);

        if (preg_match("/define\s*\(\s*'{$key}'/", $config)) {
            $config = preg_replace(
                "/define\s*\(\s*'{$key}'\s*,\s*'[^']*'\s*\)\s*;/",
                "define('{$key}', '{$escaped}');",
                $config
            );
            $results[] = ['ok', "{$key} atualizado"];
        } else {
            $line = "define('{$key}', '{$escaped}');";
            if (strpos($config, '?>') !== false) {
                $config = str_replace('?>', "{$line}\n?>", $config);
            } else {
                $config .= "\n{$line}\n";
            }
            $results[] = ['ok', "{$key} adicionado"];
        }
    }

    file_put_contents($configPath, $config);

    // ── Testar Bot Admin ────────────────────────────────────────────
    if (!empty($fields['TELEGRAM_BOT_TOKEN']) && !empty($fields['TELEGRAM_CHAT_ID'])) {
        $testMsg = "✅ <b>Ghost Pix — Bot Admin conectado!</b>\n\n🔔 Notificações ativas:\n• 💰 Vendas confirmadas\n• 👤 Novos cadastros\n• 🏦 Saques solicitados";
        $ch = curl_init("https://api.telegram.org/bot{$fields['TELEGRAM_BOT_TOKEN']}/sendMessage");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => json_encode(['chat_id' => $fields['TELEGRAM_CHAT_ID'], 'text' => $testMsg, 'parse_mode' => 'HTML']),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);
        $res = json_decode(curl_exec($ch) ?: '{}', true);
        curl_close($ch);
        $results[] = !empty($res['ok']) ? ['ok', 'Bot Admin: mensagem de teste enviada ✅'] : ['err', 'Bot Admin: falha ao enviar teste — ' . ($res['description'] ?? 'erro')];
    }

    // ── Registrar Webhook + Testar Bot Usuário ──────────────────────
    if (!empty($fields['TELEGRAM_USER_BOT_TOKEN'])) {
        $webhookUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/telegram_user_bot.php';
        $whParams = [
            'url' => $webhookUrl,
            'allowed_updates' => json_encode(['message', 'callback_query']),
            'drop_pending_updates' => true,
        ];
        if (!empty($fields['TELEGRAM_USER_BOT_SECRET'])) {
            $whParams['secret_token'] = $fields['TELEGRAM_USER_BOT_SECRET'];
        }

        $ch = curl_init("https://api.telegram.org/bot{$fields['TELEGRAM_USER_BOT_TOKEN']}/setWebhook");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $whParams, CURLOPT_TIMEOUT => 10]);
        $whRes = json_decode(curl_exec($ch) ?: '{}', true);
        curl_close($ch);

        if (!empty($whRes['ok'])) {
            $results[] = ['ok', "Webhook registrado: {$webhookUrl}"];
        } else {
            $results[] = ['err', 'Webhook falhou: ' . ($whRes['description'] ?? 'erro')];
        }

        // Get bot info
        $ch = curl_init("https://api.telegram.org/bot{$fields['TELEGRAM_USER_BOT_TOKEN']}/getMe");
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
        $meRes = json_decode(curl_exec($ch) ?: '{}', true);
        curl_close($ch);
        if (!empty($meRes['ok'])) {
            $botName = $meRes['result']['first_name'] ?? '';
            $botUser = $meRes['result']['username'] ?? '';
            $results[] = ['ok', "Bot Usuário: @{$botUser} ({$botName})"];
        }
    }
}

// ── Ler valores atuais do config ────────────────────────────────────────
$current = [];
if (file_exists($configPath)) {
    $raw = file_get_contents($configPath);
    foreach (['TELEGRAM_BOT_TOKEN','TELEGRAM_CHAT_ID','TELEGRAM_USER_BOT_TOKEN','TELEGRAM_USER_BOT_SECRET','TELEGRAM_USER_BOT_USERNAME'] as $k) {
        if (preg_match("/define\s*\(\s*'{$k}'\s*,\s*'([^']*)'\s*\)/", $raw, $m)) {
            $current[$k] = $m[1];
        }
    }
}

$mask = function($v) { return $v ? substr($v, 0, 8) . '...' . substr($v, -4) : ''; };
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Telegram — Ghost Pix</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0a0a0f; color: #e0e0e0; min-height: 100vh; display: flex; justify-content: center; padding: 2rem 1rem; }
        .container { max-width: 640px; width: 100%; }
        h1 { font-size: 1.8rem; text-align: center; margin-bottom: .5rem; }
        h1 span { color: #a855f7; }
        .subtitle { text-align: center; color: #888; margin-bottom: 2rem; font-size: .9rem; }
        .card { background: #13131a; border: 1px solid #2a2a35; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .card h2 { font-size: 1.1rem; margin-bottom: 1rem; display: flex; align-items: center; gap: .5rem; }
        .card h2 .badge { font-size: .7rem; padding: 2px 8px; border-radius: 99px; font-weight: 600; }
        .badge-admin { background: #dc262620; color: #f87171; border: 1px solid #dc262640; }
        .badge-user { background: #a855f720; color: #c084fc; border: 1px solid #a855f740; }
        label { display: block; font-size: .85rem; color: #aaa; margin-bottom: .3rem; margin-top: .8rem; }
        input[type=text] { width: 100%; padding: .6rem .8rem; border-radius: 8px; border: 1px solid #333; background: #0a0a0f; color: #fff; font-family: monospace; font-size: .9rem; }
        input[type=text]:focus { outline: none; border-color: #a855f7; box-shadow: 0 0 0 2px #a855f720; }
        input::placeholder { color: #555; }
        .hint { font-size: .75rem; color: #666; margin-top: .2rem; }
        .current { font-size: .75rem; color: #a855f7; }
        .btn { display: block; width: 100%; padding: .8rem; border: none; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor: pointer; margin-top: 1.5rem; transition: all .2s; }
        .btn-primary { background: linear-gradient(135deg, #a855f7, #7c3aed); color: #fff; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 20px #a855f740; }
        .results { margin-top: 1rem; }
        .result { padding: .5rem .8rem; border-radius: 8px; margin-bottom: .5rem; font-size: .85rem; display: flex; align-items: center; gap: .5rem; }
        .result-ok { background: #16a34a15; border: 1px solid #16a34a30; color: #4ade80; }
        .result-err { background: #dc262615; border: 1px solid #dc262630; color: #f87171; }
        .warning { text-align: center; background: #f59e0b15; border: 1px solid #f59e0b30; color: #fbbf24; padding: .8rem; border-radius: 10px; font-size: .85rem; margin-top: 1rem; }
    </style>
</head>
<body>
<div class="container">
    <h1>👻 <span>Ghost Pix</span> — Setup Telegram</h1>
    <p class="subtitle">Configure os bots do Telegram para notificações e interação com usuários</p>

    <?php if ($results): ?>
    <div class="results">
        <?php foreach ($results as [$type, $msg]): ?>
        <div class="result result-<?= $type ?>"><?= $type === 'ok' ? '✅' : '❌' ?> <?= htmlspecialchars($msg) ?></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <!-- Bot Admin -->
        <div class="card">
            <h2>🤖 Bot Admin <span class="badge badge-admin">ADMIN</span></h2>
            <p style="font-size:.8rem;color:#888;">Notifica o administrador sobre vendas, cadastros e saques.</p>

            <label>Bot Token</label>
            <input type="text" name="admin_token" placeholder="123456789:AABBCCDDEEFFgghhiijjkkllmmnn" value="<?= htmlspecialchars($current['TELEGRAM_BOT_TOKEN'] ?? '') ?>">
            <?php if (!empty($current['TELEGRAM_BOT_TOKEN'])): ?>
            <div class="current">Atual: <?= $mask($current['TELEGRAM_BOT_TOKEN']) ?></div>
            <?php endif; ?>
            <div class="hint">Obtenha em @BotFather no Telegram</div>

            <label>Chat ID (Admin)</label>
            <input type="text" name="admin_chat_id" placeholder="7244348747" value="<?= htmlspecialchars($current['TELEGRAM_CHAT_ID'] ?? '') ?>">
            <?php if (!empty($current['TELEGRAM_CHAT_ID'])): ?>
            <div class="current">Atual: <?= htmlspecialchars($current['TELEGRAM_CHAT_ID']) ?></div>
            <?php endif; ?>
            <div class="hint">Envie /start para @userinfobot para descobrir seu Chat ID</div>
        </div>

        <!-- Bot Usuários -->
        <div class="card">
            <h2>👥 Bot Usuários <span class="badge badge-user">USERS</span></h2>
            <p style="font-size:.8rem;color:#888;">Os vendedores usam para consultar saldo, gerar PIX, ver vendas, etc.</p>

            <label>Bot Token</label>
            <input type="text" name="user_token" placeholder="8658594641:AAEUWBtvt3..." value="<?= htmlspecialchars($current['TELEGRAM_USER_BOT_TOKEN'] ?? '') ?>">
            <?php if (!empty($current['TELEGRAM_USER_BOT_TOKEN'])): ?>
            <div class="current">Atual: <?= $mask($current['TELEGRAM_USER_BOT_TOKEN']) ?></div>
            <?php endif; ?>
            <div class="hint">Token de um bot DIFERENTE do admin — crie outro em @BotFather</div>

            <label>Webhook Secret</label>
            <input type="text" name="user_secret" placeholder="meu_secret_seguro_123" value="<?= htmlspecialchars($current['TELEGRAM_USER_BOT_SECRET'] ?? '') ?>">
            <?php if (!empty($current['TELEGRAM_USER_BOT_SECRET'])): ?>
            <div class="current">Atual: <?= htmlspecialchars($current['TELEGRAM_USER_BOT_SECRET']) ?></div>
            <?php endif; ?>
            <div class="hint">Qualquer texto aleatório para segurança do webhook</div>

            <label>Username do Bot</label>
            <input type="text" name="user_username" placeholder="GhostPixBot" value="<?= htmlspecialchars($current['TELEGRAM_USER_BOT_USERNAME'] ?? '') ?>">
            <?php if (!empty($current['TELEGRAM_USER_BOT_USERNAME'])): ?>
            <div class="current">Atual: @<?= htmlspecialchars($current['TELEGRAM_USER_BOT_USERNAME']) ?></div>
            <?php endif; ?>
            <div class="hint">Nome de usuário do bot (sem @)</div>
        </div>

        <button type="submit" class="btn btn-primary">💾 Salvar e Configurar</button>
    </form>

    <div class="warning">⚠️ DELETE este arquivo do servidor após configurar! Ele expõe dados sensíveis.</div>
</div>
</body>
</html>
