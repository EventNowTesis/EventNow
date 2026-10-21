<?php

session_start();

require_once __DIR__ . '/../models/Panel.php';

if (!isset($_SESSION['id_usuario'])) {

    header(
        "Location: index.php?slug=login"
    );

    exit;
}


if (($_SESSION['rol'] ?? '') !== 'visitante') {

    header(
        "Location: index.php?slug=landing"
    );

    exit;
}


$id_usuario = (int) $_SESSION['id_usuario'];


$id_evento = isset($_POST['id_evento']) ? (int) $_POST['id_evento']: 0;


$estrellas = isset($_POST['estrellas']) ? (int) $_POST['estrellas'] : 0;


$comentario =trim( $_POST['comentario'] ?? '');


if ($id_evento <= 0) {

    header("Location: index.php?slug=landing");

    exit;
}


if ($estrellas < 1 || $estrellas > 5) {

    header(
        "Location: index.php?slug=detalleEvento&id="
        . $id_evento
        . "&error=estrellas"
    );

    exit;
}



$guardado = guardarValoracionEvento($id_usuario, $id_evento, $estrellas, $comentario);

if ($guardado) {

    header(
        "Location: index.php?slug=detalleEvento&id="
        . $id_evento
        . "&valoracion=ok"
    );

    exit;

}


header( "Location: index.php?slug=detalleEvento&id=" . $id_evento. "&error=valoracion");

exit;

?>