<?php
// checklist.php - CheckList para Guarda Civil
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se o usuário é Guarda Civil
$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';

// Buscar checklists
try {
    if ($perfil === 'Guarda Civil') {
        $stmt = $pdo->prepare('SELECT * FROM checklists WHERE usuario_id = ? ORDER BY data DESC');
        $stmt->execute([$currentUser['id']]);
        $checklists = $stmt->fetchAll();
    } elseif (in_array($perfil, ['Comando', 'Secretário', 'Admin', 'Administrador'])) {
        $stmt = $pdo->query('SELECT * FROM checklists ORDER BY data DESC');
        $checklists = $stmt->fetchAll();
    } else {
        $checklists = [];
    }
} catch (PDOException $e) {
    $checklists = [];
}


// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO checklists (usuario_id, data, turno, local, observacoes, status) 
            VALUES (?, datetime('now'), ?, ?, ?, 'concluido')
        ");
        $stmt->execute([
            $currentUser['id'],
            $_POST['turno'],
            $_POST['local'],
            $_POST['observacoes']
        ]);
        
        $checklist_id = $pdo->lastInsertId();
        
        // Salvar itens do checklist
        foreach ($_POST['itens'] as $item_id => $status) {
            $stmt = $pdo->prepare("
                INSERT INTO checklist_itens (checklist_id, item_id, status, observacao) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $checklist_id,
                $item_id,
                $status,
                $_POST['observacoes_itens'][$item_id] ?? ''
            ]);
        }
        
        logAction('checklist_criado', 'checklists', $checklist_id);
        
        header('Location: checklist.php?success=1');
        exit;
        
    } catch (PDOException $e) {
        error_log("Erro ao salvar checklist: " . $e->getMessage());
        $error = "Erro ao salvar checklist: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CheckList - Sistema Integrado da Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2563eb 0%, #000 100%);
            min-height: 100vh;
            margin: 0;
        }
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
            max-width: 64px;
            max-height: 64px;
            width: auto;
            height: auto;
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
        main.content {
            margin-left: 16rem;
            padding: 2rem;
            width: calc(100% - 16rem);
        }
        .checklist-item {
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        .checklist-item:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
        }
        .checklist-item.checked {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        .checklist-item.unchecked {
            border-color: #ef4444;
            background-color: #fef2f2;
        }
    </style>
</head>
<body>

<?php if ($perfil === 'Guarda Civil'): ?>
    <!-- Sidebar para Guarda Civil -->
    <aside class="sidebar-guarda">
        <div class="logo-container">
            <img src="img/logo1.png" alt="Logo" />
            <h1>Sistema Integrado da Guarda Civil</h1>
            <p>Guarda Civil Municipal</p>
        </div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="ROGCM.php">
                <i class="fas fa-file-alt"></i>
                R.O.
            </a>
            <a href="checklist.php" class="active">
                <i class="fas fa-clipboard-check"></i>
                CheckList
            </a>
            <a href="parte.php">
                <i class="fas fa-user-tie"></i>
                Parte
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
<?php else: ?>
    <!-- Sidebar padrão para admin, comando, secretário -->
    <?php include 'sidebar.php'; ?>
<?php endif; ?>

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
                <h2 class="text-3xl font-bold text-gray-800">CheckList de Rotina</h2>
                <p class="text-gray-600 mt-1">Verificação diária de equipamentos e procedimentos</p>
            </div>
            <div class="text-right">
                <div class="text-gray-600 text-sm">
                    Guarda: <?= htmlspecialchars($currentUser['nome']) ?>
                </div>
                <div class="text-blue-600 font-medium">
                    <?= date('d/m/Y H:i') ?>
                </div>
            </div>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                CheckList salvo com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (in_array($perfil, ['Comando', 'Secretário', 'Admin', 'Administrador'])): ?>
    <div class="bg-white rounded-xl p-6 shadow-md mb-8">
        <h3 class="text-xl font-semibold mb-4 text-gray-800">
            <i class="fas fa-list-alt text-blue-500 mr-2"></i>
            Todos os Checklists Realizados
        </h3>
        <?php if (empty($checklists)): ?>
            <div class="text-gray-500">Nenhum checklist encontrado.</div>
        <?php else: ?>
        <div class="overflow-x-auto">
        <table class="min-w-full text-sm border">
            <thead>
                <tr class="bg-blue-100">
                    <th class="p-2 border">Data</th>
                    <th class="p-2 border">Turno</th>
                    <th class="p-2 border">VTR</th>
                    <th class="p-2 border">Motorista</th>
                    <th class="p-2 border">Encarregado</th>
                    <th class="p-2 border">Local</th>
                    <th class="p-2 border">Usuário</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checklists as $c): ?>
                <tr>
                    <td class="p-2 border"><?= htmlspecialchars($c['data']) ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($c['turno'] ?? '') ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($c['vtr'] ?? '') ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($c['motorista'] ?? '') ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($c['encarregado'] ?? '') ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($c['local'] ?? '') ?></td>
                    <td class="p-2 border"><?php
                        // Buscar nome do usuário
                        $uid = $c['usuario_id'] ?? null;
                        if ($uid) {
                            $stmtU = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?');
                            $stmtU->execute([$uid]);
                            $u = $stmtU->fetch();
                            echo htmlspecialchars($u['nome'] ?? '');
                        }
                    ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!in_array($perfil, ['Comando', 'Secretário', 'Admin', 'Administrador'])): ?>
        <form method="POST" class="space-y-6">
            <!-- Informações básicas -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Informações do CheckList
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Turno
                        </label>
                        <select name="turno" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione o turno</option>
                            <option value="diurno">Diurno (06:00 às 18:00)</option>
                            <option value="noturno">Noturno (18:00 às 06:00)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            VTR
                        </label>
                        <select name="vtr" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione a VTR</option>
                            <option value="009">009</option>
                            <option value="008">008</option>
                            <option value="201">201</option>
                            <option value="003 moto">003 moto</option>
                            <option value="004 moto">004 moto</option>
                            <option value="005 moto">005 moto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Motorista
                        </label>
                        <input type="text" name="motorista" placeholder="Nome do motorista" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Encarregado
                        </label>
                        <input type="text" name="encarregado" placeholder="Nome do encarregado" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Local/Setor
                        </label>
                        <input type="text" name="local" required 
                               placeholder="Ex: Centro, Bairro X, Praça Y"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Itens do CheckList -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-clipboard-list text-green-500 mr-2"></i>
                    Itens de Verificação
                </h3>
                
                <!-- Equipamentos -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-700 mb-3">Equipamentos</h4>
                    <div class="space-y-3">
                        <div class="checklist-item">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="mr-2 font-semibold">Estado:</span>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[1]" value="bom" class="mr-1 text-green-600" required>
                                        <span class="text-green-700">Bom</span>
                                    </label>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[1]" value="danificado" class="mr-1 text-red-600">
                                        <span class="text-red-600">Danificado</span>
                                    </label>
                                    <span class="font-medium ml-2">Rádio comunicador funcionando</span>
                                </div>
                                <span class="text-sm text-gray-500">Item 1</span>
                            </div>
                            <textarea name="observacoes_itens[1]" placeholder="Observações (opcional)" 
                                      class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"></textarea>
                        </div>

                        <div class="checklist-item">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="mr-2 font-semibold">Estado:</span>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[2]" value="bom" class="mr-1 text-green-600" required>
                                        <span class="text-green-700">Bom</span>
                                    </label>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[2]" value="danificado" class="mr-1 text-red-600">
                                        <span class="text-red-600">Danificado</span>
                                    </label>
                                    <span class="font-medium ml-2">Colete balístico em bom estado</span>
                                </div>
                                <span class="text-sm text-gray-500">Item 2</span>
                            </div>
                            <textarea name="observacoes_itens[2]" placeholder="Observações (opcional)" 
                                      class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"></textarea>
                        </div>

                        <div class="checklist-item">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="mr-2 font-semibold">Estado:</span>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[3]" value="bom" class="mr-1 text-green-600" required>
                                        <span class="text-green-700">Bom</span>
                                    </label>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[3]" value="danificado" class="mr-1 text-red-600">
                                        <span class="text-red-600">Danificado</span>
                                    </label>
                                    <span class="font-medium ml-2">Arma de fogo carregada e segura</span>
                                </div>
                                <span class="text-sm text-gray-500">Item 3</span>
                            </div>
                            <textarea name="observacoes_itens[3]" placeholder="Observações (opcional)" 
                                      class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"></textarea>
                        </div>

                        <div class="checklist-item">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="mr-2 font-semibold">Estado:</span>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[4]" value="bom" class="mr-1 text-green-600" required>
                                        <span class="text-green-700">Bom</span>
                                    </label>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[4]" value="danificado" class="mr-1 text-red-600">
                                        <span class="text-red-600">Danificado</span>
                                    </label>
                                    <span class="font-medium ml-2">Algemas disponíveis</span>
                                </div>
                                <span class="text-sm text-gray-500">Item 4</span>
                            </div>
                            <textarea name="observacoes_itens[4]" placeholder="Observações (opcional)" 
                                      class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Documentação -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-700 mb-3">Documentação</h4>
                    <div class="space-y-3">
                        <div class="checklist-item">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="mr-2 font-semibold">Estado:</span>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[5]" value="bom" class="mr-1 text-green-600" required>
                                        <span class="text-green-700">Bom</span>
                                    </label>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[5]" value="danificado" class="mr-1 text-red-600">
                                        <span class="text-red-600">Danificado</span>
                                    </label>
                                    <span class="font-medium ml-2">Carteira funcional atualizada</span>
                                </div>
                                <span class="text-sm text-gray-500">Item 5</span>
                            </div>
                            <textarea name="observacoes_itens[5]" placeholder="Observações (opcional)" 
                                      class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"></textarea>
                        </div>

                        <div class="checklist-item">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="mr-2 font-semibold">Estado:</span>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[6]" value="bom" class="mr-1 text-green-600" required>
                                        <span class="text-green-700">Bom</span>
                                    </label>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[6]" value="danificado" class="mr-1 text-red-600">
                                        <span class="text-red-600">Danificado</span>
                                    </label>
                                    <span class="font-medium ml-2">Formulários de R.O. disponíveis</span>
                                </div>
                                <span class="text-sm text-gray-500">Item 6</span>
                            </div>
                            <textarea name="observacoes_itens[6]" placeholder="Observações (opcional)" 
                                      class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Veículo -->
                <div class="mb-6">
                    <h4 class="text-lg font-medium text-gray-700 mb-3">Veículo (se aplicável)</h4>
                    <div class="space-y-3">
                        <div class="checklist-item">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="mr-2 font-semibold">Estado:</span>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[7]" value="bom" class="mr-1 text-green-600" required>
                                        <span class="text-green-700">Bom</span>
                                    </label>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[7]" value="danificado" class="mr-1 text-red-600">
                                        <span class="text-red-600">Danificado</span>
                                    </label>
                                    <span class="font-medium ml-2">Veículo em condições de uso</span>
                                </div>
                                <span class="text-sm text-gray-500">Item 7</span>
                            </div>
                            <textarea name="observacoes_itens[7]" placeholder="Observações (opcional)" 
                                      class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"></textarea>
                        </div>

                        <div class="checklist-item">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <span class="mr-2 font-semibold">Estado:</span>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[8]" value="bom" class="mr-1 text-green-600" required>
                                        <span class="text-green-700">Bom</span>
                                    </label>
                                    <label class="mr-3 flex items-center">
                                        <input type="radio" name="itens[8]" value="danificado" class="mr-1 text-red-600">
                                        <span class="text-red-600">Danificado</span>
                                    </label>
                                    <span class="font-medium ml-2">Combustível suficiente</span>
                                </div>
                                <span class="text-sm text-gray-500">Item 8</span>
                            </div>
                            <textarea name="observacoes_itens[8]" placeholder="Observações (opcional)" 
                                      class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-md text-sm"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Observações gerais -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-comment text-purple-500 mr-2"></i>
                    Observações Gerais
                </h3>
                <textarea name="observacoes" rows="4" 
                          placeholder="Observações adicionais sobre o checklist..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>

            <!-- Botões de ação -->
            <div class="flex justify-end space-x-4">
                <a href="dashboard.php" 
                   class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Voltar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors">
                    <i class="fas fa-save mr-2"></i>
                    Salvar CheckList
                </button>
            </div>
        </form>
<?php endif; ?>
    </main>

    <script>
        // Adicionar efeitos visuais aos itens do checklist
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const item = this.closest('.checklist-item');
                const checkedRadio = item.querySelector('input[type="radio"]:checked');
                
                // Remover classes anteriores
                item.classList.remove('checked', 'unchecked');
                
                // Adicionar classe baseada no valor
                if (checkedRadio.value === 'bom') {
                    item.classList.add('checked');
                } else if (checkedRadio.value === 'danificado') {
                    item.classList.add('unchecked');
                }
            });
        });

        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredRadios = document.querySelectorAll('input[type="radio"][required]');
            let isValid = true;
            
            requiredRadios.forEach(radio => {
                const name = radio.name;
                const checked = document.querySelector(`input[name="${name}"]:checked`);
                if (!checked) {
                    isValid = false;
                    const item = radio.closest('.checklist-item');
                    item.style.borderColor = '#ef4444';
                    item.style.backgroundColor = '#fef2f2';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, verifique todos os itens obrigatórios.');
            }
        });
    </script>
</body>
</html> 