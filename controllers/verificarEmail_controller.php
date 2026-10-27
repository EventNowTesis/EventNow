<?php

require_once __DIR__ . '/../models/Usuarios.php';

$token = $_GET['token'] ?? '';

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $_SESSION['flash_login'] = "El link de verificación no es válido.";
} elseif (verificarEmailPorToken($token)) {
    $_SESSION['flash_login'] = "Mail verificado, Ya podés iniciar sesión.";
} else {
    $_SESSION['flash_login'] = "El link venció o ya fue usado. Iniciá sesión para pedir uno nuevo.";
}

header("Location: index.php?slug=login");
exit;