<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Não autorizado']); exit; }

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// ── GET: listar cupons do vendedor ──────────────────────────────────────────
if ($method === 'GET') {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name AS product_name
        FROM coupons c
        LEFT JOIN products p ON p.id = c.product_id
        WHERE c.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$userId]);
    $coupons = $stmt->fetchAll();

    // Stats
    $stats = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(active = 1) AS active_count,
            SUM(uses_count) AS total_uses
        FROM coupons WHERE user_id = ?
    ");
    $stats->execute([$userId]);

    echo json_encode(['success' => true, 'coupons' => $coupons, 'stats' => $stats->fetch()]);
    exit;
}

// ── POST: ações ────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    switch ($action) {

        case 'create':
        case 'update':
            $code = strtoupper(preg_replace('/[^A-Z0-9_\-]/', '', strtoupper(trim($input['code'] ?? ''))));
            if (empty($code)) {
                $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            }

            // Unicidade (exceto o próprio ao editar)
            $id = (int)($input['id'] ?? 0);
            $dupCheck = $pdo->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
            $dupCheck->execute([$code, $id]);
            if ($dupCheck->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Código já está em uso. Escolha outro.']);
                exit;
            }

            $type      = in_array($input['type'] ?? '', ['percent', 'fixed']) ? $input['type'] : 'percent';
            $value     = max(0, (float)($input['value'] ?? 0));
            if ($type === 'percent' && $value > 100) $value = 100;
            $scope     = in_array($input['scope'] ?? '', ['store', 'product']) ? $input['scope'] : 'store';
            $productId = ($scope === 'product' && !empty($input['product_id'])) ? (int)$input['product_id'] : null;
            $minAmount = max(0, (float)($input['min_amount'] ?? 0));
            $maxUses   = (isset($input['max_uses']) && $input['max_uses'] !== '' && $input['max_uses'] !== null)
                         ? max(1, (int)$input['max_uses']) : null;
            $expiresAt = !empty($input['expires_at']) ? date('Y-m-d H:i:s', strtotime($input['expires_at'])) : null;

            // Verify product belongs to seller
            if ($productId) {
                $chk = $pdo->prepare("SELECT id FROM products WHERE id = ? AND user_id = ?");
                $chk->execute([$productId, $userId]);
                if (!$chk->fetch()) { $productId = null; }
            }

            if ($action === 'create') {
                $pdo->prepare("
                    INSERT INTO coupons (user_id, code, type, value, scope, product_id, min_amount, max_uses, expires_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([$userId, $code, $type, $value, $scope, $productId, $minAmount, $maxUses, $expiresAt]);
            } else {
                $pdo->prepare("
                    UPDATE coupons SET code=?, type=?, value=?, scope=?, product_id=?, min_amount=?, max_uses=?, expires_at=?
                    WHERE id = ? AND user_id = ?
                ")->execute([$code, $type, $value, $scope, $productId, $minAmount, $maxUses, $expiresAt, $id, $userId]);
            }
            echo json_encode(['success' => true, 'code' => $code]);
            break;

        case 'toggle':
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE coupons SET active = NOT active WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM coupons WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Método não permitido']);
