<?php
// usuarios.php - Gestão de Usuários
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();

// Verificar permissão de acesso à gestão de usuários
if (!hasPermission('usuarios') && !(isset($currentUser['perfil']) && $currentUser['perfil'] === 'Administrador')) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

$msg = '';

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $perfil_id = $_POST['perfil_id'] ?? '';

    if ($nome && $usuario && $senha && $perfil_id) {
        try {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare('INSERT INTO usuarios (nome, usuario, senha, perfil_id, ativo) VALUES (?, ?, ?, ?, 1)');
            $stmt->execute([$nome, $usuario, $senha_hash, $perfil_id]);
            
            $msg = 'Usuário criado com sucesso!';
        } catch (PDOException $e) {
            $msg = 'Erro ao criar usuário: ' . $e->getMessage();
        }
    }
}

// Buscar usuários
try {
    $stmt = $pdo->query('
        SELECT u.*, p.nome as perfil 
        FROM usuarios u 
        LEFT JOIN perfis p ON u.perfil_id = p.id 
        ORDER BY u.nome
    ');
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    $usuarios = [];
}

// Buscar perfis
try {
    $stmt = $pdo->query('SELECT * FROM perfis ORDER BY nome');
    $perfis = $stmt->fetchAll();
} catch (PDOException $e) {
    $perfis = [];
}

if (isset($_GET['json']) && $_GET['json'] == '1') {
    header('Content-Type: application/json');
    echo json_encode($usuarios);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestão de Usuários - Sistema Integrado da Guarda Civil</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <style>
    main.content {
      margin-left: 16rem;
      padding: 2rem;
      width: calc(100% - 16rem);
    }
  </style>
</head>
<body class="bg-gray-100">
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #2563eb 0%, #000 100%);
    min-height: 100vh;
    margin: 0;
  }

  <?php include 'sidebar.php'; ?>

  <!-- Conteúdo principal -->
  <main class="content">
    <header class="flex justify-between items-center mb-8">
      <h2 class="text-3xl font-bold">Gestão de Usuários</h2>
      <div class="flex items-center space-x-4">
        <a href="teste_completo.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">
          <i class="fas fa-search-plus mr-2"></i>Teste Completo
        </a>
        <a href="usuarios_forcado.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
          <i class="fas fa-play mr-2"></i>Forçado
        </a>
        <a href="teste_banco.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
          <i class="fas fa-database mr-2"></i>Teste Banco
        </a>
        <a href="usuarios_sem_sidebar.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded">
          <i class="fas fa-minus mr-2"></i>Sem Sidebar
        </a>
        <a href="usuarios_simples.php" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">
          <i class="fas fa-code mr-2"></i>Teste Simples
        </a>
        <a href="debug_usuarios.php" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
          <i class="fas fa-search mr-2"></i>Debug
        </a>
        <a href="gerenciar_permissoes_usuarios.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
          <i class="fas fa-user-cog mr-2"></i>Gerenciar Permissões
        </a>
        <a href="limpar_permissoes.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
          <i class="fas fa-broom mr-2"></i>Limpar Permissões
        </a>
        <a href="configurar_admin.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
          <i class="fas fa-user-cog mr-2"></i>Configurar Admin
        </a>
        <a href="verificar_tabelas.php" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded">
          <i class="fas fa-database mr-2"></i>Verificar Tabelas
        </a>
        <a href="gerenciar_permissoes.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded">
          <i class="fas fa-shield-alt mr-2"></i>Gerenciar Perfis
        </a>
        <div class="text-gray-600 text-sm">
          Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
          (<?= htmlspecialchars($currentUser['perfil']) ?>)
        </div>
      </div>
    </header>

    <?php if (!empty($msg)): ?>
      <div class="px-4 py-2 bg-green-200 text-green-800 rounded mb-6"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Formulário de criação -->
    <form method="POST" class="mb-8 bg-white p-6 rounded shadow-md max-w-lg" id="formNovoUsuario">
      <h3 class="text-xl font-semibold mb-4">Novo Usuário</h3>

      <label class="block mb-2 font-medium">Nome Completo</label>
      <input name="nome" class="w-full border px-3 py-2 rounded mb-4" required/>

      <label class="block mb-2 font-medium">Usuário (login)</label>
      <input name="usuario" class="w-full border px-3 py-2 rounded mb-4" required/>

      <label class="block mb-2 font-medium">Senha</label>
      <input name="senha" type="password" class="w-full border px-3 py-2 rounded mb-4" required/>

      <label class="block mb-2 font-medium">Perfil</label>
      <div class="mb-2 text-xs text-gray-600">
        <b>Dica:</b> Escolha o perfil conforme a função do usuário. Cada perfil já possui permissões pré-definidas.<br>
        <b>Exemplos (clique para selecionar):</b><br>
        <span class="perfil-sugestao cursor-pointer text-blue-700 hover:underline" onclick="selecionarPerfilPorNome('Administrador')">Administrador: acesso total ao sistema.</span><br>
        <span class="perfil-sugestao cursor-pointer text-blue-700 hover:underline" onclick="selecionarPerfilPorNome('Guarda Civil')">Guarda Civil: acesso operacional (comunicação, escalas, ocorrências, suporte).</span><br>
        <span class="perfil-sugestao cursor-pointer text-blue-700 hover:underline" onclick="selecionarPerfilPorNome('Comando')">Comando: acesso administrativo (ocorrências, checklists, usuários).</span><br>
        <span class="perfil-sugestao cursor-pointer text-blue-700 hover:underline" onclick="selecionarPerfilPorNome('Secretário')">Secretário: acesso a ocorrências e checklists.</span><br>
        <span class="perfil-sugestao cursor-pointer text-blue-700 hover:underline" onclick="selecionarPerfilPorNome('Visitante')">Visitante: acesso restrito apenas a ocorrências.</span><br>
        <span class="perfil-sugestao cursor-pointer text-blue-700 hover:underline" onclick="selecionarPerfilPorNome('Comandante Geral')">Comandante Geral: acesso total e relatórios estratégicos.</span><br>
      </div>
      <select name="perfil_id" class="w-full border px-3 py-2 rounded mb-4" id="perfilSelect" required onchange="mostrarPermissoesPerfil()">
        <option value="">Selecione...</option>
        <?php foreach ($perfis as $p): ?>
          <option value="<?= $p['id'] ?>" data-permissoes='<?= htmlspecialchars($p['permissoes']) ?>'><?= htmlspecialchars($p['nome']) ?></option>
        <?php endforeach; ?>
      </select>

      <div id="permissoesPerfil" class="mb-4 hidden bg-blue-50 border border-blue-200 rounded p-3 text-blue-900 text-sm"></div>

      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Criar Usuário</button>
    </form>
    <script>
function mostrarPermissoesPerfil() {
  var select = document.getElementById('perfilSelect');
  var div = document.getElementById('permissoesPerfil');
  var option = select.options[select.selectedIndex];
  var permissoes = option.getAttribute('data-permissoes');
  if (permissoes) {
    try {
      var lista = JSON.parse(permissoes);
      div.innerHTML = '<b>Permissões deste perfil:</b><ul style="margin-top:4px">' + lista.map(function(p){ return '<li>• ' + p + '</li>'; }).join('') + '</ul>';
      div.classList.remove('hidden');
    } catch(e) {
      div.innerHTML = '';
      div.classList.add('hidden');
    }
  } else {
    div.innerHTML = '';
    div.classList.add('hidden');
  }
}

function selecionarPerfilPorNome(nome) {
  var select = document.getElementById('perfilSelect');
  for (var i = 0; i < select.options.length; i++) {
    if (select.options[i].text === nome) {
      select.selectedIndex = i;
      mostrarPermissoesPerfil();
      break;
    }
  }
}
</script>

    <!-- Lista de usuários -->
    <div class="bg-white rounded shadow-md p-6">
      <h3 class="text-xl font-semibold mb-4"><i class="fas fa-users-shield mr-2"></i>Usuários Cadastrados</h3>
      <table class="w-full table-auto border-collapse">
        <thead>
          <tr class="bg-gray-200">
            <th class="border px-4 py-2">ID</th>
            <th class="border px-4 py-2">Nome</th>
            <th class="border px-4 py-2">Usuário</th>
            <th class="border px-4 py-2">Perfil</th>
            <th class="border px-4 py-2">Status</th>
            <th class="border px-4 py-2">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <tr>
              <td class="border px-4 py-2"><?= $u['id'] ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($u['nome']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($u['usuario']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($u['perfil']) ?></td>
              <td class="border px-4 py-2">
                <span class="px-2 py-1 rounded text-xs <?= $u['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                  <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>
                </span>
              </td>
              <td class="border px-4 py-2">
                <a href="editar_usuario.php?id=<?= $u['id'] ?>" class="text-blue-600 hover:underline mr-2"><i class="fas fa-edit mr-1"></i>Editar</a>
                <a href="excluir_usuario.php?id=<?= $u['id'] ?>" onclick="return confirm('Excluir este usuário?');" class="text-red-600 hover:underline"><i class="fas fa-trash mr-1"></i>Excluir</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

</body>
</html>
