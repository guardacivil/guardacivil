<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar permissão (admin tem acesso total)
$currentUser = getCurrentUser();
if (!hasPermission('perfis') && !isAdminLoggedIn() && !(isset($currentUser['perfil']) && $currentUser['perfil'] === 'Administrador')) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Inserir perfil
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['acao'] === 'inserir') {
    $nome = trim($_POST['nome_perfil']);
    $tipo = trim($_POST['tipo_perfil']);
    $perms = $_POST['permissoes'] ?? [];
    $jsonPerms = json_encode($perms);

    if ($nome && $tipo) {
        $stmt = $pdo->prepare("INSERT INTO perfis (nome, tipo, permissoes) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$nome, $tipo, $jsonPerms]);
            $msg = "Perfil criado com sucesso!";
            logAction('criar_perfil', 'perfis', $pdo->lastInsertId());
        } catch (PDOException $e) {
            $msg = "Erro: " . $e->getMessage();
        }
    }
    header("Location: perfis.php");
    exit;
}

// Editar perfil
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['acao'] === 'editar') {
    $id = intval($_POST['id']);
    $nome = trim($_POST['nome_perfil']);
    $tipo = trim($_POST['tipo_perfil']);
    $perms = $_POST['permissoes'] ?? [];
    $jsonPerms = json_encode($perms);

    if ($nome && $tipo) {
        $stmt = $pdo->prepare("UPDATE perfis SET nome=?, tipo=?, permissoes=? WHERE id=?");
        try {
            $stmt->execute([$nome, $tipo, $jsonPerms, $id]);
            $msg = "Perfil atualizado com sucesso!";
            logAction('editar_perfil', 'perfis', $id);
        } catch (PDOException $e) {
            $msg = "Erro: " . $e->getMessage();
        }
    }
    header("Location: perfis.php");
    exit;
}

// Excluir perfil
if (isset($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $stmt = $pdo->prepare("DELETE FROM perfis WHERE id=?");
    try {
        $stmt->execute([$id]);
        $msg = "Perfil excluído com sucesso!";
        logAction('excluir_perfil', 'perfis', $id);
    } catch (PDOException $e) {
        $msg = "Erro: " . $e->getMessage();
    }
    header("Location: perfis.php");
    exit;
}

// Buscar perfis
$perfis = $pdo->query("SELECT * FROM perfis ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Obter informações do usuário logado
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Perfis e Permissões - Sistema Integrado da Guarda Civil</title>
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
    <script>
    function mascaraJsonCheckboxes(form) {
      const checkboxes = form.querySelectorAll('input[type=checkbox]');
      const selected = [];
      checkboxes.forEach(cb=>cb.checked && selected.push(cb.value));
      form.querySelector('input[name="permissoes"]').value = JSON.stringify(selected);
    }
    function abrirModalEditar(id, nome, tipo, permissoes) {
      document.getElementById('modalEditar').classList.remove('hidden');
      document.getElementById('editar_id').value = id;
      document.getElementById('editar_nome').value = nome;
      document.getElementById('editar_tipo').value = tipo;
      const perms = JSON.parse(permissoes);
      document.querySelectorAll('#modalEditar input[type=checkbox]').forEach(cb=>{
        cb.checked = perms.includes(cb.value);
      });
      document.getElementById('json_edit').value = permissoes;
    }
    function fecharModal(id) {
      document.getElementById(id).classList.add('hidden');
    }
    </script>
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <!-- Conteúdo principal -->
  <main class="content">
    <header class="flex justify-between items-center mb-8">
      <h2 class="text-3xl font-bold mb-8"><i class="fas fa-id-card mr-2"></i>Perfis e Permissões</h2>
      <div class="text-gray-600 text-sm">
        Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
        (<?= htmlspecialchars($currentUser['perfil']) ?>)
      </div>
    </header>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-semibold">Gerenciar Perfis</h3>
        <button onclick="document.getElementById('modalNovoPerfil').classList.remove('hidden')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
          <i class="fas fa-plus mr-2"></i>Novo Perfil
        </button>
      </div>
      <table class="min-w-full bg-white rounded border">
        <thead class="bg-gray-100 text-left">
          <tr>
            <th class="p-2 border">ID</th>
            <th class="p-2 border">Nome</th>
            <th class="p-2 border">Tipo</th>
            <th class="p-2 border">Permissões</th>
            <th class="p-2 border">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$perfis): ?>
            <tr><td colspan="5" class="text-center p-4">Nenhum perfil cadastrado.</td></tr>
          <?php else: foreach($perfis as $p): ?>
            <tr class="hover:bg-gray-50">
              <td class="p-2 border"><?= $p['id']?></td>
              <td class="p-2 border"><?= htmlspecialchars($p['nome'])?></td>
              <td class="p-2 border"><?= htmlspecialchars($p['tipo'])?></td>
              <td class="p-2 border">
                <?php $perms = json_decode($p['permissoes'], true) ?: []; echo implode(', ', $perms);?>
              </td>
              <td class="p-2 border">
                <button onclick="abrirModalEditar('<?=$p['id']?>','<?=htmlspecialchars($p['nome'],1)?>','<?=htmlspecialchars($p['tipo'],1)?>','<?=addslashes($p['permissoes'])?>')" class="text-blue-600 mr-2 hover:underline">
                  <i class="fas fa-edit mr-1"></i>Editar
                </button>
                <a href="?excluir=<?=$p['id']?>" onclick="return confirm('Excluir este perfil?')" class="text-red-600 hover:underline">
                  <i class="fas fa-trash mr-1"></i>Excluir
                </a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Modal Inserir -->
    <div id="modalNovoPerfil" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
      <div class="bg-white p-6 rounded shadow-md w-full max-w-md">
        <h3 class="text-xl mb-4">Novo Perfil</h3>
        <form onsubmit="mascaraJsonCheckboxes(this)" method="POST">
          <input type="hidden" name="acao" value="inserir">
          <label class="block mb-2 font-medium">Nome:</label>
          <input name="nome_perfil" class="w-full border px-2 py-1 mb-3 rounded" required>
          <label class="block mb-2 font-medium">Tipo:</label>
          <input name="tipo_perfil" class="w-full border px-2 py-1 mb-3 rounded" required>
          <fieldset class="mb-3">
            <legend class="font-medium">Permissões:</legend>
            <?php foreach(['dashboard','usuarios','perfis','logs','config','db','alertas','suporte'] as $perm): ?>
              <label class="block">
                <input type="checkbox" value="<?=$perm?>" class="mr-2"> <?=$perm?>
              </label>
            <?php endforeach;?>
          </fieldset>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
          <button type="button" onclick="fecharModal('modalNovoPerfil')" class="ml-2 px-4 py-2 border rounded">Cancelar</button>
        </form>
      </div>
    </div>

    <!-- Modal Editar -->
    <div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
      <div class="bg-white p-6 rounded shadow-md w-full max-w-md">
        <h3 class="text-xl mb-4">Editar Perfil</h3>
        <form onsubmit="mascaraJsonCheckboxes(this)" method="POST">
          <input type="hidden" name="acao" value="editar">
          <input type="hidden" name="id" id="editar_id">
          <label class="block mb-2 font-medium">Nome:</label>
          <input name="nome_perfil" id="editar_nome" class="w-full border px-2 py-1 mb-3 rounded" required>
          <label class="block mb-2 font-medium">Tipo:</label>
          <input name="tipo_perfil" id="editar_tipo" class="w-full border px-2 py-1 mb-3 rounded" required>
          <fieldset class="mb-3">
            <legend class="font-medium">Permissões:</legend>
            <?php foreach(['dashboard','usuarios','perfis','logs','config','db','alertas','suporte'] as $perm): ?>
              <label class="block">
                <input type="checkbox" value="<?=$perm?>" class="mr-2"> <?=$perm?>
              </label>
            <?php endforeach;?>
          </fieldset>
          <input type="hidden" name="permissoes" id="json_edit">
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Atualizar</button>
          <button type="button" onclick="fecharModal('modalEditar')" class="ml-2 px-4 py-2 border rounded">Cancelar</button>
        </form>
      </div>
    </div>

  </main>

</body>
</html>
