<?php
/**
 * telegram_bot.php — Webhook do Bot Telegram para Ghost Pix
 *
 * Registrar: POST https://api.telegram.org/bot{TOKEN}/setWebhook
 *   {"url": "https://pixghost.site/telegram_bot.php?secret=SEU_SECRET"}
 *
 * Comandos suportados:
 *   /help       — lista de comandos
 *   /stats      — estatísticas da plataforma
 *   /saques     — saques pendentes
 *   /pendentes  — produtos aguardando aprovação
 *   /usuarios   — últimos cadastros
 *   /bloquear {id}     — bloquear usuário
 *   /aprovarusuario {id} — aprovar usuário
 *   /aprovarproduto {id} — aprovar produto
 *   /recusarproduto {id} — recusar produto
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/TelegramService.php';

// ── Autenticação por secret ──────────────────────────────────────────────────
$expectedSecret = defined('TELEGRAM_WEBHOOK_SECRET') ? TELEGRAM_WEBHOOK_SECRET : '';
$incomingSecret = $_GET['secret'] ?? '';
if ($expectedSecret && $incomingSecret !== $expectedSecret) {
    http_response_code(403);
    exit('Forbidden');
}

// ── Ler update ───────────────────────────────────────────────────────────────
$raw    = file_get_contents('php://input');
$update = json_decode($raw, true);
if (!$update) { http_response_code(200); exit; }

// ── Apenas o admin pode usar o bot ──────────────────────────────────────────
$allowedChatId = defined('TELEGRAM_CHAT_ID') ? (string) TELEGRAM_CHAT_ID : '';

function reply(string $chatId, string $msg): void {
    TelegramService::replyTo($chatId, $msg);
}

function replyKeyboard(string $chatId, string $msg, array $kb): void {
    TelegramService::sendWithKeyboard($msg, $kb, $chatId);
}

function isAllowed(string $chatId): bool {
    global $allowedChatId;
    return $chatId === $allowedChatId;
}

function formatBRL(float $v): string { return 'R$ ' . number_format($v, 2, ',', '.'); }
function div(): string { return "\n━━━━━━━━━━━━━━━━━━━━"; }
function footer(): string { return div() . "\n🤖 <i>Ghost Pix Bot • " . date('H:i') . "</i>"; }

// ── Processar callback query (botões inline) ─────────────────────────────────
if (isset($update['callback_query'])) {
    $cb     = $update['callback_query'];
    $cbId   = $cb['id'];
    $chatId = (string) $cb['message']['chat']['id'];
    $msgId  = (int)    $cb['message']['message_id'];
    $data   = $cb['data'] ?? '';

    if (!isAllowed($chatId)) {
        TelegramService::answerCallback($cbId, '❌ Não autorizado.', true);
        http_response_code(200); exit;
    }

    // prod_approve_123 | prod_reject_123
    if (preg_match('/^prod_(approve|reject)_(\d+)$/', $data, $m)) {
        $action    = $m[1];
        $productId = (int) $m[2];

        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.price, p.status, u.full_name AS seller_name, u.id AS user_id
            FROM products p JOIN users u ON u.id = p.user_id WHERE p.id = ?
        ");
        $stmt->execute([$productId]);
        $prod = $stmt->fetch();

        if (!$prod) {
            TelegramService::answerCallback($cbId, '❌ Produto não encontrado.', true);
            http_response_code(200); exit;
        }

        if ($action === 'approve') {
            $pdo->prepare("UPDATE products SET status = 'active', updated_at = NOW() WHERE id = ?")
                ->execute([$productId]);
            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')")
                ->execute([$prod['user_id'], '✅ Produto Aprovado!', 'Seu produto "' . $prod['name'] . '" foi aprovado e está disponível na plataforma.']);

            TelegramService::answerCallback($cbId, '✅ Produto aprovado!');
            TelegramService::editMessageText(
                "✅ <b>PRODUTO APROVADO</b>" . div() . "\n\n"
                . "📦 <b>Produto:</b>  {$prod['name']}\n"
                . "🏪 <b>Vendedor:</b> {$prod['seller_name']}\n"
                . "🆔 <b>ID:</b>       <code>#{$productId}</code>\n\n"
                . "✅ <i>Aprovado via Telegram Bot</i>" . footer(),
                $msgId, $chatId
            );
        } else {
            $pdo->prepare("UPDATE products SET status = 'inactive', updated_at = NOW() WHERE id = ?")
                ->execute([$productId]);
            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'warning')")
                ->execute([$prod['user_id'], '❌ Produto Reprovado', 'Seu produto "' . $prod['name'] . '" não foi aprovado. Entre em contato para mais informações.']);

            TelegramService::answerCallback($cbId, '❌ Produto recusado.');
            TelegramService::editMessageText(
                "❌ <b>PRODUTO RECUSADO</b>" . div() . "\n\n"
                . "📦 <b>Produto:</b>  {$prod['name']}\n"
                . "🏪 <b>Vendedor:</b> {$prod['seller_name']}\n"
                . "🆔 <b>ID:</b>       <code>#{$productId}</code>\n\n"
                . "❌ <i>Recusado via Telegram Bot</i>" . footer(),
                $msgId, $chatId
            );
        }

        http_response_code(200); exit;
    }

    // withdraw_approve_123 | withdraw_reject_123
    if (preg_match('/^wd_(approve|reject)_(\d+)$/', $data, $m)) {
        $action = $m[1];
        $wdId   = (int) $m[2];

        $stmt = $pdo->prepare("SELECT w.*, u.full_name FROM withdrawals w JOIN users u ON u.id = w.user_id WHERE w.id = ?");
        $stmt->execute([$wdId]);
        $wd = $stmt->fetch();

        if (!$wd || $wd['status'] !== 'pending') {
            TelegramService::answerCallback($cbId, '⚠️ Saque não encontrado ou já processado.', true);
            http_response_code(200); exit;
        }

        if ($action === 'approve') {
            $pdo->prepare("UPDATE withdrawals SET status = 'paid' WHERE id = ?")->execute([$wdId]);
            TelegramService::answerCallback($cbId, '✅ Saque marcado como pago!');
            TelegramService::editMessageText(
                "✅ <b>SAQUE APROVADO</b>" . div() . "\n\n"
                . "👤 <b>Usuário:</b> {$wd['full_name']}\n"
                . "💵 <b>Valor:</b>   " . formatBRL((float)$wd['amount']) . "\n"
                . "🔑 <b>Pix:</b>     <code>{$wd['pix_key']}</code>\n\n"
                . "✅ <i>Aprovado via Telegram Bot</i>" . footer(),
                $msgId, $chatId
            );
        } else {
            $pdo->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?")->execute([$wdId]);
            $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([(float)$wd['amount'], $wd['user_id']]);
            TelegramService::answerCallback($cbId, '❌ Saque rejeitado e saldo devolvido.');
            TelegramService::editMessageText(
                "❌ <b>SAQUE REJEITADO</b>" . div() . "\n\n"
                . "👤 <b>Usuário:</b> {$wd['full_name']}\n"
                . "💵 <b>Valor:</b>   " . formatBRL((float)$wd['amount']) . "\n\n"
                . "♻️ <i>Saldo devolvido ao usuário.</i>" . footer(),
                $msgId, $chatId
            );
        }

        http_response_code(200); exit;
    }

    // user_approve_123 | user_block_123
    if (preg_match('/^user_(approve|block)_(\d+)$/', $data, $m)) {
        $action = $m[1];
        $uid    = (int) $m[2];

        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $u = $stmt->fetch();

        if (!$u) {
            TelegramService::answerCallback($cbId, '❌ Usuário não encontrado.', true);
            http_response_code(200); exit;
        }

        $newStatus = $action === 'approve' ? 'approved' : 'blocked';
        $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $uid]);

        $icon  = $action === 'approve' ? '✅' : '🔴';
        $label = $action === 'approve' ? 'APROVADO' : 'BLOQUEADO';
        TelegramService::answerCallback($cbId, "{$icon} Usuário {$label}!");
        TelegramService::editMessageText(
            "{$icon} <b>USUÁRIO {$label}</b>" . div() . "\n\n"
            . "👤 <b>Nome:</b>   {$u['full_name']}\n"
            . "📧 <b>E-mail:</b> <code>{$u['email']}</code>\n\n"
            . "<i>{$icon} Alterado via Telegram Bot</i>" . footer(),
            $msgId, $chatId
        );

        http_response_code(200); exit;
    }

    TelegramService::answerCallback($cbId);
    http_response_code(200); exit;
}

// ── Processar mensagens / comandos ───────────────────────────────────────────
if (!isset($update['message'])) { http_response_code(200); exit; }

$msg    = $update['message'];
$chatId = (string) ($msg['chat']['id'] ?? '');
$text   = trim($msg['text'] ?? '');

if (!isAllowed($chatId)) {
    reply($chatId, '❌ Acesso negado. Este bot é privado.');
    http_response_code(200); exit;
}

$parts   = explode(' ', $text, 2);
$command = strtolower(ltrim($parts[0], '/'));
$arg     = trim($parts[1] ?? '');

// Remover @botname do comando se houver
if (strpos($command, '@') !== false) {
    $command = explode('@', $command)[0];
}

switch ($command) {

    // ── HELP ─────────────────────────────────────────────────────────
    case 'start':
    case 'help':
        reply($chatId,
            "🤖 <b>Ghost Pix Bot — Comandos</b>" . div() . "\n\n"
            . "📊 /stats — Estatísticas da plataforma\n"
            . "💸 /saques — Saques pendentes\n"
            . "🛍️ /pendentes — Produtos aguardando aprovação\n"
            . "👥 /usuarios — Últimos 10 cadastros\n\n"
            . "<b>Ações rápidas:</b>\n"
            . "✅ /aprovarproduto {id}\n"
            . "❌ /recusarproduto {id}\n"
            . "✅ /aprovarusuario {id}\n"
            . "🔴 /bloquear {id}\n"
            . "💰 /saldo {id} — ver saldo do usuário\n"
            . "🔑 /resetsenha {id} — resetar senha"
            . footer()
        );
        break;

    // ── STATS ────────────────────────────────────────────────────────
    case 'stats':
        $stats = $pdo->query("
            SELECT
                (SELECT COUNT(*) FROM users WHERE is_admin = 0) AS total_users,
                (SELECT COUNT(*) FROM users WHERE is_admin = 0 AND status = 'pending') AS pending_users,
                (SELECT COUNT(*) FROM users WHERE is_admin = 0 AND created_at >= CURDATE()) AS new_today,
                (SELECT COUNT(*) FROM transactions WHERE status = 'paid') AS total_paid,
                (SELECT COALESCE(SUM(amount_brl),0) FROM transactions WHERE status = 'paid') AS total_volume,
                (SELECT COALESCE(SUM(amount_brl),0) FROM transactions WHERE status = 'paid' AND DATE(created_at) = CURDATE()) AS today_volume,
                (SELECT COUNT(*) FROM products WHERE status = 'pending') AS pending_products,
                (SELECT COUNT(*) FROM withdrawals WHERE status = 'pending') AS pending_withdrawals
        ")->fetch();

        reply($chatId,
            "📊 <b>ESTATÍSTICAS DA PLATAFORMA</b>" . div() . "\n\n"
            . "👥 <b>Usuários:</b>      {$stats['total_users']} ({$stats['pending_users']} pendentes)\n"
            . "🆕 <b>Cadastros hoje:</b> {$stats['new_today']}\n"
            . "✅ <b>Vendas totais:</b>  {$stats['total_paid']}\n"
            . "💵 <b>Volume total:</b>  " . formatBRL((float)$stats['total_volume']) . "\n"
            . "📈 <b>Volume hoje:</b>   " . formatBRL((float)$stats['today_volume']) . "\n"
            . "🛍️ <b>Prod. pendentes:</b> {$stats['pending_products']}\n"
            . "💸 <b>Saques pend.:</b>  {$stats['pending_withdrawals']}"
            . footer()
        );
        break;

    // ── SAQUES PENDENTES ─────────────────────────────────────────────
    case 'saques':
        $withdrawals = $pdo->query("
            SELECT w.id, w.amount, w.pix_key, u.full_name
            FROM withdrawals w JOIN users u ON u.id = w.user_id
            WHERE w.status = 'pending' ORDER BY w.created_at ASC LIMIT 5
        ")->fetchAll();

        if (!$withdrawals) {
            reply($chatId, "✅ <b>Nenhum saque pendente!</b>" . footer());
            break;
        }

        foreach ($withdrawals as $wd) {
            $wText =
                "💸 <b>SAQUE PENDENTE #" . $wd['id'] . "</b>" . div() . "\n\n"
                . "👤 <b>Usuário:</b> {$wd['full_name']}\n"
                . "💵 <b>Valor:</b>   " . formatBRL((float)$wd['amount']) . "\n"
                . "🔑 <b>Pix:</b>     <code>{$wd['pix_key']}</code>\n\n"
                . "<i>Use os botões para processar:</i>";
            $kb = [[
                ['text' => '✅ Pagar', 'callback_data' => "wd_approve_{$wd['id']}"],
                ['text' => '❌ Rejeitar', 'callback_data' => "wd_reject_{$wd['id']}"],
            ]];
            replyKeyboard($chatId, $wText, $kb);
        }
        break;

    // ── PRODUTOS PENDENTES ───────────────────────────────────────────
    case 'pendentes':
        $products = $pdo->query("
            SELECT p.id, p.name, p.price, p.category, u.full_name AS seller_name
            FROM products p JOIN users u ON u.id = p.user_id
            WHERE p.status = 'pending' ORDER BY p.created_at ASC LIMIT 5
        ")->fetchAll();

        if (!$products) {
            reply($chatId, "✅ <b>Nenhum produto pendente!</b>" . footer());
            break;
        }

        foreach ($products as $p) {
            $pText =
                "🛍️ <b>PRODUTO PENDENTE #" . $p['id'] . "</b>" . div() . "\n\n"
                . "📦 <b>Produto:</b>  {$p['name']}\n"
                . "💵 <b>Preço:</b>    " . formatBRL((float)$p['price']) . "\n"
                . "🏪 <b>Vendedor:</b> {$p['seller_name']}\n"
                . "🏷️ <b>Categoria:</b> {$p['category']}\n\n"
                . "<i>Use os botões para moderar:</i>";
            $kb = [[
                ['text' => '✅ Aprovar', 'callback_data' => "prod_approve_{$p['id']}"],
                ['text' => '❌ Recusar', 'callback_data' => "prod_reject_{$p['id']}"],
            ]];
            replyKeyboard($chatId, $pText, $kb);
        }
        break;

    // ── ÚLTIMOS USUÁRIOS ─────────────────────────────────────────────
    case 'usuarios':
        $users = $pdo->query("
            SELECT id, full_name, email, status, created_at
            FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 10
        ")->fetchAll();

        if (!$users) { reply($chatId, "Nenhum usuário encontrado." . footer()); break; }

        $out = "👥 <b>ÚLTIMOS CADASTROS</b>" . div() . "\n\n";
        foreach ($users as $u) {
            $st = $u['status'] === 'approved' ? '✅' : ($u['status'] === 'blocked' ? '🔴' : '⏳');
            $out .= "{$st} <b>#{$u['id']}</b> {$u['full_name']}\n"
                  . "    <code>{$u['email']}</code>\n";
        }
        $out .= footer();
        reply($chatId, $out);
        break;

    // ── APROVAR PRODUTO ──────────────────────────────────────────────
    case 'aprovarproduto':
        $id = (int)$arg;
        if (!$id) { reply($chatId, "⚠️ Informe o ID: /aprovarproduto 42"); break; }

        $stmt = $pdo->prepare("SELECT p.*, u.full_name AS seller_name, u.id AS user_id FROM products p JOIN users u ON u.id = p.user_id WHERE p.id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch();

        if (!$p) { reply($chatId, "❌ Produto #{$id} não encontrado."); break; }

        $pdo->prepare("UPDATE products SET status = 'active', updated_at = NOW() WHERE id = ?")->execute([$id]);
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')")
            ->execute([$p['user_id'], '✅ Produto Aprovado!', 'Seu produto "' . $p['name'] . '" foi aprovado!']);

        reply($chatId, "✅ <b>Produto #{$id} aprovado!</b>\n📦 {$p['name']}\n🏪 {$p['seller_name']}" . footer());
        break;

    // ── RECUSAR PRODUTO ──────────────────────────────────────────────
    case 'recusarproduto':
        $id = (int)$arg;
        if (!$id) { reply($chatId, "⚠️ Informe o ID: /recusarproduto 42"); break; }

        $stmt = $pdo->prepare("SELECT p.*, u.full_name AS seller_name, u.id AS user_id FROM products p JOIN users u ON u.id = p.user_id WHERE p.id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch();

        if (!$p) { reply($chatId, "❌ Produto #{$id} não encontrado."); break; }

        $pdo->prepare("UPDATE products SET status = 'inactive', updated_at = NOW() WHERE id = ?")->execute([$id]);
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'warning')")
            ->execute([$p['user_id'], '❌ Produto Reprovado', 'Seu produto "' . $p['name'] . '" não foi aprovado.']);

        reply($chatId, "❌ <b>Produto #{$id} recusado.</b>\n📦 {$p['name']}\n🏪 {$p['seller_name']}" . footer());
        break;

    // ── APROVAR USUÁRIO ──────────────────────────────────────────────
    case 'aprovarusuario':
        $id = (int)$arg;
        if (!$id) { reply($chatId, "⚠️ Informe o ID: /aprovarusuario 15"); break; }

        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();

        if (!$u) { reply($chatId, "❌ Usuário #{$id} não encontrado."); break; }

        $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ?")->execute([$id]);
        reply($chatId, "✅ <b>Usuário #{$id} aprovado!</b>\n👤 {$u['full_name']}\n📧 {$u['email']}" . footer());
        break;

    // ── BLOQUEAR USUÁRIO ─────────────────────────────────────────────
    case 'bloquear':
        $id = (int)$arg;
        if (!$id) { reply($chatId, "⚠️ Informe o ID: /bloquear 15"); break; }

        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND is_admin = 0");
        $stmt->execute([$id]);
        $u = $stmt->fetch();

        if (!$u) { reply($chatId, "❌ Usuário #{$id} não encontrado."); break; }

        $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = ?")->execute([$id]);
        reply($chatId, "🔴 <b>Usuário #{$id} bloqueado.</b>\n👤 {$u['full_name']}\n📧 {$u['email']}" . footer());
        break;

    // ── SALDO DO USUÁRIO ─────────────────────────────────────────────
    case 'saldo':
        $id = (int)$arg;
        if (!$id) { reply($chatId, "⚠️ Informe o ID: /saldo 15"); break; }

        $stmt = $pdo->prepare("SELECT id, full_name, email, balance, commission_rate FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $u = $stmt->fetch();

        if (!$u) { reply($chatId, "❌ Usuário #{$id} não encontrado."); break; }

        reply($chatId,
            "💰 <b>SALDO — #{$u['id']}</b>" . div() . "\n\n"
            . "👤 <b>Nome:</b>  {$u['full_name']}\n"
            . "📧 <b>Email:</b> <code>{$u['email']}</code>\n"
            . "💵 <b>Saldo:</b> " . formatBRL((float)$u['balance']) . "\n"
            . "📉 <b>Taxa:</b>  {$u['commission_rate']}%"
            . footer()
        );
        break;

    // ── RESETAR SENHA ────────────────────────────────────────────────
    case 'resetsenha':
        $id = (int)$arg;
        if (!$id) { reply($chatId, "⚠️ Informe o ID: /resetsenha 15"); break; }

        $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ? AND is_admin = 0");
        $stmt->execute([$id]);
        $u = $stmt->fetch();

        if (!$u) { reply($chatId, "❌ Usuário #{$id} não encontrado."); break; }

        $newPass  = substr(bin2hex(random_bytes(4)), 0, 8);
        $hashed   = password_hash($newPass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $id]);

        reply($chatId,
            "🔑 <b>Senha resetada — #{$id}</b>" . div() . "\n\n"
            . "👤 <b>Nome:</b>  {$u['full_name']}\n"
            . "🔑 <b>Nova senha:</b> <code>{$newPass}</code>\n\n"
            . "<i>Envie ao usuário com segurança.</i>"
            . footer()
        );
        break;

    default:
        // Ignorar silenciosamente mensagens não reconhecidas
        break;
}

http_response_code(200);
