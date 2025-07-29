<?php
/**
 * API REST para Gestão de Escalas
 * Endpoint: /backend/api/escalas.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../EscalaController.php';

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
    $controller = new EscalaController();
    
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
            if ($action === 'turnos') {
                // GET /api/escalas/turnos
                $result = ['success' => true, 'data' => $controller->getTurnos()];
            } elseif ($action === 'funcoes') {
                // GET /api/escalas/funcoes
                $result = ['success' => true, 'data' => $controller->getFuncoes()];
            } elseif ($action === 'estatisticas') {
                // GET /api/escalas/estatisticas
                $result = $controller->getEstatisticas();
            } elseif ($action === 'usuario') {
                // GET /api/escalas/usuario
                $data_inicio = $_GET['data_inicio'] ?? null;
                $data_fim = $_GET['data_fim'] ?? null;
                $result = $controller->getEscalaUsuario(null, $data_inicio, $data_fim);
            } elseif ($action === 'pessoal' && $id) {
                // GET /api/escalas/{id}/pessoal
                $result = ['success' => true, 'data' => $controller->get($id)['data']['pessoal']];
            } elseif ($action === 'pdf' && $id) {
                // GET /api/escalas/{id}/pdf
                $result = $controller->generatePDF($id);
            } elseif ($id) {
                // GET /api/escalas/{id}
                $result = $controller->get($id);
            } else {
                // GET /api/escalas
                $filters = $_GET;
                $result = $controller->list($filters);
            }
            break;
            
        case 'POST':
            if ($action === 'create') {
                // POST /api/escalas/create
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    $input = $_POST;
                }
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->create($input);
            } elseif ($action === 'add-pessoal' && $id) {
                // POST /api/escalas/{id}/add-pessoal
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) {
                    $input = $_POST;
                }
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->addPessoal($id, $input);
            } else {
                jsonError('Ação não encontrada', 404);
            }
            break;
            
        case 'PUT':
            if ($id) {
                // PUT /api/escalas/{id}
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->update($id, $input);
            } else {
                jsonError('ID da escala é obrigatório', 400);
            }
            break;
            
        case 'DELETE':
            if ($action === 'remove-pessoal' && $id && !empty($path_parts[5])) {
                // DELETE /api/escalas/{id}/remove-pessoal/{pessoal_id}
                $pessoal_id = (int)$path_parts[5];
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->removePessoal($id, $pessoal_id);
            } elseif ($id) {
                // DELETE /api/escalas/{id}
                $input = json_decode(file_get_contents('php://input'), true);
                
                // Validar token CSRF
                if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                    jsonError('Token CSRF inválido', 403);
                }
                
                $result = $controller->update($id, ['status' => 'inativa']);
            } else {
                jsonError('ID da escala é obrigatório', 400);
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
    logMessage('ERROR', 'Erro na API de escalas', ['error' => $e->getMessage()]);
    jsonError('Erro interno do servidor', 500);
}
?> 