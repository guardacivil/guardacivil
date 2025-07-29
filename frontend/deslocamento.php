<?php
require_once 'auth_check.php';
require_once 'config.php';

// Verificar se o usuário está logado
requireLogin();

$currentUser = getCurrentUser();
$perfil = $currentUser['perfil'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deslocamento - Sistema GCM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin-left: 16rem; }
        .container { padding: 2rem; max-width: 1400px; margin: 0 auto; }
        .header { background-color: #1e40af; color: white; padding: 2rem 1rem 1.5rem 1rem; border-radius: 0.5rem; margin-bottom: 2rem; text-align: center; }
        .header h1 { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .form-section { background: white; padding: 2rem; margin-bottom: 2rem; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-section h2 { color: #1e40af; margin-bottom: 1.5rem; text-align: center; font-size: 1.25rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        .form-row { display: flex; align-items: center; margin-bottom: 1rem; }
        .form-row label { width: 120px; font-weight: bold; color: #374151; }
        .form-row input, .form-row select { flex: 1; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; font-size: 0.875rem; }
        .form-row input:focus, .form-row select:focus { outline: none; border-color: #1e40af; box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1); }
        .table-container { overflow-x: auto; margin-top: 1rem; }
        .data-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
        .data-table th, .data-table td { border: 1px solid #d1d5db; padding: 0.25rem; text-align: center; }
        .data-table th { background-color: #f3f4f6; font-weight: bold; color: #374151; }
        .data-table input { width: 100%; border: none; text-align: center; font-size: 0.75rem; }
        .data-table input:focus { outline: none; background-color: #f0f9ff; }
        .text-area { width: 100%; min-height: 100px; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.25rem; resize: vertical; font-family: inherit; }
        .text-area:focus { outline: none; border-color: #1e40af; box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1); }
        .ronda-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .ronda-table th, .ronda-table td { border: 1px solid #d1d5db; padding: 0.5rem; text-align: left; }
        .ronda-table th { background-color: #f3f4f6; font-weight: bold; color: #374151; text-align: center; }
        .ronda-table input { width: 100%; border: none; padding: 0.25rem; }
        .ronda-table input:focus { outline: none; background-color: #f0f9ff; }
        .btn-container { text-align: center; margin-top: 2rem; }
        .btn { padding: 0.75rem 2rem; border: none; border-radius: 0.25rem; font-size: 1rem; cursor: pointer; margin: 0 0.5rem; transition: background-color 0.3s ease; }
        .btn-primary { background-color: #1e40af; color: white; }
        .btn-primary:hover { background-color: #1e3a8a; }
        .btn-secondary { background-color: #6b7280; color: white; }
        .btn-secondary:hover { background-color: #4b5563; }
        .logo-container { display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; }
        .logo { width: 60px; height: 60px; background-color: #1e40af; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin-right: 1rem; }
        .header-text { text-align: center; }
        .header-text h2 { font-size: 1.25rem; margin-bottom: 0.25rem; }
        .header-text p { font-size: 0.875rem; opacity: 0.9; }
        .section-title { background-color: #1e40af; color: white; padding: 0.5rem; text-align: center; font-weight: bold; margin-bottom: 1rem; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .full-width { grid-column: 1 / -1; }
        .data-table input[type="time"] {
            width: 80px;
            min-width: 80px;
            max-width: 80px;
        }
        .data-table input[name="datahora_ronda[]"] {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
        .ronda-table input[name="datahora_ronda[]"] {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="container">
        <div class="header" style="background: #1e40af; color: white; padding: 2rem 1rem 1.5rem 1rem; border-radius: 0.5rem; margin-bottom: 2rem; text-align: center;">
            <div style="font-size: 1.25rem; font-weight: bold; letter-spacing: 1px;">PREFEITURA DE ARAÇOIABA DA SERRA</div>
            <div style="font-size: 1.1rem; margin-top: 0.25rem;">SECRETARIA MUNICIPAL DE SEGURANÇA</div>
            <div style="font-size: 1.1rem; margin-top: 0.25rem;">GUARDA CIVIL MUNICIPAL</div>
            <hr style="border: none; border-top: 2px solid #fff; margin: 1rem auto; width: 60%; opacity: 0.5;">
            <div style="font-size: 2rem; font-weight: bold; letter-spacing: 2px; margin-top: 0.5rem;">DESLOCAMENTO</div>
        </div>

        <form method="POST" action="">
            <!-- Seção de Informações Básicas -->
            <div class="form-section">
                <h2>Informações Básicas</h2>
                <div class="grid-3">
                    <div class="form-row">
                        <label>Data:</label>
                        <input type="date" name="data" required>
                    </div>
                    <div class="form-row">
                        <label>Período:</label>
                        <select name="periodo" required>
                            <option value="">Selecione</option>
                            <option value="diurno">Diurno</option>
                            <option value="noturno">Noturno</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label>Encarregado:</label>
                        <input type="text" name="encarregado" value="<?php echo htmlspecialchars($currentUser['nome'] ?? ''); ?>" required>
                    </div>
                </div>
            </div>

            <!-- Seção de Viatura (no topo) -->
            <div class="form-section">
                <h2>Viatura</h2>
                <div class="grid-3">
                    <div class="form-row">
                        <label>VTR/Placa:</label>
                        <input type="text" name="vtr_placa_topo" placeholder="ABC-1234" required>
                    </div>
                    <div class="form-row">
                        <label>KM Início:</label>
                        <input type="number" name="km_inicio_topo" required>
                    </div>
                    <div class="form-row">
                        <label>Hora Início:</label>
                        <input type="time" name="hora_inicio_topo" required>
                    </div>
                    <div class="form-row">
                        <label>KM Final:</label>
                        <input type="number" name="km_final_topo" required>
                    </div>
                    <div class="form-row">
                        <label>Hora Final:</label>
                        <input type="time" name="hora_final_topo" required>
                    </div>
                </div>
            </div>

            <!-- Seção de Deslocamento -->
            <div class="form-section">
                <div class="section-title">DESLOCAMENTO</div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Destino</th>
                                <th>KM INICIAL</th>
                                <th>HR INICIAL</th>
                                <th>KM FINAL</th>
                                <th>HR FINAL</th>
                                <th>KM TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 0; $i < 10; $i++): ?>
                            <tr>
                                <td><input type="text" name="destino[]" placeholder="Destino"></td>
                                <td><input type="number" name="km_inicial[]" placeholder="0"></td>
                                <td><input type="time" name="hr_inicial[]"></td>
                                <td><input type="number" name="km_final[]" placeholder="0"></td>
                                <td><input type="time" name="hr_final[]"></td>
                                <td><input type="number" name="km_total[]" placeholder="0" readonly></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Seção de Equipe (antes Guarnição) -->
            <div class="form-section">
                <div class="section-title">EQUIPE</div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>MOTORISTA</th>
                                <th>AUXILIAR 1</th>
                                <th>AUXILIAR 2</th>
                                <th>AUXILIAR 3</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="text" name="motorista" placeholder="Nome do motorista"></td>
                                <td><input type="text" name="auxiliar1" placeholder="Nome do auxiliar"></td>
                                <td><input type="text" name="auxiliar2" placeholder="Nome do auxiliar"></td>
                                <td><input type="text" name="auxiliar3" placeholder="Nome do auxiliar"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Seção de Atividade Diária -->
            <div class="form-section">
                <div class="section-title">ATIVIDADE DIÁRIA</div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>RO/GCM</th>
                                <th>BO/PC</th>
                                <th>P. DETIDA</th>
                                <th>P.ABORD</th>
                                <th>ENC P.S.</th>
                                <th>AUX PÚBLICO</th>
                                <th>VEIC ABORD</th>
                                <th>VEIC APREEND</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 0; $i < 8; $i++): ?>
                            <tr>
                                <td><input type="number" name="ro_gcm[]" placeholder="0"></td>
                                <td><input type="number" name="bo_pc[]" placeholder="0"></td>
                                <td><input type="number" name="p_detida[]" placeholder="0"></td>
                                <td><input type="number" name="p_abord[]" placeholder="0"></td>
                                <td><input type="number" name="enc_ps[]" placeholder="0"></td>
                                <td><input type="number" name="aux_publico[]" placeholder="0"></td>
                                <td><input type="number" name="veic_abord[]" placeholder="0"></td>
                                <td><input type="number" name="veic_apreend[]" placeholder="0"></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Seção de Autuação -->
            <div class="form-section">
                <div class="section-title">AUTUAÇÃO</div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>AC TRÂN</th>
                                <th>C/VÍT</th>
                                <th>S/VÍT</th>
                                <th>VEIC ENVOL</th>
                                <th>AUTO</th>
                                <th>CAMINHÃO</th>
                                <th>MOTO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 0; $i < 5; $i++): ?>
                            <tr>
                                <td><input type="number" name="ac_tran[]" placeholder="0"></td>
                                <td><input type="number" name="c_vit[]" placeholder="0"></td>
                                <td><input type="number" name="s_vit[]" placeholder="0"></td>
                                <td><input type="number" name="veic_envol[]" placeholder="0"></td>
                                <td><input type="number" name="auto[]" placeholder="0"></td>
                                <td><input type="number" name="caminhao[]" placeholder="0"></td>
                                <td><input type="number" name="moto[]" placeholder="0"></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Seção de Destino e Abastecimento -->
            <div class="form-section">
                <div class="grid-2">
                    <div>
                        <div class="section-title">DESTINO</div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>TALÃO Nº</th>
                                        <th>CÓD OCORR</th>
                                        <th>KM SAÍDA</th>
                                        <th>QTR SAÍDA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for($i = 0; $i < 5; $i++): ?>
                                    <tr>
                                        <td><input type="text" name="talao[]" placeholder="Nº"></td>
                                        <td><input type="text" name="cod_ocorr[]" placeholder="Código"></td>
                                        <td><input type="number" name="km_saida[]" placeholder="0"></td>
                                        <td><input type="text" name="qtr_saida[]" placeholder="QTR"></td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div>
                        <div class="section-title">ABASTECIMENTO</div>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>QTD LITROS</th>
                                        <th>Nº REQUISIÇÃO</th>
                                        <th>KM CHEG</th>
                                        <th>QTR CHEG</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php for($i = 0; $i < 5; $i++): ?>
                                    <tr>
                                        <td><input type="number" name="qtd_litros[]" placeholder="0"></td>
                                        <td><input type="text" name="num_requisicao[]" placeholder="Nº"></td>
                                        <td><input type="number" name="km_cheg[]" placeholder="0"></td>
                                        <td><input type="text" name="qtr_cheg[]" placeholder="QTR"></td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seção de Observações -->
            <div class="form-section">
                <div class="grid-2">
                    <div>
                        <label><strong>NOVIDADES:</strong></label>
                        <textarea class="text-area" name="novidades" placeholder="Descreva as novidades do dia..."></textarea>
                    </div>
                    <div>
                        <label><strong>MOTIVO(S)/PROVIDÊNCIA(S):</strong></label>
                        <textarea class="text-area" name="motivos_providencias" placeholder="Descreva os motivos e providências tomadas..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Seção de Ronda -->
            <div class="form-section">
                <h2>RONDA</h2>
                <div class="table-container">
                    <table class="ronda-table">
                        <thead>
                            <tr>
                                <th>DATA/HORA</th>
                                <th>LOCAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php for($i = 0; $i < 20; $i++): ?>
                            <tr>
                                <td><input type="text" name="datahora_ronda[]" readonly></td>
                                <td><input type="text" name="local_ronda[]" placeholder="Local da ronda"></td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1rem;">
                    <label><strong>OBSERVAÇÕES:</strong></label>
                    <textarea class="text-area" name="observacoes_ronda" placeholder="Observações sobre as rondas realizadas..."></textarea>
                </div>
                <div style="margin-top: 1rem; text-align: center;">
                    <hr style="margin: 1rem 0;">
                    <strong>SUPERVISOR</strong>
                </div>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Deslocamento
                </button>
                <button type="button" class="btn btn-secondary" onclick="gerarPDF()">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <button type="button" class="btn btn-secondary" onclick="limparFormulario()">
                    <i class="fas fa-eraser"></i> Limpar
                </button>
            </div>
        </form>
    </div>

    <script>
        // Calcular KM total automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.data-table tbody tr');
            rows.forEach(row => {
                const kmInicial = row.querySelector('input[name="km_inicial[]"]');
                const kmFinal = row.querySelector('input[name="km_final[]"]');
                const kmTotal = row.querySelector('input[name="km_total[]"]');
                if (kmInicial && kmFinal && kmTotal) {
                    kmInicial.addEventListener('input', calcularKmTotal);
                    kmFinal.addEventListener('input', calcularKmTotal);
                    function calcularKmTotal() {
                        const inicial = parseFloat(kmInicial.value) || 0;
                        const final = parseFloat(kmFinal.value) || 0;
                        kmTotal.value = Math.max(0, final - inicial);
                    }
                }
            });
        });
        function limparFormulario() {
            if (confirm('Tem certeza que deseja limpar todo o formulário?')) {
                document.querySelector('form').reset();
            }
        }
        // Auto-preenchimento da data atual
        document.addEventListener('DOMContentLoaded', function() {
            const dataInput = document.querySelector('input[name="data"]');
            if (dataInput && !dataInput.value) {
                const hoje = new Date().toISOString().split('T')[0];
                dataInput.value = hoje;
            }
            
            // Preenchimento automático de data/hora na ronda quando o local for preenchido
            const localRondaInputs = document.querySelectorAll('input[name="local_ronda[]"]');
            localRondaInputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    const row = this.closest('tr');
                    const dataHoraInput = row.querySelector('input[name="datahora_ronda[]"]');
                    
                    if (this.value.trim() !== '') {
                        // Preencher data e hora apenas se o local não estiver vazio
                        const agora = new Date();
                        const dataHoraFormatada = agora.toLocaleDateString('pt-BR') + ' ' + agora.toLocaleTimeString('pt-BR', { 
                            hour: '2-digit', 
                            minute: '2-digit' 
                        });
                        
                        dataHoraInput.value = dataHoraFormatada;
                    } else {
                        // Limpar data e hora se o local estiver vazio
                        dataHoraInput.value = '';
                    }
                });
            });
        });
        
        function gerarPDF() {
            // Capturar dados do formulário
            const formData = new FormData(document.querySelector('form'));
            const data = {};
            for (let [key, value] of formData.entries()) {
                if (key.includes('[]')) {
                    const baseKey = key.replace('[]', '');
                    if (!data[baseKey]) data[baseKey] = [];
                    data[baseKey].push(value);
                } else {
                    data[key] = value;
                }
            }
            
            // Criar PDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Configurações de página
            const pageWidth = doc.internal.pageSize.width;
            const margin = 20;
            const contentWidth = pageWidth - (margin * 2);
            let yPosition = margin;
            
            // Cabeçalho
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.text('PREFEITURA DE ARAÇOIABA DA SERRA', pageWidth / 2, yPosition, { align: 'center' });
            yPosition += 8;
            
            doc.setFontSize(14);
            doc.text('SECRETARIA MUNICIPAL DE SEGURANÇA', pageWidth / 2, yPosition, { align: 'center' });
            yPosition += 8;
            
            doc.text('GUARDA CIVIL MUNICIPAL', pageWidth / 2, yPosition, { align: 'center' });
            yPosition += 12;
            
            doc.setFontSize(20);
            doc.text('DESLOCAMENTO', pageWidth / 2, yPosition, { align: 'center' });
            yPosition += 20;
            
            // Informações Básicas
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('INFORMAÇÕES BÁSICAS', margin, yPosition);
            yPosition += 8;
            
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(`Data: ${data.data || ''}`, margin, yPosition);
            doc.text(`Período: ${data.periodo || ''}`, margin + 60, yPosition);
            doc.text(`Encarregado: ${data.encarregado || ''}`, margin + 120, yPosition);
            yPosition += 15;
            
            // Seção Viatura
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('VIATURA', margin, yPosition);
            yPosition += 8;
            
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(`VTR/Placa: ${data.vtr_placa_topo || ''}`, margin, yPosition);
            doc.text(`KM Início: ${data.km_inicio_topo || ''}`, margin + 60, yPosition);
            doc.text(`Hora Início: ${data.hora_inicio_topo || ''}`, margin + 120, yPosition);
            yPosition += 6;
            
            doc.text(`KM Final: ${data.km_final_topo || ''}`, margin, yPosition);
            doc.text(`Hora Final: ${data.hora_final_topo || ''}`, margin + 60, yPosition);
            yPosition += 15;
            
            // Tabela de Deslocamento
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('DESLOCAMENTO', margin, yPosition);
            yPosition += 8;
            
            const deslocamentoData = [];
            const headers = ['Destino', 'KM Inicial', 'HR Inicial', 'KM Final', 'HR Final', 'KM Total'];
            
            for (let i = 0; i < 10; i++) {
                const destino = data.destino && data.destino[i] ? data.destino[i] : '';
                const kmInicial = data.km_inicial && data.km_inicial[i] ? data.km_inicial[i] : '';
                const hrInicial = data.hr_inicial && data.hr_inicial[i] ? data.hr_inicial[i] : '';
                const kmFinal = data.km_final && data.km_final[i] ? data.km_final[i] : '';
                const hrFinal = data.hr_final && data.hr_final[i] ? data.hr_final[i] : '';
                const kmTotal = data.km_total && data.km_total[i] ? data.km_total[i] : '';
                
                if (destino || kmInicial || hrInicial || kmFinal || hrFinal || kmTotal) {
                    deslocamentoData.push([destino, kmInicial, hrInicial, kmFinal, hrFinal, kmTotal]);
                }
            }
            
            if (deslocamentoData.length > 0) {
                doc.autoTable({
                    startY: yPosition,
                    head: [headers],
                    body: deslocamentoData,
                    margin: { left: margin },
                    styles: { fontSize: 8 },
                    headStyles: { fillColor: [30, 64, 175] }
                });
                yPosition = doc.lastAutoTable.finalY + 10;
            } else {
                yPosition += 10;
            }
            
            // Seção Equipe
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('EQUIPE', margin, yPosition);
            yPosition += 8;
            
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(`Motorista: ${data.motorista || ''}`, margin, yPosition);
            doc.text(`Auxiliar 1: ${data.auxiliar1 || ''}`, margin + 60, yPosition);
            yPosition += 6;
            
            doc.text(`Auxiliar 2: ${data.auxiliar2 || ''}`, margin, yPosition);
            doc.text(`Auxiliar 3: ${data.auxiliar3 || ''}`, margin + 60, yPosition);
            yPosition += 15;
            
            // Tabela de Ronda
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.text('RONDA', margin, yPosition);
            yPosition += 8;
            
            const rondaData = [];
            const rondaHeaders = ['DATA/HORA', 'LOCAL'];
            
            for (let i = 0; i < 20; i++) {
                const dataHora = data.datahora_ronda && data.datahora_ronda[i] ? data.datahora_ronda[i] : '';
                const local = data.local_ronda && data.local_ronda[i] ? data.local_ronda[i] : '';
                
                if (dataHora || local) {
                    rondaData.push([dataHora, local]);
                }
            }
            
            if (rondaData.length > 0) {
                doc.autoTable({
                    startY: yPosition,
                    head: [rondaHeaders],
                    body: rondaData,
                    margin: { left: margin },
                    styles: { fontSize: 8 },
                    headStyles: { fillColor: [30, 64, 175] }
                });
                yPosition = doc.lastAutoTable.finalY + 10;
            } else {
                yPosition += 10;
            }
            
            // Observações
            if (data.observacoes_ronda) {
                doc.setFontSize(14);
                doc.setFont('helvetica', 'bold');
                doc.text('OBSERVAÇÕES:', margin, yPosition);
                yPosition += 8;
                
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                const observacoes = data.observacoes_ronda;
                const lines = doc.splitTextToSize(observacoes, contentWidth);
                doc.text(lines, margin, yPosition);
                yPosition += (lines.length * 5) + 10;
            }
            
            // Supervisor
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.text('SUPERVISOR', pageWidth / 2, yPosition, { align: 'center' });
            yPosition += 20;
            
            // Linha para assinatura
            doc.line(margin, yPosition, pageWidth - margin, yPosition);
            
            // Rodapé
            const pageHeight = doc.internal.pageSize.height;
            doc.setFontSize(8);
            doc.setFont('helvetica', 'normal');
            doc.text(`Gerado em: ${new Date().toLocaleString('pt-BR')}`, margin, pageHeight - 15);
            doc.text(`Sistema Integrado da Guarda Civil - Araçoiaba da Serra`, pageWidth - margin, pageHeight - 15, { align: 'right' });
            
            // Salvar PDF
            const fileName = `deslocamento_${data.data || new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(fileName);
        }
    </script>
</body>
</html> 