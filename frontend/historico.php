<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';

try {
    if ($perfil === 'Administrador') {
        $ocorrencias = $pdo->query("SELECT * FROM ocorrencias ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $ocorrencias = $pdo->prepare("SELECT * FROM ocorrencias WHERE usuario_id = ? ORDER BY id DESC");
        $ocorrencias->execute([$currentUser['id']]);
        $ocorrencias = $ocorrencias->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $ocorrencias = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Histórico de Ocorrências - Sistema Integrado da Guarda Civil</title>
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
<?php if (isset($_GET['enviar_id']) && is_numeric($_GET['enviar_id']) && isset($_GET['confirmar']) && $_GET['confirmar'] == '1') {
    $id = intval($_GET['enviar_id']);
    echo '<div id="modalOverlay" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.6);z-index:9998;"></div>';
    echo '<div id="modalEmail" tabindex="0" aria-modal="true" role="dialog" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;">';
    echo '<div style="background:#fff;padding:2rem 2.5rem;border-radius:12px;box-shadow:0 8px 32px #0003;max-width:90vw;min-width:320px;text-align:center;outline:none;">';
    echo '<h2 style="font-size:1.3rem;font-weight:bold;margin-bottom:1rem;">Deseja enviar o PDF da última ocorrência registrada por e-mail?</h2>';
    echo '<form method="POST" action="enviar_pdf_email.php" style="margin-bottom:1rem;">';
    echo '<input type="hidden" name="ids[]" value="' . $id . '">';
    echo '<button id="btnEnviarEmail" type="submit" style="background:#2563eb;color:#fff;font-weight:bold;padding:0.7rem 1.5rem;border:none;border-radius:8px;font-size:1rem;cursor:pointer;">Enviar por E-mail</button>';
    echo '</form>';
    echo '<form method="GET" action="historico.php">';
    echo '<button id="btnCancelarModal" type="submit" style="background:#aaa;color:#fff;font-weight:bold;padding:0.7rem 1.5rem;border:none;border-radius:8px;font-size:1rem;cursor:pointer;">Cancelar</button>';
    echo '</form>';
    echo '<p style="margin-top:1.2rem;color:#666;font-size:0.95rem;">Você deve escolher uma opção para continuar.</p>';
    echo '</div></div>';
    echo '<style>body{overflow:hidden !important;}</style>';
    echo '<script>
      Array.from(document.querySelectorAll("body *:not(#modalEmail):not(#modalEmail *)")).forEach(function(el){el.setAttribute("tabindex","-1");});
      document.addEventListener("keydown", function(e) { if (e.key === "Escape") e.preventDefault(); });
      document.getElementById("modalOverlay").addEventListener("mousedown", function(e) { e.preventDefault(); });
      document.getElementById("modalEmail").addEventListener("mousedown", function(e) { if (e.target === this) e.preventDefault(); });
      setTimeout(function() { document.getElementById("modalEmail").focus(); }, 100);
      document.getElementById("modalEmail").addEventListener("keydown", function(e) {
        if (e.key === "Tab") {
          e.preventDefault();
          var btnEnviar = document.getElementById("btnEnviarEmail");
          var btnCancelar = document.getElementById("btnCancelarModal");
          if (document.activeElement === btnEnviar) btnCancelar.focus();
          else btnEnviar.focus();
        }
      });
      window.onbeforeunload = function() { return "Confirme a ação no modal antes de sair."; };
    </script>';
} ?>

  <!-- Sidebar -->
  <?php if ($currentUser['perfil'] === 'Guarda Civil'): ?>
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
            <a href="checklist.php">
                <i class="fas fa-clipboard-check"></i>
                CheckList
            </a>
            <a href="parte.php">
                <i class="fas fa-user-tie"></i>
                Parte
            </a>
            <a href="historico.php" class="active">
                <i class="fas fa-history"></i>
                Histórico
            </a>
            <a href="logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i>
                Sair
            </a>
        </nav>
    </aside>
<?php else: ?>
    <?php include 'sidebar.php'; ?>
<?php endif; ?>

  <!-- Conteúdo principal -->
  <main class="content">
    <?php if (isset($_GET['msg'])): ?>
      <div class="mb-6 px-4 py-3 rounded bg-yellow-100 border border-yellow-400 text-yellow-800">
        <?= htmlspecialchars($_GET['msg']) ?>
      </div>
    <?php endif; ?>
    <header class="flex justify-between items-center mb-8">
      <h2 class="text-3xl font-bold">📋 Histórico de Ocorrências</h2>
      <div class="text-gray-600 text-sm">
        Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
        (<?= htmlspecialchars($currentUser['perfil']) ?>)
      </div>
    </header>

    <div class="bg-white rounded-lg shadow-md p-6">
      <h3 class="text-xl font-semibold mb-4">Registros de Ocorrências</h3>
      
      <?php if (empty($ocorrencias)): ?>
        <div class="text-center py-8 text-gray-500">
          <i class="fas fa-file-alt text-4xl mb-4"></i>
          <p>Nenhuma ocorrência encontrada.</p>
        </div>
      <?php else: ?>
        <form method="POST" action="enviar_pdf_email.php" id="formEnviarEmail">
          <div class="mb-4">
            <button type="submit" class="bg-purple-600 hover:bg-purple-800 text-white font-bold py-2 px-4 rounded" onclick="return confirm('Deseja enviar as ocorrências selecionadas por e-mail?')">
              <i class="fas fa-envelope mr-1"></i>Enviar Selecionadas por E-mail
            </button>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full table-auto border">
              <thead class="bg-gray-100">
                <tr>
                  <th class="p-2 border"><input type="checkbox" id="checkAll" onclick="marcarTodos(this)"></th>
                  <th class="p-2 border">ID</th>
                  <th class="p-2 border">Número</th>
                  <th class="p-2 border">Data</th>
                  <th class="p-2 border">Tipo</th>
                  <th class="p-2 border">Local</th>
                  <th class="p-2 border">Status</th>
                  <th class="p-2 border">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ocorrencias as $o): ?>
                <tr class="hover:bg-gray-50">
                  <td class="p-2 border text-center">
                    <input type="checkbox" name="ids[]" value="<?= $o['id'] ?>">
                  </td>
                  <td class="p-2 border"><?= $o['id'] ?></td>
                  <td class="p-2 border"><?= htmlspecialchars($o['numero_ocorrencia'] ?? 'N/A') ?></td>
                  <td class="p-2 border"><?= date('d/m/Y', strtotime($o['data_ocorrencia'] ?? $o['created_at'])) ?></td>
                  <td class="p-2 border"><?= htmlspecialchars($o['tipo_ocorrencia'] ?? 'N/A') ?></td>
                  <td class="p-2 border"><?= htmlspecialchars($o['local_ocorrencia'] ?? 'N/A') ?></td>
                  <td class="p-2 border">
                    <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">
                      Registrada
                    </span>
                  </td>
                  <td class="p-2 border">
                    <a class="text-blue-600 hover:text-blue-800 text-sm mr-2" href="ver_ocorrencia.php?id=<?= $o['id'] ?>">
                      <i class="fas fa-eye mr-1"></i>Ver
                    </a>
                    <?php if (file_exists("../temp/ocorrencia_" . $o['id'] . ".pdf")): ?>
                      <a class="text-green-600 hover:text-green-800 text-sm mr-2" href="gerar_pdf_ocorrencia.php?id=<?= $o['id'] ?>" target="_blank">
                        <i class="fas fa-download mr-1"></i>PDF
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </form>
        <script>
        function marcarTodos(source) {
          checkboxes = document.querySelectorAll('input[name="ids[]"]');
          for(var i=0, n=checkboxes.length;i<n;i++) {
            checkboxes[i].checked = source.checked;
          }
        }
        </script>
      <?php endif; ?>
    </div>

  </main>

</body>
</html>
