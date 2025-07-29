<?php
require_once 'auth_check.php';
require_once 'config.php';

if (!isLoggedIn()) {
    echo '<p>Você não está logado.</p>';
    exit;
}

$perfil_id = $_SESSION['usuario_perfil_id'] ?? null;
if (!$perfil_id) {
    echo '<p>Perfil do usuário não encontrado na sessão.</p>';
    exit;
}

$stmt = $pdo->prepare('SELECT nome, permissoes FROM perfis WHERE id = ?');
$stmt->execute([$perfil_id]);
$perfil = $stmt->fetch();

if (!$perfil) {
    echo '<p>Perfil não encontrado no banco de dados.</p>';
    exit;
}

$permissoes = json_decode($perfil['permissoes'], true);

?>
<h2>Diagnóstico de Permissões do Perfil</h2>
<p><b>Perfil:</b> <?= htmlspecialchars($perfil['nome']) ?></p>
<p><b>Permissões:</b></p>
<ul>
<?php if (is_array($permissoes)) foreach ($permissoes as $perm): ?>
    <li><?= htmlspecialchars($perm) ?><?= $perm === 'checklists' ? ' <b style="color:green">(checklists liberado)</b>' : '' ?></li>
<?php endforeach; ?>
</ul>
<?php if (is_array($permissoes) && in_array('checklists', $permissoes)): ?>
    <p style="color:green"><b>O acesso ao checklist está LIBERADO para este perfil.</b></p>
<?php else: ?>
    <p style="color:red"><b>O acesso ao checklist NÃO está liberado para este perfil.</b></p>
<?php endif; ?> 