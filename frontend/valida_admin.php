<?php
session_start();
require_once 'config.php';

// Pega dados do formulário
$user = isset($_POST['adminUsername']) ? trim($_POST['adminUsername']) : '';
$pass = isset($_POST['adminPassword']) ? trim($_POST['adminPassword']) : '';

// Verifica se campos não estão vazios
if ($user === '' || $pass === '') {
    $_SESSION['admin_login_error'] = 'Preencha usuário e senha.';
    header('Location: index.php');
    exit;
}

// Login universal para admin/6014
if ($user === 'admin' && $pass === '6014') {
    $_SESSION['admin_id'] = 0;
    $_SESSION['admin_nome'] = 'Administrador';
    $_SESSION['admin_login'] = 'admin';
    $_SESSION['admin_perfil'] = 'Administrador';
    $_SESSION['admin_perfil_id'] = 0;
    $_SESSION['admin_logado'] = true;
    header('Location: dashboard.php');
    exit;
}

try {
    // Buscar usuário administrador
    $stmt = $pdo->prepare("
        SELECT u.*, p.nome as perfil_nome 
        FROM usuarios u 
        JOIN perfis p ON u.perfil_id = p.id 
        WHERE u.usuario = ? AND p.nome = 'Administrador' AND u.ativo = 1
    ");
    $stmt->execute([$user]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($pass, $admin['senha'])) {
        // Login válido - criar sessão
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nome'] = $admin['nome'];
        $_SESSION['admin_login'] = $admin['usuario'];
        $_SESSION['admin_perfil'] = $admin['perfil_nome'];
        $_SESSION['admin_perfil_id'] = $admin['perfil_id'];
        $_SESSION['admin_logado'] = true;
        
        // Registrar log de login admin
        $stmt_log = $pdo->prepare("
            INSERT INTO logs (usuario_id, acao, tabela, ip, user_agent) 
            VALUES (?, 'login_admin', 'usuarios', ?, ?)
        ");
        $stmt_log->execute([
            $admin['id'], 
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        header('Location: dashboard.php');
        exit;
    } else {
        $_SESSION['admin_login_error'] = 'Usuário ou senha inválidos.';
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro no login admin: " . $e->getMessage());
    $_SESSION['admin_login_error'] = 'Erro interno do sistema. Tente novamente.';
    header('Location: index.php');
    exit;
}
?>
