<?php
require_once 'conexao.php';
session_start();
// Verifica se é admin (ajuste conforme seu sistema de autenticação)
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    echo '<p>Você não tem permissão para acessar esta página.</p>';
    exit;
}
// Responder ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resposta']) && isset($_POST['ticket_id'])) {
    $resposta = $_POST['resposta'];
    $ticket_id = intval($_POST['ticket_id']);
    $stmt = $pdo->prepare('UPDATE suporte SET resposta_admin = ?, status = "respondido", data_resposta = NOW() WHERE id = ?');
    $stmt->execute([$resposta, $ticket_id]);
    echo '<p class="text-green-600">Resposta enviada!</p>';
}
// Listar tickets
$stmt = $pdo->query('SELECT s.*, u.nome FROM suporte s JOIN usuarios u ON s.usuario_id = u.id ORDER BY s.data_criacao DESC');
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Tickets de Suporte</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold mb-4">Tickets de Suporte</h2>
        <?php foreach ($tickets as $ticket): ?>
            <div class="border-b py-4 mb-4">
                <div class="font-bold text-lg">Título: <?= htmlspecialchars($ticket['titulo']) ?></div>
                <div><b>Usuário:</b> <?= htmlspecialchars($ticket['nome']) ?></div>
                <div><b>Mensagem:</b> <?= nl2br(htmlspecialchars($ticket['mensagem'])) ?></div>
                <div><b>Status:</b> <?= htmlspecialchars($ticket['status']) ?></div>
                <div><b>Data:</b> <?= htmlspecialchars($ticket['data_criacao']) ?></div>
                <?php if ($ticket['status'] === 'aberto'): ?>
                    <form method="post" class="mt-2">
                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                        <textarea name="resposta" class="w-full border rounded px-2 py-1 mb-2" placeholder="Responder..." required></textarea>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Responder</button>
                    </form>
                <?php elseif (!empty($ticket['resposta_admin'])): ?>
                    <div class="mt-2"><b>Resposta do Admin:</b> <?= nl2br(htmlspecialchars($ticket['resposta_admin'])) ?></div>
                    <div><b>Data da Resposta:</b> <?= htmlspecialchars($ticket['data_resposta']) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html> 