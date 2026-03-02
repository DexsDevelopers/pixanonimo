<?php
require_once 'includes/db.php';
// Removemos o redirecionamento automático para permitir que usuários logados vejam a VSL se desejarem.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#080808">
    <title>Ghost Pix - Receba com Total Blindagem e Privacidade</title>
    <link rel="stylesheet" href="style.css?v=8.2">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="lp-body">
    <div class="lp-hero-bg"></div>

    <!-- Navbar -->
    <nav class="lp-navbar">
        <div class="logo">
            <img src="logo_premium.png?v=8.0" class="logo-img" alt="Ghost Logo">
            <span class="logo-text">Ghost<span> Pix</span></span>
        </div>
        <div class="lp-nav-links">
            <a href="#vsl" class="lp-nav-link">Como Funciona</a>
            <a href="#taxas" class="lp-nav-link">Taxas</a>
            <a href="#faq" class="lp-nav-link">Dúvidas</a>
            <a href="suporte.php" class="lp-nav-link">Suporte</a>
        </div>
        <div class="lp-auth-buttons">
            <?php if(isLoggedIn()): ?>
                <a href="dashboard.php" class="btn-lp-primary">Acessar Painel</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn-lp-outline">Entrar</a>
                <a href="auth/register.php" class="btn-lp-primary">Criar Conta</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="lp-hero">
        <div class="lp-hero-tag">A Era da Blindagem Financeira Chegou</div>
        <h1>Receba com Total <br><span style="color: var(--green);">Blindagem e Sigilo</span></h1>
        <p>Pare de se preocupar com bloqueios judiciais ou exposição de dados. O Ghost Pix é a primeira plataforma de liquidação blindada focada em privacidade absoluta.</p>
        
        <!-- Visual Hero: 3D Floating Glass Cards -->
        <div class="hero-visual-wrapper">
            <div class="floating-card card-1">
                <i class="fas fa-shield-virus"></i>
                <h4>Blindagem Total</h4>
                <p>Seu CPF nunca aparece. Transações 100% anônimas via liquidadora.</p>
                <div style="height: 2px; width: 40px; background: var(--green); border-radius: 10px;"></div>
            </div>
            
            <div class="floating-card card-2">
                <i class="fas fa-university"></i>
                <h4>Anti-Bacen</h4>
                <p>Imunidade a bloqueios judiciais instantâneos.</p>
                <div style="display: flex; gap: 5px; margin-top: 5px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--green);"></span>
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--green); opacity: 0.5;"></span>
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--green); opacity: 0.2;"></span>
                </div>
            </div>

            <div class="floating-card card-3">
                <i class="fas fa-bolt"></i>
                <h4>Webhook Real-time</h4>
                <p>Liquidado e confirmado em 2 segundos. Sem delay.</p>
                <div style="background: rgba(255,255,255,0.05); padding: 5px 10px; border-radius: 8px; font-family: monospace; font-size: 10px; color: var(--green);">status: success</div>
            </div>
        </div>

        <?php if(isLoggedIn()): ?>
            <a href="dashboard.php" class="btn-lp-primary" style="padding: 1.2rem 3rem; font-size: 1.1rem;">Acessar Meu Painel</a>
        <?php else: ?>
            <a href="auth/register.php" class="btn-lp-primary" style="padding: 1.2rem 3rem; font-size: 1.1rem;">Quero minha conta blindada</a>
        <?php endif; ?>
    </header>

    <!-- Features Section -->
    <section class="lp-section">
        <div class="lp-section-title">
            <h2>Por que Ghost Pix?</h2>
            <p>Segurança impenetrável para o seu fluxo de caixa.</p>
        </div>
        <div class="lp-feature-grid">
            <div class="lp-feature-card">
                <div class="lp-feature-icon"><i class="fas fa-user-secret"></i></div>
                <h3>Privacidade Total</h3>
                <p>Seus dados pessoais nunca são expostos no checkout. Quem paga vê apenas a nossa liquidadora parceira.</p>
            </div>
            <div class="lp-feature-card">
                <div class="lp-feature-icon"><i class="fas fa-shield-halved"></i></div>
                <h3>Anti-Bloqueio</h3>
                <p>Sistema off-shore imune a ordens de bloqueio nacionais instantâneas. Seu capital está seguro conosco.</p>
            </div>
            <div class="lp-feature-card">
                <div class="lp-feature-icon"><i class="fas fa-bolt"></i></div>
                <h3>Confirmação Real-time</h3>
                <p>API de última geração com confirmação via webhook em menos de 2 segundos. Sem atrasos.</p>
            </div>
        </div>
    </section>

    <!-- Rates Section -->
    <section class="lp-section" id="taxas">
        <div class="lp-section-title">
            <h2>Transparência é Tudo</h2>
            <p>Sem letras miúdas. Taxas fixas e competitivas.</p>
        </div>
        <div class="lp-pricing-grid">
            <div class="lp-pricing-card">
                <h3>Plano Padrão</h3>
                <div class="lp-price">5% <span>/ transação</span></div>
                <ul style="list-style: none; color: var(--text-2); text-align: left; margin-top: 2rem;">
                    <li style="margin-bottom: 1rem;"><i class="fas fa-check" style="color: var(--green);"></i> Aprovação Instantânea</li>
                    <li style="margin-bottom: 1rem;"><i class="fas fa-check" style="color: var(--green);"></i> Blindagem Anti-Bacen</li>
                    <li style="margin-bottom: 1rem;"><i class="fas fa-check" style="color: var(--green);"></i> Saques em até 2 dias</li>
                </ul>
            </div>
            <div class="lp-pricing-card featured">
                <div style="position: absolute; top: 12px; right: -30px; background: var(--green); color: black; padding: 5px 40px; transform: rotate(45deg); font-size: 0.7rem; font-weight: 700;">MAIS USADO</div>
                <h3>Plano Pro</h3>
                <div class="lp-price">3.5% <span>/ transação</span></div>
                <p style="color: var(--text-dim); font-size: 0.8rem; margin-bottom: 2rem;">Para volumes acima de R$ 50k/mês</p>
                <ul style="list-style: none; color: var(--text-2); text-align: left;">
                    <li style="margin-bottom: 1rem;"><i class="fas fa-check" style="color: var(--green);"></i> Gerente de Conta Dedicado</li>
                    <li style="margin-bottom: 1rem;"><i class="fas fa-check" style="color: var(--green);"></i> Saques Prioritários</li>
                    <li style="margin-bottom: 1rem;"><i class="fas fa-check" style="color: var(--green);"></i> API Customizada</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="lp-section" id="faq">
        <div class="lp-section-title">
            <h2>Dúvidas Frequentes</h2>
        </div>
        <div class="lp-faq-container">
            <div class="lp-faq-item">
                <div class="lp-faq-q">O que é a blindagem do Ghost Pix? <i class="fas fa-chevron-down"></i></div>
                <div class="lp-faq-a">A blindagem consiste em processar seus pagamentos através de contas de liquidação de terceiros e estruturas seguras, evitando que o seu CPF/CNPJ apareça diretamente na transação e prevenindo rastreios e bloqueios automáticos.</div>
            </div>
            <div class="lp-faq-item">
                <div class="lp-faq-q">Como recebo o meu dinheiro? <i class="fas fa-chevron-down"></i></div>
                <div class="lp-faq-a">Após o pagamento do cliente, o saldo cai no seu painel Ghost Pix. Você pode solicitar o saque para qualquer chave Pix de sua preferência. O saque é processado via nossas contas blindadas.</div>
            </div>
            <div class="lp-faq-item">
                <div class="lp-faq-q">É legal usar o Ghost Pix? <i class="fas fa-chevron-down"></i></div>
                <div class="lp-faq-a">Sim, operamos como um intermediário de pagamentos tecnológico. Providenciamos as ferramentas para que você tenha privacidade, um direito fundamental. Recomendamos sempre consultar seu contador para obrigações fiscais.</div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="lp-footer">
        <div class="logo" style="justify-content: center; margin-bottom: 1rem;">
            <img src="logo_premium.png?v=8.0" class="logo-img" alt="Ghost Logo">
            <span class="logo-text">Ghost<span> Pix</span></span>
        </div>
        <div class="footer-links">
            <?php if(isLoggedIn()): ?>
                <a href="dashboard.php" class="lp-nav-link">Dashboard</a>
            <?php else: ?>
                <a href="auth/login.php" class="lp-nav-link">Login</a>
                <a href="auth/register.php" class="lp-nav-link">Cadastrar</a>
            <?php endif; ?>
            <a href="suporte.php" class="lp-nav-link">Suporte</a>
        </div>
        <p>© 2026 Ghost Pix. Todos os direitos reservados. Foco em Privacidade e Blindagem.</p>
    </footer>

    <script>
        // FAQ Toggle
        document.querySelectorAll('.lp-faq-q').forEach(q => {
            q.onclick = () => {
                const item = q.parentElement;
                item.classList.toggle('active');
            };
        });

        // Smooth Scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
