<?php
require_once 'auth_check.php';
require_once 'config.php';
requireLogin();
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
// Montar consulta dinâmica conforme filtros recebidos
$where = [];
$params = [];
if (!empty($input['setor'])) {
    $where[] = 'o.setor_id = ?';
    $params[] = $input['setor'];
}
if (!empty($input['graduacao'])) {
    $where[] = 'u.graduacao_id = ?';
    $params[] = $input['graduacao'];
}
if (!empty($input['tipoOcorrencia'])) {
    $where[] = 'o.tipo = ?';
    $params[] = $input['tipoOcorrencia'];
}
if (!empty($input['responsavel'])) {
    $where[] = 'o.usuario_id = ?';
    $params[] = $input['responsavel'];
}
if (!empty($input['status'])) {
    $where[] = 'o.status = ?';
    $params[] = $input['status'];
}
if (!empty($input['dataInicio'])) {
    $where[] = 'o.data >= ?';
    $params[] = $input['dataInicio'];
}
if (!empty($input['dataFim'])) {
    $where[] = 'o.data <= ?';
    $params[] = $input['dataFim'];
}
if (!empty($input['palavrasChave'])) {
    $palavras = explode(',', $input['palavrasChave']);
    foreach ($palavras as $p) {
        $where[] = '(o.descricao LIKE ? OR o.titulo LIKE ?)';
        $params[] = '%' . trim($p) . '%';
        $params[] = '%' . trim($p) . '%';
    }
}
$sql = 'SELECT o.*, u.nome as responsavel_nome, s.nome as setor_nome FROM ocorrencias o LEFT JOIN usuarios u ON o.usuario_id = u.id LEFT JOIN setores s ON o.setor_id = s.id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY o.data DESC LIMIT 1000';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ocorrencias = $stmt->fetchAll();
// Aqui você pode processar $ocorrencias para montar os dados dos gráficos e métricas
// Exemplo simplificado:
$resumo = [
    'total' => count($ocorrencias),
    'por_mes' => [],
    'por_setor' => [],
];
foreach ($ocorrencias as $o) {
    $mes = substr($o['data'], 0, 7);
    if (!isset($resumo['por_mes'][$mes])) $resumo['por_mes'][$mes] = 0;
    $resumo['por_mes'][$mes]++;
    $setor = $o['setor_nome'] ?: 'N/A';
    if (!isset($resumo['por_setor'][$setor])) $resumo['por_setor'][$setor] = 0;
    $resumo['por_setor'][$setor]++;
}
echo json_encode(['ocorrencias' => $ocorrencias, 'resumo' => $resumo]); 