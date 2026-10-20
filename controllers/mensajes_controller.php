<?php
require_once __DIR__ . '/../models/mensajes.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

$id_usuario = $_SESSION["id_usuario"];
$rolUsuario = $_SESSION["rol"] ?? 'visitante';
$esAnfitrion = ($rolUsuario === 'anfitrion' || $rolUsuario === 'admin');

function e($valor) {
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function etiquetaEvento($c) {
    return $c['id_evento'] === null ? 'Consulta directa' : 'Evento: ' . $c['nombre_evento'];
}

function ultimoMensajeResumen($id_chat, $id_usuario) {
    $mensajes = obtenerMensajesChat($id_chat);
    if (empty($mensajes)) {
        return '';
    }

    $ultimo = end($mensajes); //los mensajes vienen del más viejo al más nuevo
    $texto = trim(preg_replace('/\s+/', ' ', $ultimo['mensaje']));
    $texto = mb_strimwidth($texto, 0, 45, '…', 'UTF-8');
    $prefijo = ($ultimo['id_remitente'] == $id_usuario) ? '<b>Vos:</b> ' : '';

    return "<span class='chat-item-ultimo'>{$prefijo}" . e($texto) . "</span>";
}

//chat de evento esepcificvo
if (isset($_GET['id_evento'])) {
    $id_evento = intval($_GET['id_evento']);

    if (isset($_GET['anfitrion'])) {
        $id_anfitrion = intval($_GET['anfitrion']);
    } else {
        $id_anfitrion = obtenerAnfitrionDeEvento($id_evento);
    }

    if (!$id_anfitrion || $id_anfitrion == $id_usuario) {
        header("Location: index.php?slug=landing");
        exit;
    }

    $id_chat = obtenerOCrearChat($id_evento, $id_usuario, $id_anfitrion);
    header("Location: index.php?slug=mensajes&chat=" . $id_chat);
    exit;
}


//chat directo con el anfi
if (isset($_GET['anfitrion']) && !isset($_GET['id_evento'])) {
    $id_otro = intval($_GET['anfitrion']);

    if (!$id_otro || $id_otro == $id_usuario || !usuarioExiste($id_otro)) {
        header("Location: index.php?slug=landing");
        exit;
    }

    $id_chat = obtenerOCrearChat(null, $id_usuario, $id_otro);
    header("Location: index.php?slug=mensajes&chat=" . $id_chat);
    exit;
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['enviar_mensaje'])) {
    $id_chat = intval($_POST['id_chat']);
    $texto = trim($_POST['texto_mensaje']);

    //el js manda este header para ver si viene de fetch
    $esAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'fetch';

    $enviado = false;
    if (!empty($texto) && usuarioPerteneceAChat($id_chat, $id_usuario)) {
        enviarMensaje($id_chat, $id_usuario, $texto);
        $enviado = true;
    }

    if ($esAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'exito' => $enviado,
            'texto' => $texto,
            'fecha' => date('Y-m-d H:i:s'),
            'mensaje' => $enviado ? '' : 'No se pudo enviar el mensaje.'
        ]);
        exit;
    }

    header("Location: index.php?slug=mensajes&chat=" . $id_chat);
    exit;
}

if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['chat'])) {
    $id_chat = intval($_GET['chat']);
    eliminarChatParaMi($id_chat, $id_usuario);
    header("Location: index.php?slug=mensajes");
    exit;
}


$todosLosChats = obtenerChatsUsuario($id_usuario);

$totalNoLeidos = 0;
foreach ($todosLosChats as $c) {
    $totalNoLeidos += (int) $c['no_leidos'];
}
$textoTotalNoLeidos = $totalNoLeidos > 0
    ? "Tenés {$totalNoLeidos} mensaje" . ($totalNoLeidos > 1 ? "s" : "") . " sin leer."
    : "";


$hayDirectos = false;
$eventosParaFiltro = [];
foreach ($todosLosChats as $c) {
    if ($c['id_evento'] === null) {
        $hayDirectos = true;
        continue;
    }
    $idEv = $c['id_evento'];
    if (!isset($eventosParaFiltro[$idEv])) {
        $eventosParaFiltro[$idEv] = $c['nombre_evento'];
    }
}


$filtroEvento = isset($_GET['filtro_evento']) ? intval($_GET['filtro_evento']) : 0;
$busqueda = trim($_GET['buscar'] ?? '');

$sufijoFiltro = "";
if ($filtroEvento !== 0) {
    $sufijoFiltro .= "&filtro_evento=" . $filtroEvento;
}
if ($busqueda !== '') {
    $sufijoFiltro .= "&buscar=" . urlencode($busqueda);
}

$chats = $todosLosChats;

if ($filtroEvento === -1) {
    $chats = array_filter($chats, function ($c) {
        return $c['id_evento'] === null;
    });
} elseif ($filtroEvento > 0) {
    $chats = array_filter($chats, function ($c) use ($filtroEvento) {
        return (int) $c['id_evento'] === $filtroEvento;
    });
}

if ($busqueda !== '') {
    $chats = array_filter($chats, function ($c) use ($busqueda) {
        return stripos($c['nombre_otro'], $busqueda) !== false
            || stripos($c['nombre_evento'] ?? '', $busqueda) !== false;
    });
}

$chats = array_values($chats);

$chatActivoId = isset($_GET['chat']) ? intval($_GET['chat']) : ($chats[0]['id_chat'] ?? null);


$mensajesChat = [];
$chatSeleccionado = null;
foreach ($todosLosChats as $c) {
    if ((int) $c['id_chat'] === (int) $chatActivoId) {
        $chatSeleccionado = $c;
        break;
    }
}
if ($chatSeleccionado) {
    marcarMensajesComoLeidos($chatActivoId, $id_usuario);
    $mensajesChat = obtenerMensajesChat($chatActivoId);
}


$htmlListaChats = "";
if (empty($chats)) {
    $htmlListaChats = "<p class='chat-empty-list'>No se encontraron conversaciones.</p>";
} else {
    foreach ($chats as $c) {
        $claseActivo = ((int) $c['id_chat'] === (int) $chatActivoId) ? ' chat-item-activo' : '';

        $badgeHtml = "";
        if ((int) $c['no_leidos'] > 0) {
            $cantidad = $c['no_leidos'] > 99 ? '99+' : $c['no_leidos'];
            $badgeHtml = "<span class='chat-item-badge'>{$cantidad}</span>";
        }

        $ultimoHtml = ultimoMensajeResumen($c['id_chat'], $id_usuario);

        $htmlListaChats .= "<a href='index.php?slug=mensajes&chat=" . $c['id_chat'] . $sufijoFiltro . "' class='chat-item-link{$claseActivo}'>
            <div class='chat-item-info'>
                <strong>" . e($c['nombre_otro']) . "</strong>
                <small class='chat-item-evento'>" . e(etiquetaEvento($c)) . "</small>
                {$ultimoHtml}
            </div>
            {$badgeHtml}
        </a>";
    }
}


$htmlFiltroEventos = "<option value='0'>Todos los eventos</option>";
if ($hayDirectos) {
    $sel = ($filtroEvento === -1) ? "selected" : "";
    $htmlFiltroEventos .= "<option value='-1' {$sel}>Consultas directas (sin evento)</option>";
}
foreach ($eventosParaFiltro as $idEv => $nombreEv) {
    $seleccionado = ($filtroEvento === (int) $idEv) ? "selected" : "";
    $htmlFiltroEventos .= "<option value='" . $idEv . "' {$seleccionado}>" . e($nombreEv) . "</option>";
}


$htmlMensajes = "";
foreach ($mensajesChat as $m) {
    $esMio = ($m['id_remitente'] == $id_usuario);
    $clase = $esMio ? 'mensaje-propio' : 'mensaje-otro';

    $htmlMensajes .= "<div class='mensaje-fila {$clase}'>
        <div class='mensaje-burbuja {$clase}'>
            <p class='mensaje-texto'>" . e($m['mensaje']) . "</p>
            <small class='mensaje-fecha'>" . e($m['fecha_envio']) . "</small>
        </div>
    </div>";
}


$chatActivoHtml = "";

if (!$chatSeleccionado) {
    $chatActivoHtml = "<div class='chat-blank-state'>
        <i class='fa-solid fa-comments'></i>
        <p>Seleccioná una conversación de la izquierda para comenzar a chatear.</p>
    </div>";
} else {
    $chatActivoHtml = "
        <div class='chat-active-header'>
            <div class='chat-recipient-info'>
                <h3>" . e($chatSeleccionado['nombre_otro']) . "</h3>
                <span class='chat-event-tag'>" . e(etiquetaEvento($chatSeleccionado)) . "</span>
            </div>
        </div>

        <div class='chat-messages-box'>
            {$htmlMensajes}
        </div>

        <form method='POST' action='index.php?slug=mensajes' class='chat-input-wrapper'>
            <input type='hidden' name='id_chat' value='" . $chatActivoId . "'>
            <input type='text' name='texto_mensaje' placeholder='Escribí tu mensaje...' required autocomplete='off'>
            <button type='submit' name='enviar_mensaje' class='btn-send-chat'>
                <i class='fa-solid fa-comment'></i>
            </button>
        </form>
    ";
}


$tpl = loadTPL("mensajes", "Mensajes");
$tpl = cargarNavbarComun($tpl, $id_usuario, $rolUsuario);

$tpl = setVarTPL("LISTA_CHATS", $htmlListaChats, $tpl);
$tpl = setVarTPL("CHAT_ACTIVO_HTML", $chatActivoHtml, $tpl);
$tpl = setVarTPL("FILTRO_EVENTOS_HTML", $htmlFiltroEventos, $tpl);
$tpl = setVarTPL("TOTAL_NO_LEIDOS_TEXTO", $textoTotalNoLeidos, $tpl);

printTPL($tpl);
?>