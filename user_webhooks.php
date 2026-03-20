<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];

// Auto-criar tabela se não existir
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_webhooks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        url VARCHAR(500) NOT NULL,
        label VARCHAR(100) DEFAULT '',
        events VARCHAR(255) DEFAULT 'payment.completed',
        active TINYINT(1) DEFAULT 1,
        last_status INT DEFAULT NULL,
        last_triggered_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_webhooks_user (user_id)
    )");
} catch (PDOException $e) {}

// Validação CSRF para POST/DELETE
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' || $method === 'DELETE') {
    $headers = getallheaders();
    $csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');
    check_csrf($csrfToken);
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

switch ($action) {
    case 'list':
        $stmt = $pdo->prepare("SELECT id, url, label, events, active, last_status, last_triggered_at, created_at FROM user_webhooks WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $webhooks = $stmt->fetchAll();
        echo json_encode(['success' => true, 'webhooks' => $webhooks]);
        break;

    case 'add':
        $url = trim($input['url'] ?? '');
        $label = trim($input['label'] ?? '');
        $events = trim($input['events'] ?? 'payment.completed');

        // Normalizar URL: remover protocolos duplicados
        $url = preg_replace('#^(https?://)+(https?://)#i', '$2', $url);

        if (empty($url)) {
            echo json_encode(['error' => 'URL é obrigatória']);
            exit;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $url)) {
            echo json_encode(['error' => 'URL inválida. Use http:// ou https://']);
            exit;
        }

        // Limite de 10 webhooks por usuário
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_webhooks WHERE user_id = ?");
        $stmt->execute([$userId]);
        if ((int)$stmt->fetchColumn() >= 10) {
            echo json_encode(['error' => 'Limite máximo de 10 webhooks atingido.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO user_webhooks (user_id, url, label, events) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $url, $label, $events]);

        echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'message' => 'Webhook adicionado!']);
        break;

    case 'delete':
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM user_webhooks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        echo json_encode(['success' => true, 'message' => 'Webhook removido!']);
        break;

    case 'toggle':
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE user_webhooks SET active = NOT active WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);

        echo json_encode(['success' => true, 'message' => 'Status atualizado!']);
        break;

    case 'test':
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT url FROM user_webhooks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $wh = $stmt->fetch();

        if (!$wh) {
            echo json_encode(['error' => 'Webhook não encontrado']);
            exit;
        }

        // Normalizar URL antes de enviar (corrige protocolos duplicados salvos anteriormente)
        $whUrl = preg_replace('#^(https?://)+(https?://)#i', '$2', $wh['url']);
        if ($whUrl !== $wh['url']) {
            $pdo->prepare("UPDATE user_webhooks SET url = ? WHERE id = ?")->execute([$whUrl, $id]);
        }

        $testPayload = [
            'event' => 'test',
            'transaction_id' => 0,
            'pix_id' => 'test_' . bin2hex(random_bytes(8)),
            'amount' => 25.00,
            'amount_net' => 24.00,
            'customer_name' => 'Teste Ghost Pix',
            'status' => 'paid',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $ch = curl_init($whUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testPayload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: GhostPix-Webhook/1.0',
            'X-GhostPix-Event: test'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        $out = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Atualizar status
        $stmt = $pdo->prepare("UPDATE user_webhooks SET last_status = ?, last_triggered_at = NOW() WHERE id = ?");
        $stmt->execute([$code, $id]);

        if ($error) {
            echo json_encode(['success' => false, 'error' => "Erro de conexão: $error", 'http_code' => 0]);
        } else {
            echo json_encode([
                'success' => $code >= 200 && $code < 300,
                'http_code' => $code,
                'message' => $code >= 200 && $code < 300
                    ? "Webhook respondeu com $code OK!"
                    : "Webhook respondeu com HTTP $code"
            ]);
        }
        break;

    default:
        echo json_encode(['error' => 'Ação inválida']);
}
