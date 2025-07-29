<?php
// relatorios_hierarquia.php - Relatórios Específicos por Hierarquia
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();

// Buscar dados baseados na hierarquia
try {
    // Dados específicos do usuário logado
    $stmt = $pdo->prepare("
        SELECT u.*, g.nome as graduacao_nome, s.nome as setor_nome 
        FROM usuarios u 
        LEFT JOIN graduacoes g ON u.graduacao_id = g.id 
        LEFT JOIN setores s ON u.setor_id = s.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$currentUser['id']]);
    $userData = $stmt->fetch();

    // Buscar estatísticas baseadas na hierarquia
    if (isAdminLoggedIn()) {
        // Admin vê tudo
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
        $totalPessoal = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ocorrencias");
        $totalOcorrencias = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM escalas WHERE data_inicio >= CURDATE()");
        $totalEscalas = $stmt->fetch()['total'];
        
    } elseif ($userData['graduacao_nome'] === 'Comandante') {
        // Comandante vê dados gerais
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
        $totalPessoal = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ocorrencias");
        $totalOcorrencias = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM escalas WHERE data_inicio >= CURDATE()");
        $totalEscalas = $stmt->fetch()['total'];
        
    } elseif ($userData['graduacao_nome'] === 'Subcomandante') {
        // Subcomandante vê dados do seu setor e subordinados
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1 AND setor_id = ?");
        $stmt->execute([$userData['setor_id']]);
        $totalPessoal = $stmt->fetch()['total'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM ocorrencias");
        $totalOcorrencias = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM escalas WHERE data_inicio >= CURDATE() AND setor_id = ?");
        $stmt->execute([$userData['setor_id']]);
        $totalEscalas = $stmt->fetch()['total'];
        
    } else {
        // Outros usuários vêem dados limitados
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1 AND setor_id = ?");
        $stmt->execute([$userData['setor_id']]);
        $totalPessoal = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM ocorrencias WHERE responsavel_id = ?");
        $stmt->execute([$currentUser['id']]);
        $totalOcorrencias = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM escalas WHERE data_inicio >= CURDATE() AND usuario_id = ?");
        $stmt->execute([$currentUser['id']]);
        $totalEscalas = $stmt->fetch()['total'];
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar dados hierárquicos: " . $e->getMessage());
    $userData = [];
    $totalPessoal = 0;
    $totalOcorrencias = 0;
    $totalEscalas = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Relatórios por Hierarquia - Sistema Integrado da Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --dark-color: #1e1e2d;
            --light-color: #f8fafc;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
        }
        /* Sidebar externo */
        aside.sidebar {
            width: 16rem;
            background-color: #1e40af;
            color: white;
            height: 100vh;
            padding: 1.25rem;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            box-shadow: 2px 0 12px rgba(0,0,0,0.2);
            z-index: 30;
        }
        aside.sidebar .logo-container {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        aside.sidebar .logo-container img {
            width: 10.14rem;
            margin: 0 auto 0.5rem auto;
            display: block;
        }
        aside.sidebar .logo-container h1 {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        aside.sidebar .logo-container p {
            font-size: 0.875rem;
            color: #bfdbfe;
            margin: 0;
        }
        aside.sidebar nav a {
            display: block;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        aside.sidebar nav a:hover {
            background-color: #2563eb;
        }
        aside.sidebar nav a.active {
            background-color: #2563eb;
        }
        aside.sidebar nav a.logout {
            background-color: #dc2626;
        }
        aside.sidebar nav a.logout:hover {
            background-color: #b91c1c;
        }
        /* Conteúdo principal */
        main.content {
            margin-left: 16rem;
            padding: 2rem;
            width: calc(100% - 16rem);
        }
        .btn-primary {
            background-color: var(--primary-color);
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        .hierarchy-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary-color);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .permission-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .permission-admin { background-color: #dcfce7; color: #166534; }
        .permission-comandante { background-color: #dbeafe; color: #1e40af; }
        .permission-subcomandante { background-color: #fef3c7; color: #92400e; }
        .permission-guarda { background-color: #f3e8ff; color: #7c3aed; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <main class="content">
        <header class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">Relatórios por Hierarquia</h2>
                <p class="text-gray-600 mt-2">Relatórios específicos para seu nível de acesso</p>
            </div>
            <div class="text-right">
                <div class="text-gray-600 text-sm">
                    Olá, <?= htmlspecialchars($currentUser['nome']) ?>
                </div>
                <div class="permission-badge permission-<?= strtolower($userData['graduacao_nome'] ?? 'guarda') ?>">
                    <i class="fas fa-user-shield mr-2"></i>
                    <?= htmlspecialchars($userData['graduacao_nome'] ?? 'Usuário') ?>
                </div>
            </div>
        </header>

        <!-- Informações do Usuário -->
        <div class="hierarchy-card mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-gray-800">Seu Perfil</h3>
                    <p class="text-gray-600 mt-1">
                        <?= htmlspecialchars($userData['graduacao_nome'] ?? 'Usuário') ?> - 
                        <?= htmlspecialchars($userData['setor_nome'] ?? 'Setor não definido') ?>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Nível de Acesso</p>
                    <p class="text-lg font-semibold text-blue-600">
                        <?php
                        if (isAdminLoggedIn()) echo 'Administrador';
                        elseif ($userData['graduacao_nome'] === 'Comandante') echo 'Comandante';
                        elseif ($userData['graduacao_nome'] === 'Subcomandante') echo 'Subcomandante';
                        else echo 'Operacional';
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Estatísticas por Hierarquia -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="hierarchy-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Pessoal sob sua responsabilidade</p>
                        <p class="text-3xl font-bold text-blue-600"><?= $totalPessoal ?></p>
                    </div>
                    <i class="fas fa-users text-3xl text-blue-200"></i>
                </div>
            </div>
            <div class="hierarchy-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Ocorrências relacionadas</p>
                        <p class="text-3xl font-bold text-green-600"><?= $totalOcorrencias ?></p>
                    </div>
                    <i class="fas fa-file-alt text-3xl text-green-200"></i>
                </div>
            </div>
            <div class="hierarchy-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Escalas ativas</p>
                        <p class="text-3xl font-bold text-purple-600"><?= $totalEscalas ?></p>
                    </div>
                    <i class="fas fa-calendar-alt text-3xl text-purple-200"></i>
                </div>
            </div>
        </div>

        <!-- Relatórios Disponíveis por Hierarquia -->
        <div class="space-y-6">
            <?php if (isAdminLoggedIn()): ?>
            <!-- Relatórios de Administrador -->
            <div class="hierarchy-card">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-crown text-yellow-500 mr-2"></i>
                    Relatórios de Administrador
                </h3>
                <p class="text-gray-600 mb-4">Acesso completo a todos os dados e relatórios do sistema.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Relatório Geral da Corporação</h4>
                        <p class="text-sm text-gray-600 mb-3">Visão completa de todos os dados</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('admin_geral', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('admin_geral', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Análise de Performance</h4>
                        <p class="text-sm text-gray-600 mb-3">Métricas de eficiência e produtividade</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('admin_performance', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('admin_performance', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Relatório Financeiro</h4>
                        <p class="text-sm text-gray-600 mb-3">Custos e orçamentos da corporação</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('admin_financeiro', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('admin_financeiro', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($userData['graduacao_nome'] === 'Comandante' || isAdminLoggedIn()): ?>
            <!-- Relatórios de Comandante -->
            <div class="hierarchy-card">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-star text-blue-500 mr-2"></i>
                    Relatórios de Comandante
                </h3>
                <p class="text-gray-600 mb-4">Relatórios estratégicos e de gestão da corporação.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Relatório Operacional</h4>
                        <p class="text-sm text-gray-600 mb-3">Status geral das operações</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('comandante_operacional', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('comandante_operacional', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Relatório de Pessoal</h4>
                        <p class="text-sm text-gray-600 mb-3">Distribuição e status do efetivo</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('comandante_pessoal', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('comandante_pessoal', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Relatório de Segurança</h4>
                        <p class="text-sm text-gray-600 mb-3">Análise de ocorrências e segurança</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('comandante_seguranca', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('comandante_seguranca', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($userData['graduacao_nome'] === 'Subcomandante' || $userData['graduacao_nome'] === 'Comandante' || isAdminLoggedIn()): ?>
            <!-- Relatórios de Subcomandante -->
            <div class="hierarchy-card">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-user-tie text-orange-500 mr-2"></i>
                    Relatórios de Subcomandante
                </h3>
                <p class="text-gray-600 mb-4">Relatórios de gestão de setor e coordenação de equipes.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Relatório de Setor</h4>
                        <p class="text-sm text-gray-600 mb-3">Dados específicos do seu setor</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('subcomandante_setor', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('subcomandante_setor', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Relatório de Equipe</h4>
                        <p class="text-sm text-gray-600 mb-3">Performance da equipe sob comando</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('subcomandante_equipe', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('subcomandante_equipe', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Relatório de Escalas</h4>
                        <p class="text-sm text-gray-600 mb-3">Gestão de escalas do setor</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('subcomandante_escalas', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('subcomandante_escalas', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Relatórios Operacionais (Todos os usuários) -->
            <div class="hierarchy-card">
                <h3 class="text-xl font-semibold text-gray-800 mb-4">
                    <i class="fas fa-user-shield text-purple-500 mr-2"></i>
                    Relatórios Operacionais
                </h3>
                <p class="text-gray-600 mb-4">Relatórios pessoais e de atividades operacionais.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Relatório Pessoal</h4>
                        <p class="text-sm text-gray-600 mb-3">Suas atividades e ocorrências</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('operacional_pessoal', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('operacional_pessoal', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Minhas Escalas</h4>
                        <p class="text-sm text-gray-600 mb-3">Histórico de suas escalas</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('operacional_escalas', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('operacional_escalas', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                    <div class="border rounded-lg p-4">
                        <h4 class="font-semibold text-gray-800">Relatório de Atividades</h4>
                        <p class="text-sm text-gray-600 mb-3">Resumo de suas atividades</p>
                        <div class="flex gap-2">
                            <button onclick="gerarRelatorio('operacional_atividades', 'pdf')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-pdf mr-1"></i>PDF
                            </button>
                            <button onclick="gerarRelatorio('operacional_atividades', 'excel')" class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Atividades por Hierarquia -->
        <div class="hierarchy-card mt-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Atividades por Hierarquia</h3>
            <div class="chart-container">
                <canvas id="hierarchyChart"></canvas>
            </div>
        </div>
    </main>

    <script>
        // Gráfico de Atividades por Hierarquia
        const hierarchyCtx = document.getElementById('hierarchyChart').getContext('2d');
        new Chart(hierarchyCtx, {
            type: 'doughnut',
            data: {
                labels: ['Administrador', 'Comandante', 'Subcomandante', 'Operacional'],
                datasets: [{
                    data: [15, 25, 30, 30],
                    backgroundColor: [
                        '#dcfce7',
                        '#dbeafe',
                        '#fef3c7',
                        '#f3e8ff'
                    ],
                    borderColor: [
                        '#166534',
                        '#1e40af',
                        '#92400e',
                        '#7c3aed'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: true,
                        text: 'Distribuição de Atividades por Hierarquia'
                    }
                }
            }
        });

        // Função para gerar relatórios
        function gerarRelatorio(tipo, formato) {
            const hierarquia = '<?= $userData['graduacao_nome'] ?? 'Usuario' ?>';
            const setor = '<?= $userData['setor_nome'] ?? 'N/A' ?>';
            
            console.log(`Gerando relatório: ${tipo} em formato ${formato}`);
            console.log(`Hierarquia: ${hierarquia}, Setor: ${setor}`);
            
            // Simular geração de relatório
            alert(`Relatório ${tipo} será gerado em formato ${formato.toUpperCase()} para ${hierarquia}`);
            
            // Aqui você implementaria a lógica real de geração
            // baseada na hierarquia e permissões do usuário
        }

        // Verificar permissões em tempo real
        function verificarPermissoes() {
            const hierarquia = '<?= $userData['graduacao_nome'] ?? 'Usuario' ?>';
            const isAdmin = <?= isAdminLoggedIn() ? 'true' : 'false' ?>;
            
            console.log(`Usuário: ${hierarquia}, Admin: ${isAdmin}`);
            
            // Atualizar interface baseada nas permissões
            if (!isAdmin && hierarquia !== 'Comandante' && hierarquia !== 'Subcomandante') {
                // Ocultar elementos restritos
                document.querySelectorAll('.admin-only').forEach(el => {
                    el.style.display = 'none';
                });
            }
        }

        // Executar verificação ao carregar
        window.addEventListener('load', verificarPermissoes);
    </script>
</body>
</html> 