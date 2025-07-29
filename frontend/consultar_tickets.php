<?php
require_once 'conexao.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([]);
    exit;
}
$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare('SELECT id, titulo as assunto, mensagem, prioridade, status, resposta FROM suporte WHERE usuario_id = ? ORDER BY id DESC');
$stmt->execute([$usuario_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($tickets); 