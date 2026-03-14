<?php
require_once 'includes/db.php';

// Buscar taxa padrão
$defTaxStmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'default_user_tax'");
$defaultTax = (float)($defTaxStmt->fetchColumn() ?: '4.0');

// Atualizar TODOS os usuários com taxa <= 0 (inclusive admin)
$stmt = $pdo->prepare("UPDATE users SET commission_rate = ? WHERE commission_rate <= 0 OR commission_rate IS NULL");
$affected = $stmt->execute([$defaultTax]);
$count = $stmt->rowCount();

echo "✅ $count usuários atualizados para taxa padrão de {$defaultTax}%";
?>
