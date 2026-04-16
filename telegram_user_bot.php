<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════════╗
 * ║   Ghost Pix — Telegram User Bot v2.0                                   ║
 * ║   Bot inteligente, humanizado e profissional para vendedores            ║
 * ╚══════════════════════════════════════════════════════════════════════════╝
 *
 * Comandos:
 *  /start {token}    — Vincular conta
 *  /menu             — Menu principal interativo
 *  /saldo            — Saldo + resumo rápido
 *  /pix {valor}      — Gerar cobrança PIX
 *  /vendas           — Relatório de vendas (hoje/7d/30d/total)
 *  /historico        — Últimas transações
 *  /sacar {valor}    — Solicitar saque com confirmação
 *  /produtos         — Listar produtos + estoque
 *  /ranking          — Sua posição entre vendedores
 *  /meta {valor}     — Definir meta diária de vendas
 *  /dica             — Dica aleatória de vendas
 *  /desconectar      — Desvincular conta
 *  /ajuda            — Lista de comandos
 *
 * NLP: Entende perguntas naturais em português.
 */

date_default_timezone_set('America/Sao_Paulo');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/bot_errors.log');

try {
require_once __DIR__ . '/includes/db.php';
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/bot_errors.log', date('Y-m-d H:i:s') . " DB ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(200);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONFIG
// ═══════════════════════════════════════════════════════════════════════════
$BOT_TOKEN = defined('TELEGRAM_USER_BOT_TOKEN') ? TELEGRAM_USER_BOT_TOKEN : '';
if (!$BOT_TOKEN) { http_response_code(200); exit('OK'); }

$expectedSecret = defined('TELEGRAM_USER_BOT_SECRET') ? TELEGRAM_USER_BOT_SECRET : '';
if ($expectedSecret) {
    $headerSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if ($headerSecret !== $expectedSecret) { http_response_code(403); exit; }
}

// ═══════════════════════════════════════════════════════════════════════════
// TELEGRAM API HELPERS
// ═══════════════════════════════════════════════════════════════════════════
function tgApi(string $method, array $data): array {
    global $BOT_TOKEN;
    $ch = curl_init("https://api.telegram.org/bot{$BOT_TOKEN}/{$method}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res ?: '{}', true) ?: [];
}

function sendTyping(string $chatId): void {
    tgApi('sendChatAction', ['chat_id' => $chatId, 'action' => 'typing']);
}

function uReply(string $chatId, string $text, array $keyboard = [], bool $removeKeyboard = false): ?int {
    $payload = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
    if ($keyboard) {
        $payload['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
    } elseif ($removeKeyboard) {
        $payload['reply_markup'] = json_encode(['remove_keyboard' => true]);
    }
    $res = tgApi('sendMessage', $payload);
    return $res['result']['message_id'] ?? null;
}

function uEditMessage(string $chatId, int $messageId, string $text, array $keyboard = []): void {
    $payload = ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $text, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
    if ($keyboard) $payload['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
    tgApi('editMessageText', $payload);
}

function uSendPhoto(string $chatId, string $photoUrl, string $caption = ''): void {
    tgApi('sendPhoto', ['chat_id' => $chatId, 'photo' => $photoUrl, 'caption' => $caption, 'parse_mode' => 'HTML']);
}

function answerCallback(string $cbId, string $text = '', bool $alert = false): void {
    tgApi('answerCallbackQuery', ['callback_query_id' => $cbId, 'text' => $text, 'show_alert' => $alert]);
}

// ═══════════════════════════════════════════════════════════════════════════
// FORMATAÇÃO E UI
// ═══════════════════════════════════════════════════════════════════════════
function formatBRL(float $v): string { return 'R$ ' . number_format($v, 2, ',', '.'); }
function div(): string { return "\n━━━━━━━━━━━━━━━━━━━━━━━"; }
function divLight(): string { return "\n┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈"; }

function footer(): string {
    return "\n\n<i>👻 Ghost Pix • " . date('H:i') . "</i>";
}

function greeting(): string {
    $h = (int)date('H');
    if ($h >= 5 && $h < 12) return '☀️ Bom dia';
    if ($h >= 12 && $h < 18) return '🌤 Boa tarde';
    if ($h >= 18 && $h < 23) return '🌙 Boa noite';
    return '🌜 Boa madrugada';
}

function progressBar(float $current, float $total, int $size = 10): string {
    if ($total <= 0) return str_repeat('░', $size) . ' 0%';
    $pct = min($current / $total, 1.0);
    $filled = (int)round($pct * $size);
    $bar = str_repeat('▓', $filled) . str_repeat('░', $size - $filled);
    return $bar . ' ' . round($pct * 100) . '%';
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'agora';
    if ($diff < 3600) return (int)($diff / 60) . 'min atrás';
    if ($diff < 86400) return (int)($diff / 3600) . 'h atrás';
    return (int)($diff / 86400) . 'd atrás';
}

function maskName(string $fullName): string {
    $parts = explode(' ', trim($fullName));
    $first = mb_ucfirst(mb_strtolower($parts[0]));
    if (mb_strlen($first) <= 2) {
        $masked = $first;
    } else {
        $masked = mb_substr($first, 0, 2) . str_repeat('*', mb_strlen($first) - 3) . mb_substr($first, -1);
    }
    if (count($parts) > 1) {
        $last = end($parts);
        $masked .= ' ' . mb_strtoupper(mb_substr($last, 0, 1)) . '.';
    }
    return $masked;
}

function mb_ucfirst(string $s): string {
    return mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
}

function motivational(int $salesCount): string {
    if ($salesCount === 0) return "💡 <i>Nenhuma venda hoje ainda — que tal compartilhar seu link?</i>";
    if ($salesCount === 1) return "🎯 <i>Primeira venda do dia! O momentum começou.</i>";
    if ($salesCount < 5) return "📈 <i>Bom ritmo! Continue assim.</i>";
    if ($salesCount < 10) return "🔥 <i>Dia quente! Você tá voando!</i>";
    if ($salesCount < 20) return "🚀 <i>Impressionante! Poucos vendem tanto.</i>";
    return "👑 <i>Máquina de vendas! Dia histórico!</i>";
}

function randomTip(): string {
    $tips = [
        "📌 Divulgue seus links em horários de pico: 10h-12h e 19h-22h.",
        "📌 Crie urgência: ofertas por tempo limitado convertem mais.",
        "📌 Responda rápido seus clientes — velocidade fecha vendas.",
        "📌 Use cupons de desconto para fidelizar compradores.",
        "📌 Fotos profissionais dos produtos aumentam a conversão em até 40%.",
        "📌 Defina uma meta diária com /meta — acompanhe seu progresso aqui.",
        "📌 Mantenha seu estoque atualizado para não perder vendas.",
        "📌 Links de checkout personalizados passam mais confiança.",
        "📌 Analise quais produtos mais vendem em /vendas e foque neles.",
        "📌 Ofereça diferentes valores — R$ 29, R$ 49, R$ 97 são preços psicológicos.",
        "📌 Faça remarketing: clientes que já compraram voltam mais fácil.",
        "📌 Use o Telegram como canal VIP para seus melhores clientes.",
        "📌 Teste diferentes descrições e imagens — pequenas mudanças fazem diferença.",
        "📌 Saques frequentes te motivam mais. Saque toda semana!",
        "📌 Cadastre produtos na Vitrine para ganhar visibilidade orgânica.",
    ];
    return $tips[array_rand($tips)];
}

// ═══════════════════════════════════════════════════════════════════════════
// QUICK ACTION MENUS
// ═══════════════════════════════════════════════════════════════════════════
function mainMenuKeyboard(): array {
    return [
        [['text' => '💰 Saldo', 'callback_data' => 'act_saldo'], ['text' => '📊 Vendas', 'callback_data' => 'act_vendas']],
        [['text' => '⚡ Gerar PIX', 'callback_data' => 'act_pix_menu'], ['text' => '🏦 Sacar', 'callback_data' => 'act_sacar_menu']],
        [['text' => '📦 Produtos', 'callback_data' => 'act_produtos'], ['text' => '📜 Histórico', 'callback_data' => 'act_historico']],
        [['text' => '🏆 Ranking', 'callback_data' => 'act_ranking'], ['text' => '💡 Dica', 'callback_data' => 'act_dica']],
    ];
}

function pixAmountKeyboard(): array {
    return [
        [
            ['text' => 'R$ 10', 'callback_data' => 'pix_10'],
            ['text' => 'R$ 25', 'callback_data' => 'pix_25'],
            ['text' => 'R$ 50', 'callback_data' => 'pix_50'],
        ],
        [
            ['text' => 'R$ 100', 'callback_data' => 'pix_100'],
            ['text' => 'R$ 200', 'callback_data' => 'pix_200'],
            ['text' => 'R$ 500', 'callback_data' => 'pix_500'],
        ],
        [['text' => '🔢 Outro valor', 'callback_data' => 'pix_custom']],
        [['text' => '« Voltar ao menu', 'callback_data' => 'act_menu']],
    ];
}

function afterActionKeyboard(): array {
    return [
        [['text' => '📋 Menu', 'callback_data' => 'act_menu'], ['text' => '💰 Saldo', 'callback_data' => 'act_saldo']],
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// DATABASE HELPERS
// ═══════════════════════════════════════════════════════════════════════════
function getUserByChatId(string $chatId): ?array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE telegram_chat_id = ? AND status = 'approved'");
    $stmt->execute([$chatId]);
    return $stmt->fetch() ?: null;
}

function getActivePixGoKeyForUser(int $userId): string {
    global $pdo;
    $stmt = $pdo->prepare("SELECT api_key FROM pixgo_apis WHERE user_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$userId]);
    $key = $stmt->fetchColumn();
    if ($key) return $key;
    $stmt = $pdo->query("SELECT api_key FROM pixgo_apis WHERE (user_id IS NULL OR user_id = 0) AND is_active = 1 LIMIT 1");
    $key = $stmt->fetchColumn();
    if ($key) return $key;
    return defined('PIXGO_API_KEY') ? PIXGO_API_KEY : '';
}

function getUserDailyGoal(int $userId): float {
    global $pdo;
    $stmt = $pdo->prepare("SELECT stat_value FROM daily_stats WHERE stat_date = CURDATE() AND stat_key = CONCAT('goal_', ?)");
    $stmt->execute([$userId]);
    $val = (int)($stmt->fetchColumn() ?: 0);
    return $val / 100;
}

function getUserTodayStats(int $userId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(amount_brl),0) AS vol FROM transactions WHERE user_id = ? AND status = 'paid' AND DATE(created_at) = CURDATE()");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getUserYesterdayStats(int $userId): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(amount_brl),0) AS vol FROM transactions WHERE user_id = ? AND status = 'paid' AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

function getChangeEmoji(float $current, float $previous): string {
    if ($previous == 0 && $current > 0) return '🆕';
    if ($previous == 0) return '';
    $pct = (($current - $previous) / $previous) * 100;
    if ($pct > 20) return '📈 +' . round($pct) . '%';
    if ($pct > 0) return '↗️ +' . round($pct) . '%';
    if ($pct == 0) return '➡️ 0%';
    if ($pct > -20) return '↘️ ' . round($pct) . '%';
    return '📉 ' . round($pct) . '%';
}

// ── Processar update do Telegram ────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(200);
    exit('OK');
}

// ═══════════════════════════════════════════════════════════════════════════
// CALLBACK QUERY ROUTER
// ═══════════════════════════════════════════════════════════════════════════
if (isset($input['callback_query'])) {
    $cb       = $input['callback_query'];
    $cbData   = $cb['data'] ?? '';
    $cbChatId = (string)($cb['message']['chat']['id'] ?? '');
    $cbMsgId  = (int)($cb['message']['message_id'] ?? 0);
    $cbId     = $cb['id'];

    $user = getUserByChatId($cbChatId);
    if (!$user) {
        answerCallback($cbId, '⚠️ Conta não vinculada', true);
        http_response_code(200);
        exit;
    }

    // ── Menu Principal ──────────────────────────────────────────────
    if ($cbData === 'act_menu') {
        answerCallback($cbId);
        $today = getUserTodayStats((int)$user['id']);
        uEditMessage($cbChatId, $cbMsgId,
            "👻 <b>Ghost Pix — Menu Principal</b>" . div() . "\n\n"
            . greeting() . ", <b>{$user['full_name']}</b>!\n\n"
            . "💰 Saldo: <b>" . formatBRL((float)$user['balance']) . "</b>\n"
            . "📊 Vendas hoje: <b>{$today['cnt']}</b> — " . formatBRL((float)$today['vol']) . "\n\n"
            . "Escolha uma opção:",
            mainMenuKeyboard()
        );
        http_response_code(200);
        exit;
    }

    // ── Ações rápidas ───────────────────────────────────────────────
    if ($cbData === 'act_saldo') {
        answerCallback($cbId, '💰 Carregando saldo...');
        handleSaldo($cbChatId, $user);
        http_response_code(200); exit;
    }
    if ($cbData === 'act_vendas') {
        answerCallback($cbId, '📊 Carregando vendas...');
        handleVendas($cbChatId, $user);
        http_response_code(200); exit;
    }
    if ($cbData === 'act_produtos') {
        answerCallback($cbId, '📦 Carregando produtos...');
        handleProdutos($cbChatId, $user);
        http_response_code(200); exit;
    }
    if ($cbData === 'act_historico') {
        answerCallback($cbId, '📜 Carregando histórico...');
        handleHistorico($cbChatId, $user);
        http_response_code(200); exit;
    }
    if ($cbData === 'act_ranking') {
        answerCallback($cbId, '🏆 Carregando ranking...');
        handleRanking($cbChatId, $user);
        http_response_code(200); exit;
    }
    if ($cbData === 'act_dica') {
        answerCallback($cbId);
        uReply($cbChatId, "💡 <b>Dica do Ghost Pix</b>" . div() . "\n\n" . randomTip() . "\n\n<i>Use /dica para mais dicas!</i>" . footer(), afterActionKeyboard());
        http_response_code(200); exit;
    }

    // ── PIX Menu ────────────────────────────────────────────────────
    if ($cbData === 'act_pix_menu') {
        answerCallback($cbId);
        uEditMessage($cbChatId, $cbMsgId,
            "⚡ <b>Gerar Cobrança PIX</b>" . div() . "\n\n"
            . "Escolha um valor rápido ou digite:\n"
            . "<code>/pix 75</code> para valor personalizado.",
            pixAmountKeyboard()
        );
        http_response_code(200); exit;
    }
    if (preg_match('/^pix_(\d+)$/', $cbData, $m)) {
        $amount = (float)$m[1];
        answerCallback($cbId, "⚡ Gerando PIX de R$ {$m[1]}...");
        handlePix($cbChatId, $user, $amount);
        http_response_code(200); exit;
    }
    if ($cbData === 'pix_custom') {
        answerCallback($cbId);
        uReply($cbChatId, "🔢 <b>Valor personalizado</b>\n\nDigite o valor desejado:\n<code>/pix 75</code>\n\nOu simplesmente: <i>\"gera pix de 75\"</i>" . footer());
        http_response_code(200); exit;
    }

    // ── Sacar Menu ──────────────────────────────────────────────────
    if ($cbData === 'act_sacar_menu') {
        answerCallback($cbId);
        $pendingWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
        $pendingWd->execute([$user['id']]);
        $pendingTotal = (float)$pendingWd->fetchColumn();
        $available = (float)$user['balance'] - $pendingTotal;
        uEditMessage($cbChatId, $cbMsgId,
            "🏦 <b>Solicitar Saque</b>" . div() . "\n\n"
            . "✅ Disponível: <b>" . formatBRL($available) . "</b>\n"
            . "📉 Taxa fixa: R$ 3,50\n\n"
            . "Digite o valor:\n<code>/sacar 50</code>\n\nOu: <i>\"quero sacar 100\"</i>",
            [[['text' => '« Voltar ao menu', 'callback_data' => 'act_menu']]]
        );
        http_response_code(200); exit;
    }

    // ── Confirmar Saque ─────────────────────────────────────────────
    if (preg_match('/^confirm_withdraw_(\d+(?:\.\d+)?)$/', $cbData, $m)) {
        answerCallback($cbId, '✅ Processando saque...');
        processWithdrawal($cbChatId, $user, (float)$m[1]);
        http_response_code(200); exit;
    }

    // ── Confirmar Desconexão ────────────────────────────────────────
    if ($cbData === 'confirm_disconnect') {
        answerCallback($cbId, '🔓 Desconectando...');
        doDisconnect($cbChatId, $user);
        http_response_code(200); exit;
    }

    // ── Histórico Paginação ─────────────────────────────────────────
    if (preg_match('/^hist_(\d+)$/', $cbData, $m)) {
        answerCallback($cbId);
        handleHistorico($cbChatId, $user, (int)$m[1]);
        http_response_code(200); exit;
    }

    // ── Ajuda ────────────────────────────────────────────────────────
    if ($cbData === 'act_ajuda') {
        answerCallback($cbId);
        uReply($cbChatId,
            "🤖 <b>Posso te ajudar com:</b>\n\n"
            . "💰 /saldo — Saldo e resumo\n"
            . "⚡ /pix {valor} — Gerar PIX\n"
            . "📊 /vendas — Relatório\n"
            . "📜 /historico — Transações\n"
            . "🏦 /sacar {valor} — Saque\n"
            . "📦 /produtos — Seus produtos\n"
            . "🏆 /ranking — Sua posição\n"
            . "🎯 /meta {valor} — Meta diária\n\n"
            . "Ou use /menu para o menu interativo!" . footer(),
            [[['text' => '📋 Abrir Menu', 'callback_data' => 'act_menu']]]
        );
        http_response_code(200); exit;
    }

    // ── Cancelar ────────────────────────────────────────────────────
    if ($cbData === 'cancel') {
        answerCallback($cbId, '❌ Cancelado');
        uEditMessage($cbChatId, $cbMsgId, "❌ <i>Ação cancelada.</i>\n\nUse /menu para voltar ao início.");
        http_response_code(200); exit;
    }

    answerCallback($cbId);
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
                . "🎉 Agora você tem acesso completo!\n\n"
                . "Use o menu abaixo ou pergunte naturalmente:\n"
                . "<i>\"qual meu saldo?\", \"gera pix de 50\", \"minhas vendas\"</i>"
                . footer(),
                mainMenuKeyboard()
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
        $today = getUserTodayStats((int)$user['id']);
        uReply($chatId,
            "👋 <b>" . greeting() . ", {$user['full_name']}!</b>" . div() . "\n\n"
            . "✅ Sua conta está conectada\n\n"
            . "💰 Saldo: <b>" . formatBRL((float)$user['balance']) . "</b>\n"
            . "📊 Vendas hoje: <b>{$today['cnt']}</b> — " . formatBRL((float)$today['vol']) . "\n\n"
            . "Escolha uma opção ou use /ajuda:",
            mainMenuKeyboard()
        );
    } else {
        uReply($chatId,
            "👻 <b>Ghost Pix Bot</b>" . div() . "\n\n"
            . "Seu assistente de vendas no Telegram!\n"
            . "Consulte saldo, gere PIX, acompanhe vendas e muito mais.\n\n"
            . "<b>Como conectar:</b>\n\n"
            . "1️⃣ Acesse o <b>Painel Ghost Pix</b>\n"
            . "2️⃣ Vá em ⚙️ <b>Configurações → Telegram</b>\n"
            . "3️⃣ Clique em <b>\"Gerar Código de Vinculação\"</b>\n"
            . "4️⃣ Clique no link gerado para conectar\n\n"
            . "💡 <i>Ou envie: /start SEU_CODIGO</i>"
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

// ═══════════════════════════════════════════════════════════════════════════
// NLP ENGINE — Português Natural (50+ padrões)
// ═══════════════════════════════════════════════════════════════════════════
function interpretUserNLP(string $text): ?array {
    $map = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ã'=>'a','õ'=>'o',
            'ç'=>'c','ê'=>'e','â'=>'a','ô'=>'o','ü'=>'u','à'=>'a'];
    $t = mb_strtolower(trim($text));
    $tn = strtr($t, $map);

    // ── PIX (com valor) ──────────────────────────────────────────────
    if (preg_match('/(?:gera|gerar|criar|cria|faz|fazer|quero|preciso|manda|bota|coloca|emite|emitir)\s+(?:um\s+)?(?:pix|cobranca|cobrar|qr\s*code)\s+(?:de\s+|no\s+valor\s+de\s+)?(?:r\$?\s*)?(\d+[.,]?\d*)/', $tn, $m)) {
        return ['action' => 'pix', 'amount' => (float)str_replace(',', '.', $m[1])];
    }
    if (preg_match('/pix\s+(?:de\s+)?(?:r\$?\s*)?(\d+[.,]?\d*)/', $tn, $m)) {
        return ['action' => 'pix', 'amount' => (float)str_replace(',', '.', $m[1])];
    }
    if (preg_match('/(?:cobrar|cobr[ao])\s+(?:r\$?\s*)?(\d+[.,]?\d*)/', $tn, $m)) {
        return ['action' => 'pix', 'amount' => (float)str_replace(',', '.', $m[1])];
    }
    if (preg_match('/(\d+[.,]?\d*)\s*(?:reais|real|conto|pila)/', $tn, $m)) {
        $v = (float)str_replace(',', '.', $m[1]);
        if ($v >= 10) return ['action' => 'pix', 'amount' => $v];
    }

    // ── Saldo ────────────────────────────────────────────────────────
    if (preg_match('/\b(saldo|quanto\s+tenho|meu\s+saldo|quanto\s+tem|meu\s+dinheiro|minha\s+grana|quanto\s+ta|ta\s+quanto|quanto\s+e\s+meu|ver\s+saldo|checar\s+saldo|conferir\s+saldo|dinheiro\s+na\s+conta|quanto\s+falta|quanto\s+ja\s+tenho)\b/', $tn)) {
        return ['action' => 'saldo'];
    }

    // ── Vendas / Relatório ───────────────────────────────────────────
    if (preg_match('/\b(minhas?\s+vendas?|quantos?\s+vendi|quanto\s+vendi|vendas?\s+de\s+hoje|vendas?\s+do\s+dia|como\s+tao?\s+as\s+vendas|relatorio|faturamento|quanto\s+faturei|como\s+foi\s+hoje|como\s+esta\s+hoje|como\s+ta\s+hoje|como\s+foram\s+as\s+vendas|tem\s+vendas?|vendi\s+quanto|vendi\s+alguma\s+coisa|tive\s+vendas?|resultados|desempenho|performance|como\s+estou\s+indo)\b/', $tn)) {
        return ['action' => 'vendas'];
    }

    // ── Sacar (com valor) ────────────────────────────────────────────
    if (preg_match('/(?:sacar|saque|retirar|transferir|mandar\s+pra\s+mim|enviar|quero\s+tirar)\s+(?:r\$?\s*)?(\d+[.,]?\d*)/', $tn, $m)) {
        return ['action' => 'sacar', 'amount' => (float)str_replace(',', '.', $m[1])];
    }
    if (preg_match('/\b(quero\s+sacar|solicitar\s+saque|fazer\s+saque|pedir\s+saque|sacar\s+tudo|retirar\s+dinheiro|tirar\s+dinheiro|quero\s+receber|mandar\s+meu\s+dinheiro)\b/', $tn)) {
        return ['action' => 'sacar_help'];
    }

    // ── Histórico ────────────────────────────────────────────────────
    if (preg_match('/\b(historico|transacoes|transacao|ultimas\s+vendas|ultimos\s+pix|movimentacao|movimentacoes|extrato)\b/', $tn)) {
        return ['action' => 'historico'];
    }

    // ── Produtos ─────────────────────────────────────────────────────
    if (preg_match('/\b(meus?\s+produtos?|listar?\s+produtos?|estoque|meus?\s+itens|catalogo|o\s+que\s+eu\s+vendo|minha\s+loja)\b/', $tn)) {
        return ['action' => 'produtos'];
    }

    // ── Ranking ──────────────────────────────────────────────────────
    if (preg_match('/\b(ranking|posicao|colocacao|top\s+vendedores|onde\s+eu\s+to|meu\s+lugar|como\s+to\s+no\s+ranking|estou\s+entre\s+os)\b/', $tn)) {
        return ['action' => 'ranking'];
    }

    // ── Meta ─────────────────────────────────────────────────────────
    if (preg_match('/(?:meta|objetivo|goal)\s+(?:de\s+)?(?:r\$?\s*)?(\d+[.,]?\d*)/', $tn, $m)) {
        return ['action' => 'meta', 'amount' => (float)str_replace(',', '.', $m[1])];
    }
    if (preg_match('/\b(minha\s+meta|como\s+ta\s+a\s+meta|meta\s+do\s+dia|falta\s+quanto)\b/', $tn)) {
        return ['action' => 'ver_meta'];
    }

    // ── Dica ─────────────────────────────────────────────────────────
    if (preg_match('/\b(dica|tip|sugestao|conselho|me\s+ajuda|como\s+vender\s+mais|estrategia)\b/', $tn)) {
        return ['action' => 'dica'];
    }

    // ── Menu ─────────────────────────────────────────────────────────
    if (preg_match('/\b(menu|opcoes|opcao|painel|dashboard|inicio|home)\b/', $tn)) {
        return ['action' => 'menu'];
    }

    // ── Ajuda ────────────────────────────────────────────────────────
    if (preg_match('/\b(ajuda|help|comandos|o\s+que\s+voce\s+faz|o\s+que\s+posso\s+fazer|como\s+funciona|tutorial|instrucoes|como\s+usar)\b/', $tn)) {
        return ['action' => 'ajuda'];
    }

    // ── Saudação (broad) ─────────────────────────────────────────────
    if (preg_match('/^(oi|ola|eai|e\s+ai|fala|bom\s+dia|boa\s+tarde|boa\s+noite|hey|salve|yo|opa|beleza|tudo\s+bem|tudo\s+certo|coee?|iae|blz|tmj|suave)/', $tn)) {
        return ['action' => 'saudacao'];
    }

    // ── Agradecimento ────────────────────────────────────────────────
    if (preg_match('/\b(obrigad[oa]|valeu|vlw|thanks|brigad[oa]|tmj|show|top|perfeito|massa|dahora)\b/', $tn)) {
        return ['action' => 'agradecimento'];
    }

    // ── Desconectar ──────────────────────────────────────────────────
    if (preg_match('/\b(desconectar|desvincular|remover\s+conta|desligar|sair)\b/', $tn)) {
        return ['action' => 'desconectar'];
    }

    return null;
}

// ═══════════════════════════════════════════════════════════════════════════
// HANDLER FUNCTIONS
// ═══════════════════════════════════════════════════════════════════════════

// ── SALDO (enhanced) ────────────────────────────────────────────────────
function handleSaldo(string $chatId, array $user): void {
    global $pdo;
    sendTyping($chatId);
    $userId = (int)$user['id'];

    $pendingWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
    $pendingWd->execute([$userId]);
    $pendingTotal = (float)$pendingWd->fetchColumn();

    $balance = (float)$user['balance'];
    $available = $balance - $pendingTotal;

    $today = getUserTodayStats($userId);
    $yesterday = getUserYesterdayStats($userId);
    $change = getChangeEmoji((float)$today['vol'], (float)$yesterday['vol']);

    $goal = getUserDailyGoal($userId);

    $msg = "💰 <b>Painel Financeiro</b>" . div() . "\n\n"
         . "💵 Saldo total: <b>" . formatBRL($balance) . "</b>\n";
    if ($pendingTotal > 0)
        $msg .= "⏳ Em saques pendentes: " . formatBRL($pendingTotal) . "\n";
    $msg .= "✅ Disponível: <b>" . formatBRL($available) . "</b>\n"
          . divLight() . "\n"
          . "📅 <b>Hoje:</b> {$today['cnt']} vendas — " . formatBRL((float)$today['vol']);
    if ($change) $msg .= " {$change}";
    $msg .= "\n📅 <b>Ontem:</b> {$yesterday['cnt']} vendas — " . formatBRL((float)$yesterday['vol']) . "\n";

    if ($goal > 0) {
        $msg .= divLight() . "\n"
              . "🎯 <b>Meta do dia:</b> " . formatBRL($goal) . "\n"
              . "   " . progressBar((float)$today['vol'], $goal, 12) . "\n";
    }

    $msg .= "\n" . motivational((int)$today['cnt']) . footer();

    uReply($chatId, $msg, afterActionKeyboard());
}

// ── VENDAS (enhanced com comparativos) ──────────────────────────────────
function handleVendas(string $chatId, array $user): void {
    global $pdo;
    sendTyping($chatId);
    $userId = (int)$user['id'];

    $periods = [
        ['label' => '📅 Hoje',    'where' => "DATE(t.created_at) = CURDATE()", 'prev' => "DATE(t.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)"],
        ['label' => '📆 7 dias',  'where' => "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)", 'prev' => "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND t.created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)"],
        ['label' => '🗓 30 dias', 'where' => "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)", 'prev' => "t.created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND t.created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)"],
        ['label' => '🏆 Total',   'where' => "1=1", 'prev' => null],
    ];

    $msg = "📊 <b>Relatório de Vendas</b>" . div() . "\n\n";

    foreach ($periods as $p) {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS c, COALESCE(SUM(amount_brl),0) AS v FROM transactions t WHERE t.user_id = ? AND t.status = 'paid' AND {$p['where']}");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        $line = "{$p['label']}: <b>{$row['c']}</b> vendas — <b>" . formatBRL((float)$row['v']) . "</b>";
        if ($p['prev']) {
            $prevStmt = $pdo->prepare("SELECT COALESCE(SUM(amount_brl),0) FROM transactions t WHERE t.user_id = ? AND t.status = 'paid' AND {$p['prev']}");
            $prevStmt->execute([$userId]);
            $prevVol = (float)$prevStmt->fetchColumn();
            $change = getChangeEmoji((float)$row['v'], $prevVol);
            if ($change) $line .= " {$change}";
        }
        $msg .= $line . "\n";
    }

    // Conversão hoje
    $chargesStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND DATE(created_at) = CURDATE()");
    $chargesStmt->execute([$userId]);
    $todayCharges = (int)$chargesStmt->fetchColumn();
    $paidStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'paid' AND DATE(created_at) = CURDATE()");
    $paidStmt->execute([$userId]);
    $todayPaid = (int)$paidStmt->fetchColumn();
    $convRate = $todayCharges > 0 ? round(($todayPaid / $todayCharges) * 100, 1) : 0;

    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ? AND status = 'pending'");
    $pendingStmt->execute([$userId]);
    $pending = (int)$pendingStmt->fetchColumn();

    $msg .= divLight() . "\n"
          . "⚡ PIX gerados hoje: {$todayCharges}\n"
          . "⏳ Pendentes agora: {$pending}\n"
          . "📈 Conversão hoje: <b>{$convRate}%</b>\n"
          . "\n" . randomTip() . footer();

    uReply($chatId, $msg, afterActionKeyboard());
}

// ── PIX (enhanced) ──────────────────────────────────────────────────────
function handlePix(string $chatId, array $user, float $amount): void {
    global $pdo;
    $userId = (int)$user['id'];

    if ($amount < 10) {
        uReply($chatId,
            "⚠️ <b>Valor mínimo: R$ 10,00</b>\n\n"
            . "Escolha um valor rápido ou digite:",
            pixAmountKeyboard()
        );
        return;
    }
    if ($amount > 50000) {
        uReply($chatId, "⚠️ Valor máximo por PIX: <b>R$ 50.000,00</b>." . footer());
        return;
    }
    if ($user['status'] !== 'approved') {
        uReply($chatId, "🔒 Sua conta ainda não foi aprovada. Aguarde a liberação pelo admin." . footer());
        return;
    }

    sendTyping($chatId);
    $loadingMsgId = uReply($chatId, "⏳ Gerando cobrança de <b>" . formatBRL($amount) . "</b>...\n\n<i>Aguarde um instante...</i>");

    $currentPixGoKey = getActivePixGoKeyForUser($userId);
    $externalId = 'tguser_' . $userId . '_' . time();

    // Simulation mode
    if ($currentPixGoKey === 'SUA_API_KEY_AQUI' || empty($currentPixGoKey)) {
        $pixId = 'sim_' . time();
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TESTE_' . $amount;
        $pixCode = '00020126360014br.gov.bcb.pix0114000000000000005204000053039865802BR5913GHOSTPIX6009SAOPAULO62070503***6304ABCD';
        $pixgoFee = $amount * 0.02 + ($amount < 50 ? 1.00 : 0);
        $platformFee = $amount * ($user['commission_rate'] / 100);
        $netAmount = $amount - $pixgoFee - $platformFee;
        saveTransaction($userId, $amount, $netAmount, $pixId, $pixCode, $qrImage, null, 'PIX via Telegram', $externalId, 'pix');
        $txId = (int)$pdo->lastInsertId();
        if ($loadingMsgId) uEditMessage($chatId, $loadingMsgId, "✅ <b>PIX gerado!</b> (simulação)" . div() . "\n\n💵 <b>Valor:</b> " . formatBRL($amount) . "\n💎 <b>Líquido:</b> " . formatBRL($netAmount) . "\n🆔 <b>TX:</b> <code>#{$txId}</code>\n\n⚠️ <i>Ambiente de simulação — PIX não é real.</i>\n💡 <i>Código copia e cola enviado abaixo.</i>" . footer(), afterActionKeyboard());
        uSendPhoto($chatId, $qrImage, "📱 QR Code — " . formatBRL($amount));
        uReply($chatId, "<code>{$pixCode}</code>");
        return;
    }

    // Real PixGo
    $data = ['amount' => $amount, 'description' => 'PIX via Telegram', 'webhook_url' => getFullUrl('webhook.php'), 'external_id' => $externalId];
    $ch = curl_init('https://pixgo.org/api/v1/payment/create');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['X-API-Key: ' . $currentPixGoKey, 'Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT => 30, CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $res = json_decode($response ?: '{}', true);

    if (($httpCode === 200 || $httpCode === 201) && !empty($res['success'])) {
        $pixData = $res['data'] ?? [];
        $pixId = $pixData['payment_id'] ?? '';
        $qrImage = $pixData['qr_image_url'] ?? '';
        $pixCode = $pixData['qr_code'] ?? '';
        $pixgoFee = $amount * 0.02 + ($amount < 50 ? 1.00 : 0);
        $platformFee = $amount * ($user['commission_rate'] / 100);
        $netAmount = $amount - $pixgoFee - $platformFee;
        saveTransaction($userId, $amount, $netAmount, $pixId, $pixCode, $qrImage, null, 'PIX via Telegram', $externalId, 'pix');
        $txId = (int)$pdo->lastInsertId();

        $successMsg = "✅ <b>PIX GERADO COM SUCESSO!</b>" . div() . "\n\n"
            . "💵 <b>Valor:</b> " . formatBRL($amount) . "\n"
            . "💎 <b>Líquido:</b> " . formatBRL($netAmount) . "\n"
            . "🆔 <b>TX:</b> <code>#{$txId}</code>\n"
            . "⏱ <b>Expira em:</b> 30 minutos\n\n"
            . "💡 <i>Código copia e cola enviado abaixo. Toque para copiar.</i>" . footer();

        if ($loadingMsgId) uEditMessage($chatId, $loadingMsgId, $successMsg, afterActionKeyboard());
        if ($qrImage) uSendPhoto($chatId, $qrImage, "📱 QR Code — " . formatBRL($amount));
        uReply($chatId, "<code>{$pixCode}</code>");
    } else {
        $errorMsg = $res['message'] ?? ($res['error'] ?? 'Erro de comunicação');
        if ($loadingMsgId) uEditMessage($chatId, $loadingMsgId, "❌ <b>Falha ao gerar PIX</b>" . div() . "\n\n<code>{$errorMsg}</code>\n\n💡 <i>Tente novamente em alguns instantes.</i>" . footer(), afterActionKeyboard());
    }
}

// ── SACAR (enhanced) ────────────────────────────────────────────────────
function handleSacar(string $chatId, array $user, float $amount): void {
    global $pdo;
    sendTyping($chatId);
    $userId = (int)$user['id'];
    $withdrawFee = 3.50;

    if ($amount < 10) {
        uReply($chatId, "⚠️ <b>Mínimo para saque: R$ 10,00</b>\n📉 Taxa fixa: R$ 3,50\n\nExemplo: <code>/sacar 50</code>" . footer(), afterActionKeyboard());
        return;
    }
    if (empty($user['pix_key'])) {
        uReply($chatId, "🔑 <b>Chave PIX não configurada</b>\n\nVá em ⚙️ <b>Configurações → Método de Recebimento</b> no painel para definir sua chave." . footer(), afterActionKeyboard());
        return;
    }

    $pendingWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
    $pendingWd->execute([$userId]);
    $pendingTotal = (float)$pendingWd->fetchColumn();
    $available = (float)$user['balance'] - $pendingTotal;

    if ($amount > $available) {
        $msg = "❌ <b>Saldo insuficiente</b>" . div() . "\n\n"
             . "💵 Saldo: " . formatBRL((float)$user['balance']) . "\n";
        if ($pendingTotal > 0) $msg .= "⏳ Saques pendentes: " . formatBRL($pendingTotal) . "\n";
        $msg .= "✅ Disponível: <b>" . formatBRL($available) . "</b>" . footer();
        uReply($chatId, $msg, afterActionKeyboard());
        return;
    }

    $netAmount = $amount - $withdrawFee;
    uReply($chatId,
        "🏦 <b>Confirmar Saque</b>" . div() . "\n\n"
        . "💵 Valor bruto: <b>" . formatBRL($amount) . "</b>\n"
        . "📉 Taxa de saque: -" . formatBRL($withdrawFee) . "\n"
        . "✅ Você recebe: <b>" . formatBRL($netAmount) . "</b>\n"
        . "🔑 PIX: <code>{$user['pix_key']}</code>\n\n"
        . "⚠️ <i>Após confirmar, o admin será notificado.</i>",
        [
            [['text' => '✅ Confirmar Saque', 'callback_data' => "confirm_withdraw_{$amount}"]],
            [['text' => '❌ Cancelar', 'callback_data' => 'cancel']],
        ]
    );
}

function processWithdrawal(string $chatId, array $user, float $amount): void {
    global $pdo;
    sendTyping($chatId);
    $userId = (int)$user['id'];
    $withdrawFee = 3.50;
    $netAmount = $amount - $withdrawFee;

    $stmt = $pdo->prepare("SELECT balance, pix_key FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $freshUser = $stmt->fetch();

    $pendingWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
    $pendingWd->execute([$userId]);
    $available = (float)$freshUser['balance'] - (float)$pendingWd->fetchColumn();

    if ($amount > $available) {
        uReply($chatId, "❌ Saldo mudou. Disponível: " . formatBRL($available) . footer(), afterActionKeyboard());
        return;
    }

    try {
        $pdo->prepare("INSERT INTO withdrawals (user_id, amount, pix_key, status) VALUES (?, ?, ?, 'pending')")
            ->execute([$userId, $netAmount, $freshUser['pix_key']]);
        try {
            require_once __DIR__ . '/includes/TelegramService.php';
            TelegramService::notifyWithdrawal($user['full_name'], $amount, $freshUser['pix_key']);
        } catch (Throwable $e) {}
        uReply($chatId,
            "✅ <b>Saque Solicitado!</b>" . div() . "\n\n"
            . "💵 Valor: " . formatBRL($amount) . "\n"
            . "📉 Taxa: -" . formatBRL($withdrawFee) . "\n"
            . "✅ Recebe: <b>" . formatBRL($netAmount) . "</b>\n"
            . "🔑 PIX: <code>{$freshUser['pix_key']}</code>\n\n"
            . "⏳ <i>O admin foi notificado e processará em breve.</i>\n"
            . "🔔 <i>Você receberá uma mensagem quando for aprovado!</i>"
            . footer(),
            afterActionKeyboard()
        );
    } catch (Throwable $e) {
        uReply($chatId, "❌ Erro ao processar. Tente novamente." . footer(), afterActionKeyboard());
    }
}

// ── HISTÓRICO ───────────────────────────────────────────────────────────
function handleHistorico(string $chatId, array $user, int $page = 0): void {
    global $pdo;
    sendTyping($chatId);
    $userId = (int)$user['id'];
    $perPage = 5;
    $offset = $page * $perPage;

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
    $totalStmt->execute([$userId]);
    $total = (int)$totalStmt->fetchColumn();

    if ($total === 0) {
        uReply($chatId, "📜 <b>Nenhuma transação encontrada</b>\n\nGere seu primeiro PIX com /pix ou pelo menu." . footer(), afterActionKeyboard());
        return;
    }

    $stmt = $pdo->prepare("SELECT id, amount_brl, status, customer_name, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$userId, $perPage, $offset]);
    $txs = $stmt->fetchAll();

    $msg = "📜 <b>Histórico de Transações</b>" . div() . "\n\n";
    foreach ($txs as $tx) {
        $icon = match($tx['status']) {
            'paid'    => '✅',
            'pending' => '⏳',
            'expired' => '⏰',
            'failed'  => '❌',
            default   => '❓'
        };
        $msg .= "{$icon} <code>#{$tx['id']}</code> — <b>" . formatBRL((float)$tx['amount_brl']) . "</b>\n"
              . "   👤 " . ($tx['customer_name'] ?: '<i>sem nome</i>') . " • " . timeAgo($tx['created_at']) . "\n\n";
    }

    $totalPages = (int)ceil($total / $perPage);
    $msg .= "<i>Página " . ($page + 1) . " de {$totalPages} • {$total} transações</i>" . footer();

    $nav = [];
    if ($page > 0) $nav[] = ['text' => '« Anterior', 'callback_data' => 'hist_' . ($page - 1)];
    if ($page + 1 < $totalPages) $nav[] = ['text' => 'Próxima »', 'callback_data' => 'hist_' . ($page + 1)];
    $kb = [];
    if ($nav) $kb[] = $nav;
    $kb[] = [['text' => '📋 Menu', 'callback_data' => 'act_menu']];

    uReply($chatId, $msg, $kb);
}

// ── PRODUTOS (enhanced) ─────────────────────────────────────────────────
function handleProdutos(string $chatId, array $user): void {
    global $pdo;
    sendTyping($chatId);
    $userId = (int)$user['id'];

    $stmt = $pdo->prepare("SELECT id, name, price, stock, orders_count, status FROM products WHERE user_id = ? ORDER BY orders_count DESC LIMIT 10");
    $stmt->execute([$userId]);
    $products = $stmt->fetchAll();

    if (!$products) {
        uReply($chatId,
            "📦 <b>Nenhum produto cadastrado</b>\n\n"
            . "Crie seus produtos no painel:\n"
            . "🌐 <b>Vendedor → Produtos</b>\n\n"
            . randomTip() . footer(),
            afterActionKeyboard()
        );
        return;
    }

    $totalStock = 0;
    $totalOrders = 0;
    $msg = "📦 <b>Seus Produtos</b>" . div() . "\n\n";
    foreach ($products as $i => $p) {
        $statusIcon = match($p['status']) {
            'approved' => '✅', 'pending' => '⏳', 'rejected' => '❌', default => '❓'
        };
        $stockWarn = ($p['stock'] !== null && $p['stock'] <= 3 && $p['stock'] > 0) ? '⚠️' : '';
        $stockText = ($p['stock'] === null || $p['stock'] == -1) ? '∞' : $p['stock'];
        $medal = $i === 0 && (int)$p['orders_count'] > 0 ? '👑 ' : '';
        $msg .= "{$statusIcon} {$medal}<b>{$p['name']}</b>\n"
              . "   💵 " . formatBRL((float)$p['price'])
              . " │ 📦 {$stockText} {$stockWarn}"
              . " │ 🛒 {$p['orders_count']}\n\n";
        $totalStock += (int)($p['stock'] ?? 0);
        $totalOrders += (int)$p['orders_count'];
    }

    $msg .= divLight() . "\n"
          . "📊 Total: " . count($products) . " produtos │ 🛒 {$totalOrders} vendas" . footer();

    uReply($chatId, $msg, afterActionKeyboard());
}

// ── RANKING ─────────────────────────────────────────────────────────────
function handleRanking(string $chatId, array $user): void {
    global $pdo;
    sendTyping($chatId);
    $userId = (int)$user['id'];

    // Top 10 sellers by volume this month
    $stmt = $pdo->query("
        SELECT u.id, u.full_name, COUNT(t.id) AS sales, COALESCE(SUM(t.amount_brl), 0) AS volume
        FROM users u LEFT JOIN transactions t ON u.id = t.user_id AND t.status = 'paid' AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        WHERE u.is_admin = 0 AND u.status = 'approved'
        GROUP BY u.id ORDER BY volume DESC LIMIT 10
    ");
    $ranking = $stmt->fetchAll();

    $msg = "🏆 <b>Ranking de Vendedores</b>\n<i>Últimos 30 dias</i>" . div() . "\n\n";
    $myPos = 0;
    $medals = ['🥇', '🥈', '🥉'];
    foreach ($ranking as $i => $r) {
        $medal = $medals[$i] ?? ($i + 1) . 'º';
        $isMe = (int)$r['id'] === $userId;
        $name = $isMe ? "<b>→ Você</b>" : maskName($r['full_name']);
        $msg .= "{$medal} {$name}\n"
              . "    {$r['sales']} vendas — " . formatBRL((float)$r['volume']) . "\n\n";
        if ($isMe) $myPos = $i + 1;
    }

    if ($myPos === 0) {
        // User not in top 10
        $posStmt = $pdo->prepare("
            SELECT COUNT(*) + 1 FROM (
                SELECT u2.id, COALESCE(SUM(t2.amount_brl), 0) AS vol
                FROM users u2 LEFT JOIN transactions t2 ON u2.id = t2.user_id AND t2.status = 'paid' AND t2.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                WHERE u2.is_admin = 0 AND u2.status = 'approved'
                GROUP BY u2.id
                HAVING vol > (
                    SELECT COALESCE(SUM(amount_brl), 0) FROM transactions WHERE user_id = ? AND status = 'paid' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                )
            ) sub
        ");
        $posStmt->execute([$userId]);
        $myPos = (int)$posStmt->fetchColumn();
        $msg .= "┈┈┈┈┈┈┈┈┈┈┈┈┈┈┈\n📍 <b>Sua posição: {$myPos}º lugar</b>\n";
    }

    $totalSellers = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND status = 'approved'")->fetchColumn();
    $msg .= "\n<i>🏅 Entre {$totalSellers} vendedores ativos</i>" . footer();

    uReply($chatId, $msg, afterActionKeyboard());
}

// ── META ─────────────────────────────────────────────────────────────────
function handleMeta(string $chatId, array $user, float $amount): void {
    global $pdo;
    $userId = (int)$user['id'];
    $key = "goal_{$userId}";

    $pdo->prepare("INSERT INTO daily_stats (stat_date, stat_key, stat_value) VALUES (CURDATE(), ?, ?) ON DUPLICATE KEY UPDATE stat_value = ?")
        ->execute([$key, (int)($amount * 100), (int)($amount * 100)]);

    $today = getUserTodayStats($userId);
    $progress = progressBar((float)$today['vol'], $amount, 12);

    uReply($chatId,
        "🎯 <b>Meta definida!</b>" . div() . "\n\n"
        . "💵 Meta: <b>" . formatBRL($amount) . "</b>\n"
        . "📊 Vendas hoje: " . formatBRL((float)$today['vol']) . "\n"
        . "   {$progress}\n\n"
        . "💡 <i>Vou te mostrar o progresso sempre que consultar o saldo.</i>"
        . footer(),
        afterActionKeyboard()
    );
}

function handleVerMeta(string $chatId, array $user): void {
    global $pdo;
    $userId = (int)$user['id'];
    $goal = getUserDailyGoal($userId);
    $today = getUserTodayStats($userId);

    if ($goal <= 0) {
        uReply($chatId,
            "🎯 <b>Nenhuma meta definida</b>\n\n"
            . "Defina uma meta diária:\n<code>/meta 500</code>\n\n"
            . "Isso te ajuda a acompanhar seu progresso!" . footer(),
            afterActionKeyboard()
        );
        return;
    }

    $remaining = max(0, $goal - (float)$today['vol']);
    $msg = "🎯 <b>Meta do Dia</b>" . div() . "\n\n"
         . "💵 Meta: <b>" . formatBRL($goal) . "</b>\n"
         . "📊 Alcançado: " . formatBRL((float)$today['vol']) . "\n"
         . "   " . progressBar((float)$today['vol'], $goal, 12) . "\n\n";

    if ($remaining <= 0) {
        $msg .= "🎉 <b>META BATIDA!</b> Parabéns! 🥳\n";
    } else {
        $msg .= "📍 Faltam: <b>" . formatBRL($remaining) . "</b>\n";
    }

    $msg .= footer();
    uReply($chatId, $msg, afterActionKeyboard());
}

// ── DESCONECTAR ─────────────────────────────────────────────────────────
function handleDesconectar(string $chatId, array $user): void {
    uReply($chatId,
        "⚠️ <b>Desconectar Telegram?</b>\n\n"
        . "Você perderá:\n"
        . "• Notificações de vendas\n"
        . "• Acesso aos comandos do bot\n\n"
        . "Tem certeza?",
        [
            [['text' => '🔓 Sim, desconectar', 'callback_data' => 'confirm_disconnect']],
            [['text' => '❌ Cancelar', 'callback_data' => 'cancel']],
        ]
    );
}

function doDisconnect(string $chatId, array $user): void {
    global $pdo;
    $pdo->prepare("UPDATE users SET telegram_chat_id = NULL WHERE id = ?")
        ->execute([$user['id']]);
    uReply($chatId,
        "✅ <b>Conta desvinculada</b>\n\n"
        . "Para reconectar, gere um novo código em:\n"
        . "⚙️ <b>Configurações → Telegram</b> no painel."
        . footer()
    );
}

// ═══════════════════════════════════════════════════════════════════════════
// COMMAND ROUTER
// ═══════════════════════════════════════════════════════════════════════════
$handled = true;
switch ($command) {
    case 'menu':
    case 'inicio':
        $today = getUserTodayStats($userId);
        uReply($chatId,
            "👻 <b>Ghost Pix — Menu Principal</b>" . div() . "\n\n"
            . greeting() . ", <b>{$userName}</b>!\n\n"
            . "💰 Saldo: <b>" . formatBRL((float)$user['balance']) . "</b>\n"
            . "📊 Vendas hoje: <b>{$today['cnt']}</b> — " . formatBRL((float)$today['vol']) . "\n\n"
            . "Escolha uma opção:",
            mainMenuKeyboard()
        );
        break;

    case 'ajuda':
    case 'help':
        uReply($chatId,
            "👻 <b>Ghost Pix Bot — Comandos</b>" . div() . "\n\n"
            . "<b>💰 Financeiro</b>\n"
            . "/saldo — Saldo, vendas, meta\n"
            . "/pix {valor} — Gerar cobrança PIX\n"
            . "/vendas — Relatório completo\n"
            . "/historico — Últimas transações\n"
            . "/sacar {valor} — Solicitar saque\n\n"
            . "<b>📦 Negócio</b>\n"
            . "/produtos — Seus produtos + estoque\n"
            . "/ranking — Sua posição entre vendedores\n"
            . "/meta {valor} — Definir meta diária\n"
            . "/dica — Dica de vendas\n\n"
            . "<b>⚙️ Conta</b>\n"
            . "/menu — Menu interativo\n"
            . "/desconectar — Desvincular Telegram\n\n"
            . "<b>💬 Linguagem Natural</b>\n"
            . "<i>Pergunte do jeito que quiser:</i>\n"
            . "  \"<i>qual meu saldo?</i>\"\n"
            . "  \"<i>gera pix de 50</i>\"\n"
            . "  \"<i>como foram as vendas?</i>\"\n"
            . "  \"<i>quero sacar 100</i>\"\n"
            . "  \"<i>meu ranking</i>\"\n"
            . "  \"<i>falta quanto pra meta?</i>\""
            . footer(),
            [[['text' => '📋 Abrir Menu', 'callback_data' => 'act_menu']]]
        );
        break;

    case 'saldo':
        handleSaldo($chatId, $user);
        break;

    case 'vendas':
    case 'relatorio':
    case 'relatório':
    case 'report':
        handleVendas($chatId, $user);
        break;

    case 'historico':
    case 'extrato':
        handleHistorico($chatId, $user);
        break;

    case 'pix':
        $amount = (float)str_replace(',', '.', $arg);
        if ($amount > 0) {
            handlePix($chatId, $user, $amount);
        } else {
            uReply($chatId,
                "⚡ <b>Gerar Cobrança PIX</b>" . div() . "\n\nEscolha um valor:",
                pixAmountKeyboard()
            );
        }
        break;

    case 'sacar':
    case 'saque':
        $amount = (float)str_replace(',', '.', $arg);
        if ($amount > 0) {
            handleSacar($chatId, $user, $amount);
        } else {
            $pendingWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
            $pendingWd->execute([$userId]);
            $available = (float)$user['balance'] - (float)$pendingWd->fetchColumn();
            uReply($chatId,
                "🏦 <b>Solicitar Saque</b>" . div() . "\n\n"
                . "✅ Disponível: <b>" . formatBRL($available) . "</b>\n"
                . "📉 Taxa fixa: R$ 3,50\n"
                . "📋 Mínimo: R$ 10,00\n\n"
                . "Use: <code>/sacar 50</code>" . footer(),
                afterActionKeyboard()
            );
        }
        break;

    case 'produtos':
    case 'estoque':
        handleProdutos($chatId, $user);
        break;

    case 'ranking':
    case 'top':
        handleRanking($chatId, $user);
        break;

    case 'meta':
    case 'objetivo':
        $amount = (float)str_replace(',', '.', $arg);
        if ($amount > 0) {
            handleMeta($chatId, $user, $amount);
        } else {
            handleVerMeta($chatId, $user);
        }
        break;

    case 'dica':
    case 'tip':
        uReply($chatId,
            "💡 <b>Dica do Ghost Pix</b>" . div() . "\n\n"
            . randomTip() . "\n\n"
            . "<i>Use /dica novamente para outra dica!</i>" . footer(),
            afterActionKeyboard()
        );
        break;

    case 'desconectar':
        handleDesconectar($chatId, $user);
        break;

    default:
        $handled = false;
        break;
}

// ═══════════════════════════════════════════════════════════════════════════
// NLP FALLBACK
// ═══════════════════════════════════════════════════════════════════════════
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
            case 'historico':
                handleHistorico($chatId, $user);
                break;
            case 'sacar':
                handleSacar($chatId, $user, $nlp['amount'] ?? 0);
                break;
            case 'sacar_help':
                $pendingWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
                $pendingWd->execute([$userId]);
                $available = (float)$user['balance'] - (float)$pendingWd->fetchColumn();
                uReply($chatId,
                    "🏦 <b>Solicitar Saque</b>\n\n"
                    . "✅ Disponível: <b>" . formatBRL($available) . "</b>\n"
                    . "📉 Taxa: R$ 3,50 | Mínimo: R$ 10,00\n\n"
                    . "Use: <code>/sacar 50</code>" . footer(),
                    afterActionKeyboard()
                );
                break;
            case 'produtos':
                handleProdutos($chatId, $user);
                break;
            case 'ranking':
                handleRanking($chatId, $user);
                break;
            case 'meta':
                handleMeta($chatId, $user, $nlp['amount'] ?? 0);
                break;
            case 'ver_meta':
                handleVerMeta($chatId, $user);
                break;
            case 'dica':
                uReply($chatId, "💡 <b>Dica do Ghost Pix</b>" . div() . "\n\n" . randomTip() . footer(), afterActionKeyboard());
                break;
            case 'menu':
                $today = getUserTodayStats($userId);
                uReply($chatId,
                    "👻 <b>Ghost Pix — Menu Principal</b>" . div() . "\n\n"
                    . greeting() . ", <b>{$userName}</b>!\n\n"
                    . "💰 Saldo: <b>" . formatBRL((float)$user['balance']) . "</b>\n"
                    . "📊 Vendas hoje: <b>{$today['cnt']}</b> — " . formatBRL((float)$today['vol']) . "\n\n"
                    . "Escolha uma opção:",
                    mainMenuKeyboard()
                );
                break;
            case 'ajuda':
                uReply($chatId,
                    "🤖 <b>Posso te ajudar com:</b>\n\n"
                    . "💰 /saldo — Saldo e resumo\n"
                    . "⚡ /pix {valor} — Gerar PIX\n"
                    . "📊 /vendas — Relatório\n"
                    . "📜 /historico — Transações\n"
                    . "🏦 /sacar {valor} — Saque\n"
                    . "📦 /produtos — Seus produtos\n"
                    . "🏆 /ranking — Sua posição\n"
                    . "🎯 /meta {valor} — Meta diária\n\n"
                    . "Ou use /menu para o menu interativo!"
                    . footer(),
                    [[['text' => '📋 Abrir Menu', 'callback_data' => 'act_menu']]]
                );
                break;
            case 'saudacao':
                sendTyping($chatId);
                $today = getUserTodayStats($userId);
                $balance = formatBRL((float)$user['balance']);
                $goal = getUserDailyGoal($userId);
                $msg = "👋 <b>" . greeting() . ", {$userName}!</b>\n\n"
                     . "💰 Saldo: <b>{$balance}</b>\n"
                     . "📊 Vendas hoje: <b>{$today['cnt']}</b> — " . formatBRL((float)$today['vol']) . "\n";
                if ($goal > 0) {
                    $msg .= "🎯 Meta: " . progressBar((float)$today['vol'], $goal, 8) . "\n";
                }
                $msg .= "\n" . motivational((int)$today['cnt']) . "\n\nO que precisa? 😊" . footer();
                uReply($chatId, $msg, mainMenuKeyboard());
                break;
            case 'agradecimento':
                $replies = [
                    "😊 Por nada! Tô aqui pra isso.",
                    "🤝 Sempre às ordens!",
                    "👊 Tmj! Boas vendas!",
                    "😄 Que bom que ajudei! Precisando, é só chamar.",
                    "🚀 Valeu! Bora vender mais!",
                ];
                uReply($chatId, $replies[array_rand($replies)] . footer(), afterActionKeyboard());
                break;
            case 'desconectar':
                handleDesconectar($chatId, $user);
                break;
            default:
                break;
        }
    } else {
        // Fallback inteligente
        $fallbacks = [
            "🤔 Hmm, não entendi bem... Tenta de outro jeito!\n\n",
            "😅 Essa eu não peguei... Vou te dar umas sugestões:\n\n",
            "� Não reconheci esse comando. Tenta assim:\n\n",
        ];
        uReply($chatId,
            $fallbacks[array_rand($fallbacks)]
            . "💬 <i>\"qual meu saldo?\"</i>\n"
            . "💬 <i>\"gera pix de 50\"</i>\n"
            . "💬 <i>\"como foram as vendas?\"</i>\n"
            . "💬 <i>\"quero sacar 100\"</i>\n"
            . "💬 <i>\"meu ranking\"</i>\n\n"
            . "Ou toque no botão abaixo:"
            . footer(),
            [[['text' => '📋 Abrir Menu', 'callback_data' => 'act_menu'], ['text' => '❓ Ajuda', 'callback_data' => 'act_ajuda']]]
        );
    }
}

http_response_code(200);
