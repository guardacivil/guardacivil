<?php
require_once 'auth_check.php';
require_once 'config.php';
requireLogin();
header('Content-Type: application/json');
// Detectar driver para filtro de data
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'mysql') {
    $filtroData = "l.atualizado_em >= NOW() - INTERVAL 2 MINUTE";
} else {
    $filtroData = "l.atualizado_em >= datetime('now', '-2 minutes')";
}
$sql = "
    SELECT l.usuario_id, l.latitude, l.longitude, l.atualizado_em, u.nome, p.nome as perfil_nome
    FROM localizacoes_usuarios l
    LEFT JOIN usuarios u ON l.usuario_id = u.id
    LEFT JOIN perfis p ON u.perfil_id = p.id
    WHERE u.ativo = 1
      AND l.id = (
        SELECT MAX(id) FROM localizacoes_usuarios
        WHERE usuario_id = l.usuario_id
      )
";
$stmt = $pdo->query($sql);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    echo json_encode(['sql'=>$sql, 'result'=>$result]);
    exit;
}
$usuario_id_hist = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : null;
$historico = isset($_GET['historico']) && $_GET['historico'] == 1;
$periodo_horas = isset($_GET['periodo']) ? max(1, intval($_GET['periodo'])) : 2;
$limpar = isset($_GET['limpar']) && $_GET['limpar'] == 1;
if ($limpar && $usuario_id_hist) {
    // Limpar histórico do período selecionado para o usuário
    if ($driver === 'mysql') {
        $filtroDataHist = "atualizado_em >= NOW() - INTERVAL $periodo_horas HOUR";
    } else {
        $filtroDataHist = "atualizado_em >= datetime('now', '-$periodo_horas hours')";
    }
    $sql = "DELETE FROM localizacoes_usuarios WHERE usuario_id = ? AND $filtroDataHist";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([$usuario_id_hist]);
        echo json_encode(['success'=>true]);
    } catch (Exception $e) {
        echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
    }
    exit;
}
if ($historico && $usuario_id_hist) {
    // Histórico do período selecionado para o usuário
    if ($driver === 'mysql') {
        $filtroDataHist = "l.atualizado_em >= NOW() - INTERVAL $periodo_horas HOUR";
    } else {
        $filtroDataHist = "l.atualizado_em >= datetime('now', '-$periodo_horas hours')";
    }
    $sql = "SELECT l.usuario_id, l.latitude, l.longitude, l.atualizado_em FROM localizacoes_usuarios l WHERE l.usuario_id = ? AND $filtroDataHist ORDER BY l.atualizado_em ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id_hist]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($result);
    exit;
}
echo json_encode($result); 