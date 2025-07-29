<?php
require_once 'config.php';
require_once 'auth_check.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();
$msg = '';
$erro = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    try {
        if ($acao === 'limpar_todos') {
            // Limpar todas as permissões de todos os perfis (exceto admin)
            $stmt = $pdo->prepare("UPDATE perfis SET permissoes = '[]' WHERE LOWER(nome) NOT LIKE '%admin%' AND LOWER(nome) != 'administrador'");
            $stmt->execute();
            $msg = "Todas as permissões foram removidas dos perfis comuns!";
            
        } elseif ($acao === 'configurar_basicas') {
            // Configurar permissões básicas para usuários comuns
            $permissoes_basicas = ['ocorrencias', 'minhas_escalas']; // Apenas ocorrências e minhas escalas
            
            $stmt = $pdo->prepare("UPDATE perfis SET permissoes = ? WHERE LOWER(nome) NOT LIKE '%admin%' AND LOWER(nome) != 'administrador'");
            $stmt->execute([json_encode($permissoes_basicas)]);
            $msg = "Permissões básicas configuradas para usuários comuns!";
            
        } elseif ($acao === 'criar_perfil_padrao') {
            // Criar perfil padrão sem permissões
            $stmt = $pdo->prepare("INSERT INTO perfis (nome, descricao, permissoes) VALUES (?, ?, ?)");
            $stmt->execute(['Usuário Padrão', 'Perfil com permissões limitadas', '[]']);
            $msg = "Perfil padrão criado com sucesso!";
        }
        
    } catch (PDOException $e) {
        $erro = "Erro ao processar: " . $e->getMessage();
    }
}

// Buscar perfis
$perfis = $pdo->query("SELECT * FROM perfis ORDER BY nome")->fetchAll();

// Buscar usuários
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
    <title>Limpar Permissões - Sistema Integrado da Guarda Civil</title>
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
                <h2 class="text-2xl font-bold">Limpar e Configurar Permissões</h2>
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

            <div class="bg-red-50 p-4 rounded-lg mb-6">
                <h3 class="text-lg font-semibold text-red-800 mb-2">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Atenção!
                </h3>
                <p class="text-sm text-red-700">
                    Esta página permite limpar todas as permissões dos usuários comuns. 
                    Use com cuidado, pois isso pode restringir o acesso dos usuários.
                </p>
            </div>

            <!-- Ações -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <form method="post" class="bg-yellow-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-yellow-800 mb-2">Limpar Todas as Permissões</h4>
                    <p class="text-sm text-yellow-700 mb-3">Remove todas as permissões dos perfis comuns</p>
                    <input type="hidden" name="acao" value="limpar_todos">
                    <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded w-full">
                        <i class="fas fa-trash mr-2"></i>Limpar Todas
                    </button>
                </form>

                <form method="post" class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-800 mb-2">Configurar Básicas</h4>
                    <p class="text-sm text-blue-700 mb-3">Define apenas permissões básicas (ocorrências, minhas escalas)</p>
                    <input type="hidden" name="acao" value="configurar_basicas">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full">
                        <i class="fas fa-cog mr-2"></i>Configurar Básicas
                    </button>
                </form>

                <form method="post" class="bg-green-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-green-800 mb-2">Criar Perfil Padrão</h4>
                    <p class="text-sm text-green-700 mb-3">Cria um perfil padrão sem permissões</p>
                    <input type="hidden" name="acao" value="criar_perfil_padrao">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded w-full">
                        <i class="fas fa-plus mr-2"></i>Criar Perfil
                    </button>
                </form>
            </div>

            <!-- Status Atual -->
            <div class="bg-white rounded-lg border">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold">Status Atual dos Perfis</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-3 text-left">Perfil</th>
                                <th class="p-3 text-left">Descrição</th>
                                <th class="p-3 text-left">Permissões</th>
                                <th class="p-3 text-left">Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perfis as $perfil): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3 font-medium"><?= htmlspecialchars($perfil['nome']) ?></td>
                                    <td class="p-3 text-sm text-gray-600"><?= htmlspecialchars($perfil['descricao'] ?? '') ?></td>
                                    <td class="p-3">
                                        <?php 
                                        $permissoes = json_decode($perfil['permissoes'], true);
                                        if (is_array($permissoes) && !empty($permissoes)) {
                                            echo "<span class='text-green-600'>" . count($permissoes) . " permissões</span>";
                                        } else {
                                            echo "<span class='text-red-600'>Sem permissões</span>";
                                        }
                                        ?>
                                    </td>
                                    <td class="p-3">
                                        <?php if (stripos($perfil['nome'], 'admin') !== false): ?>
                                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-sm">Admin</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-sm">Comum</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Usuários -->
            <div class="bg-white rounded-lg border mt-6">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold">Usuários e suas Permissões</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-3 text-left">Usuário</th>
                                <th class="p-3 text-left">Perfil</th>
                                <th class="p-3 text-left">Permissões</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $user): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-3">
                                        <div>
                                            <div class="font-medium"><?= htmlspecialchars($user['nome']) ?></div>
                                            <div class="text-sm text-gray-500"><?= htmlspecialchars($user['usuario']) ?></div>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 rounded text-sm <?= (stripos($user['perfil_nome'], 'admin') !== false) ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' ?>">
                                            <?= htmlspecialchars($user['perfil_nome'] ?? 'Sem perfil') ?>
                                        </span>
                                    </td>
                                    <td class="p-3">
                                        <?php 
                                        $permissoes = json_decode($user['permissoes'], true);
                                        if (is_array($permissoes) && !empty($permissoes)) {
                                            echo "<span class='text-green-600'>" . count($permissoes) . " permissões</span>";
                                        } else {
                                            echo "<span class='text-red-600'>Sem permissões</span>";
                                        }
                                        ?>
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