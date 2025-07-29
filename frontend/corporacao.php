<?php
require_once 'auth_check.php';
require_once 'config.php';

requireLogin();

// Criar tabela funcionarios se não existir
try {
    $pdo->query("CREATE TABLE IF NOT EXISTS funcionarios (
        id INTEGER PRIMARY KEY AUTO_INCREMENT,
        nome VARCHAR(255) NOT NULL,
        nome_guerra VARCHAR(100),
        matricula VARCHAR(50),
        cpf VARCHAR(20),
        rg VARCHAR(20),
        setor_id INT,
        cargo VARCHAR(100),
        data_admissao DATE,
        email VARCHAR(100),
        telefone VARCHAR(30),
        endereco VARCHAR(255),
        foto VARCHAR(255),
        ativo TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {}

// Processar novo funcionário
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['matricula'], $_POST['cpf'], $_POST['setor_id'])) {
    $nome = trim($_POST['nome']);
    $nome_guerra = trim($_POST['nome_guerra'] ?? '');
    $matricula = trim($_POST['matricula']);
    $cpf = trim($_POST['cpf']);
    $rg = trim($_POST['rg'] ?? '');
    $setor_id = intval($_POST['setor_id']);
    $cargo = trim($_POST['cargo'] ?? '');
    $data_admissao = !empty($_POST['data_admissao']) ? $_POST['data_admissao'] : null;
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $foto_nome = null;
    if (!empty($_FILES['foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $foto_nome = 'funcionario_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto_nome);
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, nome_guerra, matricula, cpf, rg, setor_id, cargo, data_admissao, email, telefone, endereco, foto, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$nome, $nome_guerra, $matricula, $cpf, $rg, $setor_id, $cargo, $data_admissao, $email, $telefone, $endereco, $foto_nome]);
        $msg = '<div class="px-4 py-2 bg-green-200 text-green-800 rounded mb-6">Funcionário cadastrado com sucesso!</div>';
    } catch (PDOException $e) {
        $msg = '<div class="px-4 py-2 bg-red-200 text-red-800 rounded mb-6">Erro ao cadastrar funcionário: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Atualizar funcionário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $nome = trim($_POST['nome']);
    $nome_guerra = trim($_POST['nome_guerra'] ?? '');
    $matricula = trim($_POST['matricula']);
    $cpf = trim($_POST['cpf']);
    $rg = trim($_POST['rg'] ?? '');
    $setor_id = intval($_POST['setor_id']);
    $cargo = trim($_POST['cargo'] ?? '');
    $data_admissao = !empty($_POST['data_admissao']) ? $_POST['data_admissao'] : null;
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $foto_nome = $_POST['foto_atual'] ?? null;
    if (!empty($_FILES['foto']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $foto_nome = 'funcionario_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $foto_nome);
    }
    try {
        $stmt = $pdo->prepare("UPDATE funcionarios SET nome=?, nome_guerra=?, matricula=?, cpf=?, rg=?, setor_id=?, cargo=?, data_admissao=?, email=?, telefone=?, endereco=?, foto=? WHERE id=?");
        $stmt->execute([$nome, $nome_guerra, $matricula, $cpf, $rg, $setor_id, $cargo, $data_admissao, $email, $telefone, $endereco, $foto_nome, $id]);
        $msg = '<div class="px-4 py-2 bg-green-200 text-green-800 rounded mb-6">Funcionário atualizado com sucesso!</div>';
    } catch (PDOException $e) {
        $msg = '<div class="px-4 py-2 bg-red-200 text-red-800 rounded mb-6">Erro ao atualizar funcionário: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

// Ativar/Inativar funcionário
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $stmt = $pdo->prepare("UPDATE funcionarios SET ativo = IF(ativo=1,0,1) WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: corporacao.php');
    exit;
}
// Excluir funcionário
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $id = intval($_GET['excluir']);
    $stmt = $pdo->prepare("DELETE FROM funcionarios WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: corporacao.php');
    exit;
}

// Buscar funcionários
try {
    $sql = "SELECT f.*, s.nome as setor_nome FROM funcionarios f LEFT JOIN setores s ON f.setor_id = s.id ORDER BY f.nome";
    $stmt = $pdo->query($sql);
    $funcionarios = $stmt->fetchAll();
    $stmt = $pdo->query("SELECT * FROM setores WHERE ativo = 1 ORDER BY nome");
    $setores = $stmt->fetchAll();
} catch (PDOException $e) {
    $funcionarios = [];
    $setores = [];
}

// Buscar funcionário para edição via AJAX
if (isset($_GET['get_funcionario']) && is_numeric($_GET['get_funcionario'])) {
    $id = intval($_GET['get_funcionario']);
    $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id = ?");
    $stmt->execute([$id]);
    $f = $stmt->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($f);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Corporação - Guarda Civil</title>
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
    <header class="flex items-center justify-between mb-8">
        <h2 class="text-3xl font-bold text-gray-800"><i class="fas fa-users mr-2"></i>Corporação</h2>
        <button type="button" onclick="document.getElementById('modalFuncionario').classList.remove('hidden')" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-8 rounded">
            <i class="fas fa-plus"></i> Novo Funcionário
        </button>
    </header>
    <?php if ($msg): ?>
        <?= $msg ?>
    <?php endif; ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
        <?php foreach (
$funcionarios as $f): ?>
        <div class="bg-white rounded-lg shadow-md p-4 flex flex-col items-center relative">
            <?php if (!$f['ativo']): ?>
                <div style="position:absolute;top:10px;left:10px;right:10px;z-index:10;">
                    <span style="display:block;background:#dc2626;color:white;font-weight:bold;padding:4px 0;border-radius:6px;text-align:center;font-size:0.95rem;">INATIVO</span>
                </div>
            <?php endif; ?>
            <?php if (!empty($f['foto']) && file_exists('uploads/' . $f['foto'])): ?>
                <img src="uploads/<?= htmlspecialchars($f['foto']) ?>" alt="Foto" class="w-60 h-72 rounded-full object-cover mb-3 border-4 border-blue-200">
            <?php else: ?>
                <div class="w-60 h-72 rounded-full bg-blue-100 flex items-center justify-center text-5xl text-blue-600 mb-3 border-4 border-blue-200">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            <div class="text-center mb-2">
                <div class="font-extrabold text-2xl text-blue-900 mb-1"><?= htmlspecialchars($f['nome_guerra']) ?></div>
                <div class="text-base text-gray-700 mb-1"><?= htmlspecialchars($f['nome']) ?></div>
                <div class="text-sm text-gray-500 mb-1"><?php
                    $setorLabel = [1=>'Guarda Civil',2=>'Secretário',3=>'Estágio',4=>'Outros'];
                    echo $setorLabel[$f['setor_id']] ?? 'N/A';
                ?></div>
            </div>
            <div class="flex gap-2 mt-2">
                <a href="?toggle=<?= $f['id'] ?>" class="px-2 py-1 rounded text-xs <?= $f['ativo'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>" title="Ativar/Inativar">
                    <?= $f['ativo'] ? 'Ativo' : 'Inativo' ?>
                </a>
                <button type="button" onclick="editarFuncionario(<?= $f['id'] ?>)" class="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800" title="Editar"><i class="fas fa-edit"></i></button>
                <a href="?excluir=<?= $f['id'] ?>" class="px-2 py-1 rounded text-xs bg-red-100 text-red-800" title="Excluir" onclick="return confirm('Excluir este funcionário?')"><i class="fas fa-trash"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>
<!-- Modal Novo Funcionário -->
<div id="modalFuncionario" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center justify-center mb-4"><i class="fas fa-user-plus text-3xl text-blue-600 mr-2"></i> Novo Funcionário</h3>
            </div>
            <form class="p-6" method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo</label>
                        <input type="text" name="nome" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome de Guerra</label>
                        <input type="text" name="nome_guerra" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Matrícula</label>
                        <input type="text" name="matricula" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CPF</label>
                        <input type="text" name="cpf" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">RG</label>
                        <input type="text" name="rg" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Setor</label>
                        <select name="setor_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Selecione...</option>
                            <option value="1">Guarda Civil</option>
                            <option value="2">Secretário</option>
                            <option value="3">Estágio</option>
                            <option value="4">Outros</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cargo</label>
                        <input type="text" name="cargo" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data de Admissão</label>
                        <input type="date" name="data_admissao" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                        <input type="text" name="telefone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Endereço</label>
                        <input type="text" name="endereco" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">E-mail</label>
                        <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto (opcional)</label>
                        <input type="file" name="foto" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <input type="hidden" name="edit_id">
                    <input type="hidden" name="foto_atual">
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modalFuncionario').classList.add('hidden')" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"><i class="fas fa-times mr-1"></i>Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"><i class="fas fa-save mr-1"></i>Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function editarFuncionario(id) {
    fetch('corporacao.php?get_funcionario=' + id)
        .then(r => r.json())
        .then(f => {
            document.getElementById('modalFuncionario').classList.remove('hidden');
            document.querySelector('#modalFuncionario [name=edit_id]').value = f.id;
            document.querySelector('#modalFuncionario [name=nome]').value = f.nome;
            document.querySelector('#modalFuncionario [name=nome_guerra]').value = f.nome_guerra;
            document.querySelector('#modalFuncionario [name=matricula]').value = f.matricula;
            document.querySelector('#modalFuncionario [name=cpf]').value = f.cpf;
            document.querySelector('#modalFuncionario [name=rg]').value = f.rg;
            document.querySelector('#modalFuncionario [name=setor_id]').value = f.setor_id;
            document.querySelector('#modalFuncionario [name=cargo]').value = f.cargo;
            document.querySelector('#modalFuncionario [name=data_admissao]').value = f.data_admissao;
            document.querySelector('#modalFuncionario [name=email]').value = f.email;
            document.querySelector('#modalFuncionario [name=telefone]').value = f.telefone;
            document.querySelector('#modalFuncionario [name=endereco]').value = f.endereco;
            document.querySelector('#modalFuncionario [name=foto_atual]').value = f.foto;
        });
}
</script>
</body>
</html> 