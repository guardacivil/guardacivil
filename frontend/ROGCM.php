<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR | E_PARSE);

if (ob_get_level()) ob_end_clean();
ob_start();
// ROGCM.php - Registro de Ocorrências da Guarda Civil Municipal (SQLite)
require_once 'auth_check.php';
require_once 'config_mysql.php';

// Adicione estes require_once e use logo após os anteriores:
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php';
require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'pdf_ocorrencia_util.php';
require_once 'enviar_email_ocorrencia.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verificar se o usuário está logado
requireLogin();

// Obter informações do usuário logado
$currentUser = getCurrentUser();

// Agora use $pdo normalmente, sem criar uma nova conexão!

$msg = '';
$error_msg = '';

// Função para salvar até 5 fotos de cada tipo
function salvarFotos($inputName, $prefix) {
    $nomes = [];
    if (!empty($_FILES[$inputName]['name'][0])) {
        for ($i = 0; $i < count($_FILES[$inputName]['name']) && $i < 5; $i++) {
            if ($_FILES[$inputName]['error'][$i] === 0) {
                $ext = pathinfo($_FILES[$inputName]['name'][$i], PATHINFO_EXTENSION);
                $nomeArquivo = uniqid($prefix . '_') . '.' . $ext;
                move_uploaded_file($_FILES[$inputName]['tmp_name'][$i], __DIR__ . '/../uploads/' . $nomeArquivo);
                $nomes[] = $nomeArquivo;
            } else {
                $nomes[] = null;
            }
        }
    }
    while (count($nomes) < 5) $nomes[] = null;
    return $nomes;
}

// Função para mascarar CPF
function mascararCPF($cpf) {
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) return $cpf;
    return substr($cpf, 0, 2) . '.***.**' . substr($cpf, 6, 1) . '-' . substr($cpf, 8, 1) . substr($cpf, 9, 1);
}
// Função para mascarar RG
function mascararRG($rg) {
    $rg = preg_replace('/\D/', '', $rg);
    if (strlen($rg) < 5) return $rg;
    return substr($rg, 0, 2) . '.***.**' . substr($rg, -2);
}

// Processar formulário de ocorrência
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['natureza'])) {
    file_put_contents('debug_ro.txt', 'POST: ' . print_r($_POST, true));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        file_put_contents('debug_ro.txt', print_r($_POST, true), FILE_APPEND);
    }
    $debug = "Início do processamento POST<br>";
    $natureza = $_POST['natureza'];
    $data_fato = $_POST['data_fato'] ?? '';
    $hora_fato = $_POST['hora_fato'] ?? '';
    $local_fato = $_POST['local_fato'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? 'Araçoiaba da Serra';
    $estado = $_POST['estado'] ?? 'SP';
    $cep = $_POST['cep'] ?? '';
    
    // Adicionar campo do tipo de solicitante
    $solicitante_tipo = $_POST['solicitante_tipo'] ?? '';
    
    // Dados do solicitante
    $nome_solicitante = $_POST['nome_solicitante'] ?? '';
    $nascimento_solicitante = $_POST['nascimento_solicitante'] ?? '';
    $rg_solicitante = $_POST['rg_solicitante'] ?? '';
    $cpf_solicitante = $_POST['cpf_solicitante'] ?? '';
    $telefone_solicitante = $_POST['telefone_solicitante'] ?? '';
    $endereco_solicitante = $_POST['endereco_solicitante'] ?? '';
    $bairro_solicitante = $_POST['bairro_solicitante'] ?? '';
    $cidade_solicitante = $_POST['cidade_solicitante'] ?? '';
    $estado_solicitante = $_POST['estado_solicitante'] ?? '';
    $cep_solicitante = $_POST['cep_solicitante'] ?? '';
    
    // Dados da ocorrência
    $relato = $_POST['relato'] ?? '';
    
    // Dados da vítima
    $nome_vitima = $_POST['nome_vitima'] ?? '';
    $nascimento_vitima = $_POST['nascimento_vitima'] ?? '';
    $rg_vitima = $_POST['rg_vitima'] ?? '';
    $cpf_vitima = $_POST['cpf_vitima'] ?? '';
    $telefone_vitima = $_POST['telefone_vitima'] ?? '';
    $endereco_vitima = $_POST['endereco_vitima'] ?? '';
    
    // Dados do autor
    $nome_autor = $_POST['nome_autor'] ?? '';
    $nascimento_autor = $_POST['nascimento_autor'] ?? '';
    $rg_autor = $_POST['rg_autor'] ?? '';
    $cpf_autor = $_POST['cpf_autor'] ?? '';
    $telefone_autor = $_POST['telefone_autor'] ?? '';
    $endereco_autor = $_POST['endereco_autor'] ?? '';
    
    // Dados das testemunhas
    $nome_testemunha1 = $_POST['nome_testemunha1'] ?? '';
    $rg_testemunha1 = $_POST['rg_testemunha1'] ?? '';
    $cpf_testemunha1 = $_POST['cpf_testemunha1'] ?? '';
    $telefone_testemunha1 = $_POST['telefone_testemunha1'] ?? '';
    $endereco_testemunha1 = $_POST['endereco_testemunha1'] ?? '';
    
    $nome_testemunha2 = $_POST['nome_testemunha2'] ?? '';
    $rg_testemunha2 = $_POST['rg_testemunha2'] ?? '';
    $cpf_testemunha2 = $_POST['cpf_testemunha2'] ?? '';
    $telefone_testemunha2 = $_POST['telefone_testemunha2'] ?? '';
    $endereco_testemunha2 = $_POST['endereco_testemunha2'] ?? '';
    
    $providencias = $_POST['providencias'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    
    // Função para processar assinaturas base64
    function processarAssinatura($assinatura_base64) {
        if (empty($assinatura_base64)) {
            return null;
        }
        
        // Remover cabeçalho data:image/png;base64, se existir
        $assinatura_base64 = preg_replace('/^data:image\/[a-z]+;base64,/', '', $assinatura_base64);
        
        // Decodificar base64 para dados binários
        $assinatura_binaria = base64_decode($assinatura_base64);
        
        if ($assinatura_binaria === false) {
            error_log("Erro ao decodificar assinatura base64");
            return null;
        }
        
        // Limpar perfil iCCP se for PNG e ImageMagick estiver disponível
        $tempFile = tempnam(sys_get_temp_dir(), 'assinatura_') . '.png';
        file_put_contents($tempFile, $assinatura_binaria);
        // Tenta rodar o magick mogrify
        if (file_exists($tempFile)) {
            $cmd = 'magick mogrify -strip ' . escapeshellarg($tempFile);
            @exec($cmd);
            $assinatura_binaria = file_get_contents($tempFile);
            @unlink($tempFile);
        }
        return $assinatura_binaria;
    }
    
    // Processar assinaturas (converter base64 para dados binários)
    $assinatura_solicitante = processarAssinatura($_POST['assinatura_solicitante_img'] ?? null);
    $assinatura_vitima = processarAssinatura($_POST['assinatura_vitima_img'] ?? null);
    $assinatura_autor = processarAssinatura($_POST['assinatura_autor_img'] ?? null);
    $assinatura_testemunha1 = processarAssinatura($_POST['assinatura_testemunha1_img'] ?? null);
    $assinatura_testemunha2 = processarAssinatura($_POST['assinatura_testemunha2_img'] ?? null);
    
    // Gerar número sequencial de ocorrência com sufixo do ano
    $anoAtual = date('Y');
    $stmtNum = $pdo->prepare("SELECT numero_ocorrencia FROM ocorrencias WHERE numero_ocorrencia LIKE ? ORDER BY numero_ocorrencia DESC LIMIT 1");
    $stmtNum->execute(['%/' . $anoAtual]);
    $ultimo = $stmtNum->fetchColumn();
    if ($ultimo && preg_match('/(\d{4})\/' . $anoAtual . '/', $ultimo, $m)) {
        $seq = str_pad(((int)$m[1]) + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $seq = '0001';
    }
    $numero_ocorrencia = $seq . '/' . $anoAtual;

    // Garantir preenchimento dos campos principais
    $data = $data_fato ?: date('Y-m-d');
    $hora_inicio = $hora_fato ?: date('H:i:s');
    $local = $local_fato ?: '';

    // NOVO: Usar hora_registro_aparelho se existir
    $data_registro = $_POST['hora_registro_aparelho'] ?? date('Y-m-d H:i:s');

    // No início do processamento do POST, logo após requireLogin() e antes do try/catch:
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fotos_vitima = salvarFotos('fotos_vitima', 'vitima');
        $fotos_autor = salvarFotos('fotos_autor', 'autor');
        $fotos_testemunha1 = salvarFotos('fotos_testemunha1', 'testemunha1');
        $fotos_testemunha2 = salvarFotos('fotos_testemunha2', 'testemunha2');
    }

    // Após processar assinaturas e coletar dados do formulário:
    try {
        // 1. Salvar ocorrência no banco
        $stmt = $pdo->prepare("INSERT INTO ocorrencias (
            data, hora_inicio, local, natureza, data_fato, hora_fato, local_fato, bairro, cidade, estado, cep,
            solicitante_tipo,
            nome_solicitante, nascimento_solicitante, rg_solicitante, cpf_solicitante, telefone_solicitante, endereco_solicitante, bairro_solicitante, cidade_solicitante, estado_solicitante, cep_solicitante,
            relato, nome_vitima, nascimento_vitima, rg_vitima, cpf_vitima, telefone_vitima, endereco_vitima,
            nome_autor, nascimento_autor, rg_autor, cpf_autor, telefone_autor, endereco_autor,
            nome_testemunha1, rg_testemunha1, cpf_testemunha1, telefone_testemunha1, endereco_testemunha1,
            nome_testemunha2, rg_testemunha2, cpf_testemunha2, telefone_testemunha2, endereco_testemunha2,
            providencias, observacoes, assinatura_solicitante, assinatura_vitima, assinatura_autor, assinatura_testemunha1, assinatura_testemunha2, numero_ocorrencia,
            usuario_id,
            data_registro,
            foto_vitima_1, foto_vitima_2, foto_vitima_3, foto_vitima_4, foto_vitima_5,
            foto_autor_1, foto_autor_2, foto_autor_3, foto_autor_4, foto_autor_5,
            foto_testemunha1_1, foto_testemunha1_2, foto_testemunha1_3, foto_testemunha1_4, foto_testemunha1_5,
            foto_testemunha2_1, foto_testemunha2_2, foto_testemunha2_3, foto_testemunha2_4, foto_testemunha2_5
        ) VALUES (
            :data, :hora_inicio, :local, :natureza, :data_fato, :hora_fato, :local_fato, :bairro, :cidade, :estado, :cep,
            :solicitante_tipo,
            :nome_solicitante, :nascimento_solicitante, :rg_solicitante, :cpf_solicitante, :telefone_solicitante, :endereco_solicitante, :bairro_solicitante, :cidade_solicitante, :estado_solicitante, :cep_solicitante,
            :relato, :nome_vitima, :nascimento_vitima, :rg_vitima, :cpf_vitima, :telefone_vitima, :endereco_vitima,
            :nome_autor, :nascimento_autor, :rg_autor, :cpf_autor, :telefone_autor, :endereco_autor,
            :nome_testemunha1, :rg_testemunha1, :cpf_testemunha1, :telefone_testemunha1, :endereco_testemunha1,
            :nome_testemunha2, :rg_testemunha2, :cpf_testemunha2, :telefone_testemunha2, :endereco_testemunha2,
            :providencias, :observacoes, :assinatura_solicitante, :assinatura_vitima, :assinatura_autor, :assinatura_testemunha1, :assinatura_testemunha2, :numero_ocorrencia,
            :usuario_id,
            :data_registro,
            :foto_vitima_1, :foto_vitima_2, :foto_vitima_3, :foto_vitima_4, :foto_vitima_5,
            :foto_autor_1, :foto_autor_2, :foto_autor_3, :foto_autor_4, :foto_autor_5,
            :foto_testemunha1_1, :foto_testemunha1_2, :foto_testemunha1_3, :foto_testemunha1_4, :foto_testemunha1_5,
            :foto_testemunha2_1, :foto_testemunha2_2, :foto_testemunha2_3, :foto_testemunha2_4, :foto_testemunha2_5
        )");
        $stmt->execute([
            ':data' => $data,
            ':hora_inicio' => $hora_inicio,
            ':local' => $local,
            ':natureza' => $natureza,
            ':data_fato' => $data_fato,
            ':hora_fato' => $hora_fato,
            ':local_fato' => $local_fato,
            ':bairro' => $bairro,
            ':cidade' => $cidade,
            ':estado' => $estado,
            ':cep' => $cep,
            ':solicitante_tipo' => $solicitante_tipo,
            ':nome_solicitante' => $nome_solicitante,
            ':nascimento_solicitante' => $nascimento_solicitante,
            ':rg_solicitante' => $rg_solicitante,
            ':cpf_solicitante' => $cpf_solicitante,
            ':telefone_solicitante' => $telefone_solicitante,
            ':endereco_solicitante' => $endereco_solicitante,
            ':bairro_solicitante' => $bairro_solicitante,
            ':cidade_solicitante' => $cidade_solicitante,
            ':estado_solicitante' => $estado_solicitante,
            ':cep_solicitante' => $cep_solicitante,
            ':relato' => $relato,
            ':nome_vitima' => $nome_vitima,
            ':nascimento_vitima' => $nascimento_vitima,
            ':rg_vitima' => $rg_vitima,
            ':cpf_vitima' => $cpf_vitima,
            ':telefone_vitima' => $telefone_vitima,
            ':endereco_vitima' => $endereco_vitima,
            ':nome_autor' => $nome_autor,
            ':nascimento_autor' => $nascimento_autor,
            ':rg_autor' => $rg_autor,
            ':cpf_autor' => $cpf_autor,
            ':telefone_autor' => $telefone_autor,
            ':endereco_autor' => $endereco_autor,
            ':nome_testemunha1' => $nome_testemunha1,
            ':rg_testemunha1' => $rg_testemunha1,
            ':cpf_testemunha1' => $cpf_testemunha1,
            ':telefone_testemunha1' => $telefone_testemunha1,
            ':endereco_testemunha1' => $endereco_testemunha1,
            ':nome_testemunha2' => $nome_testemunha2,
            ':rg_testemunha2' => $rg_testemunha2,
            ':cpf_testemunha2' => $cpf_testemunha2,
            ':telefone_testemunha2' => $telefone_testemunha2,
            ':endereco_testemunha2' => $endereco_testemunha2,
            ':providencias' => $providencias,
            ':observacoes' => $observacoes,
            ':assinatura_solicitante' => $assinatura_solicitante,
            ':assinatura_vitima' => $assinatura_vitima,
            ':assinatura_autor' => $assinatura_autor,
            ':assinatura_testemunha1' => $assinatura_testemunha1,
            ':assinatura_testemunha2' => $assinatura_testemunha2,
            ':numero_ocorrencia' => $numero_ocorrencia,
            ':usuario_id' => $currentUser['id'],
            ':data_registro' => $data_registro,
            ':foto_vitima_1' => $fotos_vitima[0],
            ':foto_vitima_2' => $fotos_vitima[1],
            ':foto_vitima_3' => $fotos_vitima[2],
            ':foto_vitima_4' => $fotos_vitima[3],
            ':foto_vitima_5' => $fotos_vitima[4],
            ':foto_autor_1' => $fotos_autor[0],
            ':foto_autor_2' => $fotos_autor[1],
            ':foto_autor_3' => $fotos_autor[2],
            ':foto_autor_4' => $fotos_autor[3],
            ':foto_autor_5' => $fotos_autor[4],
            ':foto_testemunha1_1' => $fotos_testemunha1[0],
            ':foto_testemunha1_2' => $fotos_testemunha1[1],
            ':foto_testemunha1_3' => $fotos_testemunha1[2],
            ':foto_testemunha1_4' => $fotos_testemunha1[3],
            ':foto_testemunha1_5' => $fotos_testemunha1[4],
            ':foto_testemunha2_1' => $fotos_testemunha2[0],
            ':foto_testemunha2_2' => $fotos_testemunha2[1],
            ':foto_testemunha2_3' => $fotos_testemunha2[2],
            ':foto_testemunha2_4' => $fotos_testemunha2[3],
            ':foto_testemunha2_5' => $fotos_testemunha2[4]
        ]);
        $ocorrencia_id = $pdo->lastInsertId();

        // 2. Gerar PDF da ocorrência
        $pdf_path = __DIR__ . "/../pdfs/ocorrencia_{$ocorrencia_id}.pdf";
        file_put_contents('debug_pdf.txt', "Tentando gerar PDF em: $pdf_path\n", FILE_APPEND);
        $erro_pdf = null;
        $pdf_gerado = gerarPdfOcorrencia($ocorrencia_id, $pdo, $erro_pdf, $pdf_path);
        file_put_contents('debug_pdf.txt', "Retorno gerarPdfOcorrencia: ".var_export($pdf_gerado, true)."\nErro: ".$erro_pdf."\n", FILE_APPEND);
        if (!file_exists($pdf_path)) {
            file_put_contents('debug_pdf.txt', "Arquivo PDF não encontrado em: $pdf_path\n", FILE_APPEND);
        }
        if (!$pdf_gerado || !file_exists($pdf_path)) {
            $error_msg = 'Erro ao gerar PDF: ' . ($erro_pdf ?? 'desconhecido');
        }

        // 3. Enviar e-mail com PDF em anexo
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'sec.segurancagc@gmail.com';
        $mail->Password = 'fnpofsgewuoqzaju';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom('sec.segurancagc@gmail.com', 'Sistema SMART - GCM');
        $mail->addAddress('sec.segurancagc@gmail.com', 'Secretaria de Segurança');
        $mail->Subject = 'Nova Ocorrência Registrada';
        $mail->Body = 'Uma nova ocorrência foi registrada no sistema. PDF em anexo.';
        $mail->addAttachment($pdf_path);
        $mail->isHTML(false);
        $mail->send();

        $msg = 'Ocorrência registrada, PDF gerado e e-mail enviado com sucesso!';
    } catch (Exception $e) {
        $error_msg = 'Erro ao registrar ocorrência: ' . $e->getMessage();
    }
    $debug .= "Fim do processamento ROGCM.php<br>";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro de Ocorrência - Sistema Integrado da Guarda Civil (SQLite)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #1e40af;
            --accent-color: #3b82f6;
            --dark-color: #1e1e2d;
            --light-color: #f8fafc;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2563eb 0%, #000 100%);
            min-height: 100vh;
            margin: 0;
        }
        .bo-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border: none;
        }
        .bo-title {
            text-align: center;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 30px;
            text-transform: uppercase;
            color: #1e3a8a;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .bo-section {
            margin-bottom: 30px;
            background: #f8fafc;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .bo-section-title {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 20px;
            text-transform: uppercase;
            color: #1e3a8a;
            letter-spacing: 1px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
        }
        .bo-section-title::before {
            content: '';
            width: 8px;
            height: 20px;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            border-radius: 4px;
            margin-right: 12px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-field {
            display: flex;
            flex-direction: column;
        }
        .form-field label {
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 13px;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-field input, .form-field select, .form-field textarea {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            background: white;
        }
        .form-field input:focus, .form-field select:focus, .form-field textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }
        .form-field textarea {
            resize: vertical;
            min-height: 80px;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #9ca3af);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
        }
        .btn-image {
            background: linear-gradient(135deg, #10b981, #34d399);
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
            margin-left: 10px;
        }
        .btn-image:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        .field-with-image {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .field-with-image input {
            flex: 1;
        }
        .image-preview {
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
            margin-top: 5px;
            display: none;
        }
        .sidebar {
            width: 16rem;
            background-color: #1e40af;
            color: white;
            height: 100vh;
            padding: 1.25rem;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            box-shadow: 2px 0 12px rgba(0,0,0,0.2);
            z-index: 30;
        }
        /* Sidebar para Guarda Civil */
        .sidebar-guarda {
            width: 16rem;
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            color: white;
            height: 100vh;
            padding: 1.25rem;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            box-shadow: 2px 0 12px rgba(0,0,0,0.2);
            z-index: 30;
        }
        .sidebar-guarda nav a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .sidebar-guarda nav a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .sidebar-guarda nav a.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #fbbf24;
        }
        .sidebar-guarda nav a i {
            margin-right: 0.75rem;
            width: 1.25rem;
            text-align: center;
        }
        .sidebar-guarda nav a.logout {
            background-color: #dc2626;
            margin-top: 2rem;
        }
        .sidebar-guarda nav a.logout:hover {
            background-color: #b91c1c;
        }
        .content {
            margin-left: 16rem;
            padding: 1rem;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .logo-container img {
            width: 10.14rem;
            margin: 0 auto 0.5rem auto;
            display: block;
        }
        .logo-container h1 {
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        .logo-container p {
            font-size: 0.875rem;
            color: #bfdbfe;
            margin: 0;
        }
        .sidebar nav a {
            display: block;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .sidebar nav a:hover {
            background-color: #2563eb;
        }
        .sidebar nav a.logout {
            background-color: #dc2626;
        }
        .sidebar nav a.logout:hover {
            background-color: #b91c1c;
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <main class="content">
        <div class="bo-header">
            <div class="bo-title">
                REGISTRO DE OCORRÊNCIA - SQLite
            </div>

            <?php if (isset($msg)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($msg) ?>
                </div>
                <?php if (!empty($debug)): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                        <pre><?= htmlspecialchars($debug) ?></pre>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (isset($error_msg)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6" enctype="multipart/form-data">
                <input type="hidden" name="hora_registro_aparelho" id="hora_registro_aparelho">
                
                <!-- DADOS DO FATO -->
                <div class="bo-section">
                    <div class="bo-section-title">DADOS DO FATO</div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="solicitante_tipo">Solicitante:</label>
                            <select name="solicitante_tipo" id="solicitante_tipo" required>
                                <option value="">Selecione...</option>
                                <option value="CAD">CAD</option>
                                <option value="deparou-se">Deparou-se</option>
                                <option value="anonimo">Anônimo</option>
                                <option value="municipe">Munícipe</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                        <div class="form-field" style="min-width:220px;">
                            <label>NATUREZA:</label>
                            <select name="natureza" required>
                                <option value="">Selecione...</option>
                                <option value="Furto">Furto</option>
                                <option value="Roubo">Roubo</option>
                                <option value="Furto Qualificado">Furto Qualificado</option>
                                <option value="Roubo Qualificado">Roubo Qualificado</option>
                                <option value="Estelionato">Estelionato</option>
                                <option value="Ameaça">Ameaça</option>
                                <option value="Lesão Corporal">Lesão Corporal</option>
                                <option value="Vias de Fato">Vias de Fato</option>
                                <option value="Perturbação da Paz">Perturbação da Paz</option>
                                <option value="Desacato">Desacato</option>
                                <option value="Desobediência">Desobediência</option>
                                <option value="Trânsito">Trânsito</option>
                                <option value="Ambiental">Ambiental</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                        <div class="form-field" style="min-width:160px;">
                            <label for="codigo_ocorrencia">Código da Ocorrência:</label>
                            <input type="text" name="codigo_ocorrencia" id="codigo_ocorrencia" maxlength="5" placeholder="Ex: 12345">
                        </div>
                        <div class="form-field">
                            <label>DATA DO FATO:</label>
                            <input type="date" name="data_fato" required>
                        </div>
                        <div class="form-field">
                            <label>HORA DO FATO:</label>
                            <input type="time" name="hora_fato" required>
                        </div>
                        <div class="form-field full-width">
                            <label>LOCAL DO FATO:</label>
                            <input type="text" name="local_fato" required placeholder="Endereço completo">
                        </div>
                        <div class="form-field">
                            <label>BAIRRO:</label>
                            <input type="text" name="bairro" required>
                        </div>
                        <div class="form-field">
                            <label>CIDADE:</label>
                            <input type="text" name="cidade" value="Araçoiaba da Serra" required>
                        </div>
                        <div class="form-field">
                            <label>ESTADO:</label>
                            <input type="text" name="estado" value="SP" required>
                        </div>
                        <div class="form-field">
                            <label>CEP:</label>
                            <input type="text" name="cep" placeholder="00000-000">
                        </div>
                        <div class="form-field full-width">
                            <label>EQUIPE:</label>
                            <input type="text" name="equipe" placeholder="Informe a equipe responsável pelo atendimento">
                        </div>
                    </div>
                </div>

                <!-- DADOS DO SOLICITANTE -->
                <div class="bo-section" id="dados-solicitante-section" style="display:none;">
                    <div class="bo-section-title">DADOS DO SOLICITANTE</div>
                    <div class="form-grid">
                        <div class="form-field full-width">
                            <label>NOME COMPLETO:</label>
                            <input type="text" name="nome_solicitante">
                        </div>
                        <div class="form-field">
                            <label>DATA DE NASCIMENTO:</label>
                            <input type="date" name="nascimento_solicitante">
                        </div>
                        <div class="form-field">
                            <label>RG:</label>
                            <input type="text" name="rg_solicitante" placeholder="00.000.000-0">
                        </div>
                        <div class="form-field">
                            <label>CPF:</label>
                            <input type="text" name="cpf_solicitante" placeholder="000.000.000-00">
                        </div>
                        <div class="form-field">
                            <label>TELEFONE:</label>
                            <input type="text" name="telefone_solicitante" placeholder="(00) 00000-0000">
                        </div>
                        <div class="form-field full-width">
                            <label>ENDEREÇO:</label>
                            <input type="text" name="endereco_solicitante" placeholder="Rua, número, complemento">
                        </div>
                        <div class="form-field">
                            <label>BAIRRO:</label>
                            <input type="text" name="bairro_solicitante">
                        </div>
                        <div class="form-field">
                            <label>CIDADE:</label>
                            <input type="text" name="cidade_solicitante" value="Araçoiaba da Serra">
                        </div>
                        <div class="form-field">
                            <label>ESTADO:</label>
                            <input type="text" name="estado_solicitante" value="SP">
                        </div>
                        <div class="form-field">
                            <label>CEP:</label>
                            <input type="text" name="cep_solicitante" placeholder="00000-000">
                        </div>
                        <div class="form-field full-width">
                            <label>ASSINATURA DO SOLICITANTE:</label>
                            <canvas id="assinatura_solicitante" width="900" height="180" style="border:1px solid #ccc; width:900px; height:180px;"></canvas>
                            <input type="hidden" name="assinatura_solicitante_img" id="assinatura_solicitante_img">
                            <button type="button" onclick="limparAssinatura('assinatura_solicitante')">Limpar</button>
                        </div>
                    </div>
                </div>

                <!-- RELATO -->
                <div class="bo-section">
                    <div class="bo-section-title">RELATO</div>
                    <div class="form-field full-width">
                        <label>DESCREVA DETALHADAMENTE OS FATOS:</label>
                        <textarea name="relato" required rows="8" placeholder="Descreva como aconteceu o fato, incluindo todos os detalhes relevantes..."></textarea>
                    </div>
                </div>

                <!-- VÍTIMA -->
                <div class="bo-section">
                    <div class="bo-section-title">VÍTIMA</div>
                    <div class="form-grid">
                        <div class="form-field full-width">
                            <label>FOTOS DA VÍTIMA (até 5):</label>
                            <input type="file" name="fotos_vitima[]" id="fotos_vitima" accept="image/*" multiple>
                        </div>
                        <div class="form-field full-width">
                            <label>NOME COMPLETO:</label>
                            <input type="text" name="nome_vitima" placeholder="Nome completo da vítima">
                            <img id="nome_vitima_preview" class="image-preview" alt="Preview" style="display:none">
                        </div>
                        <div class="form-field">
                            <label>DATA DE NASCIMENTO:</label>
                            <input type="date" name="nascimento_vitima">
                        </div>
                        <div class="form-field">
                            <label>RG:</label>
                            <input type="text" name="rg_vitima" placeholder="00.000.000-0">
                        </div>
                        <div class="form-field">
                            <label>CPF:</label>
                            <input type="text" name="cpf_vitima" placeholder="000.000.000-00">
                        </div>
                        <div class="form-field">
                            <label>TELEFONE:</label>
                            <input type="text" name="telefone_vitima" placeholder="(00) 00000-0000">
                        </div>
                        <div class="form-field full-width">
                            <label>ENDEREÇO:</label>
                            <input type="text" name="endereco_vitima" placeholder="Endereço completo da vítima">
                        </div>
                        <div class="form-field full-width">
                            <label>Assinatura da Vítima:</label>
                            <canvas id="assinatura_vitima" width="900" height="180" style="border:1px solid #ccc; width:900px; height:180px;"></canvas>
                            <input type="hidden" name="assinatura_vitima_img" id="assinatura_vitima_img">
                            <button type="button" onclick="limparAssinatura('assinatura_vitima')">Limpar</button>
                        </div>
                    </div>
                </div>

                <!-- AUTOR -->
                <div class="bo-section">
                    <div class="bo-section-title">AUTOR</div>
                    <div class="form-grid">
                        <div class="form-field full-width">
                            <label>FOTOS DO AUTOR (até 5):</label>
                            <input type="file" name="fotos_autor[]" id="fotos_autor" accept="image/*" multiple>
                        </div>
                        <div class="form-field full-width">
                            <label>NOME COMPLETO:</label>
                            <input type="text" name="nome_autor" placeholder="Nome completo do autor">
                            <img id="nome_autor_preview" class="image-preview" alt="Preview" style="display:none">
                        </div>
                        <div class="form-field">
                            <label>DATA DE NASCIMENTO:</label>
                            <input type="date" name="nascimento_autor">
                        </div>
                        <div class="form-field">
                            <label>RG:</label>
                            <input type="text" name="rg_autor" placeholder="00.000.000-0">
                        </div>
                        <div class="form-field">
                            <label>CPF:</label>
                            <input type="text" name="cpf_autor" placeholder="000.000.000-00">
                        </div>
                        <div class="form-field">
                            <label>TELEFONE:</label>
                            <input type="text" name="telefone_autor" placeholder="(00) 00000-0000">
                        </div>
                        <div class="form-field full-width">
                            <label>ENDEREÇO:</label>
                            <input type="text" name="endereco_autor" placeholder="Endereço completo do autor">
                        </div>
                        <div class="form-field full-width">
                            <label>Assinatura do Autor:</label>
                            <canvas id="assinatura_autor" width="900" height="180" style="border:1px solid #ccc; width:900px; height:180px;"></canvas>
                            <input type="hidden" name="assinatura_autor_img" id="assinatura_autor_img">
                            <button type="button" onclick="limparAssinatura('assinatura_autor')">Limpar</button>
                        </div>
                    </div>
                </div>

                <!-- Seção: Testemunhas -->
                <div class="bo-section">
                    <h2 class="bo-section-title">
                        <i class="fas fa-users mr-2"></i>
                        Testemunhas
                    </h2>
                    <div id="testemunhas-container"></div>
                    <button type="button" class="btn-primary" id="add-testemunha-btn">Adicionar Testemunha</button>
                </div>
                <script>
                    let testemunhaCount = 0;
                    const maxTestemunhas = 2;
                    document.addEventListener('DOMContentLoaded', function() {
                        document.getElementById('add-testemunha-btn').addEventListener('click', function() {
                            if (testemunhaCount < maxTestemunhas) {
                                testemunhaCount++;
                                const container = document.getElementById('testemunhas-container');
                                const div = document.createElement('div');
                                div.className = 'bo-section';
                                div.innerHTML = `
                                    <h3 class=\"text-lg font-semibold mb-4 text-blue-800\">Testemunha ${testemunhaCount}</h3>
                                    <button type=\"button\" class=\"btn-secondary mb-2\" onclick=\"removerTestemunha(this)\">Remover Testemunha</button>
                                    <div class=\"form-grid\">
                                        <div class=\"form-field\">
                                            <label for=\"nome_testemunha${testemunhaCount}\">Nome</label>
                                            <input type=\"text\" id=\"nome_testemunha${testemunhaCount}\" name=\"nome_testemunha${testemunhaCount}\">
                                        </div>
                                        <div class=\"form-field\">
                                            <label for=\"rg_testemunha${testemunhaCount}\">RG</label>
                                            <input type=\"text\" id=\"rg_testemunha${testemunhaCount}\" name=\"rg_testemunha${testemunhaCount}\">
                                        </div>
                                        <div class=\"form-field\">
                                            <label for=\"cpf_testemunha${testemunhaCount}\">CPF</label>
                                            <input type=\"text\" id=\"cpf_testemunha${testemunhaCount}\" name=\"cpf_testemunha${testemunhaCount}\">
                                        </div>
                                        <div class=\"form-field\">
                                            <label for=\"telefone_testemunha${testemunhaCount}\">Telefone</label>
                                            <input type=\"text\" id=\"telefone_testemunha${testemunhaCount}\" name=\"telefone_testemunha${testemunhaCount}\">
                                        </div>
                                        <div class=\"form-field\">
                                            <label for=\"endereco_testemunha${testemunhaCount}\">Endereço</label>
                                            <input type=\"text\" id=\"endereco_testemunha${testemunhaCount}\" name=\"endereco_testemunha${testemunhaCount}\">
                                        </div>
                                        <div class=\"form-field\">
                                            <label for=\"foto_nome_testemunha${testemunhaCount}\">Foto da Testemunha</label>
                                            <input type=\"file\" id=\"foto_nome_testemunha${testemunhaCount}\" name=\"foto_nome_testemunha${testemunhaCount}\" accept=\"image/*\">
                                        </div>
                                        <div class=\"form-field full-width\">
                                            <label for=\"relato_testemunha${testemunhaCount}\">Relato da Testemunha</label>
                                            <textarea id=\"relato_testemunha${testemunhaCount}\" name=\"relato_testemunha${testemunhaCount}\" rows=\"3\" placeholder=\"Descreva o relato da testemunha...\"></textarea>
                                        </div>
                                    </div>
                                    <div class=\"form-field full-width\">
                                        <label>Assinatura da Testemunha ${testemunhaCount}</label>
                                        <canvas id=\"assinatura_testemunha${testemunhaCount}\" width=\"900\" height=\"180\" style=\"border:1px solid #ccc; width:900px; height:180px;\"></canvas>
                                        <input type=\"hidden\" name=\"assinatura_testemunha${testemunhaCount}_img\" id=\"assinatura_testemunha${testemunhaCount}_img\">
                                        <button type=\"button\" onclick=\"limparAssinatura('assinatura_testemunha${testemunhaCount}')\">Limpar</button>
                                    </div>
                                `;
                                container.appendChild(div);
                                setupAssinatura(`assinatura_testemunha${testemunhaCount}`, `assinatura_testemunha${testemunhaCount}_img`);
                                if (testemunhaCount >= maxTestemunhas) {
                                    document.getElementById('add-testemunha-btn').style.display = 'none';
                                }
                            }
                        });
                    });
                </script>

                <!-- PROVIDÊNCIAS -->
                <div class="bo-section">
                    <div class="bo-section-title">PROVIDÊNCIAS TOMADAS</div>
                    <div class="form-field full-width">
                        <label>DESCREVA AS PROVIDÊNCIAS TOMADAS:</label>
                        <textarea name="providencias" rows="4" placeholder="Ações realizadas pela GCM, encaminhamentos, etc..."></textarea>
                    </div>
                </div>

                <!-- OBSERVAÇÕES -->
                <div class="bo-section">
                    <div class="bo-section-title">OBSERVAÇÕES</div>
                    <div class="form-field full-width">
                        <label>OBSERVAÇÕES ADICIONAIS:</label>
                        <textarea name="observacoes" rows="4" placeholder="Informações complementares, observações importantes..."></textarea>
                    </div>
                    <div class="form-field full-width">
                        <label for="foto_observacoes">Foto para Observações</label>
                        <input type="file" id="foto_observacoes" name="foto_observacoes" accept="image/*">
                    </div>
                </div>

                <!-- BOTÕES -->
                <div class="flex justify-end space-x-4 mt-8">
                    <button type="reset" class="btn-secondary">
                        <i class="fas fa-eraser mr-2"></i>Limpar Formulário
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save mr-2"></i>Registrar Ocorrência
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Modal de Suporte -->
    <div id="suporteModal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center;">
      <div style="background:#fff; border-radius:12px; max-width:500px; width:95vw; padding:2rem; position:relative; box-shadow:0 8px 32px rgba(0,0,0,0.2); min-height:350px;">
        <button id="closeSuporteModal" style="position:absolute; top:12px; right:12px; background:none; border:none; font-size:1.5rem; color:#888; cursor:pointer;">&times;</button>
        <div class="flex mb-4">
          <button id="abaTickets" class="flex-1 px-4 py-2 font-bold border-b-2 border-blue-600 text-blue-700 bg-blue-100">Meus Tickets</button>
          <button id="abaNovo" class="flex-1 px-4 py-2 font-bold border-b-2 border-transparent text-gray-600 bg-gray-100">Abrir Ticket</button>
        </div>
        <div id="painelTickets">
          <div id="ticketsLoading" class="text-center text-gray-500">Carregando tickets...</div>
          <table id="ticketsTable" class="w-full text-sm hidden">
            <thead>
              <tr>
                <th class="p-1 border-b">Assunto</th>
                <th class="p-1 border-b">Mensagem</th>
                <th class="p-1 border-b">Prioridade</th>
                <th class="p-1 border-b">Status</th>
                <th class="p-1 border-b">Resposta</th>
              </tr>
            </thead>
            <tbody id="ticketsBody"></tbody>
          </table>
          <div id="ticketsVazio" class="text-center text-gray-500 mt-4 hidden">Nenhum ticket encontrado.</div>
        </div>
        <div id="painelNovo" style="display:none;">
          <h2 class="text-xl font-bold mb-4">Abrir Ticket de Suporte</h2>
          <form id="suporteForm" method="post" action="abrir_ticket.php">
            <label class="block mb-2 font-medium">Assunto:
              <input type="text" name="assunto" class="w-full border rounded px-2 py-1 mb-4" required>
            </label>
            <label class="block mb-2 font-medium">Mensagem:
              <textarea name="mensagem" class="w-full border rounded px-2 py-1 mb-4" required></textarea>
            </label>
            <label class="block mb-2 font-medium">Prioridade:
              <select name="prioridade" class="w-full border rounded px-2 py-1 mb-4">
                <option value="baixa">Baixa</option>
                <option value="media" selected>Média</option>
                <option value="alta">Alta</option>
                <option value="urgente">Urgente</option>
              </select>
            </label>
            <div class="flex justify-end space-x-2">
              <button type="button" id="cancelSuporteModal" class="px-4 py-2 rounded border">Cancelar</button>
              <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Enviar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script>
      function showAlertaSuporte(msg, success) {
        const alerta = document.createElement('div');
        alerta.textContent = msg;
        alerta.style.position = 'fixed';
        alerta.style.top = '32px';
        alerta.style.right = '32px';
        alerta.style.zIndex = 11000;
        alerta.style.padding = '16px 32px';
        alerta.style.borderRadius = '8px';
        alerta.style.background = success ? '#22c55e' : '#ef4444';
        alerta.style.color = '#fff';
        alerta.style.fontWeight = 'bold';
        alerta.style.boxShadow = '0 4px 16px rgba(0,0,0,0.15)';
        document.body.appendChild(alerta);
        setTimeout(() => { alerta.remove(); }, 4000);
      }
      const openBtn = document.getElementById('openSuporteModal');
      const modal = document.getElementById('suporteModal');
      const closeBtn = document.getElementById('closeSuporteModal');
      const cancelBtn = document.getElementById('cancelSuporteModal');
      const suporteForm = document.getElementById('suporteForm');
      const abaTickets = document.getElementById('abaTickets');
      const abaNovo = document.getElementById('abaNovo');
      const painelTickets = document.getElementById('painelTickets');
      const painelNovo = document.getElementById('painelNovo');
      const ticketsTable = document.getElementById('ticketsTable');
      const ticketsBody = document.getElementById('ticketsBody');
      const ticketsLoading = document.getElementById('ticketsLoading');
      const ticketsVazio = document.getElementById('ticketsVazio');

      if(openBtn && modal && closeBtn && cancelBtn) {
        openBtn.onclick = function(e) {
          e.preventDefault();
          modal.style.display = 'flex';
          ativarAbaTickets();
        };
        closeBtn.onclick = cancelBtn.onclick = function() {
          modal.style.display = 'none';
        };
        window.onclick = function(event) {
          if (event.target === modal) {
            modal.style.display = 'none';
          }
        };
      }
      if (suporteForm) {
        suporteForm.onsubmit = function(e) {
          e.preventDefault();
          const formData = new FormData(suporteForm);
          fetch('abrir_ticket.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            showAlertaSuporte(data.message, data.success);
            if (data.success) {
              modal.style.display = 'none';
              suporteForm.reset();
            }
          })
          .catch(() => {
            showAlertaSuporte('Erro ao enviar ticket.', false);
          });
        };
      }
      function ativarAbaTickets() {
        abaTickets.classList.add('border-blue-600','text-blue-700','bg-blue-100');
        abaNovo.classList.remove('border-blue-600','text-blue-700','bg-blue-100');
        abaNovo.classList.add('border-transparent','text-gray-600','bg-gray-100');
        painelTickets.style.display = '';
        painelNovo.style.display = 'none';
        carregarTickets();
      }
      function ativarAbaNovo() {
        abaNovo.classList.add('border-blue-600','text-blue-700','bg-blue-100');
        abaTickets.classList.remove('border-blue-600','text-blue-700','bg-blue-100');
        abaTickets.classList.add('border-transparent','text-gray-600','bg-gray-100');
        painelNovo.style.display = '';
        painelTickets.style.display = 'none';
      }
      abaTickets.onclick = ativarAbaTickets;
      abaNovo.onclick = ativarAbaNovo;
      function carregarTickets() {
        ticketsLoading.style.display = '';
        ticketsTable.classList.add('hidden');
        ticketsVazio.classList.add('hidden');
        fetch('consultar_tickets.php')
          .then(res => res.json())
          .then(data => {
            ticketsLoading.style.display = 'none';
            if (data.length === 0) {
              ticketsVazio.classList.remove('hidden');
              ticketsTable.classList.add('hidden');
            } else {
              ticketsBody.innerHTML = '';
              data.forEach(ticket => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td class='border p-1'>${ticket.assunto || ticket.titulo}</td>
                                <td class='border p-1'>${ticket.mensagem || ticket.descricao}</td>
                                <td class='border p-1'>${ticket.prioridade}</td>
                                <td class='border p-1'>${ticket.status}</td>
                                <td class='border p-1'>${ticket.resposta ? `<span class='text-green-700'>${ticket.resposta}</span>` : '<span class="text-gray-400">Aguardando resposta</span>'}`;
                ticketsBody.appendChild(tr);
              });
              ticketsTable.classList.remove('hidden');
            }
          })
          .catch(() => {
            ticketsLoading.textContent = 'Erro ao carregar tickets.';
          });
      }
    </script>

    <script>
        // Função para adicionar imagem
        function addImage(fieldName) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.style.display = 'none';
            
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Criar preview da imagem
                        const preview = document.getElementById(fieldName + '_preview');
                        if (preview) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        }
                        
                        // Adicionar nome do arquivo ao campo
                        const textField = document.querySelector(`input[name="${fieldName}"]`);
                        if (textField) {
                            textField.value = file.name;
                        }

                        // Salva nome do arquivo no campo hidden
                        const hiddenField = document.getElementById(fieldName + '_file');
                        if (hiddenField) {
                            hiddenField.value = file.name;
                        }
                    };
                    reader.readAsDataURL(file);
                }
            };
            
            document.body.appendChild(input);
            input.click();
            document.body.removeChild(input);
        }

        // Função para remover imagem
        function removeImage(fieldName) {
            const preview = document.getElementById(fieldName + '_preview');
            if (preview) {
                preview.style.display = 'none';
                preview.src = '';
            }
            
            const textField = document.querySelector(`input[name="${fieldName}"]`);
            if (textField) {
                textField.value = '';
            }
        }

        function previewImage(input, previewId) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(previewId);
                    if (preview) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        }
    </script>

    <script>
function setupAssinatura(idCanvas, idInput) {
    const canvas = document.getElementById(idCanvas);
    const input = document.getElementById(idInput);
    const ctx = canvas.getContext('2d');
    let desenhando = false;
    let lastX = 0, lastY = 0;
    function draw(x, y) {
        ctx.lineTo(x, y);
        ctx.stroke();
    }
    function getTouchPos(canvas, touchEvent) {
        const rect = canvas.getBoundingClientRect();
        const touch = touchEvent.touches[0];
        const scaleX = canvas.width / rect.width;
        const scaleY = canvas.height / rect.height;
        return {
            x: (touch.clientX - rect.left) * scaleX,
            y: (touch.clientY - rect.top) * scaleY
        };
    }
    canvas.addEventListener('mousedown', e => {
        desenhando = true;
        ctx.beginPath();
        lastX = e.offsetX; lastY = e.offsetY;
        ctx.moveTo(lastX, lastY);
    });
    canvas.addEventListener('mousemove', e => {
        if (!desenhando) return;
        draw(e.offsetX, e.offsetY);
    });
    canvas.addEventListener('mouseup', () => {
        desenhando = false;
        input.value = canvas.toDataURL();
    });
    canvas.addEventListener('mouseleave', () => { desenhando = false; });
    // Touch events para mobile com ajuste de escala
    canvas.addEventListener('touchstart', function(e) {
        desenhando = true;
        const pos = getTouchPos(canvas, e);
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        lastX = pos.x;
        lastY = pos.y;
    });
    canvas.addEventListener('touchmove', function(e) {
        if (!desenhando) return;
        e.preventDefault();
        const pos = getTouchPos(canvas, e);
        draw(pos.x, pos.y);
        lastX = pos.x;
        lastY = pos.y;
    }, {passive: false});
    canvas.addEventListener('touchend', function() {
        desenhando = false;
        input.value = canvas.toDataURL();
    });
}
function limparAssinatura(idCanvas) {
    const canvas = document.getElementById(idCanvas);
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    // Limpa o campo hidden também
    const input = document.getElementById(idCanvas + '_img');
    if (input) input.value = '';
}
window.onload = function() {
    setupAssinatura('assinatura_solicitante', 'assinatura_solicitante_img');
    setupAssinatura('assinatura_vitima', 'assinatura_vitima_img');
    setupAssinatura('assinatura_autor', 'assinatura_autor_img');
    setupAssinatura('assinatura_testemunha1', 'assinatura_testemunha1_img');
    setupAssinatura('assinatura_testemunha2', 'assinatura_testemunha2_img');
};
</script>

<!-- Link para histórico SQLite -->
<div class="mt-8 text-center">
    <a href="historico_sqlite.php" class="text-blue-600 hover:underline">Ver Histórico de Ocorrências (SQLite)</a>
</div>
</script>

<script>
function removerTestemunha(btn) {
    const div = btn.closest('.bo-section');
    div.remove();
    testemunhaCount--;
    document.getElementById('add-testemunha-btn').style.display = '';
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectSolicitante = document.getElementById('solicitante_tipo');
    const secaoSolicitante = document.getElementById('dados-solicitante-section');
    function toggleSolicitanteSection() {
        if (selectSolicitante.value === 'municipe' || selectSolicitante.value === 'outros') {
            secaoSolicitante.style.display = '';
        } else {
            secaoSolicitante.style.display = 'none';
        }
    }
    selectSolicitante.addEventListener('change', toggleSolicitanteSection);
    toggleSolicitanteSection();
});
</script>

<?php if (!empty($msg)): ?>
    <div><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if (!empty($error_msg)): ?>
    <div><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function() {
            alert('Formulário enviado! Se não aparecer mensagem de erro, o POST foi disparado.');
        });
    }
});
</script>

<script>
// Ao carregar a página, preenche o campo de hora com a hora do aparelho
document.addEventListener('DOMContentLoaded', function() {
    var inputHora = document.querySelector('input[name="hora_fato"]');
    if (inputHora && !inputHora.value) {
        var agora = new Date();
        var hora = agora.getHours().toString().padStart(2, '0');
        var min = agora.getMinutes().toString().padStart(2, '0');
        inputHora.value = hora + ':' + min;
    }
    var inputData = document.querySelector('input[name="data_fato"]');
    if (inputData && !inputData.value) {
        var hoje = new Date();
        var mes = (hoje.getMonth()+1).toString().padStart(2, '0');
        var dia = hoje.getDate().toString().padStart(2, '0');
        inputData.value = hoje.getFullYear() + '-' + mes + '-' + dia;
    }
});
</script>

<script>
// Preencher campo oculto com data/hora do aparelho ao enviar o formulário
var form = document.querySelector('form[method="POST"]');
if (form) {
    form.addEventListener('submit', function() {
        var agora = new Date();
        var dataHora = agora.getFullYear() + '-' +
                       String(agora.getMonth()+1).padStart(2, '0') + '-' +
                       String(agora.getDate()).padStart(2, '0') + ' ' +
                       String(agora.getHours()).padStart(2, '0') + ':' +
                       String(agora.getMinutes()).padStart(2, '0') + ':' +
                       String(agora.getSeconds()).padStart(2, '0');
        document.getElementById('hora_registro_aparelho').value = dataHora;
    });
}
</script>

</body>
</html> 