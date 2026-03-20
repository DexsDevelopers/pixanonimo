<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'save_checkout':
            $checkoutId = (int)($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', trim($_POST['slug'] ?? '')));
            $primary_color = $_POST['primary_color'] ?? '#00ff88';
            $secondary_color = $_POST['secondary_color'] ?? '#111111';
            $active = isset($_POST['active']) && $_POST['active'] == '1' ? 1 : 0;
            $checkout_banner_url = $_POST['checkout_banner_url'] ?? '';

            if (empty($title) || empty($slug)) throw new Exception("Título e Slug são obrigatórios.");

            if ($checkoutId > 0) {
                $stmt = $pdo->prepare("UPDATE checkouts SET title=?, slug=?, primary_color=?, secondary_color=?, active=?, checkout_banner_url=? WHERE id=? AND user_id=?");
                $stmt->execute([$title, $slug, $primary_color, $secondary_color, $active, $checkout_banner_url, $checkoutId, $userId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO checkouts (user_id, title, slug, primary_color, secondary_color, active, checkout_banner_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $title, $slug, $primary_color, $secondary_color, $active, $checkout_banner_url]);
                $checkoutId = $pdo->lastInsertId();
            }

            // Sync Items
            $items = json_decode($_POST['items'] ?? '[]', true);
            $pdo->prepare("DELETE FROM checkout_items WHERE checkout_id = ?")->execute([$checkoutId]);
            $stmtItem = $pdo->prepare("INSERT INTO checkout_items (checkout_id, name, price, image_url) VALUES (?, ?, ?, ?)");
            foreach ($items as $item) {
                if (!empty($item['name']) && (float)$item['price'] > 0) {
                    $stmtItem->execute([$checkoutId, $item['name'], (float)$item['price'], $item['image_url'] ?? '']);
                }
            }

            echo json_encode(['success' => true, 'id' => $checkoutId]);
            break;

        case 'delete_checkout':
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM checkout_items WHERE checkout_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM checkouts WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação desconhecida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
