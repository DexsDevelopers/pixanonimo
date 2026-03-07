<?php
// Ghost Pix — Template de Configuração
// Renomeie este arquivo para config.php e preencha seus dados.
// NUNCA envie o seu config.php real para o GitHub!

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_NAME', 'seu_banco');

// Configurações do PixGo.org
define('PIXGO_API_KEY', 'sua_chave_pixgo');
define('PIXGO_PROJECT_ID', ''); 

// Telegram Bot Configuration
define('TELEGRAM_BOT_TOKEN', ''); 
define('TELEGRAM_CHAT_ID', '');   

// Gmail SMTP Configuration
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USER', ''); // Seu e-mail do Gmail
define('MAIL_PASS', ''); // Sua Senha de App do Google (16 caracteres)
define('MAIL_FROM_NAME', 'Ghost Pix');
?>
