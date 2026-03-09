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

// Redirecionamento automático se estiver logado e for PWA
if (isLoggedIn() && (isset($_GET['utm_source']) && $_GET['utm_source'] === 'pwa')) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="theme-color" content="#080808">
    <title>Ghost Pix - Receba com Total Blindagem e Privacidade</title>
    <link rel="stylesheet" href="style.css?v=125.0">
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

        /* Force Logo Alignment Fix */
        .lp-navbar .logo {
            margin-bottom: 0 !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            height: auto !important;
            transform: none !important;
        }
        .lp-navbar .logo-img {
            height: 38px !important;
            margin: 0 !important;
            transform: none !important;
            vertical-align: middle !important;
        }
        .lp-navbar .logo-text {
            margin: 0 !important;
            padding: 0 !important;
            line-height: 1 !important;
            display: flex !important;
            align-items: center !important;
        }

        /* Announcement Banner - Theme Refined */
        .lp-announcement-banner {
            background: linear-gradient(90deg, rgba(88, 55, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 50%, rgba(88, 55, 255, 0.05) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 14px;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            position: relative;
            z-index: 99;
            margin-top: 80px; /* Below navbar */
            overflow: hidden;
        }
        .lp-announcement-banner::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            animation: shine-banner 8s infinite linear;
        }
        @keyframes shine-banner {
            0% { left: -100%; }
            20% { left: 100%; }
            100% { left: 100%; }
        }
        .banner-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        .banner-content i {
            color: #5837ff; /* Purple to match theme */
            font-size: 1.3rem;
            filter: drop-shadow(0 0 8px rgba(88, 55, 255, 0.6));
        }
        .btn-banner {
            background: linear-gradient(135deg, #5837ff 0%, #3a1cff 100%);
            color: #fff;
            padding: 7px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.75rem;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            letter-spacing: 0.8px;
            box-shadow: 0 4px 15px rgba(88, 55, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-transform: uppercase;
        }
        .btn-banner:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(88, 55, 255, 0.5);
            filter: brightness(1.2);
        }
        @media (max-width: 768px) {
            .lp-announcement-banner { margin-top: 70px; padding: 18px 10px; }
            .banner-content { flex-direction: column; text-align: center; gap: 12px; font-size: 0.85rem; }
            .btn-banner { width: 100%; max-width: 240px; padding: 10px; }
        }

        /* Instant Approval Section */
        .instant-approval-section {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .approval-card {
            background: linear-gradient(135deg, #5837ff 0%, #3a1cff 100%);
            border-radius: 24px;
            padding: 40px;
            display: flex;
            align-items: center;
            gap: 40px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(88, 55, 255, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .approval-card::after {
            content: '';
            position: absolute;
            top: -50%; right: -10%;
            width: 400px; height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }
        .approval-icon {
            flex-shrink: 0;
            width: 120px; height: 120px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #fff;
            position: relative;
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.2);
        }
        .approval-icon i {
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.8));
        }
        .approval-text h2 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: #fff;
        }
        .approval-text p {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .btn-white {
            background: #fff;
            color: #5837ff;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 800;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }
        .btn-white:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            background: #f8f8f8;
        }

        /* Social Proof Bubbles */
        .social-proof-container {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        .sp-bubble {
            background: rgba(20, 20, 25, 0.8);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 15px;
            width: 280px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            animation: sp-in 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
            pointer-events: auto;
        }
        .sp-icon {
            width: 40px; height: 40px;
            background: #5837ff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.1rem;
        }
        .sp-content { flex: 1; }
        .sp-content b { display: block; font-size: 0.85rem; color: #fff; margin-bottom: 2px; }
        .sp-content span { font-size: 0.75rem; color: rgba(255, 255, 255, 0.6); display: block; }
        .sp-content .amt { color: #4ade80; font-weight: 700; margin-left: 4px; }
        .sp-time { font-size: 0.65rem; color: rgba(255, 255, 255, 0.3); align-self: flex-start; margin-top: 2px; }

        @keyframes sp-in {
            from { opacity: 0; transform: translateX(-50px) scale(0.9); }
            to { opacity: 1; transform: translateX(0) scale(1); }
        }
        @keyframes sp-out {
            from { opacity: 1; transform: translateX(0) scale(1); }
            to { opacity: 0; transform: translateX(-50px) scale(0.9); }
        }

        @media (max-width: 768px) {
            .approval-card { flex-direction: column; text-align: center; padding: 30px 20px; gap: 20px; }
            .approval-icon { width: 80px; height: 80px; font-size: 2rem; }
            .social-proof-container { left: 10px; bottom: 10px; }
            .sp-bubble { width: 250px; padding: 10px 14px; }
        }

        /* Hero Badge Refresh */
        .lp-hero-badge {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 100px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 25px;
            backdrop-filter: blur(5px);
        }
        .lp-hero-badge span {
            width: 10px; height: 10px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            display: inline-block;
        }

        /* Steps Section Styling */
        .steps-section { padding: 80px 20px; max-width: 1200px; margin: 0 auto; }
        .steps-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 30px; }
        .step-card {
            background: rgba(88, 55, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 28px;
            padding: 25px;
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            min-height: 380px;
        }
        .step-card:hover {
            transform: translateY(-10px);
            background: rgba(88, 55, 255, 0.08);
            border-color: rgba(88, 55, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        .step-visual {
            height: 180px;
            background: linear-gradient(135deg, #5837ff 0%, #3a1cff 100%);
            border-radius: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .step-visual i { opacity: 0.9; filter: drop-shadow(0 5px 15px rgba(0,0,0,0.2)); }
        .step-card h3 { font-size: 1.15rem; font-weight: 800; color: #fff; margin-bottom: 15px; }
        .step-card p { font-size: 0.85rem; color: rgba(255, 255, 255, 0.6); line-height: 1.6; }
        
        @media (max-width: 1024px) {
            .steps-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .steps-grid { grid-template-columns: 1fr; }
            .step-card { min-height: auto; }
        }

        /* Integrations Row */
        .integrations-bar {
            padding: 40px 0;
            background: rgba(255, 255, 255, 0.02);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            overflow: hidden;
            position: relative;
        }
        .integrations-track {
            display: flex;
            gap: 60px;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            max-width: 1200px;
            margin: 0 auto;
            opacity: 0.6;
            filter: grayscale(1) brightness(2);
        }
        .integration-item { font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }
        .integration-item i { font-size: 2rem; }

        /* Comparison Table - Advantages */
        .advantages-section { padding: 100px 20px; max-width: 1000px; margin: 0 auto; }
        .comp-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }
        .comp-table th { padding: 20px; text-align: left; color: rgba(255, 255, 255, 0.5); font-size: 0.8rem; text-transform: uppercase; }
        .comp-row {
            background: rgba(255, 255, 255, 0.03);
            transition: transform 0.3s ease;
        }
        .comp-row:hover { background: rgba(255, 255, 255, 0.05); transform: scale(1.01); }
        .comp-row td { padding: 22px 20px; border-top: 1px solid rgba(255, 255, 255, 0.05); border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .comp-row td:first-child { border-left: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px 0 0 16px; font-weight: 600; }
        .comp-row td:last-child { border-right: 1px solid rgba(255, 255, 255, 0.05); border-radius: 0 16px 16px 0; }
        .bad-feat { color: #ff4b4b; font-weight: 700; }
        .good-feat { color: #4ade80; font-weight: 800; display: flex; align-items: center; gap: 8px; }
        .ghost-col { background: rgba(88, 55, 255, 0.1); color: #fff; border: 1px solid rgba(88, 55, 255, 0.2) !important; }

        /* Pricing Section */
        .pricing-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; margin-top: 50px; }
        .price-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 32px;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        .price-card.popular {
            border: 2px solid #5837ff;
            background: rgba(88, 55, 255, 0.05);
            box-shadow: 0 20px 40px rgba(88, 55, 255, 0.15);
        }
        .price-card h4 { font-size: 1.5rem; margin-bottom: 10px; }
        .price-value { font-size: 3rem; font-weight: 800; margin: 20px 0; }
        .price-value span { font-size: 1rem; color: rgba(255, 255, 255, 0.5); }
        .price-features { list-style: none; padding: 0; margin: 30px 0; text-align: left; }
        .price-features li { padding: 10px 0; border_bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 0.9rem; display: flex; align-items: center; gap: 10px; }
        .price-features li i { color: #4ade80; }

        @media (max-width: 768px) {
            .pricing-grid { grid-template-columns: 1fr; }
            .integration-item { font-size: 1.1rem; }
            .comp-table { font-size: 0.8rem; }
            .comp-table th:nth-child(2), .comp-row td:nth-child(2) { display: none; }
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
            <img src="logo_premium.png?v=9.0" class="logo-img" alt="Ghost Logo">
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

        </button>
    </nav>

    <!-- WhatsApp Announcement Banner -->
    <div class="lp-announcement-banner">
        <div class="banner-content">
            <i class="fab fa-whatsapp"></i>
            <span>Entre no nosso canal oficial do WhatsApp para novidades e avisos!</span>
            <a href="https://whatsapp.com/channel/0029VbC56v0GZNComh5KQ73J" target="_blank" class="btn-banner">ENTRAR AGORA</a>
        </div>
    </div>

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
        <div class="lp-hero-badge" data-aos="fade-down">
            <span></span> Plataforma de vendas completa
        </div>
        <h1 class="lp-responsive-title">ESCALE COM CONFIANÇA EM UMA <br><span class="lp-gradient-text">PLATAFORMA DESENHADA PARA PERFORMANCE, SEGURANÇA E RESULTADO SÓLIDO</span></h1>
        <p>Uma plataforma completa para maximizar conversões e simplificar sua operação.</p>
        
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

    <!-- Instant Approval Highlight -->
    <section class="instant-approval-section" data-aos="fade-up">
        <div class="approval-card">
            <div class="approval-icon">
                <i class="fas fa-bolt"></i>
            </div>
            <div class="approval-text">
                <h2>Aprovação INSTANTÂNEA de produtos.</h2>
                <p>Chega de ficar esperando 2 ou 3 dias para vender. Aqui no Ghost Pix todos os seus produtos tem a aprovação instantânea. Basta fazer o cadastro, preencher as informações básicas sobre o produto e começar a vender.</p>
                <p style="font-weight: 700;">Para você sair vendendo o mais rápido possível.</p>
                <a href="auth/register.php" class="btn-white">QUERO SER GHOST PIX</a>
            </div>
        </div>
    </section>

    <!-- How it Works Section -->
    <section class="steps-section" data-aos="fade-up">
        <div class="steps-grid">
            <!-- Step 1 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="100">
                <div class="step-visual">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Crie sua conta</h3>
                <p>Clique no botão "Criar conta" e aguarde o redirecionamento para a página de cadastro.</p>
            </div>

            <!-- Step 2 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                <div class="step-visual">
                    <i class="fas fa-id-card"></i>
                </div>
                <h3>Preencha seus dados</h3>
                <p>Preencha seus dado, defina a senha, atualize seu endereço e finalize seu cadastro.</p>
            </div>

            <!-- Step 3 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                <div class="step-visual">
                    <i class="fas fa-box-open"></i>
                </div>
                <h3>Cadastre seu produto</h3>
                <p>Assim que estiver logado, você já pode criar um novo produto e ele será aprovado automaticamente.</p>
            </div>

            <!-- Step 4 -->
            <div class="step-card" data-aos="fade-up" data-aos-delay="400">
                <div class="step-visual">
                    <i class="fas fa-rocket"></i>
                </div>
                <h3>Comece a vender</h3>
                <p>Pronto! Basta utilizar o link do seu checkout e aplicar nos seus funis de venda.</p>
            </div>
        </div>
    </section>

    <!-- Integrations -->
    <div class="integrations-bar">
        <div class="integrations-track">
            <div class="integration-item"><i class="fab fa-stripe"></i> Stripe API</div>
            <div class="integration-item"><i class="fab fa-shopify"></i> Shopify</div>
            <div class="integration-item"><i class="fas fa-plug"></i> Webhooks</div>
            <div class="integration-item"><i class="fab fa-wordpress"></i> Elementor</div>
            <div class="integration-item"><i class="fas fa-robot"></i> Bot Conversas</div>
            <div class="integration-item"><i class="fas fa-university"></i> Open Bank</div>
        </div>
    </div>

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

    <!-- Advantages Comparison Section -->
    <section class="advantages-section">
        <div class="lp-section-title" data-aos="fade-up">
            <div class="tag-badge" style="background: rgba(74, 222, 128, 0.1); color: #4ade80;"><i class="fas fa-shield-alt"></i> Comparativo de Segurança</div>
            <h2 class="lp-responsive-title">Por que escolher a <span class="lp-gradient-text">Blindagem Ghost Pix?</span></h2>
            <p>Compare e veja a diferença entre operar exposto e operar sob nossa tutela.</p>
        </div>

        <table class="comp-table" data-aos="fade-up">
            <thead>
                <tr>
                    <th>Recurso</th>
                    <th>Plataformas Comuns</th>
                    <th style="color: #fff;">Ghost Pix VIP</th>
                </tr>
            </thead>
            <tbody>
                <tr class="comp-row">
                    <td>Exposição de Dados (CPF/CNPJ)</td>
                    <td class="bad-feat">Alta (Exposto no Checkout)</td>
                    <td class="good-feat" style="background: rgba(88, 55, 255, 0.1);"><i class="fas fa-check-circle"></i> Zero (100% Oculto)</td>
                </tr>
                <tr class="comp-row">
                    <td>Risco de Bloqueio Judicial</td>
                    <td class="bad-feat">Crítico (BacenJud 2.0)</td>
                    <td class="good-feat" style="background: rgba(88, 55, 255, 0.1);"><i class="fas fa-check-circle"></i> Blindado (Liquidação Indireta)</td>
                </tr>
                <tr class="comp-row">
                    <td>Velocidade de Liquidação</td>
                    <td>D+15 ou D+30</td>
                    <td class="good-feat" style="background: rgba(88, 55, 255, 0.1);"><i class="fas fa-check-circle"></i> Imediata (via API)</td>
                </tr>
                <tr class="comp-row">
                    <td>Aprovação de Produtos</td>
                    <td>2-3 dias úteis</td>
                    <td class="good-feat" style="background: rgba(88, 55, 255, 0.1);"><i class="fas fa-check-circle"></i> Instantânea</td>
                </tr>
                <tr class="comp-row">
                    <td>Suporte Especializado</td>
                    <td>Tickets Lentos</td>
                    <td class="good-feat" style="background: rgba(88, 55, 255, 0.1);"><i class="fas fa-check-circle"></i> Gerente VIP no Whats</td>
                </tr>
            </tbody>
        </table>
    </section>

    <!-- Pricing Section -->
    <section class="lp-section" id="taxas">
        <div class="lp-section-title" data-aos="fade-up">
            <div class="tag-badge"><i class="fas fa-dollar-sign"></i> Taxas Transparentes</div>
            <h2 class="lp-responsive-title">O melhor custo-beneficio <br><span class="lp-gradient-text">para sua blindagem</span></h2>
            <p>Sem mensalidades ocultas ou taxas de adesão. Pagamos pelo seu sucesso.</p>
        </div>

        <div class="pricing-grid">
            <div class="price-card" data-aos="fade-right">
                <h4>Iniciante</h4>
                <div class="price-value">0%<span>/mês</span></div>
                <p>Para quem está começando e busca segurança total desde o primeiro real.</p>
                <ul class="price-features">
                    <li><i class="fas fa-check"></i> Taxa: 9.9% + R$ 1,00</li>
                    <li><i class="fas fa-check"></i> Checkout Blindado</li>
                    <li><i class="fas fa-check"></i> Saque em D+2</li>
                    <li><i class="fas fa-check"></i> Suporte via Ticket</li>
                </ul>
                <a href="auth/register.php" class="btn-lp-outline" style="width: 100%;">COMEÇAR AGORA</a>
            </div>

            <div class="price-card popular" data-aos="fade-left">
                <div style="position: absolute; top: 15px; right: 25px; background: #5837ff; color: #fff; font-size: 0.65rem; padding: 4px 12px; border-radius: 50px; font-weight: 800;">RECOMENDADO</div>
                <h4>Enterprise</h4>
                <div class="price-value">Custom<span>/mês</span></div>
                <p>Para produtores acima de R$ 50k/mês que precisam de taxas reduzidas.</p>
                <ul class="price-features">
                    <li><i class="fas fa-check"></i> Taxas Negociáveis</li>
                    <li><i class="fas fa-check"></i> Checkout Customizável</li>
                    <li><i class="fas fa-check"></i> Saque em D+1</li>
                    <li><i class="fas fa-check"></i> Gerente de Contas Exclusivo</li>
                </ul>
                <a href="suporte.php" class="btn-lp-primary" style="width: 100%;">FALAR COM CONSULTOR</a>
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
                    <img src="logo_premium.png?v=9.0" class="logo-img" alt="Ghost Logo">
                    <span class="logo-text">GHOST<span> PIX</span></span>
                </div>
                <p class="lp-brand-tagline">Feito por uma comunidade para uma comunidade.</p>
                <div class="lp-social-icons">
                    <a href="https://www.instagram.com/pixghost.site/" target="_blank" title="Instagram Ghost Pix"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@ghost.pix" target="_blank" title="TikTok Ghost Pix"><i class="fab fa-tiktok"></i></a>
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
    <div id="social-proof-root" class="social-proof-container"></div>
    <script>
        // Social Proof System
        const spRoot = document.getElementById('social-proof-root');
        const names = ['André R.', 'Maria S.', 'João P.', 'Lucas M.', 'Felipe G.', 'Ana B.', 'Priscilla T.', 'Roberto C.', 'Gabriel H.', 'Carlos D.'];
        const methods = ['via PIX', 'via Cartão', 'via Bitcoin'];

        function showSocialProof() {
            const name = names[Math.floor(Math.random() * names.length)];
            const method = methods[Math.floor(Math.random() * methods.length)];
            const amount = (Math.random() * 400 + 50).toFixed(2);
            
            const sp = document.createElement('div');
            sp.className = 'sp-bubble';
            sp.innerHTML = `
                <div class="sp-icon"><img src="logo_premium.png" style="width: 24px; filter: brightness(10);"></div>
                <div class="sp-content">
                    <b>Venda realizada!</b>
                    <span>Comissão: <b class="amt">R$ ${amount.replace('.', ',')}</b></span>
                </div>
                <div class="sp-time">agora</div>
            `;

            spRoot.appendChild(sp);

            setTimeout(() => {
                sp.style.animation = 'sp-out 0.6s ease both';
                setTimeout(() => sp.remove(), 600);
            }, 5000);
        }

        // Random intervals between 8 and 15 seconds
        function nextProof() {
            setTimeout(() => {
                showSocialProof();
                nextProof();
            }, Math.random() * 7000 + 8000);
        }

        setTimeout(nextProof, 3000);
    </script>
</body>
</html>

