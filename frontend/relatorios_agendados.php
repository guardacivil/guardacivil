<?php
// relatorios_agendados.php - Sistema de Relatórios Agendados
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();

// Buscar relatórios agendados
try {
    $stmt = $pdo->query("
        SELECT * FROM relatorios_agendados 
        ORDER BY proximo_envio ASC
    ");
    $relatoriosAgendados = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar relatórios agendados: " . $e->getMessage());
    $relatoriosAgendados = [];
}

// Buscar usuários para destinatários
try {
    $stmt = $pdo->query("SELECT id, nome, email FROM usuarios WHERE ativo = 1 ORDER BY nome");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Relatórios Agendados - Sistema Integrado da Guarda Civil</title>
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
        .status-ativo { background-color: #dcfce7; border-left: 4px solid #16a34a; }
        .status-pausado { background-color: #fef3c7; border-left: 4px solid #f59e0b; }
        .status-inativo { background-color: #fee2e2; border-left: 4px solid #dc2626; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <main class="content">
        <header class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold text-gray-800"><i class="fas fa-clock mr-2"></i>Relatórios Agendados</h2>
            <div class="text-gray-600 text-sm">
                Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
                (<?= htmlspecialchars($currentUser['perfil']) ?>)
            </div>
        </header>

        <!-- Botões de ação -->
        <div class="mb-6 flex gap-4">
            <button onclick="abrirModalCriar()" class="btn-primary text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Novo Relatório Agendado
            </button>
            <button onclick="testarEnvio()" class="bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-green-700">
                <i class="fas fa-paper-plane"></i>
                Testar Envio
            </button>
            <button onclick="verHistorico()" class="bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-gray-700">
                <i class="fas fa-history"></i>
                Histórico
            </button>
        </div>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Total Agendados</h3>
                <p class="text-3xl font-bold text-blue-600"><?= count($relatoriosAgendados) ?></p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Ativos</h3>
                <p class="text-3xl font-bold text-green-600"><?= count(array_filter($relatoriosAgendados, function($r) { return $r['status'] === 'ativo'; })) ?></p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Próximo Envio</h3>
                <p class="text-3xl font-bold text-orange-600"><?= count(array_filter($relatoriosAgendados, function($r) { return strtotime($r['proximo_envio']) <= strtotime('+1 day'); })) ?></p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Enviados Hoje</h3>
                <p class="text-3xl font-bold text-purple-600">0</p>
            </div>
        </div>

        <!-- Lista de Relatórios Agendados -->
        <div class="space-y-4">
            <?php foreach ($relatoriosAgendados as $relatorio): ?>
            <div class="status-<?= $relatorio['status'] ?> rounded-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($relatorio['nome']) ?></h3>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($relatorio['descricao']) ?></p>
                    </div>
                    <div class="flex gap-2">
                        <?php if ($relatorio['status'] === 'ativo'): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-play mr-1"></i>Ativo
                            </span>
                        <?php elseif ($relatorio['status'] === 'pausado'): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-pause mr-1"></i>Pausado
                            </span>
                        <?php else: ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-stop mr-1"></i>Inativo
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <span class="text-sm font-medium text-gray-700">Frequência:</span>
                        <p class="text-sm text-gray-900"><?= ucfirst($relatorio['frequencia']) ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-700">Próximo Envio:</span>
                        <p class="text-sm text-gray-900"><?= date('d/m/Y H:i', strtotime($relatorio['proximo_envio'])) ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-700">Formato:</span>
                        <p class="text-sm text-gray-900"><?= strtoupper($relatorio['formato']) ?></p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <span class="text-sm font-medium text-gray-700">Destinatários:</span>
                    <p class="text-sm text-gray-900"><?= htmlspecialchars($relatorio['destinatarios']) ?></p>
                </div>
                
                <div class="flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-clock mr-1"></i>
                        Último envio: <?= $relatorio['ultimo_envio'] ? date('d/m/Y H:i', strtotime($relatorio['ultimo_envio'])) : 'Nunca' ?>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="editarRelatorio(<?= $relatorio['id'] ?>)" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i> Editar
                        </button>
                        <button onclick="toggleStatus(<?= $relatorio['id'] ?>, '<?= $relatorio['status'] ?>')" class="text-orange-600 hover:text-orange-800">
                            <i class="fas fa-toggle-on"></i> <?= $relatorio['status'] === 'ativo' ? 'Pausar' : 'Ativar' ?>
                        </button>
                        <button onclick="enviarAgora(<?= $relatorio['id'] ?>)" class="text-green-600 hover:text-green-800">
                            <i class="fas fa-paper-plane"></i> Enviar Agora
                        </button>
                        <button onclick="excluirRelatorio(<?= $relatorio['id'] ?>)" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i> Excluir
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($relatoriosAgendados)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-calendar-alt text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhum relatório agendado</h3>
                <p class="text-gray-500">Crie o primeiro relatório agendado para começar.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal para criar/editar relatório agendado -->
    <div id="modalRelatorio" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800" id="modalTitle">Novo Relatório Agendado</h3>
                </div>
                <form id="formRelatorio" class="p-6">
                    <input type="hidden" id="relatorio_id" name="id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Relatório</label>
                            <input type="text" id="nome" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Relatório</label>
                            <select id="tipo" name="tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="pessoal">Relatório de Pessoal</option>
                                <option value="ocorrencias">Relatório de Ocorrências</option>
                                <option value="escalas">Relatório de Escalas</option>
                                <option value="comunicacoes">Relatório de Comunicações</option>
                                <option value="atividades">Relatório de Atividades</option>
                                <option value="personalizado">Relatório Personalizado</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Frequência</label>
                            <select id="frequencia" name="frequencia" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="diario">Diário</option>
                                <option value="semanal">Semanal</option>
                                <option value="mensal">Mensal</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="semestral">Semestral</option>
                                <option value="anual">Anual</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Horário de Envio</label>
                            <input type="time" id="horario" name="horario" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Formato</label>
                            <select id="formato" name="formato" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="status" name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="ativo">Ativo</option>
                                <option value="pausado">Pausado</option>
                                <option value="inativo">Inativo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Destinatários (e-mails separados por vírgula)</label>
                        <textarea id="destinatarios" name="destinatarios" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="fecharModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalCriar() {
            document.getElementById('modalTitle').textContent = 'Novo Relatório Agendado';
            document.getElementById('formRelatorio').reset();
            document.getElementById('relatorio_id').value = '';
            document.getElementById('modalRelatorio').classList.remove('hidden');
        }
        
        function fecharModal() {
            document.getElementById('modalRelatorio').classList.add('hidden');
        }
        
        function editarRelatorio(id) {
            fetch(`backend/api/relatorios_agendados.php/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const r = data.data;
                        document.getElementById('modalTitle').textContent = 'Editar Relatório Agendado';
                        document.getElementById('relatorio_id').value = r.id;
                        document.getElementById('nome').value = r.nome;
                        document.getElementById('tipo').value = r.tipo;
                        document.getElementById('frequencia').value = r.frequencia;
                        document.getElementById('horario').value = r.horario;
                        document.getElementById('formato').value = r.formato;
                        document.getElementById('status').value = r.status;
                        document.getElementById('destinatarios').value = r.destinatarios;
                        document.getElementById('descricao').value = r.descricao || '';
                        document.getElementById('modalRelatorio').classList.remove('hidden');
                    } else {
                        alert('Erro ao buscar relatório: ' + data.message);
                    }
                })
                .catch(() => alert('Erro ao buscar dados do relatório.'));
        }

        document.getElementById('formRelatorio').onsubmit = function(e) {
            e.preventDefault();
            const id = document.getElementById('relatorio_id').value;
            const data = {
                nome: document.getElementById('nome').value,
                tipo: document.getElementById('tipo').value,
                frequencia: document.getElementById('frequencia').value,
                horario: document.getElementById('horario').value,
                formato: document.getElementById('formato').value,
                status: document.getElementById('status').value,
                destinatarios: document.getElementById('destinatarios').value,
                descricao: document.getElementById('descricao').value,
                csrf_token: window.csrfToken || ''
            };
            if (id) {
                fetch(`backend/api/relatorios_agendados.php/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        alert('Relatório agendado atualizado com sucesso!');
                        location.reload();
                    } else {
                        alert(resp.message || 'Erro ao atualizar relatório.');
                    }
                })
                .catch(() => alert('Erro ao atualizar relatório.'));
            } else {
                fetch('backend/api/relatorios_agendados.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        alert('Relatório agendado criado com sucesso!');
                        location.reload();
                    } else {
                        alert(resp.message || 'Erro ao criar relatório.');
                    }
                })
                .catch(() => alert('Erro ao criar relatório.'));
            }
        }

        function toggleStatus(id, statusAtual) {
            const novoStatus = statusAtual === 'ativo' ? 'pausado' : 'ativo';
            if (confirm(`Deseja ${novoStatus === 'ativo' ? 'ativar' : 'pausar'} este relatório?`)) {
                fetch(`backend/api/relatorios_agendados.php/${id}/status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: novoStatus, csrf_token: window.csrfToken || '' })
                })
                .then(r => r.json())
                .then(resp => {
                    alert(resp.message || 'Status alterado!');
                    location.reload();
                })
                .catch(() => alert('Erro ao alterar status.'));
            }
        }

        function enviarAgora(id) {
            if (confirm('Enviar relatório agora?')) {
                fetch(`backend/api/relatorios_agendados.php/${id}/enviar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: window.csrfToken || '' })
                })
                .then(r => r.json())
                .then(resp => {
                    alert(resp.message || 'Relatório enviado!');
                    location.reload();
                })
                .catch(() => alert('Erro ao enviar relatório.'));
            }
        }

        function excluirRelatorio(id) {
            if (confirm('Tem certeza que deseja excluir este relatório agendado?')) {
                fetch(`backend/api/relatorios_agendados.php/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: window.csrfToken || '' })
                })
                .then(r => r.json())
                .then(resp => {
                    alert(resp.message || 'Relatório excluído!');
                    location.reload();
                })
                .catch(() => alert('Erro ao excluir relatório.'));
            }
        }

        function testarEnvio() {
            const id = document.getElementById('relatorio_id').value;
            if (!id) { alert('Abra um relatório para testar envio.'); return; }
            fetch(`backend/api/relatorios_agendados.php/${id}/teste`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: window.csrfToken || '' })
            })
            .then(r => r.json())
            .then(resp => {
                alert(resp.message || 'Teste de envio realizado!');
            })
            .catch(() => alert('Erro ao testar envio.'));
        }

        function verHistorico() {
            const id = document.getElementById('relatorio_id').value;
            if (!id) { alert('Abra um relatório para ver o histórico.'); return; }
            fetch(`backend/api/relatorios_agendados.php/${id}/historico`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        let html = '<h3 class="text-lg font-semibold mb-2">Histórico de Envios</h3>';
                        if (data.data.length === 0) {
                            html += '<p class="text-gray-500">Nenhum envio registrado.</p>';
                        } else {
                            html += '<ul class="mb-2">';
                            data.data.forEach(h => {
                                html += `<li>${h.enviado_em} - ${h.status}</li>`;
                            });
                            html += '</ul>';
                        }
                        alert(html);
                    } else {
                        alert('Erro ao buscar histórico: ' + data.message);
                    }
                })
                .catch(() => alert('Erro ao buscar histórico.'));
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('modalRelatorio').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModal();
            }
        });
        
        // Processar formulário
        document.getElementById('formRelatorio').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Relatório agendado salvo com sucesso!');
            fecharModal();
        });
    </script>
</body>
</html> 