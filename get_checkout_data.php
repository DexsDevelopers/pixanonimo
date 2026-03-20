<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    echo json_encode(['success' => false, 'error' => 'Slug não fornecido']);
    exit;
}

try {
    // Buscar o checkout
    $stmt = $pdo->prepare("SELECT * FROM checkouts WHERE slug = ? AND active = 1");
    $stmt->execute([$slug]);
    $checkout = $stmt->fetch();

    if (!$checkout) {
        echo json_encode(['success' => false, 'error' => 'Checkout não encontrado']);
        exit;
    }

    // Buscar os itens
    $stmt = $pdo->prepare("SELECT * FROM checkout_items WHERE checkout_id = ?");
    $stmt->execute([$checkout['id']]);
    $items = $stmt->fetchAll();

    $total = 0;
    foreach ($items as $it) {
        $total += (float)$it['price'];
    }

    echo json_encode([
        'success' => true,
        'checkout' => [
            'id' => $checkout['id'],
            'title' => $checkout['title'],
            'primary_color' => $checkout['primary_color'],
            'secondary_color' => $checkout['secondary_color'],
            'banner_url' => $checkout['checkout_banner_url'],
            'custom_html_head' => $checkout['custom_html_head'],
            'custom_html_body' => $checkout['custom_html_body'],
        ],
        'items' => $items,
        'total' => $total
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor: ' . $e->getMessage()]);
}
