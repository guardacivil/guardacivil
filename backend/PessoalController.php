<?php
/**
 * Controlador de Pessoal - Gestão completa da GCM
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

class PessoalController {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Criar novo membro da corporação
     */
    public function create($data) {
        try {
            // Validar dados obrigatórios
            $required_fields = ['nome', 'nome_guerra', 'perfil_id', 'graduacao_id', 'setor_id', 'matricula', 'cpf'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Campo '$field' é obrigatório"];
                }
            }
            
            // Validar CPF
            if (!validateCPF($data['cpf'])) {
                return ['success' => false, 'message' => 'CPF inválido'];
            }
            
            // Verificar se matrícula já existe
            $stmt = $this->db->getConnection()->prepare("SELECT id FROM usuarios WHERE matricula = ?");
            $stmt->execute([$data['matricula']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Matrícula já cadastrada'];
            }
            
            // Verificar se CPF já existe
            $stmt = $this->db->getConnection()->prepare("SELECT id FROM usuarios WHERE cpf = ?");
            $stmt->execute([$data['cpf']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'CPF já cadastrado'];
            }
            
            // Verificar se nome de guerra já existe
            $stmt = $this->db->getConnection()->prepare("SELECT id FROM usuarios WHERE nome_guerra = ?");
            $stmt->execute([$data['nome_guerra']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Nome de Guerra já existe'];
            }
            
            // Sanitizar dados
            $data = $this->sanitizeData($data);
            
            // Inserir no banco
            $this->db->createPessoal($data);
            
            logMessage('INFO', 'Membro da corporação criado', ['matricula' => $data['matricula']]);
            
            return ['success' => true, 'message' => 'Membro da corporação cadastrado com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao criar membro da corporação', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter dados de um membro
     */
    public function get($id) {
        try {
            $user = $this->db->getUserById($id);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Membro não encontrado'];
            }
            
            // Verificar permissão
            $current_user = $this->auth->getCurrentUser();
            if ($current_user['perfil_tipo'] !== 'admin' && $user['id'] != $current_user['id']) {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Obter dados relacionados
            $user['graduacao'] = $this->db->getGraduacao($user['graduacao_id']);
            $user['setor'] = $this->db->getSetor($user['setor_id']);
            $user['perfil'] = $this->db->getPerfil($user['perfil_id']);
            
            return ['success' => true, 'data' => $user];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter dados do membro', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Listar pessoal com filtros
     */
    public function list($filters = []) {
        try {
            $pessoal = $this->db->getAllPessoal($filters);
            
            // Mascarar dados sensíveis para não-administradores
            $current_user = $this->auth->getCurrentUser();
            if ($current_user['perfil_tipo'] !== 'admin') {
                foreach ($pessoal as &$pessoa) {
                    $pessoa['cpf'] = maskCPF($pessoa['cpf']);
                    $pessoa['endereco'] = maskAddress($pessoa['endereco']);
                }
            }
            
            return ['success' => true, 'data' => $pessoal];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao listar pessoal', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Atualizar dados de um membro
     */
    public function update($id, $data) {
        try {
            // Verificar se membro existe
            $user = $this->db->getUserById($id);
            if (!$user) {
                return ['success' => false, 'message' => 'Membro não encontrado'];
            }
            
            // Verificar permissão
            $current_user = $this->auth->getCurrentUser();
            if ($current_user['perfil_tipo'] !== 'admin' && $user['id'] != $current_user['id']) {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Validar CPF se foi alterado
            if (!empty($data['cpf']) && $data['cpf'] !== $user['cpf']) {
                if (!validateCPF($data['cpf'])) {
                    return ['success' => false, 'message' => 'CPF inválido'];
                }
                
                // Verificar se CPF já existe
                $stmt = $this->db->getConnection()->prepare("SELECT id FROM usuarios WHERE cpf = ? AND id != ?");
                $stmt->execute([$data['cpf'], $id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'CPF já cadastrado'];
                }
            }
            
            // Validar matrícula se foi alterada
            if (!empty($data['matricula']) && $data['matricula'] !== $user['matricula']) {
                $stmt = $this->db->getConnection()->prepare("SELECT id FROM usuarios WHERE matricula = ? AND id != ?");
                $stmt->execute([$data['matricula'], $id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Matrícula já cadastrada'];
                }
            }
            
            // Sanitizar dados
            $data = $this->sanitizeData($data);
            
            // Atualizar no banco
            $this->db->updatePessoal($id, $data);
            
            logMessage('INFO', 'Dados do membro atualizados', ['id' => $id]);
            
            return ['success' => true, 'message' => 'Dados atualizados com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao atualizar dados do membro', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Alterar status do membro (ativo/inativo)
     */
    public function changeStatus($id, $status) {
        try {
            // Verificar permissão
            $this->auth->requirePermission('pessoal');
            
            $user = $this->db->getUserById($id);
            if (!$user) {
                return ['success' => false, 'message' => 'Membro não encontrado'];
            }
            
            $this->db->updatePessoal($id, ['ativo' => $status === 'ativo' ? 1 : 0]);
            
            logMessage('INFO', 'Status do membro alterado', ['id' => $id, 'status' => $status]);
            
            return ['success' => true, 'message' => 'Status alterado com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao alterar status do membro', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Alterar senha
     */
    public function changePassword($id, $current_password, $new_password) {
        try {
            $user = $this->db->getUserById($id);
            if (!$user) {
                return ['success' => false, 'message' => 'Membro não encontrado'];
            }
            
            // Verificar permissão
            $current_user = $this->auth->getCurrentUser();
            if ($current_user['perfil_tipo'] !== 'admin' && $user['id'] != $current_user['id']) {
                return ['success' => false, 'message' => 'Acesso negado'];
            }
            
            // Verificar senha atual
            if (!password_verify($current_password, $user['senha'])) {
                return ['success' => false, 'message' => 'Senha atual incorreta'];
            }
            
            // Validar nova senha
            if (strlen($new_password) < getConfig('PASSWORD_MIN_LENGTH')) {
                return ['success' => false, 'message' => 'Nova senha muito curta'];
            }
            
            // Atualizar senha
            $this->db->updatePessoal($id, ['senha' => password_hash($new_password, PASSWORD_DEFAULT)]);
            
            logMessage('INFO', 'Senha alterada', ['id' => $id]);
            
            return ['success' => true, 'message' => 'Senha alterada com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao alterar senha', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter graduações
     */
    public function getGraduacoes() {
        try {
            $graduacoes = $this->db->getAllGraduacoes();
            return ['success' => true, 'data' => $graduacoes];
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter graduações', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter setores
     */
    public function getSetores() {
        try {
            $setores = $this->db->getAllSetores();
            return ['success' => true, 'data' => $setores];
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter setores', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter pessoal por setor
     */
    public function getPessoalBySetor($setor_id) {
        try {
            $pessoal = $this->db->getPessoalBySetor($setor_id);
            return ['success' => true, 'data' => $pessoal];
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter pessoal por setor', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter estatísticas de pessoal
     */
    public function getEstatisticas() {
        try {
            $stats = $this->db->getEstatisticasGerais();
            return ['success' => true, 'data' => $stats];
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao obter estatísticas', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Gerar relatório de pessoal
     */
    public function generateRelatorio($tipo, $parametros) {
        try {
            $current_user = $this->auth->getCurrentUser();
            
            // Criar registro do relatório
            $relatorio_id = $this->db->createRelatorio([
                'titulo' => "Relatório de Pessoal - $tipo",
                'tipo' => $tipo,
                'parametros' => $parametros,
                'gerado_por' => $current_user['id']
            ]);
            
            // Gerar relatório em background (simulado)
            $arquivo_path = $this->generateRelatorioFile($tipo, $parametros);
            
            // Atualizar status
            $this->db->updateRelatorio($relatorio_id, $arquivo_path);
            
            return ['success' => true, 'data' => ['id' => $relatorio_id, 'arquivo' => $arquivo_path]];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao gerar relatório', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
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
     * Gerar arquivo de relatório (simulado)
     */
    private function generateRelatorioFile($tipo, $parametros) {
        $filename = "relatorio_pessoal_{$tipo}_" . date('Y-m-d_H-i-s') . ".pdf";
        $filepath = getConfig('UPLOAD_PATH') . 'relatorios/' . $filename;
        
        // Criar diretório se não existir
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Simular geração de PDF
        file_put_contents($filepath, "Relatório de Pessoal - $tipo\nGerado em: " . date('Y-m-d H:i:s'));
        
        return $filepath;
    }
}
?> 