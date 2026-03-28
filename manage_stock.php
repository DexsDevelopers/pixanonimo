<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Não autorizado']); exit; }

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $productId = (int)($_GET['product_id'] ?? 0);
    if (!$productId) { echo json_encode(['success' => false, 'error' => 'product_id obrigatório']); exit; }

    // Verify ownership
    $check = $pdo->prepare("SELECT id, name, stock FROM products WHERE id = ? AND user_id = ?");
    $check->execute([$productId, $userId]);
    $product = $check->fetch();
    if (!$product) { echo json_encode(['success' => false, 'error' => 'Produto não encontrado']); exit; }

    $items = $pdo->prepare("SELECT id, content, status, order_id, used_at, created_at FROM product_stock_items WHERE product_id = ? ORDER BY status ASC, created_at ASC");
    $items->execute([$productId]);
    $list = $items->fetchAll();

    $stats = $pdo->prepare("SELECT COUNT(*) as total, SUM(status='available') as available, SUM(status='used') as used FROM product_stock_items WHERE product_id = ?");
    $stats->execute([$productId]);
    $counts = $stats->fetch();

    echo json_encode(['success' => true, 'items' => $list, 'product' => $product, 'stats' => $counts]);

} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $productId = (int)($input['product_id'] ?? 0);

    // Verify ownership
    $check = $pdo->prepare("SELECT id FROM products WHERE id = ? AND user_id = ?");
    $check->execute([$productId, $userId]);
    if (!$check->fetch()) { echo json_encode(['success' => false, 'error' => 'Produto não encontrado']); exit; }

    switch ($action) {
        case 'add':
            $content = trim($input['content'] ?? '');
            if (!$content) { echo json_encode(['success' => false, 'error' => 'Conteúdo vazio']); exit; }
            $pdo->prepare("INSERT INTO product_stock_items (product_id, content) VALUES (?, ?)")->execute([$productId, $content]);
            // Update stock count
            $pdo->prepare("UPDATE products SET stock = (SELECT COUNT(*) FROM product_stock_items WHERE product_id = ? AND status = 'available') WHERE id = ?")->execute([$productId, $productId]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'bulk_add':
            $lines = array_filter(array_map('trim', explode("\n", $input['items'] ?? '')));
            if (empty($lines)) { echo json_encode(['success' => false, 'error' => 'Nenhum item']); exit; }
            $stmt = $pdo->prepare("INSERT INTO product_stock_items (product_id, content) VALUES (?, ?)");
            $added = 0;
            foreach ($lines as $line) {
                if ($line !== '') { $stmt->execute([$productId, $line]); $added++; }
            }
            // Update stock count
            $pdo->prepare("UPDATE products SET stock = (SELECT COUNT(*) FROM product_stock_items WHERE product_id = ? AND status = 'available') WHERE id = ?")->execute([$productId, $productId]);
            echo json_encode(['success' => true, 'added' => $added]);
            break;

        case 'delete':
            $itemId = (int)($input['item_id'] ?? 0);
            $pdo->prepare("DELETE FROM product_stock_items WHERE id = ? AND product_id = ? AND status = 'available'")->execute([$itemId, $productId]);
            // Update stock count
            $pdo->prepare("UPDATE products SET stock = (SELECT COUNT(*) FROM product_stock_items WHERE product_id = ? AND status = 'available') WHERE id = ?")->execute([$productId, $productId]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
}
