<?php

require_once __DIR__ . '/../models/Panel.php';
require_once __DIR__ . '/../models/Profile.php';
require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/assets/tpl_engine/tpl_engine.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . '/../models/EventoDetalle.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('America/Argentina/Buenos_Aires');

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

function escEditar($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function filaHorarioHtml($hora, $actividad)
{
    $hora = $hora !== null ? substr((string) $hora, 0, 5) : '';

    return '<div class="fila-dinamica fila-horario">'
        . '<input type="time" name="horario_hora[]" placeholder="Hora" aria-label="Hora" value="' . escEditar($hora) . '">'
        . '<input type="text" name="horario_actividad[]" placeholder="Ej: Apertura de puertas" aria-label="Actividad" value="' . escEditar($actividad) . '">'
        . '<button type="button" class="btn-quitar-fila" onclick="quitarFila(this)" aria-label="Quitar horario"><i class="fa-solid fa-xmark"></i></button>'
        . '</div>';
}

function filaInfoExtraHtml($clave, $valor)
{
    return '<div class="fila-dinamica">'
        . '<input type="text" name="info_extra_clave[]" placeholder="Ej: Estacionamiento" aria-label="Dato" value="' . escEditar($clave) . '">'
        . '<input type="text" name="info_extra_valor[]" placeholder="Ej: Gratuito en el predio" aria-label="Detalle" value="' . escEditar($valor) . '">'
        . '<button type="button" class="btn-quitar-fila" onclick="quitarFila(this)" aria-label="Quitar dato"><i class="fa-solid fa-xmark"></i></button>'
        . '</div>';
}

function volverConError($mensaje, $id_evento)
{
    $_SESSION['flash_error'] = $mensaje;
    header("Location: index.php?slug=editar_evento&id=" . (int) $id_evento);
    exit;
}

if (isset($_POST["btn_editar"])) {

    $id_evento = $_POST["id_evento"] ?? null;

    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $fecha = $_POST["fecha"] ?? "";
    $hora = $_POST["hora"] ?? "";
    $zona = trim($_POST["ubicacion"] ?? "");
    $localidad = trim($_POST["localidad"] ?? "");

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
    $infoDuracion = armarDuracion($_POST["info_duracion_h"] ?? "", $_POST["info_duracion_m"] ?? "");
    $infoCapacidad = preg_replace('/\D/', '', $_POST["info_capacidad"] ?? "");
    $infoAccesibilidad = trim($_POST["info_accesibilidad"] ?? "");
    $infoMascotas = trim($_POST["info_mascotas"] ?? "");

    $adicion = trim($_POST["adicion"] ?? "");

    $horarios = [];
    $horasPost = $_POST['horario_hora'] ?? [];
    $actividadesPost = $_POST['horario_actividad'] ?? [];

    foreach ($actividadesPost as $i => $actividad) {
        $actividad = capitalizarTexto($actividad);
        if ($actividad === '') {
            continue;
        }
        $horaFila = trim($horasPost[$i] ?? '');
        if ($horaFila !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horaFila)) {
            $horaFila = '';
        }
        $horarios[] = [
            'hora' => $horaFila,
            'actividad' => $actividad,
        ];
    }

    $minutosAnterior = null;

    foreach ($horarios as $fila) {
        if ($fila['hora'] === '') {
            continue;
        }

        if (substr($fila['hora'], 0, 5) < substr($hora, 0, 5)) {
            volverConError("Los horarios del line-up no pueden ser antes de las " . substr($hora, 0, 5) . ", cuando empieza el evento.", $id_evento);
        }

        $partes = explode(':', $fila['hora']);
        $minutos = (int) $partes[0] * 60 + (int) $partes[1];

        if ($minutosAnterior !== null && $minutos < $minutosAnterior + 30) {
            volverConError("Cada horario del line-up tiene que ser al menos 30 minutos después del anterior.", $id_evento);
        }

        $minutosAnterior = $minutos;
    }

    $infoExtra = [];
    $clavesPost = $_POST['info_extra_clave'] ?? [];
    $valoresPost = $_POST['info_extra_valor'] ?? [];

    foreach ($clavesPost as $i => $clave) {
        $clave = trim($clave);
        $valor = trim($valoresPost[$i] ?? '');
        if ($clave === '' || $valor === '') {
            continue;
        }
        $infoExtra[] = [
            'clave' => $clave,
            'valor' => $valor,
        ];
    }

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
        volverConError("Todos los campos son obligatorios.", $id_evento);
    }

    $categoriasPermitidas = [
        "Musica",
        "Deporte",
        "Feria",
        "Arte",
        "Gastronomia",
        "Tecnologia",
        "Otro"
    ];

    if (!in_array($categoria, $categoriasPermitidas, true)) {
        volverConError("Categoría no válida.", $id_evento);
    }

    $evento = getEventoDeUsuario($id_evento, $id_usuario);

    if (!$evento) {
        echo "No tenés permiso para editar este evento.";
        exit;
    }

    $hoy = new DateTime('today');
    $fechaEventoObj = DateTime::createFromFormat('Y-m-d', $fecha);

    if (!$fechaEventoObj) {
        volverConError("La fecha del evento no es válida.", $id_evento);
    }

    $fechaEventoObj->setTime(0, 0);

    if ($fechaEventoObj < $hoy) {
        volverConError("La fecha del evento no puede ser anterior a hoy.", $id_evento);
    }

    if ($fechaEventoObj == $hoy && substr($hora, 0, 5) < date('H:i')) {
        volverConError("La hora del evento no puede ser anterior a la hora actual.", $id_evento);
    }

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
        volverConError($cambioImagenes['error'], $id_evento);
    }

    $evento['lineup'] = textoLineupEvento(obtenerHorariosEvento((int) $id_evento));
    $evento['otros_datos'] = textoOtrosDatosEvento(obtenerInfoExtraEvento((int) $id_evento));

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
        volverConError("No se pudo modificar el evento.", $id_evento);
    }

    if (!reemplazarHorariosEvento((int) $id_evento, $horarios)) {
        volverConError("El evento se modificó, pero no se pudo guardar el line-up.", $id_evento);
    }

    if (!reemplazarInfoExtraEvento((int) $id_evento, $infoExtra)) {
        volverConError("El evento se modificó, pero no se pudieron guardar los otros datos.", $id_evento);
    }

    $nuevaPortada = aplicarCambioImagenes(
        $rutaCarpetaEvento,
        $cambioImagenes['eliminar'],
        $cambioImagenes['nuevas']
    );

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
        'lineup' => textoLineupEvento($horarios),
        'otros_datos' => textoOtrosDatosEvento($infoExtra),
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

$tpl = cargarNavbarComun($tpl, $id_usuario, $rolUsuario);
$tpl = setVarTPL("ESTADO_SESION", "logueado", $tpl);

$tpl = setVarTPL("ID_EVENTO", $evento["id_evento"] ?? "", $tpl);
$tpl = setVarTPL("TITULO", htmlspecialchars($evento["titulo"] ?? ""), $tpl);
$tpl = setVarTPL("DESCRIPCION", htmlspecialchars($evento["descripcion"] ?? ""), $tpl);
$tpl = setVarTPL("FECHA", $evento["fecha"] ?? "", $tpl);
$tpl = setVarTPL("FECHA_MIN", date('Y-m-d'), $tpl);
$tpl = setVarTPL("HORA", $evento["hora"] ?? "", $tpl);
$tpl = setVarTPL("ZONA", htmlspecialchars($evento["zona"] ?? ""), $tpl);
$tpl = setVarTPL("LOCALIDAD", htmlspecialchars($evento["localidad"] ?? ""), $tpl);

$tpl = setVarTPL("LAT", htmlspecialchars($evento["lat"] ?? ""), $tpl);
$tpl = setVarTPL("LON", htmlspecialchars($evento["lon"] ?? ""), $tpl);

$tpl = setVarTPL("CONTACTO_EMAIL", htmlspecialchars($evento["contacto_email"] ?? ""), $tpl);
$tpl = setVarTPL("CONTACTO_TELEFONO", htmlspecialchars($evento["contacto_telefono"] ?? ""), $tpl);
$tpl = setVarTPL("CONTACTO_INSTAGRAM", htmlspecialchars($evento["contacto_instagram"] ?? ""), $tpl);
$tpl = setVarTPL("CONTACTO_WEB", htmlspecialchars($evento["contacto_web"] ?? ""), $tpl);

[$duracionH, $duracionM] = separarDuracion($evento["info_duracion"] ?? "");
$tpl = setVarTPL("INFO_DURACION_H", $duracionH, $tpl);
$tpl = setVarTPL("INFO_DURACION_M", $duracionM, $tpl);
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

$htmlHorarios = '';
foreach (obtenerHorariosEvento((int) $id_evento) as $fila) {
    $htmlHorarios .= filaHorarioHtml($fila['hora'], $fila['actividad']);
}
if ($htmlHorarios === '') {
    $htmlHorarios = filaHorarioHtml(null, '');
}
$tpl = setVarTPL("HORARIOS_ACTUALES", $htmlHorarios, $tpl);

$htmlInfoExtra = '';
foreach (obtenerInfoExtraEvento((int) $id_evento) as $fila) {
    $htmlInfoExtra .= filaInfoExtraHtml($fila['clave'], $fila['valor']);
}
if ($htmlInfoExtra === '') {
    $htmlInfoExtra = filaInfoExtraHtml('', '');
}
$tpl = setVarTPL("INFO_EXTRA_ACTUAL", $htmlInfoExtra, $tpl);

$categorias = [
    "Musica",
    "Deporte",
    "Feria",
    "Arte",
    "Gastronomia",
    "Tecnologia",
    "Otro"
];

foreach ($categorias as $categoria) {
    $seleccionada = (($evento["categoria"] ?? "") === $categoria) ? "selected" : "";
    $tpl = setVarTPL(strtoupper($categoria), $seleccionada, $tpl);
}

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

if (isset($_SESSION['flash_error'])) {
    $mensajeError = json_encode($_SESSION['flash_error'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
    unset($_SESSION['flash_error']);

    $scriptToast = '<script>document.addEventListener("DOMContentLoaded", function () { mostrarToast(' . $mensajeError . ', "error"); });</script>';
    $tpl = str_replace('</body>', $scriptToast . '</body>', $tpl);
}

printTPL($tpl);
exit;