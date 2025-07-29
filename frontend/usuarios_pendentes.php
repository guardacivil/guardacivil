<?php
// usuarios_pendentes.php - Gerenciar usuários pendentes
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
$msg = '';
$erro = '';

// Verificar se é admin
if (!isAdminLoggedIn()) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'liberar_usuario') {
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
                
                $msg = "✅ Usuário liberado com sucesso! Permissões configuradas.";
                logAction('liberar_usuario', 'usuarios', $usuario_id);
            } else {
                $erro = "❌ Usuário não encontrado.";
            }
        } catch (PDOException $e) {
            $erro = "❌ Erro ao liberar usuário: " . $e->getMessage();
        }
    }
    
    if ($acao === 'liberar_todos_basico') {
        try {
            // Liberar todos os usuários com permissões básicas
            $permissoes_basicas = ['ocorrencias', 'minhas_escalas'];
            $permissoes_json = json_encode($permissoes_basicas);
            
            $stmt = $pdo->prepare("UPDATE perfis SET permissoes = ? WHERE permissoes = '[]' OR permissoes IS NULL");
            $stmt->execute([$permissoes_json]);
            
            $msg = "✅ Todos os usuários pendentes foram liberados com permissões básicas!";
            logAction('liberar_todos_basico', 'perfis');
        } catch (PDOException $e) {
            $erro = "❌ Erro ao liberar usuários: " . $e->getMessage();
        }
    }
}

// Buscar usuários pendentes (sem permissões)
try {
    $stmt = $pdo->query('
        SELECT u.*, p.nome as perfil_nome, p.permissoes 
        FROM usuarios u 
        LEFT JOIN perfis p ON u.perfil_id = p.id 
        WHERE (p.permissoes = "[]" OR p.permissoes IS NULL OR p.permissoes = "")
        AND u.ativo = 1
        ORDER BY u.nome
    ');
    $usuarios_pendentes = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Erro ao buscar usuários pendentes: ' . $e->getMessage();
    $usuarios_pendentes = [];
}

// Lista de permissões disponíveis
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
    <title>Usuários Pendentes - Sistema Integrado da Guarda Civil</title>
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
                        <i class="fas fa-user-clock mr-2"></i>Usuários Pendentes
                    </h1>
                    <p class="text-gray-600 mt-2">Gerenciar usuários aguardando liberação</p>
                </div>
                <div class="flex space-x-2">
                    <a href="usuarios_simples.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-users mr-2"></i>Todos os Usuários
                    </a>
                    <a href="dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                </div>
            </div>

            <!-- Mensagens -->
            <?php if ($msg): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>

            <!-- Estatísticas -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-chart-bar mr-2"></i>Estatísticas
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="bg-yellow-100 text-yellow-800 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-user-clock text-2xl"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800"><?= count($usuarios_pendentes) ?></p>
                        <p class="text-sm text-gray-600">Usuários Pendentes</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-green-100 text-green-800 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-user-check text-2xl"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800">0</p>
                        <p class="text-sm text-gray-600">Liberados Hoje</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-blue-100 text-blue-800 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-clock text-2xl"></i>
                        </div>
                        <p class="text-2xl font-bold text-gray-800">24h</p>
                        <p class="text-sm text-gray-600">Tempo Médio</p>
                    </div>
                </div>
            </div>

            <!-- Ação em Massa -->
            <?php if (!empty($usuarios_pendentes)): ?>
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4"><i class="fas fa-bolt mr-2"></i>Ação em Massa</h2>
                    <p class="text-gray-600 mb-4">Liberar todos os usuários pendentes com permissões básicas.</p>
                    
                    <form method="POST" onsubmit="return confirm('Liberar todos os usuários pendentes com permissões básicas?')">
                        <input type="hidden" name="acao" value="liberar_todos_basico">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold"><i class="fas fa-check-double mr-2"></i>Liberar Todos (Básico)</button>
                    </form>
                    
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Permissões básicas:</strong> Registro de Ocorrências, Minhas Escalas
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Lista de Usuários Pendentes -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-list mr-2"></i>Usuários Aguardando Liberação
                </h2>
                
                <?php if (empty($usuarios_pendentes)): ?>
                    <div class="text-center py-8">
                        <div class="bg-green-100 text-green-800 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Nenhum usuário pendente!</h3>
                        <p class="text-gray-600">Todos os usuários já foram liberados.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($usuarios_pendentes as $usuario): ?>
                            <div class="border rounded-lg p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($usuario['nome']) ?></h3>
                                        <p class="text-gray-600"><?= htmlspecialchars($usuario['usuario']) ?></p>
                                        <p class="text-sm text-gray-500">Perfil: <?= htmlspecialchars($usuario['perfil_nome'] ?? 'Sem perfil') ?></p>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">
                                            <i class="fas fa-clock mr-1"></i>Aguardando
                                        </span>
                                    </div>
                                </div>
                                
                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="acao" value="liberar_usuario">
                                    <input type="hidden" name="usuario_id" value="<?= $usuario['id'] ?>">
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Selecionar Permissões para <?= htmlspecialchars($usuario['nome']) ?>:
                                        </label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                            <?php foreach ($todas_permissoes as $chave => $nome): ?>
                                                <div class="flex items-center">
                                                    <input type="checkbox" name="permissoes[]" 
                                                           value="<?= $chave ?>" 
                                                           id="perm_<?= $usuario['id'] ?>_<?= $chave ?>"
                                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                    <label for="perm_<?= $usuario['id'] ?>_<?= $chave ?>" class="ml-2 block text-sm text-gray-900">
                                                        <?= htmlspecialchars($nome) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-2">
                                        <button type="button" onclick="selecionarTodos(<?= $usuario['id'] ?>)" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                            Selecionar Todos
                                        </button>
                                        <button type="button" onclick="limparTodos(<?= $usuario['id'] ?>)" 
                                                class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm">
                                            Limpar Todos
                                        </button>
                                        <button type="submit" 
                                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-1 rounded text-sm">
                                            <i class="fas fa-check mr-1"></i>Liberar Usuário
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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