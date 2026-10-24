<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../models/Usuarios.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?slug=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?slug=landing');
    exit;
}

$id_usuario = (int) $_SESSION['id_usuario'];


$usuario = getUserById($id_usuario);

if (!$usuario || $usuario['estado'] !== 'activo') {
    session_destroy();
    header('Location: index.php?slug=login');
    exit;
}

$rol_actual = $usuario['rol'];

if ($rol_actual === 'anfitrion') {
    $nuevo_rol = 'visitante';
} elseif ($rol_actual === 'visitante') {
    $nuevo_rol = 'anfitrion';
} else {
    header('Location: index.php?slug=panelAdmin');
    exit;
}

if (cambiarRolUsuario($id_usuario, $nuevo_rol)) {

    
    $_SESSION['rol'] = $nuevo_rol;

   
    if ($nuevo_rol === 'anfitrion') {
        header('Location: index.php?slug=panel');
    } else {
        header('Location: index.php?slug=panelVisitante');
    }

    exit;
}

if ($rol_actual === 'anfitrion') {
    header('Location: index.php?slug=panel&error=cambiar_modo');
} else {
    header('Location: index.php?slug=panelVisitante&error=cambiar_modo');
}

exit;
?>