<?php
require_once 'includes/db.php';
require_once 'includes/MailService.php';
require_once 'includes/TelegramService.php';

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

        case 'update_card_extra_fee':
            $fee = number_format((float)($data['card_extra_fee'] ?? 0), 2, '.', '');
            $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES ('card_extra_fee', ?) ON DUPLICATE KEY UPDATE `value` = ?")->execute([$fee, $fee]);
            echo json_encode(['success' => true]);
            break;

        case 'update_card_fees':
            // Legacy fallback for cached builds
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

        case 'create_user':
            $email     = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
            $password  = password_hash($data['password'] ?? '123456', PASSWORD_DEFAULT);
            $full_name = strip_tags(trim($data['full_name'] ?? 'Usuário'));
            $pix_key   = strip_tags(trim($data['pix_key'] ?? ''));
            $status    = in_array($data['status'] ?? 'pending', ['pending','approved']) ? $data['status'] : 'pending';

            $defTaxStmt  = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'default_user_tax'");
            $defaultTax  = (float)($defTaxStmt->fetchColumn() ?: '4.0');

            $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, pix_key, status, referral_token, is_demo, commission_rate) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
            $stmt->execute([$email, $password, $full_name, $pix_key, $status, bin2hex(random_bytes(8)), $defaultTax]);

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
                
                $u = getUser($userId);
                if ($u) {
                    if ($value == 'approved') MailService::notifyApproval($u['email'], $u['full_name']);
                    try { TelegramService::notifyUserStatusChanged($u['full_name'], $u['email'], $value); } catch (Throwable $e) {}
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
            $pdo->beginTransaction();
            try {
                // Lock + verificar status para evitar double-processing
                $stmtW = $pdo->prepare("SELECT w.user_id, w.amount, w.pix_key, w.status, u.full_name FROM withdrawals w JOIN users u ON u.id = w.user_id WHERE w.id = ? FOR UPDATE");
                $stmtW->execute([$wId]);
                $w = $stmtW->fetch();
                if (!$w || $w['status'] !== 'pending') {
                    $pdo->rollBack();
                    echo json_encode(['error' => 'Saque não encontrado ou já processado']);
                    break;
                }
                // Debitar saldo atomicamente
                $result = adjustBalance(
                    (int)$w['user_id'],
                    -abs((float)$w['amount']),
                    'withdraw_debit',
                    'wd_' . $wId,
                    'Saque #' . $wId . ' aprovado — ' . ($hash ?: 'sem hash')
                );
                if (!$result['success']) {
                    $pdo->rollBack();
                    echo json_encode(['error' => 'Falha ao debitar: ' . $result['error']]);
                    break;
                }
                $pdo->prepare("UPDATE withdrawals SET status = 'completed', tx_hash = ? WHERE id = ?")->execute([$hash, $wId]);
                $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Saque Enviado! 💸', ?, 'success')")
                    ->execute([$w['user_id'], "Seu saque de R$ " . number_format($w['amount'], 2, ',', '.') . " foi processado."]);
                $pdo->commit();
                $u = getUser($w['user_id']);
                if ($u) MailService::notifyWithdrawalPaid($u['email'], $u['full_name'], $w['amount']);
                try { TelegramService::notifyWithdrawalApproved($w['full_name'], (float)$w['amount'], $w['pix_key'] ?? '', $hash); } catch (Throwable $e) {}
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                write_log('ERROR', 'complete_withdraw FAILED', ['wd_id' => $wId, 'error' => $e->getMessage()]);
                echo json_encode(['error' => 'Erro: ' . $e->getMessage()]);
                break;
            }
            echo json_encode(['success' => true]);
            break;

        case 'reject_withdraw':
            $wId = (int)$data['withdraw_id'];
            $stmt = $pdo->prepare("SELECT w.user_id, w.amount, u.full_name FROM withdrawals w JOIN users u ON u.id = w.user_id WHERE w.id = ?");
            $stmt->execute([$wId]);
            $w = $stmt->fetch();
            if ($w) {
                $pdo->beginTransaction();
                // Saldo não precisa ser devolvido pois nunca foi debitado (debitamos só na aprovação)
                $pdo->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?")->execute([$wId]);
                $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Saque Rejeitado ❌', ?, 'warning')")
                    ->execute([$w['user_id'], "Seu saque de R$ " . number_format($w['amount'], 2, ',', '.') . " foi rejeitado."]);
                $pdo->commit();
                try { TelegramService::notifyWithdrawalRejected($w['full_name'], (float)$w['amount']); } catch (Throwable $e) {}
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
            $isAdminOnly = isset($data['is_admin_only']) && $data['is_admin_only'] == '1' ? 1 : 0;
            if (empty($key)) throw new Exception("Chave API é obrigatória");
            // Auto-add column if missing
            try { $pdo->exec("ALTER TABLE pixgo_apis ADD COLUMN is_admin_only TINYINT(1) DEFAULT 0"); } catch (PDOException $e) {}
            $stmt = $pdo->prepare("INSERT INTO pixgo_apis (name, api_key, status, is_admin_only) VALUES (?, ?, 'active', ?)");
            $stmt->execute([$name, $key, $isAdminOnly]);
            echo json_encode(['success' => true]);
            break;

        case 'set_api_type':
            $id = (int)$data['id'];
            $isAdminOnly = isset($data['is_admin_only']) && $data['is_admin_only'] == '1' ? 1 : 0;
            try { $pdo->exec("ALTER TABLE pixgo_apis ADD COLUMN is_admin_only TINYINT(1) DEFAULT 0"); } catch (PDOException $e) {}
            $pdo->prepare("UPDATE pixgo_apis SET is_admin_only = ? WHERE id = ?")->execute([$isAdminOnly, $id]);
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

        case 'toggle_med':
            $txId = (int)$data['transaction_id'];
            $stmt = $pdo->prepare("SELECT med, user_id, amount_brl, amount_net_brl, status FROM transactions WHERE id = ?");
            $stmt->execute([$txId]);
            $tx = $stmt->fetch();
            if (!$tx) {
                echo json_encode(['error' => 'Transação não encontrada']);
                break;
            }
            $newMed = $tx['med'] ? 0 : 1;
            $netAmount = (float)$tx['amount_net_brl'];

            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE transactions SET med = ? WHERE id = ?")->execute([$newMed, $txId]);

                // Ajustar saldo: marcar MED = debitar, remover MED = devolver
                if ($newMed) {
                    // Debitar valor líquido do vendedor
                    $result = adjustBalance(
                        (int)$tx['user_id'],
                        -abs($netAmount),
                        'med_debit',
                        'med_' . $txId,
                        'MED na venda #' . $txId . ' — R$ ' . number_format($tx['amount_brl'], 2, ',', '.'),
                        true // allowNegative
                    );
                } else {
                    // Devolver valor líquido ao vendedor
                    $result = adjustBalance(
                        (int)$tx['user_id'],
                        abs($netAmount),
                        'med_refund',
                        'med_refund_' . $txId,
                        'MED removido da venda #' . $txId . ' — estorno R$ ' . number_format($netAmount, 2, ',', '.')
                    );
                }

                if (!$result['success']) {
                    $pdo->rollBack();
                    echo json_encode(['error' => 'Falha ao ajustar saldo: ' . ($result['error'] ?? 'erro desconhecido')]);
                    break;
                }

                // Notificar vendedor
                $msg = $newMed
                    ? "Sua venda #$txId de R$ " . number_format($tx['amount_brl'], 2, ',', '.') . " recebeu um MED (Mecanismo Especial de Devolução). O valor líquido de R$ " . number_format($netAmount, 2, ',', '.') . " foi debitado do seu saldo."
                    : "O MED da venda #$txId foi removido. R$ " . number_format($netAmount, 2, ',', '.') . " foi devolvido ao seu saldo.";
                $title = $newMed ? 'MED Recebido ⚠️' : 'MED Removido ✅';
                $type = $newMed ? 'danger' : 'success';
                $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)")
                    ->execute([$tx['user_id'], $title, $msg, $type]);

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['error' => 'Erro: ' . $e->getMessage()]);
                break;
            }
            echo json_encode(['success' => true, 'med' => $newMed]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Ação desconhecida']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
