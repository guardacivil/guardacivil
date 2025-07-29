<?php
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once 'config.php';

$formato = $_GET['formato'] ?? 'pdf';

// Buscar dados das comunicações
try {
    $sql = "SELECT c.*, u.nome as autor_nome, s.nome as setor_nome, g.nome as graduacao_minima_nome FROM comunicacoes c LEFT JOIN usuarios u ON c.autor_id = u.id LEFT JOIN setores s ON c.setor_id = s.id LEFT JOIN graduacoes g ON c.graduacao_minima = g.id ORDER BY c.created_at DESC, c.id DESC";
    $stmt = $pdo->query($sql);
    $comunicacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Erro ao buscar dados: ' . $e->getMessage());
}

if ($formato === 'pdf') {
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('SIG');
    $pdf->SetAuthor('SIG');
    $pdf->SetTitle('Relatório de Comunicações');
    $pdf->SetHeaderData('', 0, 'Relatório de Comunicações - Guarda Municipal', date('d/m/Y H:i'));
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
        <th>ID</th><th>Título</th><th>Autor</th><th>Setor</th><th>Graduação Mínima</th><th>Prioridade</th><th>Data</th>
    </tr>';
    foreach ($comunicacoes as $c) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($c['id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($c['titulo'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($c['autor_nome'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($c['setor_nome'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($c['graduacao_minima_nome'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($c['prioridade'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($c['created_at'] ?? '') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('relatorio_comunicacoes.pdf', 'I');
    exit;
}

if ($formato === 'excel') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="relatorio_comunicacoes.xls"');
    echo "ID\tTítulo\tAutor\tSetor\tGraduação Mínima\tPrioridade\tData\n";
    foreach ($comunicacoes as $c) {
        echo ($c['id'] ?? '') . "\t" . ($c['titulo'] ?? '') . "\t" . ($c['autor_nome'] ?? '') . "\t" . ($c['setor_nome'] ?? '') . "\t" . ($c['graduacao_minima_nome'] ?? '') . "\t" . ($c['prioridade'] ?? '') . "\t" . ($c['created_at'] ?? '') . "\n";
    }
    exit;
}

if ($formato === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="relatorio_comunicacoes.csv"');
    echo "ID,Título,Autor,Setor,Graduação Mínima,Prioridade,Data\n";
    foreach ($comunicacoes as $c) {
        echo '"' . ($c['id'] ?? '') . '","' . ($c['titulo'] ?? '') . '","' . ($c['autor_nome'] ?? '') . '","' . ($c['setor_nome'] ?? '') . '","' . ($c['graduacao_minima_nome'] ?? '') . '","' . ($c['prioridade'] ?? '') . '","' . ($c['created_at'] ?? '') . '"\n';
    }
    exit;
} 