<?php
// notificacoes.php - Sistema de Notificações Inteligentes
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();

// Buscar notificações e alertas
try {
    // Notificações não lidas
    $stmt = $pdo->query("
        SELECT * FROM alertas 
        WHERE status = 'pendente' 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $notificacoes = $stmt->fetchAll();

    // Alertas inteligentes baseados em dados
    $alertas = [];
    
    // Alerta: Muitas ocorrências hoje
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ocorrencias WHERE DATE(data) = CURDATE()");
    $ocorrenciasHoje = $stmt->fetch()['total'];
    if ($ocorrenciasHoje > 10) {
        $alertas[] = [
            'tipo' => 'warning',
            'titulo' => 'Alto Volume de Ocorrências',
            'mensagem' => "Hoje foram registradas {$ocorrenciasHoje} ocorrências. Considere reforçar o efetivo.",
            'icone' => 'fas fa-exclamation-triangle'
        ];
    }

    // Alerta: Pessoal em escala baixo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
    $pessoalAtivo = $stmt->fetch()['total'];
    if ($pessoalAtivo < 20) {
        $alertas[] = [
            'tipo' => 'danger',
            'titulo' => 'Efetivo Reduzido',
            'mensagem' => "Apenas {$pessoalAtivo} membros ativos. Verifique escalas e licenças.",
            'icone' => 'fas fa-users'
        ];
    }

    // Alerta: Comunicações urgentes não lidas
    $stmt = $pdo->query("
        SELECT COUNT(*) as total FROM comunicacoes 
        WHERE prioridade = 'urgente' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $comunicacoesUrgentes = $stmt->fetch()['total'];
    if ($comunicacoesUrgentes > 0) {
        $alertas[] = [
            'tipo' => 'danger',
            'titulo' => 'Comunicações Urgentes',
            'mensagem' => "{$comunicacoesUrgentes} comunicação(ões) urgente(s) nas últimas 24h.",
            'icone' => 'fas fa-bell'
        ];
    }

    // Alerta: Relatórios pendentes
    $stmt = $pdo->query("
        SELECT COUNT(*) as total FROM logs 
        WHERE acao = 'relatorio_gerado' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $relatoriosPendentes = $stmt->fetch()['total'];
    if ($relatoriosPendentes == 0) {
        $alertas[] = [
            'tipo' => 'info',
            'titulo' => 'Relatórios Pendentes',
            'mensagem' => "Nenhum relatório foi gerado na última semana.",
            'icone' => 'fas fa-file-alt'
        ];
    }

} catch (PDOException $e) {
    error_log("Erro ao buscar notificações: " . $e->getMessage());
    $notificacoes = [];
    $alertas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Notificações - Sistema Integrado da Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
        .notification-item {
            transition: all 0.3s ease;
        }
        .notification-item:hover {
            transform: translateX(5px);
        }
        .alert-danger { background-color: #fee2e2; border-left: 4px solid #dc2626; }
        .alert-warning { background-color: #fef3c7; border-left: 4px solid #f59e0b; }
        .alert-info { background-color: #dbeafe; border-left: 4px solid #3b82f6; }
        .alert-success { background-color: #dcfce7; border-left: 4px solid #16a34a; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <main class="content">
        <header class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Notificações Inteligentes</h2>
            <div class="text-gray-600 text-sm">
                Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
                (<?= htmlspecialchars($currentUser['perfil']) ?>)
            </div>
        </header>

        <!-- Botões de ação -->
        <div class="mb-6 flex gap-4">
            <button onclick="marcarTodasComoLidas()" class="btn-primary text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <i class="fas fa-check-double"></i>
                Marcar Todas como Lidas
            </button>
            <button onclick="configurarNotificacoes()" class="bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-gray-700">
                <i class="fas fa-cog"></i>
                Configurar Notificações
            </button>
            <button onclick="testarNotificacoes()" class="bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-green-700">
                <i class="fas fa-bell"></i>
                Testar Notificações
            </button>
        </div>

        <!-- Alertas Inteligentes -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Alertas Inteligentes</h3>
            <div class="space-y-4">
                <?php foreach ($alertas as $alerta): ?>
                <div class="alert-<?= $alerta['tipo'] ?> p-4 rounded-lg notification-item">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="<?= $alerta['icone'] ?> text-2xl text-<?= $alerta['tipo'] === 'danger' ? 'red' : ($alerta['tipo'] === 'warning' ? 'yellow' : ($alerta['tipo'] === 'info' ? 'blue' : 'green')) ?>-600"></i>
                        </div>
                        <div class="ml-3 flex-1">
                            <h4 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($alerta['titulo']) ?></h4>
                            <p class="text-gray-700 mt-1"><?= htmlspecialchars($alerta['mensagem']) ?></p>
                            <div class="mt-3 flex gap-2">
                                <button onclick="dismissAlerta(this)" class="text-sm text-gray-500 hover:text-gray-700">
                                    <i class="fas fa-times mr-1"></i>Dispensar
                                </button>
                                <button onclick="verDetalhes()" class="text-sm text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-eye mr-1"></i>Ver Detalhes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($alertas)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <i class="fas fa-check-circle mr-2"></i>
                    Nenhum alerta crítico no momento. Sistema funcionando normalmente.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notificações do Sistema -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Notificações do Sistema</h3>
            <div class="space-y-4">
                <?php foreach ($notificacoes as $notificacao): ?>
                <div class="bg-white rounded-lg shadow-md p-4 notification-item">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-bell text-blue-600 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($notificacao['titulo']) ?></h4>
                                <p class="text-gray-600 mt-1"><?= htmlspecialchars($notificacao['mensagem']) ?></p>
                                <p class="text-sm text-gray-500 mt-2">
                                    <i class="fas fa-clock mr-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($notificacao['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="marcarComoLida(<?= $notificacao['id'] ?>)" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-check"></i>
                            </button>
                            <button onclick="excluirNotificacao(<?= $notificacao['id'] ?>)" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($notificacoes)): ?>
                <div class="bg-gray-100 border border-gray-300 text-gray-700 px-4 py-3 rounded text-center">
                    <i class="fas fa-inbox text-2xl mb-2"></i>
                    <p>Nenhuma notificação pendente.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Configurações de Notificação -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Configurações de Notificação</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">Tipos de Notificação</h4>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="checkbox" checked class="mr-3">
                            <span>Alertas de Segurança</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" checked class="mr-3">
                            <span>Relatórios Pendentes</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" checked class="mr-3">
                            <span>Comunicações Urgentes</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="mr-3">
                            <span>Atualizações do Sistema</span>
                        </label>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">Frequência</h4>
                    <div class="space-y-3">
                        <label class="flex items-center">
                            <input type="radio" name="frequencia" value="imediato" checked class="mr-3">
                            <span>Imediato</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="frequencia" value="hora" class="mr-3">
                            <span>A cada hora</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="frequencia" value="diario" class="mr-3">
                            <span>Diário</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="frequencia" value="semanal" class="mr-3">
                            <span>Semanal</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-6">
                <button onclick="salvarConfiguracoes()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Salvar Configurações
                </button>
            </div>
        </div>
    </main>

    <script>
        function marcarTodasComoLidas() {
            if (confirm('Marcar todas as notificações como lidas?')) {
                fetch('backend/api/alertas.php/marcar-todas-lidas', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: window.csrfToken || '' })
                })
                .then(r => r.json())
                .then(resp => {
                    alert(resp.message || 'Todas as notificações foram marcadas como lidas!');
                    location.reload();
                })
                .catch(() => alert('Erro ao marcar todas como lidas.'));
            }
        }

        function configurarNotificacoes() {
            alert('Configurações de notificação serão implementadas em breve');
        }

        function testarNotificacoes() {
            // Testar notificação do navegador
            if ('Notification' in window) {
                Notification.requestPermission().then(function(permission) {
                    if (permission === 'granted') {
                        new Notification('Sistema Integrado da Guarda Civil', {
                            body: 'Teste de notificação do sistema',
                            icon: '/img/logo1.png'
                        });
                    }
                });
            }
            alert('Notificação de teste enviada!');
        }

        function dismissAlerta(element) {
            const alerta = element.closest('.notification-item');
            alerta.style.opacity = '0';
            setTimeout(() => {
                alerta.remove();
            }, 300);
        }

        function verDetalhes() {
            alert('Detalhes do alerta serão exibidos em breve');
        }

        function marcarComoLida(id) {
            fetch(`backend/api/alertas.php/${id}/marcar-lida`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: window.csrfToken || '' })
            })
            .then(r => r.json())
            .then(resp => {
                alert(resp.message || 'Notificação marcada como lida!');
                location.reload();
            })
            .catch(() => alert('Erro ao marcar como lida.'));
        }

        function excluirNotificacao(id) {
            if (confirm('Excluir esta notificação?')) {
                fetch(`backend/api/alertas.php/${id}/excluir`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: window.csrfToken || '' })
                })
                .then(r => r.json())
                .then(resp => {
                    alert(resp.message || 'Notificação excluída!');
                    location.reload();
                })
                .catch(() => alert('Erro ao excluir notificação.'));
            }
        }

        function salvarConfiguracoes() {
            fetch('backend/api/alertas.php/configuracoes', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: window.csrfToken || '' })
            })
            .then(r => r.json())
            .then(resp => {
                alert(resp.message || 'Configurações salvas com sucesso!');
            })
            .catch(() => alert('Erro ao salvar configurações.'));
        }

        // Verificar permissões de notificação
        window.addEventListener('load', function() {
            if ('Notification' in window) {
                if (Notification.permission === 'default') {
                    console.log('Permissão de notificação não definida');
                } else if (Notification.permission === 'granted') {
                    console.log('Permissão de notificação concedida');
                } else {
                    console.log('Permissão de notificação negada');
                }
            }
        });

        // Simular notificações em tempo real
        setInterval(function() {
            // Verificar novas notificações a cada 30 segundos
            console.log('Verificando novas notificações...');
        }, 30000);
    </script>
</body>
</html> 