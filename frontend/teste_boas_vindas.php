<?php
// teste_boas_vindas.php - Teste do sistema de boas-vindas
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = isAdminLoggedIn();
$msg = '';
$erro = '';

// Processar ações de teste
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'simular_sem_permissoes') {
        try {
            // Limpar permissões do usuário atual (se não for admin)
            if (!$isAdmin && isset($_SESSION['usuario_perfil_id'])) {
                $stmt = $pdo->prepare("UPDATE perfis SET permissoes = '[]' WHERE id = ?");
                $stmt->execute([$_SESSION['usuario_perfil_id']]);
                $msg = "✅ Permissões removidas! Agora você verá a tela de boas-vindas.";
            } else {
                $msg = "ℹ️ Você é admin, então sempre verá o menu completo.";
            }
        } catch (PDOException $e) {
            $erro = "❌ Erro ao simular: " . $e->getMessage();
        }
    }
    
    if ($acao === 'restaurar_permissoes') {
        try {
            // Restaurar permissões básicas
            if (!$isAdmin && isset($_SESSION['usuario_perfil_id'])) {
                $permissoes_basicas = ['ocorrencias', 'minhas_escalas'];
                $permissoes_json = json_encode($permissoes_basicas);
                
                $stmt = $pdo->prepare("UPDATE perfis SET permissoes = ? WHERE id = ?");
                $stmt->execute([$permissoes_json, $_SESSION['usuario_perfil_id']]);
                $msg = "✅ Permissões restauradas! Menu normal ativado.";
            }
        } catch (PDOException $e) {
            $erro = "❌ Erro ao restaurar: " . $e->getMessage();
        }
    }
}

// Buscar permissões atuais
$permissoes_atuais = [];
$tem_permissoes = false;
if (isset($_SESSION['usuario_perfil_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT permissoes FROM perfis WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_perfil_id']]);
        $perfil = $stmt->fetch();
        
        if ($perfil && $perfil['permissoes']) {
            $permissoes_atuais = json_decode($perfil['permissoes'], true) ?: [];
            $tem_permissoes = is_array($permissoes_atuais) && !empty($permissoes_atuais);
        }
    } catch (PDOException $e) {
        $erro = 'Erro ao buscar permissões: ' . $e->getMessage();
    }
}

// Determinar status atual
$status_atual = '';
if ($isAdmin) {
    $status_atual = 'admin';
} elseif ($tem_permissoes) {
    $status_atual = 'com_permissoes';
} else {
    $status_atual = 'sem_permissoes';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Sistema de Boas-vindas</title>
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
                        <i class="fas fa-heart mr-2"></i>Teste - Sistema de Boas-vindas
                    </h1>
                    <p class="text-gray-600 mt-2">Teste do novo sistema de boas-vindas para usuários</p>
                </div>
                <div class="flex space-x-2">
                    <a href="usuarios_pendentes.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-user-clock mr-2"></i>Usuários Pendentes
                    </a>
                    <a href="dashboard.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
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

            <!-- Status Atual -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Status Atual
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p><strong>Usuário:</strong> <?= htmlspecialchars($currentUser['nome']) ?></p>
                        <p><strong>Admin:</strong> <?= $isAdmin ? '✅ Sim' : '❌ Não' ?></p>
                    </div>
                    <div>
                        <p><strong>Status:</strong> 
                            <?php if ($status_atual === 'admin'): ?>
                                <span class="text-purple-600 font-semibold">Administrador</span>
                            <?php elseif ($status_atual === 'com_permissoes'): ?>
                                <span class="text-green-600 font-semibold">Com Permissões</span>
                            <?php else: ?>
                                <span class="text-yellow-600 font-semibold">Aguardando Liberação</span>
                            <?php endif; ?>
                        </p>
                        <p><strong>Permissões:</strong> <?= count($permissoes_atuais) ?></p>
                    </div>
                    <div>
                        <p><strong>Permissões Atuais:</strong></p>
                        <p class="text-sm text-gray-600">
                            <?php if (empty($permissoes_atuais)): ?>
                                <span class="text-red-600">Nenhuma</span>
                            <?php else: ?>
                                <?= implode(', ', $permissoes_atuais) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Simulação -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Simular Sem Permissões -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-user-clock mr-2"></i>Simular Usuário Sem Permissões
                    </h2>
                    <p class="text-gray-600 mb-4">Remove suas permissões para testar a tela de boas-vindas.</p>
                    <p class="text-sm text-yellow-600 mb-4">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Resultado:</strong> Menu mostrará apenas mensagem de boas-vindas
                    </p>
                    
                    <form method="POST">
                        <input type="hidden" name="acao" value="simular_sem_permissoes">
                        <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg font-semibold w-full">
                            <i class="fas fa-user-clock mr-2"></i>Simular Sem Permissões
                        </button>
                    </form>
                </div>

                <!-- Restaurar Permissões -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-user-check mr-2"></i>Restaurar Permissões
                    </h2>
                    <p class="text-gray-600 mb-4">Restaura permissões básicas para voltar ao menu normal.</p>
                    <p class="text-sm text-blue-600 mb-4">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Permissões:</strong> Ocorrências, Minhas Escalas
                    </p>
                    
                    <form method="POST">
                        <input type="hidden" name="acao" value="restaurar_permissoes">
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold w-full">
                            <i class="fas fa-check mr-2"></i>Restaurar Permissões
                        </button>
                    </form>
                </div>
            </div>

            <!-- Como Funciona -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-blue-800 mb-4">
                    <i class="fas fa-play mr-2"></i>Como Funciona o Sistema
                </h2>
                <div class="space-y-3 text-blue-700">
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">1</span>
                        <div>
                            <p class="font-medium">Usuário é cadastrado</p>
                            <p class="text-sm">Inicialmente sem permissões</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">2</span>
                        <div>
                            <p class="font-medium">Vê tela de boas-vindas</p>
                            <p class="text-sm">Menu lateral mostra apenas mensagem de boas-vindas</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">3</span>
                        <div>
                            <p class="font-medium">Admin libera permissões</p>
                            <p class="text-sm">Acessa "Usuários Pendentes" e configura permissões</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">4</span>
                        <div>
                            <p class="font-medium">Menu é atualizado</p>
                            <p class="text-sm">Usuário vê apenas os itens permitidos</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Simulação Visual -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-eye mr-2"></i>Simulação Visual - Como Fica o Menu
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Admin -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-purple-600 mb-2">
                            <i class="fas fa-crown mr-1"></i>Administrador
                        </h3>
                        <div class="bg-purple-900 text-white p-3 rounded text-sm space-y-1">
                            <div>✅ Dashboard</div>
                            <div>✅ Gestão de Pessoal</div>
                            <div>✅ Graduações</div>
                            <div>✅ Setores</div>
                            <div>✅ Comunicação Interna</div>
                            <div>✅ Gestão de Escalas</div>
                            <div>✅ Minhas Escalas</div>
                            <div>✅ Registro de Ocorrências</div>
                            <div>✅ Gerenciar Ocorrências</div>
                            <div>✅ Relatórios</div>
                            <div>✅ Gestão de Usuários</div>
                            <div>✅ Usuários Pendentes</div>
                            <div>✅ Perfis e Permissões</div>
                            <div>✅ Logs do Sistema</div>
                            <div>✅ Configurações Gerais</div>
                            <div>✅ Banco de Dados</div>
                            <div>✅ Alertas e Notificações</div>
                            <div>✅ Suporte</div>
                            <div>✅ Conferir Checklists</div>
                            <div>🔴 Sair</div>
                        </div>
                    </div>

                    <!-- Com Permissões -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-green-600 mb-2">
                            <i class="fas fa-user-check mr-1"></i>Com Permissões
                        </h3>
                        <div class="bg-green-900 text-white p-3 rounded text-sm space-y-1">
                            <div>✅ Dashboard</div>
                            <div>✅ Registro de Ocorrências</div>
                            <div>✅ Minhas Escalas</div>
                            <div>🔴 Sair</div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Apenas itens permitidos</p>
                    </div>

                    <!-- Sem Permissões -->
                    <div class="border rounded-lg p-4">
                        <h3 class="font-semibold text-yellow-600 mb-2">
                            <i class="fas fa-user-clock mr-1"></i>Sem Permissões
                        </h3>
                        <div class="bg-yellow-900 text-white p-3 rounded text-sm space-y-1">
                            <div>✅ Dashboard</div>
                            <div class="bg-yellow-600 p-2 rounded text-center">
                                <i class="fas fa-user-clock"></i>
                                <div class="font-semibold">Bem-vindo ao Sistema!</div>
                                <div class="text-xs">Aguarde a liberação das suas permissões pelo administrador.</div>
                            </div>
                            <div class="bg-blue-600 p-2 rounded text-center">
                                <i class="fas fa-info-circle"></i>
                                <div class="text-xs"><strong>Status:</strong> Aguardando liberação</div>
                            </div>
                            <div>🔴 Sair</div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Tela de boas-vindas</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 