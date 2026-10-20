<?php
require_once __DIR__ . '/../models/Usuarios.php';

if (!isset($_SESSION['id_usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?slug=landing");
    exit;
}

$usuario = getUserById($_SESSION['id_usuario']);

if (!$usuario || $usuario['rol'] === 'admin') {
    header("Location: index.php?slug=landing");
    exit;
}

if (!password_verify($_POST['txt_pass'] ?? '', $usuario['password'])) {
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? "index.php?slug=landing"));
    exit;
}

desactivarCuentaPropia($usuario['id_usuario']);
session_destroy();
header("Location: index.php?slug=login&baja=1");
exit;