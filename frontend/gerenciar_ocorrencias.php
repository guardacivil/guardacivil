<?php
require_once 'auth_check.php';
require_once 'config_mysql.php';
requireLogin();

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['perfil'] === 'Guarda Civil') {
    header('Location: dashboard.php?error=permission_denied');
    exit;
}

// Excluir ocorrência
if (isset($_GET['excluir']) && is_numeric($_GET['excluir'])) {
    $idExcluir = intval($_GET['excluir']);
    $stmt = $pdo->prepare('DELETE FROM ocorrencias WHERE id = ?');
    $stmt->execute([$idExcluir]);
    header('Location: gerenciar_ocorrencias.php?msg=Ocorrência excluída com sucesso!');
    exit;
}

// Excluir múltiplas ocorrências
if (isset($_POST['excluir_selecionados']) && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $idsExcluir = array_map('intval', $_POST['ids']);
    if (count($idsExcluir) > 0) {
        $in = str_repeat('?,', count($idsExcluir) - 1) . '?';
        $stmt = $pdo->prepare('DELETE FROM ocorrencias WHERE id IN (' . $in . ')');
        $stmt->execute($idsExcluir);
        header('Location: gerenciar_ocorrencias.php?msg=' . urlencode(count($idsExcluir) . ' ocorrência(s) excluída(s) com sucesso!'));
        exit;
    }
}

// Buscar todas as ocorrências com nome do usuário
$ocorrencias = $pdo->query('SELECT o.*, u.nome as nome_usuario FROM ocorrencias o LEFT JOIN usuarios u ON o.usuario_id = u.id ORDER BY o.id DESC')->fetchAll(PDO::FETCH_ASSOC);

// Determinar quais campos têm pelo menos um valor preenchido
$campos_preenchidos = [];
foreach ($ocorrencias as $o) {
    foreach ($o as $campo => $valor) {
        if (!empty($valor) && !in_array($campo, $campos_preenchidos)) {
            $campos_preenchidos[] = $campo;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gerenciar Ocorrências - Sistema Integrado da Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100">
    <aside class="sidebar" style="width:16rem;background:#1e40af;color:white;height:100vh;position:fixed;top:0;left:0;overflow-y:auto;z-index:30;padding:1.25rem;">
        <div class="logo-container" style="text-align:center;margin-bottom:2.5rem;">
            <img src="img/logo1.png" alt="Logo" style="width:10.14rem;margin:0 auto 0.5rem auto;display:block;" />
            <h1 style="font-weight:700;font-size:1.25rem;margin-bottom:0.25rem;">Sistema Integrado da Guarda Civil</h1>
            <p style="font-size:0.875rem;color:#bfdbfe;margin:0;">Município de Araçoiaba da Serra</p>
        </div>
        <nav>
            <a href="dashboard.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Dashboard</a>
            
            <!-- Gestão de Pessoal -->
            <a href="pessoal.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Gestão de Pessoal</a>
            <a href="graduacoes.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Graduações</a>
            <a href="setores.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Setores</a>
            
            <!-- Comunicação Interna -->
            <a href="comunicacao.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Comunicação Interna</a>
            
            <!-- Gestão de Escalas -->
            <a href="escalas.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Gestão de Escalas</a>
            <a href="minhas_escalas.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Minhas Escalas</a>
            
            <!-- Ocorrências -->
            <div class="menu-group" style="margin-bottom:0.5rem;">
                <a href="#" class="menu-header" style="background-color:#1e3a8a;font-weight:600;cursor:pointer;position:relative;display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;"><i class="fas fa-exclamation-triangle"></i> Ocorrências</a>
                <div class="submenu" style="display:block;margin-left:1rem;margin-top:0.25rem;">
                    <a href="ROGCM.php" style="display:block;padding:0.375rem 1rem;font-size:0.875rem;margin-bottom:0.25rem;background-color:rgba(255,255,255,0.1);color:white;text-decoration:none;border-radius:0.375rem;"><i class="fas fa-file-alt"></i> Registro de Ocorrências</a>
                    <a href="minhas_ocorrencias.php" style="display:block;padding:0.375rem 1rem;font-size:0.875rem;margin-bottom:0.25rem;background-color:rgba(255,255,255,0.1);color:white;text-decoration:none;border-radius:0.375rem;"><i class="fas fa-clipboard-list"></i> Minhas Ocorrências</a>
                </div>
            </div>
            <a href="gerenciar_ocorrencias.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;background:#2563eb;">Gerenciar Ocorrências</a>
            
            <!-- Relatórios -->
            <a href="relatorios.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Relatórios</a>
            <a href="relatorios_agendados.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Relatórios Agendados</a>
            <a href="filtros_avancados.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Filtros Avançados</a>
            <a href="relatorios_hierarquia.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Relatórios por Hierarquia</a>
            
            <!-- Administração do Sistema -->
            <a href="usuarios.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Gestão de Usuários</a>
            <a href="perfis.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Perfis e Permissões</a>
            <a href="logs.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Logs do Sistema</a>
            <a href="configuracoes.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Configurações Gerais</a>
            <a href="banco_dados.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Banco de Dados</a>
            <a href="alertas.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Alertas e Notificações</a>
            <a href="suporte.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Suporte</a>
            
            <a href="checklist.php" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;">Conferir Checklists</a>
            <a href="logout.php" class="logout" style="display:block;padding:0.5rem 1rem;border-radius:0.375rem;margin-bottom:0.5rem;color:white;text-decoration:none;background:#dc2626;">Sair</a>
        </nav>
    </aside>
    <main class="content" style="margin-left:16rem;padding:2rem;width:calc(100% - 16rem);">
        <h2 class="text-3xl font-bold mb-6"><i class="fas fa-tasks mr-2"></i>Gerenciar Ocorrências</h2>
        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 px-4 py-3 rounded bg-green-100 border border-green-400 text-green-800">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" action="gerenciar_ocorrencias.php" onsubmit="return confirm('Tem certeza que deseja excluir as ocorrências selecionadas?');">
                <div class="mb-4">
                    <button type="submit" name="excluir_selecionados" class="bg-red-600 hover:bg-red-800 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-trash mr-1"></i>Excluir Selecionados
                    </button>
                </div>
                <table class="min-w-full table-auto border text-xs">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 border"><input type="checkbox" id="checkAll" onclick="marcarTodos(this)"></th>
                            <th class="p-2 border">ID</th>
                            <th class="p-2 border">Número</th>
                            <th class="p-2 border">Data</th>
                            <th class="p-2 border">Natureza</th>
                            <th class="p-2 border">Local</th>
                            <th class="p-2 border">Registrada por</th>
                            <th class="p-2 border">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ocorrencias as $o): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-2 border text-center">
                                <input type="checkbox" name="ids[]" value="<?= $o['id'] ?>">
                            </td>
                            <td class="p-2 border"><?= $o['id'] ?></td>
                            <td class="p-2 border"><?= htmlspecialchars($o['numero_ocorrencia'] ?? 'N/A') ?></td>
                            <td class="p-2 border"><?= !empty($o['data']) ? date('d/m/Y', strtotime($o['data'])) : '' ?></td>
                            <td class="p-2 border"><?= htmlspecialchars($o['natureza'] ?? 'N/A') ?></td>
                            <td class="p-2 border"><?= htmlspecialchars($o['local'] ?? 'N/A') ?></td>
                            <td class="p-2 border"><?= htmlspecialchars($o['nome_usuario'] ?? 'N/A') ?></td>
                            <td class="p-2 border">
                                <a class="text-blue-600 hover:text-blue-800 text-sm mr-2" href="ver_ocorrencia.php?id=<?= $o['id'] ?>" target="_blank">
                                    <i class="fas fa-eye mr-1"></i>Ver
                                </a>
                                <a class="text-green-600 hover:text-green-800 text-sm mr-2" href="ver_ocorrencia.php?id=<?= $o['id'] ?>&imagens=1" target="_blank">
                                    <i class="fas fa-image mr-1"></i>Imagens
                                </a>
                                <a class="text-yellow-600 hover:text-yellow-800 text-sm mr-2" href="editar_ocorrencia.php?id=<?= $o['id'] ?>">
                                    <i class="fas fa-edit mr-1"></i>Editar
                                </a>
                                <a class="text-red-600 hover:text-red-800 text-sm" href="gerenciar_ocorrencias.php?excluir=<?= $o['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir esta ocorrência?');">
                                    <i class="fas fa-trash mr-1"></i>Excluir
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <script>
                function marcarTodos(source) {
                    checkboxes = document.querySelectorAll('input[name="ids[]"]');
                    for(var i=0, n=checkboxes.length;i<n;i++) {
                        checkboxes[i].checked = source.checked;
                    }
                }
            </script>
        </div>
    </main>
</body>
</html> 