<?php
require_once 'includes/db.php';

// Buscar taxa padrão
$defTaxStmt = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'default_user_tax'");
$defaultTax = (float)($defTaxStmt->fetchColumn() ?: '4.0');

// Contar usuários afetados
$countStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE commission_rate = 0 AND is_admin = 0");
$count = $countStmt->fetchColumn();

// Atualizar todos os usuários com taxa 0 para a taxa padrão
$stmt = $pdo->prepare("UPDATE users SET commission_rate = ? WHERE commission_rate = 0 AND is_admin = 0");
$stmt->execute([$defaultTax]);

echo "✅ $count usuários atualizados para taxa padrão de {$defaultTax}%";
?>
