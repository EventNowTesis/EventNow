<?php

require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . '/../models/Username.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

$id_usuario = (int) $_SESSION["id_usuario"];
$rolUsuario = $_SESSION["rol"] ?? '';

if (!in_array($rolUsuario, ['visitante', 'anfitrion'], true)) {
    echo "No tenés permiso para acceder a esta página.";
    exit;
}

function cfgEscapar($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['txt_username']) && $rolUsuario === 'anfitrion') {

    $nuevo = ltrim(trim($_POST['txt_username']), '@');
    $error = '';

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $nuevo)) {
        $error = 'El nombre de usuario debe tener entre 3 y 20 caracteres: solo letras, números y guion bajo (_), sin espacios.';
    } elseif (!usernameDisponible($nuevo, $id_usuario)) {
        $error = 'Ese nombre de usuario ya está en uso. Probá con otro.';
    } elseif (!actualizarUsername($id_usuario, $nuevo)) {
        $error = 'No pudimos guardar el nombre de usuario. Probá de nuevo con otro.';
    }

    if ($error === '') {
        $_SESSION['cfg_flash'] = [
            'tipo'  => 'ok',
            'texto' => '¡Listo! Tu nombre de usuario ahora es @' . $nuevo . '.',
            'link'  => ($rolUsuario === 'anfitrion')
        ];
    } else {
        $_SESSION['cfg_flash'] = ['tipo' => 'error', 'texto' => $error, 'link' => false];
    }

    header("Location: index.php?slug=Configuracionvisitante");
    exit;
}


$usuario = getUserById($id_usuario);

if (!$usuario) {
    header("Location: index.php?slug=login");
    exit;
}

$ubicacion = getUbicacionUsuario($id_usuario);
$usernameActual = obtenerUsername($id_usuario);

$flashHtml = '';
if (!empty($_SESSION['cfg_flash'])) {
    $flash = $_SESSION['cfg_flash'];
    unset($_SESSION['cfg_flash']);

    $icono = $flash['tipo'] === 'ok' ? 'fa-circle-check' : 'fa-circle-exclamation';
    $extra = !empty($flash['link'])
        ? ' <a href="index.php?slug=posts">Crear un evento</a>'
        : '';

    $flashHtml =
        '<p class="cfg-flash ' . cfgEscapar($flash['tipo']) . '">'
        . '<i class="fa-solid ' . $icono . '"></i> '
        . cfgEscapar($flash['texto']) . $extra
        . '</p>';
}

$avisoHtml = '';

if ($usernameActual === '' && $rolUsuario === 'anfitrion') {
    $avisoHtml =
        '<div class="cfg-aviso">'
        . '<span class="cfg-aviso-icono"><i class="fa-solid fa-at"></i></span>'
        . '<div>'
        . '<strong>Primero elegí tu nombre de usuario</strong>'
        . '<p>Para crear eventos necesitás un nombre de usuario único. '
        . 'Es el nombre con el que te encuentran tus seguidores y el que aparece en tu perfil (por ejemplo, <b>@tunombre</b>). '
        . 'Escribilo en el cuadro de abajo y guardalo.</p>'
        . '</div>'
        . '</div>';
}

$claseUbicacion = ($rolUsuario === 'visitante') ? '' : 'cfg-oculto';

$claseUsername = ($rolUsuario === 'anfitrion') ? '' : 'cfg-oculto';

$tpl = loadTPL("Configuracionvisitante", "Configuración | EventNow");
$tpl = cargarNavbarComun($tpl, $id_usuario, $rolUsuario);
$tpl = setVarTPL("USUARIO_NOMBRE", cfgEscapar($usuario['display_name'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_LAT", cfgEscapar($ubicacion['lat'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_LON", cfgEscapar($ubicacion['lon'] ?? ''), $tpl);
$tpl = setVarTPL("USUARIO_UBICACION_TEXTO", cfgEscapar($ubicacion['direccion_texto'] ?? ''), $tpl);

$tpl = setVarTPL("USERNAME_ACTUAL", cfgEscapar($usernameActual), $tpl);
$tpl = setVarTPL("USERNAME_AVISO_HTML", $avisoHtml, $tpl);
$tpl = setVarTPL("USERNAME_FLASH_HTML", $flashHtml, $tpl);
$tpl = setVarTPL("CFG_UBICACION_CLASE", $claseUbicacion, $tpl);
$tpl = setVarTPL("CFG_USERNAME_CLASE", $claseUsername, $tpl);

printTPL($tpl);