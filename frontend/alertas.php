<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar permissão (admin tem acesso total)
if (!hasPermission('alertas') && !isAdminLoggedIn()) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_alert'])) {
        $titulo = trim($_POST['titulo']);
        $mensagem = trim($_POST['mensagem']);
        $prioridade = $_POST['prioridade'] ?? 'media';
        
        if ($titulo && $mensagem) {
            $stmt = $pdo->prepare("INSERT INTO alertas (titulo, mensagem, prioridade, status) VALUES (?, ?, ?, 'pendente')");
            try {
                $stmt->execute([$titulo, $mensagem, $prioridade]);
                $msg = "Alerta criado com sucesso!";
                logAction('criar_alerta', 'alertas', $pdo->lastInsertId());
            } catch (PDOException $e) {
                $msg = "Erro: " . $e->getMessage();
            }
        }
        header("Location: alertas.php");
        exit;
    }
    if (isset($_POST['edit_alert'])) {
        $id = intval($_POST['id']);
        $titulo = trim($_POST['titulo']);
        $mensagem = trim($_POST['mensagem']);
        $prioridade = $_POST['prioridade'] ?? 'media';
        
        if ($titulo && $mensagem) {
            $stmt = $pdo->prepare("UPDATE alertas SET titulo = ?, mensagem = ?, prioridade = ? WHERE id = ?");
            try {
                $stmt->execute([$titulo, $mensagem, $prioridade, $id]);
                $msg = "Alerta atualizado com sucesso!";
                logAction('editar_alerta', 'alertas', $id);
            } catch (PDOException $e) {
                $msg = "Erro: " . $e->getMessage();
            }
        }
        header("Location: alertas.php");
        exit;
    }
    if (isset($_POST['delete_alert'])) {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM alertas WHERE id = ?");
        try {
            $stmt->execute([$id]);
            $msg = "Alerta excluído com sucesso!";
            logAction('excluir_alerta', 'alertas', $id);
        } catch (PDOException $e) {
            $msg = "Erro: " . $e->getMessage();
        }
        header("Location: alertas.php");
        exit;
    }
}

// Fetch all alerts
$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';

try {
    if ($perfil === 'Guarda Civil') {
        $stmt = $pdo->prepare('SELECT * FROM alertas WHERE usuario_id = ? ORDER BY id DESC');
        $stmt->execute([$currentUser['id']]);
        $alertas = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query('SELECT * FROM alertas ORDER BY id DESC');
        $alertas = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $alertas = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Alertas e Notificações - Sistema Integrado da Guarda Civil</title>
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
        aside.sidebar nav a.logout {
            background-color: #dc2626;
        }
        aside.sidebar nav a.logout:hover {
            background-color: #b91c1c;
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
      <h2 class="text-3xl font-bold mb-8"><i class="fas fa-bell mr-2"></i>Alertas e Notificações</h2>
      <div class="text-gray-600 text-sm">
        Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
        (<?= htmlspecialchars($currentUser['perfil']) ?>)
      </div>
    </header>

    <!-- Button to open modal to add alert -->
    <button onclick="document.getElementById('addModal').classList.remove('hidden')" 
            class="mb-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i>Novo Alerta
    </button>

    <!-- Alerts list -->
    <div class="bg-white rounded-lg shadow-md p-6">
      <h3 class="text-xl font-semibold mb-4">Alertas Cadastrados</h3>
      <table class="min-w-full bg-white rounded border">
        <thead class="bg-gray-100 text-left">
          <tr>
            <th class="p-2 border">Título</th>
            <th class="p-2 border">Mensagem</th>
            <th class="p-2 border">Prioridade</th>
            <th class="p-2 border">Status</th>
            <th class="p-2 border">Data Criação</th>
            <th class="p-2 border">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($alertas as $alert): ?>
          <tr class="border-t hover:bg-gray-50">
            <td class="p-2 border"><?= htmlspecialchars($alert['titulo']) ?></td>
            <td class="p-2 border"><?= htmlspecialchars($alert['mensagem']) ?></td>
            <td class="p-2 border">
              <span class="px-2 py-1 rounded text-xs 
                <?= $alert['prioridade'] === 'urgente' ? 'bg-red-100 text-red-800' : 
                   ($alert['prioridade'] === 'alta' ? 'bg-orange-100 text-orange-800' : 
                   ($alert['prioridade'] === 'media' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800')) ?>">
                <?= ucfirst($alert['prioridade']) ?>
              </span>
            </td>
            <td class="p-2 border">
              <span class="px-2 py-1 rounded text-xs 
                <?= $alert['status'] === 'pendente' ? 'bg-yellow-100 text-yellow-800' : 
                   ($alert['status'] === 'lido' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') ?>">
                <?= ucfirst($alert['status']) ?>
              </span>
            </td>
            <td class="p-2 border"><?= date('d/m/Y H:i', strtotime($alert['created_at'])) ?></td>
            <td class="p-2 border space-x-2">
              <button onclick="openEditModal(<?= $alert['id'] ?>, '<?= addslashes(htmlspecialchars($alert['titulo'])) ?>', '<?= addslashes(htmlspecialchars($alert['mensagem'])) ?>', '<?= $alert['prioridade'] ?>')"
                      class="px-3 py-1 bg-yellow-400 rounded hover:bg-yellow-500 text-white text-sm">
                <i class="fas fa-edit mr-1"></i>Editar
              </button>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Confirma exclusão?');">
                <input type="hidden" name="id" value="<?= $alert['id'] ?>">
                <button type="submit" name="delete_alert" class="px-3 py-1 bg-red-600 rounded hover:bg-red-700 text-white text-sm">
                  <i class="fas fa-trash mr-1"></i>Excluir
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (count($alertas) === 0): ?>
          <tr><td colspan="6" class="text-center py-4 text-gray-500">Nenhum alerta cadastrado.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Modal add -->
    <div id="addModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded shadow-lg w-96">
        <h3 class="text-xl font-bold mb-4 flex items-center justify-center"><i class="fas fa-bullhorn text-4xl text-blue-600 mr-2"></i> Novo Alerta</h3>
        <form method="POST">
          <label class="block mb-2 font-medium">Título</label>
          <input type="text" name="titulo" required class="w-full mb-4 border px-3 py-2 rounded" />
          <label class="block mb-2 font-medium">Mensagem</label>
          <textarea name="mensagem" required class="w-full mb-4 border px-3 py-2 rounded" rows="3"></textarea>
          <label class="block mb-2 font-medium">Prioridade</label>
          <select name="prioridade" class="w-full mb-4 border px-3 py-2 rounded">
            <option value="baixa">Baixa</option>
            <option value="media" selected>Média</option>
            <option value="alta">Alta</option>
            <option value="urgente">Urgente</option>
          </select>
          <div class="flex justify-end space-x-2">
            <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')"
                    class="px-4 py-2 rounded border">Cancelar</button>
            <button type="submit" name="add_alert" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Salvar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal edit -->
    <div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded shadow-lg w-96">
        <h3 class="text-xl font-bold mb-4">Editar Alerta</h3>
        <form method="POST" id="editForm">
          <input type="hidden" name="id" id="edit_id" />
          <label class="block mb-2 font-medium">Título</label>
          <input type="text" name="titulo" id="edit_titulo" required class="w-full mb-4 border px-3 py-2 rounded" />
          <label class="block mb-2 font-medium">Mensagem</label>
          <textarea name="mensagem" id="edit_mensagem" required class="w-full mb-4 border px-3 py-2 rounded" rows="3"></textarea>
          <label class="block mb-2 font-medium">Prioridade</label>
          <select name="prioridade" id="edit_prioridade" class="w-full mb-4 border px-3 py-2 rounded">
            <option value="baixa">Baixa</option>
            <option value="media">Média</option>
            <option value="alta">Alta</option>
            <option value="urgente">Urgente</option>
          </select>
          <div class="flex justify-end space-x-2">
            <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')"
                    class="px-4 py-2 rounded border">Cancelar</button>
            <button type="submit" name="edit_alert" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Salvar</button>
          </div>
        </form>
      </div>
    </div>

    <script>
    function openEditModal(id, titulo, mensagem, prioridade) {
      document.getElementById('edit_id').value = id;
      document.getElementById('edit_titulo').value = titulo;
      document.getElementById('edit_mensagem').value = mensagem;
      document.getElementById('edit_prioridade').value = prioridade;
      document.getElementById('editModal').classList.remove('hidden');
    }
    </script>

  </main>

</body>
</html>
