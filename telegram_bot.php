<?php
/**
 * telegram_bot.php — Webhook do Bot Telegram para Ghost Pix
 *
 * Registrar: POST https://api.telegram.org/bot{TOKEN}/setWebhook
 *   {"url": "https://pixghost.site/telegram_bot.php?secret=SEU_SECRET"}
 *
 * Comandos + Linguagem Natural suportados:
 *   /help | /start   — lista de comandos
 *   /stats           — estatísticas da plataforma
 *   /saques          — saques pendentes
 *   /pendentes       — produtos aguardando aprovação
 *   /usuarios        — últimos cadastros
 *   /pix {valor}     — gerar cobrança PIX (admin)
 *   /meurelatorio    — relatório pessoal de vendas
 *   /relatorio       — relatório geral da plataforma
 *
 *   Mensagens naturais:
 *     "quanto vendeu hoje?", "quantos saques pendentes?",
 *     "meu relatório", "gera um pix de 50", etc.
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

function getAdminUserId(): ?int {
    global $pdo;
    $stmt = $pdo->query("SELECT id FROM users WHERE is_admin = 1 ORDER BY id ASC LIMIT 1");
    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

// ── Saudações por hora ──────────────────────────────────────────────────────
function greeting(): string {
    $h = (int)date('H');
    if ($h < 12) return 'Bom dia';
    if ($h < 18) return 'Boa tarde';
    return 'Boa noite';
}

// ═══════════════════════════════════════════════════════════════════════════════
// NLP: Interpretar mensagens em linguagem natural (português)
// ═══════════════════════════════════════════════════════════════════════════════
function interpretNaturalLanguage(string $text): ?array {
    $t = mb_strtolower(trim($text));

    // Remover acentos para matching mais robusto
    $map = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c'];
    $tn = strtr($t, $map);

    // ── Gerar PIX ─────────────────────────────────────────────────────
    if (preg_match('/(?:gera|cria|faz|quero|manda|gerar|criar|fazer).*?pix.*?(\d+[.,]?\d*)/', $tn, $m)) {
        return ['action' => 'pix', 'amount' => (float)str_replace(',', '.', $m[1])];
    }
    if (preg_match('/pix\s+(?:de\s+)?(?:r\$?\s*)?(\d+[.,]?\d*)/', $tn, $m)) {
        return ['action' => 'pix', 'amount' => (float)str_replace(',', '.', $m[1])];
    }

    // ── Meu relatório / minhas vendas ─────────────────────────────────
    if (preg_match('/\b(meu|minha|minhas?)\b.*?(relatorio|venda|faturamento|receita|lucro|ganho)/', $tn, $m)) {
        return ['action' => 'meurelatorio'];
    }
    if (preg_match('/\b(quantos?|quantas?)\b.*?\b(eu|vendi|ganhei|faturei|recebi)\b/', $tn, $m)) {
        return ['action' => 'meurelatorio'];
    }

    // ── Vendas da plataforma ──────────────────────────────────────────
    if (preg_match('/\b(quantos?|quantas?)\b.*?\b(plataforma|sistema|total)\b.*?\b(vend|fatur)/', $tn, $m)) {
        return ['action' => 'stats_vendas'];
    }
    if (preg_match('/\b(quantos?|quantas?)\s+vendas?/', $tn)) {
        return ['action' => 'stats_vendas'];
    }
    if (preg_match('/\b(quantos?|quantas?)\b.*?\b(vendeu|vendemos|vendido|faturou|faturamento|vendas?)\b/', $tn)) {
        return ['action' => 'stats_vendas'];
    }

    // ── Saques ────────────────────────────────────────────────────────
    if (preg_match('/\b(quantos?|quantas?|tem|ha|existe)\b.*?\b(saque|saques)\b.*?\b(pendente|aguardando|esperando)/', $tn)) {
        return ['action' => 'stats_saques_pendentes'];
    }
    if (preg_match('/\b(saque|saques)\b.*?\b(pendente|aguardando)/', $tn)) {
        return ['action' => 'stats_saques_pendentes'];
    }
    if (preg_match('/\b(quantos?\s+saques?\s+(ja\s+)?(realizado|pago|aprovado|feito|enviado))/', $tn)) {
        return ['action' => 'stats_saques_pagos'];
    }
    if (preg_match('/\b(saques?\s+(ja\s+)?(pago|aprovado|realizado|feito|enviado))/', $tn)) {
        return ['action' => 'stats_saques_pagos'];
    }
    if (preg_match('/\b(quantos?|quanto|total)\b.*?\b(dinheiro|grana|valor)\b.*?\b(enviad|pag|transferi)/', $tn)) {
        return ['action' => 'stats_saques_pagos'];
    }
    if (preg_match('/\bsaques?\b/', $tn)) {
        return ['action' => 'saques'];
    }

    // ── Stats geral ───────────────────────────────────────────────────
    if (preg_match('/\b(como\s+ta|como\s+esta|status|situacao|resumo|overview)\b.*?\b(plataforma|sistema|hoje|geral)?\b/', $tn)) {
        return ['action' => 'stats'];
    }
    if (preg_match('/\b(relatorio|estatistica|dados|numeros)\b.*?\b(geral|plataforma|sistema)?\b/', $tn)) {
        return ['action' => 'relatorio'];
    }

    // ── Pendentes ─────────────────────────────────────────────────────
    if (preg_match('/\b(produto|produtos)\b.*?\b(pendente|aguardando|aprovacao)/', $tn)) {
        return ['action' => 'pendentes'];
    }
    if (preg_match('/\b(pendente|pendentes|aprovar)\b/', $tn)) {
        return ['action' => 'pendentes'];
    }

    // ── Saudações ─────────────────────────────────────────────────────
    if (preg_match('/^(oi|ola|eai|hey|bom dia|boa tarde|boa noite|fala|salve|hello|hi)\b/', $tn)) {
        return ['action' => 'saudacao'];
    }

    // ── Agradecimento ─────────────────────────────────────────────────
    if (preg_match('/^(obrigad|valeu|thanks|vlw|brigadao|tmj)/', $tn)) {
        return ['action' => 'agradecimento'];
    }

    return null;
}

// ═══════════════════════════════════════════════════════════════════════════════
// Handlers para respostas humanizadas
// ═══════════════════════════════════════════════════════════════════════════════

function handleStatsVendas(string $chatId): void {
    global $pdo;
    $s = $pdo->query("
        SELECT
            COUNT(CASE WHEN status='paid' THEN 1 END) AS total_paid,
            COALESCE(SUM(CASE WHEN status='paid' THEN amount_brl END), 0) AS total_volume,
            COUNT(CASE WHEN status='paid' AND DATE(created_at) = CURDATE() THEN 1 END) AS today_paid,
            COALESCE(SUM(CASE WHEN status='paid' AND DATE(created_at) = CURDATE() THEN amount_brl END), 0) AS today_volume,
            COUNT(CASE WHEN status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) AS week_paid,
            COALESCE(SUM(CASE WHEN status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN amount_brl END), 0) AS week_volume,
            COUNT(CASE WHEN status='paid' AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW()) THEN 1 END) AS month_paid,
            COALESCE(SUM(CASE WHEN status='paid' AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW()) THEN amount_brl END), 0) AS month_volume
        FROM transactions
    ")->fetch();

    $g = greeting();
    $hojeQtd   = (int)$s['today_paid'];
    $hojeLabel = $hojeQtd === 0 ? 'nenhuma venda ainda' : ($hojeQtd === 1 ? '1 venda' : "{$hojeQtd} vendas");

    reply($chatId,
        "{$g}! Aqui vai o resumo de vendas da plataforma 📊" . div() . "\n\n"
        . "📅 <b>Hoje:</b> {$hojeLabel} — " . formatBRL((float)$s['today_volume']) . "\n"
        . "📆 <b>Últimos 7 dias:</b> {$s['week_paid']} vendas — " . formatBRL((float)$s['week_volume']) . "\n"
        . "🗓 <b>Este mês:</b> {$s['month_paid']} vendas — " . formatBRL((float)$s['month_volume']) . "\n"
        . "🏆 <b>Total geral:</b> {$s['total_paid']} vendas — " . formatBRL((float)$s['total_volume']) . "\n\n"
        . ($hojeQtd > 0 ? "💪 A plataforma tá movimentando bem hoje!" : "🕐 Dia tá começando, logo as vendas aparecem!")
        . footer()
    );
}

function handleSaquesPendentes(string $chatId): void {
    global $pdo;
    $s = $pdo->query("
        SELECT
            COUNT(*) AS pending_count,
            COALESCE(SUM(amount), 0) AS pending_total
        FROM withdrawals WHERE status = 'pending'
    ")->fetch();

    $qtd = (int)$s['pending_count'];
    if ($qtd === 0) {
        reply($chatId, "✅ Nenhum saque pendente no momento! Tudo limpo por aqui 👍" . footer());
    } else {
        $pl = $qtd === 1 ? '' : 's';
        reply($chatId,
            "💸 Tem <b>{$qtd} saque{$pl} pendente{$pl}</b> aguardando aprovação" . div() . "\n\n"
            . "💵 <b>Valor total pendente:</b> " . formatBRL((float)$s['pending_total']) . "\n\n"
            . "Use /saques para ver cada um com botões de aprovar/rejeitar."
            . footer()
        );
    }
}

function handleSaquesPagos(string $chatId): void {
    global $pdo;
    $s = $pdo->query("
        SELECT
            COUNT(CASE WHEN status='paid' THEN 1 END) AS paid_count,
            COALESCE(SUM(CASE WHEN status='paid' THEN amount END), 0) AS paid_total,
            COUNT(CASE WHEN status='rejected' THEN 1 END) AS rejected_count,
            COUNT(*) AS total_all
        FROM withdrawals
    ")->fetch();

    $g = greeting();
    reply($chatId,
        "{$g}! Aqui tá o panorama de saques 🏦" . div() . "\n\n"
        . "✅ <b>Saques pagos:</b> {$s['paid_count']}\n"
        . "💸 <b>Total enviado:</b> " . formatBRL((float)$s['paid_total']) . "\n"
        . "❌ <b>Rejeitados:</b> {$s['rejected_count']}\n"
        . "📊 <b>Total de solicitações:</b> {$s['total_all']}"
        . footer()
    );
}

function handleMeuRelatorio(string $chatId): void {
    global $pdo;
    $adminId = getAdminUserId();
    if (!$adminId) { reply($chatId, "❌ Admin não encontrado no banco." . footer()); return; }

    $u = $pdo->prepare("SELECT full_name, balance, commission_rate FROM users WHERE id = ?");
    $u->execute([$adminId]);
    $admin = $u->fetch();

    $s = $pdo->prepare("
        SELECT
            COUNT(CASE WHEN status='paid' THEN 1 END) AS total_paid,
            COALESCE(SUM(CASE WHEN status='paid' THEN amount_brl END), 0) AS total_volume,
            COALESCE(SUM(CASE WHEN status='paid' THEN amount_net_brl END), 0) AS total_net,
            COUNT(CASE WHEN status='paid' AND DATE(created_at) = CURDATE() THEN 1 END) AS today_paid,
            COALESCE(SUM(CASE WHEN status='paid' AND DATE(created_at) = CURDATE() THEN amount_brl END), 0) AS today_volume,
            COALESCE(SUM(CASE WHEN status='paid' AND DATE(created_at) = CURDATE() THEN amount_net_brl END), 0) AS today_net,
            COUNT(CASE WHEN status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) AS week_paid,
            COALESCE(SUM(CASE WHEN status='paid' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN amount_brl END), 0) AS week_volume,
            COUNT(CASE WHEN status='paid' AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW()) THEN 1 END) AS month_paid,
            COALESCE(SUM(CASE WHEN status='paid' AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW()) THEN amount_brl END), 0) AS month_volume,
            COUNT(CASE WHEN status='pending' THEN 1 END) AS pending_count
        FROM transactions WHERE user_id = ?
    ");
    $s->execute([$adminId]);
    $r = $s->fetch();

    $g = greeting();
    $name = explode(' ', $admin['full_name'])[0]; // Primeiro nome

    $todayLabel = (int)$r['today_paid'] === 0 ? 'nenhuma venda' : ((int)$r['today_paid'] . ' venda' . ((int)$r['today_paid'] > 1 ? 's' : ''));

    reply($chatId,
        "{$g}, {$name}! Aqui tá o seu relatório pessoal 📋" . div() . "\n\n"
        . "💰 <b>Saldo disponível:</b> " . formatBRL((float)$admin['balance']) . "\n\n"
        . "📅 <b>Hoje:</b>\n"
        . "   {$todayLabel} — Bruto " . formatBRL((float)$r['today_volume']) . " | Líquido " . formatBRL((float)$r['today_net']) . "\n\n"
        . "📆 <b>Últimos 7 dias:</b>\n"
        . "   {$r['week_paid']} vendas — " . formatBRL((float)$r['week_volume']) . "\n\n"
        . "🗓 <b>Este mês:</b>\n"
        . "   {$r['month_paid']} vendas — " . formatBRL((float)$r['month_volume']) . "\n\n"
        . "🏆 <b>Total geral:</b>\n"
        . "   {$r['total_paid']} vendas — Bruto " . formatBRL((float)$r['total_volume']) . " | Líquido " . formatBRL((float)$r['total_net']) . "\n\n"
        . "⏳ <b>Cobranças pendentes:</b> {$r['pending_count']}\n"
        . "📉 <b>Taxa:</b> {$admin['commission_rate']}%"
        . footer()
    );
}

function handleRelatorioGeral(string $chatId): void {
    global $pdo;

    $s = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM users WHERE is_admin = 0) AS total_users,
            (SELECT COUNT(*) FROM users WHERE is_admin = 0 AND status = 'approved') AS active_users,
            (SELECT COUNT(*) FROM users WHERE is_admin = 0 AND created_at >= CURDATE()) AS new_today,
            (SELECT COUNT(*) FROM transactions WHERE status = 'paid') AS total_paid,
            (SELECT COALESCE(SUM(amount_brl),0) FROM transactions WHERE status = 'paid') AS total_volume,
            (SELECT COALESCE(SUM(amount_brl - amount_net_brl),0) FROM transactions WHERE status = 'paid') AS total_fees,
            (SELECT COALESCE(SUM(amount_brl),0) FROM transactions WHERE status = 'paid' AND DATE(created_at) = CURDATE()) AS today_volume,
            (SELECT COUNT(*) FROM transactions WHERE status = 'paid' AND DATE(created_at) = CURDATE()) AS today_paid,
            (SELECT COALESCE(SUM(amount_brl),0) FROM transactions WHERE status = 'paid' AND YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())) AS month_volume,
            (SELECT COUNT(*) FROM products WHERE status = 'active') AS active_products,
            (SELECT COUNT(*) FROM products WHERE status = 'pending') AS pending_products,
            (SELECT COUNT(*) FROM withdrawals WHERE status = 'pending') AS pending_wd,
            (SELECT COUNT(*) FROM withdrawals WHERE status = 'paid') AS paid_wd,
            (SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status = 'paid') AS paid_wd_total,
            (SELECT COUNT(*) FROM orders WHERE status = 'paid') AS paid_orders
    ")->fetch();

    $g = greeting();
    $avgTicket = (int)$s['total_paid'] > 0 ? (float)$s['total_volume'] / (int)$s['total_paid'] : 0;

    reply($chatId,
        "{$g}! Relatório completo da plataforma 📊" . div() . "\n\n"
        . "<b>👥 USUÁRIOS</b>\n"
        . "   Total: {$s['total_users']} | Ativos: {$s['active_users']} | Novos hoje: {$s['new_today']}\n\n"
        . "<b>💰 FINANCEIRO</b>\n"
        . "   Volume total: " . formatBRL((float)$s['total_volume']) . "\n"
        . "   Taxas arrecadadas: " . formatBRL((float)$s['total_fees']) . "\n"
        . "   Hoje: {$s['today_paid']} vendas — " . formatBRL((float)$s['today_volume']) . "\n"
        . "   Este mês: " . formatBRL((float)$s['month_volume']) . "\n"
        . "   Ticket médio: " . formatBRL($avgTicket) . "\n\n"
        . "<b>🛍 PRODUTOS</b>\n"
        . "   Ativos: {$s['active_products']} | Pendentes: {$s['pending_products']}\n"
        . "   Pedidos pagos: {$s['paid_orders']}\n\n"
        . "<b>🏦 SAQUES</b>\n"
        . "   Pendentes: {$s['pending_wd']}\n"
        . "   Já pagos: {$s['paid_wd']} — " . formatBRL((float)$s['paid_wd_total']) . " enviados"
        . footer()
    );
}

function handlePixGeneration(string $chatId, float $amount): void {
    global $pdo;
    $adminId = getAdminUserId();
    if (!$adminId) { reply($chatId, "❌ Admin não encontrado." . footer()); return; }

    if ($amount < 10) {
        reply($chatId, "⚠️ O valor mínimo pra gerar um PIX é <b>R$ 10,00</b>. Manda um valor maior aí!");
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$adminId]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'approved') {
        reply($chatId, "❌ Conta admin não está aprovada para gerar PIX." . footer());
        return;
    }

    $currentPixGoKey = getActivePixGoKey(true);
    $externalId = 'tg_admin_' . time();

    // Se for ambiente de teste/simulação
    if ($currentPixGoKey === 'SUA_API_KEY_AQUI' || empty($currentPixGoKey)) {
        $pixId   = 'sim_tg_' . time();
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=TESTE_PIX_' . $amount;
        $pixCode = '00020126360014br.gov.bcb.pix0114000000000000005204000053039865802BR5913GHOSTPIX6009SAOPAULO62070503***6304ABCD';

        $pixgoFee    = $amount * 0.02 + ($amount < 50 ? 1.00 : 0);
        $platformFee = $amount * ($user['commission_rate'] / 100);
        $netAmount   = $amount - $pixgoFee - $platformFee;

        saveTransaction($adminId, $amount, $netAmount, $pixId, $pixCode, $qrImage, null, 'PIX via Telegram', $externalId, 'pix');
        $txId = (int)$pdo->lastInsertId();

        reply($chatId,
            "✅ <b>PIX gerado com sucesso!</b> (simulação)" . div() . "\n\n"
            . "💵 <b>Valor:</b>     " . formatBRL($amount) . "\n"
            . "🆔 <b>TX:</b>        <code>#{$txId}</code>\n"
            . "📋 <b>Pix Code:</b>  <code>" . substr($pixCode, 0, 40) . "...</code>\n\n"
            . "⚠️ <i>Ambiente de simulação — PIX não é real.</i>"
            . footer()
        );
        return;
    }

    // Chamada real ao PixGo
    $data = [
        'amount'      => $amount,
        'description' => 'PIX via Telegram Bot',
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
        CURLOPT_TIMEOUT        => 30,
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

        saveTransaction($adminId, $amount, $netAmount, $pixId, $pixCode, $qrImage, null, 'PIX via Telegram', $externalId, 'pix');
        $txId = (int)$pdo->lastInsertId();

        $msg = "✅ <b>PIX GERADO COM SUCESSO!</b>" . div() . "\n\n"
             . "💵 <b>Valor:</b>  " . formatBRL($amount) . "\n"
             . "🆔 <b>TX:</b>     <code>#{$txId}</code>\n\n"
             . "📋 <b>Copia e Cola:</b>\n<code>{$pixCode}</code>\n\n"
             . "💡 <i>Envie o código acima para o pagador ou use o QR Code.</i>"
             . footer();

        // Enviar QR como imagem se disponível
        if ($qrImage) {
            $token  = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
            $apiCh  = curl_init("https://api.telegram.org/bot{$token}/sendPhoto");
            curl_setopt_array($apiCh, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'chat_id' => $chatId,
                    'photo'   => $qrImage,
                    'caption' => "QR Code — " . formatBRL($amount),
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT    => 10,
            ]);
            curl_exec($apiCh);
            curl_close($apiCh);
        }

        reply($chatId, $msg);
    } else {
        $errorMsg = $res['message'] ?? ($res['error'] ?? 'Erro desconhecido');
        reply($chatId,
            "❌ <b>Erro ao gerar PIX</b>" . div() . "\n\n"
            . "Motivo: <code>{$errorMsg}</code>\n"
            . "HTTP: {$httpCode}"
            . footer()
        );
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// Processar callback query (botões inline)
// ═══════════════════════════════════════════════════════════════════════════════
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

// ═══════════════════════════════════════════════════════════════════════════════
// Processar mensagens / comandos
// ═══════════════════════════════════════════════════════════════════════════════
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

// ── Primeiro: tentar comandos tradicionais ─────────────────────────────────
$handled = true;
switch ($command) {

    // ── HELP ─────────────────────────────────────────────────────────
    case 'start':
    case 'help':
        $g = greeting();
        reply($chatId,
            "🤖 <b>{$g}! Sou o Ghost Pix Bot</b>" . div() . "\n\n"
            . "Você pode usar comandos ou me mandar mensagens normais em português.\n\n"
            . "<b>📊 Relatórios:</b>\n"
            . "/stats — Estatísticas rápidas\n"
            . "/relatorio — Relatório completo\n"
            . "/meurelatorio — Suas vendas pessoais\n\n"
            . "<b>💰 Operações:</b>\n"
            . "/pix {valor} — Gerar cobrança PIX\n"
            . "/saques — Saques pendentes\n"
            . "/pendentes — Produtos p/ aprovar\n"
            . "/usuarios — Últimos cadastros\n\n"
            . "<b>⚡ Ações rápidas:</b>\n"
            . "/aprovarproduto {id}\n"
            . "/recusarproduto {id}\n"
            . "/aprovarusuario {id}\n"
            . "/bloquear {id}\n"
            . "/saldo {id}\n"
            . "/resetsenha {id}\n\n"
            . "💬 <b>Ou simplesmente pergunte:</b>\n"
            . "<i>\"quanto vendeu hoje?\"\n"
            . "\"quantos saques pendentes?\"\n"
            . "\"meu relatório\"\n"
            . "\"gera um pix de 50\"</i>"
            . footer()
        );
        break;

    // ── PIX ──────────────────────────────────────────────────────────
    case 'pix':
        $amount = (float)str_replace(',', '.', $arg);
        handlePixGeneration($chatId, $amount);
        break;

    // ── MEU RELATÓRIO ────────────────────────────────────────────────
    case 'meurelatorio':
    case 'meurelatório':
    case 'meurelat':
        handleMeuRelatorio($chatId);
        break;

    // ── RELATÓRIO GERAL ──────────────────────────────────────────────
    case 'relatorio':
    case 'relatório':
        handleRelatorioGeral($chatId);
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

        $g = greeting();
        reply($chatId,
            "{$g}! Aqui tá o resumo da plataforma 📊" . div() . "\n\n"
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
            reply($chatId, "✅ Nenhum saque pendente! Tudo tranquilo por aqui 👍" . footer());
            break;
        }

        reply($chatId, "💸 <b>" . count($withdrawals) . " saque(s) pendente(s):</b>\n<i>Use os botões abaixo pra processar cada um.</i>");
        foreach ($withdrawals as $wd) {
            $wText =
                "💸 <b>SAQUE #" . $wd['id'] . "</b>" . div() . "\n\n"
                . "👤 {$wd['full_name']}\n"
                . "💵 " . formatBRL((float)$wd['amount']) . "\n"
                . "🔑 <code>{$wd['pix_key']}</code>";
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
            reply($chatId, "✅ Nenhum produto esperando aprovação! Tudo em dia 👍" . footer());
            break;
        }

        foreach ($products as $p) {
            $pText =
                "🛍️ <b>PRODUTO #" . $p['id'] . "</b>" . div() . "\n\n"
                . "📦 {$p['name']}\n"
                . "💵 " . formatBRL((float)$p['price']) . "\n"
                . "🏪 {$p['seller_name']}\n"
                . "🏷️ {$p['category']}";
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
            $dt = date('d/m H:i', strtotime($u['created_at']));
            $out .= "{$st} <b>#{$u['id']}</b> {$u['full_name']} <i>({$dt})</i>\n"
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
        $handled = false;
        break;
}

// ── Se não foi um comando, tentar NLP ────────────────────────────────────────
if (!$handled && $text && $text[0] !== '/') {
    $intent = interpretNaturalLanguage($text);

    if ($intent) {
        switch ($intent['action']) {
            case 'pix':
                handlePixGeneration($chatId, $intent['amount']);
                break;
            case 'meurelatorio':
                handleMeuRelatorio($chatId);
                break;
            case 'relatorio':
                handleRelatorioGeral($chatId);
                break;
            case 'stats':
            case 'stats_vendas':
                handleStatsVendas($chatId);
                break;
            case 'stats_saques_pendentes':
            case 'saques':
                handleSaquesPendentes($chatId);
                break;
            case 'stats_saques_pagos':
                handleSaquesPagos($chatId);
                break;
            case 'pendentes':
                // Redirecionar para /pendentes
                $products = $pdo->query("
                    SELECT p.id, p.name, p.price, p.category, u.full_name AS seller_name
                    FROM products p JOIN users u ON u.id = p.user_id
                    WHERE p.status = 'pending' ORDER BY p.created_at ASC LIMIT 5
                ")->fetchAll();
                if (!$products) {
                    reply($chatId, "✅ Nenhum produto esperando aprovação! Tudo certo 👍" . footer());
                } else {
                    foreach ($products as $p) {
                        $pText = "🛍️ <b>PRODUTO #" . $p['id'] . "</b>" . div() . "\n\n"
                            . "📦 {$p['name']}\n💵 " . formatBRL((float)$p['price']) . "\n🏪 {$p['seller_name']}";
                        $kb = [[
                            ['text' => '✅ Aprovar', 'callback_data' => "prod_approve_{$p['id']}"],
                            ['text' => '❌ Recusar', 'callback_data' => "prod_reject_{$p['id']}"],
                        ]];
                        replyKeyboard($chatId, $pText, $kb);
                    }
                }
                break;
            case 'saudacao':
                $g = greeting();
                $adminId = getAdminUserId();
                $name = 'chefe';
                if ($adminId) {
                    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                    $stmt->execute([$adminId]);
                    $row = $stmt->fetch();
                    if ($row) $name = explode(' ', $row['full_name'])[0];
                }

                $quick = $pdo->query("
                    SELECT
                        (SELECT COUNT(*) FROM withdrawals WHERE status = 'pending') AS wd,
                        (SELECT COUNT(*) FROM products WHERE status = 'pending') AS pr,
                        (SELECT COUNT(*) FROM transactions WHERE status = 'paid' AND DATE(created_at) = CURDATE()) AS vendas_hoje
                ")->fetch();

                $alerts = [];
                if ((int)$quick['wd'] > 0)  $alerts[] = "💸 {$quick['wd']} saque(s) pendente(s)";
                if ((int)$quick['pr'] > 0)  $alerts[] = "🛍 {$quick['pr']} produto(s) p/ aprovar";
                $alertLine = $alerts ? "\n\n⚠️ <b>Atenção:</b>\n" . implode("\n", $alerts) : "\n\n✅ Nenhuma pendência no momento!";

                reply($chatId,
                    "{$g}, {$name}! 👋 Tô aqui pra ajudar.\n\n"
                    . "📈 <b>Vendas hoje:</b> {$quick['vendas_hoje']}"
                    . $alertLine . "\n\n"
                    . "Me pergunta qualquer coisa ou manda /help pra ver os comandos."
                    . footer()
                );
                break;
            case 'agradecimento':
                $resps = [
                    "Tmj! Se precisar de mais alguma coisa, é só chamar 🤝",
                    "Nada! Tô aqui pro que precisar 💪",
                    "De nada, chefe! Qualquer dúvida é só mandar 👊",
                    "Valeu! Fico à disposição 🤖",
                ];
                reply($chatId, $resps[array_rand($resps)] . footer());
                break;
        }
    } else {
        // Mensagem não reconhecida — dar dica
        reply($chatId,
            "🤔 Não entendi bem... Mas posso te ajudar com:\n\n"
            . "💬 <i>\"quanto vendeu hoje?\"</i>\n"
            . "💬 <i>\"quantos saques pendentes?\"</i>\n"
            . "💬 <i>\"meu relatório\"</i>\n"
            . "💬 <i>\"gera um pix de 50\"</i>\n\n"
            . "Ou usa /help pra ver todos os comandos."
            . footer()
        );
    }
}

http_response_code(200);
