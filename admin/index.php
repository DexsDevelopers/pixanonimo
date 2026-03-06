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

    // Lógica Demo/Influencer
    if (isset($_POST['create_demo_user'])) {
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
        $full_name = strip_tags(trim($_POST['full_name'] ?? 'Demo Influencer'));
        $balance = (float)($_POST['initial_balance'] ?? 0);
        $pix_key = strip_tags(trim($_POST['pix_key'] ?? 'influencer@pix.com'));
        $ref_token = bin2hex(random_bytes(8));

        $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, pix_key, balance, status, referral_token, is_demo) VALUES (?, ?, ?, ?, ?, 'approved', ?, 1)");
        $stmt->execute([$email, $password, $full_name, $pix_key, $balance, $ref_token]);
        
        header("Location: index.php?success=1");
        exit;
    }

    if (isset($_POST['update_pix'])) {
        $userId = (int)$_POST['user_id'];
        $newPix = strip_tags(trim($_POST['pix_key']));
        $stmt = $pdo->prepare("UPDATE users SET pix_key = ? WHERE id = ?");
        $stmt->execute([$newPix, $userId]);
        header("Location: index.php?success=1");
        exit;
    }

    if (isset($_POST['toggle_demo'])) {
        $userId = (int)$_POST['user_id'];
        $status = (int)$_POST['is_demo'];
        $stmt = $pdo->prepare("UPDATE users SET is_demo = ? WHERE id = ?");
        $stmt->execute([$status, $userId]);
        header("Location: index.php?success=1");
        exit;
    }

    if (isset($_POST['update_balance'])) {
        $userId = (int)$_POST['user_id'];
        $newBalance = (float)$_POST['balance'];
        $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->execute([$newBalance, $userId]);
        header("Location: index.php?success=1");
        exit;
    }

    if (isset($_POST['create_fake_withdrawal'])) {
        $userId = (int)$_POST['user_id'];
        $amount = (float)$_POST['amount'];
        $pixKey = $_POST['pix_key'];
        
        $stmt = $pdo->prepare("INSERT INTO withdrawals (user_id, full_name, amount, status, type) VALUES (?, (SELECT full_name FROM users WHERE id = ?), ?, 'completed', 'fake')");
        $stmt->execute([$userId, $userId, $amount]);
        
        header("Location: index.php?success=1");
        exit;
    }

    if (isset($_POST['approve_user']) || isset($_POST['block_user'])) {
        $id = (int)$_POST['user_id'];
        $status = isset($_POST['approve_user']) ? 'approved' : 'blocked';
        
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
    <link rel="stylesheet" href="../style.css?v=122.3">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-header {
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }
        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .main-content {
            padding: 1rem !important;
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            max-width: 100%;
            overflow-x: hidden;
        }
        .card.glass.full-width {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            box-sizing: border-box !important;
            padding: 1.25rem !important;
        }
        .table-responsive {
            width: 100% !important;
            overflow-x: visible !important;
            display: block !important;
        }
        .transaction-table {
            width: 100% !important;
            table-layout: fixed;
            border-spacing: 0 0.4rem !important;
        }
        .transaction-table th, .transaction-table td {
            font-size: 0.82rem !important;
            padding: 0.8rem 0.4rem !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Tight Column Widths for Desktop */
        .col-id { width: 50px; }
        .col-user { width: 18%; }
        .col-email { width: 22%; }
        .col-balance { width: 15%; }
        .col-rate { width: 80px; }
        .col-status { width: 95px; }
        .col-actions { width: 200px; }

        @media (max-width: 1200px) {
            .transaction-table {
                table-layout: auto;
            }
            .table-responsive {
                overflow-x: auto !important;
            }
        }
        
        /* User/Email column compression */
        .col-user { width: 180px !important; }
        .col-email { width: 220px !important; }
        .col-balance { width: 130px !important; }
        
        .transaction-table {
            table-layout: auto !important; /* Allow content to dictate width if needed, but flex-container will squeeze */
        }
        .btn-demo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 0 1.5rem;
            height: 50px;
            border-radius: 14px;
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.2);
            font-size: 0.9rem;
        }
        .btn-demo:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
            filter: brightness(1.1);
        }
        .btn-demo i {
            font-size: 1.1rem;
        }
        .pix-input-admin {
            width: 130px !important;
            background: rgba(0,0,0,0.2) !important;
            border: 1px solid var(--border) !important;
            color: #fff !important;
            border-radius: 6px !important;
            padding: 4px 8px !important;
            font-size: 0.75rem !important;
        }
        .balance-input-admin {
            width: 90px !important;
            background: rgba(0,0,0,0.2) !important;
            border: 1px solid var(--border) !important;
            color: #4ade80 !important;
            border-radius: 6px !important;
            font-weight: 700 !important;
            padding: 4px 8px !important;
        }
        .tx-input-admin {
            padding: 6px 10px !important;
            font-size: 0.75rem !important;
            width: 140px !important;
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid var(--border) !important;
            color: white !important;
            border-radius: 6px !important;
            outline: none !important;
        }
        @media (max-width: 992px) {
            .tx-input-admin {
                width: 100% !important;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body class="dashboard-body" style="background: #000; overflow-x: hidden;">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content" style="flex: 1; min-width: 0;">
                <header class="top-header admin-header">
                    <div>
                        <h1>Painel Administrativo</h1>
                        <p>Gerenciamento de usuários e solicitações de liquidação.</p>
                    </div>
                    <div class="header-actions">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="badge paid" style="padding: 5px 10px; font-size: 0.75rem;">✓ Salvo com sucesso</div>
                        <?php endif; ?>

                        <div class="stat-card ghost-green" style="padding: 1rem; flex-direction: row; gap: 1.5rem; align-items: center; margin-bottom: 0; min-width: 200px;">
                            <div class="stat-icon" style="margin-bottom: 0;"><i class="fas fa-sack-dollar"></i></div>
                            <div>
                                <span class="stat-label">Lucro Plataforma</span>
                                <div class="stat-value" style="font-size: 1.2rem;">R$ <?php echo number_format($totalProfit, 2, ',', '.'); ?></div>
                            </div>
                        </div>

                        <form method="POST" class="stat-card ghost-purple" style="padding: 1rem; flex-direction: row; gap: 1.5rem; align-items: center; margin-bottom: 0; border: 1px solid rgba(168, 85, 247, 0.2);">
                            <div class="stat-icon" style="margin-bottom: 0;"><i class="fas fa-percent"></i></div>
                            <div style="display: flex; flex-direction: column;">
                                <label style="font-size: 0.65rem; color: var(--text-3); text-transform: uppercase; font-weight: 800;">Comissão Afiliados</label>
                                <div style="display: flex; align-items: center; gap: 5px;">
                                    <input type="number" name="affiliate_rate" value="<?php echo $currentAffRate; ?>" step="1" style="width: 40px; background: transparent; border: none; color: #fff; font-weight: 700; font-size: 1.1rem; outline: none; padding: 0;">
                                    <span style="font-size: 0.9rem; color: var(--text-3);">%</span>
                                    <button type="submit" name="update_settings" class="badge paid" style="border: none; cursor: pointer; margin-left: 10px;">Salvar</button>
                                </div>
                            </div>
                        </form>

                        <button onclick="document.getElementById('modal-create-demo').style.display='flex'" class="btn-demo">
                            <i class="fas fa-user-plus"></i> Criar Conta Demo
                        </button>
                    </div>
                </header>

            <div class="card glass full-width">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3>Gerenciar Usuários</h3>
                    <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                        <button type="submit" name="update_comm" class="btn-primary" style="width: auto; padding: 0.5rem 1.5rem; font-size: 0.85rem;">Salvar Taxas</button>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th class="col-id">ID</th>
                                <th class="col-user">Usuário / Demo</th>
                                <th class="col-email">Email / Pix</th>
                                <th class="col-balance">Saldo</th>
                                <th class="col-rate">Taxa (%)</th>
                                <th class="col-status">Status</th>
                                <th class="col-actions" style="text-align: right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $u): ?>
                            <tr class="responsive-row">
                                <td data-label="ID">#<?php echo $u['id']; ?></td>
                                <td data-label="Usuário / Demo">
                                    <div style="display:flex; flex-direction:column; gap:5px;">
                                        <strong><?php echo htmlspecialchars($u['full_name']); ?></strong>
                                        <form method="POST">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="is_demo" value="<?php echo $u['is_demo'] ? '0' : '1'; ?>">
                                            <button type="submit" name="toggle_demo" class="badge <?php echo $u['is_demo'] ? 'paid' : 'expired'; ?>" style="border:none; cursor:pointer; font-size:0.6rem; padding: 2px 5px;">
                                                <i class="fas <?php echo $u['is_demo'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i> Demo
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td data-label="Email / Pix">
                                    <span style="font-size:0.85rem; opacity:0.7;"><?php echo htmlspecialchars($u['email']); ?></span>
                                    <form method="POST" style="display: flex; align-items: center; gap: 5px; margin-top: 5px;">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="text" name="pix_key" value="<?php echo htmlspecialchars($u['pix_key']); ?>" class="pix-input-admin" style="padding: 3px 8px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); color: #fff; border-radius: 4px; outline: none;">
                                        <button type="submit" name="update_pix" class="btn-icon-sm" style="background: rgba(168, 85, 247, 0.1); color: var(--purple); border: none; border-radius: 4px; cursor: pointer; height: 24px; width: 24px;"><i class="fas fa-save" style="font-size: 0.7rem;"></i></button>
                                    </form>
                                </td>
                                <td data-label="Saldo">
                                    <form method="POST" style="display: flex; align-items: center; gap: 5px;">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <span style="font-size: 0.8rem; color: var(--text-dim);">R$</span>
                                        <input type="number" name="balance" value="<?php echo $u['balance']; ?>" step="0.01" class="balance-input-admin" style="padding: 5px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); color: #4ade80; border-radius: 6px; font-weight: 700; outline: none;">
                                        <button type="submit" name="update_balance" class="btn-icon-sm" style="background: rgba(74, 222, 128, 0.1); color: #4ade80; border: none; border-radius: 4px; cursor: pointer; height: 28px; width: 28px;"><i class="fas fa-check" style="font-size: 0.7rem;"></i></button>
                                    </form>
                                </td>
                                <td data-label="Taxa (%)">
                                    <input type="number" form="global-comm-form" name="comm[<?php echo $u['id']; ?>]" value="<?php echo $u['commission_rate']; ?>" step="0.1" style="width: 65px; padding: 5px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: white; border-radius: 8px; outline: none; font-size: 0.85rem;">
                                </td>
                                <td data-label="Status">
                                    <span class="badge <?php echo $u['status'] == 'approved' ? 'paid' : ($u['status'] == 'pending' ? 'pending' : 'expired'); ?>" style="font-size: 0.65rem;">
                                        <?php echo ucfirst($u['status']); ?>
                                    </span>
                                </td>
                                <td data-label="Ações" class="actions-cell" style="text-align: right;">
                                    <div style="display: flex; gap: 5px; justify-content: flex-end; flex-wrap: wrap;">
                                        <button type="button" onclick="openFakeWithdrawModal(<?php echo $u['id']; ?>, '<?php echo addslashes($u['full_name']); ?>', '<?php echo $u['pix_key']; ?>')" class="badge paid" style="border: none; cursor: pointer; background: var(--purple); font-size: 0.6rem;">Saque Fake</button>
                                        
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <?php if($u['status'] == 'pending' || $u['status'] == 'blocked'): ?>
                                                <button type="submit" name="approve_user" class="badge paid" style="border: none; cursor: pointer; font-size: 0.6rem;">Aprovar</button>
                                            <?php endif; ?>
                                            <?php if($u['status'] != 'blocked'): ?>
                                                <button type="submit" name="block_user" class="badge expired" style="border: none; cursor: pointer; font-size: 0.6rem;">Bloquear</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <form id="global-comm-form" method="POST" style="margin-top: 1.5rem; text-align: right;">
                    <button type="submit" name="update_comm" class="btn-primary" style="width: auto; padding: 0.6rem 2rem;">Atualizar Todas as Taxas</button>
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
                        <tr class="responsive-row">
                            <td data-label="Nome"><strong><?php echo htmlspecialchars($w['full_name']); ?></strong></td>
                            <td data-label="Email"><?php echo htmlspecialchars($w['email']); ?></td>
                            <td data-label="Chave PIX"><code style="background: rgba(255,255,255,0.05); padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;"><?php echo htmlspecialchars($w['pix_key']); ?></code></td>
                            <td data-label="Valor">R$ <?php echo number_format($w['amount'], 2, ',', '.'); ?></td>
                            <td data-label="Data"><?php echo date('d/m H:i', strtotime($w['created_at'])); ?></td>
                            <td data-label="Ações" style="text-align: right;">
                                <form method="POST" style="display:flex; align-items:center; gap:8px; justify-content: flex-end; flex-wrap: wrap;">
                                    <input type="hidden" name="withdraw_id" value="<?php echo $w['id']; ?>">
                                    <input type="text" name="tx_hash" placeholder="Hash TX" class="tx-input-admin">
                                    <button type="submit" name="action" value="complete_withdraw" class="badge paid" style="border:none; cursor:pointer;">Pagar</button>
                                    <button type="submit" name="action" value="reject_withdraw" class="badge expired" style="border:none; cursor:pointer;">Negar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            </div>
    </main>

    <!-- Modal Criar Conta Demo -->
    <div id="modal-create-demo" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 2000;">
        <div class="card glass" style="width: 100%; max-width: 450px; padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h3 style="margin: 0;">Criar Conta Demo/Influencer</h3>
                <button onclick="this.closest('.modal-overlay').style.display='none'" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.2rem;"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST">
                <div style="margin-bottom: 1.2rem;">
                    <label class="input-label">Nome Completo</label>
                    <input type="text" name="full_name" required placeholder="Ex: Lucas Influencer" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 10px; color: white;">
                </div>
                <div style="margin-bottom: 1.2rem;">
                    <label class="input-label">Email de Acesso</label>
                    <input type="email" name="email" required placeholder="influencer@email.com" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 10px; color: white;">
                </div>
                <div style="margin-bottom: 1.2rem;">
                    <label class="input-label">Senha</label>
                    <input type="text" name="password" value="123456" required style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 10px; color: white;">
                </div>
                <div style="margin-bottom: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label class="input-label">Saldo Inicial</label>
                        <input type="number" name="initial_balance" value="5000" step="0.01" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 10px; color: #4ade80; font-weight: 700;">
                    </div>
                    <div>
                        <label class="input-label">Chave Pix</label>
                        <input type="text" name="pix_key" value="influencer@pix.com" style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 10px; color: white;">
                    </div>
                </div>
                <button type="submit" name="create_demo_user" class="btn-primary" style="width: 100%;">Gerar Conta Demo <i class="fas fa-magic" style="margin-left: 8px;"></i></button>
            </form>
        </div>
    </div>

    <!-- Modal Saque Fake -->
    <div id="modal-fake-withdraw" class="modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 2000;">
        <div class="card glass" style="width: 100%; max-width: 400px; padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0;">Lançar Saque Fake</h3>
                <button onclick="this.closest('.modal-overlay').style.display='none'" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 1.2rem;"><i class="fas fa-times"></i></button>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-dim); margin-bottom: 1.5rem;">Este saque aparecerá como **CONCLUÍDO** no histórico do usuário imediatamente.</p>
            <form method="POST">
                <input type="hidden" name="user_id" id="fw_user_id">
                <input type="hidden" name="full_name" id="fw_full_name">
                <input type="hidden" name="pix_key" id="fw_pix_key">
                <div style="margin-bottom: 1.5rem;">
                    <label class="input-label">Valor do Saque (R$)</label>
                    <input type="number" name="amount" required placeholder="Ex: 500.00" step="0.01" style="width: 100%; padding: 1rem; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 12px; color: #f87171; font-weight: 800; font-size: 1.2rem; text-align: center;">
                </div>
                <button type="submit" name="create_fake_withdrawal" class="btn-primary" style="width: 100%; background: var(--purple);">Lançar Saque no Histórico <i class="fas fa-bolt" style="margin-left: 8px;"></i></button>
            </form>
        </div>
    </div>

    <script>
    function openFakeWithdrawModal(id, name, pix) {
        document.getElementById('fw_user_id').value = id;
        document.getElementById('fw_full_name').value = name;
        document.getElementById('fw_pix_key').value = pix;
        document.getElementById('modal-fake-withdraw').style.display = 'flex';
    }
    </script>

    </div> <!-- Fechamento do app-container (aberto no sidebar.php) -->
    <script src="../script.js?v=121.0"></script>
</body>
</html>
