<?php
// limpar_e_configurar_permissoes.php - Limpar e reconfigurar permissões
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

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'limpar_todas') {
        try {
            // Limpar todas as permissões de todos os perfis
            $stmt = $pdo->prepare("UPDATE perfis SET permissoes = '[]' WHERE id > 0");
            $stmt->execute();
            
            $msg = "Todas as permissões foram limpas com sucesso!";
            logAction('limpar_todas_permissoes', 'perfis');
        } catch (PDOException $e) {
            $erro = "Erro ao limpar permissões: " . $e->getMessage();
        }
    }
    
    if ($acao === 'configurar_basicas') {
        try {
            // Configurar permissões básicas para todos os perfis
            $permissoes_basicas = ['ocorrencias', 'minhas_escalas'];
            $permissoes_json = json_encode($permissoes_basicas);
            
            $stmt = $pdo->prepare("UPDATE perfis SET permissoes = ? WHERE id > 0");
            $stmt->execute([$permissoes_json]);
            
            $msg = "Permissões básicas configuradas com sucesso!";
            logAction('configurar_permissoes_basicas', 'perfis');
        } catch (PDOException $e) {
            $erro = "Erro ao configurar permissões básicas: " . $e->getMessage();
        }
    }
    
    if ($acao === 'configurar_admin') {
        $perfil_id = intval($_POST['perfil_id']);
        $permissoes_admin = [
            'pessoal', 'graduacoes', 'setores', 'comunicacao', 'escalas', 'minhas_escalas',
            'ocorrencias', 'gerenciar_ocorrencias', 'relatorios', 'relatorios_agendados',
            'filtros_avancados', 'relatorios_hierarquia', 'usuarios', 'perfis', 'logs',
            'config', 'db', 'alertas', 'suporte', 'checklist'
        ];
        
        try {
            $permissoes_json = json_encode($permissoes_admin);
            $stmt = $pdo->prepare("UPDATE perfis SET permissoes = ? WHERE id = ?");
            $stmt->execute([$permissoes_json, $perfil_id]);
            
            $msg = "Perfil configurado como administrador com sucesso!";
            logAction('configurar_perfil_admin', 'perfis', $perfil_id);
        } catch (PDOException $e) {
            $erro = "Erro ao configurar perfil admin: " . $e->getMessage();
        }
    }
}

// Buscar perfis
try {
    $stmt = $pdo->query('SELECT * FROM perfis ORDER BY nome');
    $perfis = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = 'Erro ao buscar perfis: ' . $e->getMessage();
    $perfis = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpar e Configurar Permissões</title>
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
                        <i class="fas fa-broom mr-2"></i>Limpar e Configurar Permissões
                    </h1>
                    <p class="text-gray-600 mt-2">Gerenciamento em massa de permissões</p>
                </div>
                <div class="flex space-x-2">
                    <a href="debug_permissoes.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-bug mr-2"></i>Debug Permissões
                    </a>
                    <a href="gerenciar_permissoes_usuarios.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-user-cog mr-2"></i>Gerenciar Individual
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

            <!-- Ações em Massa -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Limpar Todas -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-trash mr-2"></i>Limpar Todas as Permissões
                    </h2>
                    <p class="text-gray-600 mb-4">Remove todas as permissões de todos os perfis.</p>
                    <form method="POST" onsubmit="return confirm('Tem certeza? Isso removerá TODAS as permissões!')">
                        <input type="hidden" name="acao" value="limpar_todas">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-broom mr-2"></i>Limpar Todas
                        </button>
                    </form>
                </div>

                <!-- Configurar Básicas -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-cog mr-2"></i>Configurar Permissões Básicas
                    </h2>
                    <p class="text-gray-600 mb-4">Define permissões básicas para todos os perfis.</p>
                    <form method="POST">
                        <input type="hidden" name="acao" value="configurar_basicas">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-check mr-2"></i>Configurar Básicas
                        </button>
                    </form>
                </div>
            </div>

            <!-- Configurar Admin por Perfil -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-crown mr-2"></i>Configurar Perfil como Administrador
                </h2>
                <p class="text-gray-600 mb-4">Define um perfil específico com todas as permissões de administrador.</p>
                
                <form method="POST" class="flex items-end space-x-4">
                    <input type="hidden" name="acao" value="configurar_admin">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Selecionar Perfil:</label>
                        <select name="perfil_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Selecione um perfil...</option>
                            <?php foreach ($perfis as $perfil): ?>
                                <option value="<?= $perfil['id'] ?>"><?= htmlspecialchars($perfil['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-crown mr-2"></i>Configurar Admin
                    </button>
                </form>
            </div>

            <!-- Lista de Perfis -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-users mr-2"></i>Perfis e Permissões Atuais
                </h2>
                
                <?php if (empty($perfis)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p>Nenhum perfil encontrado.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Permissões</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($perfis as $perfil): ?>
                                    <?php 
                                    $permissoes = [];
                                    if ($perfil['permissoes']) {
                                        $permissoes = json_decode($perfil['permissoes'], true) ?: [];
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?= $perfil['id'] ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($perfil['nome']) ?></td>
                                        <td class="px-4 py-4 text-sm text-gray-500">
                                            <?php if (empty($permissoes)): ?>
                                                <span class="text-red-600">Nenhuma permissão</span>
                                            <?php else: ?>
                                                <span class="text-green-600"><?= implode(', ', $permissoes) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?= count($permissoes) > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= count($permissoes) ?> permissões
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html> 