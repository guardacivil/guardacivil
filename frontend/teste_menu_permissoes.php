<?php
// teste_menu_permissoes.php - Teste do menu com permissões
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

// Lista de itens do menu com suas permissões
$itens_menu = [
    'dashboard' => ['nome' => 'Dashboard', 'permissao' => null, 'sempre_visivel' => true],
    'pessoal' => ['nome' => 'Gestão de Pessoal', 'permissao' => 'pessoal'],
    'graduacoes' => ['nome' => 'Graduações', 'permissao' => 'graduacoes'],
    'setores' => ['nome' => 'Setores', 'permissao' => 'setores'],
    'comunicacao' => ['nome' => 'Comunicação Interna', 'permissao' => 'comunicacao'],
    'escalas' => ['nome' => 'Gestão de Escalas', 'permissao' => 'escalas'],
    'minhas_escalas' => ['nome' => 'Minhas Escalas', 'permissao' => 'minhas_escalas'],
    'ocorrencias' => ['nome' => 'Registro de Ocorrências', 'permissao' => 'ocorrencias'],
    'gerenciar_ocorrencias' => ['nome' => 'Gerenciar Ocorrências', 'permissao' => 'gerenciar_ocorrencias'],
    'relatorios' => ['nome' => 'Relatórios', 'permissao' => 'relatorios'],
    'relatorios_agendados' => ['nome' => 'Relatórios Agendados', 'permissao' => 'relatorios_agendados'],
    'filtros_avancados' => ['nome' => 'Filtros Avançados', 'permissao' => 'filtros_avancados'],
    'relatorios_hierarquia' => ['nome' => 'Relatórios por Hierarquia', 'permissao' => 'relatorios_hierarquia'],
    'usuarios' => ['nome' => 'Gestão de Usuários', 'permissao' => 'usuarios'],
    'perfis' => ['nome' => 'Perfis e Permissões', 'permissao' => 'perfis'],
    'logs' => ['nome' => 'Logs do Sistema', 'permissao' => 'logs'],
    'config' => ['nome' => 'Configurações Gerais', 'permissao' => 'config'],
    'db' => ['nome' => 'Banco de Dados', 'permissao' => 'db'],
    'alertas' => ['nome' => 'Alertas e Notificações', 'permissao' => 'alertas'],
    'suporte' => ['nome' => 'Suporte', 'permissao' => 'suporte'],
    'checklist' => ['nome' => 'Conferir Checklists', 'permissao' => 'checklist']
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Menu com Permissões</title>
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
                        <i class="fas fa-list mr-2"></i>Teste - Menu com Permissões
                    </h1>
                    <p class="text-gray-600 mt-2">Verificação do que deve aparecer no menu</p>
                </div>
                <div class="flex space-x-2">
                    <a href="debug_permissoes.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-bug mr-2"></i>Debug
                    </a>
                    <a href="limpar_e_configurar_permissoes.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-broom mr-2"></i>Limpar Permissões
                    </a>
                </div>
            </div>

            <!-- Informações do Usuário -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-user mr-2"></i>Informações do Usuário
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p><strong>Nome:</strong> <?= htmlspecialchars($currentUser['nome']) ?></p>
                        <p><strong>Perfil:</strong> <?= htmlspecialchars($currentUser['perfil']) ?></p>
                    </div>
                    <div>
                        <p><strong>Admin:</strong> <?= $isAdmin ? '✅ Sim' : '❌ Não' ?></p>
                        <p><strong>Total de Permissões:</strong> <?= count($permissoes_atuais) ?></p>
                    </div>
                    <div>
                        <p><strong>Permissões:</strong></p>
                        <p class="text-sm text-gray-600"><?= empty($permissoes_atuais) ? 'Nenhuma' : implode(', ', $permissoes_atuais) ?></p>
                    </div>
                </div>
            </div>

            <!-- Simulação do Menu -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-bars mr-2"></i>Simulação do Menu Lateral
                </h2>
                
                <div class="bg-blue-900 text-white p-4 rounded-lg">
                    <h3 class="font-semibold mb-3">Menu Lateral (Simulado)</h3>
                    
                    <?php if ($isAdmin): ?>
                        <div class="text-green-300 mb-2">
                            <i class="fas fa-crown mr-2"></i>ADMIN - Menu Completo
                        </div>
                        <div class="space-y-1 text-sm">
                            <?php foreach ($itens_menu as $chave => $item): ?>
                                <div class="flex items-center">
                                    <span class="text-green-400 mr-2">✅</span>
                                    <span><?= htmlspecialchars($item['nome']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-yellow-300 mb-2">
                            <i class="fas fa-user mr-2"></i>USUÁRIO COMUM - Menu Limitado
                        </div>
                        <div class="space-y-1 text-sm">
                            <?php foreach ($itens_menu as $chave => $item): ?>
                                <?php 
                                $deve_aparecer = false;
                                if ($item['sempre_visivel']) {
                                    $deve_aparecer = true;
                                } elseif ($item['permissao'] && hasPermission($item['permissao'])) {
                                    $deve_aparecer = true;
                                }
                                ?>
                                <div class="flex items-center">
                                    <?php if ($deve_aparecer): ?>
                                        <span class="text-green-400 mr-2">✅</span>
                                        <span class="text-white"><?= htmlspecialchars($item['nome']) ?></span>
                                    <?php else: ?>
                                        <span class="text-red-400 mr-2">❌</span>
                                        <span class="text-gray-400"><?= htmlspecialchars($item['nome']) ?> (Oculto)</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Análise Detalhada -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-search mr-2"></i>Análise Detalhada dos Itens
                </h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item do Menu</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissão</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tem Permissão</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aparece no Menu</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($itens_menu as $chave => $item): ?>
                                <?php 
                                $tem_permissao = false;
                                $aparece_menu = false;
                                
                                if ($isAdmin) {
                                    $tem_permissao = true;
                                    $aparece_menu = true;
                                } elseif ($item['sempre_visivel']) {
                                    $tem_permissao = true;
                                    $aparece_menu = true;
                                } elseif ($item['permissao']) {
                                    $tem_permissao = hasPermission($item['permissao']);
                                    $aparece_menu = $tem_permissao;
                                }
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($item['nome']) ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $item['permissao'] ?: 'Sempre visível' ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="<?= $tem_permissao ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= $tem_permissao ? '✅ Sim' : '❌ Não' ?>
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
        </div>
    </main>
</body>
</html> 