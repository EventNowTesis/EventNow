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

if (!empty($usuario['nombre_usuario'])) {
    $username_html = '<p class="username">@' . htmlspecialchars($usuario['nombre_usuario']) . '</p>';
} else {
    $username_html = '';
}

$tpl = loadTPL("profile", "Profile");

$tpl = setVarTPL("EVENTOS", mostrarMisEventos(), $tpl);
$tpl = setVarTPL("USUARIO_NOMBRE", htmlspecialchars($usuario['display_name'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_ROL", htmlspecialchars(ucfirst($usuario['rol'] ?? '')), $tpl);
$tpl = setVarTPL("USUARIO_USERNAME", $username_html, $tpl);

printTPL($tpl);

?>