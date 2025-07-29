<?php
// setores.php - Gestão de Setores da GCM
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

// Buscar setores
try {
    $sql = "SELECT s.*, u.nome as responsavel_nome FROM setores s LEFT JOIN usuarios u ON s.responsavel_id = u.id WHERE s.ativo = 1 ORDER BY s.nome";
    $stmt = $pdo->query($sql);
    $setores = $stmt->fetchAll();
    
    // Buscar usuários para responsáveis
    $stmt = $pdo->query("SELECT id, nome FROM usuarios WHERE ativo = 1 ORDER BY nome");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao buscar setores: " . $e->getMessage());
    $setores = [];
    $usuarios = [];
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Setores - Sistema Integrado da Guarda Civil</title>
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
            <h2 class="text-3xl font-bold text-gray-800">Setores da GCM</h2>
            <div class="text-gray-600 text-sm">
                Olá, <?= htmlspecialchars($currentUser['nome']) ?> 
                (<?= htmlspecialchars($currentUser['perfil']) ?>)
            </div>
        </header>

        <!-- Botões de ação -->
        <div class="mb-6 flex gap-4">
            <button onclick="abrirModalCriar()" class="btn-primary text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Novo Setor
            </button>
        </div>

        <!-- Lista de Setores -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($setores as $setor): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($setor['nome']) ?></h3>
                        <?php if ($setor['sigla']): ?>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($setor['sigla']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editarSetor(<?= $setor['id'] ?>)" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="excluirSetor(<?= $setor['id'] ?>)" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                
                <?php if ($setor['descricao']): ?>
                <p class="text-sm text-gray-700 mb-4"><?= htmlspecialchars($setor['descricao']) ?></p>
                <?php endif; ?>
                
                <div class="text-sm text-gray-600">
                    <p><strong>Responsável:</strong> <?= htmlspecialchars($setor['responsavel_nome'] ?? 'Não definido') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($setores)): ?>
            <div class="col-span-full bg-white rounded-lg shadow-md p-8 text-center">
                <i class="fas fa-building text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">Nenhum setor encontrado</h3>
                <p class="text-gray-500">Crie o primeiro setor para começar.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal de Novo/Editar Setor -->
    <div id="modalSetor" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center justify-center mb-4"><i class="fas fa-building text-3xl text-blue-600 mr-2"></i> <span id="modalSetorTitle">Novo Setor</span></h3>
          </div>
          <form id="formSetor" class="p-6">
            <input type="hidden" id="setor_id" name="id">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Setor *</label>
              <input type="text" id="nome_setor" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Sigla</label>
              <input type="text" id="sigla_setor" name="sigla" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
              <input type="text" id="descricao_setor" name="descricao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Responsável</label>
              <input type="text" id="responsavel_setor" name="responsavel_nome" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div id="feedbackSetor" class="mt-2 text-center text-sm"></div>
            <div class="mt-6 flex justify-end gap-3">
              <button type="button" onclick="fecharModalSetor()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">Cancelar</button>
              <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Salvar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script>
        function abrirModalCriar() {
            document.getElementById('modalSetorTitle').textContent = 'Novo Setor';
            document.getElementById('formSetor').reset();
            document.getElementById('setor_id').value = '';
            document.getElementById('feedbackSetor').textContent = '';
            document.getElementById('modalSetor').classList.remove('hidden');
        }
        
        function fecharModalSetor() {
            document.getElementById('modalSetor').classList.add('hidden');
        }
        
        function editarSetor(id) {
            // Aqui você pode implementar a busca dos dados via AJAX ou PHP
            alert('Funcionalidade de edição será implementada');
        }
        
        function excluirSetor(id) {
            if (confirm('Tem certeza que deseja excluir este setor?')) {
                // Aqui você pode implementar a exclusão via AJAX ou formulário
                alert('Funcionalidade de exclusão será implementada');
            }
        }
        
        document.getElementById('formSetor').addEventListener('submit', function(e) {
            const nome = document.getElementById('nome_setor').value.trim();
            if (!nome) {
                document.getElementById('feedbackSetor').textContent = 'O nome do setor é obrigatório.';
                document.getElementById('feedbackSetor').className = 'mt-2 text-center text-sm text-red-600';
                e.preventDefault();
                return false;
            }
            document.getElementById('feedbackSetor').textContent = '';
            // Aqui você pode implementar o envio via AJAX ou deixar o padrão do formulário
            fecharModalSetor();
            e.preventDefault(); // Remova esta linha se quiser o envio normal do formulário
        });
    </script>
</body>
</html> 