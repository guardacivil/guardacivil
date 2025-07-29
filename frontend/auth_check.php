<?php
// auth_check.php - Verificação de autenticação para páginas protegidas

// Iniciar sessão se não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se o usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['logado']) && $_SESSION['logado'] === true;
}

/**
 * Verifica se o admin está logado
 */
function isAdminLoggedIn() {
    // Agora, apenas quem fez login como admin de verdade
    return isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true;
}

/**
 * Verifica se o usuário tem permissão para acessar uma funcionalidade
 */
function hasPermission($permission) {
    global $pdo;
    // Admin tem controle total - pode acessar tudo
    if (isAdminLoggedIn()) {
        return true;
    }
    // Verificar se está logado
    if (!isLoggedIn()) {
        return false;
    }
    // Verificar permissões apenas do perfil
    if (isset($_SESSION['usuario_perfil_id']) && $_SESSION['usuario_perfil_id']) {
        try {
            $stmt = $pdo->prepare("SELECT permissoes FROM perfis WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_perfil_id']]);
            $perfil = $stmt->fetch();
            if ($perfil && $perfil['permissoes']) {
                $permissoes = json_decode($perfil['permissoes'], true);
                if (is_array($permissoes) && !empty($permissoes)) {
                    return in_array($permission, $permissoes);
                }
            }
        } catch (PDOException $e) {
            error_log("Erro ao verificar permissões: " . $e->getMessage());
        }
    }
    // Por padrão, usuários comuns não têm permissão
    return false;
}

/**
 * Verifica se o usuário tem permissão específica para menu (admin sempre tem)
 */
function hasMenuPermission($permission) {
    // Admin sempre tem acesso a todos os itens do menu
    if (isAdminLoggedIn()) {
        return true;
    }
    
    // Para usuários comuns, verificar permissão específica
    return hasPermission($permission);
}

/**
 * Redireciona para login se não estiver autenticado
 */
function requireLogin() {
    if (!isLoggedIn() && !isAdminLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Redireciona para login se não for admin
 */
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Redireciona para login se não tiver permissão específica
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        header('Location: dashboard.php?error=permission_denied');
        exit;
    }
}

/**
 * Obtém informações do usuário logado
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'],
            'login' => $_SESSION['usuario_login'],
            'perfil' => $_SESSION['usuario_perfil'],
            'perfil_id' => $_SESSION['usuario_perfil_id']
        ];
    } elseif (isAdminLoggedIn()) {
        return [
            'id' => $_SESSION['admin_id'],
            'nome' => $_SESSION['admin_nome'],
            'login' => $_SESSION['admin_login'],
            'perfil' => $_SESSION['admin_perfil'],
            'perfil_id' => $_SESSION['admin_perfil_id'],
            'is_admin' => true
        ];
    }
    
    return null;
}

/**
 * Registra uma ação no log do sistema
 */
function logAction($acao, $tabela = null, $registro_id = null, $dados_anteriores = null, $dados_novos = null) {
    $user = getCurrentUser();
    if (!$user) return;
    
    require_once 'config.php';
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            $acao,
            $tabela,
            $registro_id,
            $dados_anteriores ? json_encode($dados_anteriores) : null,
            $dados_novos ? json_encode($dados_novos) : null,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
} 

/**
 * Verifica se o usuário tem permissão para acessar uma página específica
 */
function hasPagePermission($page) {
    // Admin tem acesso a todas as páginas
    if (isAdminLoggedIn()) {
        return true;
    }
    
    // Mapeamento de páginas para permissões
    $page_permissions = [
        'usuarios.php' => 'usuarios',
        'pessoal.php' => 'pessoal',
        'graduacoes.php' => 'graduacoes',
        'setores.php' => 'setores',
        'comunicacao.php' => 'comunicacao',
        'escalas.php' => 'escalas',
        'minhas_escalas.php' => 'minhas_escalas',
        'ROGCM.php' => 'ocorrencias',
        'gerenciar_ocorrencias.php' => 'gerenciar_ocorrencias',
        'relatorios.php' => 'relatorios',
        'relatorios_agendados.php' => 'relatorios_agendados',
        'filtros_avancados.php' => 'filtros_avancados',
        'relatorios_hierarquia.php' => 'relatorios_hierarquia',
        'perfis.php' => 'perfis',
        'logs.php' => 'logs',
        'configuracoes.php' => 'config',
        'banco_dados.php' => 'db',
        'alertas.php' => 'alertas',
        'suporte.php' => 'suporte',
        'checklist.php' => 'checklist'
    ];
    
    // Se a página não está mapeada, permitir acesso (páginas públicas)
    if (!isset($page_permissions[$page])) {
        return true;
    }
    
    // Verificar permissão específica
    return hasPermission($page_permissions[$page]);
}

/**
 * Requer permissão para acessar uma página específica
 */
function requirePagePermission($page) {
    if (!hasPagePermission($page)) {
        header('Location: dashboard.php?error=permission_denied');
        exit;
    }
} 