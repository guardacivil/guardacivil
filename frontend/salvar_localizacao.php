<?php
file_put_contents(__DIR__.'/../logs/localizacao_debug.log', date('c')." - ".file_get_contents('php://input')."\n", FILE_APPEND);
require_once 'auth_check.php';
require_once 'config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

// Permitir usuario_id ou login via JSON
$usuario_id = null;
if (isset($data['usuario_id'])) {
    $usuario_id = (int)$data['usuario_id'];
} elseif (isset($data['login'])) {
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
    $stmt->execute([$data['login']]);
    $usuario_id = $stmt->fetchColumn();
}
if (!$usuario_id) {
    // Tenta pegar da sessão
    $user = getCurrentUser();
    $usuario_id = $user['id'] ?? null;
}
if (!$usuario_id) {
    echo json_encode(['success' => false, 'msg' => 'Usuário não identificado']);
    exit;
}
// Verifica se o usuário está ativo
$stmt = $pdo->prepare('SELECT ativo FROM usuarios WHERE id = ?');
$stmt->execute([$usuario_id]);
$ativo = $stmt->fetchColumn();
if (!$ativo) {
    echo json_encode(['success' => false, 'msg' => 'Usuário inativo']);
    exit;
}
$lat = $data['latitude'] ?? null;
$lng = $data['longitude'] ?? null;
if ($lat && $lng) {
    $sql = "REPLACE INTO localizacoes_usuarios (usuario_id, latitude, longitude, atualizado_em) VALUES (?, ?, ?, ".
        (strpos($pdo->getAttribute(PDO::ATTR_DRIVER_NAME), 'mysql') !== false ? 'NOW()' : "datetime('now')") . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id, $lat, $lng]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'msg' => 'Dados inválidos']);
} 