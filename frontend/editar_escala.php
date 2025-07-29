<?php
require_once 'auth_check.php';
require_once 'config.php';

requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die('ID inválido!');
}

$stmt = $pdo->prepare("SELECT * FROM escalas WHERE id = ?");
$stmt->execute([$id]);
$escala = $stmt->fetch();

if (!$escala) {
    die('Escala não encontrada!');
}

// Atualizar escala
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $setor = trim($_POST['setor_nome_livre']);
    $responsavel = trim($_POST['responsavel_nome_livre']);
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $turno = trim($_POST['turno']);
    $observacoes = trim($_POST['observacoes']);
    $stmt = $pdo->prepare("UPDATE escalas SET nome = ?, setor_nome_livre = ?, responsavel_nome_livre = ?, data_inicio = ?, data_fim = ?, turno = ?, observacoes = ? WHERE id = ?");
    $stmt->execute([$nome, $setor, $responsavel, $data_inicio, $data_fim, $turno, $observacoes, $id]);
    header("Location: escalas.php?msg=editada");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Escala</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8 mt-8">
        <h2 class="text-2xl font-bold mb-6 text-blue-900">Editar Escala</h2>
        <form method="POST">
            <div class="mb-4">
                <label class="block font-semibold mb-1">Nome:</label>
                <input type="text" name="nome" class="border rounded px-3 py-2 w-full" required value="<?= htmlspecialchars($escala['nome']) ?>">
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Setor:</label>
                <input type="text" name="setor_nome_livre" class="border rounded px-3 py-2 w-full" required value="<?= htmlspecialchars($escala['setor_nome_livre']) ?>">
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Responsável:</label>
                <input type="text" name="responsavel_nome_livre" class="border rounded px-3 py-2 w-full" required value="<?= htmlspecialchars($escala['responsavel_nome_livre']) ?>">
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Data Início:</label>
                <input type="date" name="data_inicio" class="border rounded px-3 py-2 w-full" required value="<?= htmlspecialchars($escala['data_inicio']) ?>">
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Data Fim:</label>
                <input type="date" name="data_fim" class="border rounded px-3 py-2 w-full" required value="<?= htmlspecialchars($escala['data_fim']) ?>">
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Turno:</label>
                <input type="text" name="turno" class="border rounded px-3 py-2 w-full" required value="<?= htmlspecialchars($escala['turno']) ?>">
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Observações:</label>
                <textarea name="observacoes" class="border rounded px-3 py-2 w-full" rows="3"><?= htmlspecialchars($escala['observacoes']) ?></textarea>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded">
                Salvar Alterações
            </button>
            <a href="escalas.php" class="ml-4 text-gray-600 hover:underline">Cancelar</a>
        </form>
    </div>
</body>
</html> 