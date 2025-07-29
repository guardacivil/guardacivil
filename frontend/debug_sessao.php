<?php
// debug_sessao.php - Debug da sessão
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = isAdminLoggedIn();

// Informações da sessão
$session_info = [
    'session_id' => session_id(),
    'session_status' => session_status(),
    'session_name' => session_name(),
    'session_save_path' => session_save_path(),
    'session_cookie_params' => session_get_cookie_params()
];

// Variáveis da sessão
$session_vars = $_SESSION;

// Verificar se há problemas com a sessão
$session_issues = [];
if (empty($_SESSION)) {
    $session_issues[] = 'Sessão vazia';
}
if (!isset($_SESSION['logado'])) {
    $session_issues[] = 'Variável logado não existe';
}
if (isset($_SESSION['logado']) && $_SESSION['logado'] !== true) {
    $session_issues[] = 'Variável logado não é true';
}
if (!isset($_SESSION['usuario_id'])) {
    $session_issues[] = 'ID do usuário não existe';
}
if (!isset($_SESSION['usuario_nome'])) {
    $session_issues[] = 'Nome do usuário não existe';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug da Sessão</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        main.content {
            margin-left: 16rem;
            padding: 2rem;
            width: calc(100% - 16rem);
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    
    <main class="content">
        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-bug mr-2"></i>Debug da Sessão
                    </h1>
                    <p class="text-gray-600 mt-2">Verificação detalhada da sessão</p>
                </div>
                <div class="flex space-x-2">
                    <a href="usuarios_simples.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-users mr-2"></i>Gestão de Usuários
                    </a>
                    <a href="dashboard.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                </div>
            </div>

            <!-- Status da Sessão -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Status da Sessão
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-medium mb-2">Informações Básicas:</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><strong>Session ID:</strong> <?= $session_info['session_id'] ?></li>
                            <li><strong>Status:</strong> <?= $session_info['session_status'] ?></li>
                            <li><strong>Nome:</strong> <?= $session_info['session_name'] ?></li>
                            <li><strong>Save Path:</strong> <?= $session_info['session_save_path'] ?></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-medium mb-2">Funções de Autenticação:</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li><strong>isLoggedIn():</strong> <?= isLoggedIn() ? '✅ true' : '❌ false' ?></li>
                            <li><strong>isAdminLoggedIn():</strong> <?= isAdminLoggedIn() ? '✅ true' : '❌ false' ?></li>
                            <li><strong>getCurrentUser():</strong> <?= $currentUser ? '✅ Disponível' : '❌ Nulo' ?></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Problemas Detectados -->
            <?php if (!empty($session_issues)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold text-red-800 mb-4">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Problemas Detectados
                    </h2>
                    <ul class="text-red-700 space-y-1">
                        <?php foreach ($session_issues as $issue): ?>
                            <li>• <?= htmlspecialchars($issue) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold text-green-800 mb-4">
                        <i class="fas fa-check-circle mr-2"></i>Sessão OK
                    </h2>
                    <p class="text-green-700">Nenhum problema detectado com a sessão.</p>
                </div>
            <?php endif; ?>

            <!-- Variáveis da Sessão -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-database mr-2"></i>Variáveis da Sessão
                </h2>
                
                <?php if (empty($session_vars)): ?>
                    <p class="text-gray-500">Nenhuma variável na sessão.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chave</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($session_vars as $key => $value): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($key) ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500">
                                            <?php if (is_array($value)): ?>
                                                <pre class="text-xs"><?= htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) ?></pre>
                                            <?php else: ?>
                                                <?= htmlspecialchars($value) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= gettype($value) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Teste de Funções -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-vial mr-2"></i>Teste de Funções
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-medium mb-2">Teste de Autenticação:</h3>
                        <div class="space-y-2 text-sm">
                            <p><strong>isLoggedIn():</strong> <?= isLoggedIn() ? '✅ true' : '❌ false' ?></p>
                            <p><strong>isAdminLoggedIn():</strong> <?= isAdminLoggedIn() ? '✅ true' : '❌ false' ?></p>
                            <p><strong>getCurrentUser():</strong> <?= $currentUser ? '✅ OK' : '❌ Nulo' ?></p>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-medium mb-2">Teste de Permissões:</h3>
                        <div class="space-y-2 text-sm">
                            <p><strong>hasPermission('usuarios'):</strong> <?= hasPermission('usuarios') ? '✅ true' : '❌ false' ?></p>
                            <p><strong>hasPermission('pessoal'):</strong> <?= hasPermission('pessoal') ? '✅ true' : '❌ false' ?></p>
                            <p><strong>hasPermission('ocorrencias'):</strong> <?= hasPermission('ocorrencias') ? '✅ true' : '❌ false' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações de Teste -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-tools mr-2"></i>Ações de Teste
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="usuarios_simples.php" class="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-lg text-center">
                        <i class="fas fa-users text-2xl mb-2"></i>
                        <p class="font-semibold">Testar Gestão de Usuários</p>
                        <p class="text-sm opacity-75">Verificar se causa logout</p>
                    </a>
                    
                    <a href="dashboard.php" class="bg-green-600 hover:bg-green-700 text-white p-4 rounded-lg text-center">
                        <i class="fas fa-home text-2xl mb-2"></i>
                        <p class="font-semibold">Testar Dashboard</p>
                        <p class="text-sm opacity-75">Verificar se funciona</p>
                    </a>
                    
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white p-4 rounded-lg text-center">
                        <i class="fas fa-sign-out-alt text-2xl mb-2"></i>
                        <p class="font-semibold">Logout Manual</p>
                        <p class="text-sm opacity-75">Limpar sessão</p>
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 