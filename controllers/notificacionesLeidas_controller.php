<?php

require_once __DIR__ . '/../models/Posts.php';

header('Content-Type: application/json');

if (!isset($_SESSION["id_usuario"])) {
    http_response_code(401);
    echo json_encode(['exito' => false, 'mensaje' => 'No autenticado.']);
    exit;
}

marcarNotificacionesComoLeidas($_SESSION["id_usuario"]);

echo json_encode(['exito' => true]);
exit;