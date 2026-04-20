<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Faça login para avaliar']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$productId = (int)($data['product_id'] ?? 0);
$rating = (int)($data['rating'] ?? 0);
$comment = trim($data['comment'] ?? '');

if (!$productId || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

if (strlen($comment) > 500) {
    echo json_encode(['success' => false, 'error' => 'Comentário muito longo (max 500 caracteres)']);
    exit;
}

try {
    // Check product exists
    $prodStmt = $pdo->prepare("SELECT id, user_id FROM products WHERE id = ? AND vitrine = 1 AND status = 'active'");
    $prodStmt->execute([$productId]);
    $product = $prodStmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'error' => 'Produto não encontrado']);
        exit;
    }

    // Can't review own product
    if ($product['user_id'] == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'Você não pode avaliar seu próprio produto']);
        exit;
    }

    // Check if already reviewed
    $checkStmt = $pdo->prepare("SELECT id FROM product_reviews WHERE product_id = ? AND user_id = ?");
    $checkStmt->execute([$productId, $_SESSION['user_id']]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Você já avaliou este produto']);
        exit;
    }

    // Get user name
    $userStmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $userName = $userStmt->fetchColumn() ?: 'Anônimo';

    // Insert review
    $insertStmt = $pdo->prepare("
        INSERT INTO product_reviews (product_id, user_id, buyer_name, rating, comment, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insertStmt->execute([$productId, $_SESSION['user_id'], $userName, $rating, $comment]);

    // Update product avg_rating and review_count
    $avgStmt = $pdo->prepare("
        UPDATE products SET
            review_count = (SELECT COUNT(*) FROM product_reviews WHERE product_id = ?),
            avg_rating = (SELECT COALESCE(AVG(rating), 0) FROM product_reviews WHERE product_id = ?)
        WHERE id = ?
    ");
    $avgStmt->execute([$productId, $productId, $productId]);

    // Fetch updated stats
    $statsStmt = $pdo->prepare("SELECT avg_rating, review_count FROM products WHERE id = ?");
    $statsStmt->execute([$productId]);
    $stats = $statsStmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => 'Avaliação enviada com sucesso!',
        'review' => [
            'id' => $pdo->lastInsertId(),
            'rating' => $rating,
            'comment' => $comment,
            'buyer_name' => $userName,
            'user_name' => $userName,
            'created_at' => date('Y-m-d H:i:s'),
        ],
        'avg_rating' => (float)$stats['avg_rating'],
        'review_count' => (int)$stats['review_count'],
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno']);
}
