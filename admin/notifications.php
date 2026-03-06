<?php
session_start();
require_once '../includes/db.php';

if (!isAdmin()) {
    redirect('../auth/login.php');
}

$success = false;
$error = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['user_id'] != 'all' ? (int)$_POST['user_id'] : null;
    $title = strip_tags($_POST['title']);
    $message = strip_tags($_POST['message']);
    $type = $_POST['type'];

    if (!empty($title) && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $message, $type]);
        $success = "Notificação enviada com sucesso!";
    } else {
        $error = "Preencha todos os campos.";
    }
}

$users = $pdo->query("SELECT id, full_name, email FROM users WHERE is_admin = 0 ORDER BY full_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Ghost Pix - Notificações</title>
    <link rel="stylesheet" href="../style.css?v=117.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="top-header">
            <h1>Central de Notificações</h1>
            <p style="color: var(--text-3); font-size: 0.9rem;">Envie comunicados para seus usuários</p>
        </header>

        <div class="card glass" style="max-width: 600px; margin-top: 2rem;">
            <?php if ($success): ?>
                <div class="badge paid" style="width: 100%; margin-bottom: 1rem; padding: 10px;"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="badge" style="width: 100%; margin-bottom: 1rem; padding: 10px; background: var(--danger); color: white;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-2);">Destino</label>
                    <select name="user_id" style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 12px; color: white; outline: none;">
                        <option value="all">📢 Todos os Usuários</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>">👤 <?php echo $u['full_name']; ?> (<?php echo $u['email']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-2);">Título do Alerta</label>
                    <input type="text" name="title" placeholder="Ex: Manutenção Programada" required style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 12px; color: white; outline: none;">
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-2);">Tipo</label>
                    <select name="type" style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 12px; color: white; outline: none;">
                        <option value="info">🔵 Informativo (Azul)</option>
                        <option value="success">🟢 Sucesso (Verde)</option>
                        <option value="warning">🟡 Aviso (Amarelo)</option>
                        <option value="danger">🔴 Urgente (Vermelho)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-2);">Mensagem</label>
                    <textarea name="message" rows="4" placeholder="Escreva o conteúdo da notificação..." required style="width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 12px; color: white; outline: none;"></textarea>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; padding: 1rem;">
                    <i class="fas fa-paper-plane" style="margin-right: 8px;"></i> Enviar Notificação
                </button>
            </form>
        </div>
    </main>

    <script src="../script.js?v=107.0"></script>
</body>
</html>
