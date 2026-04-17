<?php
/**
 * API de Anúncios
 * 
 * GET    ?action=active          → Lista anúncios ativos (para o user logado, exclui dispensados)
 * POST   ?action=dismiss         → Dispensar um anúncio {announcement_id}
 * 
 * Admin:
 * GET    ?action=list            → Lista todos os anúncios (admin)
 * POST   ?action=create          → Criar anúncio (multipart com file upload)
 * POST   ?action=update          → Atualizar anúncio (multipart com file upload)
 * POST   ?action=delete          → Deletar anúncio {id}
 * POST   ?action=toggle          → Ativar/desativar {id}
 */
require_once 'includes/db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ═══════════════════════════════════════════════════════════════════════════
// USER: Buscar anúncios ativos (não dispensados)
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'active' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isLoggedIn()) { echo json_encode(['error' => 'Não autorizado']); exit; }
    $userId = (int)$_SESSION['user_id'];
    $now = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        SELECT a.* FROM announcements a
        WHERE a.is_active = 1
          AND (a.starts_at IS NULL OR a.starts_at <= ?)
          AND (a.expires_at IS NULL OR a.expires_at >= ?)
          AND a.id NOT IN (SELECT announcement_id FROM announcement_dismissals WHERE user_id = ?)
        ORDER BY a.priority DESC, a.created_at DESC
    ");
    $stmt->execute([$now, $now, $userId]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['announcements' => $announcements]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// USER: Dispensar anúncio
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'dismiss' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) { echo json_encode(['error' => 'Não autorizado']); exit; }
    $userId = (int)$_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    $annId = (int)($input['announcement_id'] ?? 0);
    if (!$annId) { echo json_encode(['error' => 'ID inválido']); exit; }

    try {
        $pdo->prepare("INSERT IGNORE INTO announcement_dismissals (announcement_id, user_id) VALUES (?, ?)")
            ->execute([$annId, $userId]);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN: Verificação
// ═══════════════════════════════════════════════════════════════════════════
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN: Listar todos
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT a.*, 
            (SELECT COUNT(*) FROM announcement_dismissals WHERE announcement_id = a.id) AS dismiss_count
        FROM announcements a 
        ORDER BY a.created_at DESC
    ");
    echo json_encode(['announcements' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN: Upload helper
// ═══════════════════════════════════════════════════════════════════════════
function handleMediaUpload(): array {
    if (empty($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
        return ['url' => null, 'type' => 'none'];
    }

    $file = $_FILES['media'];
    $maxSize = 50 * 1024 * 1024; // 50MB
    if ($file['size'] > $maxSize) {
        throw new RuntimeException('Arquivo muito grande (máx 50MB)');
    }

    $mime = mime_content_type($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
        'video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov',
    ];

    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Formato não permitido. Use: jpg, png, gif, webp, mp4, webm, mov');
    }

    $ext = $allowed[$mime];
    $mediaType = str_starts_with($mime, 'image/') ? 'image' : 'video';
    $dir = __DIR__ . '/uploads/announcements';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'ann_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $path = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Falha ao salvar arquivo');
    }

    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
    return ['url' => $baseUrl . '/uploads/announcements/' . $filename, 'type' => $mediaType];
}

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN: Criar anúncio
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $media = handleMediaUpload();
        $title     = trim($_POST['title'] ?? '');
        $message   = trim($_POST['message'] ?? '');
        $linkUrl   = trim($_POST['link_url'] ?? '');
        $linkLabel = trim($_POST['link_label'] ?? 'Acessar');
        $priority  = (int)($_POST['priority'] ?? 0);
        $startsAt  = $_POST['starts_at'] ?? null;
        $expiresAt = $_POST['expires_at'] ?? null;

        if (!$title) { echo json_encode(['error' => 'Título obrigatório']); exit; }

        $stmt = $pdo->prepare("INSERT INTO announcements (title, message, media_url, media_type, link_url, link_label, priority, starts_at, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $title, $message ?: null,
            $media['url'], $media['type'],
            $linkUrl ?: null, $linkLabel,
            $priority,
            $startsAt ?: null, $expiresAt ?: null,
        ]);

        echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
    } catch (Throwable $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN: Atualizar anúncio
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'ID inválido']); exit; }

        $title     = trim($_POST['title'] ?? '');
        $message   = trim($_POST['message'] ?? '');
        $linkUrl   = trim($_POST['link_url'] ?? '');
        $linkLabel = trim($_POST['link_label'] ?? 'Acessar');
        $priority  = (int)($_POST['priority'] ?? 0);
        $startsAt  = $_POST['starts_at'] ?? null;
        $expiresAt = $_POST['expires_at'] ?? null;

        if (!$title) { echo json_encode(['error' => 'Título obrigatório']); exit; }

        // Check if new media uploaded
        $media = handleMediaUpload();
        if ($media['url']) {
            $stmt = $pdo->prepare("UPDATE announcements SET title=?, message=?, media_url=?, media_type=?, link_url=?, link_label=?, priority=?, starts_at=?, expires_at=? WHERE id=?");
            $stmt->execute([$title, $message ?: null, $media['url'], $media['type'], $linkUrl ?: null, $linkLabel, $priority, $startsAt ?: null, $expiresAt ?: null, $id]);
        } else {
            // Keep existing media
            $removeMedia = ($_POST['remove_media'] ?? '') === '1';
            if ($removeMedia) {
                $stmt = $pdo->prepare("UPDATE announcements SET title=?, message=?, media_url=NULL, media_type='none', link_url=?, link_label=?, priority=?, starts_at=?, expires_at=? WHERE id=?");
                $stmt->execute([$title, $message ?: null, $linkUrl ?: null, $linkLabel, $priority, $startsAt ?: null, $expiresAt ?: null, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE announcements SET title=?, message=?, link_url=?, link_label=?, priority=?, starts_at=?, expires_at=? WHERE id=?");
                $stmt->execute([$title, $message ?: null, $linkUrl ?: null, $linkLabel, $priority, $startsAt ?: null, $expiresAt ?: null, $id]);
            }
        }

        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN: Deletar anúncio
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'ID inválido']); exit; }

    $pdo->prepare("DELETE FROM announcement_dismissals WHERE announcement_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN: Ativar/Desativar
// ═══════════════════════════════════════════════════════════════════════════
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'ID inválido']); exit; }

    $pdo->prepare("UPDATE announcements SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Ação inválida']);
