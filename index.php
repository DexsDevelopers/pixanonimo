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
// Removemos o redirecionamento automático para permitir que usuários logados vejam a VSL se desejarem.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#080808">
    <title>Ghost Pix - Receba com Total Blindagem e Privacidade</title>
    <link rel="stylesheet" href="style.css?v=15.0">
    <style>
        /* Force dark theme even if CSS is cached */
        .lp-body { background: #000 !important; color: #fff !important; }
        .lp-hero-bg { display: none !important; }
        #canvas-3d { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; opacity: 0.6; }
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
            <img src="logo_premium.png?v=8.0" class="logo-img" alt="Ghost Logo">
            <span class="logo-text">GHOST<span> PIX</span></span>
        </div>
        <div class="lp-nav-links">
            <a href="#vsl" class="lp-nav-link">O SISTEMA</a>
            <a href="#faq" class="lp-nav-link">FAQ</a>
            <a href="suporte.php" class="lp-nav-link">CONTATO</a>
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

    <!-- Social Proof Badge -->
    <div class="lp-social-proof-wrapper">
        <div class="lp-social-proof">
            <div class="lp-avatar-group">
                <img src="assets/user1.png" alt="User 1">
                <img src="assets/user2.png" alt="User 2">
                <img src="assets/user3.png" alt="User 3">
                <div class="lp-avatar-more">+</div>
            </div>
            <span class="lp-social-text">+2.000 usuários ativos</span>
        </div>
    </div>

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

    <!-- FAQ Section -->
    <section class="lp-section" id="faq">
        <div class="lp-section-title" data-aos="fade-up">
            <h2>Perguntas Frequentes</h2>
            <p>Tire suas dúvidas sobre o funcionamento da nossa tecnologia.</p>
        </div>
        <div class="lp-faq-container" style="max-width: 800px; margin: 0 auto;">
            <div class="lp-faq-item glass-card" data-aos="fade-up" style="margin-bottom: 1rem; padding: 1.5rem; cursor: pointer;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin: 0;">Como funciona o anonimato?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="lp-faq-answer" style="display: none; margin-top: 1rem; color: var(--text-2); font-size: 0.9rem;">
                    O Ghost Pix utiliza uma camada de "proxy financeiro". Quando alguém te paga, o PIX cai em uma conta blindada e o saldo é creditado instantaneamente na sua carteira Ghost, sem expor seu CPF ou dados bancários ao pagador.
                </div>
            </div>
            <div class="lp-faq-item glass-card" data-aos="fade-up" data-aos-delay="100" style="margin-bottom: 1rem; padding: 1.5rem; cursor: pointer;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin: 0;">O sistema é legal?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="lp-faq-answer" style="display: none; margin-top: 1rem; color: var(--text-2); font-size: 0.9rem;">
                    Sim. Operamos dentro das normas de intermediação de pagamentos. Nossa tecnologia foca em privacidade de dados, um direito fundamental, sem descumprir regulamentações vigentes.
                </div>
            </div>
            <div class="lp-faq-item glass-card" data-aos="fade-up" data-aos-delay="200" style="margin-bottom: 1rem; padding: 1.5rem; cursor: pointer;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h4 style="margin: 0;">Quanto tempo leva o saque?</h4>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="lp-faq-answer" style="display: none; margin-top: 1rem; color: var(--text-2); font-size: 0.9rem;">
                    Os saques são prioritários e geralmente liquidados em até 2 horas para sua chave PIX cadastrada, garantindo que seu dinheiro esteja sempre à mão.
                </div>
            </div>
        </div>
    </section>

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


    <!-- Benefits Section -->
    <section class="lp-section" id="beneficios">
        <div class="lp-section-title" data-aos="fade-up">
            <h1>RECEBA COM <span class="lp-gradient-text">BLINDAGEM MILITAR</span> E PRIVACIDADE</h1>
            <p>A única tecnologia PIX com criptografia de ponta a ponta e anonimato garantido por protocolo Ghost.</p>
            <div style="margin-top: 1.5rem; display: flex; align-items: center; gap: 0.8rem; font-size: 0.85rem; color: #4ade80; font-weight: 600;">
                <span style="display: inline-block; width: 8px; height: 8px; background: #4ade80; border-radius: 50%; animation: pulse 1.5s infinite;"></span>
                SISTEMA OPERANDO EM CAPACIDADE MÁXIMA PARA PRIVACIDADE
            </div>
        </div>
        <div class="lp-analytics-grid">
            <div class="lp-card glass-card" data-aos="zoom-in" data-aos-delay="100">
                <div class="lp-card-icon"><i class="fas fa-user-secret"></i></div>
                <h3>Anonimato Total</h3>
                <p>Suas transações são processadas através de camadas de proteção que ocultam seus dados reais de terceiros.</p>
            </div>
            <div class="lp-card glass-card" data-aos="zoom-in" data-aos-delay="200">
                <div class="lp-card-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Blindagem Anti-Bacen</h3>
                <p>Arquitetura financeira resiliente projetada para manter seu patrimônio protegido e sempre disponível.</p>
            </div>
            <div class="lp-card glass-card" data-aos="zoom-in" data-aos-delay="300">
                <div class="lp-card-icon"><i class="fas fa-bolt"></i></div>
                <h3>Liquidação Instantânea</h3>
                <p>Receba via PIX e converta para saldo Ghost em milissegundos. Rapidez absoluta no seu fluxo.</p>
            </div>
        </div>
    </section>

    <!-- Security Badges Section -->
    <div class="lp-section" style="padding: 2rem 0; background: rgba(255,255,255,0.02); border-top: 1px solid rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.05);">
        <div class="lp-container" style="display: flex; justify-content: space-around; align-items: center; flex-wrap: wrap; gap: 2rem; opacity: 0.6;">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; font-weight: 600;">
                <i class="fas fa-lock" style="color: #4ade80;"></i> SSL SECURE 256-BIT
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; font-weight: 600;">
                <i class="fas fa-shield-virus" style="color: #4ade80;"></i> ANTI-FRAUD SYSTEM
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; font-weight: 600;">
                <i class="fas fa-check-circle" style="color: #4ade80;"></i> VERIFIED PIX GATEWAY
            </div>
        </div>
    </div>
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

    <!-- New Structured Footer -->
    <footer class="lp-footer-v2">
        <div class="lp-footer-container">
            <div class="lp-footer-brand">
                <div class="logo">
                    <img src="logo_premium.png?v=8.0" class="logo-img" alt="Ghost Logo">
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
