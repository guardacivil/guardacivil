<?php
/**
 * Controlador de Comunicação Interna - Sistema de comunicação da GCM
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class ComunicacaoController {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Criar nova comunicação
     */
    public function create($data) {
        try {
            // Validar dados obrigatórios
            $required_fields = ['titulo', 'conteudo', 'tipo', 'prioridade'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Campo '$field' é obrigatório"];
                }
            }
            
            // Verificar permissão
            $current_user = $this->auth->getCurrentUser();
            if (!$this->auth->hasPermission('comunicacao')) {
                return ['success' => false, 'message' => 'Sem permissão para criar comunicações'];
            }
            
            // Sanitizar dados
            $data = $this->sanitizeData($data);
            
            // Adicionar autor
            $data['autor_id'] = $current_user['id'];
            
            // Definir data de publicação
            $data['data_publicacao'] = date('Y-m-d H:i:s');
            
            // Inserir no banco
            $this->db->createComunicacao($data);
            
            logMessage('INFO', 'Comunicação criada', ['titulo' => $data['titulo'], 'autor' => $current_user['nome']]);
            
            return ['success' => true, 'message' => 'Comunicação criada com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao criar comunicação', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter comunicação específica
     */
    public function get($id) {
        try {
            $comunicacao = $this->db->getComunicacao($id);
            
            if (!$comunicacao) {
                return ['success' => false, 'message' => 'Comunicação não encontrada'];
            }
            
            // Verificar acesso
            if (!$this->canAccessComunicacao($comunicacao)) {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            return ['success' => true, 'data' => $comunicacao];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter comunicação', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Listar comunicações
     */
    public function list($filters = []) {
        try {
            $current_user = $this->auth->getCurrentUser();
            $comunicacoes = $this->db->getAllComunicacoes($current_user['id']);
            
            // Aplicar filtros adicionais
            if (!empty($filters)) {
                $comunicacoes = $this->applyFilters($comunicacoes, $filters);
            }
            
            return ['success' => true, 'data' => $comunicacoes];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao listar comunicações', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Atualizar comunicação
     */
    public function update($id, $data) {
        try {
            $comunicacao = $this->db->getComunicacao($id);
            
            if (!$comunicacao) {
                return ['success' => false, 'message' => 'Comunicação não encontrada'];
            }
            
            // Verificar permissão (apenas autor ou admin pode editar)
            $current_user = $this->auth->getCurrentUser();
            if ($comunicacao['autor_id'] != $current_user['id'] && $current_user['perfil_tipo'] !== 'admin') {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Sanitizar dados
            $data = $this->sanitizeData($data);
            
            // Atualizar no banco
            $this->db->updateComunicacao($id, $data);
            
            logMessage('INFO', 'Comunicação atualizada', ['id' => $id, 'usuario' => $current_user['nome']]);
            
            return ['success' => true, 'message' => 'Comunicação atualizada com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao atualizar comunicação', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Excluir comunicação
     */
    public function delete($id) {
        try {
            $comunicacao = $this->db->getComunicacao($id);
            
            if (!$comunicacao) {
                return ['success' => false, 'message' => 'Comunicação não encontrada'];
            }
            
            // Verificar permissão (apenas autor ou admin pode excluir)
            $current_user = $this->auth->getCurrentUser();
            if ($comunicacao['autor_id'] != $current_user['id'] && $current_user['perfil_tipo'] !== 'admin') {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Excluir do banco
            $this->db->deleteComunicacao($id);
            
            logMessage('INFO', 'Comunicação excluída', ['id' => $id, 'usuario' => $current_user['nome']]);
            
            return ['success' => true, 'message' => 'Comunicação excluída com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao excluir comunicação', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter tipos de comunicação
     */
    public function getTipos() {
        return [
            'geral' => 'Comunicação Geral',
            'setor' => 'Comunicação de Setor',
            'graduacao' => 'Comunicação por Graduação',
            'urgente' => 'Comunicação Urgente',
            'ordem' => 'Ordem de Serviço',
            'instrucao' => 'Instrução',
            'informacao' => 'Informação'
        ];
    }
    
    /**
     * Obter prioridades
     */
    public function getPrioridades() {
        return [
            'baixa' => 'Baixa',
            'normal' => 'Normal',
            'alta' => 'Alta',
            'urgente' => 'Urgente'
        ];
    }
    
    /**
     * Enviar comunicação por e-mail
     */
    public function sendEmail($id) {
        try {
            $comunicacao = $this->db->getComunicacao($id);
            
            if (!$comunicacao) {
                return ['success' => false, 'message' => 'Comunicação não encontrada'];
            }
            
            // Verificar permissão
            $current_user = $this->auth->getCurrentUser();
            if ($comunicacao['autor_id'] != $current_user['id'] && $current_user['perfil_tipo'] !== 'admin') {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Obter destinatários
            $destinatarios = $this->getDestinatarios($comunicacao);
            
            if (empty($destinatarios)) {
                return ['success' => false, 'message' => 'Nenhum destinatário encontrado'];
            }
            
            // Enviar e-mails
            $enviados = 0;
            foreach ($destinatarios as $destinatario) {
                if ($this->sendEmailToUser($comunicacao, $destinatario)) {
                    $enviados++;
                }
            }
            
            logMessage('INFO', 'Comunicação enviada por e-mail', ['id' => $id, 'enviados' => $enviados]);
            
            return ['success' => true, 'message' => "E-mail enviado para $enviados destinatários"];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao enviar e-mail da comunicação', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter estatísticas de comunicação
     */
    public function getEstatisticas() {
        try {
            $stats = [];
            
            // Total de comunicações
            $stmt = $this->db->getConnection()->query("SELECT COUNT(*) FROM comunicacoes WHERE data_expiracao IS NULL OR data_expiracao >= DATE('now')");
            $stats['total_comunicacoes'] = $stmt->fetchColumn();
            
            // Comunicações por tipo
            $stmt = $this->db->getConnection()->query("SELECT tipo, COUNT(*) as total FROM comunicacoes WHERE data_expiracao IS NULL OR data_expiracao >= DATE('now') GROUP BY tipo");
            $stats['por_tipo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Comunicações por prioridade
            $stmt = $this->db->getConnection()->query("SELECT prioridade, COUNT(*) as total FROM comunicacoes WHERE data_expiracao IS NULL OR data_expiracao >= DATE('now') GROUP BY prioridade");
            $stats['por_prioridade'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Comunicações do mês
            $stmt = $this->db->getConnection()->prepare("SELECT COUNT(*) FROM comunicacoes WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')");
            $stmt->execute();
            $stats['comunicacoes_mes'] = $stmt->fetchColumn();
            
            return ['success' => true, 'data' => $stats];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter estatísticas de comunicação', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Verificar se usuário pode acessar comunicação
     */
    private function canAccessComunicacao($comunicacao) {
        $current_user = $this->auth->getCurrentUser();
        
        // Se é pública, todos podem acessar
        if ($comunicacao['publico']) {
            return true;
        }
        
        // Se é o autor, pode acessar
        if ($comunicacao['autor_id'] == $current_user['id']) {
            return true;
        }
        
        // Se é admin, pode acessar
        if ($current_user['perfil_tipo'] === 'admin') {
            return true;
        }
        
        // Verificar se está na lista de destinatários
        if (!empty($comunicacao['destinatarios'])) {
            $destinatarios = explode(',', $comunicacao['destinatarios']);
            if (in_array($current_user['id'], $destinatarios)) {
                return true;
            }
        }
        
        // Verificar se é do setor
        if (!empty($comunicacao['setor_id']) && $current_user['setor_id'] == $comunicacao['setor_id']) {
            return true;
        }
        
        // Verificar graduação mínima
        if (!empty($comunicacao['graduacao_minima'])) {
            $user_graduacao = $this->db->getGraduacao($current_user['graduacao_id']);
            if ($user_graduacao && $user_graduacao['nivel'] >= $comunicacao['graduacao_minima']) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Aplicar filtros na lista de comunicações
     */
    private function applyFilters($comunicacoes, $filters) {
        $filtered = $comunicacoes;
        
        if (!empty($filters['tipo'])) {
            $filtered = array_filter($filtered, function($com) use ($filters) {
                return $com['tipo'] === $filters['tipo'];
            });
        }
        
        if (!empty($filters['prioridade'])) {
            $filtered = array_filter($filtered, function($com) use ($filters) {
                return $com['prioridade'] === $filters['prioridade'];
            });
        }
        
        if (!empty($filters['setor_id'])) {
            $filtered = array_filter($filtered, function($com) use ($filters) {
                return $com['setor_id'] == $filters['setor_id'];
            });
        }
        
        return array_values($filtered);
    }
    
    /**
     * Obter destinatários da comunicação
     */
    private function getDestinatarios($comunicacao) {
        $destinatarios = [];
        
        // Se é pública, todos os usuários ativos
        if ($comunicacao['publico']) {
            $stmt = $this->db->getConnection()->query("SELECT id, nome, email FROM usuarios WHERE ativo = 1 AND email IS NOT NULL");
            $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Destinatários específicos
            if (!empty($comunicacao['destinatarios'])) {
                $ids = explode(',', $comunicacao['destinatarios']);
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $stmt = $this->db->getConnection()->prepare("SELECT id, nome, email FROM usuarios WHERE id IN ($placeholders) AND ativo = 1 AND email IS NOT NULL");
                $stmt->execute($ids);
                $destinatarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Por setor
            if (!empty($comunicacao['setor_id'])) {
                $stmt = $this->db->getConnection()->prepare("SELECT id, nome, email FROM usuarios WHERE setor_id = ? AND ativo = 1 AND email IS NOT NULL");
                $stmt->execute([$comunicacao['setor_id']]);
                $setor_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $destinatarios = array_merge($destinatarios, $setor_users);
            }
            
            // Por graduação mínima
            if (!empty($comunicacao['graduacao_minima'])) {
                $stmt = $this->db->getConnection()->prepare("SELECT u.id, u.nome, u.email FROM usuarios u JOIN graduacoes g ON u.graduacao_id = g.id WHERE g.nivel >= ? AND u.ativo = 1 AND u.email IS NOT NULL");
                $stmt->execute([$comunicacao['graduacao_minima']]);
                $graduacao_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $destinatarios = array_merge($destinatarios, $graduacao_users);
            }
        }
        
        // Remover duplicatas
        $unique = [];
        foreach ($destinatarios as $dest) {
            $unique[$dest['id']] = $dest;
        }
        
        return array_values($unique);
    }
    
    /**
     * Enviar e-mail para usuário específico
     */
    private function sendEmailToUser($comunicacao, $usuario) {
        try {
            // Aqui você implementaria o envio real de e-mail
            // Por enquanto, apenas simula
            logMessage('INFO', 'E-mail simulado enviado', [
                'para' => $usuario['email'],
                'assunto' => $comunicacao['titulo'],
                'comunicacao_id' => $comunicacao['id']
            ]);
            
            return true;
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao enviar e-mail', ['error' => $e->getMessage()]);
            return false;
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
}
?> 