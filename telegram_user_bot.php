<?php
/**
 * telegram_user_bot.php — Bot Telegram para usuários da plataforma Ghost Pix
 *
 * Funcionalidades:
 *  - Vincular conta via token (/start {token})
 *  - Gerar cobranças PIX (/pix {valor})
 *  - Consultar saldo (/saldo)
 *  - Relatório de vendas (/vendas)
 *  - Solicitar saque (/sacar {valor})
 *  - Listar produtos (/produtos)
 *  - NLP em português natural
 */

require_once __DIR__ . '/includes/db.php';

// ── Config ──────────────────────────────────────────────────────────────────
$BOT_TOKEN = defined('TELEGRAM_USER_BOT_TOKEN') ? TELEGRAM_USER_BOT_TOKEN : '';

if (!$BOT_TOKEN) {
    http_response_code(200);
    exit('Bot token not configured');
}

// ── Verificar secret do webhook ─────────────────────────────────────────────
$expectedSecret = defined('TELEGRAM_USER_BOT_SECRET') ? TELEGRAM_USER_BOT_SECRET : '';
if ($expectedSecret) {
    $headerSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if ($headerSecret !== $expectedSecret) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// ── Helpers ─────────────────────────────────────────────────────────────────
function uReply(string $chatId, string $text, array $keyboard = []): void {
    global $BOT_TOKEN;
    $payload = [
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ];
    if ($keyboard) {
        $payload['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
    }
    $ch = curl_init("https://api.telegram.org/bot{$BOT_TOKEN}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function uSendPhoto(string $chatId, string $photoUrl, string $caption = ''): void {
    global $BOT_TOKEN;
    $ch = curl_init("https://api.telegram.org/bot{$BOT_TOKEN}/sendPhoto");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'chat_id' => $chatId,
            'photo'   => $photoUrl,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT    => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function formatBRL(float $v): string {
    return 'R$ ' . number_format($v, 2, ',', '.');
}

function div(): string { return "\n━━━━━━━━━━━━━━━━━━━━"; }

function footer(): string {
    return "\n\n🤖 <i>Ghost Pix Bot • " . date('H:i') . "</i>";
}

function greeting(): string {
    $h = (int)date('H');
    if ($h < 12) return 'Bom dia';
    if ($h < 18) return 'Boa tarde';
    return 'Boa noite';
}

function getUserByChatId(string $chatId): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE telegram_chat_id = ? AND status = 'approved'");
    $stmt->execute([$chatId]);
    return $stmt->fetch() ?: null;
}

function getFullUrl(string $path): string {
    $base = defined('APP_URL') ? APP_URL : 'https://pixghost.site';
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function getActivePixGoKeyForUser(int $userId): string {
    global $pdo;
    // Check user-specific key first, then global
    $stmt = $pdo->prepare("SELECT api_key FROM pixgo_apis WHERE user_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$userId]);
    $key = $stmt->fetchColumn();
    if ($key) return $key;

    // Global key
    $stmt = $pdo->query("SELECT api_key FROM pixgo_apis WHERE (user_id IS NULL OR user_id = 0) AND is_active = 1 LIMIT 1");
    $key = $stmt->fetchColumn();
    if ($key) return $key;

    return defined('PIXGO_API_KEY') ? PIXGO_API_KEY : '';
}

// ── Processar update do Telegram ────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(200);
    exit('OK');
}

// ── Callback Queries ────────────────────────────────────────────────────────
if (isset($input['callback_query'])) {
    $cb     = $input['callback_query'];
    $cbData = $cb['data'] ?? '';
    $cbChatId = (string)($cb['message']['chat']['id'] ?? '');
    $cbId   = $cb['id'];

    // Answer callback to remove loading
    $ch = curl_init("https://api.telegram.org/bot{$BOT_TOKEN}/answerCallbackQuery");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['callback_query_id' => $cbId]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    curl_exec($ch);
    curl_close($ch);

    $user = getUserByChatId($cbChatId);
    if (!$user) {
        uReply($cbChatId, "⚠️ Conta não vinculada. Use /start para conectar.");
        http_response_code(200);
        exit;
    }

    // Handle confirm_withdraw callback
    if (preg_match('/^confirm_withdraw_(\d+(?:\.\d+)?)$/', $cbData, $m)) {
        $amount = (float)$m[1];
        processWithdrawal($cbChatId, $user, $amount);
    }

    http_response_code(200);
    exit;
}

// ── Mensagem de texto ───────────────────────────────────────────────────────
$message = $input['message'] ?? null;
if (!$message || !isset($message['text'])) {
    http_response_code(200);
    exit('OK');
}

$chatId  = (string)$message['chat']['id'];
$text    = trim($message['text']);
$firstName = $message['from']['first_name'] ?? 'Usuário';

// Parse command
$command = '';
$arg     = '';
if (str_starts_with($text, '/')) {
    $parts  = explode(' ', $text, 2);
    $command = strtolower(str_replace('@' . ($input['message']['from']['username'] ?? ''), '', $parts[0]));
    $command = ltrim($command, '/');
    $arg     = trim($parts[1] ?? '');
}

// ══════════════════════════════════════════════════════════════════════════════
// /start {token} — Vincular conta
// ══════════════════════════════════════════════════════════════════════════════
if ($command === 'start') {
    if (!empty($arg)) {
        // Tentar vincular com token
        $token = trim($arg);
        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE telegram_link_token = ? AND telegram_link_expires > NOW()");
        $stmt->execute([$token]);
        $linkUser = $stmt->fetch();

        if ($linkUser) {
            // Check if this chat is already linked to another account
            $existingStmt = $pdo->prepare("SELECT id, full_name FROM users WHERE telegram_chat_id = ? AND id != ?");
            $existingStmt->execute([$chatId, $linkUser['id']]);
            $existing = $existingStmt->fetch();
            if ($existing) {
                uReply($chatId, "⚠️ Este Telegram já está vinculado à conta de <b>{$existing['full_name']}</b>.\n\nDesconecte primeiro na plataforma se quiser trocar." . footer());
                http_response_code(200);
                exit;
            }

            // Link!
            $pdo->prepare("UPDATE users SET telegram_chat_id = ?, telegram_link_token = NULL, telegram_link_expires = NULL WHERE id = ?")
                ->execute([$chatId, $linkUser['id']]);

            uReply($chatId,
                "✅ <b>Conta vinculada com sucesso!</b>" . div() . "\n\n"
                . "👤 <b>{$linkUser['full_name']}</b>\n"
                . "📧 {$linkUser['email']}\n\n"
                . "Agora você pode usar todos os comandos:\n"
                . "/saldo — Ver seu saldo\n"
                . "/pix {valor} — Gerar cobrança\n"
                . "/vendas — Suas vendas\n"
                . "/sacar {valor} — Solicitar saque\n"
                . "/produtos — Seus produtos\n"
                . "/ajuda — Ver todos os comandos"
                . footer()
            );
        } else {
            uReply($chatId,
                "❌ <b>Token inválido ou expirado.</b>\n\n"
                . "Gere um novo código em:\n"
                . "⚙️ <b>Configurações → Telegram</b> no painel Ghost Pix."
                . footer()
            );
        }
        http_response_code(200);
        exit;
    }

    // /start sem token — boas vindas
    $user = getUserByChatId($chatId);
    if ($user) {
        uReply($chatId,
            "👋 <b>" . greeting() . ", {$user['full_name']}!</b>" . div() . "\n\n"
            . "Sua conta já está conectada ✅\n\n"
            . "Use /ajuda para ver os comandos disponíveis."
            . footer()
        );
    } else {
        uReply($chatId,
            "👻 <b>Ghost Pix Bot</b>" . div() . "\n\n"
            . "Conecte sua conta Ghost Pix para usar o bot:\n\n"
            . "1️⃣ Acesse o <b>Painel Ghost Pix</b>\n"
            . "2️⃣ Vá em <b>Configurações → Telegram</b>\n"
            . "3️⃣ Clique em <b>\"Conectar Telegram\"</b>\n"
            . "4️⃣ Copie o link e abra aqui\n\n"
            . "💡 <i>Ou cole o código de vinculação usando:\n/start SEU_CODIGO</i>"
            . footer()
        );
    }
    http_response_code(200);
    exit;
}

// ── Verificar vinculação para todos os outros comandos ──────────────────────
$user = getUserByChatId($chatId);
if (!$user) {
    uReply($chatId,
        "🔒 <b>Conta não vinculada</b>\n\n"
        . "Vincule sua conta Ghost Pix primeiro:\n"
        . "⚙️ <b>Configurações → Telegram</b> no painel.\n\n"
        . "Depois use: /start SEU_CODIGO"
        . footer()
    );
    http_response_code(200);
    exit;
}

$userId = (int)$user['id'];
$userName = $user['full_name'] ?? $firstName;

// ══════════════════════════════════════════════════════════════════════════════
// NLP — Interpretar linguagem natural
// ══════════════════════════════════════════════════════════════════════════════
function interpretUserNLP(string $text): ?array {
    $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ã'=>'a','õ'=>'o','ç'=>'c','ê'=>'e','â'=>'a','ô'=>'o'];
    $t = mb_strtolower(trim($text));
    $tn = strtr($t, $map);

    // PIX
    if (preg_match('/(?:gera|gerar|criar|cria|faz|fazer|quero)\s+(?:um\s+)?(?:pix|cobranca)\s+(?:de\s+)?(?:r\$?\s*)?(\d+[.,]?\d*)/', $tn, $m)) {
        return ['action' => 'pix', 'amount' => (float)str_replace(',', '.', $m[1])];
    }
    if (preg_match('/pix\s+(?:de\s+)?(?:r\$?\s*)?(\d+[.,]?\d*)/', $tn, $m)) {
        return ['action' => 'pix', 'amount' => (float)str_replace(',', '.', $m[1])];
    }

    // Saldo
    if (preg_match('/\b(saldo|quanto\s+tenho|meu\s+saldo|quanto\s+tem|meu\s+dinheiro|minha\s+grana)\b/', $tn)) {
        return ['action' => 'saldo'];
    }

    // Vendas
    if (preg_match('/\b(minhas?\s+vendas?|quantos?\s+vendi|quanto\s+vendi|vendas?\s+de\s+hoje|vendas?\s+do\s+dia|como\s+tao?\s+as\s+vendas|relatorio|faturamento)\b/', $tn)) {
        return ['action' => 'vendas'];
    }

    // Sacar
    if (preg_match('/(?:sacar|saque|retirar|transferir)\s+(?:r\$?\s*)?(\d+[.,]?\d*)/', $tn, $m)) {
        return ['action' => 'sacar', 'amount' => (float)str_replace(',', '.', $m[1])];
    }
    if (preg_match('/\b(quero\s+sacar|solicitar\s+saque|fazer\s+saque|pedir\s+saque)\b/', $tn)) {
        return ['action' => 'sacar_help'];
    }

    // Produtos
    if (preg_match('/\b(meus?\s+produtos?|listar?\s+produtos?|estoque|meus?\s+itens)\b/', $tn)) {
        return ['action' => 'produtos'];
    }

    // Ajuda
    if (preg_match('/\b(ajuda|help|comandos|o\s+que\s+voce\s+faz|o\s+que\s+posso\s+fazer)\b/', $tn)) {
        return ['action' => 'ajuda'];
    }

    // Saudação
    if (preg_match('/^(oi|ola|eai|e\s+ai|fala|bom\s+dia|boa\s+tarde|boa\s+noite|hey|salve)/', $tn)) {
        return ['action' => 'saudacao'];
    }

    // Desconectar
    if (preg_match('/\b(desconectar|desvincular|remover\s+conta|desligar)\b/', $tn)) {
        return ['action' => 'desconectar'];
    }

    return null;
}

// ══════════════════════════════════════════════════════════════════════════════
// Handler Functions
// ══════════════════════════════════════════════════════════════════════════════

// ── SALDO ───────────────────────────────────────────────────────────────────
function handleSaldo(string $chatId, array $user): void {
    global $pdo;
    $userId = $user['id'];

    // Saldo disponível (descontando saques pendentes)
    $pendingWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
    $pendingWd->execute([$userId]);
    $pendingTotal = (float)$pendingWd->fetchColumn();

    $balance = (float)$user['balance'];
    $available = $balance - $pendingTotal;

    // Vendas de hoje
    $todayStmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(amount_brl), 0) FROM transactions WHERE user_id = ? AND status = 'paid' AND DATE(created_at) = CURDATE()");
    $todayStmt->execute([$userId]);
    $today = $todayStmt->fetch(PDO::FETCH_NUM);

    uReply($chatId,
        "💰 <b>Seu Saldo</b>" . div() . "\n\n"
        . "💵 <b>Saldo total:</b> " . formatBRL($balance) . "\n"
        . ($pendingTotal > 0 ? "⏳ <b>Em saques pendentes:</b> " . formatBRL($pendingTotal) . "\n" : "")
        . "✅ <b>Disponível:</b> " . formatBRL($available) . "\n\n"
        . "📊 <b>Hoje:</b> {$today[0]} vendas — " . formatBRL((float)$today[1])
        . footer()
    );
}

// ── VENDAS ──────────────────────────────────────────────────────────────────
function handleVendas(string $chatId, array $user): void {
    global $pdo;
    $userId = $user['id'];

    $periods = [
        'Hoje'    => "DATE(created_at) = CURDATE()",
        '7 dias'  => "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
        '30 dias' => "created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
        'Total'   => "1=1",
    ];

    $msg = "📊 <b>Suas Vendas</b>" . div() . "\n\n";

    foreach ($periods as $label => $where) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(amount_brl),0) AS v FROM transactions WHERE user_id = ? AND status = 'paid' AND {$where}");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $emoji = ($label === 'Total') ? '🏆' : (($label === 'Hoje') ? '📅' : '📈');
        $msg .= "{$emoji} <b>{$label}:</b> {$row['c']} vendas — " . formatBRL((float)$row['v']) . "\n";
    }

    // Cobranças pendentes
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'pending'");
    $pendingStmt->execute([$userId]);
    $pending = (int)$pendingStmt->fetchColumn();

    // Taxa de conversão hoje
    $todayChargesStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND DATE(created_at) = CURDATE()");
    $todayChargesStmt->execute([$userId]);
    $todayCharges = (int)$todayChargesStmt->fetchColumn();

    $todayPaidStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'paid' AND DATE(created_at) = CURDATE()");
    $todayPaidStmt->execute([$userId]);
    $todayPaid = (int)$todayPaidStmt->fetchColumn();
    $convRate = $todayCharges > 0 ? round(($todayPaid / $todayCharges) * 100, 1) : 0;

    $msg .= "\n⏳ Cobranças pendentes: {$pending}\n"
          . "📈 Conversão hoje: <b>{$convRate}%</b>"
          . footer();

    uReply($chatId, $msg);
}

// ── PIX ─────────────────────────────────────────────────────────────────────
function handlePix(string $chatId, array $user, float $amount): void {
    global $pdo;
    $userId = (int)$user['id'];

    if ($amount < 10) {
        uReply($chatId, "⚠️ Valor mínimo é <b>R$ 10,00</b>. Tente:\n<code>/pix 10</code>" . footer());
        return;
    }

    if ($user['status'] !== 'approved') {
        uReply($chatId, "❌ Sua conta ainda não foi aprovada para receber pagamentos." . footer());
        return;
    }

    uReply($chatId, "⏳ Gerando cobrança PIX de <b>" . formatBRL($amount) . "</b>...");

    $currentPixGoKey = getActivePixGoKeyForUser($userId);
    $externalId = 'tguser_' . $userId . '_' . time();

    // Simulation mode
    if ($currentPixGoKey === 'SUA_API_KEY_AQUI' || empty($currentPixGoKey)) {
        $pixId = 'sim_' . time();
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TESTE_' . $amount;
        $pixCode = '00020126360014br.gov.bcb.pix0114000000000000005204000053039865802BR5913GHOSTPIX6009SAOPAULO62070503***6304ABCD';

        $pixgoFee    = $amount * 0.02 + ($amount < 50 ? 1.00 : 0);
        $platformFee = $amount * ($user['commission_rate'] / 100);
        $netAmount   = $amount - $pixgoFee - $platformFee;

        saveTransaction($userId, $amount, $netAmount, $pixId, $pixCode, $qrImage, null, 'PIX via Telegram User Bot', $externalId, 'pix');
        $txId = (int)$pdo->lastInsertId();

        uSendPhoto($chatId, $qrImage, "QR Code — " . formatBRL($amount));
        uReply($chatId,
            "✅ <b>PIX gerado!</b> (simulação)" . div() . "\n\n"
            . "💵 <b>Valor:</b> " . formatBRL($amount) . "\n"
            . "🆔 <b>TX:</b> <code>#{$txId}</code>\n\n"
            . "⚠️ <i>Ambiente de simulação.</i>"
            . footer()
        );
        uReply($chatId, "<code>{$pixCode}</code>");
        return;
    }

    // Real PixGo call
    $data = [
        'amount'      => $amount,
        'description' => 'PIX via Telegram',
        'webhook_url' => getFullUrl('webhook.php'),
        'external_id' => $externalId,
    ];

    $ch = curl_init('https://pixgo.org/api/v1/payment/create');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => [
            'X-API-Key: ' . $currentPixGoKey,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $res = json_decode($response ?: '{}', true);

    if (($httpCode === 200 || $httpCode === 201) && !empty($res['success'])) {
        $pixData = $res['data'] ?? [];
        $pixId   = $pixData['payment_id'] ?? '';
        $qrImage = $pixData['qr_image_url'] ?? '';
        $pixCode = $pixData['qr_code'] ?? '';

        $pixgoFee    = $amount * 0.02 + ($amount < 50 ? 1.00 : 0);
        $platformFee = $amount * ($user['commission_rate'] / 100);
        $netAmount   = $amount - $pixgoFee - $platformFee;

        saveTransaction($userId, $amount, $netAmount, $pixId, $pixCode, $qrImage, null, 'PIX via Telegram User Bot', $externalId, 'pix');
        $txId = (int)$pdo->lastInsertId();

        if ($qrImage) {
            uSendPhoto($chatId, $qrImage, "QR Code — " . formatBRL($amount));
        }

        uReply($chatId,
            "✅ <b>PIX GERADO!</b>" . div() . "\n\n"
            . "💵 <b>Valor:</b> " . formatBRL($amount) . "\n"
            . "🆔 <b>TX:</b> <code>#{$txId}</code>\n\n"
            . "💡 <i>Código copia e cola enviado abaixo. Toque para copiar.</i>"
            . footer()
        );
        uReply($chatId, "<code>{$pixCode}</code>");
    } else {
        $errorMsg = $res['message'] ?? ($res['error'] ?? 'Erro desconhecido');
        uReply($chatId,
            "❌ <b>Erro ao gerar PIX</b>" . div() . "\n\n"
            . "Motivo: <code>{$errorMsg}</code>"
            . footer()
        );
    }
}

// ── SACAR ───────────────────────────────────────────────────────────────────
function handleSacar(string $chatId, array $user, float $amount): void {
    global $pdo;
    $userId = (int)$user['id'];
    $withdrawFee = 3.50;

    if ($amount < 10) {
        uReply($chatId, "⚠️ Valor mínimo para saque é <b>R$ 10,00</b>." . footer());
        return;
    }

    if (empty($user['pix_key'])) {
        uReply($chatId, "⚠️ Você precisa configurar sua <b>chave PIX</b> no painel antes de sacar.\n\n⚙️ <b>Configurações → Método de Recebimento</b>" . footer());
        return;
    }

    // Check available balance
    $pendingWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
    $pendingWd->execute([$userId]);
    $pendingTotal = (float)$pendingWd->fetchColumn();
    $available = (float)$user['balance'] - $pendingTotal;

    if ($amount > $available) {
        uReply($chatId,
            "❌ <b>Saldo insuficiente</b>\n\n"
            . "💵 Saldo: " . formatBRL((float)$user['balance']) . "\n"
            . ($pendingTotal > 0 ? "⏳ Saques pendentes: " . formatBRL($pendingTotal) . "\n" : "")
            . "✅ Disponível: " . formatBRL($available)
            . footer()
        );
        return;
    }

    $netAmount = $amount - $withdrawFee;

    // Confirmation button
    uReply($chatId,
        "🏦 <b>Confirmar Saque</b>" . div() . "\n\n"
        . "💵 Valor solicitado: <b>" . formatBRL($amount) . "</b>\n"
        . "📉 Taxa de saque: " . formatBRL($withdrawFee) . "\n"
        . "✅ Você receberá: <b>" . formatBRL($netAmount) . "</b>\n"
        . "🔑 Chave PIX: <code>{$user['pix_key']}</code>\n\n"
        . "Confirma o saque?",
        [[
            ['text' => '✅ Confirmar Saque', 'callback_data' => "confirm_withdraw_{$amount}"],
            ['text' => '❌ Cancelar', 'callback_data' => 'cancel'],
        ]]
    );
}

function processWithdrawal(string $chatId, array $user, float $amount): void {
    global $pdo;
    $userId = (int)$user['id'];
    $withdrawFee = 3.50;
    $netAmount = $amount - $withdrawFee;

    // Re-check balance
    $stmt = $pdo->prepare("SELECT balance, pix_key FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $freshUser = $stmt->fetch();

    $pendingWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
    $pendingWd->execute([$userId]);
    $pendingTotal = (float)$pendingWd->fetchColumn();
    $available = (float)$freshUser['balance'] - $pendingTotal;

    if ($amount > $available) {
        uReply($chatId, "❌ Saldo insuficiente. Disponível: " . formatBRL($available) . footer());
        return;
    }

    try {
        $pdo->prepare("INSERT INTO withdrawals (user_id, amount, pix_key, status) VALUES (?, ?, ?, 'pending')")
            ->execute([$userId, $netAmount, $freshUser['pix_key']]);

        // Notify admin
        try {
            require_once __DIR__ . '/includes/TelegramService.php';
            TelegramService::notifyWithdrawal($user['full_name'], $amount, $freshUser['pix_key']);
        } catch (Throwable $e) {}

        uReply($chatId,
            "✅ <b>Saque solicitado!</b>" . div() . "\n\n"
            . "💵 Valor: " . formatBRL($amount) . "\n"
            . "📉 Taxa: " . formatBRL($withdrawFee) . "\n"
            . "✅ Você receberá: <b>" . formatBRL($netAmount) . "</b>\n"
            . "🔑 PIX: <code>{$freshUser['pix_key']}</code>\n\n"
            . "⏳ <i>Aguarde a aprovação do administrador.</i>"
            . footer()
        );
    } catch (Throwable $e) {
        uReply($chatId, "❌ Erro ao processar saque. Tente novamente." . footer());
    }
}

// ── PRODUTOS ────────────────────────────────────────────────────────────────
function handleProdutos(string $chatId, array $user): void {
    global $pdo;
    $userId = (int)$user['id'];

    $stmt = $pdo->prepare("SELECT id, name, price, stock, orders_count, status FROM products WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$userId]);
    $products = $stmt->fetchAll();

    if (!$products) {
        uReply($chatId, "📦 Você ainda não tem produtos cadastrados.\n\nCrie pelo painel: <b>Vendedor → Produtos</b>" . footer());
        return;
    }

    $msg = "📦 <b>Seus Produtos</b>" . div() . "\n\n";
    foreach ($products as $p) {
        $statusIcon = match($p['status']) {
            'approved' => '✅',
            'pending'  => '⏳',
            'rejected' => '❌',
            default    => '❓'
        };
        $msg .= "{$statusIcon} <b>{$p['name']}</b>\n"
              . "   💵 " . formatBRL((float)$p['price'])
              . " | 📦 Estoque: {$p['stock']}"
              . " | 🛒 {$p['orders_count']} vendas\n\n";
    }

    uReply($chatId, $msg . footer());
}

// ── DESCONECTAR ─────────────────────────────────────────────────────────────
function handleDesconectar(string $chatId, array $user): void {
    global $pdo;
    $pdo->prepare("UPDATE users SET telegram_chat_id = NULL WHERE id = ?")
        ->execute([$user['id']]);
    uReply($chatId,
        "✅ Conta desvinculada com sucesso.\n\n"
        . "Para reconectar, gere um novo código em:\n"
        . "⚙️ <b>Configurações → Telegram</b>"
        . footer()
    );
}

// ══════════════════════════════════════════════════════════════════════════════
// Command Router
// ══════════════════════════════════════════════════════════════════════════════
$handled = true;
switch ($command) {
    case 'ajuda':
    case 'help':
        uReply($chatId,
            "🤖 <b>" . greeting() . ", {$userName}!</b>" . div() . "\n\n"
            . "<b>💰 Financeiro:</b>\n"
            . "/saldo — Ver seu saldo\n"
            . "/pix {valor} — Gerar cobrança PIX\n"
            . "/vendas — Relatório de vendas\n"
            . "/sacar {valor} — Solicitar saque\n\n"
            . "<b>📦 Produtos:</b>\n"
            . "/produtos — Listar seus produtos\n\n"
            . "<b>⚙️ Conta:</b>\n"
            . "/desconectar — Desvincular Telegram\n\n"
            . "<b>💬 Ou pergunte naturalmente:</b>\n"
            . "<i>\"qual meu saldo?\"\n"
            . "\"gera pix de 50\"\n"
            . "\"quero sacar 100\"\n"
            . "\"minhas vendas\"</i>"
            . footer()
        );
        break;

    case 'saldo':
        handleSaldo($chatId, $user);
        break;

    case 'vendas':
    case 'relatorio':
    case 'relatório':
        handleVendas($chatId, $user);
        break;

    case 'pix':
        $amount = (float)str_replace(',', '.', $arg);
        handlePix($chatId, $user, $amount);
        break;

    case 'sacar':
    case 'saque':
        $amount = (float)str_replace(',', '.', $arg);
        if ($amount > 0) {
            handleSacar($chatId, $user, $amount);
        } else {
            uReply($chatId, "💡 Use: <code>/sacar 50</code>\n\nMínimo: R$ 10,00 | Taxa: R$ 3,50" . footer());
        }
        break;

    case 'produtos':
    case 'estoque':
        handleProdutos($chatId, $user);
        break;

    case 'desconectar':
        handleDesconectar($chatId, $user);
        break;

    default:
        $handled = false;
        break;
}

// ── NLP Fallback ────────────────────────────────────────────────────────────
if (!$handled) {
    $nlp = interpretUserNLP($text);

    if ($nlp) {
        switch ($nlp['action']) {
            case 'pix':
                handlePix($chatId, $user, $nlp['amount'] ?? 0);
                break;
            case 'saldo':
                handleSaldo($chatId, $user);
                break;
            case 'vendas':
                handleVendas($chatId, $user);
                break;
            case 'sacar':
                handleSacar($chatId, $user, $nlp['amount'] ?? 0);
                break;
            case 'sacar_help':
                uReply($chatId, "💡 Para sacar, use:\n<code>/sacar 50</code>\n\nMínimo: R$ 10,00 | Taxa: R$ 3,50" . footer());
                break;
            case 'produtos':
                handleProdutos($chatId, $user);
                break;
            case 'ajuda':
                uReply($chatId,
                    "🤖 Posso te ajudar com:\n\n"
                    . "💰 /saldo — Ver saldo\n"
                    . "⚡ /pix {valor} — Gerar PIX\n"
                    . "📊 /vendas — Relatório\n"
                    . "🏦 /sacar {valor} — Saque\n"
                    . "📦 /produtos — Seus produtos"
                    . footer()
                );
                break;
            case 'saudacao':
                $balance = formatBRL((float)$user['balance']);
                $todayStmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'paid' AND DATE(created_at) = CURDATE()");
                $todayStmt->execute([$user['id']]);
                $todaySales = (int)$todayStmt->fetchColumn();
                uReply($chatId,
                    "👋 <b>" . greeting() . ", {$userName}!</b>\n\n"
                    . "💰 Saldo: <b>{$balance}</b>\n"
                    . "📊 Vendas hoje: <b>{$todaySales}</b>\n\n"
                    . "O que precisa? 😊"
                    . footer()
                );
                break;
            case 'desconectar':
                handleDesconectar($chatId, $user);
                break;
            default:
                break;
        }
    } else {
        // Fallback
        uReply($chatId,
            "🤔 Não entendi... Tenta assim:\n\n"
            . "💬 <i>\"qual meu saldo?\"</i>\n"
            . "💬 <i>\"gera pix de 50\"</i>\n"
            . "💬 <i>\"minhas vendas\"</i>\n"
            . "💬 <i>\"quero sacar 100\"</i>\n\n"
            . "Ou use /ajuda para ver todos os comandos."
            . footer()
        );
    }
}

http_response_code(200);
