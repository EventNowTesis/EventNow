<?php

require_once __DIR__ . '/../models/Seguimientos.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";
date_default_timezone_set('America/Argentina/Buenos_Aires');

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?slug=login');
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'visitante') {
    header('Location: index.php?slug=panel');
    exit;
}

function escaparTexto($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

$id_cliente = (int) $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_anfitrion_dejar'])) {
    $id_anfitrion = (int) $_POST['id_anfitrion_dejar'];

    if ($id_anfitrion > 0) {
        dejarDeSeguirAnfitrion($id_cliente, $id_anfitrion);
    }

    header('Location: index.php?slug=publicacionesSiguiendo');
    exit;
}

$anfitriones = obtenerAnfitrionesSeguidos($id_cliente);
$total = count($anfitriones);

$listaAnfitriones = '';

if ($total === 0) {
    $listaAnfitriones =
        '<p class="siguiendo-vacio">'
        . 'Todavía no seguís a ningún anfitrión. '
        . 'Entrá a un evento y seguí a su organizador para verlo acá.'
        . '</p>';
} else {
    foreach ($anfitriones as $a) {

        $nombre   = (string) $a['display_name'];
        $usuario  = (string) $a['nombre_usuario'];
        $inicial  = mb_strtoupper(mb_substr($nombre !== '' ? $nombre : $usuario, 0, 1, 'UTF-8'), 'UTF-8');
        $busqueda = mb_strtolower($nombre . ' ' . $usuario, 'UTF-8');

        $listaAnfitriones .=
            '<article class="seguido-card" data-nombre="' . escaparTexto($busqueda) . '">'
            . '<div class="seguido-avatar">' . escaparTexto($inicial) . '</div>'
            . '<div class="seguido-info">'
            . '<span class="seguido-rol">Anfitrión</span>'
            . '<span class="seguido-nombre">' . escaparTexto($nombre) . '</span>'
            . '<span class="seguido-estado"><i class="fa-solid fa-check"></i> Lo seguís</span>'
            . '</div>'
            . '<div class="seguido-acciones">'
            . '<form class="seguido-form" method="POST" action="index.php?slug=publicacionesSiguiendo"'
            . ' onsubmit="return confirm(\'¿Dejar de seguir a este anfitrión?\');">'
            . '<input type="hidden" name="id_anfitrion_dejar" value="' . (int) $a['id_usuario'] . '">'
            . '<button type="submit" class="btn-dejar-seguir">'
            . '<i class="fa-solid fa-user-minus"></i> Dejar de seguir'
            . '</button>'
            . '</form>'
            . '<a class="btn-ver-perfil" href="index.php?slug=profile&amp;username=' . urlencode($usuario) . '">'
            . 'Ver perfil <i class="fa-solid fa-arrow-up-right-from-square"></i>'
            . '</a>'
            . '</div>'
            . '</article>';
    }
}

$cantidadTxt = $total === 1 ? '1 anfitrión' : $total . ' anfitriones';

$tpl = loadTPL("siguiendo", "Siguiendo");
$tpl = cargarNavbarComun($tpl, $id_cliente, $_SESSION['rol']);

$tpl = setVarTPL("CANTIDAD_SEGUIDOS_TXT", $cantidadTxt, $tpl);
$tpl = setVarTPL("ANFITRIONES", $listaAnfitriones, $tpl);

printTPL($tpl);

?>