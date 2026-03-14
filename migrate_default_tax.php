<?php
require_once 'includes/db.php';

// Listar todas as taxas dos usuários
$stmt = $pdo->query("SELECT id, full_name, email, commission_rate FROM users WHERE is_admin = 0 ORDER BY id DESC LIMIT 10");
$users = $stmt->fetchAll();

echo "<h3>Últimos 10 usuários:</h3>";
echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Nome</th><th>Email</th><th>commission_rate</th></tr>";
foreach ($users as $u) {
    echo "<tr><td>{$u['id']}</td><td>{$u['full_name']}</td><td>{$u['email']}</td><td>{$u['commission_rate']}</td></tr>";
}
echo "</table>";

// Verificar settings
$stmt2 = $pdo->query("SELECT * FROM settings");
echo "<h3>Settings:</h3><pre>";
while ($s = $stmt2->fetch()) {
    echo "{$s['key']} = {$s['value']}\n";
}
echo "</pre>";
?>
