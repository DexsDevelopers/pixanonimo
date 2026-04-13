<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Verificar se é conta demo
$stmtUser = $pdo->prepare("SELECT is_demo, balance, commission_rate FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$currentUser = $stmtUser->fetch();

if ($currentUser && $currentUser['is_demo'] == 1) {
    // ---- DADOS FAKE PARA CONTA DEMO ----
    $period = $_GET['p'] ?? '7d';
    $daysBack = match($period) { '30d' => 30, '90d' => 90, 'anual' => 365, default => 7 };
    
    // Seed consistente por dia + user
    srand($userId * 2000 + (int)date('Ymd') + crc32($period));
    
    $balance = (float)$currentUser['balance'];
    $commRate = (float)($currentUser['commission_rate'] ?: 4);
    
    // Gerar métricas baseadas no saldo
    $baseVolume = max($balance * 2.5, 500) * ($daysBack / 7);
    $currentVolume = round($baseVolume + rand(0, (int)($baseVolume * 0.3)), 2);
    $currentTaxes = round($currentVolume * ($commRate / 100), 2);
    $salesCount = max(5, (int)($currentVolume / rand(30, 120)));
    $totalOrders = (int)($salesCount * (100 / rand(55, 85)));
    $conversion = $totalOrders > 0 ? round(($salesCount / $totalOrders) * 100, 1) : 0;
    $avgTicket = $salesCount > 0 ? round($currentVolume / $salesCount, 2) : 0;
    
    // Variações fake
    $volumeChange = round(rand(5, 35) + (rand(0, 100) / 100), 1);
    $taxesChange = round(rand(2, 20) + (rand(0, 100) / 100), 1);
    $salesChange = round(rand(3, 25) + (rand(0, 100) / 100), 1);
    $convChange = round(rand(-5, 15) + (rand(0, 100) / 100), 1);
    
    // Gráfico diário de vendas
    $dailySalesData = [];
    $dailyConvData = [];
    $dateFormat = $daysBack <= 30 ? 'd/m' : 'm/Y';
    $pointCount = min($daysBack, $daysBack <= 30 ? $daysBack : 12);
    
    for ($i = $pointCount - 1; $i >= 0; $i--) {
        $dayOffset = $daysBack <= 30 ? $i : ($i * 30);
        $label = date($dateFormat, strtotime("-{$dayOffset} days"));
        $daySales = round($currentVolume / $pointCount * (0.5 + (rand(0, 100) / 100)), 2);
        $dayConv = round(rand(40, 85) + (rand(0, 100) / 100), 1);
        
        $dailySalesData[] = ['name' => $label, 'sales' => $daySales];
        $dailyConvData[] = ['name' => $label, 'conv' => $dayConv];
    }
    
    // Top checkouts fake
    $fakeCheckouts = [
        ['name' => 'Checkout Principal', 'value' => rand(15, 60), 'color' => '#a78bfa'],
        ['name' => 'Produto Premium', 'value' => rand(8, 30), 'color' => '#818cf8'],
        ['name' => 'Oferta Especial', 'value' => rand(3, 15), 'color' => '#c084fc'],
    ];
    
    srand(); // Restaurar seed
    
    echo json_encode([
        'success' => true,
        'metrics' => [
            'volume' => number_format($currentVolume, 2, ',', '.'),
            'volume_change' => $volumeChange,
            'taxes' => number_format($currentTaxes, 2, ',', '.'),
            'taxes_change' => $taxesChange,
            'conversion' => $conversion,
            'conversion_change' => $convChange,
            'sales_count' => $salesCount,
            'sales_change' => $salesChange,
            'total_orders' => $totalOrders,
            'avg_ticket' => number_format($avgTicket, 2, ',', '.')
        ],
        'daily_sales' => $dailySalesData,
        'daily_conv' => $dailyConvData,
        'top_checkouts' => $fakeCheckouts
    ]);
    exit;
}

// Período selecionado
$period = $_GET['p'] ?? '7d';
if (!in_array($period, ['7d', '30d', '90d', 'anual'])) $period = '7d';

$periodSQL = '';
$daysBack = 7;
switch ($period) {
    case '7d':    $periodSQL = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";  $daysBack = 7;  break;
    case '30d':   $periodSQL = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"; $daysBack = 30; break;
    case '90d':   $periodSQL = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)"; $daysBack = 90; break;
    case 'anual': $periodSQL = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)"; $daysBack = 365; break;
}

// Período anterior para calcular variação percentual
$prevPeriodSQL = '';
switch ($period) {
    case '7d':    $prevPeriodSQL = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) AND t.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)"; break;
    case '30d':   $prevPeriodSQL = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND t.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"; break;
    case '90d':   $prevPeriodSQL = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY) AND t.created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"; break;
    case 'anual': $prevPeriodSQL = " AND t.created_at >= DATE_SUB(NOW(), INTERVAL 730 DAY) AND t.created_at < DATE_SUB(NOW(), INTERVAL 365 DAY)"; break;
}

// --- 1. MÉTRICAS DO PERÍODO ATUAL ---
// Volume transacionado (pagas)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(t.amount_brl), 0) as vol FROM transactions t WHERE t.user_id = ? AND t.status = 'paid'" . $periodSQL);
$stmt->execute([$userId]);
$currentVolume = (float)$stmt->fetchColumn();

// Custo de taxas (bruto - líquido)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(t.amount_brl - t.amount_net_brl), 0) as taxas FROM transactions t WHERE t.user_id = ? AND t.status = 'paid'" . $periodSQL);
$stmt->execute([$userId]);
$currentTaxes = (float)$stmt->fetchColumn();

// Vendas realizadas (count pagas)
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM transactions t WHERE t.user_id = ? AND t.status = 'paid'" . $periodSQL);
$stmt->execute([$userId]);
$currentSalesCount = (int)$stmt->fetchColumn();

// Total de transações (para taxa de conversão)
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM transactions t WHERE t.user_id = ?" . $periodSQL);
$stmt->execute([$userId]);
$currentTotalCount = (int)$stmt->fetchColumn();

$currentConversion = $currentTotalCount > 0 ? round(($currentSalesCount / $currentTotalCount) * 100, 1) : 0;

// Ticket médio
$currentTicket = $currentSalesCount > 0 ? round($currentVolume / $currentSalesCount, 2) : 0;

// --- 2. MÉTRICAS DO PERÍODO ANTERIOR (para variação %) ---
$stmt = $pdo->prepare("SELECT COALESCE(SUM(t.amount_brl), 0) FROM transactions t WHERE t.user_id = ? AND t.status = 'paid'" . $prevPeriodSQL);
$stmt->execute([$userId]);
$prevVolume = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(t.amount_brl - t.amount_net_brl), 0) FROM transactions t WHERE t.user_id = ? AND t.status = 'paid'" . $prevPeriodSQL);
$stmt->execute([$userId]);
$prevTaxes = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t WHERE t.user_id = ? AND t.status = 'paid'" . $prevPeriodSQL);
$stmt->execute([$userId]);
$prevSalesCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t WHERE t.user_id = ?" . $prevPeriodSQL);
$stmt->execute([$userId]);
$prevTotalCount = (int)$stmt->fetchColumn();
$prevConversion = $prevTotalCount > 0 ? round(($prevSalesCount / $prevTotalCount) * 100, 1) : 0;

// Calcular variações (null = sem período anterior para comparar)
function calcChange($current, $previous) {
    if ($previous == 0) return null;
    return round((($current - $previous) / $previous) * 100, 1);
}

$volumeChange = calcChange($currentVolume, $prevVolume);
$taxesChange = calcChange($currentTaxes, $prevTaxes);
$salesChange = calcChange($currentSalesCount, $prevSalesCount);
$convChange = ($prevTotalCount == 0) ? null : round($currentConversion - $prevConversion, 1);

// --- 3. DADOS DO GRÁFICO DIÁRIO ---
$dailySalesData = [];
$dailyConvData = [];

$dateFormat = $daysBack <= 90 ? '%d/%m' : '%m/%Y';
$groupBy = $daysBack <= 90 ? 'DATE(t.created_at)' : "DATE_FORMAT(t.created_at, '%Y-%m')";

$sql = "SELECT 
            DATE_FORMAT(t.created_at, '$dateFormat') as label,
            $groupBy as grp,
            COALESCE(SUM(CASE WHEN t.status = 'paid' THEN t.amount_brl ELSE 0 END), 0) as sales,
            COUNT(CASE WHEN t.status = 'paid' THEN 1 END) as paid_count,
            COUNT(*) as total_count
        FROM transactions t 
        WHERE t.user_id = ? $periodSQL
        GROUP BY grp, label
        ORDER BY grp ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

foreach ($rows as $r) {
    $dailySalesData[] = [
        'name' => $r['label'],
        'sales' => round((float)$r['sales'], 2)
    ];
    $conv = $r['total_count'] > 0 ? round(($r['paid_count'] / $r['total_count']) * 100, 1) : 0;
    $dailyConvData[] = [
        'name' => $r['label'],
        'conv' => $conv
    ];
}

// --- 4. TOP CHECKOUTS (substitui "Mix de Produtos") ---
$topCheckouts = [];
try {
    $sql = "SELECT c.title as name, COUNT(t.id) as value
            FROM transactions t
            JOIN checkouts c ON t.external_id LIKE CONCAT('chk_', c.id, '_%')
            WHERE t.user_id = ? AND t.status = 'paid' $periodSQL
            GROUP BY c.id, c.title
            ORDER BY value DESC
            LIMIT 4";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $topCheckouts = $stmt->fetchAll();
} catch (PDOException $e) {}

$colors = ['#a78bfa', '#818cf8', '#c084fc', '#f472b6'];
$checkoutData = [];
foreach ($topCheckouts as $i => $ch) {
    $checkoutData[] = [
        'name' => $ch['name'],
        'value' => (int)$ch['value'],
        'color' => $colors[$i % count($colors)]
    ];
}

echo json_encode([
    'success' => true,
    'metrics' => [
        'volume' => number_format($currentVolume, 2, ',', '.'),
        'volume_change' => $volumeChange,
        'taxes' => number_format($currentTaxes, 2, ',', '.'),
        'taxes_change' => $taxesChange,
        'conversion' => $currentConversion,
        'conversion_change' => $convChange,
        'sales_count' => $currentSalesCount,
        'sales_change' => $salesChange,
        'total_orders' => $currentTotalCount,
        'avg_ticket' => number_format($currentTicket, 2, ',', '.')
    ],
    'daily_sales' => $dailySalesData,
    'daily_conv' => $dailyConvData,
    'top_checkouts' => $checkoutData
]);
