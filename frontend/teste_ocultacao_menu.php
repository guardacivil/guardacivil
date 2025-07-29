<?php
// teste_ocultacao_menu.php - Teste específico da ocultação do menu
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = isAdminLoggedIn();

// Buscar permissões do perfil atual
$permissoes_atuais = [];
if (isset($_SESSION['usuario_perfil_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT permissoes FROM perfis WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_perfil_id']]);
        $perfil = $stmt->fetch();
        
        if ($perfil && $perfil['permissoes']) {
            $permissoes_atuais = json_decode($perfil['permissoes'], true) ?: [];
        }
    } catch (PDOException $e) {
        $erro = 'Erro ao buscar permissões: ' . $e->getMessage();
    }
}

// Lista de itens do menu para teste
$itens_teste = [
    'pessoal' => 'Gestão de Pessoal',
    'graduacoes' => 'Graduações',
    'setores' => 'Setores',
    'comunicacao' => 'Comunicação Interna',
    'escalas' => 'Gestão de Escalas',
    'minhas_escalas' => 'Minhas Escalas',
    'ocorrencias' => 'Registro de Ocorrências',
    'gerenciar_ocorrencias' => 'Gerenciar Ocorrências',
    'relatorios' => 'Relatórios',
    'usuarios' => 'Gestão de Usuários',
    'perfis' => 'Perfis e Permissões',
    'logs' => 'Logs do Sistema'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Ocultação do Menu</title>
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
                        <i class="fas fa-eye-slash mr-2"></i>Teste - Ocultação do Menu
                    </h1>
                    <p class="text-gray-600 mt-2">Verificação específica da ocultação dos itens</p>
                </div>
                <div class="flex space-x-2">
                    <a href="forcar_limpeza_permissoes.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-broom mr-2"></i>Limpar Permissões
                    </a>
                    <a href="debug_permissoes.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-bug mr-2"></i>Debug
                    </a>
                </div>
            </div>

            <!-- Status do Usuário -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-user mr-2"></i>Status do Usuário
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p><strong>Nome:</strong> <?= htmlspecialchars($currentUser['nome']) ?></p>
                        <p><strong>Perfil:</strong> <?= htmlspecialchars($currentUser['perfil']) ?></p>
                    </div>
                    <div>
                        <p><strong>Admin:</strong> <?= $isAdmin ? '✅ Sim' : '❌ Não' ?></p>
                        <p><strong>Permissões:</strong> <?= count($permissoes_atuais) ?></p>
                    </div>
                    <div>
                        <p><strong>Permissões Atuais:</strong></p>
                        <p class="text-sm text-gray-600"><?= empty($permissoes_atuais) ? 'Nenhuma' : implode(', ', $permissoes_atuais) ?></p>
                    </div>
                </div>
            </div>

            <!-- Teste de Ocultação -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-vial mr-2"></i>Teste de Ocultação - Item por Item
                </h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissão</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tem na Lista</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">hasPermission()</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aparece no Menu</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($itens_teste as $chave => $nome): ?>
                                <?php 
                                $tem_na_lista = in_array($chave, $permissoes_atuais);
                                $has_perm = hasPermission($chave);
                                $aparece_menu = $has_perm;
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($nome) ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $chave ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="<?= $tem_na_lista ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= $tem_na_lista ? '✅ Sim' : '❌ Não' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="<?= $has_perm ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= $has_perm ? '✅ Sim' : '❌ Não' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="<?= $aparece_menu ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= $aparece_menu ? '✅ Sim' : '❌ Não' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($aparece_menu): ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                Visível
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                OCULTO
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Simulação do Menu Real -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-bars mr-2"></i>Simulação do Menu Real (Como Deve Aparecer)
                </h2>
                
                <div class="bg-blue-900 text-white p-4 rounded-lg">
                    <h3 class="font-semibold mb-3">Menu Lateral - Itens Visíveis</h3>
                    
                    <?php if ($isAdmin): ?>
                        <div class="text-green-300 mb-2">
                            <i class="fas fa-crown mr-2"></i>ADMIN - Todos os itens visíveis
                        </div>
                        <div class="space-y-1 text-sm">
                            <div class="flex items-center">
                                <span class="text-green-400 mr-2">✅</span>
                                <span>Dashboard</span>
                            </div>
                            <?php foreach ($itens_teste as $chave => $nome): ?>
                                <div class="flex items-center">
                                    <span class="text-green-400 mr-2">✅</span>
                                    <span><?= htmlspecialchars($nome) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="flex items-center">
                                <span class="text-red-400 mr-2">🔴</span>
                                <span>Sair</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-yellow-300 mb-2">
                            <i class="fas fa-user mr-2"></i>USUÁRIO COMUM - Apenas itens permitidos
                        </div>
                        <div class="space-y-1 text-sm">
                            <div class="flex items-center">
                                <span class="text-green-400 mr-2">✅</span>
                                <span class="text-white">Dashboard (Sempre visível)</span>
                            </div>
                            <?php foreach ($itens_teste as $chave => $nome): ?>
                                <?php if (hasPermission($chave)): ?>
                                    <div class="flex items-center">
                                        <span class="text-green-400 mr-2">✅</span>
                                        <span class="text-white"><?= htmlspecialchars($nome) ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="flex items-center">
                                        <span class="text-red-400 mr-2">❌</span>
                                        <span class="text-gray-400"><?= htmlspecialchars($nome) ?> (OCULTO)</span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <div class="flex items-center">
                                <span class="text-red-400 mr-2">🔴</span>
                                <span class="text-white">Sair</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Debug da Função hasPermission -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-code mr-2"></i>Debug da Função hasPermission()
                </h2>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="font-medium mb-2">Informações de Debug:</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>• <strong>isAdminLoggedIn():</strong> <?= isAdminLoggedIn() ? 'true' : 'false' ?></li>
                            <li>• <strong>isLoggedIn():</strong> <?= isLoggedIn() ? 'true' : 'false' ?></li>
                            <li>• <strong>Session ID:</strong> <?= session_id() ?></li>
                            <li>• <strong>Perfil ID:</strong> <?= $_SESSION['usuario_perfil_id'] ?? 'N/A' ?></li>
                            <li>• <strong>Permissões JSON:</strong> <?= $perfil['permissoes'] ?? 'N/A' ?></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="font-medium mb-2">Teste de Função:</h3>
                        <div class="bg-gray-100 p-3 rounded">
                            <p class="text-sm"><strong>hasPermission('pessoal'):</strong> <?= hasPermission('pessoal') ? 'true' : 'false' ?></p>
                            <p class="text-sm"><strong>hasPermission('ocorrencias'):</strong> <?= hasPermission('ocorrencias') ? 'true' : 'false' ?></p>
                            <p class="text-sm"><strong>hasPermission('usuarios'):</strong> <?= hasPermission('usuarios') ? 'true' : 'false' ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 