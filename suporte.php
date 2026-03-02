<?php
require_once 'includes/db.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ghost Pix - Suporte & FAQ</title>
    <link rel="stylesheet" href="style.css?v=3.9">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .faq-container { max-width: 800px; margin: 0 auto; }
        .faq-item { margin-bottom: 1rem; border: 1px solid var(--glass-border); border-radius: 12px; overflow: hidden; transition: all 0.3s ease; }
        .faq-question { padding: 1.2rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.03); font-weight: 600; }
        .faq-question:hover { background: rgba(255,255,255,0.07); }
        .faq-answer { padding: 0 1.2rem; max-height: 0; overflow: hidden; transition: all 0.3s ease; background: rgba(0,0,0,0.2); font-size: 0.9rem; line-height: 1.6; color: var(--text-dim); }
        .faq-item.active .faq-answer { padding: 1.2rem; max-height: 500px; }
        .faq-item.active .faq-question i { transform: rotate(180deg); }
        .support-contact-card { display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap; }
        .contact-method { flex: 1; min-width: 250px; padding: 1.5rem; text-align: center; border-radius: 16px; border: 1px solid var(--glass-border); transition: transform 0.3s; text-decoration: none; color: white; }
        .contact-method:hover { transform: translateY(-5px); border-color: var(--primary); }
        .contact-method i { font-size: 2rem; margin-bottom: 1rem; color: var(--primary); }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>
    <div class="mobile-header">
        <div class="logo">
            <img src="ghost.jfif" class="logo-img" style="height: 24px;">
            <span class="logo-text" style="font-size: 1.2rem;">Ghost<span> Pix</span></span>
        </div>
        <button class="menu-toggle" id="menu-toggle">☰</button>
    </div>

    <div class="app-container">
        <aside class="sidebar">
            <div class="logo">
                <img src="ghost.jfif" class="logo-img">
                <span class="logo-text">Ghost<span> Pix</span></span>
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item">📊 Dashboard</a>
                <a href="sacar.php" class="nav-item">💸 Sacar</a>
                <a href="perfil.php" class="nav-item">👤 Perfil</a>
                <a href="suporte.php" class="nav-item active">🎧 Suporte</a>
                <?php if(isAdmin()): ?>
                    <a href="admin/index.php" class="nav-item">🛡️ Admin</a>
                <?php endif; ?>
                <a href="auth/logout.php" class="nav-item">🚪 Sair</a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="top-header">
                <div>
                    <h1>Central de Suporte</h1>
                    <p style="color: var(--text-dim);">Dúvidas frequentes e atendimento humano</p>
                </div>
                <a href="index.php" class="badge sent" style="text-decoration:none">Voltar ao Dashboard</a>
            </header>

            <div class="faq-container">
                <div class="card glass" style="margin-bottom: 2rem;">
                    <h3>Sobre a Plataforma</h3>
                    <p style="color: var(--text-dim); line-height: 1.6;">
                        O **Ghost Pix** é uma solução de pagamentos focada em rapidez e facilidade. 
                        Nossa plataforma permite gerar cobranças via Pix com confirmação instantânea 
                        e gestão simplificada de recebimentos. Oferecemos segurança total e taxas 
                        competitivas para que você foque no que importa: seu negócio.
                    </p>
                </div>

                <h3>Perguntas Frequentes (FAQ)</h3>
                
                <div class="faq-item">
                    <div class="faq-question">Como funciona a geração de Pix? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">Basta inserir o valor desejado no Dashboard e clicar em "Gerar QR Code". O sistema criará um Pix dinâmico com expiração de 20 minutos. Assim que o pagamento for concluído, o saldo cairá na sua conta Ghost Pix.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Qual o prazo para saques? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">Os saques são processados manualmente pela nossa equipe administrativa. O prazo médio é de até 2 dias úteis, garantindo a segurança de todas as transações.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Por que meu Pix foi rejeitado? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">Geralmente ocorre por expiração (passou dos 20 minutos) ou por tentativa de pagamento de um valor idêntico ao de outra cobrança pendente em curto período (colisão de valores). Sempre verifique o tempo restante.</div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">Como configurar minha chave Pix? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">Acesse o menu "Perfil" no painel lateral. Lá você poderá atualizar seu nome e sua chave Pix para recebimento de saques. Certifique-se de que a chave está correta para evitar atrasos.</div>
                </div>

                <div class="card glass" style="margin-top: 3rem; text-align: center;">
                    <h3>Ainda precisa de ajuda?</h3>
                    <p style="color: var(--text-dim); margin-bottom: 1.5rem;">Fale diretamente com nossa equipe de suporte humano.</p>
                    
                    <div class="support-contact-card">
                        <a href="https://wa.me/5551996148568" target="_blank" class="contact-method glass">
                            <i class="fab fa-whatsapp"></i>
                            <h4>WhatsApp</h4>
                            <p style="font-size: 0.8rem; color: var(--text-dim);">+55 51 99614-8568</p>
                        </a>
                        <a href="mailto:empresatokio@gmail.com" class="contact-method glass">
                            <i class="far fa-envelope"></i>
                            <h4>E-mail</h4>
                            <p style="font-size: 0.8rem; color: var(--text-dim);">empresatokio@gmail.com</p>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle FAQ
        document.querySelectorAll('.faq-question').forEach(q => {
            q.addEventListener('click', () => {
                const item = q.parentElement;
                item.classList.toggle('active');
            });
        });

        // Mobile Menu
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.add('active');
                overlay.classList.add('active');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }
    </script>
</body>
</html>
