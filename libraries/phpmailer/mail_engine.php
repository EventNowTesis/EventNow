<?php
// /libraries/mail_engine.php
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoGmail($destinatario, $asunto, $htmlMensaje)
{
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'SupportEventNow@gmail.com';
        $mail->Password = 'qotiwodhsjjqsevy';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Remitente y Destinatario
        $mail->setFrom('SupportEventNow@gmail.com', 'EventNow');
        $mail->addAddress($destinatario);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $htmlMensaje;
        $mail->CharSet = 'UTF-8';


        $mail->send();
        return true;
    } catch (Exception $e) {
        // Muestra el error si algo sale mal al enviar el correo
        echo "Error al enviar el correo: " . $mail->ErrorInfo;
        exit;
        return false;
    }
}