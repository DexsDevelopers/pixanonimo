<?php
require_once 'includes/db.php';

// Registrar log para debug (remover em produção se desejar)
$logFile = 'webhook_log.txt';
$input = file_get_contents('php://input');
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Payload: " . $input . PHP_EOL, FILE_APPEND);

$data = json_decode($input, true);

// O PixGo costuma enviar status 'paid' ou 'approved'
if (isset($data['status']) && ($data['status'] == 'paid' || $data['status'] == 'approved')) {
    $pixId = $data['id'] ?? $data['external_id'] ?? '';
    
    if (empty($pixId)) exit;

    // Buscar a transação pendente
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE pix_id = ? AND status = 'pending'");
    $stmt->execute([$pixId]);
    $transaction = $stmt->fetch();

    if ($transaction) {
        $pdo->beginTransaction();
        try {
            // 1. Atualizar status da transação
            $upd = $pdo->prepare("UPDATE transactions SET status = 'paid' WHERE id = ?");
            $upd->execute([$transaction['id']]);

            // 2. Adicionar valor ao saldo do usuário (valor líquido calculado no api.php)
            $balanceUpd = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $balanceUpd->execute([$transaction['amount_net_brl'], $transaction['user_id']]);

            $pdo->commit();
            file_put_contents($logFile, "[SUCCESS] Transação " . $transaction['id'] . " processada." . PHP_EOL, FILE_APPEND);
        } catch (Exception $e) {
            $pdo->rollBack();
            file_put_contents($logFile, "[ERROR] Falha ao processar: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }
}

// Retornar 200 para o PixGo não reenviar o webhook
http_response_code(200);
echo json_encode(['status' => 'received']);
?>
