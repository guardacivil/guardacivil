<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar permissão
if (!hasPermission('usuarios') && !isAdminLoggedIn()) {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Verificar se o ID foi passado

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo 'ID inválido recebido: ' . htmlspecialchars($_GET['id'] ?? 'NULO');
    exit;
}

$id = intval($_GET['id']);

// Não permitir que o usuário exclua a si mesmo
$currentUser = getCurrentUser();
if ($currentUser && $currentUser['id'] == $id) {
    echo 'Você não pode excluir a si mesmo. Seu ID: ' . $currentUser['id'] . ' | ID a excluir: ' . $id;
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    logAction('excluir_usuario', 'usuarios', $id);
    if ($stmt->rowCount() > 0) {
        echo 'Usuário excluído com sucesso! ID: ' . $id;
    } else {
        echo 'Nenhum usuário foi excluído. ID: ' . $id . ' pode não existir.';
    }
    exit;
} catch (PDOException $e) {
    echo 'Erro ao excluir usuário: ' . $e->getMessage();
    exit;
} 