<?php
/**
 * Controlador de Escalas - Gestão de escalas da GCM
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class EscalaController {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Criar nova escala
     */
    public function create($data) {
        try {
            // Validar dados obrigatórios
            $required_fields = ['nome', 'data_inicio', 'data_fim', 'turno', 'setor_id'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Campo '$field' é obrigatório"];
                }
            }
            
            // Verificar permissão
            if (!$this->auth->hasPermission('escalas')) {
                return ['success' => false, 'message' => 'Sem permissão para criar escalas'];
            }
            
            // Validar datas
            if (strtotime($data['data_inicio']) > strtotime($data['data_fim'])) {
                return ['success' => false, 'message' => 'Data de início deve ser anterior à data de fim'];
            }
            
            // Sanitizar dados
            $data = $this->sanitizeData($data);
            
            // Adicionar responsável
            $current_user = $this->auth->getCurrentUser();
            $data['responsavel_id'] = $current_user['id'];
            
            // Inserir no banco
            $this->db->createEscala($data);
            
            logMessage('INFO', 'Escala criada', ['nome' => $data['nome'], 'responsavel' => $current_user['nome']]);
            
            return ['success' => true, 'message' => 'Escala criada com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao criar escala', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter escala específica
     */
    public function get($id) {
        try {
            $escala = $this->db->getEscala($id);
            
            if (!$escala) {
                return ['success' => false, 'message' => 'Escala não encontrada'];
            }
            
            // Verificar acesso
            if (!$this->canAccessEscala($escala)) {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Obter pessoal da escala
            $pessoal = $this->db->getEscalaPessoal($id);
            $escala['pessoal'] = $pessoal;
            
            return ['success' => true, 'data' => $escala];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter escala', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Listar escalas
     */
    public function list($filters = []) {
        try {
            $escalas = $this->db->getAllEscalas($filters);
            
            // Filtrar por acesso do usuário
            $current_user = $this->auth->getCurrentUser();
            if ($current_user['perfil_tipo'] !== 'admin') {
                $escalas = array_filter($escalas, function($escala) use ($current_user) {
                    return $escala['setor_id'] == $current_user['setor_id'] || 
                           $escala['responsavel_id'] == $current_user['id'];
                });
            }
            
            return ['success' => true, 'data' => array_values($escalas)];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao listar escalas', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Atualizar escala
     */
    public function update($id, $data) {
        try {
            $escala = $this->db->getEscala($id);
            
            if (!$escala) {
                return ['success' => false, 'message' => 'Escala não encontrada'];
            }
            
            // Verificar permissão
            $current_user = $this->auth->getCurrentUser();
            if ($escala['responsavel_id'] != $current_user['id'] && $current_user['perfil_tipo'] !== 'admin') {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Validar datas se foram alteradas
            if (!empty($data['data_inicio']) && !empty($data['data_fim'])) {
                if (strtotime($data['data_inicio']) > strtotime($data['data_fim'])) {
                    return ['success' => false, 'message' => 'Data de início deve ser anterior à data de fim'];
                }
            }
            
            // Sanitizar dados
            $data = $this->sanitizeData($data);
            
            // Atualizar no banco
            $this->db->updateEscala($id, $data);
            
            logMessage('INFO', 'Escala atualizada', ['id' => $id, 'usuario' => $current_user['nome']]);
            
            return ['success' => true, 'message' => 'Escala atualizada com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao atualizar escala', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Adicionar pessoal à escala
     */
    public function addPessoal($escala_id, $data) {
        try {
            $escala = $this->db->getEscala($escala_id);
            
            if (!$escala) {
                return ['success' => false, 'message' => 'Escala não encontrada'];
            }
            
            // Verificar permissão
            $current_user = $this->auth->getCurrentUser();
            if ($escala['responsavel_id'] != $current_user['id'] && $current_user['perfil_tipo'] !== 'admin') {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Validar dados
            if (empty($data['usuario_id']) || empty($data['data']) || empty($data['turno'])) {
                return ['success' => false, 'message' => 'Usuário, data e turno são obrigatórios'];
            }
            
            // Verificar se usuário já está na escala nesta data/turno
            $stmt = $this->db->getConnection()->prepare("SELECT id FROM escalas_pessoal WHERE escala_id = ? AND usuario_id = ? AND data = ? AND turno = ?");
            $stmt->execute([$escala_id, $data['usuario_id'], $data['data'], $data['turno']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Usuário já está escalado nesta data e turno'];
            }
            
            // Adicionar à escala
            $data['escala_id'] = $escala_id;
            $this->db->addPessoalEscala($data);
            
            logMessage('INFO', 'Pessoal adicionado à escala', ['escala_id' => $escala_id, 'usuario_id' => $data['usuario_id']]);
            
            return ['success' => true, 'message' => 'Pessoal adicionado com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao adicionar pessoal à escala', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Remover pessoal da escala
     */
    public function removePessoal($escala_id, $pessoal_id) {
        try {
            $escala = $this->db->getEscala($escala_id);
            
            if (!$escala) {
                return ['success' => false, 'message' => 'Escala não encontrada'];
            }
            
            // Verificar permissão
            $current_user = $this->auth->getCurrentUser();
            if ($escala['responsavel_id'] != $current_user['id'] && $current_user['perfil_tipo'] !== 'admin') {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Remover da escala
            $stmt = $this->db->getConnection()->prepare("DELETE FROM escalas_pessoal WHERE id = ? AND escala_id = ?");
            $stmt->execute([$pessoal_id, $escala_id]);
            
            logMessage('INFO', 'Pessoal removido da escala', ['escala_id' => $escala_id, 'pessoal_id' => $pessoal_id]);
            
            return ['success' => true, 'message' => 'Pessoal removido com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao remover pessoal da escala', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter escala do usuário
     */
    public function getEscalaUsuario($usuario_id = null, $data_inicio = null, $data_fim = null) {
        try {
            if (!$usuario_id) {
                $current_user = $this->auth->getCurrentUser();
                $usuario_id = $current_user['id'];
            }
            
            $escala = $this->db->getEscalaUsuario($usuario_id, $data_inicio, $data_fim);
            
            return ['success' => true, 'data' => $escala];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter escala do usuário', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter turnos disponíveis
     */
    public function getTurnos() {
        return [
            'manha' => 'Manhã (06:00 - 14:00)',
            'tarde' => 'Tarde (14:00 - 22:00)',
            'noite' => 'Noite (22:00 - 06:00)',
            'integral' => 'Integral (06:00 - 18:00)',
            'administrativo' => 'Administrativo (08:00 - 17:00)'
        ];
    }
    
    /**
     * Obter funções disponíveis
     */
    public function getFuncoes() {
        return [
            'comando' => 'Comando',
            'operacional' => 'Operacional',
            'apoio' => 'Apoio',
            'observador' => 'Observador',
            'motorista' => 'Motorista',
            'radio' => 'Operador de Rádio',
            'escolta' => 'Escolta',
            'preventivo' => 'Preventivo'
        ];
    }
    
    /**
     * Gerar escala em PDF
     */
    public function generatePDF($id) {
        try {
            $escala = $this->db->getEscala($id);
            
            if (!$escala) {
                return ['success' => false, 'message' => 'Escala não encontrada'];
            }
            
            // Verificar acesso
            if (!$this->canAccessEscala($escala)) {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Obter pessoal da escala
            $pessoal = $this->db->getEscalaPessoal($id);
            
            // Gerar PDF
            $pdf_content = $this->generateEscalaPDF($escala, $pessoal);
            
            return ['success' => true, 'data' => $pdf_content];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao gerar PDF da escala', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter estatísticas de escalas
     */
    public function getEstatisticas() {
        try {
            $stats = [];
            
            // Total de escalas ativas
            $stmt = $this->db->getConnection()->query("SELECT COUNT(*) FROM escalas WHERE status = 'ativa'");
            $stats['total_escalas'] = $stmt->fetchColumn();
            
            // Escalas por setor
            $stmt = $this->db->getConnection()->query("SELECT s.nome, COUNT(e.id) as total FROM setores s LEFT JOIN escalas e ON s.id = e.setor_id WHERE e.status = 'ativa' GROUP BY s.id, s.nome");
            $stats['escalas_por_setor'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pessoal escalado hoje
            $stmt = $this->db->getConnection()->prepare("SELECT COUNT(DISTINCT ep.usuario_id) FROM escalas_pessoal ep WHERE ep.data = DATE('now')");
            $stmt->execute();
            $stats['pessoal_hoje'] = $stmt->fetchColumn();
            
            // Escalas da semana
            $stmt = $this->db->getConnection()->prepare("SELECT COUNT(*) FROM escalas WHERE data_inicio <= DATE('now', '+7 days') AND data_fim >= DATE('now') AND status = 'ativa'");
            $stmt->execute();
            $stats['escalas_semana'] = $stmt->fetchColumn();
            
            return ['success' => true, 'data' => $stats];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter estatísticas de escalas', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Verificar se usuário pode acessar escala
     */
    private function canAccessEscala($escala) {
        $current_user = $this->auth->getCurrentUser();
        
        // Admin pode acessar todas
        if ($current_user['perfil_tipo'] === 'admin') {
            return true;
        }
        
        // Responsável pode acessar
        if ($escala['responsavel_id'] == $current_user['id']) {
            return true;
        }
        
        // Pessoal do setor pode acessar
        if ($escala['setor_id'] == $current_user['setor_id']) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Gerar PDF da escala
     */
    private function generateEscalaPDF($escala, $pessoal) {
        // Aqui você implementaria a geração real do PDF
        // Por enquanto, apenas simula
        
        $content = "ESCALA DE SERVIÇO\n";
        $content .= "Nome: {$escala['nome']}\n";
        $content .= "Período: {$escala['data_inicio']} a {$escala['data_fim']}\n";
        $content .= "Turno: {$escala['turno']}\n";
        $content .= "Setor: {$escala['setor_nome']}\n";
        $content .= "Responsável: {$escala['responsavel_nome']}\n\n";
        
        $content .= "PESSOAL ESCALADO:\n";
        foreach ($pessoal as $p) {
            $content .= "- {$p['usuario_nome']} ({$p['graduacao_nome']}) - {$p['data']} - {$p['turno']} - {$p['funcao']}\n";
        }
        
        return $content;
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
}
?> 