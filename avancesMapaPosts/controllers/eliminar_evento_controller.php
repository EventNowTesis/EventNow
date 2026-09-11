<?php

session_start();

require_once __DIR__ . '/../models/Panel.php';

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=landing");
    exit;
}

$id_usuario = $_SESSION["id_usuario"];

$id_evento = $_GET["id"] ?? null;

if (!$id_evento) {
    echo "Evento no encontrado.";
    exit;
}

$resultado = eliminarEvento($id_evento, $id_usuario);

if (!$resultado) {
    echo "No se pudo eliminar el evento.";
    exit;
}

// Volver al perfil
 header("Location: index.php?slug=panel");
exit;
?>