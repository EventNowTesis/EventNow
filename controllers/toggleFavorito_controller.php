<?php

require_once __DIR__ . '/../models/Panel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["id_usuario"]) || $_SESSION["rol"] !== "visitante") {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['exito' => false, 'mensaje' => 'Debés iniciar sesión']);
    } else {
        header("Location: index.php?slug=login");
    }
    exit;
}

$id_usuario = (int) $_SESSION["id_usuario"];

if (isset($_GET['id'])) {
    $id_evento = (int) $_GET['id'];
    $from = $_GET['from'] ?? '';

    toggleFavoritoUsuario($id_usuario, $id_evento);

    if ($from === 'panel') {
        header("Location: index.php?slug=panelVisitante");
    } else {
        header("Location: index.php?slug=detalle_evento&id=" . $id_evento);
    }
    exit;
}

ini_set('display_errors', 0);
if (ob_get_length()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);
$id_evento = filter_var($data['id_evento'] ?? null, FILTER_VALIDATE_INT);

if (!$id_evento) {
    echo json_encode(['exito' => false, 'mensaje' => 'ID de evento inválido']);
    exit;
}

try {
    $resultado = toggleFavoritoUsuario($id_usuario, $id_evento);
    echo json_encode([
        'exito' => (bool)$resultado['exito'],
        'estado' => $resultado['estado']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'exito' => false, 
        'mensaje' => 'Error: ' . $e->getMessage()
    ]);
}
exit;
?>