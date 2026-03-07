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
    <link rel="stylesheet" href="style.css?v=125.0">
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

            <!-- Compact Analytics Grid -->
            <div class="analytics-grid">
                <!-- Indicações Diretas -->
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <span class="stat-label">Indicações Diretas</span>
                    <div class="stat-value"><?php echo count($referrals); ?></div>
                    <div class="stat-sub">Usuários registrados</div>
                </div>

                <!-- Comissão Atual -->
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-percentage"></i></div>
                    <span class="stat-label">Sua Comissão</span>
                    <div class="stat-value">
                        <?php 
                        $affRateStmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'affiliate_commission_rate'");
                        $affRate = $affRateStmt->fetchColumn();
                        echo $affRate ? $affRate : '10';
                        ?>%
                    </div>
                    <div class="stat-sub">Sobre o lucro da taxa</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Referral Link Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title-group">
                            <div class="card-icon"><i class="fas fa-share-nodes"></i></div>
                            <h3 class="card-title">Seu Link de Indicação</h3>
                        </div>
                    </div>
                    
                    <p class="card-hint" style="margin: 1rem 0 0.5rem;">Compartilhe este link para ganhar comissões:</p>
                    <div class="pix-key-box">
                        <input type="text" id="refLink" value="<?php echo $ref_link; ?>" readonly class="pix-key-input" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border);">
                        <button onclick="copyRefLink()" class="btn-icon-sm" title="Copiar"><i class="far fa-copy"></i></button>
                    </div>
                    <p class="card-hint" style="margin-top: 1rem; color: var(--green);">
                        <i class="fas fa-circle-check"></i> Pagamentos automáticos em tempo real
                    </p>
                </div>

                <!-- Info Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title-group">
                            <div class="card-icon"><i class="fas fa-circle-info"></i></div>
                            <h3 class="card-title">Como funciona?</h3>
                        </div>
                    </div>
                    <ul style="margin: 1rem 0; padding-left: 1.2rem; color: var(--text-2); font-size: 0.9rem; line-height: 1.6;">
                        <li>Indique novos usuários pelo seu link exclusivo.</li>
                        <li>Eles se cadastram e começam a transacionar.</li>
                        <li>Você ganha uma porcentagem do lucro da plataforma.</li>
                        <li>O saldo é creditado instantaneamente na sua conta.</li>
                    </ul>
                </div>
            </div>

            <section style="margin-top: 2.5rem;">
                <h3 style="margin-bottom: 1.25rem; font-weight: 600;">Registros de Indicações</h3>
                <div class="card" style="padding: 0; overflow: hidden; border: 1px solid var(--border);">
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>Nome do Indicado</th>
                                <th>Data de Registro</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($referrals): ?>
                                <?php foreach ($referrals as $ref): ?>
                                    <tr class="responsive-row">
                                        <td data-label="Nome do Indicado">
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem; border: 1px solid var(--border);">
                                                    <?php echo strtoupper(substr($ref['full_name'], 0, 1)); ?>
                                                </div>
                                                <span style="font-weight: 500;"><?php echo htmlspecialchars($ref['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td data-label="Data de Registro" style="color: var(--text-3); font-size: 0.85rem;"><?php echo date('d/m/Y H:i', strtotime($ref['created_at'])); ?></td>
                                        <td data-label="Status">
                                            <span class="badge <?php echo $ref['status'] == 'approved' ? 'paid' : 'pending'; ?>" style="font-size: 0.7rem;">
                                                <?php echo $ref['status'] == 'approved' ? 'Ativo' : 'Pendente'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align: center; padding: 3rem; color: var(--text-3);">
                                        <i class="fas fa-users-slash" style="font-size: 1.5rem; display: block; margin-bottom: 0.75rem; opacity: 0.5;"></i>
                                        Nenhuma indicação ainda.
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
            const linkInput = document.getElementById('refLink');
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(linkInput.value).then(() => {
                const btn = document.querySelector('.btn-icon-sm');
                const oldIcon = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check" style="color: var(--green);"></i>';
                setTimeout(() => { btn.innerHTML = oldIcon; }, 2000);
            });
        }
    </script>

    <script src="script.js?v=124.0"></script>
</body>
</html>

