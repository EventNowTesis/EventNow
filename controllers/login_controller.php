<?php

require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../libraries/phpmailer/mail_engine.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

$tpl = loadTPL("login", "Iniciar sesión");

$msg_error = "";
$mostrarReenviar = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["reenviar_verificacion"])) {

        $pendiente = $_SESSION['verif_pendiente'] ?? '';

        if ($pendiente === '') {
            $msg_error = "Iniciá sesión de nuevo para pedir el mail de verificación.";
        } else {
            $u = getUserByEmail($pendiente);

            if (!$u || (int) ($u['email_verificado'] ?? 1) === 1) {
                unset($_SESSION['verif_pendiente']);
                $msg_error = "Tu mail ya está verificado. Iniciá sesión.";
            } elseif (!puedeReenviarVerificacion($pendiente)) {
                $msg_error = "Esperá un par de minutos antes de pedir otro mail.";
                $mostrarReenviar = true;
            } elseif (enviarMailVerificacion($pendiente, $u['display_name'] ?? '')) {
                $msg_error = "Te reenviamos el mail de verificación. Revisá tu bandeja y el spam.";
            } else {
                $msg_error = "No pudimos enviar el mail. Probá de nuevo más tarde.";
                $mostrarReenviar = true;
            }
        }

    } else {

        $usuario_ingresado = trim($_POST["txt_email"] ?? "");
        $password = $_POST["txt_password"] ?? "";

        if ($usuario_ingresado === "" || $password === "") {
            $msg_error = "Completá todos los campos.";
        } else {
            $usuario = getUserByEmailOrUsername($usuario_ingresado);

            if ($usuario) {
                if (password_verify($password, $usuario["password"])) {

                    $estadoUsuario = $usuario["estado"] ?? "activo";
                    $rolUsuario = strtolower(trim($usuario["rol"]));

                    if ($estadoUsuario === "desactivado") {
                        $msg_error = "Tu cuenta ha sido desactivada";
                    } elseif ($rolUsuario !== "admin" && (int) ($usuario["email_verificado"] ?? 1) === 0) {
                        $_SESSION['verif_pendiente'] = $usuario["email"];
                        $msg_error = "Tenés que verificar tu mail antes de entrar. Revisá tu bandeja y el spam.";
                        $mostrarReenviar = true;
                    } else {
                        $_SESSION["id_usuario"] = $usuario["id_usuario"];
                        $_SESSION["email"] = $usuario["email"];
                        $_SESSION["rol"] = $usuario["rol"];

                        $rol = $rolUsuario;

                        if ($rol === "anfitrion") {
                            header("Location: index.php?slug=panel");
                            exit;
                        } elseif ($rol === "visitante") {
                            header("Location: index.php?slug=landing");
                            exit;
                        } elseif ($rol === "admin") {
                            header("Location: index.php?slug=panelAdmin");
                            exit;
                        } else {
                            $msg_error = "El rol del usuario no es válido.";
                        }
                    }

                } else {
                    $msg_error = "Contraseña inválida.";
                }
            } else {
                $msg_error = "Email o usuario no registrado.";
            }
        }
    }
}

if ($msg_error === "" && isset($_SESSION['flash_login'])) {
    $msg_error = htmlspecialchars($_SESSION['flash_login']);
    unset($_SESSION['flash_login']);
}

$tpl = setVarTPL("msg_error", $msg_error, $tpl);
$tpl = setVarTPL("display_reenviar", $mostrarReenviar ? "block" : "none", $tpl);
printTPL($tpl);
?>