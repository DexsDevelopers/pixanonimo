<?php
session_start();
require_once '../includes/db.php';

if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Lógica de Aprovação/Bloqueio
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    $status = ($action == 'approve') ? 'approved' : 'blocked';
    
    $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND is_admin = 0");
    $stmt->execute([$status, $id]);
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
                $pdo->commit();
            }
            header("Location: index.php?success=1");
            exit;
        }
    }
}

$users = $pdo->query("SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Admin - PixAnônimo</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body class="main-content">
    <header class="top-header">
        <h1>Painel Administrativo</h1>
        <a href="../index.php" class="badge sent">Voltar ao App</a>
    </header>

    <main class="container">
        <div class="card glass full-width">
            <h3>Gerenciar Usuários</h3>
            <form method="POST">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Saldo (R$)</th>
                            <th>Comissão (%)</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td><?php echo $u['email']; ?></td>
                            <td><code><?php echo $u['pix_key']; ?></code></td>
                            <td>
                                <span class="badge <?php echo $u['status'] == 'approved' ? 'paid' : 'sent'; ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </td>
                            <td>R$ <?php echo number_format($u['balance'], 2, ',', '.'); ?></td>
                            <td>
                                <input type="number" name="comm[<?php echo $u['id']; ?>]" value="<?php echo $u['commission_rate']; ?>" step="0.1" style="width: 80px; padding: 5px; background: rgba(255,255,255,0.1); border: 1px solid var(--border); color: white; border-radius: 4px;">
                            </td>
                            <td>
                                <?php if($u['status'] != 'approved'): ?>
                                    <a href="?action=approve&id=<?php echo $u['id']; ?>" class="badge paid" style="text-decoration:none">Aprovar</a>
                                <?php endif; ?>
                                <?php if($u['status'] != 'blocked'): ?>
                                    <a href="?action=block&id=<?php echo $u['id']; ?>" class="badge" style="background:var(--danger); color:white; text-decoration:none">Bloquear</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="update_comm" class="btn-primary" style="margin-top: 2rem; width: auto; padding: 0.5rem 2rem;">Salvar Alterações</button>
            </form>
        </div>

        <!-- Seção de Saques -->
        <div class="card glass full-width" style="margin-top: 2rem;">
            <h3>Solicitações de Saque</h3>
            <table class="transaction-table">
                <thead>
                    <tr style="text-align: left;">
                        <th>Usuário</th>
                        <th>Email</th>
                        <th>Chave PIX</th>
                        <th>Valor</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("SELECT w.*, u.email, u.pix_key, u.commission_rate, u.balance FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'pending' ORDER BY w.created_at DESC");
                    while($w = $stmt->fetch()):
                    ?>
                    <tr>
                        <td style="padding: 1rem;"><?php echo $w['email']; ?></td>
                        <td style="padding: 1rem;">R$ <?php echo number_format($w['amount'], 2, ',', '.'); ?></td>
                        <td style="padding: 1rem;"><small><?php echo $w['pix_key']; ?></small></td>
                        <td style="padding: 1rem;"><?php echo date('d/m H:i', strtotime($w['created_at'])); ?></td>
                        <td style="padding: 1rem;">
                            <form method="POST" style="display:inline-flex; align-items:center; gap:5px;">
                                <input type="hidden" name="withdraw_id" value="<?php echo $w['id']; ?>">
                                <input type="text" name="tx_hash" placeholder="Hash TX" style="padding: 5px; font-size: 11px; width: 100px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); color: white; border-radius: 4px;">
                                <button type="submit" name="action" value="complete_withdraw" class="badge paid" style="border:none; cursor:pointer;">Pagar</button>
                                <button type="submit" name="action" value="reject_withdraw" class="badge" style="border:none; cursor:pointer; background:var(--danger); color:white;">Negar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
