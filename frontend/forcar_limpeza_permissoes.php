<?php
// forcar_limpeza_permissoes.php - Forçar limpeza das permissões
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$msg = '';
$erro = '';

// Processar limpeza forçada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'limpar_forcado') {
        try {
            // Limpar TODAS as permissões de TODOS os perfis
            $stmt = $pdo->prepare("UPDATE perfis SET permissoes = '[]' WHERE id > 0");
            $stmt->execute();
            
            $msg = "✅ LIMPEZA FORÇADA REALIZADA! Todas as permissões foram removidas.";
            logAction('limpeza_forcada_permissoes', 'perfis');
        } catch (PDOException $e) {
            $erro = "❌ Erro ao limpar permissões: " . $e->getMessage();
        }
    }
    
    if ($acao === 'testar_usuario') {
        $usuario_id = intval($_POST['usuario_id']);
        $permissoes_teste = ['ocorrencias']; // Apenas 1 permissão para teste
        
        try {
            // Buscar perfil do usuário
            $stmt = $pdo->prepare("SELECT perfil_id FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $usuario = $stmt->fetch();
            
            if ($usuario) {
                // Configurar apenas 1 permissão
                $permissoes_json = json_encode($permissoes_teste);
                $stmt = $pdo->prepare('UPDATE perfis SET permissoes = ? WHERE id = ?');
                $stmt->execute([$permissoes_json, $usuario['perfil_id']]);
                
                $msg = "✅ Usuário configurado com apenas 1 permissão ('ocorrencias') para teste!";
                logAction('configurar_teste_usuario', 'usuarios', $usuario_id);
            } else {
                $erro = "❌ Usuário não encontrado.";
            }
        } catch (PDOException $e) {
            $erro = "❌ Erro ao configurar usuário: " . $e->getMessage();
        }
    }
}

// Buscar usuários
try {
    $stmt = $pdo->query('SELECT u.*, p.nome as perfil_nome FROM usuarios u LEFT JOIN perfis p ON u.perfil_id = p.id ORDER BY u.nome');
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
    $erro = 'Erro ao buscar usuários: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forçar Limpeza de Permissões</title>
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
                        <i class="fas fa-exclamation-triangle mr-2"></i>Forçar Limpeza de Permissões
                    </h1>
                    <p class="text-gray-600 mt-2">Limpeza forçada para testar o menu</p>
                </div>
                <div class="flex space-x-2">
                    <a href="teste_menu_permissoes.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-list mr-2"></i>Teste Menu
                    </a>
                    <a href="debug_permissoes.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-bug mr-2"></i>Debug
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

            <!-- Aviso Importante -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-red-800 mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>⚠️ ATENÇÃO!
                </h2>
                <p class="text-red-700 mb-4">
                    Esta página permite limpar TODAS as permissões de TODOS os usuários. 
                    Use apenas para testes!
                </p>
                <ul class="text-red-600 text-sm space-y-1">
                    <li>• Todas as permissões serão removidas</li>
                    <li>• Usuários comuns verão apenas o Dashboard</li>
                    <li>• Apenas administradores terão acesso completo</li>
                    <li>• Use para testar se o menu está funcionando corretamente</li>
                </ul>
            </div>

            <!-- Limpeza Forçada -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-broom mr-2"></i>Limpeza Forçada
                </h2>
                <p class="text-gray-600 mb-4">Remove TODAS as permissões de TODOS os perfis.</p>
                
                <form method="POST" onsubmit="return confirm('⚠️ ATENÇÃO! Isso removerá TODAS as permissões de TODOS os usuários! Tem certeza?')">
                    <input type="hidden" name="acao" value="limpar_forcado">
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold">
                        <i class="fas fa-trash mr-2"></i>LIMPAR TODAS AS PERMISSÕES
                    </button>
                </form>
            </div>

            <!-- Teste com Usuário Específico -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-user-cog mr-2"></i>Teste com Usuário Específico
                </h2>
                <p class="text-gray-600 mb-4">Configura um usuário com apenas 1 permissão para teste.</p>
                
                <form method="POST" class="flex items-end space-x-4">
                    <input type="hidden" name="acao" value="testar_usuario">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Selecionar Usuário:</label>
                        <select name="usuario_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Selecione um usuário...</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= $usuario['id'] ?>"><?= htmlspecialchars($usuario['nome']) ?> (<?= htmlspecialchars($usuario['perfil_nome'] ?? 'Sem perfil') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                        <i class="fas fa-test mr-2"></i>Configurar Teste
                    </button>
                </form>
                
                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <p class="text-sm text-blue-700">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Teste:</strong> O usuário selecionado terá apenas a permissão "ocorrencias". 
                        Ele deve ver apenas "Dashboard" e "Registro de Ocorrências" no menu.
                    </p>
                </div>
            </div>

            <!-- Instruções de Teste -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-blue-800 mb-4">
                    <i class="fas fa-play mr-2"></i>Como Testar
                </h2>
                <div class="space-y-3 text-blue-700">
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">1</span>
                        <div>
                            <p class="font-medium">Limpe todas as permissões</p>
                            <p class="text-sm">Clique em "LIMPAR TODAS AS PERMISSÕES"</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">2</span>
                        <div>
                            <p class="font-medium">Faça logout e login como usuário comum</p>
                            <p class="text-sm">Verifique se o menu mostra apenas "Dashboard"</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">3</span>
                        <div>
                            <p class="font-medium">Configure permissões específicas</p>
                            <p class="text-sm">Use "Gerenciar Permissões" para adicionar permissões</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">4</span>
                        <div>
                            <p class="font-medium">Teste novamente</p>
                            <p class="text-sm">Verifique se apenas os itens permitidos aparecem</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 