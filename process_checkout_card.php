<?php
/**
 * process_checkout_card.php — Gera link de cartão MedusaPay para checkouts customizados
 */
require_once 'includes/db.php';
require_once 'includes/MedusaPayService.php';
require_once 'includes/TelegramService.php';
try { require_once 'includes/PushService.php'; } catch (Throwable $e) {}

header('Content-Type: application/json');

try {
    $input        = json_decode(file_get_contents('php://input'), true);
    $checkoutId   = (int)($input['checkout_id'] ?? 0);
    $customerName = trim($input['customer_name'] ?? '');
    $customerDoc  = trim($input['customer_document'] ?? '');

    if ($checkoutId <= 0) throw new Exception('Checkout inválido.');
    if (!checkRateLimit($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')) {
        throw new Exception('Limite de geração excedido. Tente novamente em 1 minuto.');
    }

    // Buscar checkout
    $stmt = $pdo->prepare("SELECT * FROM checkouts WHERE id = ? AND active = 1");
    $stmt->execute([$checkoutId]);
    $checkout = $stmt->fetch();
    if (!$checkout) throw new Exception('Checkout não encontrado ou inativo.');

    // Total
    $stmt = $pdo->prepare("SELECT SUM(price) as total FROM checkout_items WHERE checkout_id = ?");
    $stmt->execute([$checkoutId]);
    $totalAmount = (float)$stmt->fetchColumn();
    if ($totalAmount < 5) throw new Exception('Valor mínimo para cartão: R$ 5,00.');

    // Vendedor
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$checkout['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'approved') throw new Exception('Recebedor não apto para receber pagamentos.');

    // Criar transação pendente
    $externalId = 'card_chk_' . $checkoutId . '_' . time();
    $netAmount  = $totalAmount * (1 - ($user['commission_rate'] / 100));
    saveTransaction($user['id'], $totalAmount, $netAmount, $externalId, '', '', null,
        $customerName ?: 'Cliente', $externalId, 'card');
    $txId = (int)$pdo->lastInsertId();

    // Postback URL
    $postbackUrl = getFullUrl('medusa_webhook.php') . '?tx_id=' . $txId;

    // Criar checkout MedusaPay
    $result = MedusaPayService::createCardCheckout(
        $totalAmount,
        mb_substr($checkout['title'], 0, 50),
        $postbackUrl
    );

    if (!$result['ok']) throw new Exception($result['error']);

    // Salvar referência MedusaPay na transação
    if ($result['reference']) {
        $pdo->prepare("UPDATE transactions SET external_id = ?, pix_id = ? WHERE id = ?")
            ->execute([$result['reference'], $result['reference'], $txId]);
    }

    try { TelegramService::notifyNewCharge($totalAmount, $user['full_name'] ?? 'N/A', $txId); } catch (Throwable $e) {}
    if (class_exists('PushService')) {
        try { PushService::notifyAdmins('💳 Checkout Cartão #' . $txId,
            'R$ ' . number_format($totalAmount, 2, ',', '.') . ' — ' . ($user['full_name'] ?? 'N/A'), 'info');
        } catch (Throwable $e) {}
    }

    echo json_encode(['success' => true, 'checkout_url' => $result['checkout_url'], 'amount' => $totalAmount]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
