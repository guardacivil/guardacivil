<?php
/**
 * Classe Auth - Gerenciamento de autenticação e autorização
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Autenticar usuário
     */
    public function login($usuario, $senha) {
        try {
            $user = $this->db->getUser($usuario);
            
            if (!$user) {
                logMessage('WARNING', 'Tentativa de login com usuário inexistente', ['usuario' => $usuario]);
                return ['success' => false, 'message' => 'Usuário ou senha inválidos'];
            }
            
            if (!password_verify($senha, $user['senha'])) {
                logMessage('WARNING', 'Tentativa de login com senha incorreta', ['usuario' => $usuario]);
                return ['success' => false, 'message' => 'Usuário ou senha inválidos'];
            }
            
            // Verificar se usuário está ativo
            if (!$user['ativo']) {
                logMessage('WARNING', 'Tentativa de login com usuário inativo', ['usuario' => $usuario]);
                return ['success' => false, 'message' => 'Usuário inativo'];
            }
            
            // Criar sessão
            $this->createSession($user);
            
            logMessage('INFO', 'Login realizado com sucesso', ['usuario' => $usuario, 'id' => $user['id']]);
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro no login', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Criar sessão do usuário
     */
    private function createSession($user) {
        // Regenerar ID da sessão para segurança
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['user_usuario'] = $user['usuario'];
        $_SESSION['user_perfil_id'] = $user['perfil_id'];
        $_SESSION['login_time'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Obter perfil do usuário
        $perfil = $this->db->getPerfil($user['perfil_id']);
        $_SESSION['user_perfil'] = $perfil['nome'];
        $_SESSION['user_perfil_tipo'] = $perfil['tipo'];
        $_SESSION['user_permissoes'] = json_decode($perfil['permissoes'], true);
    }
    
    /**
     * Verificar se usuário está logado
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Verificar timeout da sessão
        if (time() - $_SESSION['login_time'] > getConfig('SESSION_TIMEOUT')) {
            $this->logout();
            return false;
        }
        
        // Atualizar tempo de login
        $_SESSION['login_time'] = time();
        
        return true;
    }
    
    /**
     * Obter dados do usuário logado
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'nome' => $_SESSION['user_nome'],
            'usuario' => $_SESSION['user_usuario'],
            'perfil_id' => $_SESSION['user_perfil_id'],
            'perfil' => $_SESSION['user_perfil'],
            'perfil_tipo' => $_SESSION['user_perfil_tipo'],
            'permissoes' => $_SESSION['user_permissoes']
        ];
    }
    
    /**
     * Verificar permissão do usuário
     */
    public function hasPermission($permission) {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        // Administradores têm todas as permissões
        if ($user['perfil_tipo'] === 'admin') {
            return true;
        }
        
        return in_array($permission, $user['permissoes']);
    }
    
    /**
     * Verificar se usuário tem perfil específico
     */
    public function hasRole($role) {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            return false;
        }
        
        return $user['perfil_tipo'] === $role;
    }
    
    /**
     * Logout do usuário
     */
    public function logout() {
        logMessage('INFO', 'Logout realizado', ['usuario' => $_SESSION['user_usuario'] ?? 'desconhecido']);
        
        // Limpar sessão
        session_unset();
        session_destroy();
        
        // Iniciar nova sessão limpa
        session_start();
    }
    
    /**
     * Requer login - redireciona se não estiver logado
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . getConfig('FRONTEND_URL') . '/login.php');
            exit;
        }
    }
    
    /**
     * Requer permissão específica
     */
    public function requirePermission($permission) {
        $this->requireLogin();
        
        if (!$this->hasPermission($permission)) {
            logMessage('WARNING', 'Tentativa de acesso sem permissão', [
                'usuario' => $_SESSION['user_usuario'],
                'permissao' => $permission
            ]);
            
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado. Permissão insuficiente.'
            ]);
            exit;
        }
    }
    
    /**
     * Requer perfil específico
     */
    public function requireRole($role) {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            logMessage('WARNING', 'Tentativa de acesso com perfil incorreto', [
                'usuario' => $_SESSION['user_usuario'],
                'perfil_requerido' => $role,
                'perfil_atual' => $_SESSION['user_perfil_tipo']
            ]);
            
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado. Perfil insuficiente.'
            ]);
            exit;
        }
    }
    
    /**
     * Alterar senha do usuário
     */
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            $user = $this->db->getUserById($user_id);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Usuário não encontrado'];
            }
            
            if (!password_verify($current_password, $user['senha'])) {
                return ['success' => false, 'message' => 'Senha atual incorreta'];
            }
            
            if (strlen($new_password) < getConfig('PASSWORD_MIN_LENGTH')) {
                return ['success' => false, 'message' => 'Nova senha muito curta'];
            }
            
            $this->db->updateUser($user_id, [
                'senha' => password_hash($new_password, PASSWORD_DEFAULT)
            ]);
            
            logMessage('INFO', 'Senha alterada', ['usuario_id' => $user_id]);
            
            return ['success' => true, 'message' => 'Senha alterada com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao alterar senha', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Criar novo usuário
     */
    public function createUser($data) {
        try {
            // Validar dados
            if (empty($data['nome']) || empty($data['usuario']) || empty($data['senha'])) {
                return ['success' => false, 'message' => 'Todos os campos são obrigatórios'];
            }
            
            if (strlen($data['senha']) < getConfig('PASSWORD_MIN_LENGTH')) {
                return ['success' => false, 'message' => 'Senha muito curta'];
            }
            
            // Verificar se usuário já existe
            $existing = $this->db->getUser($data['usuario']);
            if ($existing) {
                return ['success' => false, 'message' => 'Nome de usuário já existe'];
            }
            
            $this->db->createUser($data);
            
            logMessage('INFO', 'Usuário criado', ['usuario' => $data['usuario']]);
            
            return ['success' => true, 'message' => 'Usuário criado com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao criar usuário', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Atualizar usuário
     */
    public function updateUser($id, $data) {
        try {
            $this->db->updateUser($id, $data);
            
            logMessage('INFO', 'Usuário atualizado', ['usuario_id' => $id]);
            
            return ['success' => true, 'message' => 'Usuário atualizado com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao atualizar usuário', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Desativar usuário
     */
    public function deactivateUser($id) {
        try {
            $this->db->updateUser($id, ['ativo' => 0]);
            
            logMessage('INFO', 'Usuário desativado', ['usuario_id' => $id]);
            
            return ['success' => true, 'message' => 'Usuário desativado com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao desativar usuário', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Ativar usuário
     */
    public function activateUser($id) {
        try {
            $this->db->updateUser($id, ['ativo' => 1]);
            
            logMessage('INFO', 'Usuário ativado', ['usuario_id' => $id]);
            
            return ['success' => true, 'message' => 'Usuário ativado com sucesso'];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erro ao ativar usuário', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Erro interno do sistema'];
        }
    }
    
    /**
     * Obter token CSRF
     */
    public function getCSRFToken() {
        return $_SESSION['csrf_token'] ?? null;
    }
    
    /**
     * Validar token CSRF
     */
    public function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Gerar novo token CSRF
     */
    public function regenerateCSRFToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}
?> 