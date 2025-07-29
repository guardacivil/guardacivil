<?php
require_once 'config.php';
require_once 'auth_check.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();

// Verificar se é admin
if (!isAdminLoggedIn()) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

$msg = '';
$erro = '';

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

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'atualizar_usuario') {
        $usuario_id = intval($_POST['usuario_id']);
        $permissoes_selecionadas = $_POST['permissoes'] ?? [];
        
        try {
            // Buscar perfil do usuário
            $stmt = $pdo->prepare("SELECT perfil_id FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                // Atualizar permissões do perfil
                $permissoes_json = json_encode($permissoes_selecionadas);
                $stmt = $pdo->prepare('UPDATE perfis SET permissoes = ? WHERE id = ?');
                $stmt->execute([$permissoes_json, $usuario['perfil_id']]);
                
                $msg = "Permissões do usuário atualizadas com sucesso!";
                logAction('atualizar_permissoes_usuario', 'usuarios', $usuario_id);
            } else {
                $erro = "Usuário não encontrado.";
            }
        } catch (PDOException $e) {
            $erro = "Erro ao atualizar permissões: " . $e->getMessage();
        }
    }
}

// Buscar todos os usuários com seus perfis e permissões
$usuarios = $pdo->query("
    SELECT u.*, p.nome as perfil_nome, p.permissoes 
    FROM usuarios u 
    LEFT JOIN perfis p ON u.perfil_id = p.id 
    ORDER BY u.nome
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Permissões de Usuários - Sistema Integrado da Guarda Civil</title>
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
        <div class="max-w-6xl mx-auto bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">Gerenciar Permissões de Usuários</h2>
                <a href="usuarios.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                </a>
            </div>

            <?php if ($msg): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <div class="bg-blue-50 p-4 rounded-lg mb-6">
                <h3 class="text-lg font-semibold text-blue-800 mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Controle de Permissões
                </h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• <strong>Administradores:</strong> Têm acesso total a todos os itens do menu</li>
                    <li>• <strong>Usuários comuns:</strong> Veem apenas os itens para os quais têm permissão</li>
                    <li>• <strong>Dashboard:</strong> Sempre visível para todos os usuários</li>
                    <li>• <strong>Controle granular:</strong> Admin pode liberar/bloquear cada item individualmente</li>
                </ul>
            </div>

            <!-- Lista de Usuários -->
            <div class="space-y-6">
                <?php foreach ($usuarios as $user): ?>
                    <?php
                    $permissoes_atuais = [];
                    if ($user['permissoes']) {
                        $permissoes_atuais = json_decode($user['permissoes'], true) ?: [];
                    }
                    $is_admin_user = stripos($user['perfil_nome'], 'admin') !== false;
                    ?>
                    
                    <div class="bg-white rounded-lg border p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold"><?= htmlspecialchars($user['nome']) ?></h3>
                                <p class="text-gray-600"><?= htmlspecialchars($user['usuario']) ?></p>
                                <div class="flex items-center space-x-2 mt-2">
                                    <span class="px-2 py-1 rounded text-sm <?= $is_admin_user ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' ?>">
                                        <?= htmlspecialchars($user['perfil_nome'] ?? 'Sem perfil') ?>
                                    </span>
                                    <span class="px-2 py-1 rounded text-sm <?= $user['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $user['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($is_admin_user): ?>
                                <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                                    <i class="fas fa-crown mr-1"></i>Administrador
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!$is_admin_user): ?>
                            <form method="post" class="space-y-4">
                                <input type="hidden" name="acao" value="atualizar_usuario">
                                <input type="hidden" name="usuario_id" value="<?= $user['id'] ?>">
                                
                                <div>
                                    <h4 class="font-medium text-gray-700 mb-3">Permissões do Menu:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <?php foreach ($todas_permissoes as $chave => $nome): ?>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="permissoes[]" 
                                                       value="<?= $chave ?>" 
                                                       id="perm_<?= $user['id'] ?>_<?= $chave ?>"
                                                       <?= in_array($chave, $permissoes_atuais) ? 'checked' : '' ?> 
                                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <label for="perm_<?= $user['id'] ?>_<?= $chave ?>" class="ml-2 block text-sm text-gray-900">
                                                    <?= htmlspecialchars($nome) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <button type="button" onclick="selecionarTodos(<?= $user['id'] ?>)" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                        Selecionar Todos
                                    </button>
                                    <button type="button" onclick="limparTodos(<?= $user['id'] ?>)" 
                                            class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm">
                                        Limpar Todos
                                    </button>
                                    <button type="submit" 
                                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-1 rounded text-sm">
                                        <i class="fas fa-save mr-1"></i>Salvar Permissões
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <p class="text-purple-700 text-sm">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Administradores têm acesso total a todos os itens do menu automaticamente.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        function selecionarTodos(userId) {
            document.querySelectorAll(`input[name="permissoes[]"][id^="perm_${userId}_"]`).forEach(checkbox => {
                checkbox.checked = true;
            });
        }
        
        function limparTodos(userId) {
            document.querySelectorAll(`input[name="permissoes[]"][id^="perm_${userId}_"]`).forEach(checkbox => {
                checkbox.checked = false;
            });
        }
    </script>
</body>
</html> 