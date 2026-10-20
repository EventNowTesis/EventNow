<?php

require_once __DIR__ . '/../models/Panel.php';
require_once __DIR__ . '/../models/Profile.php';
require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/assets/tpl_engine/tpl_engine.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Notificaciones.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);


if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=landing");
    exit;
}
if ($_SESSION['rol'] !== 'anfitrion') {
    header("Location: index.php?slug=landing");
    exit;
}

$id_usuario = $_SESSION["id_usuario"];
$rolUsuario = $_SESSION["rol"];

$carpetaUploads = __DIR__ . '/../uploads/';


if (isset($_POST["btn_editar"])) {

    $id_evento = $_POST["id_evento"] ?? null;

    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $fecha = $_POST["fecha"] ?? "";
    $hora = $_POST["hora"] ?? "";
    $zona = trim($_POST["ubicacion"] ?? "");
    $localidad = trim($_POST["localidad"] ?? "");

    // coordenadas del mapa
    $lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float) $_POST['lat'] : null;
    $lon = isset($_POST['lon']) && $_POST['lon'] !== '' ? (float) $_POST['lon'] : null;

    if ($lat !== null && ($lat < -90 || $lat > 90)) {
        $lat = null;
    }
    if ($lon !== null && ($lon < -180 || $lon > 180)) {
        $lon = null;
    }

    $contactoEmail = trim($_POST["contacto_email"] ?? "");
    $contactoTelefono = trim($_POST["contacto_telefono"] ?? "");
    $contactoInstagram = trim($_POST["contacto_instagram"] ?? "");
    $contactoWeb = trim($_POST["contacto_web"] ?? "");

    $infoApto = trim($_POST["info_apto"] ?? "");
    $infoDuracion = trim($_POST["info_duracion"] ?? "");
    $infoCapacidad = trim($_POST["info_capacidad"] ?? "");
    $infoAccesibilidad = trim($_POST["info_accesibilidad"] ?? "");
    $infoMascotas = trim($_POST["info_mascotas"] ?? "");

    $adicion = trim($_POST["adicion"] ?? "");


    if (!$id_evento) {
        echo "No se encontró el evento.";
        exit;
    }


    if (
        $titulo === "" ||
        $descripcion === "" ||
        $categoria === "" ||
        $fecha === "" ||
        $hora === "" ||
        $zona === ""
    ) {
        echo "Todos los campos son obligatorios.";
        exit;
    }


    $categoriasPermitidas = [
        "musica",
        "deporte",
        "feria",
        "arte",
        "gastronomia",
        "tecnologia",
        "otro"
    ];

    if (!in_array($categoria, $categoriasPermitidas, true)) {
        echo "Categoría no válida.";
        exit;
    }


    $evento = getEventoDeUsuario($id_evento, $id_usuario);

    if (!$evento) {
        echo "No tenés permiso para editar este evento.";
        exit;
    }


    /* ---------- Imágenes: validar antes de tocar nada ---------- */

    $carpetaEvento = carpetaDelEvento($evento);

    if ($carpetaEvento === null) {
        echo "No se encontró la carpeta de imágenes del evento.";
        exit;
    }

    $rutaCarpetaEvento = $carpetaUploads . $carpetaEvento . '/';

    $cambioImagenes = validarCambioImagenes(
        $rutaCarpetaEvento,
        $_POST['eliminar_imagenes'] ?? [],
        $_FILES['imagenes'] ?? null
    );

    if ($cambioImagenes['error'] !== null) {
        echo $cambioImagenes['error'];
        exit;
    }


    /* ---------- Guardar datos del evento ---------- */

    $resultado = editarMiEvento(
        $id_evento,
        $id_usuario,
        $titulo,
        $descripcion,
        $categoria,
        $fecha,
        $hora,
        $zona,
        $localidad,
        $lat,
        $lon,
        $contactoEmail,
        $contactoTelefono,
        $contactoInstagram,
        $contactoWeb,
        $infoApto,
        $infoDuracion,
        $infoCapacidad,
        $infoAccesibilidad,
        $infoMascotas,
        $adicion
    );


    if (!$resultado) {
        echo "No se pudo modificar el evento.";
        exit;
    }


    /* ---------- Imágenes: aplicar cambios en disco ---------- */

    $nuevaPortada = aplicarCambioImagenes(
        $rutaCarpetaEvento,
        $cambioImagenes['eliminar'],
        $cambioImagenes['nuevas']
    );

    // si se borró la portada (o cambió el orden), actualizamos la que guarda el evento
    if ($nuevaPortada !== null) {
        $portadaCompleta = $carpetaEvento . '/' . $nuevaPortada;

        if ($portadaCompleta !== ($evento['imagen'] ?? '')) {
            actualizarPortadaEvento((int) $id_evento, $portadaCompleta);
        }
    }


    registrarModificacionEvento((int) $id_evento, $evento, [
        'titulo' => $titulo, 'descripcion' => $descripcion, 'categoria' => $categoria,
        'fecha' => $fecha, 'hora' => $hora, 'zona' => $zona, 'localidad' => $localidad,
        'lat' => $lat, 'lon' => $lon,
        'contacto_email' => $contactoEmail, 'contacto_telefono' => $contactoTelefono,
        'contacto_instagram' => $contactoInstagram, 'contacto_web' => $contactoWeb,
        'info_apto' => $infoApto, 'info_duracion' => $infoDuracion, 'info_capacidad' => $infoCapacidad,
        'info_accesibilidad' => $infoAccesibilidad, 'info_mascotas' => $infoMascotas,
        'adicion' => $adicion,
    ]);

    header("Location: index.php?slug=panel");
    exit;
}


$id_evento = $_GET["id"] ?? null;

if (!$id_evento) {
    echo "No se encontró el evento.";
    exit;
}


$evento = getEventoDeUsuario($id_evento, $id_usuario);

if (!$evento) {
    echo "No tenés permiso para editar este evento.";
    exit;
}

if (substr($evento['fecha'], 0, 10) < date('Y-m-d') || ($evento['estado'] ?? '') === 'cancelado') {
    header("Location: index.php?slug=panel");
    exit;
}


$tpl = loadTPL("editar_evento", "Editar evento");


/* ---------- Navbar (usuario + notificaciones) ---------- */

// Setea USUARIO_NOMBRE, PERFIL_SLUG, ROL_CLASE, BADGE_* y LISTA_NOTIFICACIONES
$tpl = cargarNavbarComun($tpl, $id_usuario, $rolUsuario);
$tpl = setVarTPL("ESTADO_SESION", "logueado", $tpl);


/* ---------- Datos del evento ---------- */

$tpl = setVarTPL("ID_EVENTO", $evento["id_evento"] ?? "", $tpl);
$tpl = setVarTPL("TITULO", htmlspecialchars($evento["titulo"] ?? ""), $tpl);
$tpl = setVarTPL("DESCRIPCION", htmlspecialchars($evento["descripcion"] ?? ""), $tpl);
$tpl = setVarTPL("FECHA", $evento["fecha"] ?? "", $tpl);
$tpl = setVarTPL("HORA", $evento["hora"] ?? "", $tpl);
$tpl = setVarTPL("ZONA", htmlspecialchars($evento["zona"] ?? ""), $tpl);
$tpl = setVarTPL("LOCALIDAD", htmlspecialchars($evento["localidad"] ?? ""), $tpl);

// coordenadas para el mapa
$tpl = setVarTPL("LAT", htmlspecialchars($evento["lat"] ?? ""), $tpl);
$tpl = setVarTPL("LON", htmlspecialchars($evento["lon"] ?? ""), $tpl);

// contacto
$tpl = setVarTPL("CONTACTO_EMAIL", htmlspecialchars($evento["contacto_email"] ?? ""), $tpl);
$tpl = setVarTPL("CONTACTO_TELEFONO", htmlspecialchars($evento["contacto_telefono"] ?? ""), $tpl);
$tpl = setVarTPL("CONTACTO_INSTAGRAM", htmlspecialchars($evento["contacto_instagram"] ?? ""), $tpl);
$tpl = setVarTPL("CONTACTO_WEB", htmlspecialchars($evento["contacto_web"] ?? ""), $tpl);

// info extra
$tpl = setVarTPL("INFO_DURACION", htmlspecialchars($evento["info_duracion"] ?? ""), $tpl);
$tpl = setVarTPL("INFO_CAPACIDAD", htmlspecialchars($evento["info_capacidad"] ?? ""), $tpl);


$opcionesApto = [
    "" => "SIN_ESPECIFICAR",
    "Todo público" => "TODO_PUBLICO",
    "Mayores de 18" => "MAYORES_18",
    "Familiar / niños" => "FAMILIAR_NINOS",
];

foreach ($opcionesApto as $valor => $sufijo) {
    $seleccionada = (($evento["info_apto"] ?? "") === $valor) ? "selected" : "";
    $tpl = setVarTPL("INFO_APTO_{$sufijo}_SELECTED", $seleccionada, $tpl);
}


$opcionesAccesibilidad = [
    "" => "SIN_ESPECIFICAR",
    "Acceso para sillas de ruedas" => "SILLAS_RUEDAS",
    "Sin acceso adaptado" => "SIN_ACCESO",
];

foreach ($opcionesAccesibilidad as $valor => $sufijo) {
    $seleccionada = (($evento["info_accesibilidad"] ?? "") === $valor) ? "selected" : "";
    $tpl = setVarTPL("INFO_ACCESIBILIDAD_{$sufijo}_SELECTED", $seleccionada, $tpl);
}


$opcionesMascotas = [
    "" => "SIN_ESPECIFICAR",
    "Bienvenidas con correa" => "BIENVENIDAS",
    "No se permiten mascotas" => "NO_PERMITIDAS",
];

foreach ($opcionesMascotas as $valor => $sufijo) {
    $seleccionada = (($evento["info_mascotas"] ?? "") === $valor) ? "selected" : "";
    $tpl = setVarTPL("INFO_MASCOTAS_{$sufijo}_SELECTED", $seleccionada, $tpl);
}


$tpl = setVarTPL("adicion", htmlspecialchars($evento["adicion"] ?? ""), $tpl);


$categorias = [
    "musica",
    "deporte",
    "feria",
    "arte",
    "gastronomia",
    "tecnologia",
    "otro"
];

foreach ($categorias as $categoria) {
    $seleccionada = (($evento["categoria"] ?? "") === $categoria) ? "selected" : "";
    $tpl = setVarTPL(strtoupper($categoria), $seleccionada, $tpl);
}


/* ---------- Imágenes actuales (con casilla para quitar) ---------- */

$htmlImagenes = '';
$carpetaEvento = carpetaDelEvento($evento);
 
if ($carpetaEvento !== null) {
    $carpetaSegura = htmlspecialchars($carpetaEvento);
 
    foreach (listarImagenesEvento($carpetaUploads . $carpetaEvento . '/') as $nombre) {
        $nombreSeguro = htmlspecialchars($nombre);
        $htmlImagenes .= '<div class="img-actual"><img src="uploads/' . $carpetaSegura . '/' . $nombreSeguro . '" alt="Imagen actual"><label class="img-actual-quitar"><input type="checkbox" name="eliminar_imagenes[]" value="' . $nombreSeguro . '"> Quitar</label></div>';
    }
}
 
$tpl = setVarTPL("IMAGENES_ACTUALES", $htmlImagenes, $tpl);
 
 
printTPL($tpl);
exit;