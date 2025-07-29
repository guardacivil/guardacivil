<?php
// dashboard_mobile.php - Dashboard Mobile Otimizado
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Buscar dados do dashboard
try {
    // Total de usuários
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1');
    $totalUsuarios = $stmt->fetch()['total'] ?? 0;

    // Total de ocorrências
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM ocorrencias');
    $totalOcorrencias = $stmt->fetch()['total'] ?? 0;

    // Ocorrências do dia
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM ocorrencias WHERE DATE(data) = CURDATE()');
    $ocorrenciasHoje = $stmt->fetch()['total'] ?? 0;

    // Ocorrências da semana
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM ocorrencias WHERE YEARWEEK(data) = YEARWEEK(CURDATE())');
    $ocorrenciasSemana = $stmt->fetch()['total'] ?? 0;

    // Últimas ocorrências
    $stmt = $pdo->query('SELECT * FROM ocorrencias ORDER BY data DESC LIMIT 5');
    $ultimasOcorrencias = $stmt->fetchAll();

    // Próximas escalas
    $stmt = $pdo->query('SELECT * FROM escalas WHERE data_inicio >= CURDATE() ORDER BY data_inicio ASC LIMIT 3');
    $proximasEscalas = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Erro ao buscar dados do dashboard: " . $e->getMessage());
    $totalUsuarios = 0;
    $totalOcorrencias = 0;
    $ocorrenciasHoje = 0;
    $ocorrenciasSemana = 0;
    $ultimasOcorrencias = [];
    $proximasEscalas = [];
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <title>Dashboard Mobile - Sistema Integrado da Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .mobile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .mobile-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 0.5rem;
            z-index: 40;
        }
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #6b7280;
            font-size: 0.75rem;
        }
        .nav-item.active {
            background-color: #dbeafe;
            color: var(--primary-color);
        }
        .nav-item i {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1rem;
        }
        .quick-action {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease;
        }
        .quick-action:active {
            transform: scale(0.95);
        }
        .swipe-area {
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }
        .swipe-item {
            scroll-snap-align: start;
            min-width: 280px;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .pull-to-refresh {
            text-align: center;
            padding: 1rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <!-- Header Mobile -->
    <header class="mobile-header">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <img src="img/logo1.png" alt="Logo" class="w-8 h-8 mr-3" />
                <div>
                    <h1 class="text-lg font-bold">Sistema Integrado da Guarda Civil</h1>
                    <p class="text-sm opacity-90">GCM Araçoiaba da Serra</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="toggleNotifications()" class="relative">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="notification-badge">3</span>
                </button>
                <button onclick="toggleMenu()" class="text-xl">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        <div class="mt-3 text-sm opacity-90">
            Olá, <?= htmlspecialchars($currentUser['nome']) ?>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="pb-20">
        <!-- Pull to Refresh -->
        <div class="pull-to-refresh" id="pullToRefresh">
            <i class="fas fa-arrow-down mr-2"></i>
            Puxe para atualizar
        </div>

        <!-- Estatísticas Rápidas -->
        <div class="p-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Resumo do Dia</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Ocorrências Hoje</p>
                            <p class="text-2xl font-bold text-blue-600"><?= $ocorrenciasHoje ?></p>
                        </div>
                        <i class="fas fa-file-alt text-2xl text-blue-200"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Esta Semana</p>
                            <p class="text-2xl font-bold text-green-600"><?= $ocorrenciasSemana ?></p>
                        </div>
                        <i class="fas fa-calendar-week text-2xl text-green-200"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Pessoal</p>
                            <p class="text-2xl font-bold text-purple-600"><?= $totalUsuarios ?></p>
                        </div>
                        <i class="fas fa-users text-2xl text-purple-200"></i>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Geral</p>
                            <p class="text-2xl font-bold text-orange-600"><?= $totalOcorrencias ?></p>
                        </div>
                        <i class="fas fa-chart-bar text-2xl text-orange-200"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="p-4">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Ações Rápidas</h2>
            <div class="grid grid-cols-2 gap-4">
                <a href="ROGCM.php" class="quick-action">
                    <i class="fas fa-plus-circle text-3xl text-blue-600 mb-2"></i>
                    <p class="font-semibold">Nova Ocorrência</p>
                </a>
                <a href="comunicacao.php" class="quick-action">
                    <i class="fas fa-comments text-3xl text-green-600 mb-2"></i>
                    <p class="font-semibold">Comunicação</p>
                </a>
                <a href="escalas.php" class="quick-action">
                    <i class="fas fa-calendar-alt text-3xl text-purple-600 mb-2"></i>
                    <p class="font-semibold">Escalas</p>
                </a>
                <a href="relatorios.php" class="quick-action">
                    <i class="fas fa-chart-line text-3xl text-orange-600 mb-2"></i>
                    <p class="font-semibold">Relatórios</p>
                </a>
            </div>
        </div>

        <!-- Últimas Ocorrências -->
        <div class="p-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">Últimas Ocorrências</h2>
                <a href="gerenciar_ocorrencias.php" class="text-blue-600 text-sm">Ver Todas</a>
            </div>
            <div class="space-y-3">
                <?php foreach ($ultimasOcorrencias as $ocorrencia): ?>
                <div class="bg-white rounded-lg p-4 shadow-sm">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($ocorrencia['tipo']) ?></h3>
                            <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars(substr($ocorrencia['descricao'], 0, 100)) ?>...</p>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-clock mr-1"></i>
                                <?= date('d/m/Y H:i', strtotime($ocorrencia['data'])) ?>
                            </p>
                        </div>
                        <div class="ml-3">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                <?= ucfirst($ocorrencia['status'] ?? 'Ativo') ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($ultimasOcorrencias)): ?>
                <div class="bg-gray-50 rounded-lg p-8 text-center">
                    <i class="fas fa-inbox text-3xl text-gray-400 mb-3"></i>
                    <p class="text-gray-500">Nenhuma ocorrência recente</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Próximas Escalas -->
        <div class="p-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-800">Próximas Escalas</h2>
                <a href="minhas_escalas.php" class="text-blue-600 text-sm">Ver Todas</a>
            </div>
            <div class="swipe-area">
                <div class="flex space-x-4">
                    <?php foreach ($proximasEscalas as $escala): ?>
                    <div class="swipe-item bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($escala['nome']) ?></h3>
                        <p class="text-sm text-gray-600 mt-1"><?= ucfirst($escala['turno']) ?></p>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-calendar mr-1"></i>
                            <?= date('d/m/Y', strtotime($escala['data_inicio'])) ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($proximasEscalas)): ?>
                    <div class="swipe-item bg-gray-50 rounded-lg p-8 text-center min-w-full">
                        <i class="fas fa-calendar-times text-3xl text-gray-400 mb-3"></i>
                        <p class="text-gray-500">Nenhuma escala próxima</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Menu Lateral (Oculto) -->
        <div id="sideMenu" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
            <div class="absolute right-0 top-0 h-full w-80 bg-white shadow-lg">
                <div class="p-4 border-b">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Menu</h3>
                        <button onclick="toggleMenu()" class="text-gray-500">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                <nav class="p-4">
                    <a href="dashboard.php" class="block py-3 px-4 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-home mr-3"></i>Dashboard
                    </a>
                    <a href="pessoal.php" class="block py-3 px-4 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-users mr-3"></i>Gestão de Pessoal
                    </a>
                    <a href="comunicacao.php" class="block py-3 px-4 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-comments mr-3"></i>Comunicação
                    </a>
                    <a href="escalas.php" class="block py-3 px-4 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-calendar-alt mr-3"></i>Escalas
                    </a>
                    <a href="ROGCM.php" class="block py-3 px-4 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-file-alt mr-3"></i>Ocorrências
                    </a>
                    <a href="relatorios.php" class="block py-3 px-4 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-chart-line mr-3"></i>Relatórios
                    </a>
                    <a href="usuarios.php" class="block py-3 px-4 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-cog mr-3"></i>Administração
                    </a>
                    <a href="logout.php" class="block py-3 px-4 rounded-lg hover:bg-red-100 text-red-600">
                        <i class="fas fa-sign-out-alt mr-3"></i>Sair
                    </a>
                </nav>
            </div>
        </div>
    </main>

    <!-- Navegação Mobile -->
    <nav class="mobile-nav">
        <div class="grid grid-cols-5 gap-1">
            <a href="dashboard_mobile.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Início</span>
            </a>
            <a href="ROGCM.php" class="nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Ocorrências</span>
            </a>
            <a href="comunicacao.php" class="nav-item">
                <i class="fas fa-comments"></i>
                <span>Chat</span>
            </a>
            <a href="escalas.php" class="nav-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Escalas</span>
            </a>
            <a href="relatorios.php" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Relatórios</span>
            </a>
        </div>
    </nav>

    <script>
        // Toggle menu lateral
        function toggleMenu() {
            const menu = document.getElementById('sideMenu');
            menu.classList.toggle('hidden');
        }

        // Toggle notificações
        function toggleNotifications() {
            alert('Notificações serão implementadas em breve');
        }

        // Pull to refresh
        let startY = 0;
        let currentY = 0;
        let pullDistance = 0;
        const pullThreshold = 100;

        document.addEventListener('touchstart', function(e) {
            startY = e.touches[0].clientY;
        });

        document.addEventListener('touchmove', function(e) {
            currentY = e.touches[0].clientY;
            pullDistance = currentY - startY;
            
            if (pullDistance > 0 && window.scrollY === 0) {
                e.preventDefault();
                const refreshElement = document.getElementById('pullToRefresh');
                if (pullDistance > pullThreshold) {
                    refreshElement.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Solte para atualizar';
                } else {
                    refreshElement.innerHTML = '<i class="fas fa-arrow-down mr-2"></i>Puxe para atualizar';
                }
            }
        });

        document.addEventListener('touchend', function(e) {
            if (pullDistance > pullThreshold) {
                // Atualizar dados
                location.reload();
            }
            pullDistance = 0;
            document.getElementById('pullToRefresh').innerHTML = '<i class="fas fa-arrow-down mr-2"></i>Puxe para atualizar';
        });

        // Detectar orientação do dispositivo
        window.addEventListener('orientationchange', function() {
            setTimeout(function() {
                // Ajustar layout se necessário
                console.log('Orientação alterada');
            }, 100);
        });

        // Verificar se é dispositivo móvel
        function isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        // Adicionar classe mobile se necessário
        if (isMobile()) {
            document.body.classList.add('mobile-device');
        }

        // Atualizar dados periodicamente
        setInterval(function() {
            // Verificar novas notificações
            console.log('Verificando atualizações...');
        }, 30000);

        // Prevenir zoom em inputs
        document.addEventListener('gesturestart', function(e) {
            e.preventDefault();
        });

        // Melhorar performance de scroll
        let ticking = false;
        function updateOnScroll() {
            if (!ticking) {
                requestAnimationFrame(function() {
                    // Atualizações baseadas no scroll
                    ticking = false;
                });
                ticking = true;
            }
        }

        window.addEventListener('scroll', updateOnScroll);
    </script>
</body>
</html> 