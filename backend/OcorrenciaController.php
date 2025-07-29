<?php
/**
 * Controlador de Ocorrências - Gerencia todas as operações relacionadas
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class OcorrenciaController {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Criar nova ocorrência
     */
    public function create($data) {
        try {
            // Validar dados obrigatórios
            $required_fields = ['natureza', 'data_fato', 'local_fato'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Campo '$field' é obrigatório"];
                }
            }
            
            // Sanitizar dados
            $data = $this->sanitizeData($data);
            
            // Processar assinaturas
            $assinaturas = $this->processSignatures($data);
            
            // Preparar dados para inserção
            $insert_data = $this->prepareInsertData($data, $assinaturas);
            
            // Inserir no banco
            $id = $this->db->insertOcorrencia($insert_data);
            
            // Gerar PDF
            $pdf_content = $this->generatePDF($id);
            
            // Enviar e-mail (opcional)
            if (!empty($data['enviar_email'])) {
                $this->sendEmail($id, $pdf_content);
            }
            
            logMessage('INFO', 'Ocorrência criada com sucesso', ['id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Ocorrência registrada com sucesso',
                'data' => ['id' => $id]
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao criar ocorrência', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro ao registrar ocorrência: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obter ocorrência por ID
     */
    public function get($id) {
        try {
            $ocorrencia = $this->db->getOcorrencia($id);
            
            if (!$ocorrencia) {
                return ['success' => false, 'message' => 'Ocorrência não encontrada'];
            }
            
            // Verificar permissão
            $user = $this->auth->getCurrentUser();
            if ($user['perfil_tipo'] !== 'admin' && $ocorrencia['usuario_id'] != $user['id']) {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            return ['success' => true, 'data' => $ocorrencia];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter ocorrência', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Listar ocorrências
     */
    public function list($filters = []) {
        try {
            $user = $this->auth->getCurrentUser();
            
            // Se não for admin, só mostra suas próprias ocorrências
            $usuario_id = ($user['perfil_tipo'] === 'admin') ? null : $user['id'];
            
            $ocorrencias = $this->db->getAllOcorrencias($usuario_id);
            
            // Aplicar filtros
            if (!empty($filters)) {
                $ocorrencias = $this->applyFilters($ocorrencias, $filters);
            }
            
            // Mascarar dados sensíveis
            $ocorrencias = $this->maskSensitiveData($ocorrencias);
            
            return ['success' => true, 'data' => $ocorrencias];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao listar ocorrências', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Atualizar ocorrência
     */
    public function update($id, $data) {
        try {
            // Verificar se ocorrência existe
            $ocorrencia = $this->db->getOcorrencia($id);
            if (!$ocorrencia) {
                return ['success' => false, 'message' => 'Ocorrência não encontrada'];
            }
            
            // Verificar permissão
            $user = $this->auth->getCurrentUser();
            if ($user['perfil_tipo'] !== 'admin' && $ocorrencia['usuario_id'] != $user['id']) {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Sanitizar dados
            $data = $this->sanitizeData($data);
            
            // Atualizar no banco
            $this->db->updateOcorrencia($id, $data);
            
            logMessage('INFO', 'Ocorrência atualizada', ['id' => $id]);
            
            return ['success' => true, 'message' => 'Ocorrência atualizada com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao atualizar ocorrência', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Excluir ocorrência
     */
    public function delete($id) {
        try {
            // Verificar se ocorrência existe
            $ocorrencia = $this->db->getOcorrencia($id);
            if (!$ocorrencia) {
                return ['success' => false, 'message' => 'Ocorrência não encontrada'];
            }
            
            // Verificar permissão (apenas admin pode excluir)
            $user = $this->auth->getCurrentUser();
            if ($user['perfil_tipo'] !== 'admin') {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Excluir do banco
            $this->db->deleteOcorrencia($id);
            
            logMessage('INFO', 'Ocorrência excluída', ['id' => $id]);
            
            return ['success' => true, 'message' => 'Ocorrência excluída com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao excluir ocorrência', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Gerar PDF da ocorrência
     */
    public function generatePDF($id) {
        try {
            $ocorrencia = $this->db->getOcorrencia($id);
            if (!$ocorrencia) {
                throw new Exception('Ocorrência não encontrada');
            }
            
            // Criar PDF
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Configurar informações do documento
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor(PDF_AUTHOR);
            $pdf->SetTitle(PDF_TITLE . ' - ' . $ocorrencia['numero_ocorrencia']);
            
            // Remover cabeçalho e rodapé padrão
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Adicionar página
            $pdf->AddPage();
            
            // Cabeçalho institucional
            $this->addInstitutionalHeader($pdf);
            
            // Conteúdo da ocorrência
            $this->addOcorrenciaContent($pdf, $ocorrencia);
            
            // Assinaturas
            $this->addSignatures($pdf, $ocorrencia);
            
            return $pdf->Output('', 'S');
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao gerar PDF', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Enviar ocorrência por e-mail
     */
    public function sendEmail($id, $pdf_content = null) {
        try {
            $ocorrencia = $this->db->getOcorrencia($id);
            if (!$ocorrencia) {
                throw new Exception('Ocorrência não encontrada');
            }
            
            // Gerar PDF se não fornecido
            if (!$pdf_content) {
                $pdf_content = $this->generatePDF($id);
            }
            
            // Configurar PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = getConfig('SMTP_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = getConfig('SMTP_USER');
            $mail->Password = getConfig('SMTP_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = getConfig('SMTP_PORT');
            $mail->CharSet = 'UTF-8';
            
            // Configurar remetente
            $mail->setFrom(getConfig('SMTP_FROM'), getConfig('SMTP_FROM_NAME'));
            
            // Configurar destinatários (ajustar conforme necessário)
            $mail->addAddress('comando@gcm.aracoiaba.sp.gov.br', 'Comando GCM');
            
            // Configurar assunto e corpo
            $mail->Subject = 'Nova Ocorrência - ' . $ocorrencia['numero_ocorrencia'];
            $mail->Body = $this->generateEmailBody($ocorrencia);
            $mail->AltBody = strip_tags($this->generateEmailBody($ocorrencia));
            
            // Anexar PDF
            $mail->addStringAttachment($pdf_content, 'ocorrencia_' . $ocorrencia['numero_ocorrencia'] . '.pdf');
            
            // Enviar
            $mail->send();
            
            logMessage('INFO', 'E-mail enviado com sucesso', ['ocorrencia_id' => $id]);
            
            return ['success' => true, 'message' => 'E-mail enviado com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao enviar e-mail', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro ao enviar e-mail: ' . $e->getMessage()];
        }
    }
    
    /**
     * Sanitizar dados de entrada
     */
    private function sanitizeData($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
    
    /**
     * Processar assinaturas digitais
     */
    private function processSignatures($data) {
        $signatures = [];
        $signature_fields = [
            'assinatura_solicitante', 'assinatura_vitima', 'assinatura_autor',
            'assinatura_testemunha1', 'assinatura_testemunha2'
        ];
        
        foreach ($signature_fields as $field) {
            if (!empty($data[$field])) {
                $signature_data = $data[$field];
                // Remover cabeçalho data:image/png;base64, se existir
                $signature_data = preg_replace('/^data:image\/[a-z]+;base64,/', '', $signature_data);
                $signatures[$field] = base64_decode($signature_data);
            } else {
                $signatures[$field] = null;
            }
        }
        
        return $signatures;
    }
    
    /**
     * Preparar dados para inserção
     */
    private function prepareInsertData($data, $signatures) {
        $user = $this->auth->getCurrentUser();
        
        return [
            $data['data'] ?? date('Y-m-d'),
            $data['hora_inicio'] ?? date('H:i:s'),
            $data['local'] ?? '',
            $data['natureza'] ?? '',
            $data['data_fato'] ?? '',
            $data['hora_fato'] ?? '',
            $data['local_fato'] ?? '',
            $data['bairro'] ?? '',
            $data['cidade'] ?? 'Araçoiaba da Serra',
            $data['estado'] ?? 'SP',
            $data['cep'] ?? '',
            $data['nome_solicitante'] ?? '',
            $data['nascimento_solicitante'] ?? '',
            $data['rg_solicitante'] ?? '',
            $data['cpf_solicitante'] ?? '',
            $data['telefone_solicitante'] ?? '',
            $data['endereco_solicitante'] ?? '',
            $data['bairro_solicitante'] ?? '',
            $data['cidade_solicitante'] ?? '',
            $data['estado_solicitante'] ?? '',
            $data['cep_solicitante'] ?? '',
            $data['relato'] ?? '',
            $data['nome_vitima'] ?? '',
            $data['nascimento_vitima'] ?? '',
            $data['rg_vitima'] ?? '',
            $data['cpf_vitima'] ?? '',
            $data['telefone_vitima'] ?? '',
            $data['endereco_vitima'] ?? '',
            $data['nome_autor'] ?? '',
            $data['nascimento_autor'] ?? '',
            $data['rg_autor'] ?? '',
            $data['cpf_autor'] ?? '',
            $data['telefone_autor'] ?? '',
            $data['endereco_autor'] ?? '',
            $data['nome_testemunha1'] ?? '',
            $data['rg_testemunha1'] ?? '',
            $data['cpf_testemunha1'] ?? '',
            $data['telefone_testemunha1'] ?? '',
            $data['endereco_testemunha1'] ?? '',
            $data['nome_testemunha2'] ?? '',
            $data['rg_testemunha2'] ?? '',
            $data['cpf_testemunha2'] ?? '',
            $data['telefone_testemunha2'] ?? '',
            $data['endereco_testemunha2'] ?? '',
            $data['providencias'] ?? '',
            $data['observacoes'] ?? '',
            $user['id'],
            date('Y-m-d H:i:s'),
            'aberta',
            $this->db->getNextOcorrenciaNumber(date('Y')),
            $data['foto_nome_vitima'] ?? '',
            $data['foto_nome_autor'] ?? '',
            $data['foto_nome_testemunha1'] ?? '',
            $data['foto_nome_testemunha2'] ?? '',
            $signatures['assinatura_solicitante'],
            $signatures['assinatura_vitima'],
            $signatures['assinatura_autor'],
            $signatures['assinatura_testemunha1'],
            $signatures['assinatura_testemunha2']
        ];
    }
    
    /**
     * Aplicar filtros na lista de ocorrências
     */
    private function applyFilters($ocorrencias, $filters) {
        $filtered = $ocorrencias;
        
        if (!empty($filters['data_inicio'])) {
            $filtered = array_filter($filtered, function($oc) use ($filters) {
                return $oc['data'] >= $filters['data_inicio'];
            });
        }
        
        if (!empty($filters['data_fim'])) {
            $filtered = array_filter($filtered, function($oc) use ($filters) {
                return $oc['data'] <= $filters['data_fim'];
            });
        }
        
        if (!empty($filters['natureza'])) {
            $filtered = array_filter($filtered, function($oc) use ($filters) {
                return stripos($oc['natureza'], $filters['natureza']) !== false;
            });
        }
        
        return array_values($filtered);
    }
    
    /**
     * Mascarar dados sensíveis
     */
    private function maskSensitiveData($ocorrencias) {
        foreach ($ocorrencias as &$ocorrencia) {
            if (!empty($ocorrencia['cpf_solicitante'])) {
                $ocorrencia['cpf_solicitante'] = maskCPF($ocorrencia['cpf_solicitante']);
            }
            if (!empty($ocorrencia['cpf_vitima'])) {
                $ocorrencia['cpf_vitima'] = maskCPF($ocorrencia['cpf_vitima']);
            }
            if (!empty($ocorrencia['cpf_autor'])) {
                $ocorrencia['cpf_autor'] = maskCPF($ocorrencia['cpf_autor']);
            }
            if (!empty($ocorrencia['endereco_solicitante'])) {
                $ocorrencia['endereco_solicitante'] = maskAddress($ocorrencia['endereco_solicitante']);
            }
        }
        return $ocorrencias;
    }
    
    /**
     * Adicionar cabeçalho institucional ao PDF
     */
    private function addInstitutionalHeader($pdf) {
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, getConfig('ORGAO_NOME'), 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 8, getConfig('SISTEMA_NOME'), 0, 1, 'C');
        $pdf->Ln(10);
    }
    
    /**
     * Adicionar conteúdo da ocorrência ao PDF
     */
    private function addOcorrenciaContent($pdf, $ocorrencia) {
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'REGISTRO DE OCORRÊNCIA - ' . $ocorrencia['numero_ocorrencia'], 0, 1, 'C');
        $pdf->Ln(5);
        
        $pdf->SetFont('helvetica', '', 10);
        
        // Dados básicos
        $pdf->Cell(40, 6, 'Data:', 0);
        $pdf->Cell(0, 6, formatDate($ocorrencia['data']), 0, 1);
        
        $pdf->Cell(40, 6, 'Hora:', 0);
        $pdf->Cell(0, 6, $ocorrencia['hora_inicio'], 0, 1);
        
        $pdf->Cell(40, 6, 'Natureza:', 0);
        $pdf->Cell(0, 6, $ocorrencia['natureza'], 0, 1);
        
        $pdf->Cell(40, 6, 'Local:', 0);
        $pdf->Cell(0, 6, $ocorrencia['local_fato'], 0, 1);
        
        $pdf->Ln(5);
        
        // Solicitante
        if (!empty($ocorrencia['nome_solicitante'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'SOLICITANTE', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            
            $pdf->Cell(40, 6, 'Nome:', 0);
            $pdf->Cell(0, 6, $ocorrencia['nome_solicitante'], 0, 1);
            
            if (!empty($ocorrencia['cpf_solicitante'])) {
                $pdf->Cell(40, 6, 'CPF:', 0);
                $pdf->Cell(0, 6, maskCPF($ocorrencia['cpf_solicitante']), 0, 1);
            }
            
            $pdf->Ln(5);
        }
        
        // Relato
        if (!empty($ocorrencia['relato'])) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'RELATO', 0, 1);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 6, $ocorrencia['relato'], 0, 'L');
            $pdf->Ln(5);
        }
    }
    
    /**
     * Adicionar assinaturas ao PDF
     */
    private function addSignatures($pdf, $ocorrencia) {
        $signature_fields = [
            'assinatura_solicitante' => 'Solicitante',
            'assinatura_vitima' => 'Vítima',
            'assinatura_autor' => 'Autor',
            'assinatura_testemunha1' => 'Testemunha 1',
            'assinatura_testemunha2' => 'Testemunha 2'
        ];
        
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'ASSINATURAS', 0, 1);
        $pdf->Ln(5);
        
        foreach ($signature_fields as $field => $label) {
            if (!empty($ocorrencia[$field])) {
                $pdf->SetFont('helvetica', '', 10);
                $pdf->Cell(40, 6, $label . ':', 0);
                
                // Adicionar imagem da assinatura
                $image_data = base64_encode($ocorrencia[$field]);
                $pdf->Image('data:image/png;base64,' . $image_data, $pdf->GetX(), $pdf->GetY(), 40, 20);
                $pdf->Ln(25);
            }
        }
    }
    
    /**
     * Gerar corpo do e-mail
     */
    private function generateEmailBody($ocorrencia) {
        $body = "
        <h2>Nova Ocorrência Registrada</h2>
        <p><strong>Número:</strong> {$ocorrencia['numero_ocorrencia']}</p>
        <p><strong>Data:</strong> " . formatDate($ocorrencia['data']) . "</p>
        <p><strong>Natureza:</strong> {$ocorrencia['natureza']}</p>
        <p><strong>Local:</strong> {$ocorrencia['local_fato']}</p>
        <p><strong>Solicitante:</strong> {$ocorrencia['nome_solicitante']}</p>
        <br>
        <p>Esta ocorrência foi registrada no Sistema SMART.</p>
        <p>O PDF está anexado a este e-mail.</p>
        ";
        
        return $body;
    }
}
?> 