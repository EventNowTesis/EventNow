<?php
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';
require_once __DIR__ . '/../../models/Usuarios.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoGmail($destinatario, $asunto, $htmlMensaje)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'SupportEventNow@gmail.com';
        $mail->Password = 'luqmjpnkmpaactxm';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('SupportEventNow@gmail.com', 'EventNow');
        $mail->addAddress($destinatario);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = $htmlMensaje;
        $mail->CharSet = 'UTF-8';


        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "Error al enviar el correo: " . $mail->ErrorInfo;
        return false;
    }
}


function urlBaseSitio()
{
    if (defined('BASE_URL')) {
        return rtrim(BASE_URL, '/');
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir   = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

function enviarMailVerificacion($email, $nombre)
{
    $token = bin2hex(random_bytes(32));

    if (!guardarTokenVerificacion($email, hash('sha256', $token))) {
        return false;
    }

    $link   = urlBaseSitio() . '/index.php?slug=verificarEmail&token=' . $token;
    $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');

    $html = "<p>Hola {$nombre},</p>"
          . "<p>Confirmá tu mail para activar tu cuenta en EventNow:</p>"
          . "<p><a href=\"{$link}\">Verificar mi cuenta</a></p>"
          . "<p>El link dura 24 horas. Si no creaste esta cuenta, ignorá este mensaje.</p>";

    return enviarCorreoGmail($email, 'Verificá tu cuenta en EventNow', $html);
}