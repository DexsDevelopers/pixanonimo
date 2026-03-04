<?php
session_start();
require_once '../includes/db.php';

if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Lógica de gerenciar APIs
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'add_api') {
        $name = $_POST['name'] ?? 'Nova API';
        $key = $_POST['api_key'] ?? '';
        if (!empty($key)) {
            $stmt = $pdo->prepare("INSERT INTO pixgo_apis (name, api_key, status) VALUES (?, ?, 'active')");
            $stmt->execute([$name, $key]);
            header("Location: apis.php?success=1");
            exit;
        }
    }

    if ($action == 'toggle_status') {
        $id = (int)$_POST['id'];
        $currentStatus = $_POST['current_status'];
        $newStatus = ($currentStatus == 'active') ? 'inactive' : 'active';
        
        $stmt = $pdo->prepare("UPDATE pixgo_apis SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
        header("Location: apis.php?success=1");
        exit;
    }

    if ($action == 'delete_api') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM pixgo_apis WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: apis.php?success=1");
        exit;
    }
}

try {
    $apis = $pdo->query("SELECT * FROM pixgo_apis ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    // Se a tabela não existir, vamos tentar criar
    $pdo->exec("CREATE TABLE IF NOT EXISTS pixgo_apis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        api_key VARCHAR(255) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Migrar chave atual se estiver vazia
    $stmt = $pdo->query("SELECT COUNT(*) FROM pixgo_apis");
    if ($stmt->fetchColumn() == 0 && defined('PIXGO_API_KEY')) {
        $stmt = $pdo->prepare("INSERT INTO pixgo_apis (name, api_key, status) VALUES (?, ?, 'active')");
        $stmt->execute(['Chave Principal (Backup)', PIXGO_API_KEY]);
    }
    
    // Tenta buscar novamente
    $apis = $pdo->query("SELECT * FROM pixgo_apis ORDER BY created_at DESC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Gerenciar APIs - Ghost Pix Admin</title>
    <link rel="stylesheet" href="../style.css?v=103.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="top-header">
            <div>
                <h1>Configurações de API 🔌</h1>
                <p>Gerencie múltiplas chaves PixGo para rotação automática.</p>
            </div>
            <?php if (isset($_GET['success'])): ?>
                <div class="badge paid">✓ Atualizado</div>
            <?php endif; ?>
        </header>

        <div class="dashboard-grid" style="grid-template-columns: 1fr 2fr;">
            <!-- Formulário de Adição -->
            <div class="card glass">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon"><i class="fas fa-plus"></i></div>
                        <h3 class="card-title">Nova Chave</h3>
                    </div>
                </div>
                <form method="POST" style="margin-top: 1.5rem;">
                    <input type="hidden" name="action" value="add_api">
                    <div style="margin-bottom: 1rem;">
                        <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Nome Identificador</label>
                        <input type="text" name="name" placeholder="Ex: Conta Principal" required
                               style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem; border-radius: 10px; color: #fff;">
                    </div>
                    <div style="margin-bottom: 1.5rem;">
                        <label class="stat-label" style="display:block; margin-bottom: 0.5rem;">Chave API (PixGo)</label>
                        <input type="text" name="api_key" placeholder="pk_..." required
                               style="width: 100%; background: rgba(0,0,0,0.3); border: 1px solid var(--border); padding: 0.8rem; border-radius: 10px; color: #fff; font-family: monospace;">
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> Adicionar API
                    </button>
                </form>
            </div>

            <!-- Lista de APIs -->
            <div class="card glass">
                <div class="card-header">
                    <div class="card-title-group">
                        <div class="card-icon"><i class="fas fa-list"></i></div>
                        <h3 class="card-title">APIs Cadastradas</h3>
                    </div>
                </div>
                <div class="table-wrap" style="margin-top: 1.2rem;">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($apis)): ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; padding: 2rem; color: var(--text-dim);">Nenhuma chave cadastrada.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach($apis as $api): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; flex-direction:column;">
                                        <span style="font-weight:600;"><?php echo htmlspecialchars($api['name']); ?></span>
                                        <span style="font-size:0.7rem; color:var(--text-dim);"><?php echo 'pk_...' . substr($api['api_key'], -6); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $api['status'] == 'active' ? 'paid' : 'pending'; ?>">
                                        <?php echo $api['status'] == 'active' ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; gap: 8px;">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?php echo $api['id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $api['status']; ?>">
                                            <button type="submit" class="btn-history-action" title="<?php echo $api['status'] == 'active' ? 'Desativar' : 'Ativar'; ?>">
                                                <i class="fas <?php echo $api['status'] == 'active' ? 'fa-toggle-on' : 'fa-toggle-off'; ?>" style="color: <?php echo $api['status'] == 'active' ? 'var(--green)' : 'var(--text-dim)'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir esta API?')">
                                            <input type="hidden" name="action" value="delete_api">
                                            <input type="hidden" name="id" value="<?php echo $api['id']; ?>">
                                            <button type="submit" class="btn-history-action" style="color:var(--danger);">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.1); border-radius: 12px;">
                    <p style="font-size: 0.75rem; color: #f59e0b; line-height: 1.4;">
                        <i class="fas fa-info-circle"></i> O sistema selecionará aleatoriamente qualquer uma das chaves marcadas como <strong>Ativo</strong> para processar novos pagamentos. Se nenhuma estiver ativa, o sistema usará a chave de fallback do <code>config.php</code>.
                    </p>
                </div>
            </div>
        </div>
    </main>
    <script src="../script.js?v=103.0"></script>
</body>
</html>
