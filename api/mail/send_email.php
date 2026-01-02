<?php
// filepath: \\arca.ua.pt\Hosting\esan-tesp-ds-paw.web.ua.pt\tesp-ds-g32\E-commerce\api\mail\send_email.php

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function sendEmail($to, $to_name, $subject, $html_body, $alt_body = '')
{

    // Se EMAIL_HOST definido, usar PHPMailer SMTP
    if (!empty(EMAIL_HOST)) {
        return sendEmailSMTP($to, $to_name, $subject, $html_body, $alt_body);
    }

    return [
        'success' => false,
        'message' => 'EMAIL_HOST não configurado'
    ];
}

function sendEmailSMTP($to, $to_name, $subject, $html_body, $alt_body = '')
{
    $vendor_base = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

    require_once $vendor_base . 'Exception.php';
    require_once $vendor_base . 'PHPMailer.php';
    require_once $vendor_base . 'SMTP.php';

    $mail = new PHPMailer(true);

    try {
        // Configurar SMTP
        $mail->isSMTP();
        $mail->Host = EMAIL_HOST;
        $mail->SMTPAuth = EMAIL_SMTPAUTH;
        $mail->Username = EMAIL_USERNAME;
        $mail->Password = EMAIL_PASSWORD;
        $mail->SMTPSecure = (EMAIL_PORT == 587) ? 'tls' : ((EMAIL_PORT == 465) ? 'ssl' : '');
        $mail->Port = EMAIL_PORT;
        $mail->CharSet = 'UTF-8';

        // Debug desativado (ativar para troubleshoot)
        $mail->SMTPDebug = 0;

        // ✅ FROM = USERNAME (para evitar erro 550)
        $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM);
        $mail->addAddress($to, $to_name);

        // Conteúdo
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        $mail->AltBody = $alt_body ?: strip_tags($html_body);

        // Enviar
        $mail->send();

        return [
            'success' => true,
            'message' => 'Email enviado com sucesso para ' . $to
        ];
    } catch (PHPMailerException $e) {
        error_log("Erro ao enviar email via SMTP: " . $mail->ErrorInfo);

        return [
            'success' => false,
            'message' => 'Erro ao enviar email via SMTP',
            'error' => $mail->ErrorInfo
        ];
    }
}
