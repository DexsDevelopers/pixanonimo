<?php
// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self' https: 'unsafe-inline' 'unsafe-eval'; img-src 'self' https: data:; font-src 'self' https: data:;");

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'includes/db.php';

// affiliate tracking logic
if (isset($_GET['ref'])) {
    $refToken = substr(strip_tags($_GET['ref']), 0, 32);
    // Verificar se o token existe no banco
    $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_token = ?");
    $stmt->execute([$refToken]);
    if ($stmt->fetch()) {
        setcookie('ghost_pix_ref', $refToken, time() + (86400 * 30), "/"); // 30 dias
    }
}

// Removemos o redirecionamento automático para permitir que usuários logados vejam a VSL se desejarem.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#080808">
    <title>Ghost Pix - Receba com Total Blindagem e Privacidade</title>
    <link rel="stylesheet" href="style.css?v=117.0">
    <link rel="stylesheet" href="css/mobile-menu.css?v=107.0">
    <style>
        /* Force dark theme even if CSS is cached */
        .lp-body { background: #000 !important; color: #fff !important; }
        .lp-hero-bg { display: none !important; }
        #canvas-3d { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; opacity: 0.6; }
        
        /* Fail-safe Mobile Menu Styles */
        @media (max-width: 768px) {
            .btn-lp-outline-sm, .btn-lp-primary-sm {
                text-decoration: none !important;
                padding: 8px 12px !important;
                border-radius: 8px !important;
                font-size: 0.75rem !important;
                font-weight: 700 !important;
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }
            .btn-lp-outline-sm {
                border: 1px solid rgba(255, 255, 255, 0.3) !important;
                color: #fff !important;
                background: rgba(255, 255, 255, 0.05) !important;
            }
            .btn-lp-primary-sm {
                background: #4ade80 !important;
                color: #000 !important;
                border: 1px solid #4ade80 !important;
            }
        }
    </style>
    <!-- SEO & Premium Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    
    <!-- Structured Data (SEO) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FinancialService",
      "name": "Ghost Pix",
      "description": "Receba com total blindagem e privacidade através de tecnologia PIX anônima.",
      "url": "https://pixghost.site/",
      "logo": "https://pixghost.site/assets/logo.png"
    }
    </script>
</head>
<body class="lp-body">
    <canvas id="canvas-3d"></canvas>
    <div class="lp-hero-bg" style="display: none;"></div>

    <!-- Navbar -->
    <nav class="lp-navbar">
        <div class="logo">
            <img src="logo_premium.png?v=107.0" class="logo-img" alt="Ghost Logo">
            <span class="logo-text">GHOST<span> PIX</span></span>
        </div>
        
        <!-- Desktop Links -->
        <div class="lp-nav-links desktop-only">
            <a href="#vsl" class="lp-nav-link">O SISTEMA</a>
            <a href="api-docs.php" class="lp-nav-link" style="color: var(--green); font-weight: 700;">API & DEV</a>
            <a href="#faq" class="lp-nav-link">FAQ</a>
            <a href="suporte.php" class="lp-nav-link">CONTATO</a>
        </div>

        <div class="lp-auth-buttons desktop-only">
            <?php if(isLoggedIn()): ?>
                <a href="dashboard.php" class="btn-lp-primary">PAINEL</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn-lp-outline-sm">ENTRAR</a>
                <a href="auth/register.php" class="btn-lp-primary-sm">CRIAR CONTA</a>
            <?php endif; ?>
        </div>

        <!-- Mobile Toggle -->
        <button class="lp-menu-toggle mobile-only" id="menuToggle" aria-label="Abrir Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div class="lp-mobile-menu" id="mobileMenu">
        <div class="lp-mobile-menu-content">
            <div class="lp-mobile-menu-header">
                <div class="logo">
                    <span class="logo-text">GHOST<span> PIX</span></span>
                </div>
                <button class="lp-menu-close" id="menuClose">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav class="lp-mobile-nav">
                <a href="index.php" class="mobile-nav-link"><i class="fas fa-home"></i> INÍCIO</a>
                <a href="#vsl" class="mobile-nav-link"><i class="fas fa-rocket"></i> O SISTEMA</a>
                <a href="api-docs.php" class="mobile-nav-link" style="color: var(--green);"><i class="fas fa-code"></i> API & DEV</a>
                <a href="#faq" class="mobile-nav-link"><i class="fas fa-question-circle"></i> FAQ</a>
                <a href="suporte.php" class="mobile-nav-link"><i class="fas fa-headset"></i> SUPORTE</a>
            </nav>

            <div class="lp-mobile-auth">
                <?php if(isLoggedIn()): ?>
                    <a href="dashboard.php" class="btn-lp-primary-full">
                        <i class="fas fa-th-large"></i> ACESSAR MEU PAINEL
                    </a>
                <?php else: ?>
                    <a href="auth/login.php" class="mobile-nav-link" style="justify-content: center; background: transparent;">
                        Fazer Login
                    </a>
                    <a href="auth/register.php" class="btn-lp-primary-full">
                        CRIAR MINHA CONTA BLINDADA
                    </a>
                <?php endif; ?>
                <p style="text-align: center; font-size: 0.75rem; color: var(--text-3); margin-top: 1.5rem; opacity: 0.7;">
                    <i class="fas fa-shield-alt"></i> Ambiente 100% Criptografado
                </p>
            </div>
        </div>
    </div>

    <!-- Social Proof Badge -->
    <div class="lp-social-proof-wrapper" data-aos="fade-down">
        <div class="lp-social-proof">
            <div class="lp-avatar-group">
                <img src="assets/user1.png" alt="User 1">
                <img src="assets/user2.png" alt="User 2">
                <img src="assets/user3.png" alt="User 3">
                <div class="lp-avatar-more">+</div>
            </div>
            <span class="lp-social-text">+2.348 usuários online agora</span>
        </div>
    </div>

    <!-- Hero Section -->
    <header class="lp-hero">
        <div class="lp-hero-tag" data-aos="fade-down">A ERA DA BLINDAGEM FINANCEIRA CHEGOU</div>
        <h1 class="lp-responsive-title">RECEBA COM TOTAL <br><span class="lp-gradient-text">BLINDAGEM E SIGILO</span></h1>
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
            <a href="dashboard.php" class="btn-lp-primary">Acessar Meu Painel</a>
        <?php else: ?>
            <a href="auth/register.php" class="btn-lp-primary">Quero minha conta blindada</a>
        <?php endif; ?>
    </header>

    <!-- Section: Revenue Models & Dashboard Preview -->
    <section class="lp-section" id="vsl" style="padding-bottom: 0;">
        <div class="lp-section-title" data-aos="fade-up">
            <div class="tag-badge"><i class="fas fa-chart-line"></i> Modelos de Receita</div>
            <h2 class="lp-responsive-title">Opere como um <span class="lp-gradient-text">líder de mercado</span></h2>
            <p>Múltiplos fluxos de receita trabalhando juntos para maximizar seus lucros e sua segurança.</p>
        </div>

        <!-- Tab System -->
        <div class="lp-tabs-container" data-aos="fade-up">
            <div class="lp-tabs">
                <button class="lp-tab active" data-tab="taxas">
                    <i class="fas fa-percentage"></i> Taxas por Transação
                </button>
                <button class="lp-tab" data-tab="recorrencia">
                    <i class="fas fa-calendar-check"></i> Receita Recorrente
                </button>
                <button class="lp-tab" data-tab="premium">
                    <i class="fas fa-bolt"></i> Funcionalidades Premium
                </button>
                <button class="lp-tab" data-tab="multi">
                    <i class="fas fa-layer-group"></i> Multi-Adquirência
                </button>
            </div>
            <div class="lp-tab-indicator"></div>
        </div>

        <!-- Dashboard Live Preview -->
        <div class="dashboard-preview-wrapper" data-aos="zoom-in" data-aos-delay="200">
            <div class="dashboard-preview-window">
                <div class="window-header">
                    <div class="window-dots">
                        <span></span><span></span><span></span>
                    </div>
                    <div class="window-title">Transações ao Vivo</div>
                    <div class="window-status"><span class="pulse-dot"></span> LIVE</div>
                </div>
                <div class="window-content">
                    <div class="dashboard-grid">
                        <div class="main-stats">
                            <div class="stat-card">
                                <span>Faturamento em Tempo Real</span>
                                <h3 id="live-revenue">R$ 9.131,08</h3>
                            </div>
                            <div class="live-transactions" id="transactions-list">
                                <div class="transaction-item">
                                    <div class="t-icon"><i class="fas fa-shopping-cart"></i></div>
                                    <div class="t-info">
                                        <strong>Loja Virtual XYZ</strong>
                                        <span>via PIX</span>
                                    </div>
                                    <div class="t-amt">
                                        <span class="plus">+R$ 72,97</span>
                                        <span class="status-ok"><i class="fas fa-check-circle"></i> OK</span>
                                    </div>
                                    <div class="t-time">Agora</div>
                                </div>
                                <div class="transaction-item">
                                    <div class="t-icon"><i class="fas fa-credit-card"></i></div>
                                    <div class="t-info">
                                        <strong>E-commerce ABC</strong>
                                        <span>via Visa</span>
                                    </div>
                                    <div class="t-amt">
                                        <span class="plus">+R$ 144,52</span>
                                        <span class="status-ok"><i class="fas fa-check-circle"></i> OK</span>
                                    </div>
                                    <div class="t-time">2 min</div>
                                </div>
                            </div>
                        </div>
                        <div class="side-features">
                            <div class="feature-msg-card active">
                                <div class="f-icon orange"><i class="fas fa-check"></i></div>
                                <div class="f-text">
                                    <h4>Receita automática em cada pagamento processado</h4>
                                </div>
                                <i class="fas fa-arrow-right f-arrow"></i>
                            </div>
                            <div class="feature-msg-card">
                                <div class="f-icon orange"><i class="fas fa-check"></i></div>
                                <div class="f-text">
                                    <h4>Escalável conforme seus clientes crescem</h4>
                                </div>
                                <i class="fas fa-arrow-right f-arrow"></i>
                            </div>
                            <div class="feature-msg-card">
                                <div class="f-icon orange-glow"><i class="fas fa-check"></i></div>
                                <div class="f-text">
                                    <h4>Múltiplas formas de pagamento (PIX, cartão, boleto)</h4>
                                </div>
                                <i class="fas fa-arrow-right f-arrow"></i>
                            </div>
                             <div class="feature-msg-card">
                                <div class="f-icon orange"><i class="fas fa-check"></i></div>
                                <div class="f-text">
                                    <h4>Dashboard em tempo real com todas as transações</h4>
                                </div>
                                <i class="fas fa-arrow-right f-arrow"></i>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-footer-stats">
                        <div class="f-stat">
                            <span>Volume</span>
                            <strong>R$ 765.312,22</strong>
                        </div>
                        <div class="f-stat">
                            <span>Comissões</span>
                            <strong class="orange-text">R$ 91.837,46</strong>
                        </div>
                        <div class="f-stat">
                            <span>Aprovação</span>
                            <strong class="green-text">99.8%</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section: Integrated Resources -->
    <section class="lp-section">
        <div class="resources-box" data-aos="fade-up">
            <div class="lp-section-title">
                <h2>Recursos <span class="lp-gradient-text">Integrados</span></h2>
                <p>Todos os produtos trabalham em perfeita harmonia para sua blindagem.</p>
            </div>
            
            <div class="resources-grid">
                <div class="resource-card">
                    <div class="r-check orange"><i class="fas fa-check"></i></div>
                    <div class="r-content">
                        <h4>Pix, cartão e boleto</h4>
                        <span>Ghost Checkout</span>
                    </div>
                </div>
                <div class="resource-card">
                    <div class="r-check orange"><i class="fas fa-check"></i></div>
                    <div class="r-content">
                        <h4>API de disputas</h4>
                        <span>Ghost Shield</span>
                    </div>
                </div>
                <div class="resource-card">
                    <div class="r-check purple"><i class="fas fa-check"></i></div>
                    <div class="r-content">
                        <h4>Layout totalmente personalizável</h4>
                        <span>Ghost Custom</span>
                    </div>
                </div>
                <div class="resource-card">
                    <div class="r-check purple"><i class="fas fa-check"></i></div>
                    <div class="r-content">
                        <h4>Conversão acima da média</h4>
                        <span>Ghost Optimized</span>
                    </div>
                </div>
                <div class="resource-card">
                    <div class="r-check teal"><i class="fas fa-check"></i></div>
                    <div class="r-content">
                        <h4>Recuperação de carrinhos</h4>
                        <span>Ghost Recovery</span>
                    </div>
                </div>
                <div class="resource-card">
                    <div class="r-check teal"><i class="fas fa-check"></i></div>
                    <div class="r-content">
                        <h4>Notificações personalizadas</h4>
                        <span>Ghost Notify</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="lp-section" id="faq">
        <div class="lp-section-title" data-aos="fade-up">
            <h2>Perguntas Frequentes</h2>
            <p>Tire suas dúvidas sobre o funcionamento da nossa tecnologia.</p>
        </div>
        <div class="lp-faq-container" style="max-width: 800px; margin: 0 auto;">
            <div class="lp-faq-item glass-card" data-aos="fade-up">
                <div class="lp-faq-q">Como funciona o anonimato? <i class="fas fa-chevron-down"></i></div>
                <div class="lp-faq-answer">
                    O Ghost Pix utiliza uma camada de <strong>"proxy financeiro"</strong>. Quando alguém te paga, o PIX cai em uma conta blindada e o saldo é creditado <strong>instantaneamente</strong> na sua carteira Ghost, sem expor seu CPF ou dados bancários ao pagador.
                </div>
            </div>
            <div class="lp-faq-item glass-card" data-aos="fade-up" data-aos-delay="100">
                <div class="lp-faq-q">O sistema é legal? <i class="fas fa-chevron-down"></i></div>
                <div class="lp-faq-answer">
                    <strong>Sim.</strong> Operamos dentro das normas de intermediação de pagamentos. Nossa tecnologia foca em <strong>privacidade de dados</strong>, um direito fundamental.
                </div>
            </div>
            <div class="lp-faq-item glass-card" data-aos="fade-up" data-aos-delay="200">
                <div class="lp-faq-q">Quanto tempo leva o saque? <i class="fas fa-chevron-down"></i></div>
                <div class="lp-faq-answer">
                    Os saques são processados com <strong>segurança máxima</strong> e liquidados na sua chave PIX cadastrada em até <strong>2 dias úteis</strong>.
                </div>
            </div>
        </div>
    </section>

    <!-- New Structured Footer -->
    <footer class="lp-footer-v2">
        <div class="lp-footer-container">
            <div class="lp-footer-brand">
                <div class="logo">
                    <img src="logo_premium.png?v=107.0" class="logo-img" alt="Ghost Logo">
                    <span class="logo-text">GHOST<span> PIX</span></span>
                </div>
                <p class="lp-brand-tagline">Feito por uma comunidade para uma comunidade.</p>
                <div class="lp-social-icons">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div class="lp-footer-links-grid">
                <div class="lp-footer-col">
                    <h4>Atalhos</h4>
                    <a href="auth/register.php">Criar conta</a>
                    <a href="auth/login.php">Login</a>
                    <a href="#">Recuperar senha</a>
                </div>
                <div class="lp-footer-col">
                    <h4>Legal</h4>
                    <a href="termos.php">Termos de Uso</a>
                    <a href="privacidade.php">Política de Privacidade</a>
                    <a href="aviso-crypto.php">Aviso Crypto</a>
                </div>
                <div class="lp-footer-col">
                    <h4>Fale conosco</h4>
                    <a href="suporte.php">Suporte</a>
                    <a href="mailto:suporte@ghostpix.site">suporte@ghostpix.site</a>
                </div>
            </div>

        </div>
        <div class="lp-footer-bottom">
            <p>© 2026 GHOST PIX - Todos os direitos reservados</p>
            <p class="lp-footer-cnpj">CNPJ: 00.000.000/0001-00, com sede em São Paulo, SP.</p>
        </div>
    </footer>

    <script>
        // Mobile Menu Logic
        const menuToggle = document.getElementById('menuToggle');
        const menuClose = document.getElementById('menuClose');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileLinks = document.querySelectorAll('.mobile-nav-link');

        function toggleMenu() {
            mobileMenu.classList.toggle('active');
            document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : 'auto';
        }

        menuToggle.addEventListener('click', toggleMenu);
        menuClose.addEventListener('click', toggleMenu);

        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
        });

        // FAQ Toggle
        document.querySelectorAll('.lp-faq-item').forEach(item => {
            const q = item.querySelector('.lp-faq-q');
            q.onclick = () => {
                const isOpen = item.classList.contains('active');
                
                // Close all
                document.querySelectorAll('.lp-faq-item').forEach(el => el.classList.remove('active'));
                
                if (!isOpen) {
                    item.classList.add('active');
                }
            };
        });

        // Tabs Logic
        document.querySelectorAll('.lp-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.lp-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                // Optional: add tab content switching logic if needed
                // Currently just visual feedback as requested
            });
        });

        // Live Dashboard Simulation
        const transactionsList = document.getElementById('transactions-list');
        const revenueEl = document.getElementById('live-revenue');
        let currentRevenue = 9131.08;

        const stores = ["Loja Virtual XYZ", "E-commerce ABC", "Ghost Store", "VIP Member #12", "Marketplace Pro"];
        const paymentMethods = ["via PIX", "via Cartão", "via Boleto", "via Bitcoin"];

        function createTransaction() {
            const store = stores[Math.floor(Math.random() * stores.length)];
            const method = paymentMethods[Math.floor(Math.random() * paymentMethods.length)];
            const amt = (Math.random() * 200 + 50).toFixed(2);
            
            const item = document.createElement('div');
            item.className = 'transaction-item';
            item.style.animation = 'slideInRight 0.5s var(--ease) both';
            
            item.innerHTML = `
                <div class="t-icon"><i class="fas fa-shopping-cart"></i></div>
                <div class="t-info">
                    <strong>${store}</strong>
                    <span>${method}</span>
                </div>
                <div class="t-amt">
                    <span class="plus">+R$ ${amt.replace('.', ',')}</span>
                    <span class="status-ok"><i class="fas fa-check-circle"></i> OK</span>
                </div>
                <div class="t-time">Agora</div>
            `;

            transactionsList.insertBefore(item, transactionsList.firstChild);
            
            // Keep only 3 items
            if (transactionsList.children.length > 3) {
                transactionsList.removeChild(transactionsList.lastChild);
            }

            // Update Revenue
            currentRevenue += parseFloat(amt);
            revenueEl.innerText = `R$ ${currentRevenue.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`;
        }

        setInterval(createTransaction, 4000);

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
    <!-- Scripts: Three.js, GSAP, AOS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    
    <script>
        // AOS Initialization
        AOS.init({ duration: 1000, once: true });

        // Three.js Abstract Background
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ 
            canvas: document.getElementById('canvas-3d'), 
            alpha: true, 
            antialias: false, // Performance optimization
            powerPreference: "high-performance"
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2)); // Performance optimization
        renderer.setSize(window.innerWidth, window.innerHeight);

        const particlesGeometry = new THREE.BufferGeometry();
        const counts = 1000; // Reduced for performance
        const positions = new Float32Array(counts * 3);

        for(let i = 0; i < counts * 3; i++) {
            positions[i] = (Math.random() - 0.5) * 10;
        }

        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        const particlesMaterial = new THREE.PointsMaterial({ size: 0.015, color: 0x4ade80, transparent: true, opacity: 0.8 });
        const particles = new THREE.Points(particlesGeometry, particlesMaterial);
        scene.add(particles);

        camera.position.z = 3;

        function animate() {
            requestAnimationFrame(animate);
            particles.rotation.y += 0.001;
            particles.rotation.x += 0.0005;
            renderer.render(scene, camera);
        }
        animate();

        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });

        // Mouse interaction
        document.addEventListener('mousemove', (e) => {
            const x = (e.clientX / window.innerWidth - 0.5) * 0.5;
            const y = (e.clientY / window.innerHeight - 0.5) * 0.5;
            gsap.to(particles.rotation, { y: x, x: y, duration: 2 });
        });

        // FAQ Toggle Logic
        document.querySelectorAll('.lp-faq-item').forEach(item => {
            item.addEventListener('click', () => {
                const answer = item.querySelector('.lp-faq-answer');
                const icon = item.querySelector('i.fa-chevron-down');
                const isOpen = answer.style.display === 'block';
                
                // Close all others
                document.querySelectorAll('.lp-faq-answer').forEach(a => a.style.display = 'none');
                document.querySelectorAll('.lp-faq-item i.fa-chevron-down').forEach(i => i.style.transform = 'rotate(0deg)');

                if (!isOpen) {
                    answer.style.display = 'block';
                    icon.style.transform = 'rotate(180deg)';
                }
            });
        });
    </script>
</body>
</html>

