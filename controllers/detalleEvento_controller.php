<?php

require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Panel.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

date_default_timezone_set('America/Argentina/Buenos_Aires');

function renderAnteriorHtml($valorAnterior, $enBloque = false)
{
    $valorAnterior = trim((string) $valorAnterior);
    $texto = $valorAnterior !== '' ? e($valorAnterior) : '<em>(vacío)</em>';
    $clase = 'campo-anterior' . ($enBloque ? ' campo-anterior--bloque' : '');

    return "<span class='{$clase}'><span class='tag-anterior'>Anterior</span><span>{$texto}</span></span>";
}

function renderFechaHoraAnteriorLinea($evento, $cambios)
{
    if (!isset($cambios['fecha']) && !isset($cambios['hora'])) {
        return '';
    }

    $fecha = $cambios['fecha']['antes'] ?? ($evento['fecha'] ?? '');
    $hora  = $cambios['hora']['antes']  ?? ($evento['hora']  ?? '');

    $ts = strtotime((string) $fecha);
    $fechaTxt = $ts ? date('d/m/Y', $ts) : (string) $fecha;
    $horaTxt  = substr((string) $hora, 0, 5);

    $partes = [];
    if ($fechaTxt !== '') {
        $partes[] = $fechaTxt;
    }
    if ($horaTxt !== '') {
        $partes[] = $horaTxt . ' hs';
    }

    return renderAnteriorHtml(implode(' · ', $partes));
}

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

$id_usuario = $_SESSION["id_usuario"];
$rolUsuario = $_SESSION["rol"] ?? 'visitante';
$esAnfitrion = ($rolUsuario === 'anfitrion');

$id_evento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_evento) {
    header("Location: index.php?slug=landing");
    exit;
}

$evento = obtenerPostConDetalle($id_evento);


if (!$evento) {
    header("Location: index.php?slug=landing");
    exit;
}

$datos = prepararDatosEventoDetalle($evento);

$yaPaso = eventoYaPaso($evento);
$esRevisionAdmin = ($rolUsuario === 'admin')
    && in_array($evento['estado'] ?? '', ['pendiente', 'modificado'], true);

$flashRevision = null;
if ($esRevisionAdmin && isset($_SESSION['flash_revision'])) {
    $flashRevision = $_SESSION['flash_revision'];
    unset($_SESSION['flash_revision']);
}

$cambiosVisibles = usuarioPuedeVerCambiosEvento($evento) ? getCambiosPendientesPorCampo($evento) : [];

$idNotif = filter_input(INPUT_GET, 'notif', FILTER_VALIDATE_INT);
if ($idNotif) {
    $cambiosNotif = consumirCambiosNotificacion($idNotif, (int) $id_usuario, (int) $id_evento);
    if (!empty($cambiosNotif)) {
        $cambiosVisibles = $cambiosNotif;
    }
}


$mensajeRespuesta = '';
$errorRespuesta = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accion'])
    && $_POST['accion'] === 'guardar_respuesta'
) {

    $idValoracion =
        filter_input(
            INPUT_POST,
            'id_valoracion',
            FILTER_VALIDATE_INT
        );


    $respuesta =
        trim(
            $_POST['respuesta'] ?? ''
        );


    if (
        $idValoracion === false
        || !$idValoracion
    ) {

        $errorRespuesta =
            'La valoración no es válida.';

    }

    elseif ($respuesta === '') {

        $errorRespuesta =
            'Escribí una respuesta antes de publicarla.';

    }

    elseif (strlen($respuesta) > 1000) {

        $errorRespuesta =
            'La respuesta no puede superar los 1000 caracteres.';

    }

    else {

        $guardado =
            guardarRespuestaValoracion(
                $id_usuario,
                $id_evento,
                $idValoracion,
                $respuesta
            );


        if ($guardado) {

            header(
                "Location: index.php?slug=detalleEvento&id="
                . (int) $id_evento
                . "&respuesta=ok#comentarios"
            );

            exit;

        } else {

            $errorRespuesta =
                'No se pudo guardar la respuesta.';

        }

    }

}

$mensajeValoracion = '';
$errorValoracion = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['accion'])
    && $_POST['accion'] === 'guardar_valoracion'
) {

    $estrellas =
        filter_input(
            INPUT_POST,
            'estrellas',
            FILTER_VALIDATE_INT
        );

    $comentario =
        trim(
            $_POST['comentario'] ?? ''
        );


    if (
        $estrellas === false
        || $estrellas < 1
        || $estrellas > 5
    ) {

        $errorValoracion =
            'Seleccioná una puntuación de 1 a 5 estrellas.';

    }

    elseif (!$yaPaso) {

        $errorValoracion =
            'Vas a poder valorar el evento cuando haya terminado.';

    }

    elseif (strlen($comentario) > 310) {

        $errorValoracion =
            'El comentario no puede superar los 310 caracteres.';

    }

    else {

        $guardado =
            guardarValoracionEvento(
                $id_usuario,
                $id_evento,
                $estrellas,
                $comentario
            );


        if ($guardado) {

            header(
                "Location: index.php?slug=detalleEvento&id="
                . (int) $id_evento
                . "&valoracion=ok#comentarios"
            );

            exit;

        } else {

            $errorValoracion =
                'No se pudo guardar la valoración. Intentá nuevamente.';

        }

    }
}


if (
    isset($_GET['valoracion'])
    && $_GET['valoracion'] === 'ok'
) {

    $mensajeValoracion =
        'Tu valoración fue guardada correctamente.';

}
$tpl = loadTPL("detalleEvento", $evento['titulo']);

$tpl = cargarNavbarComun($tpl, $id_usuario, $rolUsuario);

$rutaPortada = $datos['rutaArchivo'];
$carpetaRelativa = dirname($rutaPortada);
$imagenesCarrusel = [];

if (strpos(basename($carpetaRelativa), 'evt_') === 0) {
    $archivos = glob(__DIR__ . '/../' . $carpetaRelativa . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    if ($archivos) {
        natsort($archivos);
        foreach ($archivos as $archivo) {
            $imagenesCarrusel[] = $carpetaRelativa . '/' . basename($archivo);
        }
    }
}

if (!$imagenesCarrusel) {
    $imagenesCarrusel[] = $rutaPortada;
}

$slidesHtml = '';
foreach ($imagenesCarrusel as $n => $src) {
    $lazy = $n === 0 ? '' : ' loading="lazy" decoding="async"';
    $slidesHtml .= '<div class="carousel-slide"><img src="' . e($src) . '" alt="' . e($evento['titulo']) . ' - imagen ' . ($n + 1) . '"' . $lazy . ' /></div>';
}






if (isset($_GET['respuesta']) && $_GET['respuesta'] === 'ok') {
    $mensajeRespuesta = 'Tu respuesta fue publicada.';
}

$mensajesHtml = '';

foreach ([
    [$mensajeValoracion, 'ok',    'fa-circle-check'],
    [$mensajeRespuesta,  'ok',    'fa-circle-check'],
    [$errorValoracion,   'error', 'fa-circle-exclamation'],
    [$errorRespuesta,    'error', 'fa-circle-exclamation'],
] as $aviso) {
    if ($aviso[0] !== '') {
        $mensajesHtml .= '<div class="mensaje-valoracion mensaje-valoracion-' . $aviso[1] . '"><i class="fa-solid ' . $aviso[2] . '"></i> ' . e($aviso[0]) . '</div>';
    }
}

$htmlValoraciones = generarHtmlSeccionValoraciones($id_evento, $id_usuario, $yaPaso, $mensajesHtml);


$diasSemana = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$tsEvento = strtotime($evento['fecha'] ?? '');

if ($yaPaso) {
    $eyebrow = 'Evento finalizado';
} elseif ($tsEvento) {
    $eyebrow = 'Un plan para este ' . $diasSemana[(int) date('w', $tsEvento)];
} else {
    $eyebrow = 'Un plan para vos';
}

$subtitulo = (string) ($evento['subtitulo'] ?? '');

$etiquetasHtml = '';
$listaEtiquetas = array_filter(array_map('trim', explode(',', (string) ($evento['etiquetas'] ?? ''))));
foreach ($listaEtiquetas as $tag) {
    $etiquetasHtml .= '<span class="detail-tag">' . e($tag) . '</span>';
}


if ($yaPaso) {

    $botonConfirmarHtml = '
        <button type="button" class="btn-confirm detail-confirm" disabled>
            <i class="fa-solid fa-ban" aria-hidden="true"></i>
            <span class="confirm-text">Evento finalizado</span>
        </button>
    ';

} else {

    $yaConfirmo = in_array(
        (int) $evento['id_evento'],
        obtenerIdsAsistenciasUsuario($id_usuario),
        true
    );

    $claseConfirmado = $yaConfirmo ? ' confirmado' : '';
    $textoConfirmar  = $yaConfirmo ? 'Asistencia confirmada ✓' : 'Confirmar asistencia';

        $botonConfirmarHtml = '
        <a href="index.php?slug=confirmar&id=' . (int) $evento['id_evento'] . '" class="btn-confirm detail-confirm' . $claseConfirmado . '" id="btn-confirmar">
            
            <span class="confirm-text">' . $textoConfirmar . '</span>
        </a>
    ';
}

if ($esAnfitrion) {
    $botonConfirmarHtml = '';
}

$usernameOrganizador = $evento['nombre_usuario'] ?? '';

if ($usernameOrganizador === '') {
    $organizador = getUserById($evento['id_usuario']) ?: [];
    $usernameOrganizador = $organizador['nombre_usuario'] ?? '';
}

if ($yaPaso) {

    $contactoTitulo = 'Este evento ya finalizó';
    $contactoDesc   = 'Podés conocer al anfitrión y sus próximos eventos desde su perfil.';
    $contactoUrl    = 'index.php?slug=profile&username=' . urlencode($usernameOrganizador);
    $contactoTexto  = 'Ver perfil del anfitrión';

} else {

    $contactoTitulo = '¿Tenés dudas sobre este evento?';
    $contactoDesc   = 'Escribile directamente al anfitrión.';
    $contactoUrl    = 'index.php?slug=mensajes&id_evento=' . (int) $evento['id_evento'];
    $contactoTexto  = 'Contactar al anfitrión';
}


$tituloAnteriorHtml = isset($cambiosVisibles['titulo'])
    ? renderAnteriorHtml($cambiosVisibles['titulo']['antes'], true)
    : '';

$descripcionAnteriorHtml = isset($cambiosVisibles['descripcion'])
    ? renderAnteriorHtml($cambiosVisibles['descripcion']['antes'], true)
    : '';

$camposConLugarPropio = ['titulo', 'descripcion', 'fecha', 'hora', 'zona'];
$cambiosRestantes = array_diff_key($cambiosVisibles, array_flip($camposConLugarPropio));

$botonFavoritoHtml = '';

if ($rolUsuario === 'visitante') {

    $esFavorito = in_array(
        (int) $evento['id_evento'],
        obtenerIdsFavoritosUsuario($id_usuario),
        true
    );

    $claseFavorito = $esFavorito ? 'favorito-activo' : '';
    $idEventoFav   = (int) $evento['id_evento'];

    $botonFavoritoHtml = "
        <button type='button'
                class='btn-favorito {$claseFavorito}'
                onclick='toggleFavorito(this, {$idEventoFav}, true)'
                title='Guardar como favorito'>
            <svg viewBox='0 0 24 24'>
                <path d='M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'/>
            </svg>
        </button>
    ";
}

$reemplazos = [
    'ID_EVENTO'=> $evento['id_evento'],
    'VALORACIONES_HTML' => $htmlValoraciones,
    'TITULO' => e($evento['titulo']),
    'TITULO_ANTERIOR_HTML' => $tituloAnteriorHtml,
    'DESCRIPCION'=> e($evento['descripcion']),
    'DESCRIPCION_ANTERIOR_HTML' => $descripcionAnteriorHtml,
    'CATEGORIA' => e($datos['categoriaLegible']),
    'IMAGEN'=> e($datos['rutaArchivo']),
    'FECHA_HORA'=> e($datos['fechaHoraTexto']),
    'FECHA_HORA_ANTERIOR_HTML' => renderFechaHoraAnteriorLinea($evento, $cambiosVisibles),
    'ZONA'=> e($evento['zona']),
    'UBICACION_HTML' => renderUbicacionDetalleHtml($evento, $cambiosVisibles),
    'OTROS_CAMBIOS_HTML' => renderOtrosCambiosDetalleHtml($cambiosRestantes),
    'PANEL_REVISION_HTML' => $esRevisionAdmin ? renderPanelRevisionHtml($evento, getUserById($evento['id_usuario']) ?: [], $flashRevision) : '',
    'BODY_EXTRA' => trim(($esRevisionAdmin ? 'modo-revision' : '') . ($esAnfitrion ? ' vista-anfitrion' : '')),
    'VOLVER_URL' => $esRevisionAdmin ? 'index.php?slug=panelAdmin' : 'index.php?slug=landing',
    'VOLVER_TEXTO' => $esRevisionAdmin ? 'Volver al panel' : 'Volver a eventos',
    'PRECIO_CLASE'=> $datos['precioClase'],
    'PRECIO_TEXTO'=> e($datos['precioTexto']),
    'PRECIO_BOX_CLASE' => (stripos((string) $datos['precioTexto'], 'gratis') !== false) ? 'es-gratis' : '',
    'CARRUSEL_SLIDES' => $slidesHtml,
    'EYEBROW'        => e($eyebrow),
    'BOTON_FAVORITO_HTML' => $botonFavoritoHtml,
    'SUBTITULO'      => e($subtitulo),
    'ETIQUETAS_HTML' => $etiquetasHtml,
    'ORGANIZADOR_HTML'=> renderOrganizadorHtml($evento),
    'LINEUP_SECTION_HTML'=> renderLineupSectionHtml($evento),
    'CONTACTO_SECTION_HTML'=> renderContactoSectionHtml($evento),
    'INFO_UTIL_SECTION_HTML'=> renderInfoUtilSectionHtml($evento),
    'NOTA_SECTION_HTML' => renderNotaSectionHtml($evento),

    //eventos pasados
    'BOTON_CONFIRMAR_HTML' => $botonConfirmarHtml,
    'CONTACTO_TITULO' => e($contactoTitulo),
    'CONTACTO_DESC'   => e($contactoDesc),
    'CONTACTO_URL'    => e($contactoUrl),
    'CONTACTO_TEXTO'  => e($contactoTexto),
];

foreach ($reemplazos as $clave => $valor) {
    $tpl = setVarTPL($clave, (string) $valor, $tpl);
}

printTPL($tpl);