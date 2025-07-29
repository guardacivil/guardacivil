<?php
// graduacoes.php - Gestão de Graduações da GCM
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Buscar graduações
try {
    $sql = "SELECT * FROM graduacoes ORDER BY nivel DESC";
    $stmt = $pdo->query($sql);
    $graduacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar graduações: " . $e->getMessage());
    $graduacoes = [];
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Graduações - Sistema Integrado da Guarda Civil</title>
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
        /* Estilos para menu agrupado */
        .menu-group {
            margin-bottom: 0.5rem;
        }
        .menu-header {
            background-color: #1e3a8a !important;
            font-weight: 600;
            cursor: pointer;
            position: relative;
        }
        .menu-header:hover {
            background-color: #1e40af !important;
        }
        .menu-header::after {
            content: '\f107';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            transition: transform 0.3s ease;
        }
        .menu-header.active::after {
            transform: rotate(180deg);
        }
        .submenu {
            display: none;
            margin-left: 1rem;
            margin-top: 0.25rem;
        }
        .submenu.active {
            display: block;
        }
        .submenu a {
            padding: 0.375rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .submenu a:hover {
            background-color: rgba(255, 255, 255, 0.2);
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
            <a href="graduacoes.php" class="active">Graduações</a>
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
            <h2 class="text-3xl font-bold text-gray-800"><i class="fas fa-graduation-cap mr-2"></i>Graduações da GCM</h2>
            <div class="text-gray-600 text-sm">
                Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
                (<?= htmlspecialchars($currentUser['perfil']) ?>)
            </div>
        </header>

        <!-- Botões de ação -->
        <div class="mb-6 flex gap-4">
            <button onclick="abrirModalCriar()" class="btn-primary text-white px-4 py-2 rounded-lg flex items-center gap-2"><i class="fas fa-plus"></i> Nova Graduação</button>
        </div>

        <!-- Lista de Graduações -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-800">Hierarquia da Corporação</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nível</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduação</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($graduacoes as $graduacao): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?= $graduacao['nivel'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($graduacao['nome']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?= htmlspecialchars($graduacao['descricao'] ?? '') ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="editarGraduacao(<?= $graduacao['id'] ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="excluirGraduacao(<?= $graduacao['id'] ?>)" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de Nova Graduação -->
    <div id="modalGraduacao" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center justify-center mb-4"><i class="fas fa-graduation-cap text-3xl text-blue-600 mr-2"></i> Nova Graduação</h3>
          </div>
          <form id="formGraduacao" class="p-6">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Graduação</label>
              <select id="graduacao_padrao" name="graduacao_padrao" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Selecione...</option>
                <option value="Comandante">Comandante</option>
                <option value="Subcomandante">Subcomandante</option>
                <option value="Inspetor">Inspetor</option>
                <option value="Subinspetor">Subinspetor</option>
                <option value="Classe Distinta">Classe Distinta</option>
                <option value="Classe Especial">Classe Especial</option>
                <option value="Primeira Classe">Primeira Classe</option>
                <option value="Segunda Classe">Segunda Classe</option>
                <option value="Terceira Classe">Terceira Classe</option>
                <option value="Guarda Civil">Guarda Civil</option>
                <option value="Aluno Guarda">Aluno Guarda</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
              <input type="text" id="descricao_graduacao" name="descricao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <input type="hidden" id="nivel_graduacao" name="nivel">
            <div class="mt-6 flex justify-end gap-3">
              <button type="button" onclick="fecharModalGraduacao()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"><i class="fas fa-times mr-1"></i>Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"><i class="fas fa-save mr-1"></i>Salvar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script>
        function abrirModalCriar() {
            document.getElementById('modalGraduacaoTitle').textContent = 'Nova Graduação';
            document.getElementById('formGraduacao').reset();
            document.getElementById('modalGraduacao').classList.remove('hidden');
        }
        
        function editarGraduacao(id) {
            fetch(`backend/api/graduacoes.php/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const g = data.data;
                        document.getElementById('modalGraduacaoTitle').textContent = 'Editar Graduação';
                        document.getElementById('graduacao_padrao').value = g.nome;
                        document.getElementById('descricao_graduacao').value = g.descricao || '';
                        document.getElementById('nivel_graduacao').value = g.nivel;
                        document.getElementById('modalGraduacao').classList.remove('hidden');
                        document.getElementById('formGraduacao').setAttribute('data-id', g.id);
                    } else {
                        alert('Erro ao buscar graduação: ' + data.message);
                    }
                })
                .catch(() => alert('Erro ao buscar dados da graduação.'));
        }
        
        function excluirGraduacao(id) {
            if (confirm('Tem certeza que deseja excluir esta graduação?')) {
                fetch(`backend/api/graduacoes.php/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: window.csrfToken || '' })
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        alert('Graduação excluída com sucesso!');
                        location.reload();
                    } else {
                        alert(resp.message || 'Erro ao excluir graduação.');
                    }
                })
                .catch(() => alert('Erro ao excluir graduação.'));
            }
        }

        function fecharModalGraduacao() {
            document.getElementById('modalGraduacao').classList.add('hidden');
        }

        const graduacoesPadrao = [
          {nome: 'Comandante', nivel: 11},
          {nome: 'Subcomandante', nivel: 10},
          {nome: 'Inspetor', nivel: 9},
          {nome: 'Subinspetor', nivel: 8},
          {nome: 'Classe Distinta', nivel: 7},
          {nome: 'Classe Especial', nivel: 6},
          {nome: 'Primeira Classe', nivel: 5},
          {nome: 'Segunda Classe', nivel: 4},
          {nome: 'Terceira Classe', nivel: 3},
          {nome: 'Guarda Civil', nivel: 2},
          {nome: 'Aluno Guarda', nivel: 1}
        ];

        document.getElementById('graduacao_padrao').addEventListener('change', function() {
          const selected = graduacoesPadrao.find(g => g.nome === this.value);
          document.getElementById('nivel_graduacao').value = selected ? selected.nivel : '';
        });

        // Submissão do formulário de graduação (criação/edição)
        document.getElementById('formGraduacao').onsubmit = function(e) {
            e.preventDefault();
            const id = e.target.getAttribute('data-id');
            const data = {
                nome: document.getElementById('graduacao_padrao').value,
                descricao: document.getElementById('descricao_graduacao').value,
                nivel: document.getElementById('nivel_graduacao').value,
                csrf_token: window.csrfToken || ''
            };
            if (!data.nome) {
                alert('Selecione uma graduação padrão.');
                return;
            }
            if (id) {
                // Edição
                fetch(`backend/api/graduacoes.php/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        alert('Graduação atualizada com sucesso!');
                        location.reload();
                    } else {
                        alert(resp.message || 'Erro ao atualizar graduação.');
                    }
                })
                .catch(() => alert('Erro ao atualizar graduação.'));
            } else {
                // Criação
                fetch('backend/api/graduacoes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        alert('Graduação criada com sucesso!');
                        location.reload();
                    } else {
                        alert(resp.message || 'Erro ao criar graduação.');
                    }
                })
                .catch(() => alert('Erro ao criar graduação.'));
            }
        }
        
        // Funcionalidade do menu agrupado
        document.addEventListener('DOMContentLoaded', function() {
            const menuHeaders = document.querySelectorAll('.menu-header');
            
            menuHeaders.forEach(header => {
                header.addEventListener('click', function(e) {
                    e.preventDefault();
                    const submenu = this.nextElementSibling;
                    const isActive = submenu.classList.contains('active');
                    
                    // Fechar todos os outros submenus
                    document.querySelectorAll('.submenu').forEach(menu => {
                        menu.classList.remove('active');
                    });
                    document.querySelectorAll('.menu-header').forEach(h => {
                        h.classList.remove('active');
                    });
                    
                    // Toggle do submenu atual
                    if (!isActive) {
                        submenu.classList.add('active');
                        this.classList.add('active');
                    }
                });
            });
            
            // Abrir automaticamente o menu Ocorrências se estiver em uma página relacionada
            const currentPage = window.location.pathname.split('/').pop();
            const ocorrenciasPages = ['ROGCM.php', 'minhas_ocorrencias.php'];
            
            if (ocorrenciasPages.includes(currentPage)) {
                const ocorrenciasMenus = document.querySelectorAll('.menu-header');
                ocorrenciasMenus.forEach((menu, index) => {
                    const submenu = menu.nextElementSibling;
                    if (submenu && submenu.classList.contains('submenu')) {
                        const submenuLinks = submenu.querySelectorAll('a');
                        const hasOcorrenciasLink = Array.from(submenuLinks).some(link => 
                            ocorrenciasPages.includes(link.getAttribute('href'))
                        );
                        if (hasOcorrenciasLink) {
                            submenu.classList.add('active');
                            menu.classList.add('active');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html> 