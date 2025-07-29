<?php
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once 'config.php';

$formato = $_GET['formato'] ?? 'pdf';

// Buscar dados das ocorrências
try {
    $sql = "SELECT * FROM ocorrencias ORDER BY data DESC, id DESC";
    $stmt = $pdo->query($sql);
    $ocorrencias = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Erro ao buscar dados: ' . $e->getMessage());
}

if ($formato === 'pdf') {
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('SIG');
    $pdf->SetAuthor('SIG');
    $pdf->SetTitle('Relatório de Ocorrências');
    $pdf->SetHeaderData('', 0, 'Relatório de Ocorrências - Guarda Municipal', date('d/m/Y H:i'));
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
        <th>ID</th><th>Número</th><th>Data</th><th>Tipo</th><th>Local</th><th>Status</th>
    </tr>';
    foreach ($ocorrencias as $o) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($o['id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($o['numero_ocorrencia'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($o['data'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($o['natureza'] ?? $o['tipo_ocorrencia'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($o['local'] ?? $o['local_ocorrencia'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($o['status'] ?? '') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('relatorio_ocorrencias.pdf', 'I');
    exit;
}

if ($formato === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="relatorio_ocorrencias.xls"');
    echo "ID\tNúmero\tData\tTipo\tLocal\tStatus\n";
    foreach ($ocorrencias as $o) {
        echo ($o['id'] ?? '') . "\t" . ($o['numero_ocorrencia'] ?? '') . "\t" . ($o['data'] ?? '') . "\t" . ($o['natureza'] ?? $o['tipo_ocorrencia'] ?? '') . "\t" . ($o['local'] ?? $o['local_ocorrencia'] ?? '') . "\t" . ($o['status'] ?? '') . "\n";
    }
    exit;
}

if ($formato === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="relatorio_ocorrencias.csv"');
    echo "ID,Número,Data,Tipo,Local,Status\n";
    foreach ($ocorrencias as $o) {
        echo '"' . ($o['id'] ?? '') . '","' . ($o['numero_ocorrencia'] ?? '') . '","' . ($o['data'] ?? '') . '","' . ($o['natureza'] ?? $o['tipo_ocorrencia'] ?? '') . '","' . ($o['local'] ?? $o['local_ocorrencia'] ?? '') . '","' . ($o['status'] ?? '') . '"\n';
    }
    exit;
} 