<?php
require_once 'auth_check.php';
require_once 'database_sqlite.php';

requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$ocorrencia = null;

if ($id > 0) {
    $sqlite = new DatabaseSQLite();
    $ocorrencia = $sqlite->getOcorrencia($id);
}

$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';

if ($ocorrencia) {
    if ($perfil === 'Guarda Civil' && $ocorrencia['usuario_id'] != $currentUser['id']) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Acesso negado: você não tem permissão para visualizar esta ocorrência.</div>';
        exit;
    }
}

function mascaraCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) return $cpf;
    return substr($cpf, 0, 3) . '.***.***-' . substr($cpf, -2);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Visualizar Ocorrência - Sistema SMART (SQLite)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Cabeçalho institucional -->
    <div class="max-w-3xl mx-auto flex items-center gap-4 mt-8 mb-2">
        <img src="img/brasao-oficial-sem-fundo.png" alt="Brasão" style="height:64px;width:auto;">
        <div>
            <div class="text-xl font-bold text-blue-900">Guarda Civil Municipal de Araçoiaba da Serra</div>
            <div class="text-sm text-blue-700">Sistema SMART - Visualizar Ocorrência (SQLite)</div>
        </div>
    </div>
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-8 mt-4">
        <h2 class="text-2xl font-bold mb-6 text-blue-900 flex items-center">
            <i class="fas fa-file-alt mr-2"></i> Detalhes da Ocorrência
        </h2>
        <?php if ($ocorrencia): ?>
            <div class="mb-6">
                <div class="mb-4 p-4 bg-blue-50 border-l-4 border-blue-400 rounded">
                    <span class="font-bold text-lg text-blue-800">Nº Registro: <?= htmlspecialchars($ocorrencia['numero_ocorrencia']) ?></span>
                    <a href="gerar_pdf_ocorrencia_sqlite.php?id=<?= urlencode($ocorrencia['id']) ?>" target="_blank" class="ml-4 inline-flex items-center px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 transition-colors text-sm" title="Baixar PDF da Ocorrência">
                        <i class="fas fa-file-pdf mr-2"></i> Baixar PDF
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><strong>ID:</strong> <?= htmlspecialchars($ocorrencia['id']) ?></div>
                    <div><strong>Status:</strong> <?= htmlspecialchars($ocorrencia['status']) ?></div>
                    <div><strong>Data:</strong> <?= htmlspecialchars($ocorrencia['data']) ?></div>
                    <div><strong>Hora Início:</strong> <?= htmlspecialchars($ocorrencia['hora_inicio']) ?></div>
                    <div><strong>Local:</strong> <?= htmlspecialchars($ocorrencia['local']) ?></div>
                    <div><strong>Natureza:</strong> <?= htmlspecialchars($ocorrencia['natureza']) ?></div>
                    <div><strong>Bairro:</strong> <?= htmlspecialchars($ocorrencia['bairro']) ?></div>
                    <div><strong>Cidade:</strong> <?= htmlspecialchars($ocorrencia['cidade']) ?></div>
                    <div><strong>Estado:</strong> <?= htmlspecialchars($ocorrencia['estado']) ?></div>
                    <div><strong>CEP:</strong> <?= htmlspecialchars($ocorrencia['cep']) ?></div>
                </div>
                <div class="mt-4">
                    <strong>Solicitante:</strong>
                    <div class="bg-gray-50 rounded p-3 mt-1 text-gray-800 whitespace-pre-line">
                        <?php if (!empty($ocorrencia['nome_solicitante'])): ?>Nome: <?= nl2br(htmlspecialchars($ocorrencia['nome_solicitante'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['nascimento_solicitante'])): ?>Nascimento: <?= nl2br(htmlspecialchars($ocorrencia['nascimento_solicitante'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['rg_solicitante'])): ?>RG: <?= nl2br(htmlspecialchars($ocorrencia['rg_solicitante'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['cpf_solicitante'])): ?>CPF: <?= nl2br(htmlspecialchars(mascaraCPF($ocorrencia['cpf_solicitante']))) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['telefone_solicitante'])): ?>Telefone: <?= nl2br(htmlspecialchars($ocorrencia['telefone_solicitante'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['endereco_solicitante'])): ?>Endereço: [OCULTO]<br><?php endif; ?>
                        <?php if (!empty($ocorrencia['bairro_solicitante'])): ?>Bairro: <?= nl2br(htmlspecialchars($ocorrencia['bairro_solicitante'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['cidade_solicitante'])): ?>Cidade: <?= nl2br(htmlspecialchars($ocorrencia['cidade_solicitante'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['estado_solicitante'])): ?>Estado: <?= nl2br(htmlspecialchars($ocorrencia['estado_solicitante'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['cep_solicitante'])): ?>CEP: <?= nl2br(htmlspecialchars($ocorrencia['cep_solicitante'])) ?><br><?php endif; ?>
                    </div>
                </div>
                <hr class="my-4 border-gray-300">
                <div class="mt-4">
                    <strong>Vítima:</strong>
                    <div class="bg-gray-50 rounded p-3 mt-1 text-gray-800 whitespace-pre-line">
                        <?php if (!empty($ocorrencia['nome_vitima'])): ?>Nome: <?= nl2br(htmlspecialchars($ocorrencia['nome_vitima'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['foto_nome_vitima'])): ?>
                            <div class="mt-2"><img src="<?= htmlspecialchars($ocorrencia['foto_nome_vitima']) ?>" alt="Foto da vítima" style="max-width:180px;max-height:180px;border-radius:8px;"></div>
                        <?php endif; ?>
                        <?php if (!empty($ocorrencia['nascimento_vitima'])): ?>Nascimento: <?= nl2br(htmlspecialchars($ocorrencia['nascimento_vitima'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['rg_vitima'])): ?>RG: <?= nl2br(htmlspecialchars($ocorrencia['rg_vitima'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['cpf_vitima'])): ?>CPF: <?= nl2br(htmlspecialchars($ocorrencia['cpf_vitima'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['telefone_vitima'])): ?>Telefone: <?= nl2br(htmlspecialchars($ocorrencia['telefone_vitima'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['endereco_vitima'])): ?>Endereço: <?= nl2br(htmlspecialchars($ocorrencia['endereco_vitima'])) ?><br><?php endif; ?>
                    </div>
                </div>
                <hr class="my-4 border-gray-300">
                <div class="mt-4">
                    <strong>Autor:</strong>
                    <div class="bg-gray-50 rounded p-3 mt-1 text-gray-800 whitespace-pre-line">
                        <?php if (!empty($ocorrencia['nome_autor'])): ?>Nome: <?= nl2br(htmlspecialchars($ocorrencia['nome_autor'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['foto_nome_autor'])): ?>
                            <div class="mt-2"><img src="<?= htmlspecialchars($ocorrencia['foto_nome_autor']) ?>" alt="Foto do autor" style="max-width:180px;max-height:180px;border-radius:8px;"></div>
                        <?php endif; ?>
                        <?php if (!empty($ocorrencia['nascimento_autor'])): ?>Nascimento: <?= nl2br(htmlspecialchars($ocorrencia['nascimento_autor'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['rg_autor'])): ?>RG: <?= nl2br(htmlspecialchars($ocorrencia['rg_autor'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['cpf_autor'])): ?>CPF: <?= nl2br(htmlspecialchars($ocorrencia['cpf_autor'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['telefone_autor'])): ?>Telefone: <?= nl2br(htmlspecialchars($ocorrencia['telefone_autor'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['endereco_autor'])): ?>Endereço: <?= nl2br(htmlspecialchars($ocorrencia['endereco_autor'])) ?><br><?php endif; ?>
                    </div>
                </div>
                <hr class="my-4 border-gray-300">
                <div class="mt-4">
                    <strong>Testemunha 1:</strong>
                    <div class="bg-gray-50 rounded p-3 mt-1 text-gray-800 whitespace-pre-line">
                        <?php if (!empty($ocorrencia['nome_testemunha1'])): ?>Nome: <?= nl2br(htmlspecialchars($ocorrencia['nome_testemunha1'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['foto_nome_testemunha1'])): ?>
                            <div class="mt-2"><img src="<?= htmlspecialchars($ocorrencia['foto_nome_testemunha1']) ?>" alt="Foto da testemunha 1" style="max-width:180px;max-height:180px;border-radius:8px;"></div>
                        <?php endif; ?>
                        <?php if (!empty($ocorrencia['rg_testemunha1'])): ?>RG: <?= nl2br(htmlspecialchars($ocorrencia['rg_testemunha1'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['cpf_testemunha1'])): ?>CPF: <?= nl2br(htmlspecialchars($ocorrencia['cpf_testemunha1'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['telefone_testemunha1'])): ?>Telefone: <?= nl2br(htmlspecialchars($ocorrencia['telefone_testemunha1'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['endereco_testemunha1'])): ?>Endereço: <?= nl2br(htmlspecialchars($ocorrencia['endereco_testemunha1'])) ?><br><?php endif; ?>
                    </div>
                </div>
                <hr class="my-4 border-gray-300">
                <div class="mt-4">
                    <strong>Testemunha 2:</strong>
                    <div class="bg-gray-50 rounded p-3 mt-1 text-gray-800 whitespace-pre-line">
                        <?php if (!empty($ocorrencia['nome_testemunha2'])): ?>Nome: <?= nl2br(htmlspecialchars($ocorrencia['nome_testemunha2'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['foto_nome_testemunha2'])): ?>
                            <div class="mt-2"><img src="<?= htmlspecialchars($ocorrencia['foto_nome_testemunha2']) ?>" alt="Foto da testemunha 2" style="max-width:180px;max-height:180px;border-radius:8px;"></div>
                        <?php endif; ?>
                        <?php if (!empty($ocorrencia['rg_testemunha2'])): ?>RG: <?= nl2br(htmlspecialchars($ocorrencia['rg_testemunha2'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['cpf_testemunha2'])): ?>CPF: <?= nl2br(htmlspecialchars($ocorrencia['cpf_testemunha2'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['telefone_testemunha2'])): ?>Telefone: <?= nl2br(htmlspecialchars($ocorrencia['telefone_testemunha2'])) ?><br><?php endif; ?>
                        <?php if (!empty($ocorrencia['endereco_testemunha2'])): ?>Endereço: <?= nl2br(htmlspecialchars($ocorrencia['endereco_testemunha2'])) ?><br><?php endif; ?>
                    </div>
                </div>
                <hr class="my-4 border-gray-300">
                <div class="mt-4">
                    <strong>Relato:</strong>
                    <?php if (!empty($ocorrencia['relato'])): ?>
                    <div class="bg-gray-50 rounded p-3 mt-1 text-gray-800 whitespace-pre-line">
                        <?= nl2br(htmlspecialchars($ocorrencia['relato'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <hr class="my-4 border-gray-300">
                <div class="mt-4">
                    <strong>Providências:</strong>
                    <?php if (!empty($ocorrencia['providencias'])): ?>
                    <div class="bg-gray-50 rounded p-3 mt-1 text-gray-800 whitespace-pre-line">
                        <?= nl2br(htmlspecialchars($ocorrencia['providencias'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <hr class="my-4 border-gray-300">
                <div class="mt-4">
                    <strong>Observações:</strong>
                    <?php if (!empty($ocorrencia['observacoes'])): ?>
                    <div class="bg-gray-50 rounded p-3 mt-1 text-gray-800 whitespace-pre-line">
                        <?= nl2br(htmlspecialchars($ocorrencia['observacoes'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <hr class="my-4 border-gray-300">
                <div class="mt-4 text-sm text-gray-500">
                    <strong>Registrada em:</strong> <?= htmlspecialchars($ocorrencia['data_registro']) ?>
                </div>
                <!-- Assinaturas -->
                <hr class="my-4 border-gray-300">
                <div class="mt-4">
                    <strong>Assinaturas:</strong>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                        <?php if (!empty($ocorrencia['assinatura_solicitante'])): ?>
                            <div>
                                <div class="font-semibold">Solicitante:</div>
                                <img src="data:image/png;base64,<?= base64_encode($ocorrencia['assinatura_solicitante']) ?>" alt="Assinatura do Solicitante" style="border:1px solid #ccc;max-width:300px;max-height:80px;">
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($ocorrencia['assinatura_vitima'])): ?>
                            <div>
                                <div class="font-semibold">Vítima:</div>
                                <img src="data:image/png;base64,<?= base64_encode($ocorrencia['assinatura_vitima']) ?>" alt="Assinatura da Vítima" style="border:1px solid #ccc;max-width:300px;max-height:80px;">
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($ocorrencia['assinatura_autor'])): ?>
                            <div>
                                <div class="font-semibold">Autor:</div>
                                <img src="data:image/png;base64,<?= base64_encode($ocorrencia['assinatura_autor']) ?>" alt="Assinatura do Autor" style="border:1px solid #ccc;max-width:300px;max-height:80px;">
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($ocorrencia['assinatura_testemunha1'])): ?>
                            <div>
                                <div class="font-semibold">Testemunha 1:</div>
                                <img src="data:image/png;base64,<?= base64_encode($ocorrencia['assinatura_testemunha1']) ?>" alt="Assinatura da Testemunha 1" style="border:1px solid #ccc;max-width:300px;max-height:80px;">
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($ocorrencia['assinatura_testemunha2'])): ?>
                            <div>
                                <div class="font-semibold">Testemunha 2:</div>
                                <img src="data:image/png;base64,<?= base64_encode($ocorrencia['assinatura_testemunha2']) ?>" alt="Assinatura da Testemunha 2" style="border:1px solid #ccc;max-width:300px;max-height:80px;">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <strong>Ocorrência não encontrada.</strong>
            </div>
        <?php endif; ?>
        <div class="mt-8">
            <a href="historico_sqlite.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar ao Histórico
            </a>
        </div>
    </div>
</body>
</html> 