<?php
require_once 'includes/db.php';

$isAuth = isLoggedIn();
$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Rastrear visita ao site (apenas páginas principais, sem APIs)
if (in_array($requestPath, ['/', '/login', '/register', '/vitrine', '/docs'])) {
    try {
        $pdo->prepare("INSERT INTO daily_stats (stat_date, stat_key, stat_value) VALUES (CURDATE(), 'page_views', 1) ON DUPLICATE KEY UPDATE stat_value = stat_value + 1")->execute();
        // Contar visitantes únicos por IP (1 por dia)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $checkVisitor = $pdo->prepare("SELECT 1 FROM daily_stats WHERE stat_date = CURDATE() AND stat_key = CONCAT('uv_', ?)");
        $checkVisitor->execute([$ip]);
        if (!$checkVisitor->fetch()) {
            $pdo->prepare("INSERT IGNORE INTO daily_stats (stat_date, stat_key, stat_value) VALUES (CURDATE(), CONCAT('uv_', ?), 1)")->execute([$ip]);
            $pdo->prepare("INSERT INTO daily_stats (stat_date, stat_key, stat_value) VALUES (CURDATE(), 'unique_visitors', 1) ON DUPLICATE KEY UPDATE stat_value = stat_value + 1")->execute();
        }
    } catch (Throwable $e) {}
}

// Se logado e acessando a raiz, redireciona direto pro dashboard
if ($isAuth && $requestPath === '/') {
    header('Location: /dashboard');
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <base href="/">
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/assets/dashboard-react/favicon.svg" />
    <link rel="manifest" href="/manifest.json" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-title" content="Ghost Pix" />
    <link rel="apple-touch-icon" href="/logo_premium.png" />
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <script>window.__AUTH__ = <?php echo json_encode($isAuth); ?>;</script>
    <title>Ghost Pix - Dashboard Premium</title>
    
    <!-- React Build Assets -->
    <script type="module" crossorigin src="/assets/dashboard-react/index-yX8JIUmi.js"></script>
    <link rel="stylesheet" crossorigin href="/assets/dashboard-react/index-DlTuz-Ju.css">
    
    <!-- Preload fonts to avoid layout shift -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
      body {
        margin: 0;
        background-color: #08080a;
        color: white;
        font-family: 'Outfit', sans-serif;
      }
      /* Custom scrollbar for better aesthetics */
      ::-webkit-scrollbar {
        width: 6px;
      }
      ::-webkit-scrollbar-track {
        background: #08080a;
      }
      ::-webkit-scrollbar-thumb {
        background: #222;
        border-radius: 10px;
      }
      ::-webkit-scrollbar-thumb:hover {
        background: #333;
      }
    </style>
  </head>
  <body>
    <div id="root"></div>
    <!-- Os dados são buscados via fetch('../get_dashboard_data.php') no App.jsx -->
  </body>
</html>
