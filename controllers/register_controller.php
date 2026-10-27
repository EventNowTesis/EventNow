<?php

require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../libraries/phpmailer/mail_engine.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

$tpl = loadTPL("register", "Registro");

$msg_error = "";

// Libera los mails de cuentas que nunca se verificaron
purgarNoVerificadosVencidos();

if (isset($_POST["btn_iniciar"])) {

    $email = trim($_POST["txt_email"] ?? "");
    $nombre = trim($_POST["txt_name"] ?? "");
    $password = $_POST["txt_password"] ?? "";
    $rol = $_POST["txt_tipo_cuenta"] ?? "";
    $nombre_usuario = trim($_POST["txt_username"] ?? "");

    $emailValido = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    $dominio = $emailValido ? substr(strrchr($email, '@'), 1) : '';

    if (!$emailValido) {

        $msg_error = "Ingresá un email válido.";

    } elseif (function_exists('checkdnsrr') && !checkdnsrr($dominio, 'MX')) {

        $msg_error = "El dominio del email no existe.";

    } elseif (strlen($password) < 8) {

        $msg_error = "La contraseña debe tener al menos 8 caracteres.";

    } elseif (getUserByEmail($email)) {

        $msg_error = "Email ya registrado";

    } elseif ($rol === "anfitrion") {

        if ($nombre_usuario === "" || $nombre === "") {

            $msg_error = "Todos los campos son obligatorios.";

        } elseif (getUserByUsername($nombre_usuario)) {

            $msg_error = "Nombre de usuario en uso.";

        } else {

            newUserAnfi($email, $nombre, $nombre_usuario, $password, $rol);
            $enviado = enviarMailVerificacion($email, $nombre);
            $_SESSION['flash_login'] = $enviado
                ? "Te enviamos un mail a {$email}. Verificalo para poder iniciar sesión (el link dura 24 horas)."
                : "Tu cuenta se creó, pero no pudimos enviar el mail de verificación. Iniciá sesión para pedir uno nuevo.";
            header("Location: index.php?slug=login");
            exit;
        }

    } elseif ($rol === "visitante") {

        if ($nombre === "") {
            $msg_error = "Todos los campos son obligatorios.";
        } else {
            newUserVisitante($email, $nombre, $password, $rol);
            enviarMailVerificacion($email, $nombre);
            $_SESSION['flash_login'] = "Te enviamos un mail a {$email}. Verificalo para poder iniciar sesión (el link dura 24 horas).";
            header("Location: index.php?slug=login");
            exit;
        }

    } else {

        $msg_error = "Elegí un tipo de cuenta.";
    }
}

if ($msg_error != "") {
    $msg_error_html = '<div id="msg__error">' . htmlspecialchars($msg_error) . '</div>';
} else {
    $msg_error_html = '';
}

$tpl = setVarTPL("msg_error", $msg_error_html, $tpl);

printTPL($tpl);

?>