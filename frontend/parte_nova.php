<?php
require_once 'auth_check.php';
require_once 'config.php';
requireLogin();

$currentUser = getCurrentUser();

// Gerar número sequencial da parte (reinicia a cada ano)
$ano_vigente = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM partes WHERE numero LIKE ?");
$stmt->execute(['%/' . $ano_vigente]);
$row = $stmt->fetch();
$numero_parte = str_pad(($row['total'] ?? 0) + 1, 4, '0', STR_PAD_LEFT) . '/' . $ano_vigente;

$data_atual = date('d/m/Y');
$hora_atual = date('H:i');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Salvar os dados no banco
    $numero = $numero_parte;
    $data = date('Y-m-d');
    $hora = date('H:i');
    $do_usuario_id = $currentUser['id'];
    $do_nome = $currentUser['nome'];
    $ao = $_POST['ao'] ?? '';
    $assunto = $_POST['assunto'] ?? '';
    $referencia = $_POST['referencia'] ?? '';
    $relato = $_POST['relato'] ?? '';
    $assinatura = $currentUser['nome'];

    $stmt = $pdo->prepare("INSERT INTO partes (numero, data, hora, do_usuario_id, do_nome, ao, assunto, referencia, relato, assinatura) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$numero, $data, $hora, $do_usuario_id, $do_nome, $ao, $assunto, $referencia, $relato, $assinatura]);

    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">Parte registrada com sucesso!</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Parte - Sistema Integrado da Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100">
    <main class="max-w-2xl mx-auto bg-white p-8 mt-10 rounded shadow">
        <!-- Cabeçalho padrão -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <img src="img/logo1.png" alt="Logo Guarda Civil" class="h-16 mb-2">
                <h2 class="text-xl font-bold">Guarda Civil Municipal<br>Município de Araçoiaba da Serra</h2>
            </div>
            <div class="text-right">
                <div class="text-gray-700 font-semibold">Data: <span><?= $data_atual ?></span></div>
                <div class="text-gray-700 font-semibold">Nº Parte: <span><?= $numero_parte ?></span></div>
            </div>
        </div>
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Do:</label>
                    <input type="text" name="do" value="<?= htmlspecialchars($currentUser['nome']) ?>" readonly class="w-full border px-3 py-2 rounded bg-gray-100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ao:</label>
                    <select name="ao" required class="w-full border px-3 py-2 rounded">
                        <option value="">Selecione...</option>
                        <option value="Secretário">Secretário</option>
                        <option value="Comando">Comando</option>
                        <option value="Administrativo">Administrativo</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assunto:</label>
                <input type="text" name="assunto" required class="w-full border px-3 py-2 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Referência:</label>
                <input type="text" name="referencia" required class="w-full border px-3 py-2 rounded">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Relato:</label>
                <textarea name="relato" rows="5" required class="w-full border px-3 py-2 rounded"></textarea>
            </div>
            <div class="flex items-center justify-between mt-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assinatura:</label>
                    <input type="text" name="assinatura" value="<?= htmlspecialchars($currentUser['nome']) ?>" readonly class="w-64 border px-3 py-2 rounded bg-gray-100">
                </div>
                <div class="text-right">
                    <div class="text-gray-700 font-semibold">Data: <span><?= $data_atual ?></span></div>
                    <div class="text-gray-700 font-semibold">Hora: <span><?= $hora_atual ?></span></div>
                </div>
            </div>
            <div class="flex justify-between mt-6">
                <a href="dashboard.php" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Voltar
                </a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Salvar Parte
                </button>
            </div>
        </form>
    </main>
</body>
</html> 