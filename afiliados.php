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
$stmt = $pdo->prepare("SELECT full_name, created_at FROM users WHERE affiliate_id = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$referrals = $stmt->fetchAll();

// Buscar ganhos totais de comissão (Log simplificado baseado no saldo atual para demonstração)
// Em um sistema real, teríamos uma tabela para histórico de comissões
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Afiliados - Ghost Pix</title>
    <link rel="stylesheet" href="style.css?v=18.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-container">
        <!-- Sidebar já existente ou similar -->
        <aside class="sidebar">
            <div class="logo">
                <span class="logo-text">GHOST<span> PIX</span></span>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="transactions.php" class="nav-item"><i class="fas fa-exchange-alt"></i> Transações</a>
                <a href="afiliados.php" class="nav-item active"><i class="fas fa-users"></i> Afiliados</a>
                <a href="perfil.php" class="nav-item"><i class="fas fa-user-circle"></i> Meu Perfil</a>
                <a href="auth/logout.php" class="nav-item logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h1>Painel de Afiliados</h1>
                <div class="user-badge">
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                </div>
            </header>

            <div class="stats-grid">
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
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Taxa de Comissão</h3>
                        <p>10% s/ Lucro</p>
                    </div>
                </div>
            </div>

            <section class="api-section card-glass" style="margin-top: 2rem;">
                <h2>Seu Link de Indicação</h2>
                <p>Compartilhe este link para ganhar comissões automáticas sobre cada transação dos seus indicados.</p>
                <div class="api-key-container" style="margin-top: 1.5rem;">
                    <code id="refLink" style="word-break: break-all;"><?php echo $ref_link; ?></code>
                    <button class="btn-lp-primary-sm" onclick="copyRefLink()">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
                </div>
            </section>

            <section class="transactions-section" style="margin-top: 2rem;">
                <h2>Registros de Indicações</h2>
                <div class="table-container shadow-v2">
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
                                                <div class="avatar-sm"><?php echo strtoupper(substr($ref['full_name'], 0, 1)); ?></div>
                                                <span><?php echo htmlspecialchars($ref['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($ref['created_at'])); ?></td>
                                        <td><span class="badge badge-success">Ativo</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 3rem; color: var(--text-3);">
                                        Nenhuma indicação encontrada. Comece a compartilhar seu link!
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
    function copyRefLink() {
        const link = document.getElementById('refLink').innerText;
        navigator.clipboard.writeText(link).then(() => {
            alert('Link copiado para a área de transferência!');
        });
    }
    </script>
</body>
</html>
