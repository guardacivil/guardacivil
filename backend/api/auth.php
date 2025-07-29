<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * API REST para Autenticação
 * Endpoint: /backend/api/auth.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Auth.php';

// Configurar headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $auth = new Auth();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', trim($path, '/'));
    
    // Extrair ação da URL
    $action = $path_parts[3] ?? 'login';
    
    switch ($method) {
        case 'POST':
            switch ($action) {
                case 'login':
                    // POST /api/auth/login
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input) {
                        $input = $_POST;
                    }
                    
                    if (empty($input['usuario']) || empty($input['senha'])) {
                        jsonError('Usuário e senha são obrigatórios', 400);
                    }

                    // Debug: mostrar o conteúdo recebido
                    var_dump($input);
                    exit;
                    
                    $result = $auth->login($input['usuario'], $input['senha']);
                    
                    if ($result['success']) {
                        jsonSuccess([
                            'user' => $result['user'],
                            'csrf_token' => $auth->getCSRFToken()
                        ], 'Login realizado com sucesso');
                    } else {
                        jsonError($result['message'], 401);
                    }
                    break;
                    
                case 'logout':
                    // POST /api/auth/logout
                    $auth->logout();
                    jsonSuccess(null, 'Logout realizado com sucesso');
                    break;
                    
                case 'change-password':
                    // POST /api/auth/change-password
                    $auth->requireLogin();
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input) {
                        $input = $_POST;
                    }
                    
                    // Validar token CSRF
                    if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                        jsonError('Token CSRF inválido', 403);
                    }
                    
                    if (empty($input['current_password']) || empty($input['new_password'])) {
                        jsonError('Senha atual e nova senha são obrigatórias', 400);
                    }
                    
                    $user = $auth->getCurrentUser();
                    $result = $auth->changePassword($user['id'], $input['current_password'], $input['new_password']);
                    
                    if ($result['success']) {
                        jsonSuccess(null, $result['message']);
                    } else {
                        jsonError($result['message'], 400);
                    }
                    break;
                    
                case 'create-user':
                    // POST /api/auth/create-user
                    $auth->requirePermission('usuarios');
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input) {
                        $input = $_POST;
                    }
                    
                    // Validar token CSRF
                    if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                        jsonError('Token CSRF inválido', 403);
                    }
                    
                    $result = $auth->createUser($input);
                    
                    if ($result['success']) {
                        jsonSuccess(null, $result['message']);
                    } else {
                        jsonError($result['message'], 400);
                    }
                    break;
                    
                default:
                    jsonError('Ação não encontrada', 404);
            }
            break;
            
        case 'GET':
            switch ($action) {
                case 'me':
                    // GET /api/auth/me
                    $auth->requireLogin();
                    $user = $auth->getCurrentUser();
                    jsonSuccess($user, 'Dados do usuário obtidos com sucesso');
                    break;
                    
                case 'csrf-token':
                    // GET /api/auth/csrf-token
                    $auth->requireLogin();
                    $token = $auth->getCSRFToken();
                    jsonSuccess(['csrf_token' => $token], 'Token CSRF gerado');
                    break;
                    
                case 'permissions':
                    // GET /api/auth/permissions
                    $auth->requireLogin();
                    $user = $auth->getCurrentUser();
                    jsonSuccess([
                        'permissions' => $user['permissoes'],
                        'role' => $user['perfil_tipo']
                    ], 'Permissões obtidas com sucesso');
                    break;
                    
                default:
                    jsonError('Ação não encontrada', 404);
            }
            break;
            
        case 'PUT':
            switch ($action) {
                case 'update-user':
                    // PUT /api/auth/update-user/{id}
                    $auth->requirePermission('usuarios');
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    // Validar token CSRF
                    if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                        jsonError('Token CSRF inválido', 403);
                    }
                    
                    $id = $path_parts[4] ?? null;
                    if (!$id) {
                        jsonError('ID do usuário é obrigatório', 400);
                    }
                    
                    $result = $auth->updateUser($id, $input);
                    
                    if ($result['success']) {
                        jsonSuccess(null, $result['message']);
                    } else {
                        jsonError($result['message'], 400);
                    }
                    break;
                    
                default:
                    jsonError('Ação não encontrada', 404);
            }
            break;
            
        case 'DELETE':
            switch ($action) {
                case 'deactivate-user':
                    // DELETE /api/auth/deactivate-user/{id}
                    $auth->requirePermission('usuarios');
                    
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    // Validar token CSRF
                    if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                        jsonError('Token CSRF inválido', 403);
                    }
                    
                    $id = $path_parts[4] ?? null;
                    if (!$id) {
                        jsonError('ID do usuário é obrigatório', 400);
                    }
                    
                    $result = $auth->deactivateUser($id);
                    
                    if ($result['success']) {
                        jsonSuccess(null, $result['message']);
                    } else {
                        jsonError($result['message'], 400);
                    }
                    break;
                    
                default:
                    jsonError('Ação não encontrada', 404);
            }
            break;
            
        default:
            jsonError('Método não permitido', 405);
    }
    
} catch (Exception $e) {
    logMessage('ERROR', 'Erro na API de autenticação', ['error' => $e->getMessage()]);
    jsonError('Erro interno do servidor', 500);
}
?> 