<?php

require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

$ubicacion = getUbicacionUsuario($_SESSION["id_usuario"]);
$userLat = $ubicacion['lat'] !== null ? (float) $ubicacion['lat'] : null;
$userLon = $ubicacion['lon'] !== null ? (float) $ubicacion['lon'] : null;

if ($userLat === null || $userLon === null) {
    $_SESSION['aviso_ubicacion'] = true;
    header("Location: index.php?slug=landing");
    exit;
}

$eventos = getEventosParaMapa();

$hoy = date('Y-m-d');

$eventos = array_filter($eventos, function ($e) use ($hoy) {
    return ($e['fecha'] ?? '') >= $hoy;
});

$eventos = array_values($eventos);

$eventosMapa = array_map(function ($e) {
    return [
        "id"        => (int) $e['id_evento'],
        "titulo"    => $e['titulo'],
        "categoria" => $e['categoria'],
        "zona"      => $e['zona'],
        "fecha"     => $e['fecha'],
        "hora"      => $e['hora'],
        "imagen"    => $e['imagen'],
        "lat"       => (float) $e['lat'],
        "lon"       => (float) $e['lon'],
    ];
}, $eventos);

$rolUsuario = $_SESSION['rol'] ?? 'visitante';

$tpl = loadTPL("mapa", "Mapa de eventos");

$tpl = cargarNavbarComun($tpl, $_SESSION["id_usuario"], $rolUsuario);


//transforma el array de PHP en una cadena de texto tipo json para que JavaScript sí puede entenderlo. 
//los otros JSON covbierten los "<>",  " ' ", " , " & " para q no se rompa  los atriubutos de js y html.
$tpl = setVarTPL(
    "EVENTOS_JSON",
    json_encode($eventosMapa, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
    $tpl
);
$tpl = setVarTPL("USER_LAT", $userLat !== null ? $userLat : "null", $tpl);
$tpl = setVarTPL("USER_LON", $userLon !== null ? $userLon : "null", $tpl);

printTPL($tpl);