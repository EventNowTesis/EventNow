<?php
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

purgarCuentasVencidas();

$tpl = loadTPL("recuperarCuenta", "Recuperar cuenta");
$msg_error = "";
$msg_exito = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['txt_email'] ?? '');
    $pass  = $_POST['txt_pass'] ?? '';
    $datos = getDatosBaja($email);

    if (!$datos || !password_verify($pass, $datos['password'])) {
        $msg_error = "Correo o contraseña incorrectos.";
    } elseif ($datos['estado'] !== 'desactivado' || !$datos['fecha_baja']) {
        $msg_error = "Esta cuenta no se puede recuperar.";
    } elseif (!$datos['recuperable']) {
        $msg_error = "Pasaron más de 15 días. La cuenta ya no se puede recuperar.";
    } else {
        reactivarCuenta($datos['id_usuario']);
        $msg_exito = "Cuenta recuperada. Ya podés iniciar sesión.";
    }
}

$tpl = setVarTPL("msg_error", $msg_error, $tpl);
$tpl = setVarTPL("msg_exito", $msg_exito, $tpl);
printTPL($tpl);
?>