<?php
// Suprimir avisos e erros para evitar quebra do PDF
error_reporting(E_ERROR | E_PARSE);
@ini_set('display_errors', 0);
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) {
    die('ID de escala inválido.');
}

// Buscar dados da escala
try {
    $sql = "SELECT e.*, s.nome as setor_nome, u.nome as responsavel_nome 
            FROM escalas e 
            LEFT JOIN setores s ON e.setor_id = s.id 
            LEFT JOIN usuarios u ON e.responsavel_id = u.id 
            WHERE e.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $escala = $stmt->fetch();
    if (!$escala) die('Escala não encontrada.');

    // Buscar membros da escala
    $sqlM = "SELECT u.nome FROM escala_membros em 
              LEFT JOIN usuarios u ON em.usuario_id = u.id 
              WHERE em.escala_id = ?";
    $stmtM = $pdo->prepare($sqlM);
    $stmtM->execute([$id]);
    $membros = $stmtM->fetchAll();
} catch (PDOException $e) {
    die('Erro ao buscar dados: ' . $e->getMessage());
}

// Criar PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('SIG');
$pdf->SetAuthor('SIG');
$pdf->SetTitle('Escala - ' . $escala['nome']);
$pdf->SetHeaderData('', 0, 'Escala: ' . $escala['nome'], date('d/m/Y H:i'));
$pdf->setHeaderFont(Array('helvetica', '', 12));
$pdf->setFooterFont(Array('helvetica', '', 10));
$pdf->SetMargins(10, 25, 10);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Adicionar imagem de cabeçalho
$pdf->Image(dirname(__FILE__).'/img/cabecalho.png', 10, 10, 190, 0, 'PNG', '', '', false, 300, '', false, false, 0, false, false, false);
$pdf->Ln(35); // Espaço após imagem

// Tabela colorida com dados principais
$html = '<h2 style="text-align:center; color:#1e3a8a;">Escala de Serviço</h2>';
$html .= '<table border="1" cellpadding="5" style="border-collapse:collapse; width:100%; margin-bottom:16px;">';
$html .= '<tr style="background-color:#e0e7ff;"><td style="font-weight:bold;width:120px;">Nome</td><td>' . htmlspecialchars($escala['nome']) . '</td></tr>';
$html .= '<tr style="background-color:#f1f5f9;"><td style="font-weight:bold;">Setor</td><td>' . htmlspecialchars($escala['setor_nome']) . '</td></tr>';
$html .= '<tr style="background-color:#e0e7ff;"><td style="font-weight:bold;">Responsável</td><td>' . htmlspecialchars($escala['responsavel_nome']) . '</td></tr>';
$html .= '<tr style="background-color:#f1f5f9;"><td style="font-weight:bold;">Período</td><td>' . date('d/m/Y', strtotime($escala['data_inicio'])) . ' a ' . date('d/m/Y', strtotime($escala['data_fim'])) . '</td></tr>';
$html .= '<tr style="background-color:#e0e7ff;"><td style="font-weight:bold;">Turno</td><td>' . ucfirst($escala['turno']) . '</td></tr>';
$html .= '<tr style="background-color:#f1f5f9;"><td style="font-weight:bold;">Observações</td><td>' . nl2br(htmlspecialchars($escala['observacoes'])) . '</td></tr>';
$html .= '</table>';

// Tabela de membros
$html .= '<h3 style="margin-top:24px; color:#1e3a8a;">Membros da Escala</h3>';
$html .= '<table border="1" cellpadding="4" style="border-collapse:collapse; width:100%;">';
$html .= '<tr style="background-color:#e5e7eb; font-weight:bold;"><th>Nome</th></tr>';
foreach ($membros as $m) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($m['nome']) . '</td>';
    $html .= '</tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('escala_' . $escala['id'] . '.pdf', 'I'); 