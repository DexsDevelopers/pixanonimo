<?php
/**
 * setup_medusapay.php — Salvar chaves MedusaPay no banco
 * Acesse UMA VEZ: https://pixghost.site/setup_medusapay.php
 * Delete após uso.
 */
require_once 'includes/db.php';

if (!isAdmin()) {
    http_response_code(403);
    die('Acesso negado.');
}

$saved = false;
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $secretKey = trim($_POST['secret_key'] ?? '');
    $publicKey  = trim($_POST['public_key'] ?? '');
    if ($secretKey) {
        $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES ('medusapay_secret_key', ?) ON DUPLICATE KEY UPDATE `value` = ?")
            ->execute([$secretKey, $secretKey]);
    }
    if ($publicKey) {
        $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES ('medusapay_public_key', ?) ON DUPLICATE KEY UPDATE `value` = ?")
            ->execute([$publicKey, $publicKey]);
    }
    $saved = true;
    $msg = '✅ Chaves salvas com sucesso!';
}

echo "<h2>Setup MedusaPay</h2>";
if ($saved) echo "<p style='color:green;font-size:1.3em'>$msg</p><p style='color:red'>⚠️ Delete este arquivo!</p>";
echo "<form method='POST' style='max-width:500px'>";
echo "<p><label>Secret Key (sk_live_...):<br><input name='secret_key' type='password' style='width:100%;padding:8px' placeholder='sk_live_...'></label></p>";
echo "<p><label>Public Key (pk_live_...):<br><input name='public_key' type='password' style='width:100%;padding:8px' placeholder='pk_live_...'></label></p>";
echo "<button type='submit' style='padding:10px 20px;background:#0f0;color:#000;font-weight:bold'>Salvar</button>";
echo "</form>";
