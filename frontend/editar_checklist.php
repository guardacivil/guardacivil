<?php
require_once 'auth_check.php';
require_once 'config_mysql.php';

requireLogin();

$currentUser = getCurrentUser();
$isAdmin = isAdminLoggedIn() || ($currentUser['perfil'] ?? '') === 'Administrador';

if (!$isAdmin) {
    die('Acesso restrito!');
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die('ID inválido!');
}

// Buscar checklist
$stmt = $pdo->prepare("SELECT * FROM checklist_viatura WHERE id = ?");
$stmt->execute([$id]);
$checklist = $stmt->fetch();

if (!$checklist) {
    die('Checklist não encontrado!');
}

// Itens do checklist
$itens_4_rodas = [
    'Documentação', 'Extintor', 'Macaco', 'Chave de roda', 'Estepe', 'Triângulo',
    'Luzes', 'Setas', 'Faróis', 'Lanternas', 'Sirene', 'Giroflex',
    'Freios', 'Pneus', 'Calotas', 'Retrovisores', 'Vidros', 'Palhetas',
    'Lataria', 'Portas', 'Bancos', 'Cintos de segurança', 'Rádio comunicador',
    'GPS', 'Câmera embarcada', 'Combustível', 'Óleo', 'Água do radiador', 'Ferramentas',
    'Cabo de chupeta', 'Lanterna portátil', 'Kit primeiros socorros', 'Coletes', 'Cone', 'Corrente', 'Cadeado', 'Outros'
];
$itens_2_rodas = [
    'Documentação', 'Capacete', 'Luzes', 'Setas', 'Faróis', 'Lanternas',
    'Freios', 'Pneus', 'Retrovisores', 'Lataria', 'Bancos', 'Cintos de segurança',
    'Rádio comunicador', 'Combustível', 'Óleo', 'Ferramentas', 'Kit primeiros socorros', 'Outros'
];

// Atualizar checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $placa = trim($_POST['placa']);
    $km = trim($_POST['km']);
    $observacoes = trim($_POST['observacoes']);
    $itens = isset($_POST['item']) ? $_POST['item'] : [];
    $itens_json = json_encode($itens);
    $stmt = $pdo->prepare("UPDATE checklist_viatura SET placa = ?, km = ?, observacoes = ?, itens = ? WHERE id = ?");
    $stmt->execute([$placa, $km, $observacoes, $itens_json, $id]);
    header("Location: checklist_viatura.php?msg=editado");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Checklist</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-xl mx-auto bg-white rounded-lg shadow-md p-8 mt-8">
        <h2 class="text-2xl font-bold mb-6 text-blue-900">Editar Checklist</h2>
        <form method="POST">
            <div class="mb-4">
                <label class="block font-semibold mb-1">Placa:</label>
                <input type="text" name="placa" class="border rounded px-3 py-2 w-full" required maxlength="10" value="<?= htmlspecialchars($checklist['placa']) ?>">
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">KM Atual:</label>
                <input type="number" name="km" class="border rounded px-3 py-2 w-full" required min="0" value="<?= htmlspecialchars($checklist['km']) ?>">
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Itens a verificar:</label>
                <div id="itens_checklist">
                <?php
                $tipo = $checklist['tipo_veiculo'];
                $itens_possiveis = $tipo === '2_rodas' ? $itens_2_rodas : $itens_4_rodas;
                $itens_marcados = json_decode($checklist['itens'], true) ?: [];
                foreach ($itens_possiveis as $item) {
                    $checked = in_array($item, $itens_marcados) ? 'checked' : '';
                    $id_item = 'item_' . preg_replace('/\s+/', '_', strtolower($item));
                    echo '<label class="inline-flex items-center mr-4 mb-2">';
                    echo '<input type="checkbox" name="item[]" value="' . htmlspecialchars($item) . '" class="mr-2" ' . $checked . '> ';
                    echo htmlspecialchars($item);
                    echo '</label>';
                }
                ?>
                </div>
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Observações:</label>
                <textarea name="observacoes" class="border rounded px-3 py-2 w-full" rows="3"><?= htmlspecialchars($checklist['observacoes']) ?></textarea>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded">
                Salvar Alterações
            </button>
            <a href="checklist_viatura.php" class="ml-4 text-gray-600 hover:underline">Cancelar</a>
        </form>
    </div>
</body>
</html> 