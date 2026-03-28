<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Não autorizado']); exit; }

$userId = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'products';

try {
    if ($view === 'store') {
        // Store overview: products + stats + store settings
        $products = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
        $products->execute([$userId]);
        $productList = $products->fetchAll();

        $stats = $pdo->prepare("
            SELECT
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_products,
                COUNT(*) as total_products
            FROM products WHERE user_id = ?
        ");
        $stats->execute([$userId]);
        $statsRow = $stats->fetch();

        $orderStats = $pdo->prepare("
            SELECT
                COUNT(CASE WHEN status = 'paid' OR status = 'delivered' THEN 1 END) as total_orders,
                SUM(CASE WHEN status = 'paid' OR status = 'delivered' THEN amount ELSE 0 END) as total_revenue
            FROM orders WHERE seller_id = ?
        ");
        $orderStats->execute([$userId]);
        $orderRow = $orderStats->fetch();

        $avgRating = $pdo->prepare("
            SELECT AVG(r.rating) as avg_rating
            FROM product_reviews r
            JOIN products p ON p.id = r.product_id
            WHERE p.user_id = ?
        ");
        $avgRating->execute([$userId]);
        $ratingRow = $avgRating->fetch();

        $store = $pdo->prepare("SELECT * FROM store_settings WHERE user_id = ?");
        $store->execute([$userId]);
        $storeRow = $store->fetch() ?: [];

        echo json_encode([
            'success' => true,
            'products' => $productList,
            'stats' => [
                'active_products' => $statsRow['active_products'] ?? 0,
                'total_products' => $statsRow['total_products'] ?? 0,
                'total_orders' => $orderRow['total_orders'] ?? 0,
                'total_revenue' => $orderRow['total_revenue'] ?? 0,
                'avg_rating' => $ratingRow['avg_rating'] ?? null,
            ],
            'store' => $storeRow,
        ]);
    } else {
        // Default: product list for seller
        $stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $list = $stmt->fetchAll();
        echo json_encode(['success' => true, 'products' => $list]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
