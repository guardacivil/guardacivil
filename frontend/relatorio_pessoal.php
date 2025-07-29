<?php
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once 'config.php';

// Buscar dados do pessoal
try {
    $sql = "SELECT u.nome, u.matricula, u.cpf, u.rg, g.nome as graduacao, s.nome as setor, p.nome as perfil, u.ativo
            FROM usuarios u
            LEFT JOIN graduacoes g ON u.graduacao_id = g.id
            LEFT JOIN setores s ON u.setor_id = s.id
            LEFT JOIN perfis p ON u.perfil_id = p.id
            ORDER BY g.nivel DESC, u.nome";
    $stmt = $pdo->query($sql);
    $pessoal = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Erro ao buscar dados: ' . $e->getMessage());
}

// Criar PDF
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('SIG');
$pdf->SetAuthor('SIG');
$pdf->SetTitle('Relatório de Pessoal');
$pdf->SetHeaderData('', 0, 'Relatório de Pessoal - Guarda Municipal', date('d/m/Y H:i'));
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
    <th>Nome</th>
    <th>Matrícula</th>
    <th>Graduação</th>
    <th>Setor</th>
    <th>Perfil</th>
    <th>Status</th>
</tr>';
foreach ($pessoal as $p) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($p['nome']) . '</td>';
    $html .= '<td>' . htmlspecialchars($p['matricula']) . '</td>';
    $html .= '<td>' . htmlspecialchars($p['graduacao']) . '</td>';
    $html .= '<td>' . htmlspecialchars($p['setor']) . '</td>';
    $html .= '<td>' . htmlspecialchars($p['perfil']) . '</td>';
    $html .= '<td>' . ($p['ativo'] ? 'Ativo' : 'Inativo') . '</td>';
    $html .= '</tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('relatorio_pessoal.pdf', 'I'); 