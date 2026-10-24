<?php

require_once __DIR__ . '/../models/Seguimientos.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?slug=login');
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'visitante') {
    header('Location: index.php?slug=panel');
    exit;
}

$id_cliente = (int) $_SESSION['id_usuario'];

$eventos = obtenerEventosDeAnfitrionesSeguidos($id_cliente);

function escaparTexto($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

echo '<main class="publicaciones-siguiendo">';
echo '<h1>Eventos de los anfitriones que sigo</h1>';

if (count($eventos) === 0) {
    echo '<p>Todavía no hay eventos para mostrar.</p>';
    echo '<p>Seguí a tus anfitriones favoritos para ver sus eventos acá.</p>';
} else {
    foreach ($eventos as $evento) {
        echo '<article class="evento-seguido">';

        echo '<h2>' . escaparTexto($evento['titulo']) . '</h2>';

        echo '<p>Organiza: '
            . escaparTexto($evento['display_name'])
            . ' (@' . escaparTexto($evento['nombre_usuario']) . ')'
            . '</p>';

        echo '<p>' . escaparTexto($evento['descripcion']) . '</p>';

        echo '<p>Fecha: ' . escaparTexto($evento['fecha']) . '</p>';

        echo '<p>Hora: ' . escaparTexto($evento['hora']) . '</p>';

        if (!empty($evento['zona'])) {
            echo '<p>Zona: ' . escaparTexto($evento['zona']) . '</p>';
        }

        echo '</article>';
        echo '<hr>';
    }
}

echo '<p><a href="index.php?slug=panelVisitante">Volver al panel</a></p>';
echo '</main>';
?>