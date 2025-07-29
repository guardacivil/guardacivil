<?php
require_once 'config_mysql.php';

// Listar arquivos da pasta uploads
$uploadsDir = __DIR__ . '/uploads';
$arquivos = [];
if (is_dir($uploadsDir)) {
    $arquivos = array_diff(scandir($uploadsDir), ['.', '..']);
}

// Buscar valores dos campos de foto no banco
$camposFoto = [
    'foto_nome_vitima',
    'foto_nome_autor',
    'foto_nome_testemunha1',
    'foto_nome_testemunha2'
];
$sql = 'SELECT id, numero_ocorrencia, ' . implode(',', $camposFoto) . ' FROM ocorrencias ORDER BY id DESC';
$ocorrencias = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comparar Fotos - Uploads x Banco</title>
    <style>table{border-collapse:collapse;}td,th{border:1px solid #ccc;padding:4px 8px;}</style>
</head>
<body>
    <h2>Arquivos na pasta uploads/</h2>
    <ul>
        <?php foreach ($arquivos as $arq): ?>
            <li><?= htmlspecialchars($arq) ?></li>
        <?php endforeach; ?>
    </ul>
    <h2>Valores salvos no banco (ocorrências)</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Número</th>
            <?php foreach ($camposFoto as $campo): ?><th><?= htmlspecialchars($campo) ?></th><?php endforeach; ?>
        </tr>
        <?php foreach ($ocorrencias as $o): ?>
            <tr>
                <td><?= $o['id'] ?></td>
                <td><?= htmlspecialchars($o['numero_ocorrencia']) ?></td>
                <?php foreach ($camposFoto as $campo): ?>
                    <td><?= htmlspecialchars($o[$campo]) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html> 