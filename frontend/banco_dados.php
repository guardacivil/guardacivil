<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar permissão (admin tem acesso total)
$currentUser = getCurrentUser();
if (!hasPermission('db') && !isAdminLoggedIn() && !(isset($currentUser['perfil']) && $currentUser['perfil'] === 'Administrador')) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Handle export request
if (isset($_GET['export']) && in_array($_GET['export'], ['estrutura', 'dados'])) {
    $tabela = $_GET['tabela'];
    
    // Validar nome da tabela para evitar SQL injection
    $tabelas_validas = ['usuarios', 'perfis', 'alertas', 'logs', 'configuracoes', 'ocorrencias'];
    if (!in_array($tabela, $tabelas_validas)) {
        header("Location: banco_dados.php?error=invalid_table");
        exit;
    }
    
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename='.$tabela.'_' . $_GET['export'] . '.sql');

    if ($_GET['export'] === 'estrutura') {
        $stmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?");
        $stmt->execute([$tabela]);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        echo $row[0] . ';';
    } else {
        $rows = $pdo->query("SELECT * FROM `$tabela`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $cols = implode('`,`', array_keys($r));
            $vals = implode("','", array_map(fn($v) => addslashes($v), array_values($r)));
            echo "INSERT INTO `$tabela` (`$cols`) VALUES ('$vals');\n";
        }
    }
    exit();
}

// Substituir listagem de tabelas SQLite por MySQL
$stmt = $pdo->query("SHOW TABLES");
$tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Obter informações do usuário logado
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Banco de Dados - Sistema Integrado da Guarda Civil</title>
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
      <a href="logs.php">Logs do Sistema</a>
      <a href="configuracoes.php">Configurações Gerais</a>
      <a href="banco_dados.php" class="active">Banco de Dados</a>
      <a href="alertas.php">Alertas e Notificações</a>
      <a href="suporte.php">Suporte</a>
      
      <a href="logout.php" class="logout">Sair</a>
    </nav>
  </aside>

  <!-- Conteúdo principal -->
  <main class="content">
    <header class="flex justify-between items-center mb-8">
      <h2 class="text-3xl font-bold">Banco de Dados</h2>
      <div class="text-gray-600 text-sm">
        Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
        (<?= htmlspecialchars($currentUser['perfil']) ?>)
      </div>
    </header>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_table'): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        <strong>Erro:</strong> Tabela inválida especificada.
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6">
      <h3 class="text-xl font-semibold mb-6">Gerenciamento do Banco de Dados</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($tabelas as $t): ?>
        <div class="bg-gray-50 rounded-lg p-4 border">
          <div class="flex items-center justify-between mb-3">
            <span class="font-medium text-lg"><?= htmlspecialchars($t) ?></span>
            <i class="fas fa-table text-blue-600"></i>
          </div>
          
          <?php
          // Contar registros na tabela
          try {
            $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
          } catch (PDOException $e) {
            $count = 0;
          }
          ?>
          
          <p class="text-sm text-gray-600 mb-3"><?= $count ?> registro(s)</p>
          
          <div class="flex space-x-2">
            <a href="?export=estrutura&tabela=<?= urlencode($t) ?>"
               class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
              <i class="fas fa-download mr-1"></i>Estrutura
            </a>
            <a href="?export=dados&tabela=<?= urlencode($t) ?>"
               class="text-green-600 hover:text-green-800 text-sm flex items-center">
              <i class="fas fa-download mr-1"></i>Dados
            </a>
          </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($tabelas)): ?>
          <div class="col-span-full">
            <p class="text-gray-500 text-center py-8">Nenhuma tabela encontrada.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Informações do banco -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
      <h3 class="text-xl font-semibold mb-4">Informações do Banco</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 p-4 rounded-lg">
          <h4 class="font-medium text-blue-900">Total de Tabelas</h4>
          <p class="text-2xl font-bold text-blue-600"><?= count($tabelas) ?></p>
        </div>
        <div class="bg-green-50 p-4 rounded-lg">
          <h4 class="font-medium text-green-900">Usuários Ativos</h4>
          <?php
          try {
            $usuarios_ativos = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1")->fetchColumn();
          } catch (PDOException $e) {
            $usuarios_ativos = 0;
          }
          ?>
          <p class="text-2xl font-bold text-green-600"><?= $usuarios_ativos ?></p>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
          <h4 class="font-medium text-purple-900">Alertas Pendentes</h4>
          <?php
          try {
            $alertas_pendentes = $pdo->query("SELECT COUNT(*) FROM alertas WHERE status = 'pendente'")->fetchColumn();
          } catch (PDOException $e) {
            $alertas_pendentes = 0;
          }
          ?>
          <p class="text-2xl font-bold text-purple-600"><?= $alertas_pendentes ?></p>
        </div>
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
