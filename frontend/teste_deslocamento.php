<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';

echo "<h1>Teste Deslocamento</h1>";
echo "<p>Usuário: " . ($currentUser['nome'] ?? 'N/A') . "</p>";
echo "<p>Perfil: " . $perfil . "</p>";
echo "<p>Status: " . (isLoggedIn() ? 'Logado' : 'Não logado') . "</p>";

if ($currentUser) {
    echo "<p style='color: green;'>✅ Tudo OK! O formulário de deslocamento deve funcionar.</p>";
    echo "<a href='deslocamento.php'>Ir para Deslocamento</a>";
} else {
    echo "<p style='color: red;'>❌ Problema com getCurrentUser()</p>";
}
?> 