<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db.php';

echo "<h2>Iniciando Instalação do Banco de Dados...</h2>";

try {
    $sql = file_get_contents('database.sql');
    
    // Executar o SQL
    $pdo->exec($sql);
    
    echo "<p style='color: green;'>✅ Tabelas criadas com sucesso!</p>";
    echo "<p>Você já pode deletar este arquivo (install.php) por segurança.</p>";
    echo "<a href='auth/login.php'>Ir para o Login</a>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro ao criar tabelas: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
}
?>

