<?php
// verificar_login.php - Verificar tipo de login
session_start();

echo "<h1>Verificação de Login</h1>";
echo "<h2>Variáveis da Sessão:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Status de Login:</h2>";
echo "<ul>";
echo "<li>Session ID: " . session_id() . "</li>";
echo "<li>Session Status: " . session_status() . "</li>";

if (isset($_SESSION['logado'])) {
    echo "<li>logado: " . ($_SESSION['logado'] ? 'true' : 'false') . "</li>";
} else {
    echo "<li>logado: NÃO EXISTE</li>";
}

if (isset($_SESSION['admin_logado'])) {
    echo "<li>admin_logado: " . ($_SESSION['admin_logado'] ? 'true' : 'false') . "</li>";
} else {
    echo "<li>admin_logado: NÃO EXISTE</li>";
}

if (isset($_SESSION['usuario_id'])) {
    echo "<li>usuario_id: " . $_SESSION['usuario_id'] . "</li>";
} else {
    echo "<li>usuario_id: NÃO EXISTE</li>";
}

if (isset($_SESSION['admin_id'])) {
    echo "<li>admin_id: " . $_SESSION['admin_id'] . "</li>";
} else {
    echo "<li>admin_id: NÃO EXISTE</li>";
}

if (isset($_SESSION['usuario_nome'])) {
    echo "<li>usuario_nome: " . $_SESSION['usuario_nome'] . "</li>";
} else {
    echo "<li>usuario_nome: NÃO EXISTE</li>";
}

if (isset($_SESSION['admin_nome'])) {
    echo "<li>admin_nome: " . $_SESSION['admin_nome'] . "</li>";
} else {
    echo "<li>admin_nome: NÃO EXISTE</li>";
}

echo "</ul>";

echo "<h2>Teste de Acesso:</h2>";
echo "<p><a href='usuarios_sem_restricao.php'>Testar Gestão de Usuários</a></p>";
echo "<p><a href='dashboard.php'>Testar Dashboard</a></p>";
echo "<p><a href='index.php'>Voltar ao Login</a></p>";
?> 