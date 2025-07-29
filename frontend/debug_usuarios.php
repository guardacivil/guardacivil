<?php
// Debug para gestão de usuários
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug - Gestão de Usuários</h1>";

// Verificar se os arquivos existem
echo "<h2>Verificação de Arquivos:</h2>";
$arquivos = ['auth_check.php', 'config.php', 'sidebar.php'];
foreach ($arquivos as $arquivo) {
    echo "<p><strong>$arquivo:</strong> " . (file_exists($arquivo) ? '✅ Existe' : '❌ Não existe') . "</p>";
}

// Verificar se as funções existem
echo "<h2>Verificação de Funções:</h2>";
if (function_exists('isLoggedIn')) {
    echo "<p><strong>isLoggedIn():</strong> ✅ Existe</p>";
} else {
    echo "<p><strong>isLoggedIn():</strong> ❌ Não existe</p>";
}

if (function_exists('getCurrentUser')) {
    echo "<p><strong>getCurrentUser():</strong> ✅ Existe</p>";
} else {
    echo "<p><strong>getCurrentUser():</strong> ❌ Não existe</p>";
}

// Testar conexão com banco
echo "<h2>Teste de Conexão com Banco:</h2>";
try {
    require_once 'config.php';
    echo "<p><strong>Conexão com banco:</strong> ✅ Funcionando</p>";
    
    // Testar consulta simples
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "<p><strong>Total de usuários:</strong> " . $result['total'] . "</p>";
    
} catch (Exception $e) {
    echo "<p><strong>Erro na conexão:</strong> ❌ " . $e->getMessage() . "</p>";
}

// Verificar sessão
echo "<h2>Verificação de Sessão:</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p><strong>Status da sessão:</strong> " . session_status() . "</p>";
echo "<p><strong>Dados da sessão:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Testar funções de autenticação
echo "<h2>Teste de Autenticação:</h2>";
try {
    require_once 'auth_check.php';
    
    if (function_exists('isLoggedIn')) {
        echo "<p><strong>isLoggedIn():</strong> " . (isLoggedIn() ? '✅ Sim' : '❌ Não') . "</p>";
    }
    
    if (function_exists('isAdminLoggedIn')) {
        echo "<p><strong>isAdminLoggedIn():</strong> " . (isAdminLoggedIn() ? '✅ Sim' : '❌ Não') . "</p>";
    }
    
    if (function_exists('getCurrentUser')) {
        $user = getCurrentUser();
        echo "<p><strong>getCurrentUser():</strong> " . ($user ? '✅ Retornou dados' : '❌ Retornou null') . "</p>";
        if ($user) {
            echo "<pre>";
            print_r($user);
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>Erro ao testar autenticação:</strong> ❌ " . $e->getMessage() . "</p>";
}

// Testar sidebar
echo "<h2>Teste do Sidebar:</h2>";
try {
    ob_start();
    include 'sidebar.php';
    $sidebar_content = ob_get_clean();
    echo "<p><strong>Sidebar:</strong> ✅ Carregou sem erros</p>";
    echo "<p><strong>Tamanho do conteúdo:</strong> " . strlen($sidebar_content) . " caracteres</p>";
} catch (Exception $e) {
    echo "<p><strong>Erro no sidebar:</strong> ❌ " . $e->getMessage() . "</p>";
}

echo "<p><a href='usuarios.php'>Tentar acessar Gestão de Usuários</a></p>";
echo "<p><a href='dashboard.php'>Voltar para Dashboard</a></p>";
?> 