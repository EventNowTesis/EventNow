
<?php

require_once __DIR__ . '/../models/Seguimientos.php';

if (!isset($_SESSION['id_usuario'])) {
    header('Location: index.php?slug=login');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?slug=landing');
    exit;
}


if (($_SESSION['rol'] ?? '') !== 'visitante') {
    header('Location: index.php?slug=panel');
    exit;
}

$id_cliente = (int) $_SESSION['id_usuario'];
$id_anfitrion = isset($_POST['id_anfitrion'])
    ? (int) $_POST['id_anfitrion']
    : 0;

$accion = $_POST['accion'] ?? 'seguir';
$username = trim($_POST['username'] ?? '');

if ($id_anfitrion <= 0 || $id_anfitrion === $id_cliente) {
    header('Location: index.php?slug=landing&error=seguimiento');
    exit;
}

if ($accion === 'dejar') {
    dejarDeSeguirAnfitrion($id_cliente, $id_anfitrion);
} else {
    seguirAnfitrion($id_cliente, $id_anfitrion);
}

//vuelve al perfil del anfitrión
if ($username !== '') {
    header(
        'Location: index.php?slug=profile&username=' . urlencode($username)
    );
} else {
    header('Location: index.php?slug=panelVisitante');
}

exit;
?>