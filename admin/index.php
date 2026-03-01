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

    <div class="card glass full-width">
        <h3>Gerenciar Usuários</h3>
        <form method="POST">
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Comissão (%)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td>#<?php echo $u['id']; ?></td>
                        <td><?php echo $u['email']; ?></td>
                        <td>
                            <span class="badge <?php echo $u['status'] == 'approved' ? 'paid' : 'sent'; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td>
                            <input type="number" name="comm[<?php echo $u['id']; ?>]" value="<?php echo $u['commission_rate']; ?>" step="0.1" style="width: 80px; padding: 5px;">
                        </td>
                        <td>
                            <?php if($u['status'] != 'approved'): ?>
                                <a href="?action=approve&id=<?php echo $u['id']; ?>" class="badge paid" style="text-decoration:none">Aprovar</a>
                            <?php endif; ?>
                            <?php if($u['status'] != 'blocked'): ?>
                                <a href="?action=block&id=<?php echo $u['id']; ?>" class="badge" style="background:var(--danger); color:white; text-decoration:none">Bloquear</a>
                            <?php endif; ?>
                        </td>
    <main>
        <div class="card glass full-width">
            <h3>Gerenciar Usuários</h3>
            <form method="POST">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Comissão (%)</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td><?php echo $u['email']; ?></td>
                            <td>
                                <span class="badge <?php echo $u['status'] == 'approved' ? 'paid' : 'sent'; ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </td>
                            <td>
                                <input type="number" name="comm[<?php echo $u['id']; ?>]" value="<?php echo $u['commission_rate']; ?>" step="0.1" style="width: 80px; padding: 5px;">
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
                <button type="submit" name="update_comm" class="btn-primary" style="margin-top: 2rem; width: auto; padding: 0.5rem 2rem;">Salvar Comissões</button>
            </form>
            <!-- Seção de Saques -->
            <div class="card glass" style="margin-top: 2rem;">
                <h3>Solicitações de Saque</h3>
                <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                            <th style="padding: 1rem;">Usuário</th>
                            <th style="padding: 1rem;">Valor</th>
                            <th style="padding: 1rem;">Wallet Liquid</th>
                            <th style="padding: 1rem;">Data</th>
                            <th style="padding: 1rem;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT w.*, u.email FROM withdrawals w JOIN users u ON w.user_id = u.id WHERE w.status = 'pending' ORDER BY w.created_at DESC");
                        while($w = $stmt->fetch()):
                        ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><?php echo $w['email']; ?></td>
                            <td style="padding: 1rem;">R$ <?php echo number_format($w['amount'], 2, ',', '.'); ?></td>
                            <td style="padding: 1rem;"><small><?php echo $w['liquid_address']; ?></small></td>
                            <td style="padding: 1rem;"><?php echo date('d/m/H:i', strtotime($w['created_at'])); ?></td>
                            <td style="padding: 1rem;">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="withdraw_id" value="<?php echo $w['id']; ?>">
                                    <input type="text" name="tx_hash" placeholder="Hash TX (opcional)" style="padding: 2px; font-size: 10px;">
                                    <button type="submit" name="action" value="complete_withdraw" class="badge paid" style="border:none; cursor:pointer;">Concluir</button>
                                    <button type="submit" name="action" value="reject_withdraw" class="badge pending" style="border:none; cursor:pointer;">Negar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
