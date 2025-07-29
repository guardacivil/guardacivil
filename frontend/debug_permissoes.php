<?php
// debug_permissoes.php - Debug das permissões do usuário
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

// Lista de todas as permissões disponíveis
$todas_permissoes = [
    'pessoal' => 'Gestão de Pessoal',
    'graduacoes' => 'Graduações',
    'setores' => 'Setores',
    'comunicacao' => 'Comunicação Interna',
    'escalas' => 'Gestão de Escalas',
    'minhas_escalas' => 'Minhas Escalas',
    'ocorrencias' => 'Registro de Ocorrências',
    'gerenciar_ocorrencias' => 'Gerenciar Ocorrências',
    'relatorios' => 'Relatórios',
    'relatorios_agendados' => 'Relatórios Agendados',
    'filtros_avancados' => 'Filtros Avançados',
    'relatorios_hierarquia' => 'Relatórios por Hierarquia',
    'usuarios' => 'Gestão de Usuários',
    'perfis' => 'Perfis e Permissões',
    'logs' => 'Logs do Sistema',
    'config' => 'Configurações Gerais',
    'db' => 'Banco de Dados',
    'alertas' => 'Alertas e Notificações',
    'suporte' => 'Suporte',
    'checklist' => 'Conferir Checklists'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Permissões do Usuário</title>
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
                        <i class="fas fa-bug mr-2"></i>Debug - Permissões do Usuário
                    </h1>
                    <p class="text-gray-600 mt-2">Análise detalhada das permissões</p>
                </div>
                <div class="flex space-x-2">
                    <a href="gerenciar_permissoes_usuarios.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-user-cog mr-2"></i>Gerenciar Permissões
                    </a>
                    <a href="dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                </div>
            </div>

            <!-- Informações do Usuário -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-user mr-2"></i>Informações do Usuário
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p><strong>Nome:</strong> <?= htmlspecialchars($currentUser['nome']) ?></p>
                        <p><strong>Perfil:</strong> <?= htmlspecialchars($currentUser['perfil']) ?></p>
                        <p><strong>Perfil ID:</strong> <?= $currentUser['perfil_id'] ?></p>
                    </div>
                    <div>
                        <p><strong>Admin:</strong> <?= $isAdmin ? '✅ Sim' : '❌ Não' ?></p>
                        <p><strong>Logado:</strong> <?= isLoggedIn() ? '✅ Sim' : '❌ Não' ?></p>
                        <p><strong>Total de Permissões:</strong> <?= count($permissoes_atuais) ?></p>
                    </div>
                </div>
            </div>

            <!-- Permissões Atuais -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-shield-alt mr-2"></i>Permissões Atuais do Perfil
                </h2>
                
                <?php if (empty($permissoes_atuais)): ?>
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Atenção:</strong> Este perfil não tem permissões definidas!
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                        <?php foreach ($todas_permissoes as $chave => $nome): ?>
                            <div class="flex items-center p-2 border rounded">
                                <span class="<?= in_array($chave, $permissoes_atuais) ? 'text-green-600' : 'text-red-600' ?> mr-2">
                                    <i class="fas fa-<?= in_array($chave, $permissoes_atuais) ? 'check' : 'times' ?>"></i>
                                </span>
                                <span class="text-sm"><?= htmlspecialchars($nome) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Teste de Funções -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-vial mr-2"></i>Teste de Funções de Permissão
                </h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissão</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">hasPermission()</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">hasMenuPermission()</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($todas_permissoes as $chave => $nome): ?>
                                <?php 
                                $has_perm = hasPermission($chave);
                                $has_menu = hasMenuPermission($chave);
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($nome) ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="<?= $has_perm ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= $has_perm ? '✅ Sim' : '❌ Não' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="<?= $has_menu ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= $has_menu ? '✅ Sim' : '❌ Não' ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($has_menu): ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                Visível no Menu
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                Oculto
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dados JSON -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-code mr-2"></i>Dados JSON das Permissões
                </h2>
                
                <div class="bg-gray-100 p-4 rounded-lg">
                    <pre class="text-sm overflow-x-auto"><?= json_encode($permissoes_atuais, JSON_PRETTY_PRINT) ?></pre>
                </div>
                
                <div class="mt-4">
                    <h3 class="font-medium mb-2">Informações de Debug:</h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• <strong>Session ID:</strong> <?= session_id() ?></li>
                        <li>• <strong>Perfil ID na Session:</strong> <?= $_SESSION['usuario_perfil_id'] ?? 'N/A' ?></li>
                        <li>• <strong>Permissões JSON:</strong> <?= $perfil['permissoes'] ?? 'N/A' ?></li>
                        <li>• <strong>isAdminLoggedIn():</strong> <?= isAdminLoggedIn() ? 'true' : 'false' ?></li>
                        <li>• <strong>isLoggedIn():</strong> <?= isLoggedIn() ? 'true' : 'false' ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 