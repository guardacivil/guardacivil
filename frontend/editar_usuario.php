<?php
require_once 'config.php';
require_once 'auth_check.php';

// Verificar se o usuário está logado
requireLogin();

// Temporariamente permitir acesso a todos os usuários logados para teste
// if (!hasPermission('usuarios') && !isAdminLoggedIn()) {
//     header('Location: dashboard.php?error=permission_denied');
//     exit;
// }

// Verifica se o ID foi passado
if (!isset($_GET['id'])) {
    echo '<p>ID do usuário não informado.</p>';
    exit;
}

$id = intval($_GET['id']);

// Busca os dados do usuário
$stmt = $pdo->prepare('SELECT u.*, p.nome as perfil_nome, p.permissoes FROM usuarios u LEFT JOIN perfis p ON u.perfil_id = p.id WHERE u.id = ?');
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    echo '<p>Usuário não encontrado.</p>';
    exit;
}

// Buscar perfis disponíveis
$perfis = $pdo->query('SELECT * FROM perfis ORDER BY nome')->fetchAll();

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

// Obter permissões atuais do usuário
$permissoes_atuais = [];
if ($usuario['permissoes']) {
    $permissoes_atuais = json_decode($usuario['permissoes'], true) ?: [];
}

// Atualiza os dados se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $usuario_login = trim($_POST['usuario']);
    $perfil_id = intval($_POST['perfil_id']);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Obter permissões selecionadas
    $permissoes_selecionadas = $_POST['permissoes'] ?? [];
    $permissoes_json = json_encode($permissoes_selecionadas);
    
    // Verificar se o usuário já existe (exceto o atual)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE usuario = ? AND id != ?');
    $stmt->execute([$usuario_login, $id]);
    if ($stmt->fetchColumn() > 0) {
        $erro = "Este nome de usuário já está em uso.";
    } else {
        // Atualizar usuário e permissões do usuário
        $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, usuario = ?, perfil_id = ?, ativo = ?, permissoes = ? WHERE id = ?');
        if ($stmt->execute([$nome, $usuario_login, $perfil_id, $ativo, $permissoes_json, $id])) {
            $msg = "Usuário atualizado com sucesso!";
            logAction('editar_usuario', 'usuarios', $id);
            // Atualiza os dados exibidos
            $usuario['nome'] = $nome;
            $usuario['usuario'] = $usuario_login;
            $usuario['perfil_id'] = $perfil_id;
            $usuario['ativo'] = $ativo;
            $usuario['permissoes'] = $permissoes_json;
            $permissoes_atuais = $permissoes_selecionadas;
        } else {
            $erro = "Erro ao atualizar usuário.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Sistema Integrado da Guarda Civil</title>
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
        <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Editar Usuário</h2>
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

            <form method="post" class="space-y-6">
                <!-- Informações Básicas -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">Informações Básicas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nome Completo:
                            </label>
                            <input type="text" name="nome" 
                                   value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nome de Usuário:
                            </label>
                            <input type="text" name="usuario" 
                                   value="<?= htmlspecialchars($usuario['usuario'] ?? '') ?>" 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                   required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Perfil:
                            </label>
                            <select name="perfil_id" 
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    required>
                                <option value="">Selecione um perfil...</option>
                                <?php foreach ($perfis as $perfil): ?>
                                    <option value="<?= $perfil['id'] ?>" 
                                            <?= ($usuario['perfil_id'] ?? '') == $perfil['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($perfil['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="ativo" id="ativo" 
                                   <?= ($usuario['ativo'] ?? 1) ? 'checked' : '' ?> 
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="ativo" class="ml-2 block text-sm text-gray-900">
                                Usuário Ativo
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Controle de Permissões do Menu -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4 text-blue-800">
                        <i class="fas fa-shield-alt mr-2"></i>Controle de Permissões do Menu
                    </h3>
                    <p class="text-sm text-blue-600 mb-4">
                        Selecione quais opções do menu lateral este usuário poderá acessar:
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($todas_permissoes as $chave => $nome): ?>
                            <div class="flex items-center">
                                <input type="checkbox" name="permissoes[]" 
                                       value="<?= $chave ?>" 
                                       id="perm_<?= $chave ?>"
                                       <?= in_array($chave, $permissoes_atuais) ? 'checked' : '' ?> 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="perm_<?= $chave ?>" class="ml-2 block text-sm text-gray-900">
                                    <?= htmlspecialchars($nome) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 flex space-x-2">
                        <button type="button" onclick="selecionarTodos()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                            Selecionar Todos
                        </button>
                        <button type="button" onclick="limparTodos()" 
                                class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm">
                            Limpar Todos
                        </button>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-4">
                    <a href="usuarios.php" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-colors">
                        <i class="fas fa-save mr-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function selecionarTodos() {
            document.querySelectorAll('input[name="permissoes[]"]').forEach(checkbox => {
                checkbox.checked = true;
            });
        }
        
        function limparTodos() {
            document.querySelectorAll('input[name="permissoes[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
        }
    </script>
</body>
</html> 