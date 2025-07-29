<?php
/**
 * API REST para Comunicação Interna
 * Endpoint: /backend/api/comunicacao.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../ComunicacaoController.php';

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
    $controller = new ComunicacaoController();
    
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
            if ($action === 'tipos') {
                // GET /api/comunicacao/tipos
                $result = ['success' => true, 'data' => $controller->getTipos()];
            } elseif ($action === 'prioridades') {
                // GET /api/comunicacao/prioridades
                $result = ['success' => true, 'data' => $controller->getPrioridades()];
            } elseif ($action === 'estatisticas') {
                // GET /api/comunicacao/estatisticas
                $result = $controller->getEstatisticas();
            } elseif ($id) {
                // GET /api/comunicacao/{id}
                $result = $controller->get($id);
            } else {
                // GET /api/comunicacao
                $filters = $_GET;
                $result = $controller->list($filters);
            }
            break;
            
        case 'POST':
            if ($action === 'create') {
                // POST /api/comunicacao/create
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    $input = $_POST;
                }
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->create($input);
            } elseif ($action === 'send-email' && $id) {
                // POST /api/comunicacao/{id}/send-email
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    $input = $_POST;
                }
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->sendEmail($id);
            } else {
                jsonError('Ação não encontrada', 404);
            }
            break;
            
        case 'PUT':
            if ($id) {
                // PUT /api/comunicacao/{id}
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->update($id, $input);
            } else {
                jsonError('ID da comunicação é obrigatório', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                // DELETE /api/comunicacao/{id}
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->delete($id);
            } else {
                jsonError('ID da comunicação é obrigatório', 400);
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
    logMessage('ERROR', 'Erro na API de comunicação', ['error' => $e->getMessage()]);
    jsonError('Erro interno do servidor', 500);
}
?> 