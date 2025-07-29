<?php
// diagnostico_usuarios.php - Diagnóstico completo do sistema de usuários
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
$diagnostico = [];

// 1. Verificar sessão
$diagnostico['sessao'] = [
    'logado' => isLoggedIn(),
    'admin' => isAdminLoggedIn(),
    'usuario_atual' => $currentUser,
    'session_id' => session_id(),
    'session_data' => $_SESSION
];

// 2. Verificar conexão com banco
try {
    $pdo->query('SELECT 1');
    $diagnostico['banco'] = ['status' => 'OK', 'mensagem' => 'Conexão funcionando'];
} catch (PDOException $e) {
    $diagnostico['banco'] = ['status' => 'ERRO', 'mensagem' => $e->getMessage()];
}

// 3. Verificar tabelas
$tabelas_necessarias = ['usuarios', 'perfis', 'logs'];
foreach ($tabelas_necessarias as $tabela) {
    try {
        $pdo->query("SELECT COUNT(*) FROM $tabela");
        $diagnostico['tabelas'][$tabela] = ['status' => 'OK'];
    } catch (PDOException $e) {
        $diagnostico['tabelas'][$tabela] = ['status' => 'ERRO', 'mensagem' => $e->getMessage()];
    }
}

// 4. Verificar permissões
$diagnostico['permissoes'] = [
    'hasPermission_usuarios' => hasPermission('usuarios'),
    'hasMenuPermission_usuarios' => hasMenuPermission('usuarios'),
    'hasPagePermission_usuarios' => hasPagePermission('usuarios.php'),
    'hasPagePermission_usuarios_corrigido' => hasPagePermission('usuarios_corrigido.php')
];

// 5. Verificar arquivos
$arquivos_necessarios = [
    'usuarios.php',
    'usuarios_corrigido.php',
    'auth_check.php',
    'config.php',
    'sidebar.php'
];

foreach ($arquivos_necessarios as $arquivo) {
    $diagnostico['arquivos'][$arquivo] = [
        'existe' => file_exists($arquivo),
        'legivel' => is_readable($arquivo),
        'tamanho' => file_exists($arquivo) ? filesize($arquivo) : 0
    ];
}

// 6. Verificar usuários no banco
try {
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM usuarios');
    $total_usuarios = $stmt->fetch()['total'];
    $diagnostico['usuarios_banco'] = ['total' => $total_usuarios];
} catch (PDOException $e) {
    $diagnostico['usuarios_banco'] = ['erro' => $e->getMessage()];
}

// 7. Verificar perfis no banco
try {
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM perfis');
    $total_perfis = $stmt->fetch()['total'];
    $diagnostico['perfis_banco'] = ['total' => $total_perfis];
} catch (PDOException $e) {
    $diagnostico['perfis_banco'] = ['erro' => $e->getMessage()];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Gestão de Usuários</title>
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
                        <i class="fas fa-stethoscope mr-2"></i>Diagnóstico - Gestão de Usuários
                    </h1>
                    <p class="text-gray-600 mt-2">Verificação completa do sistema</p>
                </div>
                <div class="flex space-x-2">
                    <a href="usuarios_corrigido.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-users mr-2"></i>Gestão de Usuários
                    </a>
                    <a href="dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                </div>
            </div>

            <!-- Resumo -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500">Status Login</p>
                            <p class="text-lg font-semibold <?= $diagnostico['sessao']['logado'] ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $diagnostico['sessao']['logado'] ? 'Logado' : 'Não Logado' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-crown text-purple-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500">Status Admin</p>
                            <p class="text-lg font-semibold <?= $diagnostico['sessao']['admin'] ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $diagnostico['sessao']['admin'] ? 'Admin' : 'Usuário' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-database text-green-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500">Banco de Dados</p>
                            <p class="text-lg font-semibold <?= $diagnostico['banco']['status'] === 'OK' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $diagnostico['banco']['status'] ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <i class="fas fa-shield-alt text-orange-600"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-gray-500">Permissão Usuários</p>
                            <p class="text-lg font-semibold <?= $diagnostico['permissoes']['hasPermission_usuarios'] ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $diagnostico['permissoes']['hasPermission_usuarios'] ? 'Sim' : 'Não' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalhes do Diagnóstico -->
            <div class="space-y-6">
                <!-- Sessão -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-user-circle mr-2"></i>Informações da Sessão
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p><strong>Logado:</strong> <?= $diagnostico['sessao']['logado'] ? 'Sim' : 'Não' ?></p>
                            <p><strong>Admin:</strong> <?= $diagnostico['sessao']['admin'] ? 'Sim' : 'Não' ?></p>
                            <p><strong>Session ID:</strong> <?= $diagnostico['sessao']['session_id'] ?></p>
                        </div>
                        <div>
                            <p><strong>Usuário:</strong> <?= htmlspecialchars($diagnostico['sessao']['usuario_atual']['nome'] ?? 'N/A') ?></p>
                            <p><strong>Perfil:</strong> <?= htmlspecialchars($diagnostico['sessao']['usuario_atual']['perfil'] ?? 'N/A') ?></p>
                            <p><strong>ID:</strong> <?= $diagnostico['sessao']['usuario_atual']['id'] ?? 'N/A' ?></p>
                        </div>
                    </div>
                </div>

                <!-- Permissões -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-shield-alt mr-2"></i>Verificação de Permissões
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p><strong>hasPermission('usuarios'):</strong> 
                                <span class="<?= $diagnostico['permissoes']['hasPermission_usuarios'] ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $diagnostico['permissoes']['hasPermission_usuarios'] ? 'Sim' : 'Não' ?>
                                </span>
                            </p>
                            <p><strong>hasMenuPermission('usuarios'):</strong> 
                                <span class="<?= $diagnostico['permissoes']['hasMenuPermission_usuarios'] ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $diagnostico['permissoes']['hasMenuPermission_usuarios'] ? 'Sim' : 'Não' ?>
                                </span>
                            </p>
                        </div>
                        <div>
                            <p><strong>hasPagePermission('usuarios.php'):</strong> 
                                <span class="<?= $diagnostico['permissoes']['hasPagePermission_usuarios'] ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $diagnostico['permissoes']['hasPagePermission_usuarios'] ? 'Sim' : 'Não' ?>
                                </span>
                            </p>
                            <p><strong>hasPagePermission('usuarios_corrigido.php'):</strong> 
                                <span class="<?= $diagnostico['permissoes']['hasPagePermission_usuarios_corrigido'] ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $diagnostico['permissoes']['hasPagePermission_usuarios_corrigido'] ? 'Sim' : 'Não' ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Banco de Dados -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-database mr-2"></i>Status do Banco de Dados
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <p><strong>Conexão:</strong> 
                                <span class="<?= $diagnostico['banco']['status'] === 'OK' ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $diagnostico['banco']['status'] ?>
                                </span>
                            </p>
                            <?php if (isset($diagnostico['banco']['mensagem'])): ?>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars($diagnostico['banco']['mensagem']) ?></p>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h3 class="font-medium mb-2">Tabelas:</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                <?php foreach ($diagnostico['tabelas'] as $tabela => $status): ?>
                                    <div class="flex items-center">
                                        <span class="<?= $status['status'] === 'OK' ? 'text-green-600' : 'text-red-600' ?> mr-2">
                                            <i class="fas fa-<?= $status['status'] === 'OK' ? 'check' : 'times' ?>"></i>
                                        </span>
                                        <span><?= $tabela ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p><strong>Total de Usuários:</strong> 
                                    <?= isset($diagnostico['usuarios_banco']['total']) ? $diagnostico['usuarios_banco']['total'] : 'Erro' ?>
                                </p>
                            </div>
                            <div>
                                <p><strong>Total de Perfis:</strong> 
                                    <?= isset($diagnostico['perfis_banco']['total']) ? $diagnostico['perfis_banco']['total'] : 'Erro' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Arquivos -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-file-code mr-2"></i>Verificação de Arquivos
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($diagnostico['arquivos'] as $arquivo => $info): ?>
                            <div class="border rounded p-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium"><?= $arquivo ?></span>
                                    <div class="flex space-x-2">
                                        <span class="<?= $info['existe'] ? 'text-green-600' : 'text-red-600' ?>">
                                            <i class="fas fa-<?= $info['existe'] ? 'check' : 'times' ?>"></i>
                                        </span>
                                        <span class="<?= $info['legivel'] ? 'text-green-600' : 'text-red-600' ?>">
                                            <i class="fas fa-<?= $info['legivel'] ? 'eye' : 'eye-slash' ?>"></i>
                                        </span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600">Tamanho: <?= $info['tamanho'] ?> bytes</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Ações Recomendadas -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
                <h2 class="text-xl font-semibold text-blue-800 mb-4">
                    <i class="fas fa-lightbulb mr-2"></i>Ações Recomendadas
                </h2>
                <div class="space-y-2">
                    <?php if (!$diagnostico['sessao']['logado']): ?>
                        <p class="text-blue-700">• <strong>Faça login</strong> para acessar o sistema</p>
                    <?php endif; ?>
                    
                    <?php if (!$diagnostico['permissoes']['hasPermission_usuarios']): ?>
                        <p class="text-blue-700">• <strong>Configure permissões</strong> para o usuário atual</p>
                    <?php endif; ?>
                    
                    <?php if ($diagnostico['banco']['status'] !== 'OK'): ?>
                        <p class="text-blue-700">• <strong>Verifique a conexão</strong> com o banco de dados</p>
                    <?php endif; ?>
                    
                    <?php if ($diagnostico['sessao']['logado'] && $diagnostico['permissoes']['hasPermission_usuarios']): ?>
                        <p class="text-green-700">• <strong>Tudo OK!</strong> Você pode acessar a gestão de usuários</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 