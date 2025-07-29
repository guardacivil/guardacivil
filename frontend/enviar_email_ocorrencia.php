<?php
// enviar_email_ocorrencia.php
// Uso: include este arquivo e utilize a função enviarEmailOcorrencia($ocorrencia, $pdf_content)

require_once '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once '../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once '../vendor/phpmailer/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarEmailOcorrencia($ocorrencia, $pdf_content, &$erro = null) {
    $mail = new PHPMailer(true);
    try {
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
        $mail->Subject = 'Novo Registro de Ocorrência: ' . $ocorrencia['numero_ocorrencia'];
        $mail->isHTML(true);
        $mail->Body = 'Segue em anexo o PDF da ocorrência cadastrada no sistema SMART.';
        $mail->addStringAttachment($pdf_content, 'ocorrencia_' . $ocorrencia['numero_ocorrencia'] . '.pdf', 'base64', 'application/pdf');
        return $mail->send();
    } catch (Exception $e) {
        $erro = $mail->ErrorInfo;
        return false;
    }
} 