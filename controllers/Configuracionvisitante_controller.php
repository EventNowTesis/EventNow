<?php

require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

if ($_SESSION["rol"] !== "visitante") {
    echo "No tenés permiso para acceder a esta página.";
    exit;
}

$id_usuario = $_SESSION["id_usuario"];
$rolUsuario = $_SESSION["rol"];

$usuario = getUserById($id_usuario);

if (!$usuario) {
    header("Location: index.php?slug=login");
    exit;
}

$ubicacion = getUbicacionUsuario($id_usuario);

$tpl = loadTPL("Configuracionvisitante", "Configuración | EventNow");
$tpl = cargarNavbarComun($tpl, $id_usuario, $rolUsuario);
$tpl = setVarTPL("USUARIO_NOMBRE", htmlspecialchars($usuario['display_name'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_LAT", htmlspecialchars($ubicacion['lat'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_LON", htmlspecialchars($ubicacion['lon'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_UBICACION_TEXTO", htmlspecialchars($ubicacion['direccion_texto'] ?? ''), $tpl);

printTPL($tpl);