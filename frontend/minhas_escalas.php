<?php
// minhas_escalas.php - Minhas Escalas
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();
$usuario_nome = isset($currentUser['nome']) ? $currentUser['nome'] : '';
$perfil = $currentUser['perfil'] ?? '';
$isAdmin = isAdminLoggedIn() || $perfil === 'Administrador';

$escalas = [];
if ($isAdmin || in_array($perfil, ['Comando', 'Secretário', 'Suporte'])) {
    $stmt = $pdo->query("SELECT * FROM escalas ORDER BY data_inicio DESC");
} else {
    $stmt = $pdo->query("SELECT * FROM escalas WHERE publicada = 1 ORDER BY data_inicio DESC");
}
$escalas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Minhas Escalas - Sistema Integrado da Guarda Civil</title>
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
        main.content {
            margin-left: 16rem;
            padding: 2rem;
            width: calc(100% - 16rem);
        }
        .btn-primary {
            background-color: var(--primary-color);
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <main class="content">
        <header class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold mb-8"><i class="fas fa-calendar-day mr-2"></i>Minhas Escalas</h2>
            <div class="text-gray-600 text-sm">
                Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
                (<?= htmlspecialchars($currentUser['perfil']) ?>)
            </div>
        </header>

        <!-- Próximas Escalas -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-xl font-semibold mb-4">Próximas Escalas</h3>
            <div class="space-y-4">
                <?php 
                $encontrou_escalas = false;
                foreach ($escalas as $escala): 
                    $data_inicio = strtotime($escala['data_inicio']);
                    if ($data_inicio >= time()):
                        $encontrou_escalas = true;
                ?>
                <div class="border-l-4 border-blue-500 pl-4 py-2">
                    <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($escala['nome']) ?></h4>
                    <p class="text-sm text-gray-600">
                        <?= date('d/m/Y', $data_inicio) ?> - <?= ucfirst($escala['turno']) ?>
                    </p>
                    <p class="text-xs text-gray-500">Setor: <?= htmlspecialchars($escala['setor_nome'] ?? 'Todos') ?></p>
                </div>
                <?php 
                    endif;
                endforeach; 
                
                if (!$encontrou_escalas):
                ?>
                <p class="text-gray-500 text-center py-4">Nenhuma escala futura encontrada.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Calendário de Escalas -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold mb-4">Calendário de Escalas</h3>
            <?php if (empty($escalas)): ?>
                <div class="text-center py-8">
                    <i class="fas fa-calendar-alt text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">Nenhuma escala encontrada.</p>
                </div>
            <?php else: ?>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr>
                            <th class="px-2 py-1">Data</th>
                            <th class="px-2 py-1">Turno</th>
                            <th class="px-2 py-1">Escala</th>
                            <th class="px-2 py-1">Setor</th>
                            <th class="px-2 py-1 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($escalas as $e): ?>
                        <tr>
                            <td class="px-2 py-1"><?= htmlspecialchars(date('d/m/Y', strtotime($e['data_inicio']))) ?></td>
                            <td class="px-2 py-1"><?= htmlspecialchars($e['turno']) ?></td>
                            <td class="px-2 py-1"><?= htmlspecialchars($e['nome']) ?></td>
                            <td class="px-2 py-1"><?= htmlspecialchars($e['setor_nome_livre']) ?></td>
                            <td class="px-2 py-1 text-center">
                                <a href="ver_escala.php?id=<?= $e['id'] ?>" class="inline-flex items-center px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-800 text-xs mr-2"><i class="fas fa-eye mr-1"></i>Ver</a>
                                <a href="gerar_pdf_escala.php?id=<?= $e['id'] ?>" target="_blank" class="inline-flex items-center px-2 py-1 bg-green-600 text-white rounded hover:bg-green-800 text-xs"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

</body>
</html> 