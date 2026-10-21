<?php

require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php?slug=landing");
    exit;
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] !== "POST" && isset($_SESSION['flash_revision'])) {
    $flash = $_SESSION['flash_revision'];
    unset($_SESSION['flash_revision']);
    $estiloFlash = !empty($flash['ok']) ? "" : "style='background-color:#FCE8E6; color:#C62828; border-color:#FAD2CF;'";
    $msg = "<p class='msg' " . $estiloFlash . ">" . htmlspecialchars($flash['msg'] ?? '') . "</p>";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";
    $accionPedida = $accion;
    $admin_pass = $_POST["admin_pass"] ?? "";
    $motivo = trim($_POST["motivo"] ?? "");
    $esAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

    $idSiguiente = (!empty($_POST['siguiente']) && isset($_POST['id_evento']))
        ? getSiguienteEventoPendiente((int) $_POST['id_evento'])
        : null;

    $ok = false;
    $texto = "Acción no válida.";
    $estadoNuevo = null;

    if ($accion === "rechazar") {
        $adminActual = getUserById($_SESSION['id_usuario']);

        if ($motivo === "") {
            $texto = "Tenés que escribir el motivo del rechazo.";
            $accion = "";
        } elseif (!$adminActual || empty($admin_pass) || !password_verify($admin_pass, $adminActual['password'])) {
            $texto = "Contraseña de administrador incorrecta o vacía. Operación cancelada.";
            $accion = "";
        }
    }

    if ($accion === "aprobar" && isset($_POST["id_evento"])) {
        $id_evento = intval($_POST["id_evento"]);

        $eventoInfo = getEventoById($id_evento);

        $cambiosAprobados = $eventoInfo ? getCambiosPendientesPorCampo($eventoInfo) : [];

        cambiarEstadoEvento($id_evento, "aprobado");

        if ($eventoInfo && isset($eventoInfo['id_usuario'])) {
            $tituloEvento = $eventoInfo['titulo'] ?? 'tu evento';
            crearNotificacion(
                $eventoInfo['id_usuario'],
                "¡Buenas noticias! Tu evento \"{$tituloEvento}\" fue aprobado y publicado.",
                "index.php?slug=panel"
            );
        }

        if ($eventoInfo && !empty($cambiosAprobados)) {
            notificarCambiosEventoAprobado($eventoInfo, $cambiosAprobados);
        }

        $ok = true;
        $texto = "Evento aprobado correctamente.";
    }

    if ($accion === "rechazar" && isset($_POST["id_evento"])) {
        $id_evento = intval($_POST["id_evento"]);

        $eventoInfo = getEventoById($id_evento);

        if (rechazarEvento($id_evento, $motivo)) {
            if ($eventoInfo && isset($eventoInfo['id_usuario'])) {
                $tituloEvento = $eventoInfo['titulo'] ?? 'tu evento';
                crearNotificacion(
                    $eventoInfo['id_usuario'],
                    "Tu evento \"{$tituloEvento}\" fue rechazado. Motivo: {$motivo}",
                    "index.php?slug=panel#rechazados"
                );
            }
            $ok = true;
            $texto = "Evento rechazado. El anfitrión puede verlo, corregirlo y reenviarlo.";
        } else {
            $texto = "Error al rechazar el evento.";
        }
    }

    if ($accion === "toggle_desactivar" && isset($_POST["id_usuario"])) {
        $id_usuario = intval($_POST["id_usuario"]);

        if ($id_usuario === (int) $_SESSION['id_usuario']) {
            $texto = "No podés desactivar tu propia cuenta de administrador.";
        } else {
            $usuarioObj = getUserById($id_usuario);
            $estadoActual = $usuarioObj['estado'] ?? 'activo';
            $nuevoEstado = ($estadoActual === 'desactivado') ? 'activo' : 'desactivado';

            if (actualizarEstadoUsuario($id_usuario, $nuevoEstado)) {
                $ok = true;
                $estadoNuevo = $nuevoEstado;
                $texto = "Usuario actualizado a: {$nuevoEstado}.";
            } else {
                $texto = "Error al actualizar el estado en la base de datos.";
            }
        }
    }

    if (!$esAjax && !empty($_POST['siguiente'])) {
        $_SESSION['flash_revision'] = ['ok' => $ok, 'msg' => $texto];

        if (!$ok && isset($_POST['id_evento'])) {
            $destino = "index.php?slug=detalleEvento&id=" . (int) $_POST['id_evento'];
        } elseif ($ok && $idSiguiente) {
            $destino = "index.php?slug=detalleEvento&id=" . (int) $idSiguiente;
        } else {
            $destino = "index.php?slug=panelAdmin";
        }

        header("Location: " . $destino);
        exit;
    }

    if ($esAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'msg' => $texto, 'accion' => $accionPedida, 'estado' => $estadoNuevo]);
        exit;
    }

    $estiloError = "style='background-color:#FCE8E6; color:#C62828; border-color:#FAD2CF;'";
    $msg = "<p class='msg' " . ($ok ? "" : $estiloError) . ">" . htmlspecialchars($texto) . "</p>";
}

$eventosPendientes = getEventosPendientes();
$cantidadPendientes = count($eventosPendientes);

$svgBase = "xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' aria-hidden='true'";

$iconos = [
    'nuevo' => [
        'caja'  => "<svg {$svgBase} width='16' height='16'><path d='M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z'></path><path d='M13 5v2'></path><path d='M13 17v2'></path><path d='M13 11v2'></path></svg>",
        'badge' => "<svg {$svgBase} width='10' height='10'><path d='M11.017 2.814a1 1 0 0 1 1.966 0l1.051 5.558a2 2 0 0 0 1.594 1.594l5.558 1.051a1 1 0 0 1 0 1.966l-5.558 1.051a2 2 0 0 0-1.594 1.594l-1.051 5.558a1 1 0 0 1-1.966 0l-1.051-5.558a2 2 0 0 0-1.594-1.594l-5.558-1.051a1 1 0 0 1 0-1.966l5.558-1.051a2 2 0 0 0 1.594-1.594z'></path></svg>",
    ],
    'cambios' => [
        'caja'  => "<svg {$svgBase} width='16' height='16'><path d='M12.5 22H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v9.5'></path><path d='M14 2v4a2 2 0 0 0 2 2h4'></path><path d='M13.378 15.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z'></path></svg>",
        'badge' => "<svg {$svgBase} width='10' height='10'><path d='M12.5 22H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v9.5'></path><path d='M14 2v4a2 2 0 0 0 2 2h4'></path><path d='M13.378 15.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z'></path></svg>",
    ],
];
$iconoFlecha = "<svg {$svgBase} width='14' height='14'><path d='M5 12h14'></path><path d='m12 5 7 7-7 7'></path></svg>";

$meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
$formatearFecha = function ($fecha) use ($meses) {
    $ts = strtotime((string) $fecha);
    if (!$ts) {
        return e((string) $fecha);
    }
    return date('d', $ts) . ' ' . $meses[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
};

$htmlFilasEventos = "";
if (empty($eventosPendientes)) {
    $htmlFilasEventos = "<tr><td colspan='5' style='text-align:center;'>No hay eventos pendientes de revisión.</td></tr>";
} else {
    foreach ($eventosPendientes as $e) {

        $cambios = getCambiosPendientesPorCampo($e);

        if ($e['estado'] === 'modificado') {
            $textoEstado = 'Modificado';
            $ico = $iconos['cambios'];
        } elseif (!empty($e['ultimo_cambio'])) {
            $textoEstado = 'Con cambios';
            $ico = $iconos['cambios'];
        } else {
            $textoEstado = 'Nuevo';
            $ico = $iconos['nuevo'];
        }

        // Subtítulo: categoría y, si corresponde, cuántos cambios hay
        $subtitulo = e((string) ($e['categoria'] ?? ''));
        if (!empty($cambios)) {
            $n = count($cambios);
            $textoCambios = $n . ($n === 1 ? ' cambio' : ' cambios') . ' desde la última revisión';
            $subtitulo = ($subtitulo !== '' ? $subtitulo . ' · ' : '') . $textoCambios;
        } elseif (!empty($e['ultimo_cambio'])) {
            $subtitulo = ($subtitulo !== '' ? $subtitulo . ' · ' : '') . e($e['ultimo_cambio']);
        }

        $tituloHtml = marcarCambio('titulo', $e['titulo'], $cambios);
        $fechaHtml  = isset($cambios['fecha'])
            ? marcarCambio('fecha', $e['fecha'], $cambios)
            : $formatearFecha($e['fecha']);
        $horaTxt    = substr((string) $e['hora'], 0, 5);
        $horaHtml   = isset($cambios['hora'])
            ? marcarCambio('hora', $horaTxt, $cambios)
            : e($horaTxt) . ' h';

        $idEvento = (int) $e['id_evento'];

        $htmlFilasEventos .= "<tr class='fila-pendiente' id='evento-{$idEvento}'>
            <td>
                <div class='evento-cell'>
                    <span class='evento-icono'>{$ico['caja']}</span>
                    <div>
                        <div class='evento-titulo'>{$tituloHtml}</div>
                        <div class='evento-sub'>{$subtitulo}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class='celda-doble'>
                    <span>" . htmlspecialchars($e['email']) . "</span>
                    <small>Anfitrión</small>
                </div>
            </td>
            <td>
                <div class='celda-doble'>
                    <span>{$fechaHtml}</span>
                    <small class='hora'>{$horaHtml}</small>
                </div>
            </td>
            <td><span class='badge-evento'>{$ico['badge']}{$textoEstado}</span></td>
            <td class='td-accion'>
                <a class='btn btn-secondary btn-detalle' href='index.php?slug=detalleEvento&id={$idEvento}'>Ver detalles {$iconoFlecha}</a>
            </td>
        </tr>";
    }
}

$usuarios = getTodosLosUsuarios();

$htmlFilasUsuarios = "";
if (empty($usuarios)) {
    $htmlFilasUsuarios = "<tr><td colspan='6' style='text-align:center;'>No hay usuarios registrados.</td></tr>";
} else {
    foreach ($usuarios as $u) {
        $estadoUsuario = $u['estado'] ?? 'activo';
        $textoBoton = ($estadoUsuario === 'desactivado') ? 'Activar' : 'Desactivar';
        $estiloBoton = ($estadoUsuario === 'desactivado') ? 'background-color: #2E7D32; color: white;' : '';

        $htmlFilasUsuarios .= "<tr class='fila-usuario' data-rol='" . htmlspecialchars($u['rol']) . "' data-estado='" . htmlspecialchars($estadoUsuario) . "'>
            <td>" . htmlspecialchars((string)$u['id_usuario']) . "</td>
            <td>" . htmlspecialchars($u['email']) . "</td>
            <td>" . htmlspecialchars($u['rol']) . "</td>
            <td><span class='badge-estado' style='padding: 2px 8px; border-radius: 4px; font-size: 12px; background: " . ($estadoUsuario === 'desactivado' ? '#FCE8E6; color:#C62828;' : '#E6F4EA; color:#137333;') . "'>" . htmlspecialchars($estadoUsuario) . "</span></td>
            <td>" . htmlspecialchars($u['fechaRegistro'] ?? '-') . "</td>
            <td>
                <form method='POST' action='index.php?slug=panelAdmin' class='form-ajax' style='display:inline;'>
                    <input type='hidden' name='id_usuario' value='" . $u['id_usuario'] . "'>
                    <input type='hidden' name='accion' value='toggle_desactivar'>
                    <button type='submit' class='btn-rechazar' style='" . $estiloBoton . "'>" . $textoBoton . "</button>
                </form>
            </td>
        </tr>";
    }
}

$tpl = loadTPL("panelAdmin", "Panel Administrador");

$tpl = setVarTPL("EVENTOS_PENDIENTES", $htmlFilasEventos, $tpl);
$tpl = setVarTPL("TABLA_USUARIOS", $htmlFilasUsuarios, $tpl);
$tpl = setVarTPL("CANTIDAD_PENDIENTES", $cantidadPendientes, $tpl);
$tpl = setVarTPL("MSG", $msg, $tpl);

printTPL($tpl);

?>