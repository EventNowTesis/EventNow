<?php

require_once __DIR__ . '/../models/Panel.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/mensajes.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

date_default_timezone_set('America/Argentina/Buenos_Aires');


if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

if ($_SESSION['rol'] !== 'anfitrion') {
    header("Location: index.php?slug=landing");
    exit;
}


$id_anfitrion = $_SESSION["id_usuario"];
$rolUsuario = $_SESSION["rol"] ?? 'visitante';

$usuario = getDatosAnfitrion($id_anfitrion);



$cantMsgsNoLeidos = contarMensajesNoLeidos($id_anfitrion, $rolUsuario);

$puntoVerdeHtml = "";

if ($cantMsgsNoLeidos > 0) {
    $puntoVerdeHtml = "<span style='
        background:#2E7D32;
        color:#fff;
        font-size:10px;
        font-weight:700;
        min-width:16px;
        height:16px;
        border-radius:8px;
        display:inline-flex;
        align-items:center;
        justify-content:center;
        padding:0 4px;
        position:absolute;
        top:-4px;
        right:-6px;
    '>" . ($cantMsgsNoLeidos > 99 ? '99+' : $cantMsgsNoLeidos) . "</span>";
}



$db = dbConnect();

$sql = "
    SELECT e.*, u.nombre_usuario
    FROM eventos e
    JOIN users u ON e.id_usuario = u.id_usuario
    WHERE e.id_usuario = ?
    ORDER BY e.fecha DESC
";

$stmt = mysqli_prepare($db, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_anfitrion);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$todosMisEventos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);



$fBuscar    = trim($_GET['q'] ?? '');
$fCategoria = $_GET['categoria'] ?? '';
$fZona      = $_GET['zona'] ?? '';
$fDesde     = $_GET['desde'] ?? '';
$fHasta     = $_GET['hasta'] ?? '';
$fEstado    = $_GET['estado'] ?? '';

$slugDetalle = 'detalleEvento';

function grupoDeEstado($estado)
{
    if ($estado === 'pendiente' || $estado === 'modificado') return 'pendientes';
    if ($estado === 'aprobado') return 'aprobados';
    if ($estado === 'rechazado') return 'rechazados';
    if ($estado === 'cancelado') return 'cancelados';
    return '';
}


$categorias = [];
$zonas = [];

foreach ($todosMisEventos as $ev) {
    if (!empty($ev['categoria'])) {
        $categorias[$ev['categoria']] = $ev['categoria'];
    }
    if (!empty($ev['localidad'])) {
        $zonas[$ev['localidad']] = $ev['localidad'];
    }
}

sort($categorias);
sort($zonas);


function armarOpciones($valores, $seleccionado, $textoTodos, $capitalizar = false)
{
    $html = "<option value=''>" . $textoTodos . "</option>";

    foreach ($valores as $valor) {
        $sel = ($valor === $seleccionado) ? " selected" : "";
        $v = htmlspecialchars($valor, ENT_QUOTES);
        $texto = $capitalizar ? htmlspecialchars(mb_strtoupper(mb_substr($valor, 0, 1)) . mb_substr($valor, 1), ENT_QUOTES) : $v;
        $html .= "<option value='" . $v . "'" . $sel . ">" . $texto . "</option>";
    }

    return $html;
}


$eventosFiltrados = [];

foreach ($todosMisEventos as $ev) {

    $fechaEv = substr($ev['fecha'] ?? '', 0, 10);

    if ($fBuscar !== '') {
        $texto = mb_strtolower(($ev['titulo'] ?? '') . ' ' . ($ev['descripcion'] ?? ''));
        if (mb_strpos($texto, mb_strtolower($fBuscar)) === false) continue;
    }

    if ($fCategoria !== '' && ($ev['categoria'] ?? '') !== $fCategoria) continue;

    if ($fZona !== '' && ($ev['localidad'] ?? '') !== $fZona) continue;

    if ($fDesde !== '' && $fechaEv < $fDesde) continue;

    if ($fHasta !== '' && $fechaEv > $fHasta) continue;

    if ($fEstado !== '' && grupoDeEstado($ev['estado'] ?? '') !== $fEstado) continue;

    $eventosFiltrados[] = $ev;
}



$htmlEventos = "";

$hoy = date('Y-m-d');



foreach ($eventosFiltrados as $evento) {

    $estado = $evento['estado'] ?? "";
    $yaPaso = substr($evento['fecha'] ?? '', 0, 10) < $hoy;
    $idEvento = (int) $evento['id_evento'];



    $claseEstado = 'estado-borrador';
    $textoEstado = ucfirst($estado);

    if ($estado === 'aprobado') {
        $claseEstado = $yaPaso ? 'estado-finalizado' : 'estado-publicado';
        $textoEstado = $yaPaso ? 'Finalizado' : 'Publicado';
    } elseif ($estado === 'pendiente') {
        $claseEstado = 'estado-revision';
        $textoEstado = 'En revisión';
    } elseif ($estado === 'modificado') {
        $claseEstado = 'estado-revision';
        $textoEstado = 'En revisión (modificado)';
    } elseif ($estado === 'rechazado') {
        $claseEstado = 'estado-rechazado';
        $textoEstado = 'Rechazado';
    } elseif ($estado === 'cancelado') {
        $claseEstado = 'estado-cancelado';
        $textoEstado = 'Cancelado';
    }



    $titulo = htmlspecialchars($evento['titulo'] ?? '', ENT_QUOTES, 'UTF-8');

    if (!empty($evento['imagen'])) {
        $htmlImagen = '<img src="uploads/' . htmlspecialchars($evento['imagen'], ENT_QUOTES, 'UTF-8') . '" alt="' . $titulo . '">';
    } else {
        $htmlImagen = '<div class="evento-sin-imagen"><i class="fa-regular fa-image"></i></div>';
    }



    $htmlHora = '';

    if (!empty($evento['hora'])) {
        $htmlHora = '<span class="meta-sep">·</span><span><i class="fa-regular fa-clock"></i> ' . htmlspecialchars(substr($evento['hora'], 0, 5)) . ' h</span>';
    }

    $lugar = $evento['localidad'] ?? '';
    if ($lugar === '') $lugar = $evento['zona'] ?? '';



    $bloqueAviso = '';
    $textoEditar = 'Editar evento';

    if ($estado === 'rechazado') {

        $motivo = $evento['motivo_rechazo'] ?? '';

        $bloqueAviso = '<div class="evento-aviso"><strong>Motivo del rechazo:</strong> ' . htmlspecialchars($motivo !== '' ? $motivo : 'Sin motivo indicado') . '</div>';

        $textoEditar = 'Corregir y reenviar';
    }

    if ($estado === 'cancelado') {

        $motivo = $evento['motivo_cancelacion'] ?? '';
        $fechaCanc = $evento['fecha_cancelacion'] ?? '';

        $bloqueAviso = '<div class="evento-aviso"><strong>Motivo de cancelación:</strong> ' . htmlspecialchars($motivo !== '' ? $motivo : 'Sin motivo indicado');

        if ($fechaCanc !== '') {
            $bloqueAviso .= '<small>Cancelado el ' . htmlspecialchars($fechaCanc) . '</small>';
        }

        $bloqueAviso .= '</div>';
    }


    if ($estado === 'cancelado') {

        $botones = '<a href="index.php?slug=' . $slugDetalle . '&id=' . $idEvento . '" class="btn-editar"> Ver detalle</a>';

    } elseif ($yaPaso) {

        $botones = '<span class="btn-editar deshabilitado"><i class="fa-solid fa-pencil"></i> ' . $textoEditar . '</span>'
                 . '<span class="btn-cancelar deshabilitado" title="Este evento ya pasó"><i class="fa-solid fa-xmark"></i> Cancelar</span>';

    } else {

        $botones = '<a href="index.php?slug=editar_evento&id=' . $idEvento . '" class="btn-editar"><i class="fa-solid fa-pencil"></i> ' . $textoEditar . '</a>'
                 . '<a href="index.php?slug=cancelar_evento&id=' . $idEvento . '" class="btn-cancelar"><i class="fa-solid fa-xmark"></i> Cancelar</a>';
    }



    $htmlEventos .= '
        <article class="evento">
            <span class="evento-badge">' . htmlspecialchars(ucfirst($evento['categoria'] ?? '')) . '</span>
            ' . $htmlImagen . '
            <div class="evento-contenido">
                <span class="estado ' . $claseEstado . '"><span class="estado-punto"></span>' . htmlspecialchars($textoEstado) . '</span>
                <h2>' . $titulo . '</h2>
                <p class="evento-descripcion">' . htmlspecialchars($evento['descripcion'] ?? '') . '</p>
                ' . $bloqueAviso . '
                <p class="evento-lugar"><i class="fa-solid fa-location-dot"></i> ' . htmlspecialchars($lugar) . '</p>
            </div>
            <div class="botones-evento">' . $botones . '</div>
        </article>
    ';
}


if ($htmlEventos === "") {
    $htmlEventos = '<div class="empty-state"><i class="fa-regular fa-calendar-xmark"></i><h3>No se encontraron eventos</h3><p>Probá con otros filtros o creá un evento nuevo.</p></div>';
}



$tpl = loadTPL("panel", "Panel");

$tpl = cargarNavbarComun($tpl, $id_anfitrion, $rolUsuario);

$tpl = setVarTPL("USUARIO_ROL", htmlspecialchars(ucfirst($usuario['rol'] ?? '')), $tpl);
$tpl = setVarTPL("USUARIO_EMAIL", htmlspecialchars($usuario['email'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_USERNAME", htmlspecialchars($usuario['nombre_usuario'] ?? ''), $tpl);

$tpl = setVarTPL("FILTRO_Q", htmlspecialchars($fBuscar, ENT_QUOTES), $tpl);
$tpl = setVarTPL("OPCIONES_CATEGORIA", armarOpciones($categorias, $fCategoria, "Todas las categorías", true), $tpl);
$tpl = setVarTPL("OPCIONES_ZONA", armarOpciones($zonas, $fZona, "Todas las localidades"), $tpl);

$opcionesEstado = "<option value=''>Todos los estados</option>";

$estadosDisponibles = [
    'pendientes' => 'Pendientes',
    'aprobados'  => 'Aprobados',
    'rechazados' => 'Rechazados',
    'cancelados' => 'Cancelados'
];

foreach ($estadosDisponibles as $valor => $texto) {
    $sel = ($valor === $fEstado) ? " selected" : "";
    $opcionesEstado .= "<option value='" . $valor . "'" . $sel . ">" . $texto . "</option>";
}

$tpl = setVarTPL("OPCIONES_ESTADO", $opcionesEstado, $tpl);

$tpl = setVarTPL("FILTRO_DESDE", htmlspecialchars($fDesde, ENT_QUOTES), $tpl);
$tpl = setVarTPL("FILTRO_HASTA", htmlspecialchars($fHasta, ENT_QUOTES), $tpl);

$tpl = setVarTPL("EVENTOS", $htmlEventos, $tpl);

$tpl = setVarTPL("PUNTO_VERDE_MSGS", $puntoVerdeHtml, $tpl);

printTPL($tpl);

?>