<?php

require_once __DIR__ . '/../models/Panel.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Profile.php';
require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . '/assets/tpl_engine/tpl_engine.php';

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

if ($_SESSION["rol"] !== "anfitrion") {
    header("Location: index.php?slug=landing");
    exit;
}

$id_usuario = $_SESSION["id_usuario"];

$rolUsuario = $_SESSION["rol"] ?? 'visitante';  

$esAnfitrion = ($rolUsuario === 'anfitrion' || $rolUsuario === 'admin');

$id_evento = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($id_evento <= 0) {
    header("Location: index.php?slug=panel");
    exit;
}

$evento = getEventoDeUsuario($id_evento, $id_usuario);

if (!$evento) {
    header("Location: index.php?slug=panel");
    exit;
}

if (substr($evento['fecha'], 0, 10) < date('Y-m-d') || ($evento['estado'] ?? '') === 'cancelado') {
    header("Location: index.php?slug=panel");
    exit;
}

$mensajeError = "";

$motivo = "";


if (isset($_POST["btn_cancelar_evento"])) {

    $motivo = trim($_POST["motivo_cancelacion"] ?? "");

    if ($motivo === "") {

        $mensajeError = "Debe explicar por qué cancela el evento.";

    } elseif (mb_strlen($motivo) < 10) {

        $mensajeError = "El motivo debe tener al menos 10 caracteres.";

    } else {

        $resultado = cancelarEvento($id_evento, $id_usuario, $motivo);

        if ($resultado) {

            notificarCancelacionEvento($id_evento, $motivo);

            header("Location: index.php?slug=panel");
            exit;

        } else {

            $mensajeError = "No se pudo cancelar el evento.";
        }
    }
}


$usuario = getUserById($id_usuario);

$tpl = loadTPL("cancelar_evento", "Cancelar evento");
$tpl = cargarNavbarComun($tpl, $id_usuario, $rolUsuario);

$tpl = setVarTPL("USUARIO_NOMBRE", htmlspecialchars($usuario['display_name'] ?? '', ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("PERFIL_SLUG", "panel", $tpl);
$tpl = setVarTPL("ESTADO_SESION", "logueado", $tpl);
$tpl = setVarTPL("ROL_CLASE", "es-anfitrion", $tpl);

$tpl = setVarTPL("ID_EVENTO", $evento["id_evento"], $tpl);
$tpl = setVarTPL("EVENTO_TITULO", htmlspecialchars($evento["titulo"] ?? "", ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("MOTIVO", htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("MENSAJE_ERROR", $mensajeError !== "" ? '<div class="cancelar-error">' . htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') . '</div>' : "", $tpl);

printTPL($tpl);