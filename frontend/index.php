<?php
session_start();
$adminErrorMsg = '';
if (isset($_SESSION['admin_login_error'])) {
    $adminErrorMsg = $_SESSION['admin_login_error'];
    unset($_SESSION['admin_login_error']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - Sistema SMART</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#2563eb">
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('service-worker.js');
      });
    }
  </script>
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
        <i class="fas fa-shield-halved text-5xl md:text-7xl text-blue-200 drop-shadow-lg"></i>
        <span class="text-2xl md:text-3xl font-bold tracking-wide">SISTEMA SMART</span>
      </div>
      <img src="img/logo.png" alt="Logo Araçoiaba da Serra" class="h-20 w-20 object-contain" />
    </div>
  </nav>

  <!-- Logo institucional acima do card de login -->
  <div class="flex justify-center mt-12 mb-4">
    <img src="img/logo1.png" alt="Logo Guarda Civil" class="h-96 w-auto object-contain" />
  </div>
  <!-- Remover bloco de ícones temáticos de segurança pública -->
  <!-- Card de Login Guarda Civil -->
  <main class="py-8 px-6 flex justify-center items-center min-h-[40vh]">
    <div class="grid grid-cols-1 max-w-md w-full">
      <a href="login.php" class="bg-white rounded-lg shadow-md hover:shadow-lg transition-all p-6 text-center flex flex-col items-center space-y-4 form-card">
        <i class="fas fa-user-shield text-7xl text-blue-700 mb-2"></i>
        <h3 class="text-lg font-bold text-gray-800">Guarda Civil</h3>
        <span class="text-sm text-gray-500">Entrar no Sistema</span>
      </a>
    </div>
  </main>
</body>
</html>
