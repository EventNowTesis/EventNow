<?php

require_once __DIR__ . '/../models/Panel.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

$categoriasPermitidas = [
    'musica', 'deporte', 'feria', 'arte',
    'gastronomia', 'tecnologia', 'otro'
];

$categoria = $_GET['categoria'] ?? '';

if ($categoria !== '') {
    if (!in_array($categoria, $categoriasPermitidas, true)) {
        echo "Categoría no válida.";
        exit;
    }
    $eventosPropios = filtrarCategoria($categoria);
} else {
    $eventosPropios = getEventos();
}


$eventos = array_merge($eventosPropios);

$tpl = loadTPL("landing", "Landing");

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

$tpl = setVarTPL("MENU_SESION", $menu_sesion, $tpl);
$tpl = setVarTPL("EVENTOS", mostrarEventos($eventos), $tpl);
$tpl = setVarTPL("CANTIDAD_EVENTOS", count($eventos), $tpl);

printTPL($tpl);

?>