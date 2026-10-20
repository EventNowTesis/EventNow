<?php

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/Panel.php';

date_default_timezone_set('America/Argentina/Buenos_Aires');

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Usuario no autenticado.'
    ]);
    exit;
}

$idUsuario = $_SESSION['id_usuario'];

$input = json_decode(file_get_contents('php://input'), true);

$idEvento = $input['id_evento'] ?? $_GET['id'] ?? null;

if (!$idEvento) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'ID de evento no proporcionado.'
    ]);
    exit;
}

$idEvento = (int) $idEvento;


$db = dbConnect();

$stmt = mysqli_prepare(
    $db,
    "SELECT fecha FROM eventos WHERE id_evento = ?"
);

mysqli_stmt_bind_param($stmt, "i", $idEvento);
mysqli_stmt_execute($stmt);

$resultadoConsulta = mysqli_stmt_get_result($stmt);
$fila = mysqli_fetch_assoc($resultadoConsulta);

mysqli_stmt_close($stmt);

if (!$fila) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'El evento no existe.'
    ]);
    exit;
}


if (substr($fila['fecha'], 0, 10) < date('Y-m-d')) {

    echo json_encode([
        'exito' => false,
        'mensaje' => 'Este evento ya pasó, no se puede modificar la asistencia.'
    ]);

    exit;
}


/* ---------------------------------------
   Cambiar asistencia
--------------------------------------- */

$resultado = toggleAsistenciaUsuario(
    $idUsuario,
    $idEvento
);


if ($resultado['exito']) {

    echo json_encode([
        'exito' => true,
        'estado' => $resultado['estado']
    ]);

} else {

    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error al actualizar la asistencia en la base de datos.'
    ]);
}

exit;