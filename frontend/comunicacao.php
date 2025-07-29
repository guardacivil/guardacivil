<?php
// comunicacao.php - Sistema de Comunicação Interna da GCM
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar permissão
if (!hasPermission('comunicacao') && !isAdminLoggedIn()) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Buscar comunicações
try {
    $currentUser = getCurrentUser();
    // Formulário de Filtros
    $setores = [];
    $graduacoes = [];

    if (isset($_GET['filtro_setor'])) {
        $stmt = $pdo->prepare("SELECT * FROM setores WHERE ativo = 1 AND id = ?");
        $stmt->execute([$_GET['filtro_setor']]);
        $setores = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("SELECT * FROM setores WHERE ativo = 1 ORDER BY nome");
        $setores = $stmt->fetchAll();
    }

    if (isset($_GET['filtro_graduacao'])) {
        $stmt = $pdo->prepare("SELECT * FROM graduacoes WHERE id = ?");
        $stmt->execute([$_GET['filtro_graduacao']]);
        $graduacoes = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("SELECT * FROM graduacoes ORDER BY nivel DESC");
        $graduacoes = $stmt->fetchAll();
    }

    // Aplicar filtros na busca das comunicações
    $where = [];
    $params = [];
    if (!empty($_GET['filtro_setor'])) {
        $where[] = 'c.setor_id = :setor_id';
        $params[':setor_id'] = $_GET['filtro_setor'];
    }
    if (!empty($_GET['filtro_graduacao'])) {
        $where[] = 'c.graduacao_minima = :graduacao_minima';
        $params[':graduacao_minima'] = $_GET['filtro_graduacao'];
    }
    if (!empty($_GET['filtro_prioridade'])) {
        $where[] = 'c.prioridade = :prioridade';
        $params[':prioridade'] = $_GET['filtro_prioridade'];
    }
    if (!empty($_GET['filtro_palavra'])) {
        $where[] = '(c.titulo LIKE :palavra OR c.mensagem LIKE :palavra)';
        $params[':palavra'] = '%' . $_GET['filtro_palavra'] . '%';
    }
    $whereSql = $where ? ' AND ' . implode(' AND ', $where) : '';
    $sql = "SELECT c.*, u.nome as autor_nome, s.nome as setor_nome, g.nome as graduacao_minima_nome 
            FROM comunicacoes c 
            LEFT JOIN usuarios u ON c.autor_id = u.id 
            LEFT JOIN setores s ON c.setor_id = s.id 
            LEFT JOIN graduacoes g ON c.graduacao_minima = g.id 
            WHERE (c.data_expiracao IS NULL OR c.data_expiracao >= CURDATE()) $whereSql
            ORDER BY c.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $comunicacoes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erro ao buscar comunicações: " . $e->getMessage());
    $comunicacoes = [];
    $setores = [];
    $graduacoes = [];
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Comunicação Interna - Sistema Integrado da Guarda Civil</title>
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
            background: linear-gradient(135deg, #2563eb 0%, #000 100%);
            min-height: 100vh;
            margin: 0;
        }
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
        .prioridade-urgente { background-color: #fee2e2; border-left: 4px solid #dc2626; }
        .prioridade-alta { background-color: #fef3c7; border-left: 4px solid #f59e0b; }
        .prioridade-normal { background-color: #dbeafe; border-left: 4px solid #3b82f6; }
        .prioridade-baixa { background-color: #dcfce7; border-left: 4px solid #16a34a; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <main class="content">
        <header class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Comunicação Interna</h2>
            <div class="text-gray-600 text-sm">
                Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
                (<?= htmlspecialchars($currentUser['perfil']) ?>)
            </div>
        </header>

        <!-- Botões de ação -->
        <div class="mb-6 flex gap-4">
            <button onclick="abrirModalComunicacao()" class="btn-primary text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Nova Comunicação
            </button>
            <!-- Formulário de Filtros -->
            <form method="GET" class="mb-6 flex flex-wrap gap-4 items-end bg-white p-4 rounded shadow">
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Setor</label>
                <select name="filtro_setor" class="px-2 py-1 border rounded">
                  <option value="">Todos</option>
                  <?php foreach ($setores as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= isset($_GET['filtro_setor']) && $_GET['filtro_setor'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Graduação Mínima</label>
                <select name="filtro_graduacao" class="px-2 py-1 border rounded">
                  <option value="">Todas</option>
                  <?php foreach ($graduacoes as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= isset($_GET['filtro_graduacao']) && $_GET['filtro_graduacao'] == $g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Prioridade</label>
                <select name="filtro_prioridade" class="px-2 py-1 border rounded">
                  <option value="">Todas</option>
                  <option value="normal" <?= isset($_GET['filtro_prioridade']) && $_GET['filtro_prioridade'] == 'normal' ? 'selected' : '' ?>>Normal</option>
                  <option value="baixa" <?= isset($_GET['filtro_prioridade']) && $_GET['filtro_prioridade'] == 'baixa' ? 'selected' : '' ?>>Baixa</option>
                  <option value="alta" <?= isset($_GET['filtro_prioridade']) && $_GET['filtro_prioridade'] == 'alta' ? 'selected' : '' ?>>Alta</option>
                  <option value="urgente" <?= isset($_GET['filtro_prioridade']) && $_GET['filtro_prioridade'] == 'urgente' ? 'selected' : '' ?>>Urgente</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Palavra-chave</label>
                <input type="text" name="filtro_palavra" value="<?= htmlspecialchars($_GET['filtro_palavra'] ?? '') ?>" class="px-2 py-1 border rounded" placeholder="Buscar...">
              </div>
              <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 flex items-center gap-2">
                <i class="fas fa-filter"></i> Filtrar
              </button>
            </form>
        </div>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Total de Comunicações</h3>
                <p class="text-3xl font-bold text-blue-600"><?= count($comunicacoes) ?></p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Urgentes</h3>
                <p class="text-3xl font-bold text-red-600"><?= count(array_filter($comunicacoes, function($c) { return $c['prioridade'] === 'urgente'; })) ?></p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Altas</h3>
                <p class="text-3xl font-bold text-orange-600"><?= count(array_filter($comunicacoes, function($c) { return $c['prioridade'] === 'alta'; })) ?></p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Normais</h3>
                <p class="text-3xl font-bold text-green-600"><?= count(array_filter($comunicacoes, function($c) { return $c['prioridade'] === 'normal'; })) ?></p>
            </div>
        </div>

        <!-- Lista de Comunicações -->
        <div class="space-y-4">
            <?php foreach ($comunicacoes as $comunicacao): ?>
            <div class="bg-white rounded-lg shadow-md p-6 prioridade-<?= $comunicacao['prioridade'] ?>">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($comunicacao['titulo']) ?></h3>
                        <p class="text-sm text-gray-600">
                            Por: <?= htmlspecialchars($comunicacao['autor_nome']) ?> | 
                            <?= date('d/m/Y H:i', strtotime($comunicacao['created_at'])) ?>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <?php if ($comunicacao['prioridade'] === 'urgente'): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Urgente
                            </span>
                        <?php elseif ($comunicacao['prioridade'] === 'alta'): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                <i class="fas fa-exclamation-circle mr-1"></i>Alta
                            </span>
                        <?php elseif ($comunicacao['prioridade'] === 'normal'): ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                <i class="fas fa-info-circle mr-1"></i>Normal
                            </span>
                        <?php else: ?>
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Baixa
                            </span>
                        <?php endif; ?>
                        
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                            <?= ucfirst($comunicacao['tipo']) ?>
                        </span>
                    </div>
                </div>
                
                <div class="prose max-w-none mb-4">
                    <?= nl2br(htmlspecialchars($comunicacao['conteudo'])) ?>
                </div>
                
                <div class="flex justify-between items-center text-sm text-gray-600">
                    <div class="flex gap-4">
                        <?php if ($comunicacao['setor_nome']): ?>
                            <span><i class="fas fa-building mr-1"></i><?= htmlspecialchars($comunicacao['setor_nome']) ?></span>
                        <?php endif; ?>
                        <?php if ($comunicacao['graduacao_minima_nome']): ?>
                            <span><i class="fas fa-star mr-1"></i><?= htmlspecialchars($comunicacao['graduacao_minima_nome']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="enviarEmail(<?= $comunicacao['id'] ?>)" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-envelope"></i> Enviar E-mail
                        </button>
                        <?php if ($comunicacao['autor_id'] == $currentUser['id'] || isAdminLoggedIn()): ?>
                            <button onclick="editarComunicacao(<?= $comunicacao['id'] ?>)" class="text-green-600 hover:text-green-800">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button onclick="excluirComunicacao(<?= $comunicacao['id'] ?>)" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($comunicacoes)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-comments text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhuma comunicação encontrada</h3>
                <p class="text-gray-500">Crie a primeira comunicação para começar.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal para criar/editar comunicação -->
    <div id="modalComunicacao" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center justify-center mb-4"><i class="fas fa-envelope-open-text text-3xl text-blue-600 mr-2"></i> Nova Comunicação</h3>
                </div>
                <form id="formComunicacao" class="p-6">
                    <input type="hidden" id="comunicacao_id" name="id">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo_comunicacao" name="titulo" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mensagem *</label>
                        <textarea id="mensagem_comunicacao" name="mensagem" rows="4" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Setor de Destino *</label>
                        <select id="setor_comunicacao" name="setor_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione...</option>
                            <?php foreach ($setores as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Graduação Mínima *</label>
                        <select id="graduacao_comunicacao" name="graduacao_minima" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione...</option>
                            <?php foreach ($graduacoes as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prioridade *</label>
                        <select id="prioridade_comunicacao" name="prioridade" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="normal">Normal</option>
                            <option value="baixa">Baixa</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Expiração</label>
                        <input type="date" id="expiracao_comunicacao" name="data_expiracao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div id="feedbackComunicacao" class="mt-2 text-center text-sm"></div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" onclick="fecharModalComunicacao()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancelar</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalComunicacao() {
            document.getElementById('modalComunicacaoTitle').textContent = 'Nova Comunicação';
            document.getElementById('formComunicacao').reset();
            document.getElementById('comunicacao_id').value = '';
            document.getElementById('feedbackComunicacao').textContent = '';
            document.getElementById('modalComunicacao').classList.remove('hidden');
        }
        
        function fecharModalComunicacao() {
            document.getElementById('modalComunicacao').classList.add('hidden');
        }
        
        function editarComunicacao(id) {
            fetch(`backend/api/comunicacao.php/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const c = data.data;
                        document.getElementById('modalComunicacaoTitle').textContent = 'Editar Comunicação';
                        document.getElementById('comunicacao_id').value = c.id;
                        document.getElementById('titulo_comunicacao').value = c.titulo;
                        document.getElementById('mensagem_comunicacao').value = c.conteudo;
                        document.getElementById('setor_comunicacao').value = c.setor_id;
                        document.getElementById('graduacao_comunicacao').value = c.graduacao_minima;
                        document.getElementById('prioridade_comunicacao').value = c.prioridade;
                        document.getElementById('expiracao_comunicacao').value = c.data_expiracao || '';
                        document.getElementById('feedbackComunicacao').textContent = '';
                        document.getElementById('modalComunicacao').classList.remove('hidden');
                    } else {
                        alert('Erro ao buscar comunicação: ' + data.message);
                    }
                })
                .catch(() => alert('Erro ao buscar dados da comunicação.'));
        }
        
        function excluirComunicacao(id) {
            if (confirm('Tem certeza que deseja excluir esta comunicação?')) {
                fetch(`backend/api/comunicacao.php/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: window.csrfToken || '' })
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        alert('Comunicação excluída com sucesso!');
                        location.reload();
                    } else {
                        alert(resp.message || 'Erro ao excluir comunicação.');
                    }
                })
                .catch(() => alert('Erro ao excluir comunicação.'));
            }
        }
        
        function enviarEmail(id) {
            if (confirm('Deseja enviar esta comunicação por e-mail?')) {
                fetch(`backend/api/comunicacao.php/${id}/send-email`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: window.csrfToken || '' })
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        alert(resp.message || 'E-mail enviado com sucesso!');
                    } else {
                        alert(resp.message || 'Erro ao enviar e-mail.');
                    }
                })
                .catch(() => alert('Erro ao enviar e-mail.'));
            }
        }
        
        function filtrarComunicacoes() {
            document.querySelector('form[method=GET]').submit();
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('modalComunicacao').addEventListener('click', function(e) {
            if (e.target === this) {
                fecharModalComunicacao();
            }
        });

        // Submissão do formulário de comunicação (criação/edição)
        document.getElementById('formComunicacao').onsubmit = function(e) {
            e.preventDefault();
            const id = document.getElementById('comunicacao_id').value;
            const form = e.target;
            const data = {
                titulo: form.titulo.value,
                conteudo: form.mensagem.value,
                setor_id: form.setor_id.value,
                graduacao_minima: form.graduacao_minima.value,
                prioridade: form.prioridade.value,
                data_expiracao: form.data_expiracao.value,
                csrf_token: window.csrfToken || ''
            };
            document.getElementById('feedbackComunicacao').textContent = 'Salvando...';
            if (id) {
                // Edição
                fetch(`backend/api/comunicacao.php/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        document.getElementById('feedbackComunicacao').textContent = 'Comunicação atualizada com sucesso!';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        document.getElementById('feedbackComunicacao').textContent = resp.message || 'Erro ao atualizar comunicação.';
                    }
                })
                .catch(() => {
                    document.getElementById('feedbackComunicacao').textContent = 'Erro ao atualizar comunicação.';
                });
            } else {
                // Criação
                fetch('backend/api/comunicacao.php/create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        document.getElementById('feedbackComunicacao').textContent = 'Comunicação criada com sucesso!';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        document.getElementById('feedbackComunicacao').textContent = resp.message || 'Erro ao criar comunicação.';
                    }
                })
                .catch(() => {
                    document.getElementById('feedbackComunicacao').textContent = 'Erro ao criar comunicação.';
                });
            }
        }
    </script>
</body>
</html> 