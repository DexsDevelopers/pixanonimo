<?php
require_once 'includes/db.php';
require_once 'includes/MailService.php';

// Pegar o e-mail do destinatário (pode ser o mesmo que envia para teste)
$testEmail = MAIL_USER; 
$testName = "Administrador Ghost Pix";

echo "<h2>Iniciando teste de e-mail...</h2>";
echo "Destinatário: {$testEmail}<br>";

$success = MailService::notifyApproval($testEmail, $testName);

if ($success) {
    echo "<h3 style='color: green;'>✅ E-mail de teste enviado com sucesso!</h3>";
    echo "<p>Verifique a caixa de entrada (e a pasta de Spam) de <strong>{$testEmail}</strong>.</p>";
} else {
    echo "<h3 style='color: red;'>❌ Falha ao enviar e-mail.</h3>";
    echo "<p>Verifique os registros em <strong>/logs/</strong> para mais detalhes.</p>";
}

echo "<br><a href='index.php'>Voltar para o site</a>";
?>
