<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar permissão (admin tem acesso total)
if (!hasPermission('config') && !isAdminLoggedIn()) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_sistema = trim($_POST['nome_sistema']);
    $orgao = trim($_POST['orgao']);
    $cor = $_POST['cor'];
    $modo = $_POST['modo'];
    $itens_pagina = (int)$_POST['itens_pagina'];
    $idioma = $_POST['idioma'];
    $fuso_horario = $_POST['fuso_horario'];
    $notificacoes_email = isset($_POST['notificacoes_email']) ? 1 : 0;
    $notificacoes_push = isset($_POST['notificacoes_push']) ? 1 : 0;
    $alertas_seguranca = isset($_POST['alertas_seguranca']) ? 1 : 0;
    $limite_ocorrencias = (int)$_POST['limite_ocorrencias'];
    $limite_usuarios = (int)$_POST['limite_usuarios'];
    $smtp_host = trim($_POST['smtp_host']);
    $smtp_port = (int)$_POST['smtp_port'];
    $smtp_user = trim($_POST['smtp_user']);
    $smtp_pass = trim($_POST['smtp_pass']);
    $api_externa = trim($_POST['api_externa']);

    if ($nome_sistema && $orgao) {
        $logo = '';
        $params = [$nome_sistema, $orgao, $cor, $modo, $itens_pagina, $idioma, $fuso_horario, $notificacoes_email, $notificacoes_push, $alertas_seguranca, $limite_ocorrencias, $limite_usuarios, $smtp_host, $smtp_port, $smtp_user, $smtp_pass, $api_externa];
        
        if (!empty($_FILES['logo']['name'])) {
            $nomeLogo = "logo_" . time() . ".png";
            $uploadPath = "img/" . $nomeLogo;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                $logo = ", logo = ?";
                $params[] = $nomeLogo;
            }
        }

        $sql = "UPDATE configuracoes SET 
                    nome_sistema = ?,
                    orgao = ?,
                    cor = ?,
                    modo = ?,
                    itens_pagina = ?,
                    idioma = ?,
                    fuso_horario = ?,
                    notificacoes_email = ?,
                    notificacoes_push = ?,
                    alertas_seguranca = ?,
                    limite_ocorrencias = ?,
                    limite_usuarios = ?,
                    smtp_host = ?,
                    smtp_port = ?,
                    smtp_user = ?,
                    smtp_pass = ?,
                    api_externa = ?
                    $logo
                    WHERE id = 1";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = "Configurações atualizadas com sucesso!";
            logAction('atualizar_config', 'configuracoes', 1);
        } catch (PDOException $e) {
            $msg = "Erro: " . $e->getMessage();
        }
    } else {
        $msg = "Nome do sistema e órgão são obrigatórios.";
    }
}

$config = $pdo->query("SELECT * FROM configuracoes WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

// Obter informações do usuário logado
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Configurações Gerais - Sistema Integrado da Guarda Civil</title>
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
<body class="bg-gray-100">

  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Conteúdo principal -->
  <main class="content">
    <header class="flex justify-between items-center mb-8">
      <h2 class="text-3xl font-bold">Configurações Gerais</h2>
      <div class="text-gray-600 text-sm">
        Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
        (<?= htmlspecialchars($currentUser['perfil']) ?>)
      </div>
    </header>

    <?php if ($msg): ?>
      <div class="bg-green-200 text-green-800 p-3 rounded mb-6"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6">
      <h3 class="text-xl font-semibold mb-6">Configurações do Sistema</h3>
      
      <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block font-medium mb-2">Nome do Sistema</label>
            <input type="text" name="nome_sistema" class="w-full border rounded px-3 py-2" value="<?= htmlspecialchars($config['nome_sistema'] ?? '') ?>" required>
          </div>

          <div>
            <label class="block font-medium mb-2">Órgão Público</label>
            <input type="text" name="orgao" class="w-full border rounded px-3 py-2" value="<?= htmlspecialchars($config['orgao'] ?? '') ?>" required>
          </div>

          <div>
            <label class="block font-medium mb-2">Cor Padrão</label>
            <input type="color" name="cor" value="<?= htmlspecialchars($config['cor'] ?? '#1e40af') ?>" class="w-20 h-10 border rounded">
          </div>

          <div>
            <label class="block font-medium mb-2">Modo de Tema</label>
            <select name="modo" class="w-full border rounded px-3 py-2">
              <option value="claro" <?= ($config['modo'] ?? 'claro') == 'claro' ? 'selected' : '' ?>>Claro</option>
              <option value="escuro" <?= ($config['modo'] ?? 'claro') == 'escuro' ? 'selected' : '' ?>>Escuro</option>
            </select>
          </div>

          <div>
            <label class="block font-medium mb-2">Itens por Página</label>
            <input type="number" name="itens_pagina" class="w-full border rounded px-3 py-2" value="<?= $config['itens_pagina'] ?? 20 ?>" min="5" max="100">
          </div>

          <div>
            <label class="block font-medium mb-2">Idioma</label>
            <select name="idioma" class="w-full border rounded px-3 py-2">
              <option value="pt-BR" <?= ($config['idioma'] ?? 'pt-BR') == 'pt-BR' ? 'selected' : '' ?>>Português (Brasil)</option>
              <option value="en" <?= ($config['idioma'] ?? '') == 'en' ? 'selected' : '' ?>>Inglês</option>
              <option value="es" <?= ($config['idioma'] ?? '') == 'es' ? 'selected' : '' ?>>Espanhol</option>
            </select>
          </div>
          <div>
            <label class="block font-medium mb-2">Fuso Horário</label>
            <input type="text" name="fuso_horario" class="w-full border rounded px-3 py-2" value="<?= htmlspecialchars($config['fuso_horario'] ?? 'America/Sao_Paulo') ?>">
          </div>
          <div>
            <label class="block font-medium mb-2">Notificações por E-mail</label>
            <input type="checkbox" name="notificacoes_email" <?= !empty($config['notificacoes_email']) ? 'checked' : '' ?>>
          </div>
          <div>
            <label class="block font-medium mb-2">Notificações Push</label>
            <input type="checkbox" name="notificacoes_push" <?= !empty($config['notificacoes_push']) ? 'checked' : '' ?>>
          </div>
          <div>
            <label class="block font-medium mb-2">Alertas de Segurança</label>
            <input type="checkbox" name="alertas_seguranca" <?= !empty($config['alertas_seguranca']) ? 'checked' : '' ?>>
          </div>
          <div>
            <label class="block font-medium mb-2">Limite de Ocorrências</label>
            <input type="number" name="limite_ocorrencias" class="w-full border rounded px-3 py-2" value="<?= $config['limite_ocorrencias'] ?? 1000 ?>" min="1">
          </div>
          <div>
            <label class="block font-medium mb-2">Limite de Usuários</label>
            <input type="number" name="limite_usuarios" class="w-full border rounded px-3 py-2" value="<?= $config['limite_usuarios'] ?? 100 ?>" min="1">
          </div>
          <div>
            <label class="block font-medium mb-2">Host SMTP</label>
            <input type="text" name="smtp_host" class="w-full border rounded px-3 py-2" value="<?= htmlspecialchars($config['smtp_host'] ?? '') ?>">
          </div>
          <div>
            <label class="block font-medium mb-2">Porta SMTP</label>
            <input type="number" name="smtp_port" class="w-full border rounded px-3 py-2" value="<?= $config['smtp_port'] ?? 587 ?>">
          </div>
          <div>
            <label class="block font-medium mb-2">Usuário SMTP</label>
            <input type="text" name="smtp_user" class="w-full border rounded px-3 py-2" value="<?= htmlspecialchars($config['smtp_user'] ?? '') ?>">
          </div>
          <div>
            <label class="block font-medium mb-2">Senha SMTP</label>
            <input type="password" name="smtp_pass" class="w-full border rounded px-3 py-2" value="<?= htmlspecialchars($config['smtp_pass'] ?? '') ?>">
          </div>
          <div>
            <label class="block font-medium mb-2">API Externa (URL)</label>
            <input type="text" name="api_externa" class="w-full border rounded px-3 py-2" value="<?= htmlspecialchars($config['api_externa'] ?? '') ?>">
          </div>
          <div>
            <label class="block font-medium mb-2">Logotipo</label>
            <?php if (!empty($config['logo'])): ?>
              <div class="mb-2">
                <img src="img/<?= htmlspecialchars($config['logo']) ?>" alt="Logo atual" class="w-32 h-16 object-contain border rounded">
              </div>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/png,image/jpg,image/jpeg" class="w-full border rounded px-3 py-2">
            <p class="text-sm text-gray-600 mt-1">Formatos aceitos: PNG, JPG, JPEG</p>
          </div>
        </div>

        <div class="flex justify-end">
          <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
            <i class="fas fa-save mr-2"></i>Salvar Configurações
          </button>
        </div>
      </form>
    </div>

  </main>

</body>
</html>
