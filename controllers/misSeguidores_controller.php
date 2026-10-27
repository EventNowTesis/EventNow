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

if (($_SESSION['rol'] ?? '') !== 'anfitrion') {
    header('Location: index.php?slug=panelVisitante');
    exit;
}

function escaparSeguidor($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

$id_anfitrion = (int) $_SESSION['id_usuario'];

$seguidores = obtenerSeguidoresAnfitrion($id_anfitrion);
$total = count($seguidores);

$listaSeguidores = '';

if ($total === 0) {
    $listaSeguidores =
        '<p class="siguiendo-vacio">'
        . 'Todavía no tenés seguidores. Cuando alguien te siga, va a aparecer acá.'
        . '</p>';
} else {
    foreach ($seguidores as $s) {

        $nombre   = (string) $s['display_name'];
        $usuario  = (string) $s['nombre_usuario'];
        $inicial  = mb_strtoupper(mb_substr($nombre !== '' ? $nombre : $usuario, 0, 1, 'UTF-8'), 'UTF-8');
        $busqueda = mb_strtolower($nombre . ' ' . $usuario, 'UTF-8');

        $desde = '';
        if (!empty($s['fecha_seguimiento'])) {
            $ts = strtotime($s['fecha_seguimiento']);
            if ($ts) {
                $desde = 'Te sigue desde el ' . date('d/m/Y', $ts);
            }
        }

        $listaSeguidores .=
            '<article class="seguido-card" data-nombre="' . escaparSeguidor($busqueda) . '">'
            . '<div class="seguido-avatar">' . escaparSeguidor($inicial) . '</div>'
            . '<div class="seguido-info">'
            . '<span class="seguido-rol">@' . escaparSeguidor($usuario) . '</span>'
            . '<span class="seguido-nombre">' . escaparSeguidor($nombre) . '</span>'
            . ($desde !== ''
                ? '<span class="seguido-estado"><i class="fa-solid fa-check"></i> ' . escaparSeguidor($desde) . '</span>'
                : '')
            . '</div>'
            . '<div class="seguido-acciones">'
            . '<a class="btn-ver-perfil" href="index.php?slug=profile&amp;username=' . urlencode($usuario) . '">'
            . 'Ver perfil <i class="fa-solid fa-arrow-up-right-from-square"></i>'
            . '</a>'
            . '</div>'
            . '</article>';
    }
}

$tpl = loadTPL("misSeguidores", "Mis seguidores");
$tpl = cargarNavbarComun($tpl, $id_anfitrion, $_SESSION['rol']);

$tpl = setVarTPL("SEGUIDORES", $listaSeguidores, $tpl);

printTPL($tpl);

?>