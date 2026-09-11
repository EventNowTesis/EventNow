<?php

require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

$tpl = loadTPL("register", "Registro");

$msg_error = "";

if (isset($_POST["btn_iniciar"])) {

    $email = trim($_POST["txt_email"] ?? "");
    $nombre = trim($_POST["txt_name"] ?? "");
    $password = $_POST["txt_password"] ?? "";
    $rol = $_POST["txt_tipo_cuenta"] ?? "";
    $nombre_usuario = trim($_POST["txt_username"] ?? "");

    $usuario = getUserByEmail($email);

    if ($usuario) {

        $msg_error = "Email ya registrado";

    } elseif ($rol === "anfitrion") {

        if ($nombre_usuario === "" || $nombre === "") {

            $msg_error = "Todos los campos son obligatorios.";

        } elseif (getUserByUsername($nombre_usuario)) {

            $msg_error = "Nombre de usuario en uso.";

        } else {

            newUserAnfi($email, $nombre, $nombre_usuario, $password, $rol);
            header("Location: index.php?slug=login");
            exit;
        }

    } elseif ($rol === "visitante") {

        if ($nombre === "") {
            $msg_error = "Todos los campos son obligatorios.";
        } else {
            newUserVisitante($email, $nombre, $password, $rol);
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