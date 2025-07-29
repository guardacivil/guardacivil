<?php
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once 'config.php';

// Buscar escalas
try {
    $sql = "SELECT e.*, s.nome as setor_nome, u.nome as responsavel_nome 
            FROM escalas e 
            LEFT JOIN setores s ON e.setor_id = s.id 
            LEFT JOIN usuarios u ON e.responsavel_id = u.id 
            ORDER BY e.data_inicio DESC";
    $stmt = $pdo->query($sql);
    $escalas = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Erro ao buscar dados: ' . $e->getMessage());
}

// Criar PDF
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

$html = '<h2>Relatório de Escalas</h2>';
$html .= '<table border="1" cellpadding="4">
<tr style="background-color:#e5e7eb; font-weight:bold;">
    <th>Nome</th>
    <th>Setor</th>
    <th>Responsável</th>
    <th>Período</th>
    <th>Turno</th>
    <th>Status</th>
    <th>Membros</th>
</tr>';
foreach ($escalas as $e) {
    // Buscar quantidade de membros
    $stmtM = $pdo->prepare("SELECT COUNT(*) FROM escala_membros WHERE escala_id = ?");
    $stmtM->execute([$e['id']]);
    $qtdMembros = $stmtM->fetchColumn();
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($e['nome']) . '</td>';
    $html .= '<td>' . htmlspecialchars($e['setor_nome']) . '</td>';
    $html .= '<td>' . htmlspecialchars($e['responsavel_nome']) . '</td>';
    $html .= '<td>' . date('d/m/Y', strtotime($e['data_inicio'])) . ' a ' . date('d/m/Y', strtotime($e['data_fim'])) . '</td>';
    $html .= '<td>' . ucfirst($e['turno']) . '</td>';
    $html .= '<td>' . ucfirst($e['status']) . '</td>';
    $html .= '<td>' . $qtdMembros . '</td>';
    $html .= '</tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('relatorio_escalas.pdf', 'I'); 