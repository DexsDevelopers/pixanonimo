<?php
require_once 'includes/db.php';
require_once 'includes/MailService.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$data = $_POST;
$action = $data['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'update_global_settings':
            $aff_rate = (float)$data['affiliate_rate'];
            $def_tax = (float)$data['default_user_tax'];
            
            $pdo->prepare("UPDATE settings SET `value` = ? WHERE `key` = 'affiliate_commission_rate'")->execute([$aff_rate]);
            $pdo->prepare("UPDATE settings SET `value` = ? WHERE `key` = 'default_user_tax'")->execute([$def_tax]);
            
            echo json_encode(['success' => true]);
            break;

        case 'create_demo_user':
            $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password = password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT);
            $full_name = strip_tags(trim($data['full_name'] ?? 'Demo'));
            $balance = (float)($data['initial_balance'] ?? 0);
            $pix_key = strip_tags(trim($data['pix_key'] ?? 'demo@pix.com'));
            
            $defTaxStmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'default_user_tax'");
            $defaultTax = (float)($defTaxStmt->fetchColumn() ?: '4.0');

            $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, pix_key, balance, status, referral_token, is_demo, commission_rate) VALUES (?, ?, ?, ?, ?, 'approved', ?, 1, ?)");
            $stmt->execute([$email, $password, $full_name, $pix_key, $balance, bin2hex(random_bytes(8)), $defaultTax]);
            
            echo json_encode(['success' => true]);
            break;

        case 'update_user_field':
            $userId = (int)$data['user_id'];
            $field = $data['field']; // 'pix_key', 'balance', 'commission_rate', 'is_demo', 'status'
            $value = $data['value'];

            if (!in_array($field, ['pix_key', 'balance', 'commission_rate', 'is_demo', 'status'])) {
                throw new Exception("Campo inválido");
            }

            $stmt = $pdo->prepare("UPDATE users SET $field = ? WHERE id = ? AND is_admin = 0");
            $stmt->execute([$value, $userId]);

            // Se for aprovação, enviar e-mail e notificação
            if ($field === 'status') {
                $title = ($value == 'approved') ? 'Conta Aprovada! ✅' : 'Conta Bloqueada ⚠️';
                $msg = ($value == 'approved') ? 'Sua conta foi verificada e aprovada.' : 'Sua conta foi bloqueada.';
                $type = ($value == 'approved') ? 'success' : 'danger';
                
                $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)")->execute([$userId, $title, $msg, $type]);
                
                if ($value == 'approved') {
                    $u = getUser($userId);
                    if ($u) MailService::notifyApproval($u['email'], $u['full_name']);
                }
            }

            echo json_encode(['success' => true]);
            break;

        case 'create_fake_withdrawal':
            $userId = (int)$data['user_id'];
            $amount = (float)$data['amount'];
            
            $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, full_name, amount, status, type) VALUES (?, (SELECT full_name FROM users WHERE id = ?), ?, 'completed', 'fake')");
            $stmt->execute([$userId, $userId, $amount]);
            
            echo json_encode(['success' => true]);
            break;

        case 'complete_withdraw':
            $wId = (int)$data['withdraw_id'];
            $hash = $data['tx_hash'] ?? '';
            $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'completed', tx_hash = ? WHERE id = ?");
            if ($stmt->execute([$hash, $wId])) {
                $stmtW = $pdo->prepare("SELECT user_id, amount FROM withdrawals WHERE id = ?");
                $stmtW->execute([$wId]);
                $w = $stmtW->fetch();
                if ($w) {
                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Saque Enviado! 💸', ?, 'success')")
                        ->execute([$w['user_id'], "Seu saque de R$ " . number_format($w['amount'], 2, ',', '.') . " foi processado."]);
                    
                    $u = getUser($w['user_id']);
                    if ($u) MailService::notifyWithdrawalPaid($u['email'], $u['full_name'], $w['amount']);
                }
            }
            echo json_encode(['success' => true]);
            break;

        case 'reject_withdraw':
            $wId = (int)$data['withdraw_id'];
            $stmt = $pdo->prepare("SELECT user_id, amount FROM withdrawals WHERE id = ?");
            $stmt->execute([$wId]);
            $w = $stmt->fetch();
            if ($w) {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$w['amount'], $w['user_id']]);
                $pdo->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?")->execute([$wId]);
                $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Saque Rejeitado ❌', ?, 'warning')")
                    ->execute([$w['user_id'], "Seu saque de R$ " . number_format($w['amount'], 2, ',', '.') . " foi rejeitado."]);
                $pdo->commit();
            }
            echo json_encode(['success' => true]);
            break;

        case 'reset_user_password':
            $userId = (int)$data['user_id'];
            // Auto-criar coluna se não existir
            try { $pdo->exec("ALTER TABLE users ADD COLUMN must_reset_password TINYINT(1) DEFAULT 0"); } catch (PDOException $e) {}
            // Setar flag de reset (não muda a senha - o user vai definir uma nova ao fazer login)
            $pdo->prepare("UPDATE users SET must_reset_password = 1 WHERE id = ? AND is_admin = 0")->execute([$userId]);
            // Notificar usuário
            $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Senha Resetada 🔑', 'Sua senha foi resetada pelo administrador. Ao fazer login, você precisará criar uma nova senha.', 'warning')")->execute([$userId]);
            write_log('INFO', 'Admin resetou senha do usuário', ['user_id' => $userId]);
            echo json_encode(['success' => true, 'message' => 'Senha resetada. O usuário precisará definir uma nova senha no próximo login.']);
            break;

        case 'add_api':
            $name = strip_tags(trim($data['name'] ?? 'Nova API'));
            $key = strip_tags(trim($data['api_key'] ?? ''));
            if (empty($key)) throw new Exception("Chave API é obrigatória");
            
            $stmt = $pdo->prepare("INSERT INTO pixgo_apis (name, api_key, status) VALUES (?, ?, 'active')");
            $stmt->execute([$name, $key]);
            echo json_encode(['success' => true]);
            break;

        case 'toggle_api_status':
            $id = (int)$data['id'];
            $stmt = $pdo->prepare("UPDATE pixgo_apis SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_api':
            $id = (int)$data['id'];
            $stmt = $pdo->prepare("DELETE FROM pixgo_apis WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação desconhecida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
