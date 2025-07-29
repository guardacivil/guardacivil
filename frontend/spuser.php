<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Administração - Sistema SMART</title>
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
        }

        /* Sidebar */
        .sidebar {
            background-color: var(--dark-color);
            color: var(--light-color);
            min-width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            box-shadow: 2px 0 12px rgba(0,0,0,0.2);
            z-index: 30;
        }

        .sidebar a {
            transition: background-color 0.3s ease;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: var(--primary-color);
            color: white;
        }

        /* Content */
        .content {
            margin-left: 250px;
            padding: 2rem;
        }

        /* Scrollbar estilizada para sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: var(--dark-color);
        }
        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--primary-color);
            border-radius: 3px;
        }

        /* Navbar */
        .nav-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar flex flex-col">
        <div class="flex items-center justify-center h-20 border-b border-gray-700">
            <img src="img/logo.png" alt="Logo Araçoiaba da Serra" class="h-12 w-12 object-contain mr-3" />
            <h1 class="text-xl font-bold">Sistema SMART</h1>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-primary-color active">
                <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
            </a>
            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-primary-color">
                <i class="fas fa-users mr-3"></i> Gestão de Usuários
            </a>
            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-primary-color">
                <i class="fas fa-user-shield mr-3"></i> Perfis e Permissões
            </a>
            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-primary-color">
                <i class="fas fa-file-alt mr-3"></i> Logs do Sistema
            </a>
            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-primary-color">
                <i class="fas fa-cogs mr-3"></i> Configurações Gerais
            </a>
            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-primary-color">
                <i class="fas fa-database mr-3"></i> Banco de Dados
            </a>
            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-primary-color">
                <i class="fas fa-bell mr-3"></i> Alertas e Notificações
            </a>
            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-primary-color">
                <i class="fas fa-question-circle mr-3"></i> Suporte
            </a>
            <a href="#" class="flex items-center px-3 py-2 rounded hover:bg-primary-color">
                <i class="fas fa-sign-out-alt mr-3"></i> Sair
            </a>
        </nav>

        <div class="px-4 py-4 border-t border-gray-700 text-sm text-center">
            © 2025 Sistema SMART<br />
            Prefeitura de Araçoiaba da Serra
        </div>
    </aside>

    <!-- Main content -->
    <main class="content">
        <header class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Painel Administrativo</h2>
            <div class="text-gray-600 text-sm">Olá, Administrador</div>
        </header>

        <section class="bg-white rounded-xl p-6 shadow-md">
            <h3 class="text-xl font-semibold mb-4">Resumo do Sistema</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-blue-100 rounded p-4">
                    <h4 class="font-bold text-blue-900">Usuários Cadastrados</h4>
                    <p class="text-3xl mt-2">120</p>
                </div>
                <div class="bg-green-100 rounded p-4">
                    <h4 class="font-bold text-green-900">Perfis Ativos</h4>
                    <p class="text-3xl mt-2">8</p>
                </div>
                <div class="bg-yellow-100 rounded p-4">
                    <h4 class="font-bold text-yellow-900">Alertas Pendentes</h4>
                    <p class="text-3xl mt-2">5</p>
                </div>
            </div>
        </section>

        <section class="mt-8 bg-white rounded-xl p-6 shadow-md">
            <h3 class="text-xl font-semibold mb-4">Ações Rápidas</h3>
            <div class="flex flex-wrap gap-4">
                <button class="btn-primary text-white px-5 py-3 rounded flex items-center hover:bg-blue-700 transition">
                    <i class="fas fa-user-plus mr-2"></i> Novo Usuário
                </button>
                <button class="btn-primary text-white px-5 py-3 rounded flex items-center hover:bg-blue-700 transition">
                    <i class="fas fa-cog mr-2"></i> Configurações
                </button>
                <button class="btn-primary text-white px-5 py-3 rounded flex items-center hover:bg-blue-700 transition">
                    <i class="fas fa-file-alt mr-2"></i> Ver Logs
                </button>
            </div>
        </section>

        <section class="mt-8 bg-white rounded-xl p-6 shadow-md">
            <h3 class="text-xl font-semibold mb-4">Últimas Atividades</h3>
            <table class="min-w-full table-auto border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-4 py-2 text-left">Usuário</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Ação</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Data / Hora</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2">joaodasilva</td>
                        <td class="border border-gray-300 px-4 py-2">Criou novo usuário</td>
                        <td class="border border-gray-300 px-4 py-2">26/06/2025 10:15</td>
                        <td class="border border-gray-300 px-4 py-2 text-green-600 font-semibold">Sucesso</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2">mariarosa</td>
                        <td class="border border-gray-300 px-4 py-2">Alterou permissões</td>
                        <td class="border border-gray-300 px-4 py-2">25/06/2025 15:47</td>
                        <td class="border border-gray-300 px-4 py-2 text-yellow-600 font-semibold">Pendente</td>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2">carlosm</td>
                        <td class="border border-gray-300 px-4 py-2">Resetou senha</td>
                        <td class="border border-gray-300 px-4 py-2">24/06/2025 09:03</td>
                        <td class="border border-gray-300 px-4 py-2 text-green-600 font-semibold">Sucesso</td>
                    </tr>
                </tbody>
            </table>
        </section>

    </main>

</body>
</html>
