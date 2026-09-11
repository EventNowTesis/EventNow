<?php

require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

if ($_SESSION["rol"] !== "visitante") {
    echo "No tenés permiso para acceder a este panel.";
    exit;
}

$usuario = getUserById($_SESSION["id_usuario"]);

if (!$usuario) {
    header("Location: index.php?slug=login");
    exit;
}

$tpl = loadTPL("panelVisitante", "Panel Visitante");

$tpl = setVarTPL("USUARIO_NOMBRE", htmlspecialchars($usuario['display_name'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_ROL", htmlspecialchars(ucfirst($usuario['rol'] ?? '')), $tpl);
$tpl = setVarTPL("USUARIO_EMAIL", htmlspecialchars($usuario['email'] ?? ''), $tpl);

printTPL($tpl);

?>