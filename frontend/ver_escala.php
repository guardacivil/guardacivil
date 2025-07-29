<?php
require_once 'auth_check.php';
require_once 'config.php';

requireLogin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die('ID inválido!');
}

$stmt = $pdo->prepare("SELECT * FROM escalas WHERE id = ?");
$stmt->execute([$id]);
$escala = $stmt->fetch();

if (!$escala) {
    die('Escala não encontrada!');
}

$detalhes = [];
if (!empty($escala['detalhes'])) {
    $detalhes = json_decode($escala['detalhes'], true);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Ver Escala</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-5xl mx-auto bg-white rounded-lg shadow-md p-8 mt-8">
        <div class="flex justify-center mb-6">
            <img src="img/cabecalho.png" alt="Cabeçalho" class="max-h-32">
        </div>
        <h2 class="text-2xl font-bold mb-6 text-blue-900 text-center">Escala de Serviço</h2>
        <table class="min-w-full mb-6 border rounded overflow-hidden">
            <tbody>
                <tr class="bg-blue-100">
                    <td class="font-semibold px-4 py-2 w-40">Nome</td>
                    <td class="px-4 py-2"><?= htmlspecialchars($escala['nome']) ?></td>
                </tr>
                <tr class="bg-blue-50">
                    <td class="font-semibold px-4 py-2">Setor</td>
                    <td class="px-4 py-2"><?= htmlspecialchars($escala['setor_nome_livre']) ?></td>
                </tr>
                <tr class="bg-blue-100">
                    <td class="font-semibold px-4 py-2">Responsável</td>
                    <td class="px-4 py-2"><?= htmlspecialchars($escala['responsavel_nome_livre']) ?></td>
                </tr>
                <tr class="bg-blue-50">
                    <td class="font-semibold px-4 py-2">Período</td>
                    <td class="px-4 py-2"><?= date('d/m/Y', strtotime($escala['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($escala['data_fim'])) ?></td>
                </tr>
                <tr class="bg-blue-100">
                    <td class="font-semibold px-4 py-2">Turno</td>
                    <td class="px-4 py-2"><?= htmlspecialchars($escala['turno']) ?></td>
                </tr>
                <tr class="bg-blue-50">
                    <td class="font-semibold px-4 py-2">Observações</td>
                    <td class="px-4 py-2"><?= nl2br(htmlspecialchars($escala['observacoes'])) ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Equipes -->
        <h3 class="text-lg font-bold mt-8 mb-2 text-blue-800">Equipes</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <?php
            $equipes = [
                'A Diurna Impar' => ['eq_a_diurna_impar','supervisor_a','cad_a','encarregado_vtr_a','motorista_vtr_a'],
                'B Noturna Impar' => ['eq_b_noturna_impar','supervisor_b','cad_b','encarregado_vtr_b','motorista_vtr_b'],
                'C Diurna Par' => ['eq_c_diurna_par','supervisor_c','cad_c','encarregado_vtr_c','motorista_vtr_c'],
                'D Noturna Par' => ['eq_d_noturna_par','supervisor_d','cad_d','encarregado_vtr_d','motorista_vtr_d'],
            ];
            $cores = ['bg-blue-50','bg-blue-100','bg-blue-50','bg-blue-100'];
            $i = 0;
            foreach ($equipes as $nome => $campos): ?>
            <div class="border rounded p-4 <?= $cores[$i%count($cores)] ?>">
                <div class="font-semibold mb-2">Equipe <?= $nome ?></div>
                <?php foreach ($campos as $campo): ?>
                    <div><span class="font-medium capitalize"><?= ucwords(str_replace(['eq_','_'],['', ' '], $campo)) ?>:</span> <?= htmlspecialchars($detalhes[$campo] ?? '') ?></div>
                <?php endforeach; ?>
            </div>
            <?php $i++; endforeach; ?>
        </div>

        <!-- Postos Patrimoniais -->
        <h3 class="text-lg font-bold mt-8 mb-2 text-blue-800">Postos Patrimoniais (12x36)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <?php
            $postos = [
                'Paço Municipal' => 'posto_paco_municipal',
                'P.A. Central' => 'posto_pa_central',
                'Garagem' => 'posto_garagem',
                'UBS Alcides Vieira' => 'posto_ubs_alcides',
                'UBS Morro' => 'posto_ubs_morro',
                'Castelinho' => 'posto_castelinho',
                'Paço Municipal (2)' => 'posto_paco_municipal2',
                'Praça/Parques' => 'posto_praca_parques',
                'SPIM' => 'posto_spim',
                'CULT/AMB/DEMU' => 'posto_cult_amb_demu',
                'CRAS' => 'posto_cras',
            ];
            $turnos = ['diurno_impar'=>'Diurno Ímpar','noturno_impar'=>'Noturno Ímpar','diurno_par'=>'Diurno Par','noturno_par'=>'Noturno Par'];
            foreach ($postos as $label => $prefix): ?>
            <div class="border rounded p-4 bg-blue-50 mb-2">
                <div class="font-semibold mb-2"><?= $label ?></div>
                <div class="grid grid-cols-2 gap-2">
                <?php foreach ($turnos as $sufixo => $turnoLabel): ?>
                    <div class="<?= strpos($sufixo,'diurno')!==false?'bg-blue-100':'bg-blue-200' ?> rounded px-2 py-1">
                        <span class="font-medium"><?= $turnoLabel ?>:</span> <?= htmlspecialchars($detalhes[$prefix.'_'.$sufixo] ?? '') ?>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Postos Administrativos -->
        <h3 class="text-lg font-bold mt-8 mb-2 text-blue-800">Postos Administrativos (Semanal)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <?php
            $adm = [
                'Paço Municipal' => 'adm_paco_municipal',
                'Administração' => 'adm_administracao',
                'Conselho Tutelar' => 'adm_conselho_tutelar',
                'Defesa Civil' => 'adm_defesa_civil',
                'DEL.POL.' => 'adm_delpol',
                'Câmara Municipal' => 'adm_camara_municipal',
                'Museu Municipal' => 'adm_museu_municipal',
                'CAPS' => 'adm_caps',
                'ESF Jundiacanga' => 'adm_esf_jundiacanga',
            ];
            foreach ($adm as $label => $campo): ?>
            <div class="border rounded p-4 bg-blue-50 mb-2">
                <span class="font-medium"><?= $label ?>:</span> <?= htmlspecialchars($detalhes[$campo] ?? '') ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Afastamentos e Licenças Médicas -->
        <h3 class="text-lg font-bold mt-8 mb-2 text-blue-800">Afastamentos e Licenças Médicas</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <?php for($i=1;$i<=3;$i++): ?>
            <div class="border rounded p-4 bg-blue-50 mb-2">
                <span class="font-medium">Nome:</span> <?= htmlspecialchars($detalhes['afastamento_'.$i] ?? '') ?>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Férias -->
        <h3 class="text-lg font-bold mt-8 mb-2 text-blue-800">Período de Férias</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <?php for($i=1;$i<=3;$i++): ?>
            <div class="border rounded p-4 bg-blue-50 mb-2">
                <span class="font-medium">Nome:</span> <?= htmlspecialchars($detalhes['ferias_nome_'.$i] ?? '') ?><br>
                <span class="font-medium">Período:</span> <?= htmlspecialchars($detalhes['ferias_periodo_'.$i] ?? '') ?>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Avisos -->
        <div class="mt-8">
            <label class="block font-semibold mb-1">Avisos:</label>
            <div class="border rounded p-4 bg-blue-100">
                <?= nl2br(htmlspecialchars($escala['observacoes'])) ?>
            </div>
        </div>

        <a href="minhas_escalas.php" class="mt-8 inline-block text-blue-600 hover:underline">Voltar</a>
    </div>
</body>
</html> 