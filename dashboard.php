<?php
require_once 'includes/db.php';

// Segurança: Mantém o login original do seu sistema PHP
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$userId = $_SESSION['user_id'];
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="assets/dashboard-react/favicon.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="<?php echo csrf_token(); ?>">
    <title>Ghost Pix - Dashboard Premium</title>
    
    <!-- React Build Assets -->
    <script type="module" crossorigin src="assets/dashboard-react/index-Dk-qChRR.js"></script>
    <link rel="stylesheet" crossorigin href="assets/dashboard-react/index-IFTZxKPB.css">
    
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
