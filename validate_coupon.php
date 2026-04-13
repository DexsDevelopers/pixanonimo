<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

$input     = json_decode(file_get_contents('php://input'), true) ?? [];
$code      = strtoupper(trim($input['code'] ?? $_GET['code'] ?? ''));
$productId = (int)($input['product_id'] ?? $_GET['product_id'] ?? 0);
$amount    = (float)($input['amount'] ?? $_GET['amount'] ?? 0);

if (!$code || !$productId || $amount <= 0) {
    echo json_encode(['valid' => false, 'error' => 'Dados incompletos']);
    exit;
}

// Buscar produto e vendedor
$pStmt = $pdo->prepare("SELECT user_id FROM products WHERE id = ? AND status = 'active'");
$pStmt->execute([$productId]);
$prod = $pStmt->fetch();
if (!$prod) { echo json_encode(['valid' => false, 'error' => 'Produto não encontrado']); exit; }
$sellerId = $prod['user_id'];

// Buscar cupom
$cStmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND active = 1");
$cStmt->execute([$code]);
$coupon = $cStmt->fetch();

if (!$coupon) {
    echo json_encode(['valid' => false, 'error' => 'Cupom inválido ou inativo']);
    exit;
}

// Validar pertencimento ao vendedor
if ((int)$coupon['user_id'] !== (int)$sellerId) {
    echo json_encode(['valid' => false, 'error' => 'Cupom não válido para este produto']);
    exit;
}

// Validar escopo
if ($coupon['scope'] === 'product' && (int)$coupon['product_id'] !== $productId) {
    echo json_encode(['valid' => false, 'error' => 'Cupom não válido para este produto']);
    exit;
}

// Validar expiração
if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
    echo json_encode(['valid' => false, 'error' => 'Cupom expirado']);
    exit;
}

// Validar usos
if ($coupon['max_uses'] !== null && (int)$coupon['uses_count'] >= (int)$coupon['max_uses']) {
    echo json_encode(['valid' => false, 'error' => 'Cupom esgotado']);
    exit;
}

// Validar valor mínimo
if ($amount < (float)$coupon['min_amount']) {
    echo json_encode([
        'valid' => false,
        'error' => 'Valor mínimo para este cupom: R$ ' . number_format($coupon['min_amount'], 2, ',', '.')
    ]);
    exit;
}

// Calcular desconto
$discount = $coupon['type'] === 'percent'
    ? round($amount * ((float)$coupon['value'] / 100), 2)
    : min((float)$coupon['value'], $amount);

$finalAmount = max(10, $amount - $discount);
$label = $coupon['type'] === 'percent'
    ? '-' . rtrim(rtrim(number_format($coupon['value'], 2), '0'), '.') . '%'
    : '-R$ ' . number_format($coupon['value'], 2, ',', '.');

echo json_encode([
    'valid'           => true,
    'coupon_id'       => $coupon['id'],
    'code'            => $coupon['code'],
    'label'           => $label,
    'discount_amount' => $discount,
    'final_amount'    => $finalAmount,
    'scope'           => $coupon['scope'],
]);
