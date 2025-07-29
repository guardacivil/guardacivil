<?php
require_once 'auth_check.php';
require_once 'config.php';
require_once 'pdf_ocorrencia_util.php';
requireLogin();

$currentUser = getCurrentUser();

// Download do PDF
if (isset($_GET['download_pdf']) && is_numeric($_GET['download_pdf'])) {
    $id = intval($_GET['download_pdf']);
    $stmt = $pdo->prepare("SELECT * FROM partes WHERE id = ? AND do_usuario_id = ?");
    $stmt->execute([$id, $currentUser['id']]);
    $parte = $stmt->fetch();
    if ($parte) {
        $pdf_path = gerarPdfParte($parte);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="parte_' . $parte['numero'] . '.pdf"');
        readfile($pdf_path);
        unlink($pdf_path);
        exit;
    }
}

// Buscar partes enviadas pelo usuário logado
$stmt = $pdo->prepare("SELECT * FROM partes WHERE do_usuario_id = ? ORDER BY data DESC, hora DESC");
$stmt->execute([$currentUser['id']]);
$partes = $stmt->fetchAll();

// Encerrar parte
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['encerrar_parte']) &&
    isset($_POST['parte_id'])
) {
    $id = intval($_POST['parte_id']);
    $stmt = $pdo->prepare('UPDATE partes SET status = "encerrado", encerrado_por = ?, data_encerramento = NOW() WHERE id = ?');
    $stmt->execute([$currentUser['id'], $id]);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
// Reabrir parte
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['reabrir_parte']) &&
    isset($_POST['parte_id'])
) {
    $id = intval($_POST['parte_id']);
    $stmt = $pdo->prepare('UPDATE partes SET status = "aberto", encerrado_por = NULL, data_encerramento = NULL WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Partes - Sistema Integrado da Guarda Civil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100">
    <main class="max-w-3xl mx-auto bg-white p-8 mt-10 rounded shadow">
        <h2 class="text-2xl font-bold mb-6"><i class="fas fa-paper-plane mr-2"></i>Minhas Partes Enviadas</h2>
        <?php if (empty($partes)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">Nenhuma parte enviada.</div>
        <?php else: ?>
            <table class="w-full table-auto border-collapse mb-6">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border px-4 py-2">Nº Parte</th>
                        <th class="border px-4 py-2">Data</th>
                        <th class="border px-4 py-2">Hora</th>
                        <th class="border px-4 py-2">Para</th>
                        <th class="border px-4 py-2">Assunto</th>
                        <th class="border px-4 py-2">Referência</th>
                        <th class="border px-4 py-2">Relato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($partes as $parte): ?>
                        <tr>
                            <td class="border px-4 py-2"><?= htmlspecialchars($parte['numero']) ?></td>
                            <td class="border px-4 py-2"><?= htmlspecialchars($parte['data']) ?></td>
                            <td class="border px-4 py-2"><?= htmlspecialchars($parte['hora']) ?></td>
                            <td class="border px-4 py-2"><?= htmlspecialchars($parte['ao']) ?></td>
                            <td class="border px-4 py-2"><?= htmlspecialchars($parte['assunto']) ?></td>
                            <td class="border px-4 py-2"><?= htmlspecialchars($parte['referencia']) ?></td>
                            <td class="border px-4 py-2">
                                <div class="mb-1">
                                    <b>Status:</b>
                                    <?php if ($parte['status'] === 'encerrado'): ?>
                                        <span class="text-red-700 font-bold">Encerrado</span>
                                        <?php if ($parte['data_encerramento']): ?>
                                            em <?= date('d/m/Y H:i', strtotime($parte['data_encerramento'])) ?>
                                        <?php endif; ?>
                                    <?php elseif ($parte['status'] === 'aguardando'): ?>
                                        <span class="text-yellow-700 font-bold">Aguardando</span>
                                    <?php else: ?>
                                        <span class="text-green-700 font-bold">Aberto</span>
                                    <?php endif; ?>
                                </div>
                                <?= nl2br(htmlspecialchars($parte['relato'])) ?>
                                <?php
                                // Buscar histórico de respostas
                                $stmtResp = $pdo->prepare('SELECT * FROM parte_respostas WHERE parte_id = ? ORDER BY data_resposta ASC');
                                $stmtResp->execute([$parte['id']]);
                                $respostas = $stmtResp->fetchAll();
                                ?>
                                <?php foreach ($respostas as $resp): ?>
                                    <div class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-green-800">
                                        <b><?= htmlspecialchars($resp['usuario_nome']) ?> respondeu em <?= date('d/m/Y H:i', strtotime($resp['data_resposta'])) ?>:</b><br>
                                        <?= nl2br(htmlspecialchars($resp['resposta'])) ?>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($parte['status'] !== 'encerrado'): ?>
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="parte_id" value="<?= $parte['id'] ?>">
                                        <textarea name="resposta" class="w-full border rounded px-2 py-1 mb-2" required placeholder="Digite sua resposta ou questionamento..."></textarea>
                                        <button type="submit" name="responder_parte" class="bg-blue-600 text-white px-3 py-1 rounded">Responder / Questionar</button>
                                    </form>
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="parte_id" value="<?= $parte['id'] ?>">
                                        <button type="submit" name="encerrar_parte" class="bg-red-600 text-white px-3 py-1 rounded" onclick="return confirm('Tem certeza que deseja encerrar este processo?')">Encerrar</button>
                                    </form>
                                <?php elseif ($parte['status'] === 'encerrado' && $parte['encerrado_por'] == $currentUser['id']): ?>
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="parte_id" value="<?= $parte['id'] ?>">
                                        <button type="submit" name="reabrir_parte" class="bg-yellow-600 text-white px-3 py-1 rounded" onclick="return confirm('Deseja reabrir este processo?')">Reabrir</button>
                                    </form>
                                <?php endif; ?>
                                <a href="?download_pdf=<?= $parte['id'] ?>" class="inline-block mt-2 px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors" title="Baixar PDF"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <a href="dashboard.php" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors"><i class="fas fa-arrow-left mr-2"></i>Voltar</a>
    </main>
</body>
</html> 