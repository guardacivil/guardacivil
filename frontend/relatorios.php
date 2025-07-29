<?php
require_once 'auth_check.php';
require_once 'config.php';
requireLogin();

// Filtros
$setor_filtro = isset($_GET['setor']) ? intval($_GET['setor']) : 0;
$data_ini = $_GET['data_ini'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$where = [];
$params = [];
if ($setor_filtro) { $where[] = 'setor_id = ?'; $params[] = $setor_filtro; }
if ($data_ini) { $where[] = 'data_admissao >= ?'; $params[] = $data_ini; }
if ($data_fim) { $where[] = 'data_admissao <= ?'; $params[] = $data_fim; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Resumos
$total_funcionarios = $pdo->query("SELECT COUNT(*) FROM funcionarios")->fetchColumn();
$ativos = $pdo->query("SELECT COUNT(*) FROM funcionarios WHERE ativo=1")->fetchColumn();
$inativos = $pdo->query("SELECT COUNT(*) FROM funcionarios WHERE ativo=0")->fetchColumn();
$total_escalas = $pdo->query("SELECT COUNT(*) FROM escalas")->fetchColumn();
$total_checklists = $pdo->query("SELECT COUNT(*) FROM checklist_viatura")->fetchColumn();
$total_ocorrencias = $pdo->query("SELECT COUNT(*) FROM ocorrencias")->fetchColumn();

// Funcionários por setor
$setores = $pdo->query("SELECT id, nome FROM setores WHERE ativo=1 ORDER BY nome")->fetchAll();
$setorLabel = [1=>'Guarda Civil',2=>'Secretário',3=>'Estágio',4=>'Outros'];
$func_por_setor = $pdo->query("SELECT setor_id, COUNT(*) as total FROM funcionarios GROUP BY setor_id")->fetchAll();
$ativos_por_setor = $pdo->query("SELECT setor_id, COUNT(*) as total FROM funcionarios WHERE ativo=1 GROUP BY setor_id")->fetchAll();
$inativos_por_setor = $pdo->query("SELECT setor_id, COUNT(*) as total FROM funcionarios WHERE ativo=0 GROUP BY setor_id")->fetchAll();

// Ocorrências por mês
$ocorrencias_mes = $pdo->query("SELECT DATE_FORMAT(data, '%Y-%m') as mes, COUNT(*) as total FROM ocorrencias GROUP BY mes ORDER BY mes")->fetchAll();
// Checklists por mês
$checklists_mes = $pdo->query("SELECT DATE_FORMAT(data_registro, '%Y-%m') as mes, COUNT(*) as total FROM checklist_viatura GROUP BY mes ORDER BY mes")->fetchAll();

// Funcionários detalhados para tabela
$sql = "SELECT f.*, s.nome as setor_nome FROM funcionarios f LEFT JOIN setores s ON f.setor_id = s.id $where_sql ORDER BY f.nome";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$funcionarios = $stmt->fetchAll();

// Ocorrências por bairro (para mapa de calor)
$ocorrencias_bairro = $pdo->query("SELECT bairro, COUNT(*) as total FROM ocorrencias GROUP BY bairro HAVING bairro IS NOT NULL AND bairro != ''")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Relatórios - Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        main.content { margin-left: 16rem; padding: 2rem; width: calc(100% - 16rem); }
        #map { height: 400px; width: 100%; }
        .table-auto th, .table-auto td { padding: 0.5rem; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
<?php include 'sidebar.php'; ?>
<main class="content">
    <h2 class="text-3xl font-bold mb-8 text-blue-900"><i class="fas fa-chart-bar mr-2"></i>Relatórios</h2>
    <!-- Filtros -->
    <form method="get" class="flex flex-wrap gap-4 mb-8 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Setor</label>
            <select name="setor" class="border rounded px-3 py-2">
                <option value="">Todos</option>
                <?php foreach ($setores as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $setor_filtro==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data de Admissão (início)</label>
            <input type="date" name="data_ini" value="<?= htmlspecialchars($data_ini) ?>" class="border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data de Admissão (fim)</label>
            <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>" class="border rounded px-3 py-2">
        </div>
        <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded">Filtrar</button>
    </form>
    <!-- Cards de resumo -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg p-6 shadow-md text-center">
            <h3 class="text-lg font-semibold text-gray-800">Funcionários</h3>
            <p class="text-3xl font-bold text-blue-600"><?= $total_funcionarios ?></p>
            <div class="mt-2 text-sm text-green-700">Ativos: <?= $ativos ?> | Inativos: <?= $inativos ?></div>
        </div>
        <div class="bg-white rounded-lg p-6 shadow-md text-center">
            <h3 class="text-lg font-semibold text-gray-800">Escalas</h3>
            <p class="text-3xl font-bold text-purple-600"><?= $total_escalas ?></p>
        </div>
        <div class="bg-white rounded-lg p-6 shadow-md text-center">
            <h3 class="text-lg font-semibold text-gray-800">Checklists</h3>
            <p class="text-3xl font-bold text-orange-600"><?= $total_checklists ?></p>
        </div>
        <div class="bg-white rounded-lg p-6 shadow-md text-center">
            <h3 class="text-lg font-semibold text-gray-800">Ocorrências</h3>
            <p class="text-3xl font-bold text-red-600"><?= $total_ocorrencias ?></p>
        </div>
    </div>
    <!-- Gráficos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold mb-4 text-blue-800">Funcionários por Setor</h3>
            <canvas id="setorChart" height="100"></canvas>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold mb-4 text-blue-800">Ativos x Inativos</h3>
            <canvas id="ativoChart" height="100"></canvas>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold mb-4 text-blue-800">Ocorrências por Mês</h3>
            <canvas id="ocorrenciaMesChart" height="100"></canvas>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-xl font-semibold mb-4 text-blue-800">Checklists por Mês</h3>
            <canvas id="checklistMesChart" height="100"></canvas>
        </div>
    </div>
    <!-- Mapa de calor de ocorrências -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-xl font-semibold mb-4 text-blue-800">Mapa de Ocorrências por Bairro</h3>
        <div id="map"></div>
    </div>
    <!-- Tabela detalhada -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8 overflow-x-auto">
        <h3 class="text-xl font-semibold mb-4 text-blue-800">Funcionários Detalhados</h3>
        <table class="table-auto min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th>Nome</th><th>Nome de Guerra</th><th>Matrícula</th><th>Setor</th><th>Cargo</th><th>Data Admissão</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($funcionarios as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['nome']) ?></td>
                    <td><?= htmlspecialchars($f['nome_guerra']) ?></td>
                    <td><?= htmlspecialchars($f['matricula']) ?></td>
                    <td><?= htmlspecialchars($f['setor_nome']) ?></td>
                    <td><?= htmlspecialchars($f['cargo']) ?></td>
                    <td><?= $f['data_admissao'] ? date('d/m/Y', strtotime($f['data_admissao'])) : '' ?></td>
                    <td><?= $f['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<script>
// Gráfico de barras de funcionários por setor
const ctx = document.getElementById('setorChart').getContext('2d');
const setorData = {
    labels: [<?php foreach ($func_por_setor as $s) echo "'" . ($setorLabel[$s['setor_id']] ?? 'N/A') . "',"; ?>],
    datasets: [{
        label: 'Funcionários',
        data: [<?php foreach ($func_por_setor as $s) echo $s['total'] . ','; ?>],
        backgroundColor: ['#2563eb', '#9333ea', '#f59e42', '#f43f5e'],
    }]
};
new Chart(ctx, { type: 'bar', data: setorData, options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } } });
// Gráfico de pizza ativos/inativos
const ctx2 = document.getElementById('ativoChart').getContext('2d');
new Chart(ctx2, {
    type: 'pie',
    data: {
        labels: ['Ativos', 'Inativos'],
        datasets: [{
            data: [<?= $ativos ?>, <?= $inativos ?>],
            backgroundColor: ['#22c55e', '#f43f5e'],
        }]
    },
    options: { responsive: true }
});
// Ocorrências por mês
const ctx3 = document.getElementById('ocorrenciaMesChart').getContext('2d');
new Chart(ctx3, {
    type: 'line',
    data: {
        labels: [<?php foreach ($ocorrencias_mes as $o) echo "'" . $o['mes'] . "',"; ?>],
        datasets: [{
            label: 'Ocorrências',
            data: [<?php foreach ($ocorrencias_mes as $o) echo $o['total'] . ','; ?>],
            borderColor: '#f43f5e',
            backgroundColor: '#fca5a5',
            fill: true,
        }]
    },
    options: { responsive: true }
});
// Checklists por mês
const ctx4 = document.getElementById('checklistMesChart').getContext('2d');
new Chart(ctx4, {
    type: 'line',
    data: {
        labels: [<?php foreach ($checklists_mes as $c) echo "'" . $c['mes'] . "',"; ?>],
        datasets: [{
            label: 'Checklists',
            data: [<?php foreach ($checklists_mes as $c) echo $c['total'] . ','; ?>],
            borderColor: '#f59e42',
            backgroundColor: '#fde68a',
            fill: true,
        }]
    },
    options: { responsive: true }
});
// Mapa de calor de ocorrências por bairro
const bairros = <?php echo json_encode($ocorrencias_bairro); ?>;
const bairroCoords = {
    // Exemplo: 'Centro': [-23.5025, -47.6158],
    // Adicione coordenadas reais dos bairros se desejar precisão
};
const map = L.map('map').setView([-23.5025, -47.6158], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '© OpenStreetMap'
}).addTo(map);
bairros.forEach(function(b) {
    if (bairroCoords[b.bairro]) {
        L.circle(bairroCoords[b.bairro], {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.5,
            radius: 200 + b.total * 100
        }).addTo(map).bindPopup(b.bairro + ': ' + b.total + ' ocorrências');
    }
});
</script>
</body>
</html> 