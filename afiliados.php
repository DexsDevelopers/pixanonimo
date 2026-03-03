<?php
session_start();
require_once 'includes/db.php';

if (!isLoggedIn()) {
    header("Location: auth/login.php");
    exit;
}

$user = getUser($_SESSION['user_id']);
$ref_link = "https://" . $_SERVER['HTTP_HOST'] . "/index.php?ref=" . $user['referral_token'];

// Buscar indicados
$stmt = $pdo->prepare("SELECT full_name, created_at, status FROM users WHERE affiliate_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$referrals = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#000000">
    <title>Ghost Pix - Afiliados</title>
    <link rel="stylesheet" href="style.css?v=18.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <header class="top-header">
                <div>
                    <h1>Sistema de Afiliados 📢</h1>
                    <p>Compartilhe seu link e ganhe comissões automáticas.</p>
                </div>
            </header>

            <!-- Stats Grid -->
            <div class="analytics-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(74, 222, 128, 0.1); color: var(--green);">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Indicações Diretas</h3>
                        <p><?php echo count($referrals); ?></p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Comissão</h3>
                        <p>10% s/ Lucro</p>
                    </div>
                </div>
            </div>

            <section class="api-section" style="margin-top: 2rem; background: rgba(255,255,255,0.02); padding: 2rem; border-radius: 20px; border: 1px solid var(--border);">
                <h2>Seu Link de Indicação</h2>
                <p style="color: var(--text-2); margin-top: 0.5rem;">Copie o link abaixo e convide novos membros para a plataforma.</p>
                
                <div style="margin-top: 1.5rem; display: flex; gap: 10px; background: #000; padding: 1rem; border-radius: 12px; border: 1px solid var(--border-h);">
                    <code id="refLink" style="color: var(--green); word-break: break-all; flex: 1;"><?php echo $ref_link; ?></code>
                    <button onclick="copyRefLink()" style="background: var(--green); color: #000; border: none; padding: 0 1rem; border-radius: 8px; font-weight: 700; cursor: pointer;">
                        COPIAR
                    </button>
                </div>
            </section>

            <section style="margin-top: 2.5rem;">
                <h2>Registros de Indicações</h2>
                <div class="table-container shadow-v2" style="margin-top: 1.5rem;">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>NOME DO INDICADO</th>
                                <th>DATA DE REGISTRO</th>
                                <th>STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($referrals): ?>
                                <?php foreach ($referrals as $ref): ?>
                                    <tr>
                                        <td>
                                            <div class="user-cell">
                                                <div class="avatar-sm" style="background: var(--border-h);"><?php echo strtoupper(substr($ref['full_name'], 0, 1)); ?></div>
                                                <span><?php echo htmlspecialchars($ref['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($ref['created_at'])); ?></td>
                                        <td><span class="badge badge-success"><?php echo ucfirst($ref['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 4rem; color: var(--text-3);">
                                        <i class="fas fa-users-slash" style="font-size: 2rem; display: block; margin-bottom: 1rem; opacity: 0.5;"></i>
                                        Nenhuma indicação ainda. Compartilhe seu link!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Sidebar Toggle
        const menuToggle = document.getElementById('menu-toggle');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const sidebar = document.querySelector('.sidebar');

        menuToggle.addEventListener('click', () => {
            sidebar.classList.add('active');
            sidebarOverlay.classList.add('active');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });

        function copyRefLink() {
            const link = document.getElementById('refLink').innerText;
            navigator.clipboard.writeText(link).then(() => {
                alert('✓ Link copiado para a área de transferência!');
            });
        }
    </script>
</body>
</html>
