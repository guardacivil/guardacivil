<?php
require_once 'auth_check.php';
require_once 'config_mysql.php';

requireLogin();

$currentUser = getCurrentUser();
$userId = $currentUser['id'] ?? 0;

// Buscar ocorrências registradas pelo usuário logado
$stmt = $pdo->prepare("SELECT * FROM ocorrencias WHERE usuario_id = ? ORDER BY data_registro DESC");
$stmt->execute([$userId]);
$ocorrencias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Minhas Ocorrências</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        main.content {
            margin-left: 16rem;
            padding: 2rem;
            width: calc(100% - 16rem);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
<?php include 'sidebar.php'; ?>
<main class="content">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-8 mt-8">
        <h2 class="text-2xl font-bold mb-6 text-blue-900 flex items-center">
            <i class="fas fa-clipboard-list mr-2"></i> Minhas Ocorrências
        </h2>
        <?php if (empty($ocorrencias)): ?>
            <div class="text-gray-500">Nenhuma ocorrência registrada por você.</div>
        <?php else: ?>
            <table class="min-w-full bg-white border border-gray-200 rounded">
                <thead>
                    <tr class="bg-blue-50">
                        <th class="px-4 py-2 border-b">Nº</th>
                        <th class="px-4 py-2 border-b">Data</th>
                        <th class="px-4 py-2 border-b">Natureza</th>
                        <th class="px-4 py-2 border-b">Local</th>
                        <th class="px-4 py-2 border-b">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ocorrencias as $o): ?>
                        <tr>
                            <td class="px-4 py-2 border-b text-center font-semibold text-blue-800"><?= htmlspecialchars($o['numero_ocorrencia']) ?></td>
                            <td class="px-4 py-2 border-b text-center"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($o['data_registro']))) ?></td>
                            <td class="px-4 py-2 border-b text-center"><?= htmlspecialchars($o['natureza']) ?></td>
                            <td class="px-4 py-2 border-b text-center"><?= htmlspecialchars($o['local']) ?></td>
                            <td class="px-4 py-2 border-b text-center">
                                <a href="ver_ocorrencia.php?id=<?= urlencode($o['id']) ?>" class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors text-sm" title="Ver Detalhes"><i class="fas fa-eye mr-1"></i>Ver</a>
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