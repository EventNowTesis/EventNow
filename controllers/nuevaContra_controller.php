<?php
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

// Solo se entra con un código ya verificado
if (!isset($_SESSION['reset_email'])) {
    header("Location: index.php?slug=recuperar");
    exit;
}

$tpl = loadTPL("nuevaContra", "Nueva contraseña");
$msg_error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pass  = $_POST['txt_pass']  ?? '';
    $pass2 = $_POST['txt_pass2'] ?? '';

    if (strlen($pass) < 8) {
        $msg_error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($pass !== $pass2) {
        $msg_error = "Las contraseñas no coinciden.";
    } elseif (!actualizarPasswordPorEmail($_SESSION['reset_email'], $pass)) {
        $msg_error = "No se pudo actualizar la contraseña. Inténtalo de nuevo.";
    } else {
        unset($_SESSION['reset_email']);
        header("Location: index.php?slug=login");
        exit;
    }
}

$tpl = setVarTPL("msg_error", $msg_error, $tpl);
printTPL($tpl);
?>