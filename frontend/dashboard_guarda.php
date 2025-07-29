<?php
// dashboard_guarda.php - Dashboard específico para Guarda Civil
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se o usuário é Guarda Civil
$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['perfil'] !== 'Guarda Civil') {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Buscar dados específicos do guarda
try {
    // Total de ocorrências registradas pelo guarda
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM ocorrencias WHERE usuario_id = ?');
    $stmt->execute([$currentUser['id']]);
    $totalOcorrencias = $stmt->fetch()['total'] ?? 0;

    // Ocorrências do dia
    $stmt = $pdo->prepare('SELECT COUNT(*) as total FROM ocorrencias WHERE usuario_id = ? AND DATE(data) = CURDATE()');
    $stmt->execute([$currentUser['id']]);
    $ocorrenciasHoje = $stmt->fetch()['total'] ?? 0;

    // Últimas ocorrências
    $stmt = $pdo->prepare('SELECT * FROM ocorrencias WHERE usuario_id = ? ORDER BY data DESC LIMIT 5');
    $stmt->execute([$currentUser['id']]);
    $ultimasOcorrencias = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Erro ao buscar dados do dashboard guarda: " . $e->getMessage());
    $totalOcorrencias = 0;
    $ocorrenciasHoje = 0;
    $ultimasOcorrencias = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Guarda Civil - Sistema SMART</title>
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
        /* Sidebar para Guarda Civil */
        aside.sidebar-guarda {
            width: 16rem;
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
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
        aside.sidebar-guarda .logo-container {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        aside.sidebar-guarda .logo-container img {
            width: 6rem;
            margin: 0 auto 0.5rem auto;
            display: block;
        }
        aside.sidebar-guarda .logo-container h1 {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        aside.sidebar-guarda .logo-container p {
            font-size: 0.875rem;
            color: #bfdbfe;
            margin: 0;
        }
        aside.sidebar-guarda nav a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        aside.sidebar-guarda nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        aside.sidebar-guarda nav a.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #fbbf24;
        }
        aside.sidebar-guarda nav a i {
            margin-right: 0.75rem;
            width: 1.25rem;
            text-align: center;
        }
        aside.sidebar-guarda nav a.logout {
            background-color: #dc2626;
            margin-top: 2rem;
        }
        aside.sidebar-guarda nav a.logout:hover {
            background-color: #b91c1c;
        }
        /* Conteúdo principal */
        main.content {
            margin-left: 16rem;
            padding: 2rem;
            width: calc(100% - 16rem);
        }
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card.green {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card.orange {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card.purple {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
    </style>
</head>
<body>

    <!-- Sidebar para Guarda Civil -->
    <aside class="sidebar-guarda">
        <div class="logo-container">
            <img src="img/logo.png" alt="Logo" />
            <h1>Sistema SMART</h1>
            <p>Guarda Civil Municipal</p>
        </div>
        <nav>
            <a href="dashboard_guarda.php">
              <i class="fas fa-home"></i>
              Dashboard
            </a>
            <a href="ROGCM.php">
              <i class="fas fa-file-alt"></i>
              R.O.
            </a>
            <a href="checklist.php">
              <i class="fas fa-clipboard-check"></i>
              CheckList
            </a>
            <a href="parte.php">
              <i class="fas fa-user-tie"></i>
              Parte
            </a>
            <a href="historico_sqlite.php" class="active">
              <i class="fas fa-history"></i>
              Histórico
            </a>
            <a href="suporte.php" id="openSuporteModal">
              <i class="fas fa-question-circle"></i>
              Suporte
            </a>
            <a href="logout.php" class="logout">
              <i class="fas fa-sign-out-alt"></i>
              Sair
            </a>
        </nav>
    </aside>

<!-- Modal de Suporte -->
<div id="suporteModal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
  <div style="background:#fff; border-radius:12px; max-width:500px; width:95vw; padding:2rem; position:relative; box-shadow:0 8px 32px rgba(0,0,0,0.2); min-height:350px;">
    <button id="closeSuporteModal" style="position:absolute; top:12px; right:12px; background:none; border:none; font-size:1.5rem; color:#888; cursor:pointer;">&times;</button>
    <div class="flex mb-4">
      <button id="abaTickets" class="flex-1 px-4 py-2 font-bold border-b-2 border-blue-600 text-blue-700 bg-blue-100">Meus Tickets</button>
      <button id="abaNovo" class="flex-1 px-4 py-2 font-bold border-b-2 border-transparent text-gray-600 bg-gray-100">Abrir Ticket</button>
    </div>
    <div id="painelTickets">
      <div id="ticketsLoading" class="text-center text-gray-500">Carregando tickets...</div>
      <table id="ticketsTable" class="w-full text-sm hidden">
        <thead>
          <tr>
            <th class="p-1 border-b">Assunto</th>
            <th class="p-1 border-b">Mensagem</th>
            <th class="p-1 border-b">Prioridade</th>
            <th class="p-1 border-b">Status</th>
            <th class="p-1 border-b">Resposta</th>
          </tr>
        </thead>
        <tbody id="ticketsBody"></tbody>
      </table>
      <div id="ticketsVazio" class="text-center text-gray-500 mt-4 hidden">Nenhum ticket encontrado.</div>
    </div>
    <div id="painelNovo" style="display:none;">
      <h2 class="text-xl font-bold mb-4">Abrir Ticket de Suporte</h2>
      <form id="suporteForm" method="post" action="abrir_ticket.php">
        <label class="block mb-2 font-medium">Assunto:
          <input type="text" name="assunto" class="w-full border rounded px-2 py-1 mb-4" required>
        </label>
        <label class="block mb-2 font-medium">Mensagem:
          <textarea name="mensagem" class="w-full border rounded px-2 py-1 mb-4" required></textarea>
        </label>
        <label class="block mb-2 font-medium">Prioridade:
          <select name="prioridade" class="w-full border rounded px-2 py-1 mb-4">
            <option value="baixa">Baixa</option>
            <option value="media" selected>Média</option>
            <option value="alta">Alta</option>
            <option value="urgente">Urgente</option>
          </select>
        </label>
        <div class="flex justify-end space-x-2">
          <button type="button" id="cancelSuporteModal" class="px-4 py-2 rounded border">Cancelar</button>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Enviar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  function showAlertaSuporte(msg, success) {
    const alerta = document.createElement('div');
    alerta.textContent = msg;
    alerta.style.position = 'fixed';
    alerta.style.top = '32px';
    alerta.style.right = '32px';
    alerta.style.zIndex = 11000;
    alerta.style.padding = '16px 32px';
    alerta.style.borderRadius = '8px';
    alerta.style.background = success ? '#22c55e' : '#ef4444';
    alerta.style.color = '#fff';
    alerta.style.fontWeight = 'bold';
    alerta.style.boxShadow = '0 4px 16px rgba(0,0,0,0.15)';
    document.body.appendChild(alerta);
    setTimeout(() => { alerta.remove(); }, 4000);
  }
  const openBtn = document.getElementById('openSuporteModal');
  const modal = document.getElementById('suporteModal');
  const closeBtn = document.getElementById('closeSuporteModal');
  const cancelBtn = document.getElementById('cancelSuporteModal');
  const suporteForm = document.getElementById('suporteForm');
  const abaTickets = document.getElementById('abaTickets');
  const abaNovo = document.getElementById('abaNovo');
  const painelTickets = document.getElementById('painelTickets');
  const painelNovo = document.getElementById('painelNovo');
  const ticketsTable = document.getElementById('ticketsTable');
  const ticketsBody = document.getElementById('ticketsBody');
  const ticketsLoading = document.getElementById('ticketsLoading');
  const ticketsVazio = document.getElementById('ticketsVazio');

  if(openBtn && modal && closeBtn && cancelBtn) {
    openBtn.onclick = function(e) {
      e.preventDefault();
      modal.style.display = 'flex';
      ativarAbaTickets();
    };
    closeBtn.onclick = cancelBtn.onclick = function() {
      modal.style.display = 'none';
    };
    window.onclick = function(event) {
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    };
  }
  if (suporteForm) {
    suporteForm.onsubmit = function(e) {
      e.preventDefault();
      const formData = new FormData(suporteForm);
      fetch('abrir_ticket.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        showAlertaSuporte(data.message, data.success);
        if (data.success) {
          modal.style.display = 'none';
          suporteForm.reset();
        }
      })
      .catch(() => {
        showAlertaSuporte('Erro ao enviar ticket.', false);
      });
    };
  }
  function ativarAbaTickets() {
    abaTickets.classList.add('border-blue-600','text-blue-700','bg-blue-100');
    abaNovo.classList.remove('border-blue-600','text-blue-700','bg-blue-100');
    abaNovo.classList.add('border-transparent','text-gray-600','bg-gray-100');
    painelTickets.style.display = '';
    painelNovo.style.display = 'none';
    carregarTickets();
  }
  function ativarAbaNovo() {
    abaNovo.classList.add('border-blue-600','text-blue-700','bg-blue-100');
    abaTickets.classList.remove('border-blue-600','text-blue-700','bg-blue-100');
    abaTickets.classList.add('border-transparent','text-gray-600','bg-gray-100');
    painelNovo.style.display = '';
    painelTickets.style.display = 'none';
  }
  abaTickets.onclick = ativarAbaTickets;
  abaNovo.onclick = ativarAbaNovo;
  function carregarTickets() {
    ticketsLoading.style.display = '';
    ticketsTable.classList.add('hidden');
    ticketsVazio.classList.add('hidden');
    fetch('consultar_tickets.php')
      .then(res => res.json())
      .then(data => {
        ticketsLoading.style.display = 'none';
        if (data.length === 0) {
          ticketsVazio.classList.remove('hidden');
          ticketsTable.classList.add('hidden');
        } else {
          ticketsBody.innerHTML = '';
          data.forEach(ticket => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td class='border p-1'>${ticket.assunto || ticket.titulo}</td>
                            <td class='border p-1'>${ticket.mensagem || ticket.descricao}</td>
                            <td class='border p-1'>${ticket.prioridade}</td>
                            <td class='border p-1'>${ticket.status}</td>
                            <td class='border p-1'>${ticket.resposta ? `<span class='text-green-700'>${ticket.resposta}</span>` : '<span class="text-gray-400">Aguardando resposta</span>'}`;
            ticketsBody.appendChild(tr);
          });
          ticketsTable.classList.remove('hidden');
        }
      })
      .catch(() => {
        ticketsLoading.textContent = 'Erro ao carregar tickets.';
      });
  }
</script>

    <!-- Conteúdo principal -->
    <main class="content">
        <header class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-800">Dashboard Guarda Civil</h2>
                <p class="text-gray-600 mt-1">Bem-vindo ao seu painel de trabalho</p>
            </div>
            <div class="text-right">
                <div class="text-gray-600 text-sm">
                    Olá, <?= htmlspecialchars($currentUser['nome']) ?>
                </div>
                <div class="text-blue-600 font-medium">
                    Guarda Civil Municipal
                </div>
            </div>
        </header>

        <!-- Cards de estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="card stat-card blue p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold">Total de Ocorrências</h3>
                        <p class="text-3xl font-bold mt-2"><?= htmlspecialchars($totalOcorrencias) ?></p>
                    </div>
                    <div class="text-4xl opacity-75">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
            
            <div class="card stat-card green p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold">Ocorrências Hoje</h3>
                        <p class="text-3xl font-bold mt-2"><?= htmlspecialchars($ocorrenciasHoje) ?></p>
                    </div>
                    <div class="text-4xl opacity-75">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
            
            <div class="card stat-card orange p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold">CheckLists</h3>
                        <p class="text-3xl font-bold mt-2">0</p>
                    </div>
                    <div class="text-4xl opacity-75">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="card stat-card purple p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold">Partes</h3>
                        <p class="text-3xl font-bold mt-2">0</p>
                    </div>
                    <div class="text-4xl opacity-75">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações rápidas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="card p-6">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>
                    Ações Rápidas
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <a href="ROGCM.php" class="bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-lg text-center transition-colors">
                        <i class="fas fa-file-alt text-2xl mb-2"></i>
                        <div class="font-semibold">Nova R.O.</div>
                        <div class="text-sm opacity-90">Registrar ocorrência</div>
                    </a>
                    <a href="checklist.php" class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-lg text-center transition-colors">
                        <i class="fas fa-clipboard-check text-2xl mb-2"></i>
                        <div class="font-semibold">CheckList</div>
                        <div class="text-sm opacity-90">Verificar rotina</div>
                    </a>
                    <a href="parte.php" class="bg-purple-500 hover:bg-purple-600 text-white p-4 rounded-lg text-center transition-colors">
                        <i class="fas fa-user-tie text-2xl mb-2"></i>
                        <div class="font-semibold">Parte</div>
                        <div class="text-sm opacity-90">Gerenciar partes</div>
                    </a>
                    <a href="historico_sqlite.php" class="bg-gray-500 hover:bg-gray-600 text-white p-4 rounded-lg text-center transition-colors">
                        <i class="fas fa-history text-2xl mb-2"></i>
                        <div class="font-semibold">Histórico</div>
                        <div class="text-sm opacity-90">Ver registros</div>
                    </a>
                </div>
            </div>

            <!-- Últimas ocorrências -->
            <div class="card p-6">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-clock text-blue-500 mr-2"></i>
                    Últimas Ocorrências
                </h3>
                <?php if (empty($ultimasOcorrencias)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4"></i>
                        <p>Nenhuma ocorrência registrada ainda</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($ultimasOcorrencias as $ocorrencia): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <div class="font-medium text-gray-800">
                                        R.O. #<?= htmlspecialchars($ocorrencia['id']) ?>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <?= date('d/m/Y H:i', strtotime($ocorrencia['data'])) ?>
                                    </div>
                                </div>
                                <a href="ver_ocorrencia.php?id=<?= $ocorrencia['id'] ?>" 
                                   class="text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informações do sistema -->
        <div class="card p-6">
            <h3 class="text-xl font-semibold mb-4 text-gray-800">
                <i class="fas fa-info-circle text-green-500 mr-2"></i>
                Informações do Sistema
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center">
                    <i class="fas fa-calendar text-blue-500 mr-2"></i>
                    <span>Data: <?= date('d/m/Y') ?></span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-clock text-green-500 mr-2"></i>
                    <span>Hora: <?= date('H:i:s') ?></span>
                </div>
                <div class="flex items-center">
                    <i class="fas fa-user-shield text-purple-500 mr-2"></i>
                    <span>Status: Ativo</span>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Atualizar hora em tempo real
        setInterval(function() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('pt-BR');
            document.querySelector('.text-green-500 + span').textContent = 'Hora: ' + timeString;
        }, 1000);

        // Adicionar efeitos de hover nos cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html> 