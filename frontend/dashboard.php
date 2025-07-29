<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();
$isAdmin = isAdminLoggedIn();

// Consultas para os cards do dashboard
try {
    // Total de Usuários
    $totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

    // Ocorrências Hoje (ajuste o campo de data se necessário)
    $ocorrenciasHoje = $pdo->query("SELECT COUNT(*) FROM ocorrencias WHERE DATE(data_ocorrencia) = DATE('now')")->fetchColumn();

    // Escalas Ativas (ajuste o campo 'status' e nome da tabela se necessário)
    $escalasAtivas = $pdo->query("SELECT COUNT(*) FROM escalas WHERE status = 'ativa'")->fetchColumn();

    // Relatórios (ajuste conforme sua definição de relatório)
    // Exemplo: contar registros em uma tabela 'relatorios'
    // $relatorios = $pdo->query("SELECT COUNT(*) FROM relatorios")->fetchColumn();
    // Se não houver tabela, pode ser o total de ocorrências, ou outro critério:
    $relatorios = $pdo->query("SELECT COUNT(*) FROM ocorrencias")->fetchColumn();

} catch (PDOException $e) {
    $totalUsuarios = $ocorrenciasHoje = $escalasAtivas = $relatorios = 0;
}

// Verificar se o usuário tem alguma permissão
$tem_permissoes = false;
if (!$isAdmin && isset($_SESSION['usuario_perfil_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT permissoes FROM perfis WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_perfil_id']]);
        $perfil = $stmt->fetch();
        
        if ($perfil && $perfil['permissoes']) {
            $permissoes = json_decode($perfil['permissoes'], true);
            $tem_permissoes = is_array($permissoes) && !empty($permissoes);
        }
    } catch (PDOException $e) {
        $tem_permissoes = false;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Integrado da Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        main.content {
            margin-left: 16rem;
            padding: 2rem;
            width: calc(100% - 16rem);
            position: relative;
            z-index: 1;
            min-height: 100vh;
            overflow-x: auto;
        }
        .watermark-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
            opacity: 0.07;
            background: url('img/logo.png') no-repeat center center;
            background-size: 40vw auto;
        }
        .dashboard-content {
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    <main class="content">
        <div class="watermark-bg"></div>
        <div class="dashboard-content max-w-6xl mx-auto">
            <?php if ($isAdmin || (isset($currentUser['perfil']) && $currentUser['perfil'] === 'Administrador')): ?>
                <!-- Dashboard do Administrador -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard Administrativo
                    </h1>
                    <p class="text-gray-600 mt-2">Painel de controle do sistema</p>
                </div>

                <!-- Cards de Estatísticas -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-users text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Total de Usuários</p>
                                <p class="text-2xl font-semibold text-gray-800"><?= $totalUsuarios ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-file-alt text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Ocorrências Hoje</p>
                                <p class="text-2xl font-semibold text-gray-800"><?= $ocorrenciasHoje ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-calendar-alt text-yellow-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Escalas Ativas</p>
                                <p class="text-2xl font-semibold text-gray-800"><?= $escalasAtivas ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-chart-bar text-purple-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Relatórios</p>
                                <p class="text-2xl font-semibold text-gray-800"><?= $relatorios ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações Rápidas -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h2 class="text-xl font-semibold mb-4">
                        <i class="fas fa-bolt mr-2"></i>Ações Rápidas
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Removido o card de Gestão de Usuários -->
                        <a href="ROGCM.php" class="bg-green-600 hover:bg-green-700 text-white p-4 rounded-lg text-center">
                            <i class="fas fa-file-alt text-2xl mb-2"></i>
                            <p class="font-semibold">Nova Ocorrência</p>
                        </a>
                        <a href="relatorios.php" class="bg-purple-600 hover:bg-purple-700 text-white p-4 rounded-lg text-center">
                            <i class="fas fa-chart-bar text-2xl mb-2"></i>
                            <p class="font-semibold">Relatórios</p>
                        </a>
                    </div>
                </div>

            <?php elseif ($tem_permissoes): ?>
                <!-- Dashboard do Usuário com Permissões -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                    </h1>
                    <p class="text-gray-600 mt-2">Bem-vindo, <?= htmlspecialchars($currentUser['nome']) ?>!</p>
                </div>

                <!-- Imagem de cabeçalho e mensagem de boas-vindas -->
                <div class="flex flex-col items-center justify-center mb-8">
                    <img src="img/cabecalho.png" alt="Cabeçalho" class="w-full max-w-3xl rounded-lg shadow mb-8" style="object-fit:cover;">
                    <div class="w-full flex flex-col items-center justify-center text-center mt-16">
                        <h2 class="text-3xl font-extrabold text-blue-900 mb-4" style="font-size:2.6rem;">Bem-vindo ao Sistema Integrado da Guarda Civil!</h2>
                        <p class="text-2xl text-blue-800" style="font-size:1.7rem;">Tenha um excelente plantão de trabalho! Conte sempre com a tecnologia a seu favor.</p>
                    </div>
                </div>

            <?php else: ?>
                <!-- Dashboard de Boas-vindas para Usuários Sem Permissões -->
                <div class="text-center py-12">
                    <div class="max-w-2xl mx-auto">
                        <!-- Ícone de Boas-vindas -->
                        <div class="mb-8">
                            <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-user-clock text-3xl"></i>
                            </div>
                        </div>

                        <!-- Mensagem Principal -->
                        <h1 class="text-4xl font-bold text-gray-800 mb-4">
                            Bem-vindo ao Sistema!
                        </h1>
                        
                        <p class="text-xl text-gray-600 mb-8">
                            Olá, <strong><?= htmlspecialchars($currentUser['nome']) ?></strong>!
                        </p>

                        <!-- Card de Status -->
                        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                            <div class="flex items-center justify-center mb-4">
                                <div class="bg-yellow-100 p-3 rounded-full mr-4">
                                    <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                                </div>
                                <div class="text-left">
                                    <h2 class="text-xl font-semibold text-gray-800">Aguardando Liberação</h2>
                                    <p class="text-gray-600">Suas permissões estão sendo configuradas</p>
                                </div>
                            </div>
                            
                            <div class="border-t pt-4">
                                <p class="text-sm text-gray-500 mb-4">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    O administrador irá liberar suas permissões em breve.
                                </p>
                                
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <h3 class="font-semibold text-blue-800 mb-2">
                                        <i class="fas fa-lightbulb mr-2"></i>O que acontece depois?
                                    </h3>
                                    <ul class="text-sm text-blue-700 space-y-1">
                                        <li>• Você receberá acesso aos módulos liberados</li>
                                        <li>• O menu lateral será atualizado automaticamente</li>
                                        <li>• Poderá acessar as funcionalidades permitidas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Informações do Usuário -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h3 class="font-semibold text-gray-800 mb-4">
                                <i class="fas fa-user mr-2"></i>Suas Informações
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p><strong>Nome:</strong> <?= htmlspecialchars($currentUser['nome']) ?></p>
                                    <p><strong>Perfil:</strong> <?= htmlspecialchars($currentUser['perfil']) ?></p>
                                </div>
                                <div>
                                    <p><strong>Status:</strong> <span class="text-yellow-600 font-semibold">Aguardando liberação</span></p>
                                    <p><strong>Data de Cadastro:</strong> <?= date('d/m/Y') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
