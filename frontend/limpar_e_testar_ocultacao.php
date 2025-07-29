<?php
// limpar_e_testar_ocultacao.php - Limpar e testar ocultação
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$msg = '';
$erro = '';

// Processar limpeza automática
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'limpar_tudo') {
        try {
            // Limpar TODAS as permissões
            $stmt = $pdo->prepare("UPDATE perfis SET permissoes = '[]' WHERE id > 0");
            $stmt->execute();
            
            $msg = "✅ LIMPEZA COMPLETA REALIZADA! Todas as permissões foram removidas.";
            logAction('limpeza_completa_permissoes', 'perfis');
            
            // Redirecionar para teste após 2 segundos
            header("Refresh: 2; URL=teste_ocultacao_menu.php");
        } catch (PDOException $e) {
            $erro = "❌ Erro ao limpar permissões: " . $e->getMessage();
        }
    }
    
    if ($acao === 'configurar_minimo') {
        try {
            // Configurar apenas 1 permissão para teste
            $permissoes_minimas = ['ocorrencias'];
            $permissoes_json = json_encode($permissoes_minimas);
            
            $stmt = $pdo->prepare("UPDATE perfis SET permissoes = ? WHERE id > 0");
            $stmt->execute([$permissoes_json]);
            
            $msg = "✅ Configurado com permissão mínima! Apenas 'ocorrencias' permitida.";
            logAction('configurar_permissoes_minimas', 'perfis');
            
            // Redirecionar para teste após 2 segundos
            header("Refresh: 2; URL=teste_ocultacao_menu.php");
        } catch (PDOException $e) {
            $erro = "❌ Erro ao configurar permissões mínimas: " . $e->getMessage();
        }
    }
}

// Buscar permissões atuais
$permissoes_atuais = [];
if (isset($_SESSION['usuario_perfil_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT permissoes FROM perfis WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_perfil_id']]);
        $perfil = $stmt->fetch();
        
        if ($perfil && $perfil['permissoes']) {
            $permissoes_atuais = json_decode($perfil['permissoes'], true) ?: [];
        }
    } catch (PDOException $e) {
        $erro = 'Erro ao buscar permissões: ' . $e->getMessage();
    }
}

$currentUser = getCurrentUser();
$isAdmin = isAdminLoggedIn();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Limpar e Testar Ocultação</title>
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
                        <i class="fas fa-eye-slash mr-2"></i>Limpar e Testar Ocultação
                    </h1>
                    <p class="text-gray-600 mt-2">Limpeza automática e teste de ocultação</p>
                </div>
                <div class="flex space-x-2">
                    <a href="teste_ocultacao_menu.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-eye mr-2"></i>Teste Ocultação
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
                    <p class="mt-2 text-sm">Redirecionando para teste em 2 segundos...</p>
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
                        <p><strong>Total de Permissões:</strong> <?= count($permissoes_atuais) ?></p>
                        <p><strong>Permissões:</strong></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">
                            <?php if (empty($permissoes_atuais)): ?>
                                <span class="text-red-600">Nenhuma permissão</span>
                            <?php else: ?>
                                <?= implode(', ', $permissoes_atuais) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Ações de Limpeza -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Limpar Tudo -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-trash mr-2"></i>Limpar Todas as Permissões
                    </h2>
                    <p class="text-gray-600 mb-4">Remove TODAS as permissões de TODOS os usuários.</p>
                    <p class="text-sm text-red-600 mb-4">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Resultado esperado:</strong> Menu mostrará apenas "Dashboard" e "Sair"
                    </p>
                    
                    <form method="POST" onsubmit="return confirm('⚠️ ATENÇÃO! Isso removerá TODAS as permissões! Tem certeza?')">
                        <input type="hidden" name="acao" value="limpar_tudo">
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold w-full">
                            <i class="fas fa-broom mr-2"></i>LIMPAR TUDO
                        </button>
                    </form>
                </div>

                <!-- Configurar Mínimo -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-cog mr-2"></i>Configurar Permissão Mínima
                    </h2>
                    <p class="text-gray-600 mb-4">Configura apenas 1 permissão para teste.</p>
                    <p class="text-sm text-blue-600 mb-4">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Resultado esperado:</strong> Menu mostrará "Dashboard", "Registro de Ocorrências" e "Sair"
                    </p>
                    
                    <form method="POST">
                        <input type="hidden" name="acao" value="configurar_minimo">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold w-full">
                            <i class="fas fa-check mr-2"></i>CONFIGURAR MÍNIMO
                        </button>
                    </form>
                </div>
            </div>

            <!-- Instruções de Teste -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-blue-800 mb-4">
                    <i class="fas fa-play mr-2"></i>Como Testar a Ocultação
                </h2>
                <div class="space-y-3 text-blue-700">
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">1</span>
                        <div>
                            <p class="font-medium">Clique em "LIMPAR TUDO"</p>
                            <p class="text-sm">Isso removerá todas as permissões</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">2</span>
                        <div>
                            <p class="font-medium">Aguarde o redirecionamento automático</p>
                            <p class="text-sm">Será redirecionado para o teste de ocultação</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">3</span>
                        <div>
                            <p class="font-medium">Verifique o menu lateral</p>
                            <p class="text-sm">Deve mostrar apenas "Dashboard" e "Sair"</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="bg-blue-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold mr-3 mt-0.5">4</span>
                        <div>
                            <p class="font-medium">Teste com permissão mínima</p>
                            <p class="text-sm">Clique em "CONFIGURAR MÍNIMO" para adicionar 1 permissão</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verificação Rápida -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-search mr-2"></i>Verificação Rápida
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-medium mb-2">Itens que DEVEM aparecer sempre:</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>✅ Dashboard</li>
                            <li>🔴 Sair</li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-medium mb-2">Itens que DEVEM ficar ocultos (sem permissão):</h3>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li>❌ Gestão de Pessoal</li>
                            <li>❌ Graduações</li>
                            <li>❌ Setores</li>
                            <li>❌ Comunicação Interna</li>
                            <li>❌ Gestão de Escalas</li>
                            <li>❌ Minhas Escalas</li>
                            <li>❌ Gerenciar Ocorrências</li>
                            <li>❌ Relatórios</li>
                            <li>❌ Gestão de Usuários</li>
                            <li>❌ E todos os outros...</li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-lightbulb mr-2"></i>
                        <strong>Dica:</strong> Se você ainda vê itens no menu após limpar as permissões, 
                        pode ser que o cache do navegador esteja interferindo. Tente fazer refresh (F5) ou 
                        logout/login novamente.
                    </p>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 