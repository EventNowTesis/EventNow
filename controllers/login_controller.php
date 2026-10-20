<?php

require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

$tpl = loadTPL("login", "Iniciar sesión");

$msg_error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario_ingresado = trim($_POST["txt_email"] ?? "");
    $password = $_POST["txt_password"] ?? "";

    if ($usuario_ingresado === "" || $password === "") {
        $msg_error = "Completá todos los campos.";
    } else {
        $usuario = getUserByEmailOrUsername($usuario_ingresado);
        
        if ($usuario) {
            if (password_verify($password, $usuario["password"])) {

                $estadoUsuario = $usuario["estado"] ?? "activo";
                if ($estadoUsuario === "desactivado") {
                    $msg_error = "Tu cuenta ha sido desactivada";
                } else {
                    $_SESSION["id_usuario"] = $usuario["id_usuario"];
                    $_SESSION["email"] = $usuario["email"];
                    $_SESSION["rol"] = $usuario["rol"];

                    $rol = strtolower(trim($usuario["rol"]));
                  
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

$tpl = setVarTPL("msg_error", $msg_error, $tpl);
printTPL($tpl);
?>