<?php
session_start();
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="lp-body">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Ghost Pix - API de Pagamentos Blindada</title>
    <link rel="stylesheet" href="style.css?v=17.0">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        .code-block {
            background: rgba(0,0,0,0.5);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #d1d5db;
            overflow-x: auto;
            position: relative;
            margin: 2rem 0;
        }
        .code-keyword { color: #f472b6; }
        .code-string { color: #4ade80; }
        .code-comment { color: #6b7280; }
        .api-hero {
            padding: 8rem 0 4rem;
            text-align: center;
        }
        .api-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }
    </style>
</head>
<body class="lp-body">
    <canvas id="canvas-3d" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; pointer-events: none; opacity: 0.4;"></canvas>

    <nav class="lp-navbar">
        <div class="logo">
            <img src="logo_premium.png?v=9.0" class="logo-img" alt="Ghost Logo">
            <span class="logo-text">GHOST<span> PIX</span></span>
        </div>
        <div class="lp-nav-links">
            <a href="index.php" class="lp-nav-link">HOME</a>
            <a href="index.php#faq" class="lp-nav-link">FAQ</a>
            <a href="suporte.php" class="lp-nav-link">CONTATO</a>
        </div>
        <div class="lp-auth-buttons mobile-hide-links">
            <?php if(isLoggedIn()): ?>
                <a href="dashboard.php" class="btn-lp-primary">PAINEL</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn-lp-outline-sm">ENTRAR</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="lp-container">
        <section class="api-hero" data-aos="fade-up">
            <div class="lp-hero-tag" style="margin-bottom: 1rem;">PARA DESENVOLVEDORES E LOJISTAS</div>
            <h1 class="lp-responsive-title">INTEGRE A <span class="lp-gradient-text">GHOST API</span> <br>EM SEU CHECKOUT</h1>
            <p style="max-width: 700px; margin: 1.5rem auto;">Processe pagamentos Pix com total anonimato, webhooks em tempo real e blindagem contra bloqueios judiciais.</p>
            <div style="margin-top: 3rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="auth/register.php" class="btn-lp-primary">Começar agora</a>
                <a href="#docs" class="btn-lp-outline-sm" style="padding: 1rem 2rem;">Ver Documentação</a>
            </div>
        </section>

        <section class="api-grid">
            <div class="lp-card glass-card" data-aos="fade-up" data-aos-delay="100">
                <div class="lp-card-icon" style="color: var(--green);"><i class="fas fa-key"></i></div>
                <h3>Chaves Privadas</h3>
                <p>Gere Ghost Keys únicas para cada site ou checkout que você gerenciar.</p>
            </div>
            <div class="lp-card glass-card" data-aos="fade-up" data-aos-delay="200">
                <div class="lp-card-icon" style="color: var(--blue);"><i class="fas fa-satellite-dish"></i></div>
                <h3>Webhooks Externos</h3>
                <p>Notificações instantâneas via POST JSON direto para o seu servidor.</p>
            </div>
            <div class="lp-card glass-card" data-aos="fade-up" data-aos-delay="300">
                <div class="lp-card-icon" style="color: var(--amber);"><i class="fas fa-shield-halved"></i></div>
                <h3>Anti-Bacen Logic</h3>
                <p>Nossa arquitetura interna evita rastreios e protege seu fluxo de caixa.</p>
            </div>
        </section>

        <section id="docs" style="margin: 6rem 0;" data-aos="fade-up">
            <div class="lp-section-title">
                <h2>Guia de Integração Rápida</h2>
                <p>Tudo o que você precisa em menos de 5 minutos.</p>
            </div>

            <div style="max-width: 900px; margin: 0 auto;">
                <h3>1. Gerar Cobrança (Pix)</h3>
                <p>Envie um POST para o endpoint de API com sua Ghost Key no Header.</p>
                
                <div class="code-block">
                    <span class="code-comment"># Exemplo em cURL</span><br>
                    curl -X <span class="code-keyword">POST</span> https://pixghost.site/api.php \<br>
                    &nbsp;&nbsp;-H <span class="code-string">"Authorization: Bearer ghost_sua_chave_secreta"</span> \<br>
                    &nbsp;&nbsp;-H <span class="code-string">"Content-Type: application/json"</span> \<br>
                    &nbsp;&nbsp;-d '{<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;<span class="code-keyword">"amount"</span>: <span class="code-string">50.00</span>,<br>
                    &nbsp;&nbsp;&nbsp;&nbsp;<span class="code-keyword">"callback_url"</span>: <span class="code-string">"https://seu-site.com/webhook"</span><br>
                    &nbsp;&nbsp;}'
                </div>

                <h3>2. Resposta da API</h3>
                <p>Você receberá o QR Code e o ID da transação instantaneamente.</p>
                <div class="code-block">
                    {<br>
                    &nbsp;&nbsp;<span class="code-keyword">"status"</span>: <span class="code-string">"success"</span>,<br>
                    &nbsp;&nbsp;<span class="code-keyword">"pix_id"</span>: <span class="code-string">"px_123..."</span>,<br>
                    &nbsp;&nbsp;<span class="code-keyword">"pix_code"</span>: <span class="code-string">"000201..."</span>,<br>
                    &nbsp;&nbsp;<span class="code-keyword">"qr_image"</span>: <span class="code-string">"https://..."</span><br>
                    }
                </div>

                <div class="card glass-card" style="margin-top: 4rem; padding: 2rem;">
                    <h3>Pronto para escalar?</h3>
                    <p>Milhares de transações são processadas diariamente com total segurança. Não deixe seu capital exposto.</p>
                    <a href="auth/register.php" class="btn-lp-primary" style="margin-top: 1.5rem;">Registrar-se como Desenvolvedor</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="lp-footer-v2">
        <div class="lp-footer-container">
            <div class="lp-footer-brand">
                <div class="logo">
                    <img src="logo_premium.png?v=8.0" class="logo-img" alt="Ghost Logo">
                    <span class="logo-text">GHOST<span> PIX</span></span>
                </div>
                <p class="lp-brand-tagline">Privacidade é um direito, não um privilégio.</p>
            </div>
            <div class="lp-footer-links-grid">
                <div class="lp-footer-col">
                    <h4>Páginas</h4>
                    <a href="index.php">Início</a>
                    <a href="suporte.php">Suporte</a>
                </div>
                <div class="lp-footer-col">
                    <h4>Legal</h4>
                    <a href="termos.php">Termos</a>
                    <a href="privacidade.php">Privacidade</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true });
        
        // Three.js Abstract Background (Simplified version of index.php for visual consistency)
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
        const renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('canvas-3d'), alpha: true });
        renderer.setSize(window.innerWidth, window.innerHeight);

        const particlesGeometry = new THREE.BufferGeometry();
        const counts = 800;
        const positions = new Float32Array(counts * 3);
        for(let i = 0; i < counts * 3; i++) positions[i] = (Math.random() - 0.5) * 10;
        particlesGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
        const particlesMaterial = new THREE.PointsMaterial({ size: 0.015, color: 0x4ade80, transparent: true, opacity: 0.5 });
        const particles = new THREE.Points(particlesGeometry, particlesMaterial);
        scene.add(particles);
        camera.position.z = 2;

        function animate() {
            requestAnimationFrame(animate);
            particles.rotation.y += 0.0008;
            renderer.render(scene, camera);
        }
        animate();

        window.addEventListener('resize', () => {
            camera.aspect = window.innerWidth / window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    </script>
</body>
</html>
