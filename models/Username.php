<?php

require_once __DIR__ . '/DBAbstract.php';

function obtenerUsername($id_usuario)
{
    $db = dbConnect();

    $sql = "SELECT nombre_usuario
            FROM users
            WHERE id_usuario = ?
            LIMIT 1";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return '';
    }

    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $fila = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($stmt);

    return trim((string) ($fila['nombre_usuario'] ?? ''));
}

function usuarioTieneUsername($id_usuario)
{
    return obtenerUsername($id_usuario) !== '';
}

function usernameDisponible($username, $id_usuario)
{
    $db = dbConnect();

    $sql = "SELECT id_usuario
            FROM users
            WHERE LOWER(nombre_usuario) = LOWER(?)
            AND id_usuario <> ?
            LIMIT 1";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "si", $username, $id_usuario);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $libre = mysqli_num_rows($resultado) === 0;

    mysqli_stmt_close($stmt);

    return $libre;
}

function actualizarUsername($id_usuario, $username)
{
    $db = dbConnect();

    $sql = "UPDATE users
            SET nombre_usuario = ?
            WHERE id_usuario = ?";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "si", $username, $id_usuario);

    try {
        $ok = mysqli_stmt_execute($stmt);
    } catch (Throwable $e) {
        // por ejemplo, índice UNIQUE si dos personas eligen el mismo nombre a la vez
        $ok = false;
    }

    mysqli_stmt_close($stmt);

    return $ok;
}

?>