<?php
require_once 'config.php';
require_once 'auth_check.php';

// Verificar permissão - apenas admin pode gerenciar permissões
if (!isAdminLoggedIn()) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
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

// Buscar todos os usuários com seus perfis
$stmt = $pdo->prepare("
    SELECT u.*, p.nome as perfil_nome, p.permissoes 
    FROM usuarios u 
    LEFT JOIN perfis p ON u.perfil_id = p.id 
    ORDER BY u.nome
");
$stmt->execute();
$usuarios = $stmt->fetchAll();

// Buscar todos os perfis
$perfis = $pdo->query('SELECT * FROM perfis ORDER BY nome')->fetchAll();

// Atualizar permissões se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['atualizar_perfil'])) {
        $perfil_id = intval($_POST['perfil_id']);
        $permissoes_selecionadas = $_POST['permissoes'] ?? [];
        $permissoes_json = json_encode($permissoes_selecionadas);
        
        $stmt = $pdo->prepare('UPDATE perfis SET permissoes = ? WHERE id = ?');
        if ($stmt->execute([$permissoes_json, $perfil_id])) {
            $msg = "Permissões do perfil atualizadas com sucesso!";
            logAction('atualizar_permissoes_perfil', 'perfis', $perfil_id);
        } else {
            $erro = "Erro ao atualizar permissões.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Permissões - Sistema Integrado da Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    
    <main class="content">
        <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Gerenciar Permissões do Menu</h2>
                <a href="usuarios.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                </a>
            </div>

            <?php if (isset($msg)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($erro)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <!-- Resumo de Permissões -->
            <div class="bg-blue-50 p-4 rounded-lg mb-6">
                <h3 class="text-lg font-semibold text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Como Funciona o Controle de Permissões
                </h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• <strong>Administradores:</strong> Veem o menu completo automaticamente</li>
                    <li>• <strong>Usuários comuns:</strong> Veem apenas as opções para as quais têm permissão</li>
                    <li>• <strong>Permissões:</strong> São definidas por perfil e aplicadas a todos os usuários do perfil</li>
                    <li>• <strong>Dashboard:</strong> Sempre visível para todos os usuários</li>
                </ul>
            </div>

            <!-- Lista de Usuários e suas Permissões -->
            <div class="space-y-6">
                <h3 class="text-xl font-semibold">Usuários e suas Permissões Atuais</h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3 border text-left">Usuário</th>
                                <th class="p-3 border text-left">Perfil</th>
                                <th class="p-3 border text-left">Status</th>
                                <th class="p-3 border text-left">Permissões</th>
                                <th class="p-3 border text-left">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 border">
                                        <div>
                                            <div class="font-medium"><?= htmlspecialchars($user['nome']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($user['usuario']) ?></div>
                                        </div>
                                    </td>
                                    <td class="p-3 border">
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                                            <?= htmlspecialchars($user['perfil_nome'] ?? 'Sem perfil') ?>
                                        </span>
                                    </td>
                                    <td class="p-3 border">
                                        <?php if ($user['ativo']): ?>
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">
                                                <i class="fas fa-check mr-1"></i>Ativo
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-sm">
                                                <i class="fas fa-times mr-1"></i>Inativo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 border">
                                        <?php
                                        $permissoes = [];
                                        if ($user['permissoes']) {
                                            $permissoes = json_decode($user['permissoes'], true) ?: [];
                                        }
                                        ?>
                                        <div class="text-sm">
                                            <?php if (empty($permissoes)): ?>
                                                <span class="text-gray-500">Nenhuma permissão</span>
                                            <?php else: ?>
                                                <div class="flex flex-wrap gap-1">
                                                    <?php foreach (array_slice($permissoes, 0, 3) as $perm): ?>
                                                        <span class="px-1 py-0.5 bg-gray-200 text-gray-700 rounded text-xs">
                                                            <?= htmlspecialchars($todas_permissoes[$perm] ?? $perm) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                    <?php if (count($permissoes) > 3): ?>
                                                        <span class="px-1 py-0.5 bg-blue-200 text-blue-700 rounded text-xs">
                                                            +<?= count($permissoes) - 3 ?> mais
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="p-3 border">
                                        <a href="editar_usuario.php?id=<?= $user['id'] ?>" 
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-edit mr-1"></i>Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Gerenciar Permissões por Perfil -->
            <div class="mt-8 bg-gray-50 p-6 rounded-lg">
                <h3 class="text-xl font-semibold mb-4">Gerenciar Permissões por Perfil</h3>
                
                <form method="post" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Selecione o Perfil:
                            </label>
                            <select name="perfil_id" id="perfil_select" 
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    onchange="carregarPermissoes()">
                                <option value="">Selecione um perfil...</option>
                                <?php foreach ($perfis as $perfil): ?>
                                    <option value="<?= $perfil['id'] ?>" 
                                            data-permissoes='<?= htmlspecialchars($perfil['permissoes'] ?? '[]') ?>'>
                                        <?= htmlspecialchars($perfil['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Ações:
                            </label>
                            <div class="flex space-x-2">
                                <button type="button" onclick="selecionarTodosPermissoes()" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm">
                                    Selecionar Todos
                                </button>
                                <button type="button" onclick="limparTodosPermissoes()" 
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded text-sm">
                                    Limpar Todos
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="permissoes_container" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Permissões do Menu:
                        </label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 bg-white p-4 rounded border">
                            <?php foreach ($todas_permissoes as $chave => $nome): ?>
                                <div class="flex items-center">
                                    <input type="checkbox" name="permissoes[]" 
                                           value="<?= $chave ?>" 
                                           id="perm_<?= $chave ?>"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="perm_<?= $chave ?>" class="ml-2 block text-sm text-gray-900">
                                        <?= htmlspecialchars($nome) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" name="atualizar_perfil" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition-colors">
                                <i class="fas fa-save mr-2"></i>Atualizar Permissões do Perfil
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function carregarPermissoes() {
            const select = document.getElementById('perfil_select');
            const container = document.getElementById('permissoes_container');
            const selectedOption = select.options[select.selectedIndex];
            
            if (select.value) {
                container.classList.remove('hidden');
                
                // Limpar todas as checkboxes
                document.querySelectorAll('input[name="permissoes[]"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Carregar permissões do perfil selecionado
                const permissoes = JSON.parse(selectedOption.dataset.permissoes || '[]');
                permissoes.forEach(perm => {
                    const checkbox = document.getElementById('perm_' + perm);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            } else {
                container.classList.add('hidden');
            }
        }
        
        function selecionarTodosPermissoes() {
            document.querySelectorAll('input[name="permissoes[]"]').forEach(checkbox => {
                checkbox.checked = true;
            });
        }
        
        function limparTodosPermissoes() {
            document.querySelectorAll('input[name="permissoes[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
        }
    </script>
</body>
</html> 