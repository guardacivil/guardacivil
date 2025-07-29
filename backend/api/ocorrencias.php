<?php
/**
 * API REST para Ocorrências
 * Endpoint: /backend/api/ocorrencias.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../OcorrenciaController.php';

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
    $controller = new OcorrenciaController();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', trim($path, '/'));
    
    // Extrair ID da URL se existir
    $id = null;
    if (count($path_parts) > 3 && is_numeric($path_parts[3])) {
        $id = (int)$path_parts[3];
    }
    
    switch ($method) {
        case 'GET':
            if ($id) {
                // GET /api/ocorrencias/{id}
                $result = $controller->get($id);
            } else {
                // GET /api/ocorrencias
                $filters = $_GET;
                $result = $controller->list($filters);
            }
            break;
            
        case 'POST':
            // POST /api/ocorrencias
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            // Validar token CSRF
            if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                jsonError('Token CSRF inválido', 403);
            }
            
            $result = $controller->create($input);
            break;
            
        case 'PUT':
            // PUT /api/ocorrencias/{id}
            if (!$id) {
                jsonError('ID da ocorrência é obrigatório', 400);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validar token CSRF
            if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                jsonError('Token CSRF inválido', 403);
            }
            
            $result = $controller->update($id, $input);
            break;
            
        case 'DELETE':
            // DELETE /api/ocorrencias/{id}
            if (!$id) {
                jsonError('ID da ocorrência é obrigatório', 400);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validar token CSRF
            if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                jsonError('Token CSRF inválido', 403);
            }
            
            $result = $controller->delete($id);
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
    logMessage('ERROR', 'Erro na API de ocorrências', ['error' => $e->getMessage()]);
    jsonError('Erro interno do servidor', 500);
}
?> 