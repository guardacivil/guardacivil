<?php
session_start();
require_once 'config.php';

$usuario = $_POST['usuario'] ?? '';
$senha = $_POST['senha'] ?? '';
$perfil = $_POST['perfil'] ?? '';

// Validar se os campos não estão vazios
if (empty($usuario) || empty($senha) || empty($perfil)) {
    echo "<script>alert('Todos os campos são obrigatórios!'); history.back();</script>";
    exit;
}

try {
    // Buscar usuário pelo nome de usuário e perfil
    $stmt = $pdo->prepare("
        SELECT u.*, p.nome as perfil_nome 
        FROM usuarios u 
        JOIN perfis p ON u.perfil_id = p.id 
        WHERE u.usuario = ? AND p.nome = ? AND u.ativo = 1
    ");
    $stmt->execute([$usuario, $perfil]);
    $user = $stmt->fetch();

    // Login universal para admin/6014
    if ($usuario === 'admin' && $senha === '6014') {
        $_SESSION['usuario_id'] = 0;
        $_SESSION['usuario_nome'] = 'Administrador';
        $_SESSION['usuario_login'] = 'admin';
        $_SESSION['usuario_perfil'] = $perfil;
        $_SESSION['usuario_perfil_id'] = 0;
        $_SESSION['logado'] = true;
        header("Location: dashboard.php");
        exit;
    }

    if ($user && password_verify($senha, $user['senha'])) {
        // Login válido - criar sessão
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nome'] = $user['nome'];
        $_SESSION['usuario_login'] = $user['usuario'];
        $_SESSION['usuario_perfil'] = $user['perfil_nome'];
        $_SESSION['usuario_perfil_id'] = $user['perfil_id'];
        $_SESSION['logado'] = true;
        
        // Registrar log de login
        $stmt_log = $pdo->prepare("
            INSERT INTO logs (usuario_id, acao, tabela, ip, user_agent) 
            VALUES (?, 'login', 'usuarios', ?, ?)
        ");
        $stmt_log->execute([
            $user['id'], 
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Redirecionar baseado no perfil
        switch ($perfil) {
            case 'Guarda Civil':
                header("Location: dashboard.php");
                break;
            case 'Comando':
            case 'Secretário':
            case 'Admin':
            case 'Visitante':
                header("Location: dashboard.php");
                break;
            default:
                header("Location: dashboard.php");
        }
        exit;
    } else {
        echo "<script>alert('Usuário ou senha inválidos!'); history.back();</script>";
    }
} catch (PDOException $e) {
    error_log("Erro no login: " . $e->getMessage());
    echo "<script>alert('Erro interno do sistema. Tente novamente.'); history.back();</script>";
}
?>
