<?php
/**
 * API REST para Gestão de Graduações
 * Endpoint: /backend/api/graduacoes.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $auth = new Auth();
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $path_parts = explode('/', trim($path, '/'));
    $id = null;
    if (count($path_parts) > 3 && is_numeric($path_parts[3])) {
        $id = (int)$path_parts[3];
    }

    switch ($method) {
        case 'GET':
            if ($id) {
                $graduacao = $db->getGraduacao($id);
                if ($graduacao) {
                    jsonSuccess($graduacao);
                } else {
                    jsonError('Graduação não encontrada', 404);
                }
            } else {
                $graduacoes = $db->getAllGraduacoes();
                jsonSuccess($graduacoes);
            }
            break;
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                jsonError('Token CSRF inválido', 403);
            }
            $db->createGraduacao($input);
            jsonSuccess(null, 'Graduação criada com sucesso');
            break;
        case 'PUT':
            if (!$id) jsonError('ID da graduação é obrigatório', 400);
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                jsonError('Token CSRF inválido', 403);
            }
            $db->updateGraduacao($id, $input);
            jsonSuccess(null, 'Graduação atualizada com sucesso');
            break;
        case 'DELETE':
            if (!$id) jsonError('ID da graduação é obrigatório', 400);
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                jsonError('Token CSRF inválido', 403);
            }
            $db->deleteGraduacao($id);
            jsonSuccess(null, 'Graduação excluída com sucesso');
            break;
        default:
            jsonError('Método não permitido', 405);
    }
} catch (Exception $e) {
    jsonError('Erro interno do servidor', 500);
} 