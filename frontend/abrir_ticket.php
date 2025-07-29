<?php
require_once 'conexao.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado para abrir um ticket.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assunto = isset($_POST['assunto']) ? trim($_POST['assunto']) : '';
    $mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
    $prioridade = isset($_POST['prioridade']) ? $_POST['prioridade'] : 'media';
    $usuario_id = $_SESSION['usuario_id'];
    if ($assunto && $mensagem) {
        try {
            $stmt = $pdo->prepare('INSERT INTO suporte (usuario_id, titulo, mensagem, prioridade, status) VALUES (?, ?, ?, ?, "aberto")');
            $stmt->execute([$usuario_id, $assunto, $mensagem, $prioridade]);
            // Enviar e-mail para o admin
            $admin_email = 'admin@seudominio.com'; // Troque para o e-mail real do admin
            $subject = "Novo ticket de suporte: $assunto";
            $body = "<b>Assunto:</b> $assunto<br><b>Mensagem:</b> $mensagem<br><b>Prioridade:</b> $prioridade<br><b>ID Usuário:</b> $usuario_id";
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8\r\n";
            $headers .= "From: sistema@seudominio.com\r\n";
            @mail($admin_email, $subject, $body, $headers);
            echo json_encode(['success' => true, 'message' => 'Ticket criado com sucesso!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar ticket: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
    }
    exit;
}
echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);
exit; 