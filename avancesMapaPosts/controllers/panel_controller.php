<?php

require_once __DIR__ . '/../models/Profile.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

$usuario = getUserById($_SESSION["id_usuario"]);

if (!$usuario) {
    header("Location: index.php?slug=login");
    exit;
}

$tpl = loadTPL("panel", "Panel");

$tpl = setVarTPL("EVENTOS", mostrarMisEventos(), $tpl);
$tpl = setVarTPL("USUARIO_NOMBRE", htmlspecialchars($usuario['display_name'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_ROL", htmlspecialchars(ucfirst($usuario['rol'] ?? '')), $tpl);
$tpl = setVarTPL("USUARIO_EMAIL", htmlspecialchars($usuario['email'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_USER", htmlspecialchars($usuario['nombre_usuario'] ?? ''), $tpl);

printTPL($tpl);

?>