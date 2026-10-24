<?php

require_once __DIR__ . '/../models/Seguimientos.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?slug=login');
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'anfitrion') {
    header('Location: index.php?slug=panelVisitante');
    exit;
}

$id_anfitrion = (int) $_SESSION['id_usuario'];

$seguidores = obtenerSeguidoresAnfitrion($id_anfitrion);

function escaparSeguidor($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

echo '<main class="mis-seguidores">';
echo '<h1>Mis seguidores</h1>';

echo '<p>Total de seguidores: '
    . contarSeguidoresAnfitrion($id_anfitrion)
    . '</p>';

if (count($seguidores) === 0) {
    echo '<p>Todavía no tenés seguidores.</p>';
} else {
    echo '<ul>';

    foreach ($seguidores as $seguidor) {
        echo '<li>';

        echo escaparSeguidor($seguidor['display_name']);

        if (!empty($seguidor['nombre_usuario'])) {
            echo ' (@'
                . escaparSeguidor($seguidor['nombre_usuario'])
                . ')';
        }

        echo '<p>Te sigue desde: '
            . escaparSeguidor($seguidor['fecha_seguimiento'])
            . '</p>';

        echo '</li>';
    }

    echo '</ul>';
}

echo '<p><a href="index.php?slug=panel">Volver al panel</a></p>';
echo '</main>';
?>