<?php
require_once 'conexao.php';
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';

function gerarPdfOcorrencia($id, $pdo, &$erro = null, $pdf_path = null) {
    // Buscar ocorrência
    $sql = "SELECT * FROM ocorrencias WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $ocorrencia = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ocorrencia) {
        $erro = 'Ocorrência não encontrada no banco de dados.';
        return false;
    }

    // Classe customizada igual ao gerar_pdf_ocorrencia.php
    // Corrigir caminhos das imagens para garantir que sempre existam
    // Forçar uso do JPG e corrigir barras para TCPDF/Windows
    // Forçar uso do caminho absoluto do PNG fornecido pelo usuário
    $imgCabecalho = __DIR__ . '/../public/img/cabecalho.png';
    $imgCabecalho = str_replace('\\', '/', $imgCabecalho);
    $imgLogo = str_replace('\\', '/', realpath(__DIR__ . '/../public/img/logo.png'));
    if (!$imgLogo) $imgLogo = str_replace('\\', '/', realpath(__DIR__ . '/../public/img/logo.jpg'));
    class CustomPDF extends TCPDF {
        public $imgLogo;
        public $imgCabecalho;
        public function Header() {
            $this->SetAlpha(0.08);
            if ($this->imgLogo && file_exists($this->imgLogo)) {
                $this->Image($this->imgLogo, 60, 80, 90, 90, '', '', '', false, 300, '', false, false, 0);
            }
            $this->SetAlpha(1);
            $this->SetLineWidth(1);
            $this->SetDrawColor(0,51,153);
            $this->RoundedRect(10, 10, 190, 277, 8, '1234');
            if ($this->PageNo() == 1 && $this->imgCabecalho && file_exists($this->imgCabecalho)) {
                try {
                    $this->Image($this->imgCabecalho, 15, 15, 180, 28, '', '', '', false, 300, '', false, false, 0);
                } catch (Exception $e) {
                    file_put_contents(__DIR__.'/erro_tcpdf.log', $e->getMessage());
                }
                $this->Ln(35);
            }
        }
        public function Footer() {
            $this->SetY(-25);
            $this->SetFont('helvetica', 'I', 9);
            $this->SetDrawColor(220,220,220);
            $this->Cell(0, 0, '', 'T');
            $this->Cell(0, 8, 'Documento gerado em: ' . date('d/m/Y H:i'), 0, 0, 'R');
        }
    }

    $pdf = new CustomPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->imgLogo = $imgLogo;
    $pdf->imgCabecalho = $imgCabecalho;
    $pdf->SetCreator('Sistema SMART');
    $pdf->SetAuthor('Guarda Civil Municipal de Araçoiaba da Serra');
    $pdf->SetTitle('Ocorrência nº ' . $ocorrencia['numero_ocorrencia']);
    $pdf->SetMargins(15, 40, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();
    $pdf->SetY(48);
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->SetFillColor(230, 240, 255);
    $pdf->SetDrawColor(0, 51, 153);
    $pdf->SetTextColor(0, 51, 153);
    $pdf->Cell(0, 14, 'Ocorrência nº ' . $ocorrencia['numero_ocorrencia'], 0, 1, 'C', 1);
    $pdf->SetTextColor(0,0,0);
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(0, 9, 'Dados da Ocorrência', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetDrawColor(200,200,200);
    // Substituir exibição da data principal
    $dataPrincipal = !empty($ocorrencia['data_fato']) ? $ocorrencia['data_fato'] : $ocorrencia['data'];
    $pdf->Cell(45, 8, 'Data:', 1, 0, 'L', 1);
    $pdf->Cell(45, 8, 'Hora Início:', 1, 0, 'L', 1);
    $pdf->Cell(45, 8, 'Local:', 1, 0, 'L', 1);
    $pdf->Cell(0, 8, '', 0, 1, 'L', 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(45, 8, formatarDataBR($dataPrincipal), 1, 0, 'L');
    $pdf->Cell(45, 8, $ocorrencia['hora_fato'], 1, 0, 'L');
    $pdf->Cell(45, 8, $ocorrencia['local_fato'], 1, 0, 'L');
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
    if (!empty($ocorrencia['nome_solicitante'])) {
        $pdf->MultiCell(0, 7, 'Nome: ' . $ocorrencia['nome_solicitante'], 0, 'L');
        if (!empty($ocorrencia['assinatura_solicitante'])) {
            $yAss = $pdf->GetY();
            $tmp = tempnam(sys_get_temp_dir(), 'ass_sol_') . '.png';
            file_put_contents($tmp, $ocorrencia['assinatura_solicitante']);
            $pdf->Image($tmp, 20, $yAss, 60, 18, 'PNG');
            unlink($tmp);
            $pdf->Ln(20);
        }
    }
    if (!empty($ocorrencia['nascimento_solicitante'])) $pdf->MultiCell(0, 7, 'Nascimento: ' . formatarDataBR($ocorrencia['nascimento_solicitante']), 0, 'L');
    if (!empty($ocorrencia['rg_solicitante'])) $pdf->MultiCell(0, 7, 'RG: ' . $ocorrencia['rg_solicitante'], 0, 'L');
    if (!empty($ocorrencia['cpf_solicitante'])) $pdf->MultiCell(0, 7, 'CPF: ' . mascaraCPF($ocorrencia['cpf_solicitante']), 0, 'L');
    if (!empty($ocorrencia['telefone_solicitante'])) $pdf->MultiCell(0, 7, 'Telefone: ' . $ocorrencia['telefone_solicitante'], 0, 'L');
    if (!empty($ocorrencia['endereco_solicitante'])) $pdf->MultiCell(0, 7, 'Endereço: [OCULTO]', 0, 'L');
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
    if (!empty($ocorrencia['nome_vitima'])) {
        $pdf->MultiCell(0, 7, 'Nome: ' . $ocorrencia['nome_vitima'], 0, 'L');
        if (!empty($ocorrencia['assinatura_vitima'])) {
            $yAss = $pdf->GetY();
            $tmp = tempnam(sys_get_temp_dir(), 'ass_vit_') . '.png';
            file_put_contents($tmp, $ocorrencia['assinatura_vitima']);
            $pdf->Image($tmp, 20, $yAss, 60, 18, 'PNG');
            unlink($tmp);
            $pdf->Ln(20);
        }
    }
    if (!empty($ocorrencia['nascimento_vitima'])) $pdf->MultiCell(0, 7, 'Nascimento: ' . formatarDataBR($ocorrencia['nascimento_vitima']), 0, 'L');
    if (!empty($ocorrencia['rg_vitima'])) $pdf->MultiCell(0, 7, 'RG: ' . $ocorrencia['rg_vitima'], 0, 'L');
    if (!empty($ocorrencia['cpf_vitima'])) $pdf->MultiCell(0, 7, 'CPF: ' . mascaraCPF($ocorrencia['cpf_vitima']), 0, 'L');
    if (!empty($ocorrencia['telefone_vitima'])) $pdf->MultiCell(0, 7, 'Telefone: ' . $ocorrencia['telefone_vitima'], 0, 'L');
    if (!empty($ocorrencia['endereco_vitima'])) $pdf->MultiCell(0, 7, 'Endereço: [OCULTO]', 0, 'L');
    $pdf->Ln(2);
    // --- DADOS DO AUTOR ---
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 9, 'Autor', 0, 1, 'L');
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
    $pdf->SetFont('helvetica', '', 11);
    if (!empty($ocorrencia['nome_autor'])) {
        $pdf->MultiCell(0, 7, 'Nome: ' . $ocorrencia['nome_autor'], 0, 'L');
        if (!empty($ocorrencia['assinatura_autor'])) {
            $yAss = $pdf->GetY();
            $tmp = tempnam(sys_get_temp_dir(), 'ass_aut_') . '.png';
            file_put_contents($tmp, $ocorrencia['assinatura_autor']);
            $pdf->Image($tmp, 20, $yAss, 60, 18, 'PNG');
            unlink($tmp);
            $pdf->Ln(20);
        }
    }
    if (!empty($ocorrencia['nascimento_autor'])) $pdf->MultiCell(0, 7, 'Nascimento: ' . formatarDataBR($ocorrencia['nascimento_autor']), 0, 'L');
    if (!empty($ocorrencia['rg_autor'])) $pdf->MultiCell(0, 7, 'RG: ' . $ocorrencia['rg_autor'], 0, 'L');
    if (!empty($ocorrencia['cpf_autor'])) $pdf->MultiCell(0, 7, 'CPF: ' . mascaraCPF($ocorrencia['cpf_autor']), 0, 'L');
    if (!empty($ocorrencia['telefone_autor'])) $pdf->MultiCell(0, 7, 'Telefone: ' . $ocorrencia['telefone_autor'], 0, 'L');
    if (!empty($ocorrencia['endereco_autor'])) $pdf->MultiCell(0, 7, 'Endereço: [OCULTO]', 0, 'L');
    $pdf->Ln(2);
    // --- DADOS TESTEMUNHA 1 ---
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 9, 'Testemunha 1', 0, 1, 'L');
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
    $pdf->SetFont('helvetica', '', 11);
    if (!empty($ocorrencia['nome_testemunha1'])) {
        $pdf->MultiCell(0, 7, 'Nome: ' . $ocorrencia['nome_testemunha1'], 0, 'L');
        if (!empty($ocorrencia['assinatura_testemunha1'])) {
            $yAss = $pdf->GetY();
            $tmp = tempnam(sys_get_temp_dir(), 'ass_t1_') . '.png';
            file_put_contents($tmp, $ocorrencia['assinatura_testemunha1']);
            $pdf->Image($tmp, 20, $yAss, 60, 18, 'PNG');
            unlink($tmp);
            $pdf->Ln(20);
        }
    }
    if (!empty($ocorrencia['rg_testemunha1'])) $pdf->MultiCell(0, 7, 'RG: ' . $ocorrencia['rg_testemunha1'], 0, 'L');
    if (!empty($ocorrencia['cpf_testemunha1'])) $pdf->MultiCell(0, 7, 'CPF: ' . mascaraCPF($ocorrencia['cpf_testemunha1']), 0, 'L');
    if (!empty($ocorrencia['telefone_testemunha1'])) $pdf->MultiCell(0, 7, 'Telefone: ' . $ocorrencia['telefone_testemunha1'], 0, 'L');
    if (!empty($ocorrencia['endereco_testemunha1'])) $pdf->MultiCell(0, 7, 'Endereço: [OCULTO]', 0, 'L');
    $pdf->Ln(2);
    // --- DADOS TESTEMUNHA 2 ---
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 9, 'Testemunha 2', 0, 1, 'L');
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
    $pdf->SetFont('helvetica', '', 11);
    if (!empty($ocorrencia['nome_testemunha2'])) {
        $pdf->MultiCell(0, 7, 'Nome: ' . $ocorrencia['nome_testemunha2'], 0, 'L');
        if (!empty($ocorrencia['assinatura_testemunha2'])) {
            $yAss = $pdf->GetY();
            $tmp = tempnam(sys_get_temp_dir(), 'ass_t2_') . '.png';
            file_put_contents($tmp, $ocorrencia['assinatura_testemunha2']);
            $pdf->Image($tmp, 20, $yAss, 60, 18, 'PNG');
            unlink($tmp);
            $pdf->Ln(20);
        }
    }
    if (!empty($ocorrencia['rg_testemunha2'])) $pdf->MultiCell(0, 7, 'RG: ' . $ocorrencia['rg_testemunha2'], 0, 'L');
    if (!empty($ocorrencia['cpf_testemunha2'])) $pdf->MultiCell(0, 7, 'CPF: ' . mascaraCPF($ocorrencia['cpf_testemunha2']), 0, 'L');
    if (!empty($ocorrencia['telefone_testemunha2'])) $pdf->MultiCell(0, 7, 'Telefone: ' . $ocorrencia['telefone_testemunha2'], 0, 'L');
    if (!empty($ocorrencia['endereco_testemunha2'])) $pdf->MultiCell(0, 7, 'Endereço: [OCULTO]', 0, 'L');
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
    // Área de assinaturas centralizada e alinhada, bloco indivisível
    $pdf->Ln(8);
    $assinaturas = function($pdf) {
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(80, 12, '', 'B', 0, 'C');
        $pdf->Cell(10, 12, '', 0, 0);
        $pdf->Cell(80, 12, '', 'B', 1, 'C');
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(80, 7, 'Assinatura das partes envolvidas', 0, 0, 'C');
        $pdf->Cell(10, 7, '', 0, 0);
        $pdf->Cell(80, 7, 'Assinatura das partes envolvidas', 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(80, 12, '', 'B', 0, 'C');
        $pdf->Cell(10, 12, '', 0, 0);
        $pdf->Cell(80, 12, '', 'B', 1, 'C');
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(80, 7, 'Equipe', 0, 0, 'C');
        $pdf->Cell(10, 7, '', 0, 0);
        $pdf->Cell(80, 7, 'Equipe', 0, 1, 'C');
        $pdf->Ln(4);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(80, 12, '', 'B', 1, 'C');
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->Cell(80, 7, 'Comandante Geral', 0, 1, 'C');
    };
    $startY = $pdf->GetY();
    $pdf->startTransaction();
    $assinaturas($pdf);
    if ($pdf->GetY() > ($pdf->getPageHeight() - 30)) {
        $pdf->rollbackTransaction(true);
        $pdf->AddPage();
        $assinaturas($pdf);
    }
    // Salvar PDF
    if ($pdf_path) {
        try {
            $pdf->Output($pdf_path, 'F');
            return $pdf_path;
        } catch (Exception $e) {
            $erro = 'Erro ao salvar PDF: ' . $e->getMessage();
            return false;
        }
    } else {
        // Salva em arquivo temporário
        $tmp = tempnam(sys_get_temp_dir(), 'ocorrencia_') . '.pdf';
        $pdf->Output($tmp, 'F');
        return $tmp;
    }
}

function gerarPdfParte($parte, $pdf_path = null) {
    require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
    $imgCabecalho = realpath(__DIR__ . '/../public/img/cabecalho.png');
    class PartePDF extends TCPDF {
        public $imgCabecalho;
        public function Header() {
            // Imagem de cabeçalho
            if ($this->imgCabecalho && file_exists($this->imgCabecalho)) {
                $this->Image($this->imgCabecalho, 15, 15, 180, 28, '', '', '', false, 300, '', false, false, 0);
                $this->Ln(35);
            }
            // Bordas arredondadas
            $this->SetLineWidth(1);
            $this->SetDrawColor(0,51,153);
            $this->RoundedRect(10, 10, 190, 277, 8, '1234');
        }
        public function Footer() {
            $this->SetY(-25);
            $this->SetFont('helvetica', 'I', 9);
            $this->SetDrawColor(220,220,220);
            $this->Cell(0, 0, '', 'T');
            $this->Cell(0, 8, 'Documento gerado em: ' . date('d/m/Y H:i'), 0, 0, 'R');
        }
    }
    $pdf = new PartePDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->imgCabecalho = $imgCabecalho;
    $pdf->SetCreator('Sistema SMART');
    $pdf->SetAuthor('Guarda Civil Municipal de Araçoiaba da Serra');
    $pdf->SetTitle('Parte nº ' . $parte['numero']);
    $pdf->SetMargins(15, 48, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();
    $pdf->SetY(55);
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->Cell(0, 12, 'Parte nº ' . $parte['numero'], 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Ln(2);
    $pdf->MultiCell(0, 8, 'Data: ' . (isset($parte['data']) ? date('d/m/Y', strtotime($parte['data'])) : '') . '   Hora: ' . ($parte['hora'] ?? ''), 0, 'L');
    $pdf->MultiCell(0, 8, 'De: ' . ($parte['do_nome'] ?? ''), 0, 'L');
    $pdf->MultiCell(0, 8, 'Para: ' . ($parte['ao'] ?? ''), 0, 'L');
    $pdf->MultiCell(0, 8, 'Assunto: ' . ($parte['assunto'] ?? ''), 0, 'L');
    $pdf->MultiCell(0, 8, 'Referência: ' . ($parte['referencia'] ?? ''), 0, 'L');
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Relato', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(0, 10, $parte['relato'], 1, 'L', 0);
    $pdf->Ln(2);
    // Status da parte
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'Status do Processo', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $status = isset($parte['status']) ? $parte['status'] : 'aberto';
    if ($status === 'encerrado') {
        $linha = 'Encerrado';
        if (!empty($parte['data_encerramento'])) {
            $linha .= ' em ' . date('d/m/Y H:i', strtotime($parte['data_encerramento']));
        }
        if (!empty($parte['encerrado_por'])) {
            // Buscar nome do usuário que encerrou
            if (isset($GLOBALS['pdo'])) {
                $stmtUser = $GLOBALS['pdo']->prepare('SELECT nome FROM usuarios WHERE id = ?');
                $stmtUser->execute([$parte['encerrado_por']]);
                $user = $stmtUser->fetch();
                if ($user) $linha .= ' por ' . $user['nome'];
            }
        }
        $pdf->MultiCell(0, 8, $linha, 1, 'L', 0);
    } elseif ($status === 'aguardando') {
        $pdf->MultiCell(0, 8, 'Aguardando', 1, 'L', 0);
    } else {
        $pdf->MultiCell(0, 8, 'Aberto', 1, 'L', 0);
    }
    $pdf->Ln(2);
    // Histórico de respostas
    if (isset($parte['id']) && isset($GLOBALS['pdo'])) {
        $stmtResp = $GLOBALS['pdo']->prepare('SELECT * FROM parte_respostas WHERE parte_id = ? ORDER BY data_resposta ASC');
        $stmtResp->execute([$parte['id']]);
        $respostas = $stmtResp->fetchAll();
        if ($respostas) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Histórico de Respostas', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 11);
            foreach ($respostas as $resp) {
                $pdf->MultiCell(0, 8, $resp['usuario_nome'] . ' respondeu em ' . date('d/m/Y H:i', strtotime($resp['data_resposta'])) . ":\n" . $resp['resposta'], 1, 'L', 0);
                $pdf->Ln(1);
            }
            $pdf->Ln(2);
        }
    }
    if (!empty($parte['resposta'])) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'Resposta', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 10, $parte['resposta'], 1, 'L', 0);
        $pdf->Ln(2);
    }
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, 'Assinatura: ' . $parte['assinatura'], 0, 1, 'L');
    $pdf->Cell(0, 8, 'Documento gerado em: ' . date('d/m/Y H:i'), 0, 1, 'R');
    if ($pdf_path) {
        $pdf->Output($pdf_path, 'F');
        return $pdf_path;
    } else {
        $tmp = tempnam(sys_get_temp_dir(), 'parte_') . '.pdf';
        $pdf->Output($tmp, 'F');
        return $tmp;
    }
}

function formatarDataBR($data) {
    if (!$data || $data == '0000-00-00') return '';
    $partes = explode('-', $data);
    if (count($partes) === 3) return $partes[2] . '/' . $partes[1] . '/' . $partes[0];
    return $data;
}

function mascaraCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) return $cpf;
    return substr($cpf, 0, 3) . '.***.***-' . substr($cpf, -2);
} 