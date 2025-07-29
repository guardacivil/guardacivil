<?php
require_once 'config.php';

$sql = "UPDATE perfis SET permissoes = '[\"comunicacao\",\"minhas_escalas\",\"ocorrencias\",\"suporte\",\"checklists\"]' WHERE nome = 'Guarda Civil'";
$ok = $pdo->exec($sql);

if ($ok !== false) {
    echo '<p>Permissão de checklist liberada para o perfil Guarda Civil!</p>';
} else {
    echo '<p>Erro ao atualizar permissões.</p>';
} 