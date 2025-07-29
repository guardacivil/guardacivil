<?php
/**
 * API REST para Gestão de Pessoal
 * Endpoint: /backend/api/pessoal.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../PessoalController.php';

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
    $controller = new PessoalController();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', trim($path, '/'));
    
    // Extrair ID da URL se existir
    $id = null;
    if (count($path_parts) > 3 && is_numeric($path_parts[3])) {
        $id = (int)$path_parts[3];
    }
    
    // Extrair ação da URL
    $action = $path_parts[4] ?? null;
    
    switch ($method) {
        case 'GET':
            if ($action === 'graduacoes') {
                // GET /api/pessoal/graduacoes
                $result = $controller->getGraduacoes();
            } elseif ($action === 'setores') {
                // GET /api/pessoal/setores
                $result = $controller->getSetores();
            } elseif ($action === 'estatisticas') {
                // GET /api/pessoal/estatisticas
                $result = $controller->getEstatisticas();
            } elseif ($action === 'por-setor' && !empty($path_parts[5])) {
                // GET /api/pessoal/por-setor/{setor_id}
                $result = $controller->getPessoalBySetor($path_parts[5]);
            } elseif ($id) {
                // GET /api/pessoal/{id}
                $result = $controller->get($id);
            } else {
                // GET /api/pessoal
                $filters = $_GET;
                $result = $controller->list($filters);
            }
            break;
            
        case 'POST':
            if ($action === 'create') {
                // POST /api/pessoal/create
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    $input = $_POST;
                }
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->create($input);
            } elseif ($action === 'change-password' && $id) {
                // POST /api/pessoal/{id}/change-password
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    $input = $_POST;
                }
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->changePassword($id, $input['current_password'], $input['new_password']);
            } elseif ($action === 'change-status' && $id) {
                // POST /api/pessoal/{id}/change-status
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    $input = $_POST;
                }
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->changeStatus($id, $input['status']);
            } elseif ($action === 'relatorio') {
                // POST /api/pessoal/relatorio
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    $input = $_POST;
                }
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->generateRelatorio($input['tipo'], $input['parametros']);
            } else {
                jsonError('Ação não encontrada', 404);
            }
            break;
            
        case 'PUT':
            if ($id) {
                // PUT /api/pessoal/{id}
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->update($id, $input);
            } else {
                jsonError('ID do pessoal é obrigatório', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                // DELETE /api/pessoal/{id}
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->changeStatus($id, 'inativo');
            } else {
                jsonError('ID do pessoal é obrigatório', 400);
            }
            break;
            
        default:
            jsonError('Método não permitido', 405);
    }
    
    // Retornar resposta
    if ($result['success']) {
        jsonSuccess($result['data'] ?? null, $result['message'] ?? '');
    } else {
        jsonError($result['message'], 400, $result['data'] ?? null);
    }
    
} catch (Exception $e) {
    logMessage('ERROR', 'Erro na API de pessoal', ['error' => $e->getMessage()]);
    jsonError('Erro interno do servidor', 500);
}
?> 