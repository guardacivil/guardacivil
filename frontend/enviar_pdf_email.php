<?php
require_once 'auth_check.php';
require_once 'config.php';
require_once 'pdf_ocorrencia_util.php';
require_once 'enviar_email_ocorrencia.php';

if (!isset($_POST['ids']) || !is_array($_POST['ids']) || count($_POST['ids']) == 0) {
    die('Nenhuma ocorrência selecionada.');
}

$ids = array_map('intval', $_POST['ids']);
$sucesso = 0;
$falha = 0;
$erroMsgDetalhado = '';

foreach ($ids as $id) {
    // Buscar ocorrência
    $stmt = $pdo->prepare('SELECT * FROM ocorrencias WHERE id = ?');
    $stmt->execute([$id]);
    $ocorrencia = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ocorrencia) {
        $falha++;
        continue;
    }
    $pdf_content = gerarPdfOcorrencia($id, $pdo);
    if (!$pdf_content) {
        $falha++;
        continue;
    }
    $result = enviarEmailOcorrencia($ocorrencia, $pdf_content, $erroMsgDetalhado);
    if ($result) {
        $sucesso++;
    } else {
        $falha++;
        $erroMsgDetalhado = $erroMsgDetalhado ? $erroMsgDetalhado . ' Erro: ' . $erroMsgDetalhado : $erroMsgDetalhado;
    }
}

$msg = "$sucesso ocorrência(s) enviada(s) com sucesso. $falha falha(s).";
if (!empty($erroMsgDetalhado)) {
    $msg .= ' Erro: ' . $erroMsgDetalhado;
}
header('Location: historico_sqlite.php?msg=' . urlencode($msg));
exit; 