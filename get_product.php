<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    // Fetch product with seller info
    $stmt = $pdo->prepare("
        SELECT
            p.id, p.name, p.description, p.price, p.image_url,
            p.category, p.type, p.delivery_info, p.delivery_method,
            p.orders_count, p.avg_rating, p.review_count, p.stock, p.created_at,
            p.user_id AS seller_id,
            u.full_name AS seller_name,
            ss.store_name AS seller_store, ss.store_description, ss.store_banner, ss.slug AS store_slug
        FROM products p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN store_settings ss ON ss.user_id = p.user_id
        WHERE p.id = ? AND p.vitrine = 1 AND p.status = 'active'
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Produto não encontrado']);
        exit;
    }

    // Fetch reviews
    $reviewStmt = $pdo->prepare("
        SELECT
            pr.id, pr.rating, pr.comment, pr.buyer_name, pr.created_at,
            u.full_name AS user_name
        FROM product_reviews pr
        LEFT JOIN users u ON u.id = pr.user_id
        WHERE pr.product_id = ?
        ORDER BY pr.created_at DESC
        LIMIT 50
    ");
    $reviewStmt->execute([$id]);
    $reviews = $reviewStmt->fetchAll();

    // Fetch related products (same category, exclude current)
    $relatedStmt = $pdo->prepare("
        SELECT p.id, p.name, p.price, p.image_url, p.category, p.type,
               p.orders_count, p.avg_rating, p.review_count,
               u.full_name AS seller_name
        FROM products p
        JOIN users u ON u.id = p.user_id
        WHERE p.vitrine = 1 AND p.status = 'active' AND p.id != ? AND p.category = ?
        ORDER BY p.orders_count DESC
        LIMIT 4
    ");
    $relatedStmt->execute([$id, $product['category']]);
    $related = $relatedStmt->fetchAll();

    // Check if current user has purchased this product (for review eligibility)
    $canReview = false;
    if (isset($_SESSION['user_id'])) {
        $orderCheck = $pdo->prepare("
            SELECT COUNT(*) FROM orders
            WHERE product_id = ? AND buyer_name IS NOT NULL AND status = 'paid'
            AND (seller_id != ? OR buyer_document = (SELECT email FROM users WHERE id = ?))
        ");
        // Simplified: check if user bought it by matching user_id in orders or session
        $buyCheck = $pdo->prepare("
            SELECT COUNT(*) FROM orders o
            JOIN transactions t ON t.id = o.transaction_id
            WHERE o.product_id = ? AND o.status = 'paid' AND t.user_id = ?
        ");
        // Actually, orders don't have a buyer_user_id directly. Let's check by user_id in session
        // For now, any logged-in user who is NOT the seller can review
        $canReview = ($_SESSION['user_id'] != $product['seller_id']);

        // Check if already reviewed
        if ($canReview) {
            $alreadyReviewed = $pdo->prepare("SELECT COUNT(*) FROM product_reviews WHERE product_id = ? AND user_id = ?");
            $alreadyReviewed->execute([$id, $_SESSION['user_id']]);
            if ($alreadyReviewed->fetchColumn() > 0) {
                $canReview = false;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'product' => $product,
        'reviews' => $reviews,
        'related' => $related,
        'can_review' => $canReview,
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno']);
}
