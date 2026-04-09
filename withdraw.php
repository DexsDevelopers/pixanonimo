<?php
require_once 'includes/db.php';
try {
    require_once 'includes/PushService.php';
} catch (Throwable $e) {}
require_once 'includes/TelegramService.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

// Validação CSRF
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');
check_csrf($csrfToken);

$amount = (float)($input['amount'] ?? 0);
$withdrawFee = 3.50;

try {
    $stmt = $pdo->prepare("SELECT balance, pix_key FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro de Banco de Dados: Colunas faltando. Por favor, execute a migração acessando ' . (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/migrate_v2.php"]);
    exit;
}

if ($amount < 10) {
    echo json_encode(['error' => 'O valor mínimo para saque é R$ 10,00.']);
    exit;
}

if ($amount > $user['balance']) {
    echo json_encode(['error' => 'Saldo insuficiente. Seu saldo é R$ ' . number_format($user['balance'], 2, ',', '.') . '.']);
    exit;
}

$netAmount = $amount - $withdrawFee;

if (!$user['pix_key']) {
    echo json_encode(['error' => 'Configure sua chave PIX antes de sacar.']);
    exit;
}

try {
    // Verificar se já há saques pendentes que consumiriam o saldo
    $pendingStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id = ? AND status = 'pending'");
    $pendingStmt->execute([$userId]);
    $pendingTotal = (float)$pendingStmt->fetchColumn();
    $availableBalance = $user['balance'] - $pendingTotal;

    if ($amount > $availableBalance) {
        echo json_encode(['error' => 'Saldo insuficiente. Saldo disponível para saque: R$ ' . number_format($availableBalance, 2, ',', '.') . ' (considerando saques pendentes).']);
        exit;
    }

    $pdo->beginTransaction();

    // Registrar pedido de saque (o saldo só é debitado quando o admin aprovar)
    $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, pix_key, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$userId, $netAmount, $user['pix_key']]);

    $pdo->commit();
    write_log('INFO', 'Pedido de Saque Realizado', ['user_id' => $userId, 'amount' => $amount, 'fee' => $withdrawFee, 'net' => $netAmount]);
    // Buscar nome do usuário
    $userInfo = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $userInfo->execute([$userId]);
    $userName = $userInfo->fetchColumn() ?: "Usuário #$userId";

    // Notificar Admin (Push + In-App)
    if (class_exists('PushService')) {
        try { PushService::notifyAdmins('🏦 Saque Solicitado', $userName . ' — R$ ' . number_format($amount, 2, ',', '.') . ' — Pix: ' . $user['pix_key'], 'warning'); } catch (Throwable $e) {}
    }
    
    // Notificar Admin via Telegram
    try { TelegramService::notifyWithdrawal($userName, $amount, $user['pix_key']); } catch (Throwable $e) {}
    
    echo json_encode(['status' => 'success', 'message' => 'Solicitação de saque enviada ao administrador!']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    write_log('ERROR', 'Erro ao processar saque', ['error' => $e->getMessage(), 'user_id' => $userId]);
    echo json_encode(['error' => 'Erro ao processar saque: ' . $e->getMessage()]);
}

