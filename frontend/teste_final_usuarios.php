<?php
// teste_final_usuarios.php - Teste final do sistema de usuários
require_once 'auth_check.php';
require_once 'config.php';

echo "<!DOCTYPE html>";
echo "<html lang='pt-BR'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Teste Final - Gestão de Usuários</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' />";
echo "</head>";
echo "<body class='bg-gray-100 p-8'>";

echo "<div class='max-w-4xl mx-auto'>";
echo "<h1 class='text-3xl font-bold text-gray-800 mb-8'>🔍 Teste Final - Sistema de Usuários</h1>";

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6'>";
    echo "<i class='fas fa-exclamation-triangle mr-2'></i>❌ ERRO: Usuário não está logado!";
    echo "</div>";
    echo "<p><a href='index.php' class='text-blue-600 hover:underline'>Fazer Login</a></p>";
    echo "</div></body></html>";
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = isAdminLoggedIn();

echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-6 mb-8'>";

// Card 1: Informações do Usuário
echo "<div class='bg-white rounded-lg shadow-md p-6'>";
echo "<h2 class='text-xl font-semibold mb-4'><i class='fas fa-user mr-2'></i>Informações do Usuário</h2>";
echo "<ul class='space-y-2'>";
echo "<li><strong>Nome:</strong> " . htmlspecialchars($currentUser['nome']) . "</li>";
echo "<li><strong>Perfil:</strong> " . htmlspecialchars($currentUser['perfil']) . "</li>";
echo "<li><strong>Admin:</strong> " . ($isAdmin ? '✅ Sim' : '❌ Não') . "</li>";
echo "<li><strong>ID:</strong> " . $currentUser['id'] . "</li>";
echo "</ul>";
echo "</div>";

// Card 2: Status do Sistema
echo "<div class='bg-white rounded-lg shadow-md p-6'>";
echo "<h2 class='text-xl font-semibold mb-4'><i class='fas fa-cogs mr-2'></i>Status do Sistema</h2>";
echo "<ul class='space-y-2'>";
echo "<li><strong>isLoggedIn():</strong> " . (isLoggedIn() ? '✅ Sim' : '❌ Não') . "</li>";
echo "<li><strong>isAdminLoggedIn():</strong> " . (isAdminLoggedIn() ? '✅ Sim' : '❌ Não') . "</li>";
echo "<li><strong>hasPermission('usuarios'):</strong> " . (hasPermission('usuarios') ? '✅ Sim' : '❌ Não') . "</li>";
echo "<li><strong>hasMenuPermission('usuarios'):</strong> " . (hasMenuPermission('usuarios') ? '✅ Sim' : '❌ Não') . "</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

// Status de Acesso
if ($isAdmin || hasPermission('usuarios')) {
    echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6'>";
    echo "<h3 class='font-semibold'><i class='fas fa-check-circle mr-2'></i>✅ ACESSO PERMITIDO!</h3>";
    echo "<p class='mt-2'>Você pode acessar a gestão de usuários.</p>";
    echo "</div>";
} else {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6'>";
    echo "<h3 class='font-semibold'><i class='fas fa-times-circle mr-2'></i>❌ ACESSO NEGADO!</h3>";
    echo "<p class='mt-2'>Você não tem permissão para acessar a gestão de usuários.</p>";
    echo "</div>";
}

// Links de Teste
echo "<div class='bg-white rounded-lg shadow-md p-6 mb-8'>";
echo "<h2 class='text-xl font-semibold mb-4'><i class='fas fa-link mr-2'></i>Links de Teste</h2>";
echo "<div class='grid grid-cols-1 md:grid-cols-2 gap-4'>";

echo "<div class='space-y-2'>";
echo "<h3 class='font-medium text-gray-700'>Versões da Página de Usuários:</h3>";
echo "<ul class='space-y-1'>";
echo "<li><a href='usuarios.php' class='text-blue-600 hover:underline' target='_blank'>📄 usuarios.php (Original)</a></li>";
echo "<li><a href='usuarios_simples.php' class='text-green-600 hover:underline' target='_blank'>📄 usuarios_simples.php (Simplificada)</a></li>";
echo "</ul>";
echo "</div>";

echo "<div class='space-y-2'>";
echo "<h3 class='font-medium text-gray-700'>Ferramentas de Diagnóstico:</h3>";
echo "<ul class='space-y-1'>";
echo "<li><a href='teste_usuarios.php' class='text-purple-600 hover:underline'>🔬 Teste Detalhado</a></li>";
echo "<li><a href='diagnostico_usuarios.php' class='text-orange-600 hover:underline'>🔍 Diagnóstico Completo</a></li>";
echo "<li><a href='gerenciar_permissoes_usuarios.php' class='text-indigo-600 hover:underline'>⚙️ Gerenciar Permissões</a></li>";
echo "</ul>";
echo "</div>";

echo "</div>";
echo "</div>";

// Recomendações
echo "<div class='bg-blue-50 border border-blue-200 rounded-lg p-6'>";
echo "<h2 class='text-xl font-semibold text-blue-800 mb-4'><i class='fas fa-lightbulb mr-2'></i>Recomendações</h2>";
echo "<div class='space-y-2 text-blue-700'>";

if ($isAdmin) {
    echo "<p>✅ <strong>Você é administrador!</strong> Use qualquer versão da página de usuários.</p>";
    echo "<p>💡 <strong>Recomendado:</strong> usuarios_simples.php (versão mais estável)</p>";
} elseif (hasPermission('usuarios')) {
    echo "<p>✅ <strong>Você tem permissão!</strong> Pode acessar a gestão de usuários.</p>";
    echo "<p>💡 <strong>Recomendado:</strong> usuarios_simples.php (versão mais estável)</p>";
} else {
    echo "<p>❌ <strong>Sem permissão:</strong> Solicite acesso ao administrador.</p>";
    echo "<p>🔧 <strong>Solução:</strong> Configure permissões em 'Gerenciar Permissões'</p>";
}

echo "</div>";
echo "</div>";

// Botões de Ação
echo "<div class='flex justify-center space-x-4 mt-8'>";
echo "<a href='dashboard.php' class='bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg'>";
echo "<i class='fas fa-home mr-2'></i>Dashboard";
echo "</a>";
echo "<a href='usuarios_simples.php' class='bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg'>";
echo "<i class='fas fa-users mr-2'></i>Gestão de Usuários";
echo "</a>";
echo "<a href='gerenciar_permissoes_usuarios.php' class='bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg'>";
echo "<i class='fas fa-user-cog mr-2'></i>Gerenciar Permissões";
echo "</a>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?> 