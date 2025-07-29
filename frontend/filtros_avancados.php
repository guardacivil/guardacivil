<?php
// filtros_avancados.php - Sistema de Filtros Dinâmicos Avançados
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();

// Buscar dados para filtros
try {
    // Setores
    $stmt = $pdo->query("SELECT * FROM setores WHERE ativo = 1 ORDER BY nome");
    $setores = $stmt->fetchAll();
    
    // Graduações
    $stmt = $pdo->query("SELECT * FROM graduacoes ORDER BY nivel DESC");
    $graduacoes = $stmt->fetchAll();
    
    // Tipos de ocorrência
    $stmt = $pdo->query("SELECT DISTINCT tipo FROM ocorrencias ORDER BY tipo");
    $tiposOcorrencia = $stmt->fetchAll();
    
    // Usuários
    $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome");
    $usuarios = $stmt->fetchAll();
    
    // Filtros salvos do banco
    $stmt = $pdo->prepare("SELECT * FROM filtros_salvos WHERE usuario_id = ? ORDER BY nome");
    $stmt->execute([$currentUser['id']]);
    $filtrosSalvos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar dados para filtros: " . $e->getMessage());
    $setores = [];
    $graduacoes = [];
    $tiposOcorrencia = [];
    $usuarios = [];
    $filtrosSalvos = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Filtros Avançados - Sistema Integrado da Guarda Civil</title>
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
        .filter-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin: 20px 0;
        }
        .comparison-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-container">
            <img src="img/logo1.png" alt="Logo" />
            <h1>Sistema Integrado da Guarda Civil</h1>
            <p>Município de Araçoiaba da Serra</p>
        </div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            
            <!-- Gestão de Pessoal -->
            <a href="pessoal.php">Gestão de Pessoal</a>
            <a href="graduacoes.php">Graduações</a>
            <a href="setores.php">Setores</a>
            
            <!-- Comunicação Interna -->
            <a href="comunicacao.php">Comunicação Interna</a>
            
            <!-- Gestão de Escalas -->
            <a href="escalas.php">Gestão de Escalas</a>
            <a href="minhas_escalas.php">Minhas Escalas</a>
            
            <!-- Ocorrências -->
            <div class="menu-group">
                <a href="#" class="menu-header"><i class="fas fa-exclamation-triangle"></i> Ocorrências</a>
                <div class="submenu">
                    <a href="ROGCM.php"><i class="fas fa-file-alt"></i> Registro de Ocorrências</a>
                    <a href="minhas_ocorrencias.php"><i class="fas fa-clipboard-list"></i> Minhas Ocorrências</a>
                </div>
            </div>
            <a href="gerenciar_ocorrencias.php">Gerenciar Ocorrências</a>
            
            <!-- Relatórios -->
            <a href="relatorios.php">Relatórios</a>
            <a href="filtros_avancados.php" class="active">Filtros Avançados</a>
            
                  <a href="relatorios_agendados.php">Relatórios Agendados</a>
      <a href="filtros_avancados.php">Filtros Avançados</a>
      <a href="relatorios_hierarquia.php">Relatórios por Hierarquia</a>
      <!-- Administração do Sistema -->
            <a href="usuarios.php">Gestão de Usuários</a>
            <a href="perfis.php">Perfis e Permissões</a>
            <a href="logs.php">Logs do Sistema</a>
            <a href="configuracoes.php">Configurações Gerais</a>
            <a href="banco_dados.php">Banco de Dados</a>
            <a href="alertas.php">Alertas e Notificações</a>
            <a href="suporte.php">Suporte</a>
            
            <a href="logout.php" class="logout">Sair</a>
        </nav>
    </aside>

    <!-- Conteúdo principal -->
    <main class="content">
        <header class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold mb-8"><i class="fas fa-filter mr-2"></i>Filtros Avançados</h2>
            <div class="text-gray-600 text-sm">
                Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
                (<?= htmlspecialchars($currentUser['perfil']) ?>)
            </div>
        </header>

        <!-- Filtros Temporais -->
        <div class="filter-section">
            <h3 class="text-lg font-semibold mb-4">Filtros Temporais</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Período Base</label>
                    <select id="periodoBase" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="mes">Último Mês</option>
                        <option value="trimestre">Último Trimestre</option>
                        <option value="semestre">Último Semestre</option>
                        <option value="ano">Último Ano</option>
                        <option value="personalizado">Personalizado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Início</label>
                    <input type="date" id="dataInicio" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim</label>
                    <input type="date" id="dataFim" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Comparar Com</label>
                    <select id="comparacao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Sem comparação</option>
                        <option value="mes_anterior">Mês Anterior</option>
                        <option value="trimestre_anterior">Trimestre Anterior</option>
                        <option value="ano_anterior">Ano Anterior</option>
                        <option value="periodo_custom">Período Customizado</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Filtros de Categoria -->
        <div class="filter-section">
            <h3 class="text-lg font-semibold mb-4">Filtros de Categoria</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Setor</label>
                    <select id="setor" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos os Setores</option>
                        <?php foreach ($setores as $setor): ?>
                            <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Graduação</label>
                    <select id="graduacao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todas as Graduações</option>
                        <?php foreach ($graduacoes as $graduacao): ?>
                            <option value="<?= $graduacao['id'] ?>"><?= htmlspecialchars($graduacao['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Ocorrência</label>
                    <select id="tipoOcorrencia" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos os Tipos</option>
                        <?php foreach ($tiposOcorrencia as $tipo): ?>
                            <option value="<?= $tipo['tipo'] ?>"><?= htmlspecialchars($tipo['tipo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Filtros Avançados -->
        <div class="filter-section">
            <h3 class="text-lg font-semibold mb-4">Filtros Avançados</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Responsável</label>
                    <select id="responsavel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos os Responsáveis</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= $usuario['id'] ?>"><?= htmlspecialchars($usuario['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos os Status</option>
                        <option value="ativo">Ativo</option>
                        <option value="pendente">Pendente</option>
                        <option value="concluido">Concluído</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Palavras-chave</label>
                <input type="text" id="palavrasChave" placeholder="Digite palavras-chave separadas por vírgula" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="flex gap-4 mb-8">
            <button onclick="aplicarFiltros()" class="btn-primary text-white px-6 py-3 rounded-lg flex items-center gap-2">
                <i class="fas fa-filter"></i>
                Aplicar Filtros
            </button>
            <button onclick="limparFiltros()" class="bg-gray-600 text-white px-6 py-3 rounded-lg flex items-center gap-2 hover:bg-gray-700">
                <i class="fas fa-times"></i>
                Limpar Filtros
            </button>
            <button onclick="salvarFiltros()" class="bg-green-600 text-white px-6 py-3 rounded-lg flex items-center gap-2 hover:bg-green-700">
                <i class="fas fa-save"></i>
                Salvar Filtros
            </button>
            <button onclick="carregarFiltros()" class="bg-blue-600 text-white px-6 py-3 rounded-lg flex items-center gap-2 hover:bg-blue-700">
                <i class="fas fa-folder-open"></i>
                Carregar Filtros
            </button>
        </div>

        <!-- Resultados e Comparativos -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Gráfico de Comparação -->
            <div class="comparison-card">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Comparativo de Períodos</h3>
                <div class="chart-container">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <!-- Tendências -->
            <div class="comparison-card">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Análise de Tendências</h3>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Métricas Comparativas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="comparison-card">
                <h4 class="font-semibold text-gray-800 mb-2">Período Atual</h4>
                <div class="text-2xl font-bold text-blue-600">1,234</div>
                <p class="text-sm text-gray-600">Ocorrências</p>
                <div class="mt-2">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-arrow-up mr-1"></i>+12%
                    </span>
                </div>
            </div>
            <div class="comparison-card">
                <h4 class="font-semibold text-gray-800 mb-2">Período Anterior</h4>
                <div class="text-2xl font-bold text-gray-600">1,098</div>
                <p class="text-sm text-gray-600">Ocorrências</p>
                <div class="mt-2">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                        Base
                    </span>
                </div>
            </div>
            <div class="comparison-card">
                <h4 class="font-semibold text-gray-800 mb-2">Variação</h4>
                <div class="text-2xl font-bold text-green-600">+136</div>
                <p class="text-sm text-gray-600">Diferença</p>
                <div class="mt-2">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        +12.4%
                    </span>
                </div>
            </div>
            <div class="comparison-card">
                <h4 class="font-semibold text-gray-800 mb-2">Média Diária</h4>
                <div class="text-2xl font-bold text-purple-600">41.1</div>
                <p class="text-sm text-gray-600">Ocorrências/dia</p>
                <div class="mt-2">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        Crescente
                    </span>
                </div>
            </div>
        </div>

        <!-- Análise Preditiva -->
        <div class="comparison-card mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Análise Preditiva</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600 mb-2">1,450</div>
                    <p class="text-sm text-gray-600">Previsão Próximo Mês</p>
                    <div class="mt-2">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            +17.5%
                        </span>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600 mb-2">Alta</div>
                    <p class="text-sm text-gray-600">Confiança da Previsão</p>
                    <div class="mt-2">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            85%
                        </span>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-orange-600 mb-2">Verão</div>
                    <p class="text-sm text-gray-600">Fator Sazonal</p>
                    <div class="mt-2">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                            +8%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros Salvos -->
        <div class="comparison-card">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Filtros Salvos</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($filtrosSalvos as $filtro): ?>
                <div class="border rounded-lg p-4">
                    <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($filtro['nome']) ?></h4>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($filtro['descricao']) ?></p>
                    <div class="mt-2 flex gap-2">
                        <button onclick="carregarFiltroSalvo('<?= $filtro['id'] ?>')" class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-folder-open mr-1"></i>Carregar
                        </button>
                        <button onclick="excluirFiltroSalvo('<?= $filtro['id'] ?>')" class="text-red-600 hover:text-red-800 text-sm">
                            <i class="fas fa-trash mr-1"></i>Excluir
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
        // Gráfico de Comparação
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');
        new Chart(comparisonCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Período Atual',
                    data: [120, 135, 142, 138, 145, 150],
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }, {
                    label: 'Período Anterior',
                    data: [110, 125, 132, 128, 135, 140],
                    backgroundColor: 'rgba(156, 163, 175, 0.8)',
                    borderColor: 'rgb(156, 163, 175)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Tendências
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Tendência',
                    data: [120, 135, 142, 138, 145, 150],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Funções de filtros
        function aplicarFiltros() {
            const filtros = {
                periodoBase: document.getElementById('periodoBase').value,
                dataInicio: document.getElementById('dataInicio').value,
                dataFim: document.getElementById('dataFim').value,
                comparacao: document.getElementById('comparacao').value,
                setor: document.getElementById('setor').value,
                graduacao: document.getElementById('graduacao').value,
                tipoOcorrencia: document.getElementById('tipoOcorrencia').value,
                responsavel: document.getElementById('responsavel').value,
                status: document.getElementById('status').value,
                palavrasChave: document.getElementById('palavrasChave').value
            };
            // Enviar filtros via AJAX para buscar dados reais
            fetch('filtros_avancados_resultados.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(filtros)
            })
            .then(r => r.json())
            .then(dados => {
                atualizarGraficos(dados);
                atualizarMetricas(dados);
            });
        }

        function limparFiltros() {
            document.getElementById('periodoBase').value = 'mes';
            document.getElementById('dataInicio').value = '';
            document.getElementById('dataFim').value = '';
            document.getElementById('comparacao').value = '';
            document.getElementById('setor').value = '';
            document.getElementById('graduacao').value = '';
            document.getElementById('tipoOcorrencia').value = '';
            document.getElementById('responsavel').value = '';
            document.getElementById('status').value = '';
            document.getElementById('palavrasChave').value = '';
        }

        function salvarFiltros() {
            const filtros = {
                periodoBase: document.getElementById('periodoBase').value,
                dataInicio: document.getElementById('dataInicio').value,
                dataFim: document.getElementById('dataFim').value,
                comparacao: document.getElementById('comparacao').value,
                setor: document.getElementById('setor').value,
                graduacao: document.getElementById('graduacao').value,
                tipoOcorrencia: document.getElementById('tipoOcorrencia').value,
                responsavel: document.getElementById('responsavel').value,
                status: document.getElementById('status').value,
                palavrasChave: document.getElementById('palavrasChave').value
            };
            const nomeFiltro = prompt('Digite um nome para salvar estes filtros:');
            if (nomeFiltro) {
                fetch('filtros_avancados_salvar.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nome: nomeFiltro, filtros })
                })
                .then(r => r.json())
                .then(resp => { alert(resp.msg); location.reload(); });
            }
        }

        function carregarFiltros() {
            const filtrosSalvos = Object.keys(localStorage).filter(key => key.startsWith('filtro_'));
            if (filtrosSalvos.length === 0) {
                alert('Nenhum filtro salvo encontrado.');
                return;
            }
            
            const filtroEscolhido = prompt('Digite o nome do filtro a carregar:');
            if (filtroEscolhido) {
                const filtros = JSON.parse(localStorage.getItem(`filtro_${filtroEscolhido}`));
                if (filtros) {
                    document.getElementById('periodoBase').value = filtros.periodoBase;
                    document.getElementById('dataInicio').value = filtros.dataInicio;
                    document.getElementById('dataFim').value = filtros.dataFim;
                    document.getElementById('comparacao').value = filtros.comparacao;
                    document.getElementById('setor').value = filtros.setor;
                    document.getElementById('graduacao').value = filtros.graduacao;
                    document.getElementById('tipoOcorrencia').value = filtros.tipoOcorrencia;
                    document.getElementById('responsavel').value = filtros.responsavel;
                    document.getElementById('status').value = filtros.status;
                    document.getElementById('palavrasChave').value = filtros.palavrasChave;
                    alert('Filtros carregados com sucesso!');
                } else {
                    alert('Filtro não encontrado.');
                }
            }
        }

        function carregarFiltroSalvo(id) {
            fetch('filtros_avancados_carregar.php?id=' + id)
            .then(r => r.json())
            .then(filtros => {
                document.getElementById('periodoBase').value = filtros.periodoBase;
                document.getElementById('dataInicio').value = filtros.dataInicio;
                document.getElementById('dataFim').value = filtros.dataFim;
                document.getElementById('comparacao').value = filtros.comparacao;
                document.getElementById('setor').value = filtros.setor;
                document.getElementById('graduacao').value = filtros.graduacao;
                document.getElementById('tipoOcorrencia').value = filtros.tipoOcorrencia;
                document.getElementById('responsavel').value = filtros.responsavel;
                document.getElementById('status').value = filtros.status;
                document.getElementById('palavrasChave').value = filtros.palavrasChave;
                alert('Filtros carregados com sucesso!');
            });
        }

        function excluirFiltroSalvo(id) {
            if (confirm('Excluir filtro salvo?')) {
                fetch('filtros_avancados_excluir.php?id=' + id, { method: 'POST' })
                .then(r => r.json())
                .then(resp => { alert(resp.msg); location.reload(); });
            }
        }

        function atualizarGraficos(dados) {
            // Atualizar os gráficos com os dados recebidos do backend
            // ... implementar ...
        }
        function atualizarMetricas(dados) {
            // Atualizar as métricas comparativas com os dados recebidos do backend
            // ... implementar ...
        }

        // Configurar datas padrão
        window.addEventListener('load', function() {
            const hoje = new Date();
            const umMesAtras = new Date(hoje.getFullYear(), hoje.getMonth() - 1, hoje.getDate());
            
            document.getElementById('dataFim').value = hoje.toISOString().split('T')[0];
            document.getElementById('dataInicio').value = umMesAtras.toISOString().split('T')[0];
        });
    </script>
</body>
</html> 