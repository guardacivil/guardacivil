<?php
require_once 'auth_check.php';
require_once 'config.php';
requireLogin();
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$nome = trim($input['nome'] ?? '');
$filtros = json_encode($input['filtros'] ?? []);
$usuario_id = getCurrentUser()['id'];
if ($nome) {
    $stmt = $pdo->prepare('INSERT INTO filtros_salvos (usuario_id, nome, filtros) VALUES (?, ?, ?)');
    $stmt->execute([$usuario_id, $nome, $filtros]);
    echo json_encode(['msg' => 'Filtro salvo com sucesso!']);
} else {
    echo json_encode(['msg' => 'Nome do filtro obrigatório!']);
} 