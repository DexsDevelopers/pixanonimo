<?php
session_start();
require_once '../includes/db.php';

if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Lógica de Aprovação/Bloqueio (Suporta ?approve=ID, ?block=ID ou ?action=...&id=...)
$action = null;
$id = null;

if (isset($_GET['approve'])) {
    $action = 'approve';
    $id = (int)$_GET['approve'];
} elseif (isset($_GET['block'])) {
    $action = 'block';
    $id = (int)$_GET['block'];
} elseif (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
}

if ($action && $id) {
    $status = ($action == 'approve') ? 'approved' : 'blocked';
    
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND is_admin = 0");
    $stmt->execute([$status, $id]);
    
    // Notificação automática
    $title = ($status == 'approved') ? 'Conta Aprovada! ✅' : 'Conta Bloqueada ⚠️';
    $msg = ($status == 'approved') ? 'Sua conta foi verificada e aprovada. Já pode começar a operar!' : 'Sua conta foi bloqueada por nossa equipe de segurança.';
    $type = ($status == 'approved') ? 'success' : 'danger';
    
    try {
        $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)")->execute([$id, $title, $msg, $type]);
    } catch (PDOException $e) {
        write_log('error', 'Falha ao inserir notificação automática (Aprovação): ' . $e->getMessage());
    }

    header("Location: index.php");
    exit;
}

// Lógica de Comissões e Saques
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_comm'])) {
        foreach ($_POST['comm'] as $userId => $rate) {
            $stmt = $pdo->prepare("UPDATE users SET commission_rate = ? WHERE id = ?");
            $stmt->execute([(float)$rate, $userId]);
        }
        header("Location: index.php?success=1");
        exit;
    }

    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action == 'complete_withdraw') {
            $wId = $_POST['withdraw_id'];
            $hash = $_POST['tx_hash'] ?? '';
            $stmt = $pdo->prepare("UPDATE withdrawals SET status = 'completed', tx_hash = ? WHERE id = ?");
            $stmt->execute([$hash, $wId]);
            
            // Notificação automática
            $stmtUser = $pdo->prepare("SELECT user_id, amount FROM withdrawals WHERE id = ?");
            $stmtUser->execute([$wId]);
            $wInfo = $stmtUser->fetch();
            if ($wInfo) {
                $val = number_format($wInfo['amount'], 2, ',', '.');
                try {
                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Saque Enviado! 💸', ?, 'success')")
                        ->execute([$wInfo['user_id'], "Seu saque no valor de R$ {$val} foi processado e enviado para sua chave Pix."]);
                } catch (PDOException $e) {
                    write_log('error', 'Falha ao inserir notificação automática (Saque Pago): ' . $e->getMessage());
                }
            }

            header("Location: index.php?success=1");
            exit;
        }

        if ($action == 'reject_withdraw') {
            $wId = $_POST['withdraw_id'];
            // Devolver saldo ao usuário
            $stmt = $pdo->prepare("SELECT user_id, amount FROM withdrawals WHERE id = ?");
            $stmt->execute([$wId]);
            $w = $stmt->fetch();
            
            if ($w) {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$w['amount'], $w['user_id']]);
                $pdo->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?")->execute([$wId]);
                
                $val = number_format($w['amount'], 2, ',', '.');
                try {
                    $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, 'Saque Rejeitado ❌', ?, 'warning')")
                        ->execute([$w['user_id'], "Seu saque de R$ {$val} foi rejeitado e o saldo retornou para sua conta. Verifique sua chave Pix."]);
                } catch (PDOException $e) {
                    write_log('error', 'Falha ao inserir notificação automática (Saque Rejeitado): ' . $e->getMessage());
                }
                
                $pdo->commit();
            }
            header("Location: index.php?success=1");
            exit;
        }
    }
}

$users = $pdo->query("SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC")->fetchAll();

// Lógica de Configurações Globais (Processamento no topo para redirecionamento limpo)
if (isset($_POST['update_settings'])) {
    $aff_rate = (float)$_POST['affiliate_rate'];
    
    // Verificar se a chave existe
    $check = $pdo->prepare("SELECT `key` FROM settings WHERE `key` = 'affiliate_commission_rate'");
    $check->execute();
    if ($check->fetch()) {
        $pdo->prepare("UPDATE settings SET `value` = ? WHERE `key` = 'affiliate_commission_rate'")->execute([$aff_rate]);
    } else {
        $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES ('affiliate_commission_rate', ?)")->execute([$aff_rate]);
    }
    header("Location: index.php?success=1");
    exit;
}

// Buscar taxa atual para o formulário
$affRateStmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'affiliate_commission_rate'");
$currentAffRate = $affRateStmt->fetchColumn() ?: '10';

// Calcular Lucro Total da Plataforma (Lucro líquido após taxa de 2% do PixGo)
$stmtProfit = $pdo->query("SELECT SUM((amount_brl - amount_net_brl) - (amount_brl * 0.02)) as total FROM transactions WHERE status = 'paid'");
$totalProfit = $stmtProfit->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Ghost Pix Admin</title>
    <link rel="stylesheet" href="../style.css?v=110.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-header admin-header">
                <div>
                    <h1>Painel Administrativo</h1>
                    <p>Gerenciamento de usuários e solicitações de liquidação.</p>
                </div>
                <div class="header-actions" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <?php if (isset($_GET['success'])): ?>
                        <div class="badge paid" style="padding: 5px 10px; font-size: 0.75rem;">✓ Salvo com sucesso</div>
                    <?php endif; ?>

                    <div class="stat-card ghost-green" style="padding: 1rem; flex-direction: row; gap: 1.5rem; align-items: center; margin-bottom: 0; min-width: 220px;">
                        <div class="stat-icon" style="margin-bottom: 0;"><i class="fas fa-sack-dollar"></i></div>
                        <div>
                            <span class="stat-label">Lucro Plataforma</span>
                            <div class="stat-value" style="font-size: 1.4rem;">R$ <?php echo number_format($totalProfit, 2, ',', '.'); ?></div>
                        </div>
                    </div>

                    <form method="POST" class="stat-card ghost-purple" style="padding: 1rem; flex-direction: row; gap: 1.5rem; align-items: center; margin-bottom: 0; border: 1px solid rgba(168, 85, 247, 0.2);">
                        <div class="stat-icon" style="margin-bottom: 0;"><i class="fas fa-percent"></i></div>
                        <div style="display: flex; flex-direction: column;">
                            <label style="font-size: 0.65rem; color: var(--text-3); text-transform: uppercase; font-weight: 800;">Comissão Afiliados</label>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <input type="number" name="affiliate_rate" value="<?php echo $currentAffRate; ?>" step="1" style="width: 40px; background: transparent; border: none; color: #fff; font-weight: 700; font-size: 1.1rem; outline: none;">
                                <span style="font-size: 0.9rem; color: var(--text-3);">%</span>
                                <button type="submit" name="update_settings" class="badge paid" style="border: none; cursor: pointer; margin-left: 10px;">Salvar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </header>

            <div class="card glass full-width">
            <h3>Gerenciar Usuários</h3>
            <form method="POST">
                <div class="table-responsive">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>Email</th>
                                <th>Saldo</th>
                                <th>Taxa (%)</th>
                                <th>Status</th>
                                <th style="text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr>
                                <td>#<?php echo $u['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>R$ <?php echo number_format($u['balance'], 2, ',', '.'); ?></td>
                                <td>
                                    <input type="number" name="comm[<?php echo $u['id']; ?>]" value="<?php echo $u['commission_rate']; ?>" step="0.1" style="width: 70px; padding: 5px 10px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: white; border-radius: 8px; outline: none; font-size: 0.85rem;">
                                </td>
                                <td><span class="badge <?php echo $u['status'] == 'approved' ? 'paid' : ($u['status'] == 'pending' ? 'pending' : 'expired'); ?>"><?php echo ucfirst($u['status']); ?></span></td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <?php if($u['status'] == 'pending'): ?>
                                            <a href="?approve=<?php echo $u['id']; ?>" class="badge paid" style="text-decoration: none; border: none;">Aprovar</a>
                                        <?php endif; ?>
                                        <?php if($u['status'] != 'blocked'): ?>
                                            <a href="?block=<?php echo $u['id']; ?>" class="badge expired" style="text-decoration: none; border: none;">Bloquear</a>
                                        <?php endif; ?>
                                        <?php if($u['status'] == 'blocked'): ?>
                                            <a href="?approve=<?php echo $u['id']; ?>" class="badge paid" style="text-decoration: none; border: none;">Desbloquear</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="update_comm" class="btn-primary" style="margin-top: 2rem; width: auto; padding: 0.5rem 2rem;">Salvar Alterações</button>
            </form>
        </div>

        <!-- Seção de Saques -->
        <div class="card glass full-width" style="margin-top: 2rem;">
            <h3>Solicitações de Saque</h3>
            <div class="table-responsive">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Chave PIX</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th style="text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT w.*, u.email, u.pix_key, u.commission_rate, u.balance FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'pending' ORDER BY w.created_at DESC");
                        while($w = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($w['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($w['email']); ?></td>
                            <td><code style="background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;"><?php echo htmlspecialchars($w['pix_key']); ?></code></td>
                            <td>R$ <?php echo number_format($w['amount'], 2, ',', '.'); ?></td>
                            <td><?php echo date('d/m H:i', strtotime($w['created_at'])); ?></td>
                            <td style="text-align: right;">
                                <form method="POST" style="display:flex; align-items:center; gap:8px; justify-content: flex-end;">
                                    <input type="hidden" name="withdraw_id" value="<?php echo $w['id']; ?>">
                                    <input type="text" name="tx_hash" placeholder="Hash TX" style="padding: 6px 10px; font-size: 0.75rem; width: 120px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: white; border-radius: 6px; outline: none;">
                                    <button type="submit" name="action" value="complete_withdraw" class="badge paid" style="border:none; cursor:pointer;">Pagar</button>
                                    <button type="submit" name="action" value="reject_withdraw" class="badge expired" style="border:none; cursor:pointer;">Negar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div> <!-- Final app-container vindo da sidebar.php -->
    <script src="../script.js?v=110.0"></script>
</body>
</html>
