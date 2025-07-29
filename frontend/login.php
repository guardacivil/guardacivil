<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Processar formulário de login ANTES de qualquer saída HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    $isApp = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    session_start();
    require_once 'config.php';
    
    $usuario = $_POST['username'] ?? '';
    $senha = $_POST['password'] ?? '';

    // Login universal para admin/6014
    if ($usuario === 'admin' && $senha === '6014') {
        $_SESSION['admin_id'] = 0;
        $_SESSION['admin_nome'] = 'Administrador';
        $_SESSION['admin_login'] = 'admin';
        $_SESSION['admin_perfil'] = 'Administrador';
        $_SESSION['admin_perfil_id'] = 0;
        $_SESSION['admin_logado'] = true;
        if ($isApp) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Login realizado com sucesso']);
            exit;
        } else {
            header('Location: dashboard.php');
            exit;
        }
    }
    
    if (empty($usuario) || empty($senha)) {
        if ($isApp) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Todos os campos são obrigatórios!']);
            exit;
        } else {
            $error_msg = 'Todos os campos são obrigatórios!';
        }
    } else {
        try {
            // Buscar usuário pelo nome de usuário
            $stmt = $pdo->prepare("
                SELECT u.*, p.nome as perfil_nome 
                FROM usuarios u 
                JOIN perfis p ON u.perfil_id = p.id 
                WHERE u.usuario = ? AND u.ativo = 1
            ");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($senha, $user['senha'])) {
                // Login válido - criar sessão
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_login'] = $user['usuario'];
                $_SESSION['usuario_perfil'] = $user['perfil_nome'];
                $_SESSION['usuario_perfil_id'] = $user['perfil_id'];
                $_SESSION['logado'] = true;
                // Registrar log de login
                $stmt_log = $pdo->prepare("
                    INSERT INTO logs (usuario_id, acao, tabela, ip, user_agent) 
                    VALUES (?, 'login', 'usuarios', ?, ?)
                ");
                $stmt_log->execute([
                    $user['id'], 
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);
                if ($isApp) {
                    header('Content-Type: application/json');
                    echo json_encode(['status' => 'success', 'message' => 'Login realizado com sucesso']);
                    exit;
                } else {
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                if ($isApp) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['status' => 'error', 'message' => 'Usuário ou senha inválidos!']);
                    exit;
                } else {
                    $error_msg = 'Usuário ou senha inválidos!';
                }
            }
        } catch (PDOException $e) {
            if ($isApp) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Erro interno do sistema: ' . $e->getMessage()]);
                exit;
            } else {
                $error_msg = 'Erro interno do sistema: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Sistema SMART</title>
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
      padding: 0;
    }
    .form-card {
      position: relative;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      overflow: hidden;
    }
    .form-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px rgba(0,0,0,0.1);
    }
    .form-card::before {
      content: "\f132";
      font-family: "Font Awesome 6 Free";
      font-weight: 900;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 160px;
      color: rgba(30,58,138,0.1);
      pointer-events: none;
      user-select: none;
      z-index: 0;
    }
    .nav-gradient {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    }
  </style>
</head>
<body>
  <!-- Navbar -->
  <nav class="nav-gradient text-white shadow-md">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <i class="fas fa-shield-halved text-3xl"></i>
        <span class="text-xl font-bold">SISTEMA SMART</span>
      </div>
      <img src="img/logo.png" alt="Logo Araçoiaba da Serra" class="h-16 w-16 object-contain" />
    </div>
  </nav>

  <!-- Card de Login Guarda Civil -->
  <main class="py-20 px-6 flex justify-center items-center min-h-[60vh]">
    <div class="grid grid-cols-1 max-w-md w-full">
      <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-all p-6 text-center flex flex-col items-center space-y-4 form-card">
        <i class="fas fa-shield-halved text-5xl text-blue-700"></i>
        <h3 class="text-lg font-bold text-gray-800">Acesso ao Sistema</h3>
        <span class="text-sm text-gray-500 mb-2">Entre com seu usuário e senha</span>
        <?php if (isset($error_msg)): ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-2 w-full">
            <?= htmlspecialchars($error_msg) ?>
          </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4 w-full">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Usuário</label>
            <input type="text" name="username" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required autocomplete="username">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Senha</label>
            <input type="password" name="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required autocomplete="current-password">
          </div>
          <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold">Entrar</button>
        </form>
      </div>
      <div class="text-center text-sm text-gray-500 mt-8 relative z-20">
        © 2025 Sistema SMART - Guarda Civil Municipal
      </div>
      <div class="flex justify-center mt-6">
        <img src="img/logo1.png" alt="Logo GCM" style="max-width: 180px; width: 100%; height: auto;" />
      </div>
    </div>
  </main>
</body>
</html>
