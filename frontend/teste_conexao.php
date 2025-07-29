<?php
require_once 'auth_check.php';
require_once 'config.php';

echo "<h1>Teste de Conexão</h1>";

// Testar conexão com o banco
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✅ Conexão com banco de dados OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na conexão com banco: " . $e->getMessage() . "</p>";
}

// Testar se o usuário está logado
if (isLoggedIn()) {
    echo "<p style='color: green;'>✅ Usuário logado: " . $_SESSION['usuario_nome'] . "</p>";
} elseif (isAdminLoggedIn()) {
    echo "<p style='color: green;'>✅ Admin logado: " . $_SESSION['admin_nome'] . "</p>";
} else {
    echo "<p style='color: red;'>❌ Nenhum usuário logado</p>";
}

// Testar getCurrentUser()
$currentUser = getCurrentUser();
if ($currentUser) {
    echo "<p style='color: green;'>✅ getCurrentUser() OK: " . $currentUser['nome'] . "</p>";
    echo "<pre>" . print_r($currentUser, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ getCurrentUser() retornou null</p>";
}

// Mostrar todas as variáveis de sessão
echo "<h2>Variáveis de Sessão:</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
?> 