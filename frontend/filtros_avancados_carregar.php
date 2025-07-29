<?php
require_once 'auth_check.php';
require_once 'config.php';
requireLogin();
header('Content-Type: application/json');
$id = intval($_GET['id'] ?? 0);
$usuario_id = getCurrentUser()['id'];
$stmt = $pdo->prepare('SELECT filtros FROM filtros_salvos WHERE id = ? AND usuario_id = ?');
$stmt->execute([$id, $usuario_id]);
$filtros = $stmt->fetchColumn();
echo $filtros ?: '{}'; 