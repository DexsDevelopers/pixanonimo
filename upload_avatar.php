<?php
session_start();
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Nenhum arquivo enviado ou erro no upload']);
    exit;
}

$file = $_FILES['avatar'];

// Validar tipo
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Tipo de arquivo não permitido. Use JPG, PNG, WebP ou GIF.']);
    exit;
}

// Validar tamanho (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Arquivo muito grande. Máximo 5MB.']);
    exit;
}

// Criar diretório se não existir
$uploadDir = __DIR__ . '/uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Determinar extensão
$extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
$ext = $extMap[$mimeType] ?? 'jpg';

// Remover avatar antigo (qualquer extensão)
foreach (glob($uploadDir . $userId . '.*') as $oldFile) {
    unlink($oldFile);
}

$filename = $userId . '.' . $ext;
$destination = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar arquivo']);
    exit;
}

$avatarUrl = '/uploads/avatars/' . $filename . '?v=' . time();

echo json_encode([
    'success' => true,
    'avatar_url' => $avatarUrl
]);
