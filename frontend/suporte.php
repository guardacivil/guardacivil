<?php
require_once 'conexao.php';
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar permissão (admin tem acesso total)
$currentUser = getCurrentUser();
if (!hasPermission('suporte') && !isAdminLoggedIn() && !(isset($currentUser['perfil']) && $currentUser['perfil'] === 'Administrador')) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// --- NOVO FLUXO SUPORTE ---
$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';
$msg = '';

// Salvar resposta do admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder_ticket'])) {
    $id = intval($_POST['ticket_id']);
    $resposta = trim($_POST['resposta']);
    if ($id && $resposta) {
        $stmt = $pdo->prepare('UPDATE suporte SET resposta = ?, status = "fechado" WHERE id = ?');
        $stmt->execute([$resposta, $id]);
        // Buscar dados do ticket e e-mail do usuário
        $stmt = $pdo->prepare('SELECT s.*, u.email, u.nome FROM suporte s LEFT JOIN usuarios u ON s.usuario_id = u.id WHERE s.id = ?');
        $stmt->execute([$id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($ticket && !empty($ticket['email'])) {
            $to = $ticket['email'];
            $subject = "Resposta ao seu ticket de suporte: " . ($ticket['titulo'] ?? $ticket['assunto'] ?? '');
            $body = "<b>Assunto:</b> " . htmlspecialchars($ticket['titulo'] ?? $ticket['assunto'] ?? '') . "<br>"
                  . "<b>Mensagem original:</b> " . nl2br(htmlspecialchars($ticket['mensagem'] ?? $ticket['descricao'] ?? '')) . "<br>"
                  . "<b>Resposta do admin:</b> " . nl2br(htmlspecialchars($resposta)) . "<br>"
                  . "<b>Status:</b> Fechado";
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: sistema@seudominio.com\r\n";
            @mail($to, $subject, $body, $headers);
        }
        $msg = 'Resposta enviada com sucesso!';
    }
}

// Salvar novo ticket (para todos os usuários)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $prioridade = trim($_POST['prioridade'] ?? 'media');
    if ($titulo && $descricao) {
        $stmt = $pdo->prepare('INSERT INTO suporte (usuario_id, titulo, mensagem, prioridade, status) VALUES (?, ?, ?, ?, "aberto")');
        $stmt->execute([$currentUser['id'], $titulo, $descricao, $prioridade]);
        $msg = 'Ticket criado com sucesso!';
    }
}

// Buscar tickets
if (isset($currentUser['perfil']) && $currentUser['perfil'] === 'Administrador') {
    $stmt = $pdo->query('SELECT s.*, u.nome as usuario_nome FROM suporte s LEFT JOIN usuarios u ON s.usuario_id = u.id ORDER BY s.id DESC');
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare('SELECT s.*, u.nome as usuario_nome FROM suporte s LEFT JOIN usuarios u ON s.usuario_id = u.id WHERE s.usuario_id = ? ORDER BY s.id DESC');
    $stmt->execute([$currentUser['id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Suporte - Sistema Integrado da Guarda Civil</title>
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
    </style>
</head>
<body>

  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Conteúdo principal -->
  <main class="content">
    <header class="flex justify-between items-center mb-8">
      <h2 class="text-3xl font-bold">Suporte</h2>
      <div class="text-gray-600 text-sm">
        Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
        (<?= htmlspecialchars($currentUser['perfil']) ?>)
      </div>
    </header>

    <!-- Botão abrir modal novo ticket -->
    <button onclick="document.getElementById('newTicketModal').classList.remove('hidden')" 
            class="mb-6 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"><i class="fas fa-plus mr-2"></i>Novo Ticket</button>

    <!-- Tabela tickets -->
    <div class="bg-white rounded-lg shadow-md p-6">
      <h3 class="text-xl font-semibold mb-4"><i class="fas fa-headset mr-2"></i>Tickets de Suporte</h3>
      <?php if ($msg): ?>
        <div class="mb-4 px-4 py-2 bg-green-200 text-green-800 rounded"> <?= htmlspecialchars($msg) ?> </div>
      <?php endif; ?>
      <table class="min-w-full bg-white rounded border">
        <thead class="bg-gray-100 text-left">
          <tr>
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Assunto</th>
            <th class="p-2 border">Usuário</th>
            <th class="p-2 border">Prioridade</th>
            <th class="p-2 border">Status</th>
            <th class="p-2 border">Mensagem</th>
            <th class="p-2 border">Resposta</th>
            <th class="p-2 border">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($tickets) === 0): ?>
            <tr><td colspan="8" class="text-center py-4 text-gray-500">Nenhum ticket encontrado.</td></tr>
          <?php else: ?>
            <?php foreach ($tickets as $ticket): ?>
              <tr class="border-t hover:bg-gray-50">
                <td class="p-2 border"> <?= $ticket['id'] ?> </td>
                <td class="p-2 border"> <?= htmlspecialchars($ticket['titulo'] ?? $ticket['assunto'] ?? '') ?> </td>
                <td class="p-2 border"> <?= htmlspecialchars($ticket['usuario_nome'] ?? 'N/A') ?> </td>
                <td class="p-2 border"> <?= ucfirst($ticket['prioridade'] ?? 'media') ?> </td>
                <td class="p-2 border"> <?= ucfirst($ticket['status'] ?? 'aberto') ?> </td>
                <td class="p-2 border"> <?= nl2br(htmlspecialchars($ticket['mensagem'] ?? $ticket['descricao'] ?? '')) ?> </td>
                <td class="p-2 border">
                  <?php if (!empty($ticket['resposta'])): ?>
                    <span class="text-green-700"> <?= nl2br(htmlspecialchars($ticket['resposta'])) ?> </span>
                  <?php elseif (isset($currentUser['perfil']) && $currentUser['perfil'] === 'Administrador' && $ticket['status'] !== 'fechado'): ?>
                    <form method="post" style="min-width:180px;">
                      <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                      <textarea name="resposta" class="border rounded px-2 py-1 w-full mb-2" required placeholder="Digite a resposta..."></textarea>
                      <button type="submit" name="responder_ticket" class="bg-blue-600 text-white px-3 py-1 rounded">Responder</button>
                    </form>
                  <?php else: ?>
                    <span class="text-gray-400">Aguardando resposta</span>
                  <?php endif; ?>
                </td>
                <td class="p-2 border">
                  <!-- Futuras ações -->
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Modal Novo Ticket -->
    <div id="newTicketModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded shadow-lg w-96">
        <h3 class="text-xl font-bold mb-4 flex items-center justify-center"><i class="fas fa-headset text-4xl text-blue-600 mr-2"></i> Novo Ticket de Suporte</h3>
        <form method="POST">
          <label class="block mb-2 font-medium">Título</label>
          <input type="text" name="titulo" required class="w-full mb-4 border px-3 py-2 rounded" />
          <label class="block mb-2 font-medium">Descrição</label>
          <textarea name="descricao" required class="w-full mb-4 border px-3 py-2 rounded" rows="4"></textarea>
          <label class="block mb-2 font-medium">Prioridade</label>
          <select name="prioridade" class="w-full mb-4 border px-3 py-2 rounded">
            <option value="baixa">Baixa</option>
            <option value="media" selected>Média</option>
            <option value="alta">Alta</option>
            <option value="urgente">Urgente</option>
          </select>
          <div class="flex justify-end space-x-2">
            <button type="button" onclick="document.getElementById('newTicketModal').classList.add('hidden')" 
                    class="px-4 py-2 rounded border">Cancelar</button>
            <button type="submit" name="create_ticket" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Enviar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Ver Ticket -->
    <div id="viewTicketModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded shadow-lg w-96 max-h-96 overflow-y-auto">
        <h3 class="text-xl font-bold mb-4" id="ticketTitle"></h3>
        <div class="mb-4">
          <p class="text-gray-700" id="ticketDescription"></p>
        </div>
        <div class="flex justify-end">
          <button type="button" onclick="document.getElementById('viewTicketModal').classList.add('hidden')" 
                  class="px-4 py-2 rounded border">Fechar</button>
        </div>
      </div>
    </div>

    <script>
    function verTicket(id, titulo, descricao) {
      document.getElementById('ticketTitle').textContent = titulo;
      document.getElementById('ticketDescription').textContent = descricao;
      document.getElementById('viewTicketModal').classList.remove('hidden');
    }
    </script>

  </main>

</body>
</html>
