<?php
require_once 'includes/db.php';
require_once 'includes/TelegramService.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Não autorizado']); exit; }

$currentUser = getUser($_SESSION['user_id']);
if (!$currentUser || !$currentUser['is_admin']) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']); exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? 'pending';
    $search = trim($_GET['search'] ?? '');
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 20;
    $offset  = ($page - 1) * $perPage;

    $where = [];
    $params = [];

    $validStatuses = ['pending', 'active', 'inactive', 'all'];
    if ($status !== 'all' && in_array($status, $validStatuses)) {
        $where[] = "p.status = ?";
        $params[] = $status;
    }

    if ($search !== '') {
        $where[] = "(p.name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
        $like = "%{$search}%";
        $params[] = $like; $params[] = $like; $params[] = $like;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    try {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN users u ON u.id = p.user_id {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT
                p.*,
                u.full_name  AS seller_name,
                u.email      AS seller_email,
                u.status     AS seller_status
            FROM products p
            JOIN users u ON u.id = p.user_id
            {$whereSQL}
            ORDER BY p.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        // Stats
        $stats = $pdo->query("
            SELECT
                COUNT(CASE WHEN status = 'pending'  THEN 1 END) AS pending,
                COUNT(CASE WHEN status = 'active'   THEN 1 END) AS active,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) AS inactive,
                COUNT(*) AS total
            FROM products
        ")->fetch();

        echo json_encode([
            'success'  => true,
            'products' => $products,
            'total'    => $total,
            'stats'    => $stats,
            'page'     => $page,
            'per_page' => $perPage,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

} elseif ($method === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $id     = (int)($input['id'] ?? 0);

    if (!$id) { echo json_encode(['success' => false, 'error' => 'ID inválido']); exit; }

    try {
        switch ($action) {
            case 'approve':
                // Aprova o produto E coloca na vitrine
                $pdo->prepare("UPDATE products SET status = 'active', vitrine = 1, updated_at = NOW() WHERE id = ?")
                    ->execute([$id]);

                // Notificar o vendedor (in-app)
                $product = $pdo->prepare("SELECT p.user_id, p.name, p.price, u.full_name AS seller_name FROM products p JOIN users u ON u.id = p.user_id WHERE p.id = ?");
                $product->execute([$id]);
                $prod = $product->fetch();
                if ($prod) {
                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'success')")
                        ->execute([$prod['user_id'], '✅ Produto Aprovado!', 'Seu produto "' . $prod['name'] . '" foi aprovado e agora aparece na vitrine.']);
                    try { TelegramService::notifyProductStatus($id, $prod['name'], $prod['seller_name'], 'active'); } catch (Throwable $e) {}
                }

                echo json_encode(['success' => true, 'message' => 'Produto aprovado e adicionado à vitrine.']);
                break;

            case 'reject':
                // Remove da vitrine mas mantém o produto ativo para o vendedor usar em checkouts próprios
                $reason = trim($input['reason'] ?? '');
                $sendChat = (bool)($input['send_chat'] ?? true);
                $pdo->prepare("UPDATE products SET vitrine = 0, updated_at = NOW() WHERE id = ?")
                    ->execute([$id]);

                // Notificar o vendedor (in-app)
                $product = $pdo->prepare("SELECT p.user_id, p.name, u.full_name AS seller_name, p.status FROM products p JOIN users u ON u.id = p.user_id WHERE p.id = ?");
                $product->execute([$id]);
                $prod = $product->fetch();
                if ($prod) {
                    // Se estava pending (nunca aprovado), mantemos inativo. Se já estava active, removemos só da vitrine
                    if ($prod['status'] === 'pending') {
                        $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?")->execute([$id]);
                        $notifMsg = 'Seu produto "' . $prod['name'] . '" não foi aprovado para a vitrine.';
                        if ($reason) $notifMsg .= ' Motivo: ' . $reason;
                        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'warning')")
                            ->execute([$prod['user_id'], '❌ Produto Reprovado', $notifMsg]);
                        try { TelegramService::notifyProductStatus($id, $prod['name'], $prod['seller_name'], 'inactive', $reason); } catch (Throwable $e) {}
                    } else {
                        // Já estava aprovado antes - só removemos da vitrine
                        $notifMsg = 'Seu produto "' . $prod['name'] . '" foi removido da vitrine pública.';
                        if ($reason) $notifMsg .= ' Motivo: ' . $reason;
                        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'warning')")
                            ->execute([$prod['user_id'], '📤 Removido da Vitrine', $notifMsg . ' O produto continua ativo para seus checkouts.']);
                    }

                    // Enviar mensagem no chat para o vendedor
                    if ($sendChat && $reason) {
                        try {
                            // Procurar sala de moderação existente para este vendedor
                            $roomCheck = $pdo->prepare("SELECT id FROM chat_rooms WHERE seller_id = ? AND buyer_name = 'Moderação da Plataforma' AND status = 'open' LIMIT 1");
                            $roomCheck->execute([$prod['user_id']]);
                            $roomId = $roomCheck->fetchColumn();

                            if (!$roomId) {
                                // Criar sala de moderação
                                $token = bin2hex(random_bytes(16));
                                $pdo->prepare("INSERT INTO chat_rooms (seller_id, product_id, buyer_name, buyer_email, chat_token, status) VALUES (?, ?, 'Moderação da Plataforma', 'admin@plataforma', ?, 'open')")
                                    ->execute([$prod['user_id'], $id, $token]);
                                $roomId = (int)$pdo->lastInsertId();
                            }

                            // Compor mensagem com contexto do produto
                            $isPending = $prod['status'] === 'pending';
                            $chatMsg = ($isPending ? "❌ *Produto Reprovado*" : "📤 *Produto Removido da Vitrine*")
                                     . "\n\n📦 Produto: \"{$prod['name']}\""
                                     . "\n\n💬 Mensagem do Admin:\n{$reason}"
                                     . ($isPending ? "\n\n✏️ Corrija o que foi pedido e envie novamente para aprovação." : "");

                            $pdo->prepare("INSERT INTO chat_messages (room_id, sender_type, sender_name, message) VALUES (?, 'admin', 'Dono da Plataforma', ?)")
                                ->execute([$roomId, $chatMsg]);
                            $pdo->prepare("UPDATE chat_rooms SET last_message_at = NOW(), product_id = ? WHERE id = ?")->execute([$id, $roomId]);
                        } catch (Throwable $e) {}
                    }
                }

                echo json_encode(['success' => true, 'message' => 'Produto removido da vitrine.']);
                break;

            case 'delete':
                $pdo->prepare("DELETE FROM products WHERE id = ?")
                    ->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Produto apagado.']);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Ação inválida.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
}
