
<?php

require_once __DIR__ . '/../models/Profile.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . '/../models/Panel.php';
require_once __DIR__ . '/../models/PerfilAnfitrion.php';
require_once __DIR__ . '/../models/Seguimientos.php';
require_once __DIR__ . '/assets/tpl_engine/tpl_engine.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?slug=login');
    exit;
}

$id_sesion = (int) $_SESSION['id_usuario'];
$rolSesion = $_SESSION['rol'] ?? 'visitante';

$username = $_GET['username'] ?? null;

if ($username) {
    $usuarioData = getUsuarioPerfilPorUsername($username);
} else {
    $usuarioData = getDatosAnfitrion($id_sesion);
}

if (!$usuarioData) {
    echo 'El perfil solicitado no existe.';
    exit;
}

$idPerfil = (int) ($usuarioData['id_usuario'] ?? $id_sesion);
$rolPerfil = strtolower($usuarioData['rol'] ?? '');

$nombrePerfil = $usuarioData['display_name']
    ?? $usuarioData['nombre_usuario']
    ?? '';

$partesNombre = explode(' ', trim($nombrePerfil));

$primerNombre = $partesNombre[0] !== ''
    ? $partesNombre[0]
    : 'este anfitrión';

/* =====================================================
   BOTÓN DE MENSAJE Y SEGUIMIENTO
   ===================================================== */

$botonMensaje = '';

if ($idPerfil !== $id_sesion) {

    // Conservar el botón de mensajes que ya tenías.
    $botonMensaje = "
        <a href='index.php?slug=mensajes&anfitrion=" . $idPerfil . "'
           class='btn-mensaje-perfil'>
            <i class='fa-regular fa-comment'></i>
            Enviar mensaje
        </a>
    ";

    // Mostrar el seguimiento solamente si el perfil
    // pertenece a un anfitrión y quien lo visita es cliente.
    if (
        $rolPerfil === 'anfitrion' &&
        $rolSesion === 'visitante'
    ) {
        $yaLoSigue = clienteSigueAnfitrion(
            $id_sesion,
            $idPerfil
        );

        $totalSeguidores = contarSeguidoresAnfitrion(
            $idPerfil
        );

        $usernameSeguro = htmlspecialchars(
            $usuarioData['nombre_usuario'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );

        $botonMensaje .= "
            <span class='contador-seguidores'>
                " . $totalSeguidores . "
                " . ($totalSeguidores === 1
                    ? 'seguidor'
                    : 'seguidores') . "
            </span>

            <form
                action='index.php?slug=seguirAnfitrion'
                method='POST'
                class='form-seguir-anfitrion'
            >
                <input
                    type='hidden'
                    name='id_anfitrion'
                    value='" . $idPerfil . "'
                >

                <input
                    type='hidden'
                    name='username'
                    value='" . $usernameSeguro . "'
                >

                <input
                    type='hidden'
                    name='accion'
                    value='" . ($yaLoSigue ? 'dejar' : 'seguir') . "'
                >

                <button type='submit' class='btn-seguir-perfil'>
                    <i class='fa-solid fa-user-" .
                    ($yaLoSigue ? 'check' : 'plus') . "'></i>
                    " . ($yaLoSigue ? 'Dejar de seguir' : 'Seguir') . "
                </button>
            </form>
        ";
    }
}

/* =====================================================
   EVENTOS DEL PERFIL
   ===================================================== */

$eventosPerfil = getEventosDeAnfitrion($idPerfil);

$cantidadEventos = count($eventosPerfil);

$textoCantidad = $cantidadEventos .
    ($cantidadEventos === 1 ? ' EVENTO' : ' EVENTOS');

/* =====================================================
   VALORACIONES
   ===================================================== */

$valoracion = obtenerValoracionAnfitrion($idPerfil);

if ($valoracion['cantidad'] > 0) {
    $promedioTxt = number_format(
        $valoracion['promedio'],
        1,
        ',',
        '.'
    );

    $estrellasHtml = generarEstrellasValoracion(
        round($valoracion['promedio'])
    );

    $valoracionesTxt = $valoracion['cantidad'] .
        ($valoracion['cantidad'] === 1
            ? ' valoración'
            : ' valoraciones');
} else {
    $promedioTxt = '—';
    $estrellasHtml = generarEstrellasValoracion(0);
    $valoracionesTxt = 'Sin valoraciones';
}

/* =====================================================
   CARGAR VISTA
   ===================================================== */

$tpl = loadTPL('profile', 'Profile');

$tpl = cargarNavbarComun(
    $tpl,
    $id_sesion,
    $rolSesion
);

$tpl = setVarTPL(
    'EVENTOS',
    generarHtmlEventosPerfil($eventosPerfil),
    $tpl
);

$tpl = setVarTPL(
    'PERFIL_NOMBRE',
    htmlspecialchars($nombrePerfil, ENT_QUOTES, 'UTF-8'),
    $tpl
);

$tpl = setVarTPL(
    'PERFIL_PRIMER_NOMBRE',
    htmlspecialchars($primerNombre, ENT_QUOTES, 'UTF-8'),
    $tpl
);

$tpl = setVarTPL(
    'USUARIO_USERNAME',
    htmlspecialchars(
        $usuarioData['nombre_usuario'] ?? 'Usuario',
        ENT_QUOTES,
        'UTF-8'
    ),
    $tpl
);

$tpl = setVarTPL(
    'USUARIO_EMAIL',
    htmlspecialchars(
        $usuarioData['email'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    ),
    $tpl
);

$tpl = setVarTPL(
    'USUARIO_ROL',
    htmlspecialchars(
        ucfirst($rolPerfil ?: 'visitante'),
        ENT_QUOTES,
        'UTF-8'
    ),
    $tpl
);

$tpl = setVarTPL('BOTON_MENSAJE', $botonMensaje, $tpl);

$tpl = setVarTPL(
    'CANTIDAD_EVENTOS_TXT',
    $textoCantidad,
    $tpl
);

$tpl = setVarTPL('PROMEDIO_ANFITRION', $promedioTxt, $tpl);
$tpl = setVarTPL('ESTRELLAS_ANFITRION', $estrellasHtml, $tpl);
$tpl = setVarTPL('VALORACIONES_TXT', $valoracionesTxt, $tpl);

printTPL($tpl);

?>
