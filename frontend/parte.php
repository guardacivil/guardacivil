<?php
// parte.php - Gerenciamento de Partes para Guarda Civil
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar se o usuário é Guarda Civil
$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';

// Buscar partes registradas
try {
    if ($perfil === 'Guarda Civil') {
        $stmt = $pdo->prepare('SELECT * FROM partes WHERE usuario_id = ? ORDER BY data_criacao DESC LIMIT 20');
        $stmt->execute([$currentUser['id']]);
        $partes = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare('SELECT * FROM partes ORDER BY data_criacao DESC LIMIT 20');
        $stmt->execute();
        $partes = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar partes: " . $e->getMessage());
    $partes = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Partes - Sistema Integrado da Guarda Civil</title>
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
            width: 10.14rem;
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
        .parte-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid #3b82f6;
        }
        .parte-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .parte-card.vitima {
            border-left-color: #10b981;
        }
        .parte-card.autor {
            border-left-color: #ef4444;
        }
        .parte-card.testemunha {
            border-left-color: #f59e0b;
        }
    </style>
</head>
<body>

    <!-- Sidebar para Guarda Civil -->
    <aside class="sidebar-guarda">
        <div class="logo-container">
            <img src="img/logo1.png" alt="Logo" />
            <h1>Sistema Integrado da Guarda Civil</h1>
            <p>Guarda Civil Municipal</p>
        </div>
        <nav>
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="ROGCM.php">
                <i class="fas fa-file-alt"></i>
                R.O.
            </a>
            <a href="parte.php" class="active">
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
                <h2 class="text-3xl font-bold text-gray-800">Gerenciamento de Partes</h2>
                <p class="text-gray-600 mt-1">Registro e consulta de partes envolvidas</p>
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
                Parte registrada com sucesso!
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Formulário de registro -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-user-plus text-blue-500 mr-2"></i>
                    Nova Parte
                </h3>
                
                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tipo de Parte *
                            </label>
                            <select name="tipo_parte" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione o tipo</option>
                                <option value="vitima">Vítima</option>
                                <option value="autor">Autor</option>
                                <option value="testemunha">Testemunha</option>
                                <option value="denunciante">Denunciante</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Nome Completo *
                            </label>
                            <input type="text" name="nome" required 
                                   placeholder="Nome completo"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Documento (RG/CPF) *
                            </label>
                            <input type="text" name="documento" required 
                                   placeholder="000.000.000-00"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Data de Nascimento
                            </label>
                            <input type="date" name="data_nascimento" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Sexo
                            </label>
                            <select name="sexo" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione</option>
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Estado Civil
                            </label>
                            <select name="estado_civil" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione</option>
                                <option value="solteiro">Solteiro(a)</option>
                                <option value="casado">Casado(a)</option>
                                <option value="divorciado">Divorciado(a)</option>
                                <option value="viuvo">Viúvo(a)</option>
                                <option value="separado">Separado(a)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Endereço
                        </label>
                        <input type="text" name="endereco" 
                               placeholder="Endereço completo"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Telefone
                            </label>
                            <input type="tel" name="telefone" 
                                   placeholder="(11) 99999-9999"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Profissão
                            </label>
                            <input type="text" name="profissao" 
                                   placeholder="Profissão"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Observações
                        </label>
                        <textarea name="observacoes" rows="3" 
                                  placeholder="Observações adicionais sobre a parte..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="reset" 
                                class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                            <i class="fas fa-undo mr-2"></i>
                            Limpar
                        </button>
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Registrar Parte
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de partes -->
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-list text-green-500 mr-2"></i>
                    Partes Registradas
                </h3>
                
                <?php if (empty($partes)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p>Nenhuma parte registrada ainda</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php foreach ($partes as $parte): ?>
                            <div class="parte-card <?= $parte['tipo_parte'] ?>">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-2">
                                            <span class="font-semibold text-gray-800">
                                                <?= htmlspecialchars($parte['nome']) ?>
                                            </span>
                                            <span class="ml-2 px-2 py-1 text-xs rounded-full 
                                                       <?= $parte['tipo_parte'] === 'vitima' ? 'bg-green-100 text-green-800' : 
                                                          ($parte['tipo_parte'] === 'autor' ? 'bg-red-100 text-red-800' : 
                                                           'bg-yellow-100 text-yellow-800') ?>">
                                                <?= ucfirst($parte['tipo_parte']) ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <div><strong>Documento:</strong> <?= htmlspecialchars($parte['documento']) ?></div>
                                            <?php if ($parte['telefone']): ?>
                                                <div><strong>Telefone:</strong> <?= htmlspecialchars($parte['telefone']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($parte['endereco']): ?>
                                                <div><strong>Endereço:</strong> <?= htmlspecialchars($parte['endereco']) ?></div>
                                            <?php endif; ?>
                                            <div class="text-xs text-gray-500">
                                                Registrado em: <?= date('d/m/Y H:i', strtotime($parte['data_criacao'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="editarParte(<?= $parte['id'] ?>)" 
                                                class="text-blue-500 hover:text-blue-700"><i class="fas fa-edit"></i></button>
                                        <button onclick="visualizarParte(<?= $parte['id'] ?>)" 
                                                class="text-green-500 hover:text-green-700"><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Botão voltar -->
        <div class="mt-8">
            <a href="dashboard.php" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar ao Dashboard
            </a>
        </div>
    </main>

    <script>
        // Máscara para documento
        document.querySelector('input[name="documento"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            }
            e.target.value = value;
        });

        // Máscara para telefone
        document.querySelector('input[name="telefone"]').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            }
            e.target.value = value;
        });

        // Funções para editar e visualizar partes
        function editarParte(id) {
            alert('Funcionalidade de edição será implementada em breve.');
        }

        function visualizarParte(id) {
            alert('Funcionalidade de visualização será implementada em breve.');
        }

        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#ef4444';
                } else {
                    field.style.borderColor = '#d1d5db';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
            }
        });
    </script>
</body>
</html> 