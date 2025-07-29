<?php
/**
 * API REST para Alertas/Notificações
 * Endpoint: /backend/api/alertas.php
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
    $action = $path_parts[4] ?? null;
    $currentUser = $auth->getCurrentUser();

    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                jsonError('Token CSRF inválido', 403);
            }
            if ($action === 'marcar-todas-lidas') {
                $db->getConnection()->prepare("UPDATE alertas SET status = 'lido' WHERE status = 'pendente'")->execute();
                jsonSuccess(null, 'Todas as notificações marcadas como lidas!');
            } elseif ($action === 'configuracoes') {
                // Exemplo: salvar preferências do usuário (simulado)
                jsonSuccess(null, 'Configurações salvas com sucesso!');
            } elseif ($id && $action === 'marcar-lida') {
                $db->getConnection()->prepare("UPDATE alertas SET status = 'lido' WHERE id = ?")->execute([$id]);
                jsonSuccess(null, 'Notificação marcada como lida!');
            } elseif ($id && $action === 'excluir') {
                $db->getConnection()->prepare("DELETE FROM alertas WHERE id = ?")->execute([$id]);
                jsonSuccess(null, 'Notificação excluída!');
            } else {
                jsonError('Ação não encontrada', 404);
            }
            break;
        default:
            jsonError('Método não permitido', 405);
    }
} catch (Exception $e) {
    jsonError('Erro interno do servidor', 500);
} 