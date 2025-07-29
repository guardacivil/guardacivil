<?php
/**
 * API REST para Relatórios Agendados
 * Endpoint: /backend/api/relatorios_agendados.php
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

    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $db->getConnection()->prepare("SELECT * FROM relatorios_agendados WHERE id = ?");
                $stmt->execute([$id]);
                $relatorio = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($relatorio) {
                    jsonSuccess($relatorio);
                } else {
                    jsonError('Relatório não encontrado', 404);
                }
            } elseif ($action === 'historico' && $id) {
                $stmt = $db->getConnection()->prepare("SELECT * FROM relatorios_enviados WHERE relatorio_id = ? ORDER BY enviado_em DESC");
                $stmt->execute([$id]);
                $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
                jsonSuccess($historico);
            } else {
                $relatorios = $db->getConnection()->query("SELECT * FROM relatorios_agendados ORDER BY proximo_envio ASC")->fetchAll(PDO::FETCH_ASSOC);
                jsonSuccess($relatorios);
            }
            break;
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                jsonError('Token CSRF inválido', 403);
            }
            if ($action === 'status' && $id) {
                $novoStatus = $input['status'] ?? null;
                if (!$novoStatus) jsonError('Status não informado', 400);
                $db->getConnection()->prepare("UPDATE relatorios_agendados SET status = ? WHERE id = ?")->execute([$novoStatus, $id]);
                jsonSuccess(null, 'Status alterado com sucesso');
            } elseif ($action === 'enviar' && $id) {
                // Simular envio
                $db->getConnection()->prepare("UPDATE relatorios_agendados SET ultimo_envio = NOW() WHERE id = ?")->execute([$id]);
                jsonSuccess(null, 'Relatório enviado com sucesso!');
            } elseif ($action === 'teste' && $id) {
                jsonSuccess(null, 'Teste de envio realizado com sucesso!');
            } else {
                // Criar novo relatório agendado
                $stmt = $db->getConnection()->prepare("INSERT INTO relatorios_agendados (nome, tipo, frequencia, horario, formato, status, destinatarios, descricao, proximo_envio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $input['nome'], $input['tipo'], $input['frequencia'], $input['horario'], $input['formato'], $input['status'], $input['destinatarios'], $input['descricao'], date('Y-m-d H:i:s')
                ]);
                jsonSuccess(null, 'Relatório agendado criado com sucesso');
            }
            break;
        case 'PUT':
            if (!$id) jsonError('ID do relatório é obrigatório', 400);
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                jsonError('Token CSRF inválido', 403);
            }
            $stmt = $db->getConnection()->prepare("UPDATE relatorios_agendados SET nome=?, tipo=?, frequencia=?, horario=?, formato=?, status=?, destinatarios=?, descricao=? WHERE id=?");
            $stmt->execute([
                $input['nome'], $input['tipo'], $input['frequencia'], $input['horario'], $input['formato'], $input['status'], $input['destinatarios'], $input['descricao'], $id
            ]);
            jsonSuccess(null, 'Relatório agendado atualizado com sucesso');
            break;
        case 'DELETE':
            if (!$id) jsonError('ID do relatório é obrigatório', 400);
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$auth->validateCSRFToken($input['csrf_token'] ?? '')) {
                jsonError('Token CSRF inválido', 403);
            }
            $db->getConnection()->prepare("DELETE FROM relatorios_agendados WHERE id = ?")->execute([$id]);
            jsonSuccess(null, 'Relatório agendado excluído com sucesso');
            break;
        default:
            jsonError('Método não permitido', 405);
    }
} catch (Exception $e) {
    jsonError('Erro interno do servidor', 500);
} 