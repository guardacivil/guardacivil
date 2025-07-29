<?php
// usuarios_simples.php - Versão simplificada da gestão de usuários
require_once 'auth_check.php';
require_once 'config.php';

// Verificar apenas se está logado - SEM verificações extras
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
$msg = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'criar_usuario') {
        $nome = $_POST['nome'] ?? '';
        $usuario = $_POST['usuario'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $perfil_id = $_POST['perfil_id'] ?? '';

        if ($nome && $usuario && $senha && $perfil_id) {
            try {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare('INSERT INTO usuarios (nome, usuario, senha, perfil_id, ativo) VALUES (?, ?, ?, ?, 1)');
                $stmt->execute([$nome, $usuario, $senha_hash, $perfil_id]);
                
                $msg = 'Usuário criado com sucesso!';
            } catch (PDOException $e) {
                $msg = 'Erro ao criar usuário: ' . $e->getMessage();
            }
        } else {
            $msg = 'Todos os campos são obrigatórios!';
        }
    }
}

// Buscar usuários
try {
    $stmt = $pdo->query('
        SELECT u.*, p.nome as perfil_nome 
        FROM usuarios u 
        LEFT JOIN perfis p ON u.perfil_id = p.id 
        ORDER BY u.nome
    ');
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
    $msg = 'Erro ao buscar usuários: ' . $e->getMessage();
}

// Buscar perfis
try {
    $stmt = $pdo->query('SELECT * FROM perfis ORDER BY nome');
    $perfis = $stmt->fetchAll();
} catch (PDOException $e) {
    $perfis = [];
    $msg = 'Erro ao buscar perfis: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - Sistema Integrado da Guarda Civil</title>
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
            <!-- Cabeçalho -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-users mr-2"></i>Gestão de Usuários
                    </h1>
                    <p class="text-gray-600 mt-2">Versão Simplificada - Sem Verificações Complexas</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="usuarios_pendentes.php" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-user-clock mr-2"></i>Usuários Pendentes
                    </a>
                    <a href="teste_boas_vindas.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-heart mr-2"></i>Teste Boas-vindas
                    </a>
                    <div class="text-gray-600 text-sm bg-white px-3 py-2 rounded-lg">
                        <i class="fas fa-user mr-2"></i>
                        <?= htmlspecialchars($currentUser['nome']) ?> 
                        (<?= htmlspecialchars($currentUser['perfil']) ?>)
                    </div>
                </div>
            </div>

            <!-- Mensagem -->
            <?php if ($msg): ?>
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-info-circle mr-2"></i><?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de criação -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-user-plus mr-2"></i>Novo Usuário
                </h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="acao" value="criar_usuario">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo</label>
                            <input name="nome" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Usuário (login)</label>
                            <input name="usuario" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
                            <input name="senha" type="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Perfil</label>
                            <select name="perfil_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <option value="">Selecione um perfil...</option>
                                <?php foreach ($perfis as $perfil): ?>
                                    <option value="<?= $perfil['id'] ?>"><?= htmlspecialchars($perfil['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                            <i class="fas fa-save mr-2"></i>Criar Usuário
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de usuários -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-users mr-2"></i>Usuários Cadastrados (<?= count($usuarios) ?>)
                </h2>
                
                <?php if (empty($usuarios)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p>Nenhum usuário cadastrado ainda.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Perfil</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900"><?= $usuario['id'] ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($usuario['nome']) ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($usuario['usuario']) ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($usuario['perfil_nome'] ?? 'Sem perfil') ?></td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?= $usuario['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="editar_usuario.php?id=<?= $usuario['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-edit mr-1"></i>Editar
                                            </a>
                                            <a href="gerenciar_permissoes_usuario.php?id=<?= $usuario['id'] ?>" class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-key mr-1"></i>Permissões
                                            </a>
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