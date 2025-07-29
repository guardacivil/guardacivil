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
    $usuario_id = intval($_POST['usuario_id']);
    $tornar_admin = isset($_POST['tornar_admin']);
    
    try {
        if ($tornar_admin) {
            // Criar perfil de administrador se não existir
            $stmt = $pdo->prepare("SELECT id FROM perfis WHERE LOWER(nome) LIKE '%admin%' OR LOWER(nome) = 'administrador'");
            $stmt->execute();
            $admin_perfil = $stmt->fetch();
            
            if (!$admin_perfil) {
                // Criar perfil de administrador
                $stmt = $pdo->prepare("INSERT INTO perfis (nome, descricao, permissoes) VALUES (?, ?, ?)");
                $todas_permissoes = [
                    'pessoal', 'graduacoes', 'setores', 'comunicacao', 'escalas', 'minhas_escalas',
                    'ocorrencias', 'gerenciar_ocorrencias', 'relatorios', 'relatorios_agendados',
                    'filtros_avancados', 'relatorios_hierarquia', 'usuarios', 'perfis', 'logs',
                    'config', 'db', 'alertas', 'suporte', 'checklist'
                ];
                $permissoes_json = json_encode($todas_permissoes);
                $stmt->execute(['Administrador', 'Perfil com todas as permissões', $permissoes_json]);
                $admin_perfil_id = $pdo->lastInsertId();
            } else {
                $admin_perfil_id = $admin_perfil['id'];
            }
            
            // Atualizar usuário para perfil de administrador
            $stmt = $pdo->prepare("UPDATE usuarios SET perfil_id = ? WHERE id = ?");
            $stmt->execute([$admin_perfil_id, $usuario_id]);
            
            $msg = "Usuário configurado como administrador com sucesso!";
        } else {
            // Remover perfil de administrador (definir para perfil padrão)
            $stmt = $pdo->prepare("SELECT id FROM perfis WHERE LOWER(nome) NOT LIKE '%admin%' AND LOWER(nome) != 'administrador' LIMIT 1");
            $stmt->execute();
            $perfil_padrao = $stmt->fetch();
            
            if ($perfil_padrao) {
                $stmt = $pdo->prepare("UPDATE usuarios SET perfil_id = ? WHERE id = ?");
                $stmt->execute([$perfil_padrao['id'], $usuario_id]);
                $msg = "Perfil de administrador removido com sucesso!";
            } else {
                $erro = "Não foi possível encontrar um perfil padrão para atribuir.";
            }
        }
    } catch (PDOException $e) {
        $erro = "Erro ao configurar usuário: " . $e->getMessage();
    }
}

// Buscar usuários
$usuarios = $pdo->query("
    SELECT u.*, p.nome as perfil_nome 
    FROM usuarios u 
    LEFT JOIN perfis p ON u.perfil_id = p.id 
    ORDER BY u.nome
")->fetchAll();

// Buscar perfis
$perfis = $pdo->query("SELECT * FROM perfis ORDER BY nome")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Administrador - Sistema Integrado da Guarda Civil</title>
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
                <h2 class="text-2xl font-bold">Configurar Administrador</h2>
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
                    <i class="fas fa-info-circle mr-2"></i>Como Funciona
                </h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• <strong>Administradores:</strong> Veem o menu completo automaticamente</li>
                    <li>• <strong>Usuários comuns:</strong> Veem apenas opções para as quais têm permissão</li>
                    <li>• <strong>Para tornar um usuário admin:</strong> Selecione o usuário e marque a opção</li>
                    <li>• <strong>Para remover admin:</strong> Desmarque a opção</li>
                </ul>
            </div>

            <div class="bg-white rounded-lg border">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-semibold">Usuários do Sistema</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-3 text-left">Usuário</th>
                                <th class="p-3 text-left">Perfil Atual</th>
                                <th class="p-3 text-left">Status</th>
                                <th class="p-3 text-left">Ações</th>
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
                                    <td class="p-3">
                                        <form method="post" class="inline">
                                            <input type="hidden" name="usuario_id" value="<?= $user['id'] ?>">
                                            <label class="flex items-center">
                                                <input type="checkbox" name="tornar_admin" 
                                                       <?= (stripos($user['perfil_nome'], 'admin') !== false) ? 'checked' : '' ?> 
                                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <span class="ml-2 text-sm">Administrador</span>
                                            </label>
                                            <button type="submit" class="ml-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                                Aplicar
                                            </button>
                                        </form>
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