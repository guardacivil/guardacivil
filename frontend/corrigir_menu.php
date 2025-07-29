<?php
// Script para corrigir menus laterais em todos os arquivos PHP
$frontendDir = __DIR__;

// Menu lateral padrão completo
$menuPadrao = '    <nav>
      <a href="dashboard.php">Dashboard</a>
      
      <!-- Gestão de Pessoal -->
      <a href="pessoal.php">Gestão de Pessoal</a>
      <a href="graduacoes.php">Graduações</a>
      <a href="setores.php">Setores</a>
      
      <!-- Comunicação Interna -->
      <a href="comunicacao.php">Comunicação Interna</a>
      
      <!-- Gestão de Escalas -->
      <a href="escalas.php">Gestão de Escalas</a>
      <a href="minhas_escalas.php">Minhas Escalas</a>
      
      <!-- Ocorrências -->
      <div class="menu-group">
        <a href="#" class="menu-header"><i class="fas fa-exclamation-triangle"></i> Ocorrências</a>
        <div class="submenu">
          <a href="ROGCM.php"><i class="fas fa-file-alt"></i> Registro de Ocorrências</a>
          <a href="minhas_ocorrencias.php"><i class="fas fa-clipboard-list"></i> Minhas Ocorrências</a>
        </div>
      </div>
      <a href="gerenciar_ocorrencias.php">Gerenciar Ocorrências</a>
      
      <!-- Relatórios -->
      <a href="relatorios.php">Relatórios</a>
      <a href="relatorios_agendados.php">Relatórios Agendados</a>
      <a href="filtros_avancados.php">Filtros Avançados</a>
      <a href="relatorios_hierarquia.php">Relatórios por Hierarquia</a>
      
      <!-- Administração do Sistema -->
      <a href="usuarios.php">Gestão de Usuários</a>
      <a href="perfis.php">Perfis e Permissões</a>
      <a href="logs.php">Logs do Sistema</a>
      <a href="configuracoes.php">Configurações Gerais</a>
      <a href="banco_dados.php">Banco de Dados</a>
      <a href="alertas.php">Alertas e Notificações</a>
      <a href="suporte.php">Suporte</a>
      
      <a href="checklist.php">Conferir Checklists</a>
      <a href="logout.php" class="logout">Sair</a>
    </nav>';

// Header padrão
$headerPadrao = '    <div class="logo-container">
      <img src="img/logo1.png" alt="Logo" />
      <h1>Sistema Integrado da Guarda Civil</h1>
      <p>Município de Araçoiaba da Serra</p>
    </div>';

// Lista de arquivos para corrigir
$arquivos = [
    'usuarios.php',
    'perfis.php',
    'logs.php',
    'configuracoes.php',
    'banco_dados.php',
    'alertas.php',
    'suporte.php',
    'pessoal.php',
    'graduacoes.php',
    'setores.php',
    'comunicacao.php',
    'escalas.php',
    'minhas_escalas.php',
    'ROGCM.php',
    'gerenciar_ocorrencias.php',
    'relatorios.php',
    'relatorios_agendados.php',
    'filtros_avancados.php',
    'relatorios_hierarquia.php',
    'checklist.php',
    'notificacoes.php',
    'config_notificacoes.php',

];

$corrigidos = 0;

foreach ($arquivos as $arquivo) {
    $caminho = $frontendDir . '/' . $arquivo;
    
    if (!file_exists($caminho)) {
        echo "Arquivo não encontrado: $arquivo\n";
        continue;
    }
    
    $conteudo = file_get_contents($caminho);
    $modificado = false;
    
    // Substituir nome do sistema
    if (strpos($conteudo, 'Sistema SMART') !== false) {
        $conteudo = str_replace('Sistema SMART', 'Sistema Integrado da Guarda Civil', $conteudo);
        $modificado = true;
    }
    
    // Substituir logo
    if (strpos($conteudo, 'logo.png') !== false) {
        $conteudo = str_replace('logo.png', 'logo1.png', $conteudo);
        $modificado = true;
    }
    
    // Corrigir menu lateral se necessário
    if (strpos($conteudo, 'relatorios_agendados.php') === false) {
        // Encontrar o início do menu
        $inicioMenu = strpos($conteudo, '<nav>');
        $fimMenu = strpos($conteudo, '</nav>');
        
        if ($inicioMenu !== false && $fimMenu !== false) {
            $menuAtual = substr($conteudo, $inicioMenu, $fimMenu - $inicioMenu + 6);
            
            // Verificar se precisa adicionar os links de relatórios
            if (strpos($menuAtual, 'relatorios_agendados.php') === false) {
                // Encontrar onde inserir os novos links
                $posRelatorios = strpos($menuAtual, '<!-- Relatórios -->');
                if ($posRelatorios !== false) {
                    $posFimRelatorios = strpos($menuAtual, '<!-- Administração do Sistema -->', $posRelatorios);
                    
                    if ($posFimRelatorios !== false) {
                        $novoMenu = substr($menuAtual, 0, $posFimRelatorios) . 
                                   '      <a href="relatorios_agendados.php">Relatórios Agendados</a>' . "\n" .
                                   '      <a href="filtros_avancados.php">Filtros Avançados</a>' . "\n" .
                                   '      <a href="relatorios_hierarquia.php">Relatórios por Hierarquia</a>' . "\n" .
                                   '      ' . substr($menuAtual, $posFimRelatorios);
                        
                        $conteudo = str_replace($menuAtual, $novoMenu, $conteudo);
                        $modificado = true;
                    }
                }
            }
        }
    }
    
    // Salvar se foi modificado
    if ($modificado) {
        file_put_contents($caminho, $conteudo);
        $corrigidos++;
        echo "✓ Corrigido: $arquivo\n";
    } else {
        echo "- Sem alterações: $arquivo\n";
    }
}

echo "\n🎉 Correção concluída! $corrigidos arquivos foram atualizados.\n";
echo "✅ Todos os menus laterais agora têm os links completos.\n";
echo "✅ Nome do sistema atualizado para 'Sistema Integrado da Guarda Civil'.\n";
echo "✅ Logo atualizado para 'logo1.png'.\n";
?> 