<?php
require_once 'includes/db.php';
require_once 'includes/TelegramService.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['success' => false, 'error' => 'Não autorizado']); exit; }

$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');
check_csrf($csrfToken);

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {

        case 'create':
            $stmt = $pdo->prepare("INSERT INTO products (user_id, name, description, price, image_url, category, type, delivery_method, delivery_info, vitrine, stock, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([
                $userId,
                trim($input['name'] ?? ''),
                trim($input['description'] ?? ''),
                (float)($input['price'] ?? 0),
                trim($input['image_url'] ?? ''),
                $input['category'] ?? 'Digital',
                $input['type'] ?? 'digital',
                trim($input['delivery_method'] ?? ''),
                trim($input['delivery_info'] ?? ''),
                ($input['vitrine'] ?? '0') === '1' ? 1 : 0,
                (int)($input['stock'] ?? -1),
            ]);
            $newId = (int) $pdo->lastInsertId();

            // Notificar admin via Telegram com botões de aprovação
            try {
                $seller = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                $seller->execute([$userId]);
                $sellerName = $seller->fetchColumn() ?: 'Desconhecido';
                TelegramService::notifyNewProductAdmin(
                    $sellerName,
                    trim($input['name'] ?? ''),
                    (float)($input['price'] ?? 0),
                    $newId,
                    $input['category'] ?? ''
                );
            } catch (Throwable $e) {}

            echo json_encode(['success' => true, 'id' => $newId]);
            break;

        case 'update':
            $id = (int)($input['id'] ?? 0);
            // Verify ownership
            $check = $pdo->prepare("SELECT id, vitrine, status FROM products WHERE id = ? AND user_id = ?");
            $check->execute([$id, $userId]);
            $current = $check->fetch();
            if (!$current) { echo json_encode(['success' => false, 'error' => 'Produto não encontrado.']); exit; }

            $wantsVitrine = ($input['vitrine'] ?? '0') === '1' ? 1 : 0;

            // Se quer vitrine, status SEMPRE volta pra pendente (admin precisa aprovar)
            // Se não quer vitrine, mantém active
            if ($wantsVitrine) {
                $newStatus = 'pending';
                $newVitrine = 1;
            } else {
                $newStatus = 'active';
                $newVitrine = 0;
            }

            $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, image_url=?, category=?, type=?, delivery_method=?, delivery_info=?, vitrine=?, stock=?, status=?, updated_at=NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([
                trim($input['name'] ?? ''),
                trim($input['description'] ?? ''),
                (float)($input['price'] ?? 0),
                trim($input['image_url'] ?? ''),
                $input['category'] ?? 'Digital',
                $input['type'] ?? 'digital',
                trim($input['delivery_method'] ?? ''),
                trim($input['delivery_info'] ?? ''),
                $newVitrine,
                (int)($input['stock'] ?? -1),
                $newStatus,
                $id,
                $userId,
            ]);

            // Notificar admin se produto quer vitrine e mudou para pendente
            if ($wantsVitrine && ($current['status'] !== 'pending' || $current['vitrine'] != 1)) {
                try {
                    $seller = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
                    $seller->execute([$userId]);
                    $sellerName = $seller->fetchColumn() ?: 'Desconhecido';
                    TelegramService::notifyNewProductAdmin(
                        $sellerName,
                        trim($input['name'] ?? ''),
                        (float)($input['price'] ?? 0),
                        $id,
                        $input['category'] ?? ''
                    );
                } catch (Throwable $e) {}
            }

            echo json_encode(['success' => true]);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            echo json_encode(['success' => true]);
            break;

        case 'update_store':
            $field = $input['field'] ?? '';
            $value = trim($input['value'] ?? '');
            $allowed = ['store_name', 'store_description', 'store_banner'];
            if (!in_array($field, $allowed)) { echo json_encode(['success' => false, 'error' => 'Campo inválido.']); exit; }

            // Upsert store settings
            $existing = $pdo->prepare("SELECT id FROM store_settings WHERE user_id = ?");
            $existing->execute([$userId]);
            if ($existing->fetch()) {
                $pdo->prepare("UPDATE store_settings SET {$field} = ?, updated_at = NOW() WHERE user_id = ?")->execute([$value, $userId]);
            } else {
                $slug = 'loja-' . $userId . '-' . substr(md5($userId . time()), 0, 6);
                $pdo->prepare("INSERT INTO store_settings (user_id, {$field}, slug) VALUES (?, ?, ?)")->execute([$userId, $value, $slug]);
            }
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação inválida.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
