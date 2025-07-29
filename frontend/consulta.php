<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Variáveis de filtro
$nome     = $_GET['nome'] ?? '';
$cpf      = $_GET['cpf'] ?? '';
$data     = $_GET['data'] ?? '';
$tipo     = $_GET['tipo'] ?? '';
$local    = $_GET['local'] ?? '';

// Consulta ao banco de dados
$resultados = [];
$where = [];
$params = [];

if ($nome) {
    $where[] = "descricao LIKE ?";
    $params[] = '%' . $nome . '%';
}

if ($cpf) {
    $where[] = "envolvidos LIKE ?";
    $params[] = '%' . $cpf . '%';
}

if ($data) {
    $where[] = "DATE(data_ocorrencia) = ?";
    $params[] = $data;
}

if ($tipo) {
    $where[] = "tipo_ocorrencia = ?";
    $params[] = $tipo;
}

if ($local) {
    $where[] = "local_ocorrencia LIKE ?";
    $params[] = '%' . $local . '%';
}

if ($perfil === 'Guarda Civil') {
    $where[] = "usuario_id = ?";
    $params[] = $currentUser['id'];
}

$sql = "SELECT * FROM ocorrencias";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY data_ocorrencia DESC, created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $resultados = [];
}

// Obter informações do usuário logado
$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Consulta de Ocorrências - Sistema Integrado da Guarda Civil</title>
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
    .sidebar-guarda nav a {
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
    .sidebar-guarda nav a:hover {
      background-color: rgba(255, 255, 255, 0.1);
      transform: translateX(5px);
    }
    .sidebar-guarda nav a.active {
      background-color: rgba(255, 255, 255, 0.2);
      border-left: 4px solid #fbbf24;
    }
    .sidebar-guarda nav a i {
      margin-right: 0.75rem;
      width: 1.25rem;
      text-align: center;
    }
    .sidebar-guarda nav a.logout {
      background-color: #dc2626;
      margin-top: 2rem;
    }
    .sidebar-guarda nav a.logout:hover {
      background-color: #b91c1c;
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
<?php if ($currentUser['perfil'] === 'Guarda Civil'): ?>
    <!-- Sidebar para Guarda Civil -->
    <aside class="sidebar-guarda">
        <div class="logo-container">
            <img src="img/logo1.png" alt="Logo" style="width: 10.14rem;" />
            <h1>Sistema Integrado da Guarda Civil</h1>
            <p>Município de Araçoiaba da Serra</p>
        </div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            
            <!-- Gestão de Pessoal -->
            <a href="pessoal.php">Gestão de Pessoal</a>
            <a href="graduacoes.php">Graduações</a>
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
            
            <a href="checklist.php">Conferir Checklists</a>
            <a href="logout.php" class="logout">Sair</a>
        </nav>
    </aside>
<?php else: ?>
    <?php include 'sidebar.php'; ?>
<?php endif; ?>

  <!-- Conteúdo principal -->
  <main class="content">
    <header class="flex justify-between items-center mb-8">
      <h2 class="text-3xl font-bold">🔍 Consulta de Ocorrências</h2>
      <div class="text-gray-600 text-sm">
        Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
        (<?= htmlspecialchars($currentUser['perfil']) ?>)
      </div>
    </header>

    <form method="GET" class="bg-white p-6 rounded shadow-md mb-6">
      <h3 class="text-xl font-semibold mb-4">Filtros de Busca</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div>
          <label class="block font-medium mb-2">Descrição/Conteúdo</label>
          <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" 
                 class="border rounded px-3 py-2 w-full" placeholder="Buscar na descrição...">
        </div>
        <div>
          <label class="block font-medium mb-2">CPF/Envolvidos</label>
          <input type="text" name="cpf" value="<?= htmlspecialchars($cpf) ?>" 
                 class="border rounded px-3 py-2 w-full" placeholder="CPF ou nome...">
        </div>
        <div>
          <label class="block font-medium mb-2">Data da Ocorrência</label>
          <input type="date" name="data" value="<?= htmlspecialchars($data) ?>" 
                 class="border rounded px-3 py-2 w-full">
        </div>
        <div>
          <label class="block font-medium mb-2">Tipo de Ocorrência</label>
          <select name="tipo" class="border rounded px-3 py-2 w-full">
            <option value="">Todos os tipos</option>
            <option value="furto" <?= $tipo=='furto'?'selected':'' ?>>Furto</option>
            <option value="roubo" <?= $tipo=='roubo'?'selected':'' ?>>Roubo</option>
            <option value="homicidio" <?= $tipo=='homicidio'?'selected':'' ?>>Homicídio</option>
            <option value="trafico" <?= $tipo=='trafico'?'selected':'' ?>>Tráfico</option>
            <option value="acidente" <?= $tipo=='acidente'?'selected':'' ?>>Acidente</option>
            <option value="ameaca" <?= $tipo=='ameaca'?'selected':'' ?>>Ameaça</option>
            <option value="lesao" <?= $tipo=='lesao'?'selected':'' ?>>Lesão Corporal</option>
            <option value="outros" <?= $tipo=='outros'?'selected':'' ?>>Outros</option>
          </select>
        </div>
        <div>
          <label class="block font-medium mb-2">Local</label>
          <input type="text" name="local" value="<?= htmlspecialchars($local) ?>" 
                 class="border rounded px-3 py-2 w-full" placeholder="Local da ocorrência...">
        </div>
      </div>

      <div class="mt-4 flex gap-2">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
          <i class="fas fa-search mr-2"></i> Buscar
        </button>
        <a href="consulta.php" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400 text-gray-800">
          <i class="fas fa-eraser mr-2"></i> Limpar
        </a>
      </div>
    </form>

    <?php if (!empty($resultados)): ?>
      <div class="bg-white rounded-lg shadow-md p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold">Resultados da Busca (<?= count($resultados) ?> registros)</h3>
          <div class="text-sm text-gray-600">
            <span class="font-medium">Ordenar por:</span>
            <button onclick="sortBy('data')" class="px-2 py-1 bg-blue-100 rounded mr-2 text-sm hover:bg-blue-200">Data</button>
            <button onclick="sortBy('tipo')" class="px-2 py-1 bg-blue-100 rounded mr-2 text-sm hover:bg-blue-200">Tipo</button>
            <button onclick="sortBy('local')" class="px-2 py-1 bg-blue-100 rounded text-sm hover:bg-blue-200">Local</button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table id="resultsTable" class="min-w-full table-auto border">
            <thead class="bg-gray-100">
              <tr>
                <th class="p-2 border">Número</th>
                <th class="p-2 border">Data</th>
                <th class="p-2 border">Tipo</th>
                <th class="p-2 border">Local</th>
                <th class="p-2 border">Descrição</th>
                <th class="p-2 border">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($resultados as $r): ?>
              <tr class="hover:bg-gray-50">
                <td class="p-2 border"><?= htmlspecialchars($r['numero_ocorrencia'] ?? 'N/A') ?></td>
                <td class="p-2 border"><?= date('d/m/Y', strtotime($r['data_ocorrencia'] ?? $r['created_at'])) ?></td>
                <td class="p-2 border">
                  <span class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
                    <?= htmlspecialchars($r['tipo_ocorrencia'] ?? 'N/A') ?>
                  </span>
                </td>
                <td class="p-2 border"><?= htmlspecialchars($r['local_ocorrencia'] ?? 'N/A') ?></td>
                <td class="p-2 border">
                  <?= htmlspecialchars(substr($r['descricao'] ?? '', 0, 100)) ?>
                  <?= strlen($r['descricao'] ?? '') > 100 ? '...' : '' ?>
                </td>
                <td class="p-2 border">
                  <a href="ver_ocorrencia.php?id=<?= $r['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm mr-2">
                    <i class="fas fa-eye mr-1"></i>Ver
                  </a>
                  <?php if (file_exists("../temp/ocorrencia_" . $r['id'] . ".pdf")): ?>
                    <a href="../temp/ocorrencia_<?= $r['id'] ?>.pdf" target="_blank" class="text-green-600 hover:text-green-800 text-sm">
                      <i class="fas fa-download mr-1"></i>PDF
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php elseif($_SERVER['REQUEST_METHOD']=='GET' && (!empty($nome) || !empty($cpf) || !empty($data) || !empty($tipo) || !empty($local))): ?>
      <div class="bg-white rounded-lg shadow-md p-6 text-center">
        <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-600">Nenhum resultado encontrado com os filtros selecionados.</p>
        <a href="consulta.php" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
          <i class="fas fa-arrow-left mr-1"></i>Limpar filtros
        </a>
      </div>
    <?php endif ?>

  </main>

  <script>
    function sortBy(col) {
      const table = document.getElementById('resultsTable');
      const rows = Array.from(table.tBodies[0].rows);
      const idx = {'data':1,'tipo':2,'local':3}[col];
      const asc = table.getAttribute('data-sort') !== col;
      rows.sort((a,b) => {
        const x = a.cells[idx].innerText.toLowerCase(), y = b.cells[idx].innerText.toLowerCase();
        return asc ? x.localeCompare(y) : y.localeCompare(x);
      });
      rows.forEach(r => table.tBodies[0].appendChild(r));
      table.setAttribute('data-sort', asc ? col : '');
    }
  </script>

</body>
</html>
