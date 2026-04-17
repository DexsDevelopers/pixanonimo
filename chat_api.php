<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ═══════════════════════════════════════════════════════════════════
// PUBLIC ROUTES (buyer via chat_token)
// ═══════════════════════════════════════════════════════════════════
if ($action === 'buyer_get') {
    $token = trim($_GET['token'] ?? '');
    if (!$token) { echo json_encode(['success' => false, 'error' => 'Token inválido']); exit; }

    $stmt = $pdo->prepare("
        SELECT cr.*, p.name AS product_name, p.image_url AS product_image,
               u.full_name AS seller_name,
               COALESCE(ss.store_name, u.full_name) AS store_name
        FROM chat_rooms cr
        JOIN users u ON u.id = cr.seller_id
        LEFT JOIN products p ON p.id = cr.product_id
        LEFT JOIN store_settings ss ON ss.user_id = cr.seller_id
        WHERE cr.chat_token = ?
    ");
    $stmt->execute([$token]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) { echo json_encode(['success' => false, 'error' => 'Chat não encontrado']); exit; }

    // Get messages
    $after = (int)($_GET['after'] ?? 0);
    if ($after > 0) {
        $msgStmt = $pdo->prepare("SELECT id, sender_type, sender_name, message, created_at FROM chat_messages WHERE room_id = ? AND id > ? ORDER BY created_at ASC");
        $msgStmt->execute([$room['id'], $after]);
    } else {
        $msgStmt = $pdo->prepare("SELECT id, sender_type, sender_name, message, created_at FROM chat_messages WHERE room_id = ? ORDER BY created_at ASC");
        $msgStmt->execute([$room['id']]);
    }

    // Mark seller/admin messages as read
    $pdo->prepare("UPDATE chat_messages SET read_at = NOW() WHERE room_id = ? AND sender_type IN ('seller','admin') AND read_at IS NULL")
        ->execute([$room['id']]);

    echo json_encode([
        'success'  => true,
        'room'     => [
            'id'           => (int)$room['id'],
            'status'       => $room['status'],
            'buyer_name'   => $room['buyer_name'],
            'product_name' => $room['product_name'],
            'product_image'=> $room['product_image'],
            'seller_name'  => $room['seller_name'],
            'store_name'   => $room['store_name'],
            'created_at'   => $room['created_at'],
        ],
        'messages' => $msgStmt->fetchAll(PDO::FETCH_ASSOC),
    ]);
    exit;
}

if ($action === 'buyer_send') {
    $input = json_decode(file_get_contents('php://input'), true);
    $token   = trim($input['token'] ?? '');
    $message = trim($input['message'] ?? '');

    if (!$token || !$message) { echo json_encode(['success' => false, 'error' => 'Dados inválidos']); exit; }

    $stmt = $pdo->prepare("SELECT id, buyer_name, status FROM chat_rooms WHERE chat_token = ?");
    $stmt->execute([$token]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) { echo json_encode(['success' => false, 'error' => 'Chat não encontrado']); exit; }
    if ($room['status'] === 'closed') { echo json_encode(['success' => false, 'error' => 'Este chat foi encerrado']); exit; }

    $pdo->prepare("INSERT INTO chat_messages (room_id, sender_type, sender_name, message) VALUES (?, 'buyer', ?, ?)")
        ->execute([$room['id'], $room['buyer_name'], mb_substr($message, 0, 2000)]);
    $pdo->prepare("UPDATE chat_rooms SET last_message_at = NOW() WHERE id = ?")->execute([$room['id']]);

    echo json_encode(['success' => true, 'message_id' => (int)$pdo->lastInsertId()]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// AUTHENTICATED ROUTES (seller / admin)
// ═══════════════════════════════════════════════════════════════════
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$userId   = (int)$_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? 'Vendedor';
$isAdm    = isAdmin();

// ── List rooms ──────────────────────────────────────────────────
if ($action === 'rooms') {
    $status = $_GET['status'] ?? 'all';

    if ($isAdm) {
        $sql = "SELECT cr.*, p.name AS product_name, u.full_name AS seller_name,
                    (SELECT COUNT(*) FROM chat_messages cm WHERE cm.room_id = cr.id AND cm.sender_type = 'buyer' AND cm.read_at IS NULL) AS unread
                FROM chat_rooms cr
                LEFT JOIN products p ON p.id = cr.product_id
                LEFT JOIN users u ON u.id = cr.seller_id";
        if ($status !== 'all') $sql .= " WHERE cr.status = ?";
        $sql .= " ORDER BY cr.last_message_at DESC, cr.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($status !== 'all' ? [$status] : []);
    } else {
        $sql = "SELECT cr.*, p.name AS product_name,
                    (SELECT COUNT(*) FROM chat_messages cm WHERE cm.room_id = cr.id AND cm.sender_type = 'buyer' AND cm.read_at IS NULL) AS unread
                FROM chat_rooms cr
                LEFT JOIN products p ON p.id = cr.product_id
                WHERE cr.seller_id = ?";
        if ($status !== 'all') $sql .= " AND cr.status = ?";
        $sql .= " ORDER BY cr.last_message_at DESC, cr.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $params = [$userId];
        if ($status !== 'all') $params[] = $status;
        $stmt->execute($params);
    }

    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalUnread = array_sum(array_column($rooms, 'unread'));

    echo json_encode(['success' => true, 'rooms' => $rooms, 'total_unread' => $totalUnread]);
    exit;
}

// ── Get messages for a room ─────────────────────────────────────
if ($action === 'messages') {
    $roomId = (int)($_GET['room_id'] ?? 0);
    if (!$roomId) { echo json_encode(['success' => false, 'error' => 'Room ID inválido']); exit; }

    // Check access
    $roomStmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE id = ?");
    $roomStmt->execute([$roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    if (!$room || (!$isAdm && $room['seller_id'] !== $userId)) {
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        exit;
    }

    $after = (int)($_GET['after'] ?? 0);
    if ($after > 0) {
        $msgStmt = $pdo->prepare("SELECT id, sender_type, sender_name, message, created_at FROM chat_messages WHERE room_id = ? AND id > ? ORDER BY created_at ASC");
        $msgStmt->execute([$roomId, $after]);
    } else {
        $msgStmt = $pdo->prepare("SELECT id, sender_type, sender_name, message, created_at FROM chat_messages WHERE room_id = ? ORDER BY created_at ASC");
        $msgStmt->execute([$roomId]);
    }

    // Mark buyer messages as read
    $pdo->prepare("UPDATE chat_messages SET read_at = NOW() WHERE room_id = ? AND sender_type = 'buyer' AND read_at IS NULL")
        ->execute([$roomId]);

    echo json_encode(['success' => true, 'messages' => $msgStmt->fetchAll(PDO::FETCH_ASSOC), 'room' => $room]);
    exit;
}

// ── Send message ────────────────────────────────────────────────
if ($action === 'send') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $roomId = (int)($input['room_id'] ?? 0);
    $msg    = trim($input['message'] ?? '');

    if (!$roomId || !$msg) { echo json_encode(['success' => false, 'error' => 'Dados inválidos']); exit; }

    $roomStmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE id = ?");
    $roomStmt->execute([$roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    if (!$room || (!$isAdm && $room['seller_id'] !== $userId)) {
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        exit;
    }

    $senderType = $isAdm ? 'admin' : 'seller';
    $senderName = $isAdm ? 'Dono da Plataforma' : $userName;

    $pdo->prepare("INSERT INTO chat_messages (room_id, sender_type, sender_name, message) VALUES (?, ?, ?, ?)")
        ->execute([$roomId, $senderType, $senderName, mb_substr($msg, 0, 2000)]);
    $pdo->prepare("UPDATE chat_rooms SET last_message_at = NOW() WHERE id = ?")->execute([$roomId]);

    echo json_encode(['success' => true, 'message_id' => (int)$pdo->lastInsertId()]);
    exit;
}

// ── Close / reopen room ─────────────────────────────────────────
if ($action === 'toggle_status') {
    $input  = json_decode(file_get_contents('php://input'), true);
    $roomId = (int)($input['room_id'] ?? 0);
    if (!$roomId) { echo json_encode(['success' => false, 'error' => 'Room ID inválido']); exit; }

    $roomStmt = $pdo->prepare("SELECT * FROM chat_rooms WHERE id = ?");
    $roomStmt->execute([$roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    if (!$room || (!$isAdm && $room['seller_id'] !== $userId)) {
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        exit;
    }

    $newStatus = $room['status'] === 'open' ? 'closed' : 'open';
    $pdo->prepare("UPDATE chat_rooms SET status = ? WHERE id = ?")->execute([$newStatus, $roomId]);

    echo json_encode(['success' => true, 'status' => $newStatus]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Ação inválida']);
