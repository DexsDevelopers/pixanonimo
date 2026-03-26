<?php
/**
 * Quick fix: Set is_admin = 1 for the admin user
 * Access once: https://pixghost.site/fix_admin.php
 * Then delete this file.
 */
require_once 'includes/db.php';

if (!isLoggedIn()) {
    die('Não autorizado. Faça login primeiro.');
}

$userId = $_SESSION['user_id'];

// Check current value
$stmt = $pdo->prepare("SELECT id, email, full_name, is_admin FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

echo "<pre>";
echo "User ID: " . $user['id'] . "\n";
echo "Email: " . $user['email'] . "\n";
echo "Name: " . $user['full_name'] . "\n";
echo "is_admin (current): " . var_export($user['is_admin'], true) . "\n\n";

if (isset($_GET['fix'])) {
    $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
    $stmt->execute([$userId]);
    echo "✅ is_admin atualizado para 1!\n";
    echo "Recarregue o dashboard.\n";
} else {
    if ($user['is_admin'] != 1) {
        echo "⚠️ is_admin NÃO está como 1!\n";
        echo "Acesse: fix_admin.php?fix=1 para corrigir\n";
    } else {
        echo "✅ is_admin já está correto (1)\n";
    }
}
echo "</pre>";
