<?php
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once 'config.php';

$formato = $_GET['formato'] ?? 'pdf';

// Buscar dados das escalas
try {
    $sql = "SELECT e.*, s.nome as setor_nome, u.nome as responsavel_nome FROM escalas e LEFT JOIN setores s ON e.setor_id = s.id LEFT JOIN usuarios u ON e.responsavel_id = u.id ORDER BY e.data_inicio DESC, e.id DESC";
    $stmt = $pdo->query($sql);
    $escalas = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Erro ao buscar dados: ' . $e->getMessage());
}

if ($formato === 'pdf') {
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('SIG');
    $pdf->SetAuthor('SIG');
    $pdf->SetTitle('Relatório de Escalas');
    $pdf->SetHeaderData('', 0, 'Relatório de Escalas - Guarda Municipal', date('d/m/Y H:i'));
    $pdf->setHeaderFont(Array('helvetica', '', 12));
    $pdf->setFooterFont(Array('helvetica', '', 10));
    $pdf->SetMargins(10, 25, 10);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);
    $html = '<table border="1" cellpadding="4">
    <tr style="background-color:#e5e7eb; font-weight:bold;">
        <th>ID</th><th>Nome</th><th>Setor</th><th>Responsável</th><th>Data Início</th><th>Data Fim</th><th>Turno</th><th>Status</th>
    </tr>';
    foreach ($escalas as $e) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($e['id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($e['nome'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($e['setor_nome'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($e['responsavel_nome'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($e['data_inicio'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($e['data_fim'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($e['turno'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($e['status'] ?? '') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('relatorio_escalas.pdf', 'I');
    exit;
}

if ($formato === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="relatorio_escalas.xls"');
    echo "ID\tNome\tSetor\tResponsável\tData Início\tData Fim\tTurno\tStatus\n";
    foreach ($escalas as $e) {
        echo ($e['id'] ?? '') . "\t" . ($e['nome'] ?? '') . "\t" . ($e['setor_nome'] ?? '') . "\t" . ($e['responsavel_nome'] ?? '') . "\t" . ($e['data_inicio'] ?? '') . "\t" . ($e['data_fim'] ?? '') . "\t" . ($e['turno'] ?? '') . "\t" . ($e['status'] ?? '') . "\n";
    }
    exit;
}

if ($formato === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="relatorio_escalas.csv"');
    echo "ID,Nome,Setor,Responsável,Data Início,Data Fim,Turno,Status\n";
    foreach ($escalas as $e) {
        echo '"' . ($e['id'] ?? '') . '","' . ($e['nome'] ?? '') . '","' . ($e['setor_nome'] ?? '') . '","' . ($e['responsavel_nome'] ?? '') . '","' . ($e['data_inicio'] ?? '') . '","' . ($e['data_fim'] ?? '') . '","' . ($e['turno'] ?? '') . '","' . ($e['status'] ?? '') . '"\n';
    }
    exit;
} 