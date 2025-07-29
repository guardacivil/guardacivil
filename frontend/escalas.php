<?php
// escalas.php - Gestão de Escalas da GCM
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Verificar permissão
if (!hasPermission('escalas') && !isAdminLoggedIn()) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Adicionar instrução para criar o campo 'publicada' se não existir
try {
    $pdo->query("ALTER TABLE escalas ADD COLUMN publicada TINYINT(1) DEFAULT 0");
} catch (PDOException $e) {}

// Adicionar campo 'detalhes' se não existir
try {
    $pdo->query("ALTER TABLE escalas ADD COLUMN detalhes TEXT");
} catch (PDOException $e) {}

// Publicar escala
if (isset($_GET['publicar']) && is_numeric($_GET['publicar'])) {
    $id = intval($_GET['publicar']);
    $stmt = $pdo->prepare("UPDATE escalas SET publicada = 1 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: escalas.php?msg=publicada');
    exit;
}
// Remover publicação da escala
if (isset($_GET['despublicar']) && is_numeric($_GET['despublicar'])) {
    $id = intval($_GET['despublicar']);
    $stmt = $pdo->prepare("UPDATE escalas SET publicada = 0 WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: escalas.php?msg=despublicada');
    exit;
}
// Excluir escala
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $stmt = $pdo->prepare("DELETE FROM escalas WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: escalas.php?msg=excluida');
    exit;
}

// 1. Processar o POST do formulário para salvar nova escala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data_inicio'], $_POST['data_fim'], $_POST['comandante_geral'], $_POST['setor_nome_livre'], $_POST['responsavel_nome_livre'])) {
    $nome = 'Escala ' . date('d/m/Y', strtotime($_POST['data_inicio'])) . ' a ' . date('d/m/Y', strtotime($_POST['data_fim']));
    $setor_nome_livre = $_POST['setor_nome_livre'];
    $responsavel_nome_livre = $_POST['responsavel_nome_livre'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $turno = 'integral';
    $observacoes = isset($_POST['avisos']) ? $_POST['avisos'] : '';
    $status = 'ativa';
    $comandante_geral = $_POST['comandante_geral'];
    // Salvar todos os campos extras do formulário em detalhes (JSON)
    $detalhes = $_POST;
    unset($detalhes['data_inicio'], $detalhes['data_fim'], $detalhes['comandante_geral'], $detalhes['setor_nome_livre'], $detalhes['responsavel_nome_livre'], $detalhes['avisos']);
    $detalhes_json = json_encode($detalhes, JSON_UNESCAPED_UNICODE);
    try {
        $stmt = $pdo->prepare("INSERT INTO escalas (nome, setor_nome_livre, responsavel_nome_livre, data_inicio, data_fim, turno, observacoes, status, detalhes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$nome, $setor_nome_livre, $responsavel_nome_livre, $data_inicio, $data_fim, $turno, $observacoes, $status, $detalhes_json]);
    } catch (PDOException $e) {
        echo "<div style='color:red'>Erro ao salvar escala: " . $e->getMessage() . "</div>";
    }
    header('Location: escalas.php?msg=salvo');
    exit;
}

// 2. Buscar todas as escalas, sem filtro de publicação
try {
    $sql = "SELECT * FROM escalas ORDER BY data_inicio DESC";
    
    $stmt = $pdo->query($sql);
    $escalas = $stmt->fetchAll();
    
    // Buscar setores
    $stmt = $pdo->query("SELECT * FROM setores WHERE ativo = 1 ORDER BY nome");
    $setores = $stmt->fetchAll();
    
    // Buscar pessoal
    $stmt = $pdo->query("SELECT u.*, g.nome as graduacao_nome FROM usuarios u LEFT JOIN graduacoes g ON u.graduacao_id = g.id WHERE u.ativo = 1 ORDER BY g.nivel DESC, u.nome");
    $pessoal = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erro ao buscar escalas: " . $e->getMessage());
    if (!isset($escalas)) $escalas = [];
    if (!isset($setores)) $setores = [];
    if (!isset($pessoal)) $pessoal = [];
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gestão de Escalas - Sistema Integrado da Guarda Civil</title>
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
        /* Sidebar externo */
        aside.sidebar {
            width: 16rem;
            background-color: #1e40af;
            color: white;
            height: 100vh;
            padding: 1.25rem;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            box-shadow: 2px 0 12px rgba(0,0,0,0.2);
            z-index: 30;
        }
        aside.sidebar .logo-container {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        aside.sidebar .logo-container img {
            width: 10.14rem;
            margin: 0 auto 0.5rem auto;
            display: block;
        }
        aside.sidebar .logo-container h1 {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        aside.sidebar .logo-container p {
            font-size: 0.875rem;
            color: #bfdbfe;
            margin: 0;
        }
        aside.sidebar nav a {
            display: block;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        aside.sidebar nav a:hover {
            background-color: #2563eb;
        }
        aside.sidebar nav a.active {
            background-color: #2563eb;
        }
        aside.sidebar nav a.logout {
            background-color: #dc2626;
        }
        aside.sidebar nav a.logout:hover {
            background-color: #b91c1c;
        }
        /* Conteúdo principal */
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

    <?php include 'sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <main class="content">
        <header class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold text-gray-800"><i class="fas fa-calendar-check mr-2"></i>Gestão de Escalas</h2>
            <div class="text-gray-600 text-sm">
                Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
                (<?= htmlspecialchars($currentUser['perfil']) ?>)
            </div>
        </header>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Escalas Ativas</h3>
                <p class="text-3xl font-bold text-blue-600"><?= count($escalas) ?></p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Escalas da Semana</h3>
                <p class="text-3xl font-bold text-green-600"><?= count(array_filter($escalas, function($e) { 
                    $data_inicio = strtotime($e['data_inicio']);
                    $semana_inicio = strtotime('monday this week');
                    $semana_fim = strtotime('sunday this week');
                    return $data_inicio >= $semana_inicio && $data_inicio <= $semana_fim;
                })) ?></p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Pessoal Escalado Hoje</h3>
                <p class="text-3xl font-bold text-orange-600">0</p>
            </div>
            <div class="bg-white rounded-lg p-6 shadow-md">
                <h3 class="text-lg font-semibold text-gray-800">Setores</h3>
                <p class="text-3xl font-bold text-purple-600"><?= is_array($setores) ? count($setores) : 0 ?></p>
            </div>
        </div>

        <!-- Lista de Escalas -->
        <div class="space-y-4">
            <?php foreach ($escalas as $escala): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-4 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-semibold"><?= htmlspecialchars($escala['nome']) ?></h3>
                    <p class="text-sm text-gray-600">
                        Responsável: <?= htmlspecialchars($escala['responsavel_nome_livre'] ?? '') ?> | 
                        Setor: <?= htmlspecialchars($escala['setor_nome_livre'] ?? '') ?>
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="ver_escala.php?id=<?= $escala['id'] ?>" class="text-blue-600 hover:underline">Ver</a>
                    <a href="editar_escala.php?id=<?= $escala['id'] ?>" class="text-orange-600 hover:underline">Editar</a>
                    <?php if (empty($escala['publicada'])): ?>
                        <a href="escalas.php?publicar=<?= $escala['id'] ?>" class="text-green-600 hover:underline">Publicar</a>
                    <?php else: ?>
                        <a href="escalas.php?despublicar=<?= $escala['id'] ?>" class="text-yellow-600 hover:underline">Remover publicação</a>
                    <?php endif; ?>
                    <a href="escalas.php?excluir=<?= $escala['id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Excluir esta escala?')">Excluir</a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($escalas)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-calendar-alt text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhuma escala encontrada</h3>
                <p class="text-gray-500">Crie a primeira escala para começar.</p>
            </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Modal de Nova/Edição Escala -->
    <div id="modalEscala" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800" id="modalEscalaTitle">Nova Escala</h3>
          </div>
          <form id="formEscala" class="p-6">
            <input type="hidden" id="escala_id" name="id">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Nome da Escala *</label>
              <input type="text" id="nome_escala" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Setor *</label>
              <select id="setor_escala" name="setor_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Selecione...</option>
                <?php foreach ($setores as $s): ?>
                  <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Responsável *</label>
              <select id="responsavel_escala" name="responsavel_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Selecione...</option>
                <?php foreach ($pessoal as $p): ?>
                  <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Início *</label>
                <input type="date" id="data_inicio_escala" name="data_inicio" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Fim *</label>
                <input type="date" id="data_fim_escala" name="data_fim" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
              </div>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Turno *</label>
              <select id="turno_escala" name="turno" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Selecione...</option>
                <option value="manha">Manhã</option>
                <option value="tarde">Tarde</option>
                <option value="noite">Noite</option>
                <option value="madrugada">Madrugada</option>
                <option value="integral">Integral</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
              <textarea id="observacoes_escala" name="observacoes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Membros da Escala *</label>
              <select id="membros_escala" name="membros[]" multiple required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 h-32">
                <?php foreach ($pessoal as $p): ?>
                  <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['graduacao_nome']) ?>)</option>
                <?php endforeach; ?>
              </select>
              <small class="text-gray-500">Segure Ctrl (Windows) ou Command (Mac) para selecionar múltiplos.</small>
            </div>
            <div id="feedbackEscala" class="mt-2 text-center text-sm"></div>
            <div class="mt-6 flex justify-end gap-3">
              <button type="button" onclick="fecharModalEscala()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Salvar</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal de Pessoal da Escala -->
    <div id="modalPessoalEscala" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-lg w-full p-6" id="modalPessoalContent"></div>
        </div>
    </div>

    <!-- Botão Nova Escala -->
    <div class="max-w-5xl mx-auto mt-8 text-right">
      <button id="btnNovaEscala" type="button" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-8 rounded">
        Nova Escala
      </button>
    </div>

    <!-- Formulário de Escala (inicialmente oculto) -->
    <div id="formNovaEscala" class="max-w-5xl mx-auto bg-white rounded-lg shadow-md p-8 mt-8 hidden">
      <h2 class="text-2xl font-bold mb-6 text-blue-900">Cadastro de Escala de Serviço</h2>
      <form method="POST" action="escalas.php">
        <!-- Período e Comandante -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div>
            <label class="block font-semibold mb-1">Data Início:</label>
            <input type="date" name="data_inicio" class="border rounded px-3 py-2 w-full" required>
          </div>
          <div>
            <label class="block font-semibold mb-1">Data Fim:</label>
            <input type="date" name="data_fim" class="border rounded px-3 py-2 w-full" required>
          </div>
          <div>
            <label class="block font-semibold mb-1">Comandante Geral:</label>
            <input type="text" name="comandante_geral" class="border rounded px-3 py-2 w-full" required>
          </div>
          <div>
            <label class="block font-semibold mb-1">Setor:</label>
            <input type="text" name="setor_nome_livre" class="border rounded px-3 py-2 w-full" required>
          </div>
          <div>
            <label class="block font-semibold mb-1">Responsável:</label>
            <input type="text" name="responsavel_nome_livre" class="border rounded px-3 py-2 w-full" required>
          </div>
        </div>
        <!-- Equipes -->
        <h3 class="text-lg font-bold mt-6 mb-2 text-blue-800">Equipes</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div class="border rounded p-4">
            <label class="block font-semibold mb-1">Equipe A Diurna Impar (06x18):</label>
            <input type="text" name="eq_a_diurna_impar" class="border rounded px-3 py-2 w-full mb-2" placeholder="Responsável">
            <label class="block font-semibold mb-1">Supervisor:</label>
            <input type="text" name="supervisor_a" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">CAD:</label>
            <input type="text" name="cad_a" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">Encarregado de VTR:</label>
            <input type="text" name="encarregado_vtr_a" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">Motorista de VTR:</label>
            <input type="text" name="motorista_vtr_a" class="border rounded px-3 py-2 w-full">
          </div>
          <div class="border rounded p-4">
            <label class="block font-semibold mb-1">Equipe B Noturna Impar (18x06):</label>
            <input type="text" name="eq_b_noturna_impar" class="border rounded px-3 py-2 w-full mb-2" placeholder="Responsável">
            <label class="block font-semibold mb-1">Supervisor:</label>
            <input type="text" name="supervisor_b" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">CAD:</label>
            <input type="text" name="cad_b" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">Encarregado de VTR:</label>
            <input type="text" name="encarregado_vtr_b" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">Motorista de VTR:</label>
            <input type="text" name="motorista_vtr_b" class="border rounded px-3 py-2 w-full">
          </div>
          <div class="border rounded p-4">
            <label class="block font-semibold mb-1">Equipe C Diurna Par (06x18):</label>
            <input type="text" name="eq_c_diurna_par" class="border rounded px-3 py-2 w-full mb-2" placeholder="Responsável">
            <label class="block font-semibold mb-1">Supervisor:</label>
            <input type="text" name="supervisor_c" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">CAD:</label>
            <input type="text" name="cad_c" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">Encarregado de VTR:</label>
            <input type="text" name="encarregado_vtr_c" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">Motorista de VTR:</label>
            <input type="text" name="motorista_vtr_c" class="border rounded px-3 py-2 w-full">
          </div>
          <div class="border rounded p-4">
            <label class="block font-semibold mb-1">Equipe D Noturna Par (18x06):</label>
            <input type="text" name="eq_d_noturna_par" class="border rounded px-3 py-2 w-full mb-2" placeholder="Responsável">
            <label class="block font-semibold mb-1">Supervisor:</label>
            <input type="text" name="supervisor_d" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">CAD:</label>
            <input type="text" name="cad_d" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">Encarregado de VTR:</label>
            <input type="text" name="encarregado_vtr_d" class="border rounded px-3 py-2 w-full mb-2">
            <label class="block font-semibold mb-1">Motorista de VTR:</label>
            <input type="text" name="motorista_vtr_d" class="border rounded px-3 py-2 w-full">
          </div>
        </div>
        <!-- Postos Patrimoniais -->
        <h3 class="text-lg font-bold mt-6 mb-2 text-blue-800">Postos Patrimoniais (12x36)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block font-semibold mb-1">Paço Municipal:</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_paco_municipal_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_paco_municipal_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_paco_municipal_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_paco_municipal_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1">P.A. Central:</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_pa_central_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_pa_central_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_pa_central_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_pa_central_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1">Garagem:</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_garagem_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_garagem_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_garagem_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_garagem_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1">UBS Alcides Vieira:</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_ubs_alcides_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_ubs_alcides_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_ubs_alcides_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_ubs_alcides_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1">UBS Morro:</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_ubs_morro_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_ubs_morro_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_ubs_morro_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_ubs_morro_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1">Castelinho:</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_castelinho_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_castelinho_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_castelinho_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_castelinho_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1">Paço Municipal (2):</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_paco_municipal2_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_paco_municipal2_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_paco_municipal2_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_paco_municipal2_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1">Praça/Parques:</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_praca_parques_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_praca_parques_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_praca_parques_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_praca_parques_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1">SPIM:</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_spim_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_spim_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_spim_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_spim_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1">CULT/AMB/DEMU:</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_cult_amb_demu_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_cult_amb_demu_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_cult_amb_demu_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_cult_amb_demu_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
          <div>
            <label class="block font-semibold mb-1">CRAS:</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <input type="text" name="posto_cras_diurno_impar" class="border rounded px-3 py-2 w-full" placeholder="Diurno Impar">
              <input type="text" name="posto_cras_noturno_impar" class="border rounded px-3 py-2 w-full" placeholder="Noturno Impar">
              <input type="text" name="posto_cras_diurno_par" class="border rounded px-3 py-2 w-full" placeholder="Diurno Par">
              <input type="text" name="posto_cras_noturno_par" class="border rounded px-3 py-2 w-full" placeholder="Noturno Par">
            </div>
          </div>
        </div>
        <!-- Postos Administrativos -->
        <h3 class="text-lg font-bold mt-6 mb-2 text-blue-800">Postos Administrativos (Semanal)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block font-semibold mb-1">Paço Municipal:</label>
            <input type="text" name="adm_paco_municipal" class="border rounded px-3 py-2 w-full">
          </div>
          <div>
            <label class="block font-semibold mb-1">Administração:</label>
            <input type="text" name="adm_administracao" class="border rounded px-3 py-2 w-full">
          </div>
          <div>
            <label class="block font-semibold mb-1">Conselho Tutelar:</label>
            <input type="text" name="adm_conselho_tutelar" class="border rounded px-3 py-2 w-full">
          </div>
          <div>
            <label class="block font-semibold mb-1">Defesa Civil:</label>
            <input type="text" name="adm_defesa_civil" class="border rounded px-3 py-2 w-full">
          </div>
          <div>
            <label class="block font-semibold mb-1">DEL.POL.:</label>
            <input type="text" name="adm_delpol" class="border rounded px-3 py-2 w-full">
          </div>
          <div>
            <label class="block font-semibold mb-1">Câmara Municipal:</label>
            <input type="text" name="adm_camara_municipal" class="border rounded px-3 py-2 w-full">
          </div>
          <div>
            <label class="block font-semibold mb-1">Museu Municipal:</label>
            <input type="text" name="adm_museu_municipal" class="border rounded px-3 py-2 w-full">
          </div>
          <div>
            <label class="block font-semibold mb-1">CAPS:</label>
            <input type="text" name="adm_caps" class="border rounded px-3 py-2 w-full">
          </div>
          <div>
            <label class="block font-semibold mb-1">ESF Jundiacanga:</label>
            <input type="text" name="adm_esf_jundiacanga" class="border rounded px-3 py-2 w-full">
          </div>
        </div>
        <!-- Afastamentos e Licenças Médicas -->
        <h3 class="text-lg font-bold mt-6 mb-2 text-blue-800">Afastamentos e Licenças Médicas</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div>
            <label class="block font-semibold mb-1">Nome:</label>
            <input type="text" name="afastamento_1" class="border rounded px-3 py-2 w-full">
          </div>
          <div>
            <label class="block font-semibold mb-1">Nome:</label>
            <input type="text" name="afastamento_2" class="border rounded px-3 py-2 w-full">
          </div>
          <div>
            <label class="block font-semibold mb-1">Nome:</label>
            <input type="text" name="afastamento_3" class="border rounded px-3 py-2 w-full">
          </div>
        </div>
        <!-- Férias -->
        <h3 class="text-lg font-bold mt-6 mb-2 text-blue-800">Período de Férias</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div>
            <label class="block font-semibold mb-1">Nome:</label>
            <input type="text" name="ferias_nome_1" class="border rounded px-3 py-2 w-full">
            <label class="block font-semibold mb-1 mt-2">Período:</label>
            <input type="text" name="ferias_periodo_1" class="border rounded px-3 py-2 w-full" placeholder="Ex: 01/07/2025 - 30/07/2025">
          </div>
          <div>
            <label class="block font-semibold mb-1">Nome:</label>
            <input type="text" name="ferias_nome_2" class="border rounded px-3 py-2 w-full">
            <label class="block font-semibold mb-1 mt-2">Período:</label>
            <input type="text" name="ferias_periodo_2" class="border rounded px-3 py-2 w-full" placeholder="Ex: 01/07/2025 - 30/07/2025">
          </div>
          <div>
            <label class="block font-semibold mb-1">Nome:</label>
            <input type="text" name="ferias_nome_3" class="border rounded px-3 py-2 w-full">
            <label class="block font-semibold mb-1 mt-2">Período:</label>
            <input type="text" name="ferias_periodo_3" class="border rounded px-3 py-2 w-full" placeholder="Ex: 01/07/2025 - 20/07/2025">
          </div>
        </div>
        <!-- Avisos -->
        <div class="mt-8">
          <label class="block font-semibold mb-1">Avisos:</label>
          <textarea name="avisos" class="border rounded px-3 py-2 w-full" rows="2" placeholder="Ex: Viaturas devem ser usadas de forma responsável..."></textarea>
        </div>
        <!-- Botão de Salvar -->
        <div class="mt-8 text-right">
          <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-8 rounded">
            Salvar Escala
          </button>
        </div>
      </form>
    </div>

    <script>
        function abrirModalCriar() {
            document.getElementById('modalEscalaTitle').textContent = 'Nova Escala';
            document.getElementById('formEscala').reset();
            document.getElementById('escala_id').value = '';
            document.getElementById('feedbackEscala').textContent = '';
            document.getElementById('modalEscala').classList.remove('hidden');
        }
        
        function fecharModalEscala() {
            document.getElementById('modalEscala').classList.add('hidden');
        }
        
        function editarEscala(id) {
            fetch(`backend/api/escalas.php/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const escala = data.data;
                        document.getElementById('modalEscalaTitle').textContent = 'Editar Escala';
                        document.getElementById('escala_id').value = escala.id;
                        document.getElementById('nome_escala').value = escala.nome;
                        document.getElementById('setor_escala').value = escala.setor_id;
                        document.getElementById('responsavel_escala').value = escala.responsavel_id;
                        document.getElementById('data_inicio_escala').value = escala.data_inicio;
                        document.getElementById('data_fim_escala').value = escala.data_fim;
                        document.getElementById('turno_escala').value = escala.turno;
                        document.getElementById('observacoes_escala').value = escala.observacoes || '';
                        // Selecionar membros
                        const membrosSelect = document.getElementById('membros_escala');
                        Array.from(membrosSelect.options).forEach(opt => {
                            opt.selected = escala.pessoal.some(p => p.usuario_id == opt.value);
                        });
                        document.getElementById('feedbackEscala').textContent = '';
                        document.getElementById('modalEscala').classList.remove('hidden');
                    } else {
                        alert('Erro ao buscar escala: ' + data.message);
                    }
                })
                .catch(() => alert('Erro ao buscar dados da escala.'));
        }
        
        function desativarEscala(id) {
            if (confirm('Tem certeza que deseja desativar esta escala?')) {
                fetch(`backend/api/escalas.php/${id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: window.csrfToken || '' })
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        alert('Escala desativada com sucesso!');
                        location.reload();
                    } else {
                        alert(resp.message || 'Erro ao desativar escala.');
                    }
                })
                .catch(() => alert('Erro ao desativar escala.'));
            }
        }
        
        // Modal de Pessoal da Escala
        let pessoalEscalaAtual = [];
        function verPessoal(id) {
            fetch(`backend/api/escalas.php/${id}/pessoal`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        pessoalEscalaAtual = data.data;
                        let html = '<h3 class="text-lg font-semibold mb-2">Pessoal Escalado</h3>';
                        if (pessoalEscalaAtual.length === 0) {
                            html += '<p class="text-gray-500">Nenhum membro escalado.</p>';
                        } else {
                            html += '<ul class="mb-2">';
                            pessoalEscalaAtual.forEach(p => {
                                html += `<li class='mb-1'>${p.usuario_nome} (${p.graduacao_nome}) - ${p.data} - ${p.turno} - ${p.funcao} <button onclick=\"removerPessoalEscala(${id},${p.id})\" class='text-red-600 ml-2'>Remover</button></li>`;
                            });
                            html += '</ul>';
                        }
                        html += `<button onclick=\"adicionarPessoal(${id})\" class='bg-green-600 text-white px-3 py-1 rounded'>Adicionar Pessoal</button> <button onclick=\"fecharModalPessoal()\" class='bg-gray-300 px-3 py-1 rounded ml-2'>Fechar</button>`;
                        abrirModalPessoal(html);
                    } else {
                        alert('Erro ao buscar pessoal da escala.');
                    }
                });
        }
        function abrirModalPessoal(html) {
            let modal = document.getElementById('modalPessoalEscala');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'modalPessoalEscala';
                modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
                modal.innerHTML = `<div class='bg-white rounded-lg shadow-xl max-w-lg w-full p-6' id='modalPessoalContent'></div>`;
                document.body.appendChild(modal);
            }
            document.getElementById('modalPessoalContent').innerHTML = html;
            modal.classList.remove('hidden');
        }
        function fecharModalPessoal() {
            const modal = document.getElementById('modalPessoalEscala');
            if (modal) modal.classList.add('hidden');
        }
        function adicionarPessoal(id) {
            // Formulário simples para adicionar
            let html = `<h3 class='text-lg font-semibold mb-2'>Adicionar Pessoal</h3>
                <form id='formAddPessoal'>
                    <label>Usuário:<br><select name='usuario_id' class='border rounded w-full mb-2'>`;
            Array.from(document.getElementById('membros_escala').options).forEach(opt => {
                html += `<option value='${opt.value}'>${opt.textContent}</option>`;
            });
            html += `</select></label>
                    <label>Data:<br><input type='date' name='data' class='border rounded w-full mb-2' required></label>
                    <label>Turno:<br><select name='turno' class='border rounded w-full mb-2'>
                        <option value='manha'>Manhã</option>
                        <option value='tarde'>Tarde</option>
                        <option value='noite'>Noite</option>
                        <option value='madrugada'>Madrugada</option>
                        <option value='integral'>Integral</option>
                    </select></label>
                    <label>Função:<br><input type='text' name='funcao' class='border rounded w-full mb-2'></label>
                    <label>Observações:<br><input type='text' name='observacoes' class='border rounded w-full mb-2'></label>
                    <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded'>Adicionar</button>
                    <button type='button' onclick='verPessoal(${id})' class='bg-gray-300 px-4 py-2 rounded ml-2'>Cancelar</button>
                </form>`;
            abrirModalPessoal(html);
            document.getElementById('formAddPessoal').onsubmit = function(e) {
                e.preventDefault();
                const form = e.target;
                const data = {
                    usuario_id: form.usuario_id.value,
                    data: form.data.value,
                    turno: form.turno.value,
                    funcao: form.funcao.value,
                    observacoes: form.observacoes.value,
                    csrf_token: window.csrfToken || ''
                };
                fetch(`backend/api/escalas.php/${id}/add-pessoal`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        alert('Pessoal adicionado!');
                        verPessoal(id);
                    } else {
                        alert(resp.message || 'Erro ao adicionar pessoal.');
                    }
                })
                .catch(() => alert('Erro ao adicionar pessoal.'));
            }
        }
        function removerPessoalEscala(escala_id, pessoal_id) {
            if (confirm('Remover este membro da escala?')) {
                fetch(`backend/api/escalas.php/${escala_id}/remove-pessoal/${pessoal_id}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: window.csrfToken || '' })
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        alert('Removido com sucesso!');
                        verPessoal(escala_id);
                    } else {
                        alert(resp.message || 'Erro ao remover.');
                    }
                })
                .catch(() => alert('Erro ao remover.'));
            }
        }
        
        function gerarPDFEscala(id) {
            window.open(`backend/api/escalas.php/${id}/pdf`, '_blank');
        }
        
        function gerarPDF() {
            alert('Funcionalidade de geração de PDF geral será implementada');
        }
        
        function filtrarEscalas() {
            document.querySelector('form[method=GET]').submit();
        }
        
        // Fechar modal ao clicar fora do conteúdo
        if (document.getElementById('modalEscala')) {
            document.getElementById('modalEscala').addEventListener('click', function(e) {
                if (e.target === this) {
                    fecharModalEscala();
                }
            });
        }

        // Submissão do formulário de escala (criação/edição)
        document.getElementById('formEscala').onsubmit = function(e) {
            e.preventDefault();
            const id = document.getElementById('escala_id').value;
            const form = e.target;
            const data = {
                nome: form.nome.value,
                setor_id: form.setor_id.value,
                responsavel_id: form.responsavel_id.value,
                data_inicio: form.data_inicio.value,
                data_fim: form.data_fim.value,
                turno: form.turno.value,
                observacoes: form.observacoes.value,
                membros: Array.from(form['membros[]'].selectedOptions).map(opt => opt.value),
                csrf_token: window.csrfToken || ''
            };
            document.getElementById('feedbackEscala').textContent = 'Salvando...';
            if (id) {
                // Edição
                fetch(`backend/api/escalas.php/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        document.getElementById('feedbackEscala').textContent = 'Escala atualizada com sucesso!';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        document.getElementById('feedbackEscala').textContent = resp.message || 'Erro ao atualizar escala.';
                    }
                })
                .catch(() => {
                    document.getElementById('feedbackEscala').textContent = 'Erro ao atualizar escala.';
                });
            } else {
                // Criação
                fetch('backend/api/escalas.php/create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(resp => {
                    if (resp.success) {
                        document.getElementById('feedbackEscala').textContent = 'Escala criada com sucesso!';
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        document.getElementById('feedbackEscala').textContent = resp.message || 'Erro ao criar escala.';
                    }
                })
                .catch(() => {
                    document.getElementById('feedbackEscala').textContent = 'Erro ao criar escala.';
                });
            }
        }

        document.getElementById('btnNovaEscala').onclick = function() {
            document.getElementById('formNovaEscala').classList.remove('hidden');
            this.classList.add('hidden');
        };
    </script>
</body>
</html> 