<?php
require_once 'includes/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort     = $_GET['sort'] ?? 'recent';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 12;
$offset   = ($page - 1) * $perPage;

$orderBy = match($sort) {
    'popular'    => 'p.orders_count DESC',
    'rating'     => 'p.avg_rating DESC, p.review_count DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    default      => 'p.created_at DESC',
};

$where = ["p.vitrine = 1", "p.status = 'active'"];
$params = [];

if ($search !== '') {
    $where[] = "(p.name LIKE ? OR p.description LIKE ? OR u.full_name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($category !== '') {
    $where[] = "p.category = ?";
    $params[] = $category;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN users u ON u.id = p.user_id {$whereSQL}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT
            p.id, p.name, p.description, p.price, p.image_url,
            p.category, p.type, p.delivery_info,
            p.orders_count, p.avg_rating, p.review_count, p.created_at,
            u.full_name AS seller_name,
            ss.store_name AS seller_store
        FROM products p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN store_settings ss ON ss.user_id = p.user_id
        {$whereSQL}
        ORDER BY {$orderBy}
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    echo json_encode([
        'success'  => true,
        'products' => $products,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
