<?php
require_once 'includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

// Validação CSRF para ações via painel
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');
check_csrf($csrfToken);

if (isset($input['action']) && $input['action'] === 'generate') {
    // Gerar uma chave aleatória segura (ghost_...)
    $newKey = 'ghost_' . bin2hex(random_bytes(24));
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?");
        $stmt->execute([$newKey, $userId]);
        
        write_log('INFO', 'Nova API Key gerada', ['user_id' => $userId]);
        
        echo json_encode([
            'success' => true, 
            'api_key' => $newKey,
            'message' => 'Sua nova chave de API foi gerada com sucesso!'
        ]);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Erro ao salvar chave: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Ação inválida']);
}
?>

