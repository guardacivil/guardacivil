<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<style>
    aside.sidebar {
        width: 16rem;
        background-color: #1e40af;
        color: white;
        height: 100vh;
        padding: 1.25rem;
        position: fixed;
        top: 0;
        left: 0;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        box-shadow: 2px 0 12px rgba(0,0,0,0.2);
        z-index: 30;
    }
    aside.sidebar .logo-container {
        text-align: center;
        margin-bottom: 2.5rem;
    }
    aside.sidebar .logo-container img {
        width: 10.14rem; /* Aumentado em mais 30% de 7.8rem para 10.14rem */
        margin: 0 auto 0.5rem auto;
        display: block;
    }
    aside.sidebar .logo-container h1 {
        font-weight: 700;
        font-size: 1.25rem;
        margin-bottom: 0.25rem;
    }
    aside.sidebar .logo-container p {
        font-size: 0.875rem;
        color: #bfdbfe;
        margin: 0;
    }
    aside.sidebar nav a {
        display: block;
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
        color: white;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }
    aside.sidebar nav a:hover {
        background-color: #2563eb;
    }
    aside.sidebar nav a.active {
        background-color: #2563eb;
    }
    aside.sidebar nav a.logout {
        background-color: #dc2626;
    }
    aside.sidebar nav a.logout:hover {
        background-color: #b91c1c;
    }
    .exclamacao-pisca {
        color: #dc2626;
        animation: piscar 1s infinite;
        margin-left: 6px;
    }
    @keyframes piscar {
        0%, 100% { opacity: 1; }
        50% { opacity: 0; }
    }
    /* Estilos para menu agrupado */
    .menu-group {
        margin-bottom: 0.5rem;
    }
    .menu-header {
        background-color: #1e3a8a !important;
        font-weight: 600;
        cursor: pointer;
        position: relative;
    }
    .menu-header:hover {
        background-color: #1e40af !important;
    }
    .menu-header::after {
        content: '\f107';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: 1rem;
        transition: transform 0.3s ease;
    }
    .menu-header.active::after {
        transform: rotate(180deg);
    }
    .submenu {
        display: none;
        margin-left: 1rem;
        margin-top: 0.25rem;
    }
    .submenu.active {
        display: block;
    }
    .submenu a {
        padding: 0.375rem 1rem;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
        background-color: rgba(255, 255, 255, 0.1);
    }
    .submenu a:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }
</style>
<?php
// Definir variáveis de pendências ANTES de qualquer HTML
$temPartesPendentes = false;
$temSuportePendente = false;
try {
    if (!isset($pdo)) {
        require_once 'config.php';
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM partes WHERE status = 'pendente_resposta'");
    $stmt->execute();
    $temPartesPendentes = $stmt->fetchColumn() > 0;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM suporte WHERE status = 'aberto' AND (resposta IS NULL OR resposta = '')");
    $stmt->execute();
    $temSuportePendente = $stmt->fetchColumn() > 0;
} catch (Exception $e) {
    $temPartesPendentes = false;
    $temSuportePendente = false;
}

// Definir variáveis de usuário ANTES dos grupos que as utilizam
if (!function_exists('getCurrentUser')) {
    require_once 'auth_check.php';
}
$currentUser = getCurrentUser();
$isAdmin = isAdminLoggedIn();
$perfil = $currentUser['perfil'] ?? '';
?>
<aside class="sidebar">
    <div class="logo-container" style="display: flex; flex-direction: column; align-items: center; justify-content: center; margin-bottom: 2.5rem;">
        <img src="img/logo1.png" alt="Logo" style="width: 10.14rem; max-width: 100%; height: auto; margin: 0 auto 0.5rem auto; display: block;" />
        <h1 style="font-weight: 700; font-size: 1.25rem; margin-bottom: 0.25rem; margin-top: 0.5rem; text-align: center;">Sistema Integrado da Guarda Civil</h1>
        <p style="font-size: 0.875rem; color: #bfdbfe; margin: 0; text-align: center;">Município de Araçoiaba da Serra</p>
    </div>
    <nav>
        <a href="dashboard.php"><i class="fas fa-shield-alt"></i> Dashboard</a>
        <!-- Grupos com subitens -->
        <div class="menu-group">
            <a href="#" class="menu-header"><i class="fas fa-calendar-day"></i> Plantão</a>
            <div class="submenu">
                <a href="checklist_viatura.php"><i class="fas fa-clipboard-check"></i> Checklist</a>
                <a href="deslocamento.php"><i class="fas fa-route"></i> Deslocamento</a>
            </div>
        </div>
        <div class="menu-group">
            <a href="#" class="menu-header"><i class="fas fa-exclamation-triangle"></i> Ocorrências</a>
            <div class="submenu">
                <?php if ($isAdmin || $perfil === 'Administrador' || in_array($perfil, ['Comando', 'Secretário', 'Suporte'])): ?>
                <a href="gerenciar_ocorrencias.php"><i class="fas fa-tasks"></i> Gerenciar Ocorrências</a>
                <?php endif; ?>
                <a href="ROGCM.php"><i class="fas fa-file-alt"></i> Registro de Ocorrências</a>
                <a href="minhas_ocorrencias.php"><i class="fas fa-clipboard-list"></i> Minhas Ocorrências</a>
            </div>
        </div>
        <div class="menu-group">
            <a href="#" class="menu-header"><i class="fas fa-file-alt"></i> Parte</a>
            <div class="submenu">
                <?php if ($isAdmin || $perfil === 'Administrador' || in_array($perfil, ['Comando', 'Secretário', 'Suporte'])): ?>
                <a href="partes_recebidas.php"><i class="fas fa-envelope-open-text"></i> Partes Recebidas<?php if($temPartesPendentes): ?><span class="exclamacao-pisca"> <i class="fas fa-exclamation-circle"></i></span><?php endif; ?></a>
                <?php endif; ?>
                <a href="minhas_partes.php"><i class="fas fa-paper-plane"></i> Minhas Partes</a>
                <a href="parte_nova.php"><i class="fas fa-file-signature"></i> Nova Parte</a>
            </div>
        </div>
        <?php if ($isAdmin || $perfil === 'Administrador' || in_array($perfil, ['Comando', 'Secretário', 'Suporte'])): ?>
        <div class="menu-group">
            <a href="#" class="menu-header"><i class="fas fa-users-cog"></i> Servidores</a>
            <div class="submenu">
                <a href="corporacao.php"><i class="fas fa-users"></i> Corporação</a>
                <a href="usuarios.php"><i class="fas fa-user-shield"></i> Gestão de Usuários</a>
                <a href="perfis.php"><i class="fas fa-id-card"></i> Perfis e Permissões</a>
            </div>
        </div>
        <?php endif; ?>
        <?php
        if ($isAdmin || $perfil === 'Administrador'): ?>
            <div class="menu-group">
                <a href="#" class="menu-header"><i class="fas fa-truck"></i> Frota</a>
                <div class="submenu">
                    <a href="gerenciar_deslocamentos.php"><i class="fas fa-list-alt"></i> Gerenciar Deslocamentos</a>
                    <a href="checklist_viatura.php"><i class="fas fa-clipboard-check"></i> Checklist de Viatura</a>
                </div>
            </div>
        <?php endif; ?>
        <!-- Demais links individuais do menu continuam abaixo dos grupos -->
        <?php
        if ($isAdmin || $perfil === 'Administrador'):
        ?>
            <div class="menu-group">
                <a href="#" class="menu-header"><i class="fas fa-cogs"></i> ADM</a>
                <div class="submenu">
                    <a href="setores.php"><i class="fas fa-building"></i> Setores</a>
                    <a href="escalas.php"><i class="fas fa-calendar-check"></i> Gestão de Escalas</a>
                    <a href="relatorios.php"><i class="fas fa-file-alt"></i> Relatórios</a>
                    <a href="filtros_avancados.php"><i class="fas fa-filter"></i> Filtros Avançados</a>
                    <a href="relatorios_hierarquia.php"><i class="fas fa-sitemap"></i> Relatórios por Hierarquia</a>
                </div>
            </div>
            <a href="mapa_usuarios.php"><i class="fas fa-map-marker-alt"></i> MAPA</a>
        <?php endif; ?>
        <?php
        if ($isAdmin || $perfil === 'Administrador'):
        ?>
            <a href="minhas_escalas.php"><i class="fas fa-calendar-day"></i> Minhas Escalas</a>
            <a href="logs.php"><i class="fas fa-history"></i> Logs do Sistema</a>
            <a href="configuracoes.php"><i class="fas fa-cog"></i> Configurações Gerais</a>
            <a href="banco_dados.php"><i class="fas fa-database"></i> Banco de Dados</a>
            <a href="alertas.php"><i class="fas fa-bell"></i> Alertas e Notificações</a>
            <a href="suporte.php"><i class="fas fa-headset"></i> Suporte<?php if($temSuportePendente): ?><span class="exclamacao-pisca"> <i class="fas fa-exclamation-circle"></i></span><?php endif; ?></a>
        <?php elseif (in_array($perfil, ['Comando', 'Secretário', 'Suporte'])): ?>
            <a href="setores.php"><i class="fas fa-building"></i> Setores</a>
            <a href="escalas.php"><i class="fas fa-calendar-check"></i> Gestão de Escalas</a>
            <a href="gerenciar_ocorrencias.php"><i class="fas fa-tasks"></i> Gerenciar Ocorrências</a>
            <a href="suporte.php"><i class="fas fa-headset"></i> Suporte</a>
        <?php elseif ($perfil === 'Guarda Civil'): ?>
            <a href="minhas_escalas.php"><i class="fas fa-calendar-day"></i> Minhas Escalas</a>
            <a href="suporte.php"><i class="fas fa-headset"></i> Suporte</a>
        <?php else: ?>
            <a href="suporte.php"><i class="fas fa-headset"></i> Suporte</a>
        <?php endif; ?>
        <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
        <div style="height: 6rem;"></div>
    </nav>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Adicionar funcionalidade de toggle para menus agrupados
    const menuHeaders = document.querySelectorAll('.menu-header');
    
    menuHeaders.forEach(header => {
        header.addEventListener('click', function(e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;
            const isActive = submenu.classList.contains('active');
            
            // Fechar todos os outros submenus
            document.querySelectorAll('.submenu').forEach(menu => {
                menu.classList.remove('active');
            });
            document.querySelectorAll('.menu-header').forEach(h => {
                h.classList.remove('active');
            });
            
            // Toggle do submenu atual
            if (!isActive) {
                submenu.classList.add('active');
                this.classList.add('active');
            }
        });
    });
    
    // Abrir automaticamente o menu Parte se estiver em uma página relacionada
    const currentPage = window.location.pathname.split('/').pop();
    const partePages = ['partes_recebidas.php', 'minhas_partes.php', 'parte_nova.php', 'parte.php'];
    const ocorrenciasPages = ['ROGCM.php', 'minhas_ocorrencias.php'];
    
    if (partePages.includes(currentPage)) {
        const parteMenu = document.querySelector('.menu-header');
        const parteSubmenu = document.querySelector('.submenu');
        if (parteMenu && parteSubmenu) {
            parteSubmenu.classList.add('active');
            parteMenu.classList.add('active');
        }
    }
    
    // Abrir automaticamente o menu Ocorrências se estiver em uma página relacionada
    if (ocorrenciasPages.includes(currentPage)) {
        const ocorrenciasMenus = document.querySelectorAll('.menu-header');
        ocorrenciasMenus.forEach((menu, index) => {
            const submenu = menu.nextElementSibling;
            if (submenu && submenu.classList.contains('submenu')) {
                const submenuLinks = submenu.querySelectorAll('a');
                const hasOcorrenciasLink = Array.from(submenuLinks).some(link => 
                    ocorrenciasPages.includes(link.getAttribute('href'))
                );
                if (hasOcorrenciasLink) {
                    submenu.classList.add('active');
                    menu.classList.add('active');
                }
            }
        });
    }
});
</script>
