<?php
require_once 'includes/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$token = trim($_GET['token'] ?? '');
if (!$token) { echo json_encode(['success' => false, 'error' => 'Token inválido']); exit; }

try {
    $stmt = $pdo->prepare("
        SELECT
            o.id, o.status, o.amount, o.buyer_name, o.delivered_content,
            o.delivery_data, o.created_at,
            p.name AS product_name,
            p.description AS product_description,
            p.image_url AS product_image,
            p.delivery_method,
            p.delivery_info,
            p.type AS product_type,
            u.full_name AS seller_name,
            ss.store_name AS seller_store
        FROM orders o
        JOIN products p ON p.id = o.product_id
        JOIN users u ON u.id = o.seller_id
        LEFT JOIN store_settings ss ON ss.user_id = o.seller_id
        WHERE o.delivery_token = ?
    ");
    $stmt->execute([$token]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Pedido não encontrado']); exit;
    }

    echo json_encode([
        'success' => true,
        'order'   => $order,
        'paid'    => in_array($order['status'], ['paid', 'delivered']),
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno']);
}
