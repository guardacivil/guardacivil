<?php
// teste_usuarios.php - Teste de acesso à página de usuários
require_once 'auth_check.php';
require_once 'config.php';

echo "<h1>🔍 Teste de Acesso - usuarios.php</h1>";

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ ERRO: Usuário não está logado!</p>";
    echo "<p><a href='index.php'>Fazer Login</a></p>";
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = isAdminLoggedIn();

echo "<h2>📋 Informações do Usuário:</h2>";
echo "<ul>";
echo "<li><strong>Nome:</strong> " . htmlspecialchars($currentUser['nome']) . "</li>";
echo "<li><strong>Perfil:</strong> " . htmlspecialchars($currentUser['perfil']) . "</li>";
echo "<li><strong>Admin:</strong> " . ($isAdmin ? '✅ Sim' : '❌ Não') . "</li>";
echo "<li><strong>Permissão 'usuarios':</strong> " . (hasPermission('usuarios') ? '✅ Sim' : '❌ Não') . "</li>";
echo "</ul>";

echo "<h2>🔗 Links de Teste:</h2>";
echo "<ul>";
echo "<li><a href='usuarios.php' target='_blank'>📄 usuarios.php (Original)</a></li>";
echo "<li><a href='dashboard.php'>🏠 Dashboard</a></li>";
echo "<li><a href='gerenciar_permissoes_usuarios.php'>⚙️ Gerenciar Permissões</a></li>";
echo "</ul>";

echo "<h2>🔧 Status do Sistema:</h2>";
echo "<ul>";
echo "<li><strong>isLoggedIn():</strong> " . (isLoggedIn() ? '✅ Sim' : '❌ Não') . "</li>";
echo "<li><strong>isAdminLoggedIn():</strong> " . (isAdminLoggedIn() ? '✅ Sim' : '❌ Não') . "</li>";
echo "<li><strong>hasPermission('usuarios'):</strong> " . (hasPermission('usuarios') ? '✅ Sim' : '❌ Não') . "</li>";
echo "</ul>";

if ($isAdmin || hasPermission('usuarios')) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #155724; margin: 0;'>✅ ACESSO PERMITIDO!</h3>";
    echo "<p style='color: #155724; margin: 10px 0 0 0;'>Você pode acessar a gestão de usuários.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin: 0;'>❌ ACESSO NEGADO!</h3>";
    echo "<p style='color: #721c24; margin: 10px 0 0 0;'>Você não tem permissão para acessar a gestão de usuários.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='diagnostico_usuarios.php'>🔬 Diagnóstico Completo</a></p>";
echo "<p><a href='dashboard.php'>🏠 Voltar ao Dashboard</a></p>";
?> 