<?php
ini_set('display_errors', 0);
error_reporting(E_ERROR | E_PARSE);
require_once 'conexao.php';
require_once 'auth_check.php';
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';

// Classe customizada para borda, marca d'água e rodapé em todas as páginas
class CustomPDF extends TCPDF {
    public $imgLogo;
    public $imgCabecalho;
    public function Header() {
        // Marca d'água
        $this->SetAlpha(0.08);
        $this->Image($this->imgLogo, 60, 80, 90, 90, '', '', '', false, 300, '', false, false, 0);
        $this->SetAlpha(1);
        // Borda arredondada
        $this->SetLineWidth(1);
        $this->SetDrawColor(0,51,153);
        $this->RoundedRect(10, 10, 190, 277, 8, '1234');
        // Imagem de cabeçalho só na primeira página
        if ($this->PageNo() == 1) {
            $this->Image($this->imgCabecalho, 15, 15, 180, 28, '', '', '', false, 300, '', false, false, 0);
            $this->Ln(35);
        }
    }
    public function Footer() {
        $this->SetY(-25); // 1cm acima da borda
        $this->SetFont('helvetica', 'I', 9);
        $this->SetDrawColor(220,220,220);
        $this->Cell(0, 0, '', 'T');
        $this->Cell(0, 8, 'Documento gerado em: ' . date('d/m/Y H:i'), 0, 0, 'R');
    }
}

$imgLogo = __DIR__ . '/../public/img/logo.png';
$imgCabecalho = __DIR__ . '/../public/img/cabecalho.png';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die('ID de ocorrência inválido.');
}

$sql = "SELECT * FROM ocorrencias WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$ocorrencia = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ocorrencia) {
    die('Ocorrência não encontrada.');
}

// Inicia o PDF
$pdf = new CustomPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Sistema SMART');
$pdf->SetAuthor('Guarda Civil Municipal de Araçoiaba da Serra');
$pdf->SetTitle('Ocorrência nº ' . $ocorrencia['numero_ocorrencia']);
$pdf->SetMargins(15, 40, 15); // 1,5cm de margem
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();

// Ajusta Y após cabeçalho
$pdf->SetY(48);

// Quadro de destaque para o número da ocorrência
$pdf->SetFont('helvetica', 'B', 15);
$pdf->SetFillColor(230, 240, 255);
$pdf->SetDrawColor(0, 51, 153);
$pdf->SetTextColor(0, 51, 153);
$pdf->Cell(0, 14, 'Ocorrência nº ' . $ocorrencia['numero_ocorrencia'], 0, 1, 'C', 1);
$pdf->SetTextColor(0,0,0);
$pdf->Ln(6);

// Seção: Dados principais
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(0, 9, 'Dados da Ocorrência', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->SetFillColor(255,255,255);
$pdf->SetDrawColor(200,200,200);
// Remover campo status
$pdf->Cell(45, 8, 'Data:', 1, 0, 'L', 1);
$pdf->Cell(45, 8, 'Hora Início:', 1, 0, 'L', 1);
$pdf->Cell(45, 8, 'Local:', 1, 0, 'L', 1);
$pdf->Cell(0, 8, '', 0, 1, 'L', 0); // célula vazia para alinhar
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(45, 8, $ocorrencia['data'], 1, 0, 'L');
$pdf->Cell(45, 8, $ocorrencia['hora_inicio'], 1, 0, 'L');
$pdf->Cell(45, 8, $ocorrencia['local'], 1, 0, 'L');
$pdf->Cell(0, 8, '', 0, 1, 'L', 0);
$pdf->Cell(45, 8, 'Natureza:', 1, 0, 'L', 1);
$pdf->Cell(45, 8, 'Bairro:', 1, 0, 'L', 1);
$pdf->Cell(45, 8, 'Cidade:', 1, 0, 'L', 1);
$pdf->Cell(0, 8, 'Estado:', 1, 1, 'L', 1);
$pdf->Cell(45, 8, $ocorrencia['natureza'], 1, 0, 'L');
$pdf->Cell(45, 8, $ocorrencia['bairro'], 1, 0, 'L');
$pdf->Cell(45, 8, $ocorrencia['cidade'], 1, 0, 'L');
$pdf->Cell(0, 8, $ocorrencia['estado'], 1, 1, 'L');
$pdf->Cell(45, 8, 'CEP:', 1, 0, 'L', 1);
$pdf->Cell(0, 8, $ocorrencia['cep'], 1, 1, 'L');
$pdf->Ln(6);

// --- DADOS DO SOLICITANTE ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 9, 'Solicitante', 0, 1, 'L');
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->SetFont('helvetica', '', 11);
if (!empty($ocorrencia['nome_solicitante'])) $pdf->MultiCell(0, 7, 'Nome: ' . $ocorrencia['nome_solicitante'], 0, 'L');

// Exibir nascimento do solicitante apenas se não for CAD, deparou-se ou anonimo
$tipoSolicitante = strtolower($ocorrencia['solicitante_tipo'] ?? '');
if (!in_array($tipoSolicitante, ['cad', 'deparou-se', 'anonimo'])) {
    if (!empty($ocorrencia['nascimento_solicitante'])) {
        // Formatar data para dd/mm/aaaa
        $dataNasc = $ocorrencia['nascimento_solicitante'];
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNasc)) {
            $dataNasc = date('d/m/Y', strtotime($dataNasc));
        }
        $pdf->MultiCell(0, 7, 'Nascimento: ' . $dataNasc, 0, 'L');
    }
}
// RG mascarado
if (!empty($ocorrencia['rg_solicitante'])) {
    $rg = $ocorrencia['rg_solicitante'];
    // Mascara: mostra só o início e fim
    $rg = preg_replace('/(\d{2})\.(\d{3})\.(\d{2})(\d)-(\d)/', '$1.***.**$4-$5', $rg);
    if ($rg === $ocorrencia['rg_solicitante']) {
        // fallback para outros formatos
        $rg = substr($rg, 0, 2) . '.***.**' . substr($rg, -3);
    }
    $pdf->MultiCell(0, 7, 'RG: ' . $rg, 0, 'L');
}
if (!empty($ocorrencia['cpf_solicitante'])) $pdf->MultiCell(0, 7, 'CPF: ' . $ocorrencia['cpf_solicitante'], 0, 'L');
if (!empty($ocorrencia['telefone_solicitante'])) $pdf->MultiCell(0, 7, 'Telefone: ' . $ocorrencia['telefone_solicitante'], 0, 'L');
if (!empty($ocorrencia['endereco_solicitante'])) $pdf->MultiCell(0, 7, 'Endereço: ' . $ocorrencia['endereco_solicitante'], 0, 'L');
if (!empty($ocorrencia['bairro_solicitante'])) $pdf->MultiCell(0, 7, 'Bairro: ' . $ocorrencia['bairro_solicitante'], 0, 'L');
if (!empty($ocorrencia['cidade_solicitante'])) $pdf->MultiCell(0, 7, 'Cidade: ' . $ocorrencia['cidade_solicitante'], 0, 'L');
if (!empty($ocorrencia['estado_solicitante'])) $pdf->MultiCell(0, 7, 'Estado: ' . $ocorrencia['estado_solicitante'], 0, 'L');
if (!empty($ocorrencia['cep_solicitante'])) $pdf->MultiCell(0, 7, 'CEP: ' . $ocorrencia['cep_solicitante'], 0, 'L');
$pdf->Ln(2);
// --- DADOS DA VÍTIMA ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 9, 'Vítima', 0, 1, 'L');
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->SetFont('helvetica', '', 11);
if (!empty($ocorrencia['nome_vitima'])) $pdf->MultiCell(0, 7, 'Nome: ' . $ocorrencia['nome_vitima'], 0, 'L');
if (!empty($ocorrencia['nascimento_vitima'])) $pdf->MultiCell(0, 7, 'Nascimento: ' . $ocorrencia['nascimento_vitima'], 0, 'L');
if (!empty($ocorrencia['rg_vitima'])) $pdf->MultiCell(0, 7, 'RG: ' . $ocorrencia['rg_vitima'], 0, 'L');
if (!empty($ocorrencia['cpf_vitima'])) $pdf->MultiCell(0, 7, 'CPF: ' . $ocorrencia['cpf_vitima'], 0, 'L');
if (!empty($ocorrencia['telefone_vitima'])) $pdf->MultiCell(0, 7, 'Telefone: ' . $ocorrencia['telefone_vitima'], 0, 'L');
if (!empty($ocorrencia['endereco_vitima'])) $pdf->MultiCell(0, 7, 'Endereço: ' . $ocorrencia['endereco_vitima'], 0, 'L');
$pdf->Ln(2);
// --- DADOS DO AUTOR ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 9, 'Autor', 0, 1, 'L');
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->SetFont('helvetica', '', 11);
if (!empty($ocorrencia['nome_autor'])) $pdf->MultiCell(0, 7, 'Nome: ' . $ocorrencia['nome_autor'], 0, 'L');
if (!empty($ocorrencia['nascimento_autor'])) $pdf->MultiCell(0, 7, 'Nascimento: ' . $ocorrencia['nascimento_autor'], 0, 'L');
if (!empty($ocorrencia['rg_autor'])) $pdf->MultiCell(0, 7, 'RG: ' . $ocorrencia['rg_autor'], 0, 'L');
if (!empty($ocorrencia['cpf_autor'])) $pdf->MultiCell(0, 7, 'CPF: ' . $ocorrencia['cpf_autor'], 0, 'L');
if (!empty($ocorrencia['telefone_autor'])) $pdf->MultiCell(0, 7, 'Telefone: ' . $ocorrencia['telefone_autor'], 0, 'L');
if (!empty($ocorrencia['endereco_autor'])) $pdf->MultiCell(0, 7, 'Endereço: ' . $ocorrencia['endereco_autor'], 0, 'L');
$pdf->Ln(2);
// --- DADOS TESTEMUNHA 1 ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 9, 'Testemunha 1', 0, 1, 'L');
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->SetFont('helvetica', '', 11);
if (!empty($ocorrencia['nome_testemunha1'])) $pdf->MultiCell(0, 7, 'Nome: ' . $ocorrencia['nome_testemunha1'], 0, 'L');
if (!empty($ocorrencia['rg_testemunha1'])) $pdf->MultiCell(0, 7, 'RG: ' . $ocorrencia['rg_testemunha1'], 0, 'L');
if (!empty($ocorrencia['cpf_testemunha1'])) $pdf->MultiCell(0, 7, 'CPF: ' . $ocorrencia['cpf_testemunha1'], 0, 'L');
if (!empty($ocorrencia['telefone_testemunha1'])) $pdf->MultiCell(0, 7, 'Telefone: ' . $ocorrencia['telefone_testemunha1'], 0, 'L');
if (!empty($ocorrencia['endereco_testemunha1'])) $pdf->MultiCell(0, 7, 'Endereço: ' . $ocorrencia['endereco_testemunha1'], 0, 'L');
$pdf->Ln(2);
// --- DADOS TESTEMUNHA 2 ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 9, 'Testemunha 2', 0, 1, 'L');
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->SetFont('helvetica', '', 11);
if (!empty($ocorrencia['nome_testemunha2'])) $pdf->MultiCell(0, 7, 'Nome: ' . $ocorrencia['nome_testemunha2'], 0, 'L');
if (!empty($ocorrencia['rg_testemunha2'])) $pdf->MultiCell(0, 7, 'RG: ' . $ocorrencia['rg_testemunha2'], 0, 'L');
if (!empty($ocorrencia['cpf_testemunha2'])) $pdf->MultiCell(0, 7, 'CPF: ' . $ocorrencia['cpf_testemunha2'], 0, 'L');
if (!empty($ocorrencia['telefone_testemunha2'])) $pdf->MultiCell(0, 7, 'Telefone: ' . $ocorrencia['telefone_testemunha2'], 0, 'L');
if (!empty($ocorrencia['endereco_testemunha2'])) $pdf->MultiCell(0, 7, 'Endereço: ' . $ocorrencia['endereco_testemunha2'], 0, 'L');
$pdf->Ln(2);
// --- RELATO ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 9, 'Relato', 0, 1, 'L');
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->SetFont('helvetica', '', 11);
if (!empty($ocorrencia['relato'])) $pdf->MultiCell(0, 18, $ocorrencia['relato'], 1, 'L', 1);
$pdf->Ln(3);
// --- PROVIDÊNCIAS ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 9, 'Providências', 0, 1, 'L');
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->SetFont('helvetica', '', 11);
if (!empty($ocorrencia['providencias'])) $pdf->MultiCell(0, 14, $ocorrencia['providencias'], 1, 'L', 1);
$pdf->Ln(3);
// --- OBSERVAÇÕES ---
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 9, 'Observações', 0, 1, 'L');
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->SetFont('helvetica', '', 11);
if (!empty($ocorrencia['observacoes'])) $pdf->MultiCell(0, 12, $ocorrencia['observacoes'], 1, 'L', 1);
$pdf->Ln(3);

// Seção: Vítima (exemplo)
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 9, 'Vítima', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);
$pdf->MultiCell(0, 8, $ocorrencia['nome_vitima'], 0, 'L');

// Seção: Registro
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(100,100,100);
$pdf->Cell(0, 8, 'Registrada em: ' . $ocorrencia['data_registro'], 0, 1, 'R');
$pdf->SetTextColor(0,0,0);

// Formatar datas principais para dd/mm/aaaa
if (!empty($ocorrencia['data'])) $ocorrencia['data'] = date('d/m/Y', strtotime($ocorrencia['data']));
if (!empty($ocorrencia['data_fato'])) $ocorrencia['data_fato'] = date('d/m/Y', strtotime($ocorrencia['data_fato']));
if (!empty($ocorrencia['nascimento_vitima'])) $ocorrencia['nascimento_vitima'] = date('d/m/Y', strtotime($ocorrencia['nascimento_vitima']));
if (!empty($ocorrencia['nascimento_autor'])) $ocorrencia['nascimento_autor'] = date('d/m/Y', strtotime($ocorrencia['nascimento_autor']));

// Área de assinaturas centralizada e alinhada, bloco indivisível
$pdf->Ln(8);
$assinaturas = function($pdf) {
    // Assinatura das partes envolvidas
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(80, 12, '', 'B', 0, 'C');
    $pdf->Cell(10, 12, '', 0, 0);
    $pdf->Cell(80, 12, '', 'B', 1, 'C');
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(80, 7, 'Assinatura das partes envolvidas', 0, 0, 'C');
    $pdf->Cell(10, 7, '', 0, 0);
    $pdf->Cell(80, 7, 'Assinatura das partes envolvidas', 0, 1, 'C');
    $pdf->Ln(4);
    // Equipe
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(80, 12, '', 'B', 0, 'C');
    $pdf->Cell(10, 12, '', 0, 0);
    $pdf->Cell(80, 12, '', 'B', 1, 'C');
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(80, 7, 'Equipe', 0, 0, 'C');
    $pdf->Cell(10, 7, '', 0, 0);
    $pdf->Cell(80, 7, 'Equipe', 0, 1, 'C');
    $pdf->Ln(4);
    // Comandante Geral
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(80, 12, '', 'B', 1, 'C');
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(80, 7, 'Comandante Geral', 0, 1, 'C');
};

// Garante que bloco de assinaturas não será quebrado
$startY = $pdf->GetY();
$pdf->startTransaction();
$assinaturas($pdf);
if ($pdf->GetY() > ($pdf->getPageHeight() - 30)) { // Se não couber, volta e adiciona nova página
    $pdf->rollbackTransaction(true);
    $pdf->AddPage();
    $assinaturas($pdf);
}

// Sanitizar número da ocorrência para nome de arquivo
$numeroOcorrenciaFile = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $ocorrencia['numero_ocorrencia']);
$pdfPath = sys_get_temp_dir() . '/Ocorrencia_' . $numeroOcorrenciaFile . '_' . uniqid() . '.pdf';
// Salvar PDF em disco temporariamente
$pdf->Output($pdfPath, 'F');

// Exibir PDF normalmente para o usuário
$pdf->Output('Ocorrencia_' . $ocorrencia['numero_ocorrencia'] . '.pdf', 'I');
// Remover arquivo temporário
@unlink($pdfPath);
exit; 