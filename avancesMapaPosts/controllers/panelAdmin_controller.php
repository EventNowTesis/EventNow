<?php

require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: index.php?slug=landing");
    exit;
}

$msg = "";

// ==========================================
// PROCESAR ACCIONES (POST)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";
    $admin_pass = $_POST["admin_pass"] ?? "";

    if ($accion === "rechazar" || $accion === "eliminar_usuario") {
        $adminActual = getUserById($_SESSION['id_usuario']);

        if (!$adminActual || !password_verify($admin_pass, $adminActual['password'])) {
            $msg = "<p class='msg' style='background-color:#FCE8E6; color:#C62828; border-color:#FAD2CF;'>Contraseña de administrador incorrecta. Operación cancelada.</p>";
            $accion = ""; 
        }
    }

    if ($accion === "aprobar" && isset($_POST["id_evento"])) {
        $id_evento = intval($_POST["id_evento"]);
        cambiarEstadoEvento($id_evento, "aprobado");
        $msg = "<p class='msg'>Evento aprobado correctamente.</p>";
    }

    if ($accion === "rechazar" && isset($_POST["id_evento"])) {
        $id_evento = intval($_POST["id_evento"]);
        
        if (eliminarEventoPorCompleto($id_evento)) {
            $msg = "<p class='msg'>Evento rechazado y eliminado de la base de datos.</p>";
        } else {
            $msg = "<p class='msg' style='background-color:#FCE8E6; color:#C62828; border-color:#FAD2CF;'>Error al eliminar el evento.</p>";
        }
    }

    if ($accion === "eliminar_usuario" && isset($_POST["id_usuario"])) {
        $id_usuario = intval($_POST["id_usuario"]);

        if ($id_usuario === $_SESSION['id_usuario']) {
            $msg = "<p class='msg' style='background-color:#FCE8E6; color:#C62828; border-color:#FAD2CF;'>No podés eliminar tu propia cuenta de administrador.</p>";
        } else {
            if (eliminarUsuario($id_usuario)) {
                $msg = "<p class='msg'>Usuario eliminado correctamente.</p>";
            } else {
                $msg = "<p class='msg' style='background-color:#FCE8E6; color:#C62828; border-color:#FAD2CF;'>Error al eliminar el usuario.</p>";
            }
        }
    }
}

// ==========================================
// RENDERIZAR TABLA DE EVENTOS PENDIENTES
// ==========================================
$eventosPendientes = getEventosPendientes();

$htmlFilasEventos = "";
if (empty($eventosPendientes)) {
    $htmlFilasEventos = "<tr><td colspan='7' style='text-align:center;'>No hay eventos pendientes de revisión.</td></tr>";
} else {
    foreach ($eventosPendientes as $e) {
        $htmlFilasEventos .= "<tr>
            <td>" . htmlspecialchars($e['titulo']) . "</td>
            <td>" . htmlspecialchars($e['email']) . "</td>
            <td>" . htmlspecialchars($e['categoria']) . "</td>
            <td>" . htmlspecialchars($e['fecha']) . " " . htmlspecialchars($e['hora']) . "</td>
            <td>" . htmlspecialchars($e['zona']) . "</td>
            <td><a href='uploads/" . htmlspecialchars($e['imagen']) . "' target='_blank' class='link-archivo'>Ver archivo</a></td>
            <td>
                <!-- Formulario para Aprobar -->
                <form method='POST' action='index.php?slug=panelAdmin' style='display:inline;'>
                    <input type='hidden' name='id_evento' value='" . $e['id_evento'] . "'>
                    <input type='hidden' name='accion' value='aprobar'>
                    <button type='submit' class='btn-aprobar'>Aprobar</button>
                </form>

                <!-- Formulario para Rechazar (Borra de la BD pidiendo contraseña) -->
                <form method='POST' action='index.php?slug=panelAdmin' style='display:inline;' onsubmit='return solicitarPassword(this, \"¿Confirmás el rechazo y eliminación total de este evento?\");'>
                    <input type='hidden' name='id_evento' value='" . $e['id_evento'] . "'>
                    <input type='hidden' name='admin_pass' value=''>
                    <input type='hidden' name='accion' value='rechazar'>
                    <button type='submit' class='btn-rechazar'>Rechazar</button>
                </form>
            </td>
        </tr>";
    }
}

// ==========================================
// RENDERIZAR TABLA DE USUARIOS
// ==========================================
$usuarios = getTodosLosUsuarios();

$htmlFilasUsuarios = "";
if (empty($usuarios)) {
    $htmlFilasUsuarios = "<tr><td colspan='5' style='text-align:center;'>No hay usuarios registrados.</td></tr>";
} else {
    foreach ($usuarios as $u) {
        $htmlFilasUsuarios .= "<tr>
            <td>" . htmlspecialchars((string)$u['id_usuario']) . "</td>
            <td>" . htmlspecialchars($u['email']) . "</td>
            <td>" . htmlspecialchars($u['rol']) . "</td>
            <td>" . htmlspecialchars($u['fechaRegistro']) . "</td>
            <td>
                <form method='POST' action='index.php?slug=panelAdmin' style='display:inline;' onsubmit='return solicitarPassword(this, \"¿Estás seguro de eliminar este usuario?\");'>
                    <input type='hidden' name='id_usuario' value='" . $u['id_usuario'] . "'>
                    <input type='hidden' name='admin_pass' value=''>
                    <input type='hidden' name='accion' value='eliminar_usuario'>
                    <button type='submit' class='btn-rechazar'>Eliminar</button>
                </form>
            </td>
        </tr>";
    }
}

// ==========================================
// CARGAR PLANTILLA Y MOSTRAR
// ==========================================
$tpl = loadTPL("panelAdmin", "Panel Administrador");

$tpl = setVarTPL("EVENTOS_PENDIENTES", $htmlFilasEventos, $tpl);
$tpl = setVarTPL("TABLA_USUARIOS", $htmlFilasUsuarios, $tpl);
$tpl = setVarTPL("MSG", $msg, $tpl);

printTPL($tpl);

?>