<?php

require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";


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


function e($valor) {
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}


if (isset($_SESSION["id_usuario"])) {
    if ($_SESSION['rol'] === 'visitante') {
        $perfilSlug = 'panelVisitante';
    } elseif ($_SESSION['rol'] === 'admin') {
        $perfilSlug = 'panelAdmin';
    } else {
        $perfilSlug = 'panel';
    }

    $menu_sesion = '<a href="index.php?slug=' . $perfilSlug . '" class="login-link">Perfil</a>
                    <a href="index.php?slug=logout" class="register-button" onclick="return confirm(\'¿Seguro que querés cerrar sesión?\');">Cerrar sesión</a>';
} else {
    $menu_sesion = '<a href="index.php?slug=login" class="login-link">Iniciar sesión</a>
                    <a href="index.php?slug=register" class="register-button">Crear cuenta</a>';
}


$categorias = [
    'musica'      => 'Música',
    'deporte'     => 'Deporte',
    'feria'       => 'Ferias',
    'arte'        => 'Arte y cultura',
    'gastronomia' => 'Gastronomía',
    'tecnologia'  => 'Tecnología',
    'otro'        => 'Otro',
];

$categoriaLegible = $categorias[$evento['categoria']] ?? e($evento['categoria']);

$fechaFormateada = '';
if (!empty($evento['fecha'])) {
    $ts = strtotime($evento['fecha']);
    $fechaFormateada = $ts ? date('d/m/Y', $ts) : e($evento['fecha']);
}
$horaFormateada = $evento['hora'] !== null ? substr($evento['hora'], 0, 5) : '';
$fechaHoraTexto = trim($fechaFormateada . ($horaFormateada !== '' ? ' · ' . $horaFormateada . ' hs' : ''));

$extensionesVideo = ['mp4', 'mov', 'webm', 'avi', 'mkv'];
$extArchivo = strtolower(pathinfo($evento['imagen'] ?? '', PATHINFO_EXTENSION));
$esVideo = in_array($extArchivo, $extensionesVideo, true);
$rutaArchivo = !empty($evento['imagen']) ? 'uploads/' . $evento['imagen'] : '';

// Como no hice los privados se van a mostrar automaticamente como gratis/ a confirmar. Si se implementan los privados, se puede cambiar la logica para que muestre el precio real
$precioTexto = 'A confirmar';
$precioClase = 'event-price-tbd';


$organizadorHtml = '';
if (!empty($evento['contacto_instagram'])) {
    $organizadorHtml = '<p class="detail-organizer">Organiza ' . e($evento['contacto_instagram']) . '</p>';
} elseif (!empty($evento['contacto_web'])) {
    $organizadorHtml = '<p class="detail-organizer">Organiza ' . e($evento['contacto_web']) . '</p>';
}


$lineupSectionHtml = '';
if (!empty($evento['horario'])) {
    $items = '';
    foreach ($evento['horario'] as $fila) {
        $horaFila = $fila['hora'] !== null ? substr($fila['hora'], 0, 5) : '';
        $items .= '<li>';
        if ($horaFila !== '') {
            $items .= '<strong>' . e($horaFila) . '</strong> — ';
        }
        $items .= e($fila['actividad']) . '</li>';
    }

    $lineupSectionHtml = '
        <section class="detail-block reveal">
          <h2 class="section-title">Line-up</h2>
          <ul class="detail-list">' . $items . '</ul>
        </section>';
}


$contactoItems = '';

if (!empty($evento['contacto_email'])) {
    $contactoItems .= '<li><a href="mailto:' . e($evento['contacto_email']) . '">' . e($evento['contacto_email']) . '</a></li>';
}
if (!empty($evento['contacto_telefono'])) {
    $telHref = preg_replace('/[^\d+]/', '', $evento['contacto_telefono']);
    $contactoItems .= '<li><a href="tel:' . e($telHref) . '">' . e($evento['contacto_telefono']) . '</a></li>';
}
if (!empty($evento['contacto_instagram'])) {
    $usuario = ltrim($evento['contacto_instagram'], '@');
    $contactoItems .= '<li><a href="https://instagram.com/' . e($usuario) . '" target="_blank" rel="noopener">' . e($evento['contacto_instagram']) . '</a></li>';
}
if (!empty($evento['contacto_web'])) {
    $contactoItems .= '<li><a href="' . e($evento['contacto_web']) . '" target="_blank" rel="noopener">' . e($evento['contacto_web']) . '</a></li>';
}

$contactoSectionHtml = '';
if ($contactoItems !== '') {
    $contactoSectionHtml = '
        <section class="detail-card reveal">
          <h3 class="detail-card-title">Contacto del organizador</h3>
          <ul class="detail-contact">' . $contactoItems . '</ul>
        </section>';
}


$infoFacts = [
    'Apto'           => $evento['info_apto'] ?? null,
    'Duración'       => $evento['info_duracion'] ?? null,
    'Capacidad'      => $evento['info_capacidad'] ?? null,
    'Accesibilidad'  => $evento['info_accesibilidad'] ?? null,
    'Mascotas'       => $evento['info_mascotas'] ?? null,
];

$infoItems = '';
foreach ($infoFacts as $etiqueta => $valor) {
    if ($valor !== null && $valor !== '') {
        $infoItems .= '<li><span>' . e($etiqueta) . '</span><strong>' . e($valor) . '</strong></li>';
    }
}

if (!empty($evento['info_extra'])) {
    foreach ($evento['info_extra'] as $fila) {
        $infoItems .= '<li><span>' . e($fila['clave']) . '</span><strong>' . e($fila['valor']) . '</strong></li>';
    }
}

$infoUtilSectionHtml = '';
if ($infoItems !== '') {
    $infoUtilSectionHtml = '
        <section class="detail-card reveal">
          <h3 class="detail-card-title">Información útil</h3>
          <ul class="detail-facts">' . $infoItems . '</ul>
        </section>';
}



$notaSectionHtml = '';
if (!empty($evento['adicional'])) {
    $notaSectionHtml = '
        <section class="detail-card detail-card-note reveal">
          <h3 class="detail-card-title">A tener en cuenta</h3>
          <p>' . nl2br(e($evento['adicional'])) . '</p>
        </section>';
}


$tpl = loadTPL("detalleEvento", $evento['titulo']);

$reemplazos = [
    'ID_EVENTO'            => $evento['id_evento'],
    'TITULO'                => e($evento['titulo']),
    'DESCRIPCION'           => e($evento['descripcion']),
    'CATEGORIA'             => e($categoriaLegible),
    'IMAGEN'                => e($rutaArchivo),
    'FECHA_HORA'            => e($fechaHoraTexto),
    'ZONA'                  => e($evento['zona']),
    'PRECIO_CLASE'          => $precioClase,
    'PRECIO_TEXTO'          => e($precioTexto),
    'ORGANIZADOR_HTML'      => $organizadorHtml,
    'LINEUP_SECTION_HTML'   => $lineupSectionHtml,
    'CONTACTO_SECTION_HTML' => $contactoSectionHtml,
    'INFO_UTIL_SECTION_HTML'=> $infoUtilSectionHtml,
    'NOTA_SECTION_HTML'     => $notaSectionHtml,
    'MENU_SESION'           => $menu_sesion,
];

foreach ($reemplazos as $clave => $valor) {
    $tpl = setVarTPL($clave, $valor, $tpl);
}

printTPL($tpl);

?>