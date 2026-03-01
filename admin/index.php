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

// Lógica de Comissões
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_comm'])) {
    foreach ($_POST['comm'] as $userId => $rate) {
        $stmt = $pdo->prepare("UPDATE users SET commission_rate = ? WHERE id = ?");
        $stmt->execute([(float)$rate, $userId]);
    }
    header("Location: index.php?success=1");
    exit;
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="update_comm" class="btn-primary" style="margin-top: 2rem; width: auto; padding: 0.5rem 2rem;">Salvar Comissões</button>
        </form>
    </div>
</body>
</html>
