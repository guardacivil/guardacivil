<?php
require_once 'auth_check.php';
require_once 'config_mysql.php';

date_default_timezone_set('America/Sao_Paulo');

requireLogin();

$currentUser = getCurrentUser();
$userId = $currentUser['id'] ?? 0;
$isAdmin = isAdminLoggedIn() || ($currentUser['perfil'] ?? '') === 'Administrador';

// Salvar checklist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_veiculo'])) {
    $tipo = $_POST['tipo_veiculo'];
    $placa = trim($_POST['placa']);
    $km = trim($_POST['km']);
    $itens = isset($_POST['item']) ? $_POST['item'] : [];
    $observacoes = trim($_POST['observacoes']);
    $data = date('Y-m-d H:i:s');
    $itens_json = json_encode($itens);
    // Upload de fotos
    $nomes_fotos = [];
    if (!empty($_FILES['fotos']['name'][0])) {
        $total = count($_FILES['fotos']['name']);
        if ($total > 15) $total = 15;
        for ($i = 0; $i < $total; $i++) {
            $nomeTmp = $_FILES['fotos']['tmp_name'][$i];
            $nomeOriginal = $_FILES['fotos']['name'][$i];
            $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif'])) {
                $nomeFinal = uniqid('checklist_') . '.' . $ext;
                move_uploaded_file($nomeTmp, __DIR__ . '/../uploads/' . $nomeFinal);
                $nomes_fotos[] = $nomeFinal;
            }
        }
    }
    $fotos_json = json_encode($nomes_fotos);
    $stmt = $pdo->prepare("INSERT INTO checklist_viatura (usuario_id, tipo_veiculo, placa, km, itens, observacoes, fotos, data_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $tipo, $placa, $km, $itens_json, $observacoes, $fotos_json, $data]);
    $msg = "Checklist registrado com sucesso!";
}

// Buscar checklists
if ($isAdmin) {
    $stmt = $pdo->query("SELECT c.*, u.nome as usuario_nome FROM checklist_viatura c LEFT JOIN usuarios u ON c.usuario_id = u.id ORDER BY c.data_registro DESC");
    $checklists = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT c.*, u.nome as usuario_nome FROM checklist_viatura c LEFT JOIN usuarios u ON c.usuario_id = u.id WHERE c.usuario_id = ? ORDER BY c.data_registro DESC");
    $stmt->execute([$userId]);
    $checklists = $stmt->fetchAll();
}

// Excluir checklist (apenas admin)
if ($isAdmin && isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $idExcluir = intval($_GET['excluir']);
    $stmt = $pdo->prepare("DELETE FROM checklist_viatura WHERE id = ?");
    $stmt->execute([$idExcluir]);
    header("Location: checklist_viatura.php?msg=excluido");
    exit;
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Checklist Viatura</title>
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
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-8 mt-8">
        <h2 class="text-2xl font-bold mb-6 text-blue-900 flex items-center">
            <i class="fas fa-clipboard-check mr-2"></i> Checklist de Viatura
        </h2>
        <?php if (!empty($msg)): ?>
            <div class="mb-4 px-4 py-2 rounded bg-green-100 border border-green-400 text-green-800"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <form method="POST" class="mb-10" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="block font-semibold mb-1">Tipo de Veículo:</label>
                <select name="tipo_veiculo" id="tipo_veiculo" class="border rounded px-3 py-2 w-full" required onchange="atualizarItens()">
                    <option value="4_rodas">Viatura 4 Rodas</option>
                    <option value="2_rodas">Viatura 2 Rodas</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Placa:</label>
                <input type="text" name="placa" class="border rounded px-3 py-2 w-full" required maxlength="10">
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">KM Atual:</label>
                <input type="number" name="km" class="border rounded px-3 py-2 w-full" required min="0">
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Itens a verificar:</label>
                <div id="itens_checklist">
                    <!-- Itens serão preenchidos via JS -->
                </div>
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Observações:</label>
                <textarea name="observacoes" class="border rounded px-3 py-2 w-full" rows="3"></textarea>
            </div>
            <div class="mb-4">
                <label class="block font-semibold mb-1">Fotos (até 15):</label>
                <input type="file" name="fotos[]" accept="image/*" multiple max="15">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded">
                <i class="fas fa-save mr-1"></i> Enviar Checklist
            </button>
        </form>
        <h3 class="text-xl font-semibold mb-4 text-blue-800">Checklists Registrados<?= $isAdmin ? ' (Todos os Usuários)' : '' ?></h3>
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'excluido'): ?>
            <div class="mb-4 px-4 py-2 rounded bg-green-100 border border-green-400 text-green-800">Checklist excluído com sucesso!</div>
        <?php endif; ?>
        <div class="overflow-auto">
            <table class="min-w-full bg-white border border-gray-200 rounded text-xs">
                <thead>
                    <tr class="bg-blue-50">
                        <th class="px-2 py-1 border">Data</th>
                        <th class="px-2 py-1 border">Tipo</th>
                        <th class="px-2 py-1 border">Placa</th>
                        <th class="px-2 py-1 border">KM</th>
                        <th class="px-2 py-1 border">Itens</th>
                        <th class="px-2 py-1 border">Observações</th>
                        <th class="px-2 py-1 border">Usuário</th>
                        <th class="px-2 py-1 border">Fotos</th>
                        <?php if ($isAdmin): ?>
                        <th class="px-2 py-1 border">Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checklists as $c): ?>
                        <tr>
                            <td class="px-2 py-1 border text-center"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['data_registro']))) ?></td>
                            <td class="px-2 py-1 border text-center"><?= $c['tipo_veiculo'] === '4_rodas' ? '4 Rodas' : '2 Rodas' ?></td>
                            <td class="px-2 py-1 border text-center"><?= htmlspecialchars($c['placa']) ?></td>
                            <td class="px-2 py-1 border text-center"><?= htmlspecialchars($c['km']) ?></td>
                            <td class="px-2 py-1 border">
                                <?php $itens = json_decode($c['itens'], true) ?: [];
                                echo implode(', ', array_map('htmlspecialchars', $itens)); ?>
                            </td>
                            <td class="px-2 py-1 border"><?= htmlspecialchars($c['observacoes']) ?></td>
                            <td class="px-2 py-1 border text-center"><?= htmlspecialchars($c['usuario_nome'] ?? '-') ?></td>
                            <td class="px-2 py-1 border text-center">
                                <?php $fotos = json_decode($c['fotos'] ?? '[]', true) ?: [];
                                foreach ($fotos as $foto) {
                                    echo '<a href="../uploads/' . htmlspecialchars($foto) . '" target="_blank"><img src="../uploads/' . htmlspecialchars($foto) . '" style="width:32px;height:32px;object-fit:cover;margin:1px;border-radius:4px;display:inline-block;" alt="foto"></a>';
                                }
                                ?>
                            </td>
                            <?php if ($isAdmin): ?>
                            <td class="px-2 py-1 border text-center">
                                <a href="editar_checklist.php?id=<?= $c['id'] ?>" class="text-blue-600 hover:underline mr-2">Editar</a>
                                <a href="?excluir=<?= $c['id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Tem certeza que deseja excluir este checklist?')">Excluir</a>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<script>
const itens4 = <?= json_encode($itens_4_rodas) ?>;
const itens2 = <?= json_encode($itens_2_rodas) ?>;
function atualizarItens() {
    const tipo = document.getElementById('tipo_veiculo').value;
    const container = document.getElementById('itens_checklist');
    container.innerHTML = '';
    const itens = tipo === '2_rodas' ? itens2 : itens4;
    itens.forEach(function(item) {
        const id = 'item_' + item.replace(/\s+/g, '_').toLowerCase();
        const label = document.createElement('label');
        label.className = 'inline-flex items-center mr-4 mb-2';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'item[]';
        checkbox.value = item;
        checkbox.className = 'mr-2';
        label.appendChild(checkbox);
        label.appendChild(document.createTextNode(item));
        container.appendChild(label);
    });
}
document.addEventListener('DOMContentLoaded', atualizarItens);
document.getElementById('tipo_veiculo').addEventListener('change', atualizarItens);
</script>
</body>
</html> 