<?php
require_once 'includes/db.php';

echo "<h1>Migração: Sistema de Afiliados</h1>";

try {
    // 1. Adicionar colunas à tabela users
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS affiliate_id INT DEFAULT NULL AFTER is_admin");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS referral_token VARCHAR(32) UNIQUE DEFAULT NULL AFTER affiliate_id");
    echo "<p>✅ Colunas `affiliate_id` e `referral_token` adicionadas com sucesso.</p>";

    // 2. Adicionar configuração de comissão se não existir
    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (`key`, `value`) VALUES ('affiliate_commission_rate', '10.0')");
    $stmt->execute();
    echo "<p>✅ Configuração `affiliate_commission_rate` (10%) adicionada.</p>";

    // 3. Gerar tokens para usuários que não possuem
    $stmt = $pdo->query("SELECT id FROM users WHERE referral_token IS NULL");
    $users = $stmt->fetchAll();
    
    if ($users) {
        $upd = $pdo->prepare("UPDATE users SET referral_token = ? WHERE id = ?");
        foreach ($users as $user) {
            $token = bin2hex(random_bytes(8)); // Token de 16 caracteres
            $upd->execute([$token, $user['id']]);
        }
        echo "<p>✅ Tokens de indicação gerados para " . count($users) . " usuários.</p>";
    } else {
        echo "<p>ℹ️ Todos os usuários já possuem tokens.</p>";
    }

    echo "<h3>🚀 Migração Concluída!</h3>";
    echo "<a href='dashboard.php'>Voltar ao Painel</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erro na migração: " . $e->getMessage() . "</p>";
}
?>
