<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar permissão (admin tem acesso total)
$currentUser = getCurrentUser();
if (!hasPermission('logs') && !isAdminLoggedIn() && !(isset($currentUser['perfil']) && $currentUser['perfil'] === 'Administrador')) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Limpar logs
if (isset($_POST['limpar']) && $_POST['limpar'] === '1') {
    try {
        $pdo->exec("TRUNCATE TABLE logs");
        $msg = "Logs limpos com sucesso!";
        logAction('limpar_logs', 'logs', 0);
    } catch (PDOException $e) {
        $msg = "Erro: " . $e->getMessage();
    }
    header("Location: logs.php");
    exit;
}

// Filtros
$where = [];
$params = [];

if (!empty($_GET['usuario'])) {
    $where[] = "u.nome = ?";
    $params[] = $_GET['usuario'];
}
if (!empty($_GET['acao'])) {
    $where[] = "acao = ?";
    $params[] = $_GET['acao'];
}
// Filtros de data removidos pois a tabela logs não tem coluna de data/hora

$sql = "SELECT l.*, u.nome as usuario_nome FROM logs l LEFT JOIN usuarios u ON l.usuario_id = u.id";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY l.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para filtro de usuário - buscar nomes dos usuários
$usuarios = $pdo->query("SELECT DISTINCT u.nome FROM logs l LEFT JOIN usuarios u ON l.usuario_id = u.id WHERE u.nome IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

// Obter informações do usuário logado
$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';

try {
    if ($perfil === 'Guarda Civil') {
        $stmt = $pdo->prepare('SELECT * FROM logs WHERE usuario_id = ? ORDER BY id DESC');
        $stmt->execute([$currentUser['id']]);
        $logs = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query('SELECT * FROM logs ORDER BY id DESC');
        $logs = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $logs = [];
}

// Relatório detalhado por usuário (apenas admin)
if (isAdminLoggedIn() || (isset($currentUser['perfil']) && $currentUser['perfil'] === 'Administrador')) {
    if (isset($_GET['relatorio_usuario']) && $_GET['relatorio_usuario'] !== '') {
        $usuarioDetalhe = $_GET['relatorio_usuario'];
        $stmt = $pdo->prepare("SELECT l.*, u.nome as usuario_nome FROM logs l LEFT JOIN usuarios u ON l.usuario_id = u.id WHERE u.nome = ? ORDER BY l.id DESC");
        $stmt->execute([$usuarioDetalhe]);
        $logsDetalhados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $logsDetalhados = [];
    }
    $usuariosRelatorio = $pdo->query("SELECT DISTINCT u.nome FROM logs l LEFT JOIN usuarios u ON l.usuario_id = u.id WHERE u.nome IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Logs do Sistema - Sistema Integrado da Guarda Civil</title>
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
        main.content {
            margin-left: 16rem;
            padding: 2rem;
            width: calc(100% - 16rem);
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
      <a href="logs.php" class="active">Logs do Sistema</a>
      <a href="configuracoes.php">Configurações Gerais</a>
      <a href="banco_dados.php">Banco de Dados</a>
      <a href="alertas.php">Alertas e Notificações</a>
      <a href="suporte.php">Suporte</a>
      
      <a href="logout.php" class="logout">Sair</a>
    </nav>
  </aside>

  <!-- Conteúdo principal -->
  <main class="content">
    <header class="flex justify-between items-center mb-8">
      <h2 class="text-3xl font-bold">Logs do Sistema</h2>
      <div class="text-gray-600 text-sm">
        Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
        (<?= htmlspecialchars($currentUser['perfil']) ?>)
      </div>
    </header>

    <div class="flex justify-between items-center mb-6">
      <h3 class="text-xl font-semibold">Registros de Atividade</h3>
      <form method="POST">
        <input type="hidden" name="limpar" value="1">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded"
                onclick="return confirm('Deseja limpar todos os logs?')">
          <i class="fas fa-trash mr-2"></i>Limpar Logs
        </button>
      </form>
    </div>

    <form method="GET" class="bg-white p-4 rounded shadow-md mb-6 grid grid-cols-1 md:grid-cols-5 gap-4">
      <select name="usuario" class="border px-2 py-1 rounded">
        <option value="">Todos Usuários</option>
        <?php foreach ($usuarios as $u): ?>
          <option value="<?=htmlspecialchars($u)?>" <?=($_GET['usuario']==$u)?'selected':''?>><?=htmlspecialchars($u)?></option>
        <?php endforeach; ?>
      </select>
      <select name="acao" class="border px-2 py-1 rounded">
        <option value="">Todas Ações</option>
        <option value="login" <?=($_GET['acao']=='login')?'selected':''?>>Login</option>
        <option value="logout" <?=($_GET['acao']=='logout')?'selected':''?>>Logout</option>
        <option value="criar_usuario" <?=($_GET['acao']=='criar_usuario')?'selected':''?>>Criar Usuário</option>
        <option value="editar_usuario" <?=($_GET['acao']=='editar_usuario')?'selected':''?>>Editar Usuário</option>
        <option value="excluir_usuario" <?=($_GET['acao']=='excluir_usuario')?'selected':''?>>Excluir Usuário</option>
        <option value="criar_perfil" <?=($_GET['acao']=='criar_perfil')?'selected':''?>>Criar Perfil</option>
        <option value="editar_perfil" <?=($_GET['acao']=='editar_perfil')?'selected':''?>>Editar Perfil</option>
        <option value="excluir_perfil" <?=($_GET['acao']=='excluir_perfil')?'selected':''?>>Excluir Perfil</option>
        <option value="criar_alerta" <?=($_GET['acao']=='criar_alerta')?'selected':''?>>Criar Alerta</option>
        <option value="editar_alerta" <?=($_GET['acao']=='editar_alerta')?'selected':''?>>Editar Alerta</option>
        <option value="excluir_alerta" <?=($_GET['acao']=='excluir_alerta')?'selected':''?>>Excluir Alerta</option>
      </select>
      <!-- Campos de data removidos pois a tabela logs não tem coluna de data/hora -->
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
        <i class="fas fa-search mr-2"></i>Filtrar
      </button>
    </form>

    <?php if (isAdminLoggedIn() || (isset($currentUser['perfil']) && $currentUser['perfil'] === 'Administrador')): ?>
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-xl font-semibold mb-4 flex items-center"><i class="fas fa-user-shield text-blue-700 mr-2"></i>Relatório Detalhado por Usuário</h3>
        <form method="GET" class="flex flex-wrap gap-4 items-center mb-4">
          <input type="hidden" name="relatorio" value="1">
          <label for="relatorio_usuario" class="font-medium">Selecione o usuário:</label>
          <select name="relatorio_usuario" id="relatorio_usuario" class="border px-2 py-1 rounded">
            <option value="">-- Escolha um usuário --</option>
            <?php foreach ($usuariosRelatorio as $u): ?>
              <option value="<?=htmlspecialchars($u)?>" <?=isset($_GET['relatorio_usuario']) && $_GET['relatorio_usuario']==$u?'selected':''?>><?=htmlspecialchars($u)?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded"><i class="fas fa-search mr-2"></i>Ver Relatório</button>
        </form>
        <?php if (isset($usuarioDetalhe) && $usuarioDetalhe): ?>
          <h4 class="text-lg font-bold mb-2">Ações do usuário: <span class="text-blue-700"><?=htmlspecialchars($usuarioDetalhe)?></span></h4>
          <div class="overflow-auto">
            <table class="min-w-full table-auto border">
              <thead class="bg-gray-100">
                <tr>
                  <th class="px-4 py-2 border">ID</th>
                  <th class="px-4 py-2 border">Ação</th>
                  <th class="px-4 py-2 border">Descrição</th>
                  <th class="px-4 py-2 border">Data / Hora</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!$logsDetalhados): ?>
                  <tr><td colspan="4" class="text-center p-4 text-gray-500">Sem registros para este usuário.</td></tr>
                <?php else: foreach ($logsDetalhados as $l): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 border"><?= $l['id'] ?></td>
                    <td class="px-4 py-2 border">
                      <span class="px-2 py-1 rounded text-xs 
                        <?= strpos($l['acao'], 'criar') !== false ? 'bg-green-100 text-green-800' : 
                           (strpos($l['acao'], 'editar') !== false ? 'bg-yellow-100 text-yellow-800' : 
                           (strpos($l['acao'], 'excluir') !== false ? 'bg-red-100 text-red-800' : 
                           (strpos($l['acao'], 'login') !== false ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'))) ?>">
                        <?= htmlspecialchars($l['acao']) ?>
                      </span>
                    </td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($l['descricao']) ?></td>
                    <td class="px-4 py-2 border">ID: <?= htmlspecialchars($l['id']) ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6">
      <h3 class="text-xl font-semibold mb-4">Histórico de Atividades</h3>
      <div class="overflow-auto">
        <table class="min-w-full table-auto border">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 border">ID</th>
              <th class="px-4 py-2 border">Usuário</th>
              <th class="px-4 py-2 border">Ação</th>
              <th class="px-4 py-2 border">Descrição</th>
              <th class="px-4 py-2 border">Data / Hora</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$logs): ?>
              <tr><td colspan="5" class="text-center p-4 text-gray-500">Sem registros.</td></tr>
            <?php else: foreach ($logs as $l): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border"><?= $l['id'] ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($l['usuario_nome'] ?? 'Usuário não encontrado') ?></td>
                <td class="px-4 py-2 border">
                  <span class="px-2 py-1 rounded text-xs 
                    <?= strpos($l['acao'], 'criar') !== false ? 'bg-green-100 text-green-800' : 
                       (strpos($l['acao'], 'editar') !== false ? 'bg-yellow-100 text-yellow-800' : 
                       (strpos($l['acao'], 'excluir') !== false ? 'bg-red-100 text-red-800' : 
                       (strpos($l['acao'], 'login') !== false ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'))) ?>">
                    <?= htmlspecialchars($l['acao']) ?>
                  </span>
                </td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($l['descricao']) ?></td>
                <td class="px-4 py-2 border">ID: <?= htmlspecialchars($l['id']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>

  <script>
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
