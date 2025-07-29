<?php
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once 'config.php';

$formato = $_GET['formato'] ?? 'pdf';

// Buscar dados das atividades (exemplo: ocorrências)
try {
    $sql = "SELECT * FROM ocorrencias ORDER BY data DESC, id DESC";
    $stmt = $pdo->query($sql);
    $atividades = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Erro ao buscar dados: ' . $e->getMessage());
}

if ($formato === 'pdf') {
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('SIG');
    $pdf->SetAuthor('SIG');
    $pdf->SetTitle('Relatório de Atividades');
    $pdf->SetHeaderData('', 0, 'Relatório de Atividades - Guarda Municipal', date('d/m/Y H:i'));
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
    foreach ($atividades as $a) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($a['id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($a['numero_ocorrencia'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($a['data'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($a['natureza'] ?? $a['tipo_ocorrencia'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($a['local'] ?? $a['local_ocorrencia'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($a['status'] ?? '') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('relatorio_atividades.pdf', 'I');
    exit;
}

if ($formato === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="relatorio_atividades.xls"');
    echo "ID\tNúmero\tData\tTipo\tLocal\tStatus\n";
    foreach ($atividades as $a) {
        echo ($a['id'] ?? '') . "\t" . ($a['numero_ocorrencia'] ?? '') . "\t" . ($a['data'] ?? '') . "\t" . ($a['natureza'] ?? $a['tipo_ocorrencia'] ?? '') . "\t" . ($a['local'] ?? $a['local_ocorrencia'] ?? '') . "\t" . ($a['status'] ?? '') . "\n";
    }
    exit;
}

if ($formato === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="relatorio_atividades.csv"');
    echo "ID,Número,Data,Tipo,Local,Status\n";
    foreach ($atividades as $a) {
        echo '"' . ($a['id'] ?? '') . '","' . ($a['numero_ocorrencia'] ?? '') . '","' . ($a['data'] ?? '') . '","' . ($a['natureza'] ?? $a['tipo_ocorrencia'] ?? '') . '","' . ($a['local'] ?? $a['local_ocorrencia'] ?? '') . '","' . ($a['status'] ?? '') . '"\n';
    }
    exit;
} 