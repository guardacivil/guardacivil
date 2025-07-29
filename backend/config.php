<?php
/**
 * Configurações do Sistema SMART - Backend
 * Guarda Civil Municipal de Araçoiaba da Serra
 */

// Configurações de Sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // 1 para HTTPS
session_start();

// Configurações de Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de Erro
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Configurações de Banco de Dados
define('DB_TYPE', 'sqlite'); // 'mysql' ou 'sqlite'
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PATH', __DIR__ . '/../database/smart_system.db');

// Configurações de E-mail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu-email@gmail.com');
define('SMTP_PASS', 'sua-senha-app');
define('SMTP_FROM', 'smart@gcm.aracoiaba.sp.gov.br');
define('SMTP_FROM_NAME', 'Sistema SMART - GCM Araçoiaba');

// Configurações do Sistema
define('SISTEMA_NOME', 'Sistema SMART');
define('SISTEMA_VERSAO', '2.0.0');
define('ORGAO_NOME', 'Guarda Civil Municipal de Araçoiaba da Serra');
define('ORGAO_CNPJ', '00.000.000/0000-00');
define('ORGAO_ENDERECO', 'Rua Example, 123 - Centro');
define('ORGAO_CIDADE', 'Araçoiaba da Serra - SP');
define('ORGAO_CEP', '18190-000');
define('ORGAO_TELEFONE', '(15) 3333-3333');

// Configurações de Upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Configurações de PDF
define('PDF_TITLE', 'Registro de Ocorrência - GCM Araçoiaba');
define('PDF_AUTHOR', 'Sistema SMART');
define('PDF_CREATOR', 'Sistema SMART v2.0');

// Configurações de Segurança
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600); // 1 hora
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutos

// URLs do Sistema
define('BASE_URL', 'https://guardacivil.github.io/guardacivil');
define('FRONTEND_URL', BASE_URL . '/frontend');
define('BACKEND_URL', BASE_URL . '/backend');
define('UPLOADS_URL', BASE_URL . '/uploads');

// Configurações de Log
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Função para obter configuração
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Função para log
function logMessage($level, $message, $context = []) {
    $logFile = LOG_PATH . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logEntry = "[$timestamp] [$level] $message$contextStr\n";
    
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Função para sanitizar dados
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Função para validar CPF
function validateCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return false;
    }
    
    if (preg_match('/^(\d)\1+$/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

// Função para gerar token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Função para validar token CSRF
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Função para mascarar CPF
function maskCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) === 11) {
        return substr($cpf, 0, 3) . '.***.***-' . substr($cpf, -2);
    }
    return $cpf;
}

// Função para mascarar endereço
function maskAddress($address) {
    return '[OCULTO]';
}

// Função para formatar data
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

// Função para formatar data e hora
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return date($format, strtotime($datetime));
}

// Função para gerar número de ocorrência
function generateOcorrenciaNumber($ano) {
    return str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) . '/' . $ano;
}

// Configurações de resposta JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Tratamento de erros
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Função para resposta JSON
function jsonResponse($data, $status = 200, $message = '') {
    http_response_code($status);
    echo json_encode([
        'success' => $status < 400,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Função para resposta de erro
function jsonError($message, $status = 400, $data = null) {
    jsonResponse($data, $status, $message);
}

// Função para resposta de sucesso
function jsonSuccess($data = null, $message = 'Operação realizada com sucesso') {
    jsonResponse($data, 200, $message);
}
?> 