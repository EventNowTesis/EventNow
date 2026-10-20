<?php

require_once __DIR__ . '/../models/Usuarios.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION["id_usuario"])) {
    echo json_encode(["success" => false, "mensaje" => "No autenticado."]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "mensaje" => "Método no permitido."]);
    exit;
}

$id_usuario = $_SESSION["id_usuario"];

$lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float) $_POST['lat'] : null;
$lon = isset($_POST['lon']) && $_POST['lon'] !== '' ? (float) $_POST['lon'] : null;
$direccion = trim($_POST['direccion'] ?? '');

if ($lat === null || $lon === null) {
    echo json_encode(["success" => false, "mensaje" => "Faltan coordenadas."]);
    exit;
}

if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
    echo json_encode(["success" => false, "mensaje" => "Coordenadas inválidas."]);
    exit;
}

$resultado = guardarUbicacionUsuario($id_usuario, $lat, $lon, $direccion);

if (!$resultado) {
    echo json_encode(["success" => false, "mensaje" => "No se pudo guardar la ubicación."]);
    exit;
}

echo json_encode(["success" => true]);
exit;

?>