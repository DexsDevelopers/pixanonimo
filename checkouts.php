<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];

// Obter os checkouts
$stmt = $pdo->prepare("SELECT * FROM checkouts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$checkouts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#000000">
    <title>Meus Checkouts - Ghost Pix</title>
    <link rel="stylesheet" href="style.css?v=125.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .checkout-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            transition: all 0.3s ease;
        }
        .checkout-card:hover {
            border-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.5);
        }
        .checkout-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .checkout-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-1);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .checkout-slug {
            font-size: 0.85rem;
            color: var(--text-3);
            background: rgba(255,255,255,0.05);
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            margin-top: 8px;
            display: inline-block;
        }
        .checkout-badge {
            background: rgba(37, 211, 102, 0.1);
            color: #25d366;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .checkout-badge.inactive {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-3);
        }
        .checkout-actions {
            display: flex;
            gap: 10px;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
        .btn-checkout-action {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-edit-chk {
            background: rgba(255,255,255,0.05);
            color: var(--text-1);
            border: 1px solid var(--border);
        }
        .btn-edit-chk:hover {
            background: rgba(255,255,255,0.1);
        }
        .btn-copy-chk {
            background: var(--accent);
            color: #000;
            border: none;
            font-weight: 600;
        }
        .btn-copy-chk:hover {
            opacity: 0.9;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-card);
            border-radius: 16px;
            border: 1px dashed var(--border);
            margin-top: 2rem;
        }
        .empty-icon {
            font-size: 3rem;
            color: var(--text-3);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="top-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1>Meus Checkouts 🛒</h1>
                <p>Crie páginas de pagamento transparentes personalizadas.</p>
            </div>
            <a href="checkout_builder.php" class="btn-primary" style="padding: 12px 24px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                <i class="fas fa-plus"></i> Novo Checkout
            </a>
        </header>

        <?php if (empty($checkouts)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-store-slash"></i></div>
                <h3>Nenhum checkout criado</h3>
                <p style="color: var(--text-2); margin: 10px 0 20px;">Você ainda não criou nenhum link de pagamento personalizado.</p>
                <a href="checkout_builder.php" class="btn-primary" style="padding: 12px 24px; text-decoration: none; display: inline-block;">
                    Criar meu primeiro Checkout
                </a>
            </div>
        <?php else: ?>
            <div class="checkout-grid">
                <?php foreach ($checkouts as $checkout): 
                    // Em um cenário real, substituiremos localhost pelo domínio real configurado em um config ou pegaremos da URL
                    $serverName = $_SERVER['HTTP_HOST'];
                    $baseUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$serverName" . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                    $checkoutUrl = $baseUri . "/c/" . urlencode($checkout['slug']);
                ?>
                <div class="checkout-card">
                    <div class="checkout-header">
                        <div>
                            <h3 class="checkout-title"><?php echo htmlspecialchars($checkout['title']); ?></h3>
                            <div class="checkout-slug">/c/<?php echo htmlspecialchars($checkout['slug']); ?></div>
                        </div>
                        <?php if($checkout['active']): ?>
                            <span class="checkout-badge">Ativo</span>
                        <?php else: ?>
                            <span class="checkout-badge inactive">Inativo</span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="font-size: 0.8rem; color: var(--text-3); display: flex; align-items: center; gap: 5px;">
                        <i class="fas fa-palette"></i> Cor: <span style="display:inline-block; width:12px; height:12px; border-radius:3px; background:<?php echo htmlspecialchars($checkout['primary_color']); ?>;"></span>
                    </div>

                    <div class="checkout-actions">
                        <a href="checkout_builder.php?id=<?php echo $checkout['id']; ?>" class="btn-checkout-action btn-edit-chk">
                            <i class="fas fa-pen"></i> Editar
                        </a>
                        <button class="btn-checkout-action btn-copy-chk copy-url-btn" data-url="<?php echo htmlspecialchars($checkoutUrl); ?>">
                            <i class="fas fa-copy"></i> Copiar Link
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
    
    <script>
        document.querySelectorAll('.copy-url-btn').forEach(button => {
            button.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                navigator.clipboard.writeText(url).then(() => {
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                    this.style.background = '#25d366';
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.style.background = 'var(--accent)';
                    }, 2000);
                }).catch(err => {
                    alert('Erro ao copiar URL!');
                });
            });
        });

        // Mobile sidebar toggle fallback if not in script.js
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (menuToggle && sidebar && overlay) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }
    </script>
</body>
</html>
