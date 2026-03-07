<?php
require_once '../includes/db.php';

if (!isAdmin()) {
    redirect('../auth/login.php');
}

$success = false;
$error = false;

// Salvar Edição
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_template'])) {
    $slug = $_POST['slug'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    if (!empty($slug) && !empty($subject) && !empty($message)) {
        try {
            $stmt = $pdo->prepare("UPDATE email_templates SET subject = ?, message = ? WHERE slug = ?");
            $stmt->execute([$subject, $message, $slug]);
            $success = "Template atualizado com sucesso!";
        } catch (PDOException $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    } else {
        $error = "Preencha todos os campos.";
    }
}

// Buscar todos os templates
$templates = $pdo->query("SELECT * FROM email_templates ORDER BY slug ASC")->fetchAll();

// Mapeamento Amigável de Nomes
$friendlyNames = [
    'account_approved' => 'Aprovação de Conta',
    'sale_confirmed' => 'Venda Confirmada (PIX Pago)',
    'withdrawal_paid' => 'Saque Pago/Concluido',
    'global_announcement' => 'Avisos Globais (Geral)'
];

// Mapeamento de Variáveis Disponíveis
$availableVars = [
    'account_approved' => '{name}',
    'sale_confirmed' => '{name}, {amount}',
    'withdrawal_paid' => '{name}, {amount}',
    'global_announcement' => '{name}, {title}, {message}'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Ghost Pix - Configurações de E-mail</title>
    <link rel="stylesheet" href="../style.css?v=121.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .template-card {
            margin-bottom: 2rem;
            border: 1px solid var(--border);
            padding: 2rem;
        }
        .var-badge {
            background: rgba(74, 222, 128, 0.1);
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        .input-dark {
            width: 100%;
            padding: 12px;
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: #fff;
            outline: none;
            margin-top: 10px;
        }
        .input-dark:focus { border-color: var(--primary); }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="top-header">
            <h1>Configurações de E-mail</h1>
            <p style="color: var(--text-3); font-size: 0.9rem;">Personalize as mensagens automáticas enviadas via Gmail.</p>
        </header>

        <?php if ($success): ?>
            <div class="badge paid" style="width: 100%; margin-bottom: 1rem; padding: 15px;"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="badge" style="width: 100%; margin-bottom: 1rem; padding: 15px; background: var(--danger); color: white;"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php foreach($templates as $tpl): ?>
            <div class="card glass template-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                    <div>
                        <h3 style="margin: 0; color: var(--primary);"><?php echo $friendlyNames[$tpl['slug']] ?? $tpl['slug']; ?></h3>
                        <p style="font-size: 0.85rem; color: var(--text-3); margin-top: 5px;">Slug: <code><?php echo $tpl['slug']; ?></code></p>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 0.75rem; color: var(--text-3); display: block; margin-bottom: 5px;">Variáveis disponíveis:</span>
                        <?php 
                        $vars = explode(', ', $availableVars[$tpl['slug']] ?? '');
                        foreach($vars as $v): if(!empty($v)): ?>
                            <span class="var-badge"><?php echo $v; ?></span>
                        <?php endif; endforeach; ?>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="slug" value="<?php echo $tpl['slug']; ?>">
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600; color: var(--text-2);">Assunto do E-mail</label>
                        <input type="text" name="subject" value="<?php echo htmlspecialchars($tpl['subject']); ?>" required class="input-dark">
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label style="font-weight: 600; color: var(--text-2);">Mensagem (HTML permitido)</label>
                        <textarea name="message" rows="6" required class="input-dark" style="font-family: 'Outfit', sans-serif; line-height: 1.6;"><?php echo htmlspecialchars($tpl['message']); ?></textarea>
                    </div>

                    <button type="submit" name="save_template" class="btn-primary" style="width: auto; padding: 0.8rem 2rem;">
                        <i class="fas fa-save" style="margin-right: 8px;"></i> Salvar Alterações
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </main>

    <script src="../script.js?v=121.0"></script>
</body>
</html>
