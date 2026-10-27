<?php

require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Panel.php';
require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";


if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}



if ($_SESSION["rol"] !== "visitante") {
    echo "No tenés permiso para acceder a este panel.";
    exit;
}



$id_usuario = $_SESSION["id_usuario"];
$rolUsuario = $_SESSION["rol"];

$usuario = getUserById($id_usuario);

if (!$usuario) {
    header("Location: index.php?slug=login");
    exit;
}



$msgUbicacion = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["btn_guardar_ubicacion"])
) {

    $lat = trim($_POST["lat_visitante"] ?? "");
    $lon = trim($_POST["lon_visitante"] ?? "");
    $direccionTexto = trim($_POST["direccion_exacta"] ?? "");

    if ($lat === "" || $lon === "" || $direccionTexto === "") {

        $msgUbicacion = "Elegí una dirección desde el buscador antes de guardar.";

    } else {

        $guardado = guardarUbicacionUsuario(
            $id_usuario,
            (float) $lat,
            (float) $lon,
            $direccionTexto
        );

        $msgUbicacion = $guardado
            ? "Dirección guardada correctamente."
            : "No se pudo guardar la dirección. Probá de nuevo.";
    }
}

$ubicacion = getUbicacionUsuario($id_usuario);



function leerParamGet($nombre)
{
    $valor = $_GET[$nombre] ?? '';

    return is_string($valor) ? trim($valor) : '';
}

$categoriasPermitidas = [
    'musica'      => 'Música',
    'deporte'     => 'Deportes',
    'feria'       => 'Ferias',
    'arte'        => 'Arte',
    'gastronomia' => 'Gastronomía',
    'tecnologia'  => 'Tecnología',
    'otro'        => 'Otro',
];

$temporalidadesPermitidas = [
    'todos'    => 'Todos los favoritos',
    'vigentes' => 'Eventos próximos',
    'pasados'  => 'Eventos pasados',
];

$rankingsPermitidos = [
    ''  => 'Todas las puntuaciones',
    '5' => '5 estrellas',
    '4' => '4 estrellas o más',
    '3' => '3 estrellas o más',
    '2' => '2 estrellas o más',
    '1' => '1 estrella o más',
];


$buscar       = leerParamGet('buscar');
$localidadSel = leerParamGet('localidad');

$categoria = leerParamGet('categoria');
if (!array_key_exists($categoria, $categoriasPermitidas)) {
    $categoria = '';
}

$temporalidad = leerParamGet('temporalidad');
if (!array_key_exists($temporalidad, $temporalidadesPermitidas)) {
    $temporalidad = 'todos';
}

$ranking = leerParamGet('ranking');
if (!in_array($ranking, ['', '1', '2', '3', '4', '5'], true)) {
    $ranking = '';
}

$fechaDesde = normalizarFechaFiltro(leerParamGet('fecha_desde'));
$fechaHasta = normalizarFechaFiltro(leerParamGet('fecha_hasta'));

if ($fechaDesde !== '' && $fechaHasta !== '' && $fechaHasta < $fechaDesde) {
    $fechaHasta = '';
}




$todosLosFavoritos = getFavoritosVisitante($id_usuario);

$eventosFavoritos = filtrarFavoritosVisitante($todosLosFavoritos, [
    'buscar'       => $buscar,
    'categoria'    => $categoria,
    'localidad'    => $localidadSel,
    'ranking'      => $ranking,
    'fecha_desde'  => $fechaDesde,
    'fecha_hasta'  => $fechaHasta,
    'temporalidad' => $temporalidad,
]);

$totalFavoritos  = count($todosLosFavoritos);
$totalFiltrados  = count($eventosFavoritos);

if ($totalFavoritos === 0) {
    $htmlFavoritos = generarHtmlTarjetasEventos([], 'favoritos');
} elseif ($totalFiltrados === 0) {
    $htmlFavoritos = '';
} else {
    $htmlFavoritos = generarHtmlTarjetasEventos($eventosFavoritos, 'favoritos');
}

$sinResultadosHidden = ($totalFavoritos > 0 && $totalFiltrados === 0) ? '' : 'hidden';



$opcionesCategoria = ['' => 'Todas las categorías'] + $categoriasPermitidas;

$opcionesZona = ['' => 'Todas las zonas'] + obtenerZonasFavoritos($todosLosFavoritos);




$eventosAsistire = getAsistireVisitante($id_usuario);

$htmlAsistire = generarHtmlTarjetasEventos($eventosAsistire, 'asistire');
$htmlCalendario = generarCalendarioAnual($eventosAsistire);




$tpl = loadTPL("panelVisitante", "Panel Visitante");

$tpl = cargarNavbarComun($tpl, $id_usuario, $rolUsuario);

$tpl = setVarTPL("USUARIO_ROL", htmlspecialchars(ucfirst($usuario['rol'] ?? ''), ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("USUARIO_EMAIL", htmlspecialchars($usuario['email'] ?? '', ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("USUARIO_USERNAME", htmlspecialchars($usuario['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8'), $tpl);

$tpl = setVarTPL("USUARIO_LAT", htmlspecialchars($ubicacion['lat'] ?? '', ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("USUARIO_LON", htmlspecialchars($ubicacion['lon'] ?? '', ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("USUARIO_UBICACION_TEXTO", htmlspecialchars($ubicacion['direccion_texto'] ?? '', ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("MSG_UBICACION", $msgUbicacion, $tpl);

$tpl = setVarTPL("FAVORITOS", $htmlFavoritos, $tpl);
$tpl = setVarTPL("CALENDARIO", $htmlCalendario, $tpl);


$tpl = setVarTPL("BUSQUEDA", htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("OPCIONES_CATEGORIA", armarOpcionesSelect($opcionesCategoria, $categoria), $tpl);
$tpl = setVarTPL("OPCIONES_ZONA", armarOpcionesSelect($opcionesZona, $localidadSel), $tpl);
$tpl = setVarTPL("OPCIONES_TEMPORALIDAD", armarOpcionesSelect($temporalidadesPermitidas, $temporalidad), $tpl);
$tpl = setVarTPL("OPCIONES_RANKING", armarOpcionesSelect($rankingsPermitidos, $ranking), $tpl);
$tpl = setVarTPL("FECHA_DESDE", htmlspecialchars($fechaDesde, ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("FECHA_HASTA", htmlspecialchars($fechaHasta, ENT_QUOTES, 'UTF-8'), $tpl);
$tpl = setVarTPL("CANTIDAD_FILTRADOS", $totalFiltrados, $tpl);
$tpl = setVarTPL("CANTIDAD_FAVORITOS", $totalFavoritos, $tpl);
$tpl = setVarTPL("SIN_RESULTADOS_HIDDEN", $sinResultadosHidden, $tpl);




$añoActual = (int) date('Y');
$añoInicial = $añoActual - 10;
$añoFinal = $añoActual + 10;

$htmlOpcionesAnios = "";

for ($año = $añoInicial; $año <= $añoFinal; $año++) {
    $seleccionado = ($año === $añoActual) ? "selected" : "";
    $htmlOpcionesAnios .= "<option value='{$año}' {$seleccionado}>{$año}</option>";
}

$tpl = setVarTPL("OPCIONES_ANIOS", $htmlOpcionesAnios, $tpl);
$tpl = setVarTPL("ANIO_ACTUAL", $añoActual, $tpl);

printTPL($tpl);

?>