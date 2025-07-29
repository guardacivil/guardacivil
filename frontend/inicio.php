<?php
// inicio.php - Página inicial para visitantes
session_start();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Início - Sistema SMART</title>
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
        .nav-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
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
            <div class="flex items-center space-x-4">
                <a href="index.php" class="hover:text-blue-200 transition-colors">Login</a>
                <img src="img/logo.png" alt="Logo Araçoiaba da Serra" class="h-16 w-16 object-contain" />
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                Sistema SMART
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-blue-100">
                Guarda Civil Municipal de Araçoiaba da Serra
            </p>
            <div class="flex justify-center space-x-4">
                <a href="index.php" class="bg-white text-blue-800 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors">
                    <i class="fas fa-sign-in-alt mr-2"></i>Acessar Sistema
                </a>
                <a href="#sobre" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-800 transition-colors">
                    <i class="fas fa-info-circle mr-2"></i>Saiba Mais
                </a>
            </div>
        </div>
    </section>

    <!-- Sobre Section -->
    <section id="sobre" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Sobre o Sistema SMART</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <i class="fas fa-shield-halved text-5xl text-blue-600 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-3">Proteção</h3>
                    <p class="text-gray-600">Sistema integrado para proteção e segurança da população de Araçoiaba da Serra.</p>
                </div>
                
                <div class="text-center p-6">
                    <i class="fas fa-bell text-5xl text-yellow-600 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-3">Alerta</h3>
                    <p class="text-gray-600">Sistema de alertas e notificações em tempo real para emergências.</p>
                </div>
                
                <div class="text-center p-6">
                    <i class="fas fa-hand-holding-medical text-5xl text-red-600 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-3">Emergência</h3>
                    <p class="text-gray-600">Atendimento rápido e eficiente para situações de emergência.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Funcionalidades -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Funcionalidades</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <i class="fas fa-users text-3xl text-blue-600 mb-4"></i>
                    <h3 class="font-semibold mb-2">Gestão de Usuários</h3>
                    <p class="text-sm text-gray-600">Controle de acesso e permissões por perfil.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <i class="fas fa-clipboard-list text-3xl text-green-600 mb-4"></i>
                    <h3 class="font-semibold mb-2">Registro de Ocorrências</h3>
                    <p class="text-sm text-gray-600">Sistema completo de registro e acompanhamento.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <i class="fas fa-exclamation-triangle text-3xl text-yellow-600 mb-4"></i>
                    <h3 class="font-semibold mb-2">Alertas</h3>
                    <p class="text-sm text-gray-600">Notificações e alertas em tempo real.</p>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <i class="fas fa-chart-line text-3xl text-purple-600 mb-4"></i>
                    <h3 class="font-semibold mb-2">Relatórios</h3>
                    <p class="text-sm text-gray-600">Relatórios e estatísticas detalhadas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-4 text-center">
            <div class="flex justify-center items-center space-x-4 mb-4">
                <i class="fas fa-shield-halved text-2xl"></i>
                <span class="text-xl font-bold">Sistema SMART</span>
            </div>
            <p class="text-gray-300 mb-4">
                Município de Araçoiaba da Serra - Secretaria de Segurança Pública
            </p>
            <p class="text-sm text-gray-400">
                © 2025 Sistema SMART - Guarda Civil Municipal. Todos os direitos reservados.
            </p>
        </div>
    </footer>
</body>
</html> 