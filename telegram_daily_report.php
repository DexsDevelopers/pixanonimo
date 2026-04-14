<?php
/**
 * telegram_daily_report.php — Relatório diário automático via Telegram
 *
 * Configurar como cron job no servidor para rodar às 23:55 diariamente:
 *   55 23 * * * curl -s "https://pixghost.site/telegram_daily_report.php?secret=SEU_SECRET" > /dev/null
 *
 * Ou usar um serviço como cron-job.org para chamar a URL diariamente.
 *
 * Também pode ser chamado manualmente via /resumodia no bot Telegram.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/TelegramService.php';

// Autenticação: aceita secret via GET ou flag interna
$expectedSecret = defined('TELEGRAM_WEBHOOK_SECRET') ? TELEGRAM_WEBHOOK_SECRET : '';
$incomingSecret = $_GET['secret'] ?? '';
$isInternal     = defined('DAILY_REPORT_INTERNAL') && DAILY_REPORT_INTERNAL === true;

if (!$isInternal && $expectedSecret && $incomingSecret !== $expectedSecret) {
    http_response_code(403);
    exit('Forbidden');
}

$targetDate = $_GET['date'] ?? date('Y-m-d');

// ── Buscar estatísticas do dia ──────────────────────────────────────────────

// Visitas ao site
$pvStmt = $pdo->prepare("SELECT stat_value FROM daily_stats WHERE stat_date = ? AND stat_key = 'page_views'");
$pvStmt->execute([$targetDate]);
$pageViews = (int)($pvStmt->fetchColumn() ?: 0);

$uvStmt = $pdo->prepare("SELECT stat_value FROM daily_stats WHERE stat_date = ? AND stat_key = 'unique_visitors'");
$uvStmt->execute([$targetDate]);
$uniqueVisitors = (int)($uvStmt->fetchColumn() ?: 0);

// Novos cadastros
$newUsersStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND DATE(created_at) = ?");
$newUsersStmt->execute([$targetDate]);
$newUsers = (int)$newUsersStmt->fetchColumn();

// Vendas confirmadas
$salesStmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS count,
        COALESCE(SUM(amount_brl), 0) AS volume,
        COALESCE(SUM(amount_net_brl), 0) AS net_volume,
        COALESCE(SUM(amount_brl - amount_net_brl), 0) AS fees
    FROM transactions 
    WHERE status = 'paid' AND DATE(created_at) = ?
");
$salesStmt->execute([$targetDate]);
$sales = $salesStmt->fetch();

// PIX gerados (cobranças)
$chargesStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = ?");
$chargesStmt->execute([$targetDate]);
$totalCharges = (int)$chargesStmt->fetchColumn();

// PIX expirados
$expiredStmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE status = 'expired' AND DATE(created_at) = ?");
$expiredStmt->execute([$targetDate]);
$expiredPix = (int)$expiredStmt->fetchColumn();

// Conversão
$convRate = $totalCharges > 0 ? round(((int)$sales['count'] / $totalCharges) * 100, 1) : 0;

// Produtos criados
$productsStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE DATE(created_at) = ?");
$productsStmt->execute([$targetDate]);
$newProducts = (int)$productsStmt->fetchColumn();

// Pedidos de produtos (loja)
$ordersStmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(amount), 0) FROM orders WHERE status = 'paid' AND DATE(created_at) = ?");
$ordersStmt->execute([$targetDate]);
$ordersRow = $ordersStmt->fetch(PDO::FETCH_NUM);
$paidOrders = (int)$ordersRow[0];
$ordersVolume = (float)$ordersRow[1];

// Saques
$wdStmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending,
        COUNT(CASE WHEN status = 'paid' AND DATE(created_at) = ? THEN 1 END) AS paid_today,
        COALESCE(SUM(CASE WHEN status = 'paid' AND DATE(created_at) = ? THEN amount END), 0) AS paid_volume
    FROM withdrawals
");
$wdStmt->execute([$targetDate, $targetDate]);
$wd = $wdStmt->fetch();

// Top vendedor do dia
$topSellerStmt = $pdo->prepare("
    SELECT u.full_name, COUNT(t.id) AS sales, SUM(t.amount_brl) AS volume
    FROM transactions t JOIN users u ON u.id = t.user_id
    WHERE t.status = 'paid' AND DATE(t.created_at) = ? AND u.is_admin = 0
    GROUP BY t.user_id ORDER BY volume DESC LIMIT 1
");
$topSellerStmt->execute([$targetDate]);
$topSeller = $topSellerStmt->fetch();

// Total de usuários ativos na plataforma
$totalUsersStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0 AND status = 'approved'");
$totalUsers = (int)$totalUsersStmt->fetchColumn();

// ── Montar mensagem ─────────────────────────────────────────────────────────

$dateLabel = date('d/m/Y', strtotime($targetDate));
$dayName   = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][(int)date('w', strtotime($targetDate))];

function fBRL(float $v): string { return 'R$ ' . number_format($v, 2, ',', '.'); }
$div = "\n━━━━━━━━━━━━━━━━━━━━";

$salesCount = (int)$sales['count'];
$salesEmoji = $salesCount > 10 ? '🔥' : ($salesCount > 0 ? '✅' : '😴');

$msg = "📋 <b>RELATÓRIO DO DIA</b>{$div}\n"
     . "📅 {$dayName}, {$dateLabel}\n\n"

     . "<b>🌐 TRÁFEGO</b>\n"
     . "   👀 Page views: <b>{$pageViews}</b>\n"
     . "   👥 Visitantes únicos: <b>{$uniqueVisitors}</b>\n"
     . "   🆕 Novos cadastros: <b>{$newUsers}</b>\n"
     . "   📊 Total de usuários ativos: {$totalUsers}\n\n"

     . "<b>💰 FINANCEIRO</b> {$salesEmoji}\n"
     . "   ⚡ PIX gerados: {$totalCharges}\n"
     . "   ✅ Vendas confirmadas: <b>{$salesCount}</b>\n"
     . "   ❌ PIX expirados: {$expiredPix}\n"
     . "   📈 Taxa de conversão: <b>{$convRate}%</b>\n"
     . "   💵 Volume bruto: <b>" . fBRL((float)$sales['volume']) . "</b>\n"
     . "   💎 Volume líquido: " . fBRL((float)$sales['net_volume']) . "\n"
     . "   📉 Taxas arrecadadas: " . fBRL((float)$sales['fees']) . "\n\n"

     . "<b>🛍 LOJA / PRODUTOS</b>\n"
     . "   📦 Novos produtos: {$newProducts}\n"
     . "   🛒 Pedidos pagos: {$paidOrders}" . ($ordersVolume > 0 ? " — " . fBRL($ordersVolume) : "") . "\n\n"

     . "<b>🏦 SAQUES</b>\n"
     . "   ⏳ Pendentes agora: {$wd['pending']}\n"
     . "   ✅ Pagos hoje: {$wd['paid_today']}" . ((float)$wd['paid_volume'] > 0 ? " — " . fBRL((float)$wd['paid_volume']) : "") . "\n\n";

if ($topSeller) {
    $msg .= "<b>🏆 TOP VENDEDOR DO DIA</b>\n"
          . "   👤 {$topSeller['full_name']}\n"
          . "   {$topSeller['sales']} vendas — " . fBRL((float)$topSeller['volume']) . "\n\n";
}

// Resumo rápido
if ($salesCount === 0 && $newUsers === 0) {
    $msg .= "💤 <i>Dia tranquilo. Amanhã vai ser melhor!</i>\n";
} elseif ($salesCount >= 10) {
    $msg .= "🔥 <i>Dia excelente! Plataforma bombando!</i>\n";
} elseif ($salesCount > 0) {
    $msg .= "💪 <i>Dia produtivo. Seguimos crescendo!</i>\n";
}

$msg .= "{$div}\n🤖 <i>Ghost Pix • Relatório automático • " . date('H:i') . "</i>";

// Enviar
$sent = TelegramService::send($msg);

// Resposta HTTP
if (!$isInternal) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $sent,
        'date'    => $targetDate,
        'stats'   => [
            'page_views'      => $pageViews,
            'unique_visitors' => $uniqueVisitors,
            'new_users'       => $newUsers,
            'sales'           => $salesCount,
            'volume'          => (float)$sales['volume'],
        ]
    ]);
}
