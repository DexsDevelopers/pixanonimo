<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

// --- 1. DADOS DO USUÁRIO (SALDO) ---
// Auto-criar colunas de crypto se não existirem
try { $pdo->exec("ALTER TABLE users ADD COLUMN withdraw_method VARCHAR(10) DEFAULT 'pix'"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN crypto_address VARCHAR(255) DEFAULT ''"); } catch (PDOException $e) {}
try { $pdo->exec("ALTER TABLE users ADD COLUMN crypto_network VARCHAR(20) DEFAULT ''"); } catch (PDOException $e) {}

$stmt = $pdo->prepare("SELECT balance, commission_rate, pix_key, status, is_demo, is_admin, full_name, email, referral_token, withdraw_method, crypto_address, crypto_network, api_key FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'Usuário não encontrado']);
    exit;
}

// --- Período selecionado (para manter consistência no auto-refresh) ---
$period = $_GET['p'] ?? '7d';
if (!in_array($period, ['today', '7d', '30d', 'all'])) $period = '7d';

$periodSQL = '';
switch ($period) {
    case 'today': $periodSQL = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)"; break;
    case '7d':    $periodSQL = " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; break;
    case '30d':   $periodSQL = " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"; break;
    case 'all':   $periodSQL = ''; break;
}

// --- 2. ESTATÍSTICAS ---
$displayBalance = $user['balance'];

// Saldo disponível para saque = saldo total - saques pendentes ainda não aprovados
$pendingW = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
$pendingW->execute([$userId]);
$pendingWithdrawals = (float)$pendingW->fetchColumn();
$availableForWithdraw = max(0, $user['balance'] - $pendingWithdrawals);

$stats = [
    'balance_fmt' => number_format($displayBalance, 2, ',', '.'),
    'today_volume' => 0,
    'month_volume' => 0,
    'total_paid' => 0,
    'pending_count' => 0
];

if ($user['is_demo'] == 1) {
    // Lógica Demo: Receita Total = Saldo Atual + Saques Concluídos
    $stmtW = $pdo->prepare("SELECT SUM(amount) as total FROM withdrawals WHERE user_id = ? AND status = 'completed'");
    $stmtW->execute([$userId]);
    $totalWithdrawn = $stmtW->fetch()['total'] ?? 0;
    
    $totalPaidVal = $user['balance'] + $totalWithdrawn;
    // No demo, os multiplicadores são fixos por enquanto
    $stats['total_paid'] = number_format($totalPaidVal, 2, ',', '.');
    $stats['month_volume'] = number_format($totalPaidVal * 0.82, 2, ',', '.');
    $stats['today_volume'] = number_format($totalPaidVal * 0.14, 2, ',', '.');
    $stats['pending_count'] = floor($totalPaidVal / 140);
} else {
    // Volume Hoje (a partir de meia-noite no timezone do servidor)
    $todayStart = date('Y-m-d 00:00:00');
    $stmtToday = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid' AND created_at >= ?");
    $stmtToday->execute([$userId, $todayStart]);
    $stats['today_volume'] = number_format($stmtToday->fetch()['vol'] ?? 0, 2, ',', '.');

    // Volume no Período Selecionado
    $stmtMonth = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid'" . $periodSQL);
    $stmtMonth->execute([$userId]);
    $stats['month_volume'] = number_format($stmtMonth->fetch()['vol'] ?? 0, 2, ',', '.');

    // Total Acumulado no Período
    $stmtTotal = $pdo->prepare("SELECT SUM(amount_brl) as vol FROM transactions WHERE user_id = ? AND status = 'paid'" . $periodSQL);
    $stmtTotal->execute([$userId]);
    $stats['total_paid'] = number_format($stmtTotal->fetch()['vol'] ?? 0, 2, ',', '.');

    // Cobranças Pendentes (< 20 min)
    $stmtPending = $pdo->prepare("SELECT COUNT(*) as qtd FROM transactions WHERE user_id = ? AND status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 20 MINUTE)");
    $stmtPending->execute([$userId]);
    $stats['pending_count'] = $stmtPending->fetch()['qtd'] ?? 0;
}

// --- 3. ÚLTIMAS TRANSAÇÕES ---
if ($user['is_demo'] == 1) {
    // Gerar transações fake para conta demo
    $fakeNames = ['João Silva', 'Maria Oliveira', 'Pedro Santos', 'Ana Costa', 'Lucas Ferreira', 'Juliana Souza', 'Carlos Lima', 'Fernanda Rocha', 'Rafael Almeida', 'Camila Ribeiro', 'Bruno Martins', 'Patrícia Gomes', 'Diego Araújo', 'Larissa Pereira', 'Thiago Barbosa'];
    $fakeStatuses = [
        ['status' => 'Pago', 'badge' => 'approved'],
        ['status' => 'Pago', 'badge' => 'approved'],
        ['status' => 'Pago', 'badge' => 'approved'],
        ['status' => 'Pago', 'badge' => 'approved'],
        ['status' => 'Pago', 'badge' => 'approved'],
        ['status' => 'Pago', 'badge' => 'approved'],
        ['status' => 'Pendente', 'badge' => 'pending'],
        ['status' => 'Expirado', 'badge' => 'expired'],
    ];
    
    // Seed baseado no user_id para manter consistência entre reloads
    srand($userId * 1000 + (int)date('Ymd'));
    
    $formattedRows = [];
    $txCount = rand(8, 12);
    for ($i = 0; $i < $txCount; $i++) {
        $amount = rand(1000, 50000) / 100; // R$10 a R$500
        $commRate = $user['commission_rate'] ?: 4;
        $netAmount = $amount * (1 - $commRate / 100);
        $statusInfo = $fakeStatuses[array_rand($fakeStatuses)];
        $hoursAgo = $i * rand(1, 4);
        $date = date('d/m/Y H:i', strtotime("-{$hoursAgo} hours"));
        $secondsOld = $statusInfo['badge'] === 'pending' ? rand(60, 600) : rand(3600, 86400);
        
        $formattedRows[] = [
            'id' => 90000 + $i,
            'date' => $date,
            'amount_brl' => number_format($amount, 2, ',', '.'),
            'amount_net_brl' => number_format($netAmount, 2, ',', '.'),
            'status' => $statusInfo['status'],
            'badge' => $statusInfo['badge'],
            'customer_name' => $fakeNames[array_rand($fakeNames)],
            'qr_image' => '',
            'pix_code' => '',
            'seconds_old' => $secondsOld
        ];
    }
    
    // Restaurar seed aleatório
    srand();
} else {
    $stmtRows = $pdo->prepare("SELECT *, (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(created_at)) as seconds_old FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmtRows->execute([$userId]);
    $rows = $stmtRows->fetchAll();

    $formattedRows = [];
    foreach ($rows as $t) {
        // Lógica de status
        $status = $t['status'];
        $displayStatus = 'Pendente';
        $badgeClass = 'pending';

        if ($status == 'paid') {
            $displayStatus = 'Pago';
            $badgeClass = 'approved';
        } elseif ($status == 'pending') {
            if ($t['seconds_old'] > 1200) {
                $displayStatus = 'Expirado';
                $badgeClass = 'expired';
            }
        } elseif ($status == 'rejected') {
            $displayStatus = 'Rejeitado';
            $badgeClass = 'rejected';
        }

        $formattedRows[] = [
            'id' => $t['id'],
            'date' => date('d/m/Y H:i', strtotime($t['created_at'])),
            'amount_brl' => number_format($t['amount_brl'], 2, ',', '.'),
            'amount_net_brl' => number_format($t['amount_net_brl'], 2, ',', '.'),
            'status' => $displayStatus,
            'badge' => $badgeClass,
            'customer_name' => $t['customer_name'] ?? 'Sem nome',
            'qr_image' => $t['qr_image'] ?? '',
            'pix_code' => $t['pix_code'] ?? '',
            'seconds_old' => (int)$t['seconds_old']
        ];
    }
}

// --- 4. NOTIFICAÇÕES TIPO DASHBOARD ---
$stmtNotif = $pdo->prepare("SELECT id, title, message, type, is_read, created_at FROM notifications WHERE (user_id = ? OR user_id IS NULL) ORDER BY created_at DESC LIMIT 20");
$stmtNotif->execute([$userId]);
$notifs = $stmtNotif->fetchAll();

$formattedNotifs = [];
foreach ($notifs as $n) {
    $formattedNotifs[] = [
        'id' => (int)$n['id'],
        'title' => $n['title'],
        'message' => $n['message'],
        'type' => $n['type'],
        'is_read' => (bool)$n['is_read'],
        'time' => date('d/m H:i', strtotime($n['created_at']))
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'balance' => $stats['balance_fmt'],
    'available_for_withdraw' => number_format($availableForWithdraw, 2, ',', '.'),
    'pending_withdrawals' => number_format($pendingWithdrawals, 2, ',', '.'),
    'stats' => $stats,
    'user' => [
        'name' => $user['full_name'] ?? 'Usuário',
        'email' => $user['email'] ?? '',
        'pix_key' => $user['pix_key'] ?? '',
        'withdraw_method' => $user['withdraw_method'] ?? 'pix',
        'crypto_address' => $user['crypto_address'] ?? '',
        'crypto_network' => $user['crypto_network'] ?? '',
        'api_token' => $user['api_key'] ?? '',
        'is_admin' => (bool)$user['is_admin'],
        'avatar_url' => (function() use ($userId) {
            $dir = __DIR__ . '/uploads/avatars/';
            foreach (['jpg','png','webp','gif'] as $ext) {
                if (file_exists($dir . $userId . '.' . $ext)) {
                    return '/uploads/avatars/' . $userId . '.' . $ext . '?v=' . filemtime($dir . $userId . '.' . $ext);
                }
            }
            return null;
        })()
    ],
    'transactions' => $formattedRows,
    'notifications' => $formattedNotifs
]);

