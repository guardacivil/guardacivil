<?php
// importar_dados.php - Recebe upload de arquivo para importação de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        echo '<script>alert("Erro ao enviar arquivo.");window.history.back();</script>';
        exit;
    }
    $arquivo = $_FILES['arquivo'];
    $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
        echo '<script>alert("Formato de arquivo não suportado. Envie CSV ou Excel.");window.history.back();</script>';
        exit;
    }
    // Aqui você pode salvar o arquivo temporariamente e processar depois
    // Exemplo: move_uploaded_file($arquivo['tmp_name'], 'uploads/' . $arquivo['name']);
    echo '<script>alert("Arquivo enviado com sucesso! (Funcionalidade de importação em desenvolvimento)");window.location.href="relatorios.php";</script>';
    exit;
} else {
    header('Location: relatorios.php');
    exit;
} 