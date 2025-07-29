<?php
require_once 'auth_check.php';
require_once 'config_mysql.php';

// Verificar se o usuário é admin
$currentUser = getCurrentUser();
if (!isset($currentUser['perfil']) || $currentUser['perfil'] !== 'Administrador') {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Obter ID da ocorrência
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die('ID de ocorrência inválido.');
}

// Buscar ocorrência
$stmt = $pdo->prepare('SELECT * FROM ocorrencias WHERE id = ?');
$stmt->execute([$id]);
$ocorrencia = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ocorrencia) {
    die('Ocorrência não encontrada.');
}

// Processar edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'data', 'hora_inicio', 'local', 'natureza', 'data_fato', 'hora_fato', 'local_fato', 'bairro', 'cidade', 'estado', 'cep',
        'nome_solicitante', 'nascimento_solicitante', 'rg_solicitante', 'cpf_solicitante', 'telefone_solicitante', 'endereco_solicitante',
        'relato', 'providencias', 'observacoes', 'status'
    ];
    $valores = [];
    foreach ($campos as $campo) {
        $valores[$campo] = $_POST[$campo] ?? $ocorrencia[$campo];
    }
    $sql = 'UPDATE ocorrencias SET ' . implode(' = ?, ', $campos) . ' = ? WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($valores) + [$id]);
    header('Location: gerenciar_ocorrencias.php?msg=Ocorrência atualizada com sucesso!');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Ocorrência</title>
    <link rel="stylesheet" href="https://cdn.tailwindcss.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
      body {
        background: linear-gradient(135deg, #e0e7ff 0%, #f0fdfa 100%);
      }
    </style>
</head>
<body class="min-h-screen">
    <div class="max-w-2xl mx-auto bg-white p-10 mt-12 rounded-3xl shadow-2xl border border-blue-100">
        <h2 class="text-3xl font-extrabold mb-8 text-blue-900 flex items-center drop-shadow"><i class="fas fa-edit mr-3 text-blue-700"></i>Editar Ocorrência <span class="ml-2 text-blue-500">#<?= htmlspecialchars($ocorrencia['numero_ocorrencia']) ?></span></h2>
        <form method="POST" class="space-y-10">
            <!-- Seção: Dados da Ocorrência -->
            <div>
                <h3 class="text-xl font-bold text-blue-800 mb-4 border-b-2 border-blue-200 pb-2 flex items-center"><i class="fas fa-info-circle mr-2 text-blue-400"></i>Dados da Ocorrência</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Data</label>
                        <input type="date" name="data" value="<?= htmlspecialchars($ocorrencia['data'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Hora Início</label>
                        <input type="time" name="hora_inicio" value="<?= htmlspecialchars($ocorrencia['hora_inicio'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold mb-1 text-blue-900">Local</label>
                        <input type="text" name="local" value="<?= htmlspecialchars($ocorrencia['local'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Natureza</label>
                        <input type="text" name="natureza" value="<?= htmlspecialchars($ocorrencia['natureza'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Status</label>
                        <input type="text" name="status" value="<?= htmlspecialchars($ocorrencia['status'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                </div>
            </div>
            <!-- Seção: Dados do Fato -->
            <div>
                <h3 class="text-xl font-bold text-blue-800 mb-4 border-b-2 border-blue-200 pb-2 flex items-center"><i class="fas fa-calendar-day mr-2 text-blue-400"></i>Dados do Fato</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Data do Fato</label>
                        <input type="date" name="data_fato" value="<?= htmlspecialchars($ocorrencia['data_fato'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Hora do Fato</label>
                        <input type="time" name="hora_fato" value="<?= htmlspecialchars($ocorrencia['hora_fato'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold mb-1 text-blue-900">Local do Fato</label>
                        <input type="text" name="local_fato" value="<?= htmlspecialchars($ocorrencia['local_fato'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Bairro</label>
                        <input type="text" name="bairro" value="<?= htmlspecialchars($ocorrencia['bairro'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Cidade</label>
                        <input type="text" name="cidade" value="<?= htmlspecialchars($ocorrencia['cidade'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Estado</label>
                        <input type="text" name="estado" value="<?= htmlspecialchars($ocorrencia['estado'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">CEP</label>
                        <input type="text" name="cep" value="<?= htmlspecialchars($ocorrencia['cep'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                </div>
            </div>
            <!-- Seção: Solicitante -->
            <div>
                <h3 class="text-xl font-bold text-blue-800 mb-4 border-b-2 border-blue-200 pb-2 flex items-center"><i class="fas fa-user mr-2 text-blue-400"></i>Dados do Solicitante</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Nome</label>
                        <input type="text" name="nome_solicitante" value="<?= htmlspecialchars($ocorrencia['nome_solicitante'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Nascimento</label>
                        <input type="date" name="nascimento_solicitante" value="<?= htmlspecialchars($ocorrencia['nascimento_solicitante'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">RG</label>
                        <input type="text" name="rg_solicitante" value="<?= htmlspecialchars($ocorrencia['rg_solicitante'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">CPF</label>
                        <input type="text" name="cpf_solicitante" value="<?= htmlspecialchars($ocorrencia['cpf_solicitante'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Telefone</label>
                        <input type="text" name="telefone_solicitante" value="<?= htmlspecialchars($ocorrencia['telefone_solicitante'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block font-semibold mb-1 text-blue-900">Endereço</label>
                        <input type="text" name="endereco_solicitante" value="<?= htmlspecialchars($ocorrencia['endereco_solicitante'] ?? '') ?>" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" />
                    </div>
                </div>
            </div>
            <!-- Seção: Detalhes -->
            <div>
                <h3 class="text-xl font-bold text-blue-800 mb-4 border-b-2 border-blue-200 pb-2 flex items-center"><i class="fas fa-align-left mr-2 text-blue-400"></i>Detalhes</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Relato</label>
                        <textarea name="relato" rows="3" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition"><?= htmlspecialchars($ocorrencia['relato'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Providências</label>
                        <textarea name="providencias" rows="2" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition"><?= htmlspecialchars($ocorrencia['providencias'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-blue-900">Observações</label>
                        <textarea name="observacoes" rows="2" class="w-full border-2 border-blue-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition"><?= htmlspecialchars($ocorrencia['observacoes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between pt-6">
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-400 hover:from-blue-700 hover:to-blue-500 text-white px-8 py-3 rounded-2xl shadow-lg flex items-center font-bold text-lg transition-all duration-200"><i class="fas fa-save mr-3"></i>Salvar Alterações</button>
                <a href="gerenciar_ocorrencias.php" class="text-blue-600 hover:underline flex items-center font-semibold text-lg transition-all duration-200"><i class="fas fa-times mr-2"></i>Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html> 