<?php
require_once 'config.php';

// tipo: pessoal, ocorrencias, escalas, comunicacoes, atividades
$tipo = $_GET['tipo'] ?? '';
$campos = $_GET['campos'] ?? [];
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

if (!$tipo || empty($campos)) {
    echo '<p>Tipo de relatório ou campos não selecionados.</p>';
    exit;
}

$titulo = 'Relatório Personalizado - ' . ucfirst($tipo);

// Montar SQL e buscar dados
switch ($tipo) {
    case 'pessoal':
        $sql = "SELECT u.*, g.nome as graduacao, s.nome as setor, p.nome as perfil FROM usuarios u LEFT JOIN graduacoes g ON u.graduacao_id = g.id LEFT JOIN setores s ON u.setor_id = s.id LEFT JOIN perfis p ON u.perfil_id = p.id WHERE 1=1";
        if ($data_inicio) $sql .= " AND u.created_at >= '" . $data_inicio . "'";
        if ($data_fim) $sql .= " AND u.created_at <= '" . $data_fim . "'";
        $sql .= " ORDER BY u.nome";
        break;
    case 'ocorrencias':
        $sql = "SELECT * FROM ocorrencias WHERE 1=1";
        if ($data_inicio) $sql .= " AND data >= '" . $data_inicio . "'";
        if ($data_fim) $sql .= " AND data <= '" . $data_fim . "'";
        $sql .= " ORDER BY data DESC, id DESC";
        break;
    case 'escalas':
        $sql = "SELECT e.*, s.nome as setor_nome, u.nome as responsavel_nome FROM escalas e LEFT JOIN setores s ON e.setor_id = s.id LEFT JOIN usuarios u ON e.responsavel_id = u.id WHERE 1=1";
        if ($data_inicio) $sql .= " AND e.data_inicio >= '" . $data_inicio . "'";
        if ($data_fim) $sql .= " AND e.data_fim <= '" . $data_fim . "'";
        $sql .= " ORDER BY e.data_inicio DESC, e.id DESC";
        break;
    case 'comunicacoes':
        $sql = "SELECT c.*, u.nome as autor_nome, s.nome as setor_nome, g.nome as graduacao_minima_nome FROM comunicacoes c LEFT JOIN usuarios u ON c.autor_id = u.id LEFT JOIN setores s ON c.setor_id = s.id LEFT JOIN graduacoes g ON c.graduacao_minima = g.id WHERE 1=1";
        if ($data_inicio) $sql .= " AND c.created_at >= '" . $data_inicio . "'";
        if ($data_fim) $sql .= " AND c.created_at <= '" . $data_fim . "'";
        $sql .= " ORDER BY c.created_at DESC, c.id DESC";
        break;
    case 'atividades':
        $sql = "SELECT * FROM ocorrencias WHERE 1=1";
        if ($data_inicio) $sql .= " AND data >= '" . $data_inicio . "'";
        if ($data_fim) $sql .= " AND data <= '" . $data_fim . "'";
        $sql .= " ORDER BY data DESC, id DESC";
        break;
    default:
        echo '<p>Tipo de relatório inválido.</p>';
        exit;
}

try {
    $stmt = $pdo->query($sql);
    $dados = $stmt->fetchAll();
} catch (PDOException $e) {
    echo '<p>Erro ao buscar dados: ' . $e->getMessage() . '</p>';
    exit;
}
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo) ?></title>
    <link rel="stylesheet" href="https://cdn.tailwindcss.com">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-5xl mx-auto bg-white p-8 mt-10 rounded shadow">
        <h2 class="text-2xl font-bold mb-6 text-blue-900 flex items-center">
            <i class="fas fa-table mr-2"></i> <?= htmlspecialchars($titulo) ?>
        </h2>
        <div class="overflow-x-auto">
            <table class="min-w-full table-auto border">
                <thead class="bg-gray-100">
                    <tr>
                        <?php foreach ($campos as $campo): ?>
                            <th class="p-2 border text-left text-sm font-semibold text-gray-700"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $campo))) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados as $linha): ?>
                        <tr class="hover:bg-gray-50">
                            <?php foreach ($campos as $campo): ?>
                                <td class="p-2 border text-sm text-gray-800"><?= htmlspecialchars($linha[$campo] ?? '') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-8">
            <a href="relatorios.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar aos Relatórios
            </a>
        </div>
    </div>
</body>
</html> 