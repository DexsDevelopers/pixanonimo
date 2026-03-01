<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';

$email = 'admin@pixanonimo.com';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Tentar atualizar se já existir, ou inserir se não existir
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET password = ?, is_admin = 1, status = 'approved' WHERE email = ?");
        $stmt->execute([$hash, $email]);
        echo "<h2>✅ Senha do Admin atualizada com sucesso!</h2>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (email, password, is_admin, status) VALUES (?, ?, 1, 'approved')");
        $stmt->execute([$email, $hash]);
        echo "<h2>✅ Usuário Admin criado com sucesso!</h2>";
    }

    echo "<p><strong>Login:</strong> $email</p>";
    echo "<p><strong>Senha:</strong> $password</p>";
    echo "<br><a href='auth/login.php'>Ir para o Login</a>";

} catch (PDOException $e) {
    echo "<h2>❌ Erro: " . $e->getMessage() . "</h2>";
}
?>
