<?php

require_once __DIR__ . '/DBAbstract.php';
require_once __DIR__ . '/Posts.php';

function clienteSigueAnfitrion($id_cliente, $id_anfitrion)
{
    $db = dbConnect();

    $sql = "SELECT id_seguimiento
            FROM seguimientos
            WHERE id_cliente = ?
            AND id_anfitrion = ?
            LIMIT 1";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_cliente,
        $id_anfitrion
    );

    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $existe = mysqli_num_rows($resultado) > 0;

    mysqli_stmt_close($stmt);

    return $existe;
}



function seguirAnfitrion($id_cliente, $id_anfitrion)
{
    if (
        $id_cliente <= 0 ||
        $id_anfitrion <= 0 ||
        $id_cliente === $id_anfitrion
    ) {
        return false;
    }

    $db = dbConnect();

    $sql = "SELECT id_usuario
            FROM users
            WHERE id_usuario = ?
            AND rol = 'visitante'
            AND estado = 'activo'
            LIMIT 1";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $cliente_valido = mysqli_num_rows($resultado) === 1;

    mysqli_stmt_close($stmt);

    if (!$cliente_valido) {
        return false;
    }

    $sql = "SELECT id_usuario
            FROM users
            WHERE id_usuario = ?
            AND rol = 'anfitrion'
            AND estado = 'activo'
            LIMIT 1";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_anfitrion);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $anfitrion_valido = mysqli_num_rows($resultado) === 1;

    mysqli_stmt_close($stmt);

    if (!$anfitrion_valido) {
        return false;
    }

    //INSERT IGNORE es para evitar que siga dos veces a la misma persona
    $sql = "INSERT IGNORE INTO seguimientos
            (id_cliente, id_anfitrion, fecha_seguimiento)
            VALUES (?, ?, NOW())";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_cliente,
        $id_anfitrion
    );

    $ok = mysqli_stmt_execute($stmt);

    //si el INSERT IGNORE no insertó nada (ya lo seguía)
    $esNuevo = $ok && mysqli_stmt_affected_rows($stmt) === 1;

    mysqli_stmt_close($stmt);

    if ($esNuevo) {
        notificarNuevoSeguidor($id_anfitrion, $id_cliente);
    }

    return $ok;
}




function dejarDeSeguirAnfitrion($id_cliente, $id_anfitrion)
{
    $db = dbConnect();

    $sql = "DELETE FROM seguimientos
            WHERE id_cliente = ?
            AND id_anfitrion = ?";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $id_cliente,
        $id_anfitrion
    );

    $ok = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $ok;
}



function obtenerAnfitrionesSeguidos($id_cliente)
{
    $db = dbConnect();

    $sql = "SELECT
                u.id_usuario,
                u.display_name,
                u.nombre_usuario,
                s.fecha_seguimiento
            FROM seguimientos s
            INNER JOIN users u
                ON u.id_usuario = s.id_anfitrion
            WHERE s.id_cliente = ?
            AND u.rol = 'anfitrion'
            ORDER BY s.fecha_seguimiento DESC";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $anfitriones = [];

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $anfitriones[] = $fila;
    }

    mysqli_stmt_close($stmt);

    return $anfitriones;
}



function obtenerSeguidoresAnfitrion($id_anfitrion)
{
    $db = dbConnect();

    $sql = "SELECT
                u.id_usuario,
                u.display_name,
                u.nombre_usuario,
                s.fecha_seguimiento
            FROM seguimientos s
            INNER JOIN users u
                ON u.id_usuario = s.id_cliente
            WHERE s.id_anfitrion = ?
            AND u.rol = 'visitante'
            ORDER BY s.fecha_seguimiento DESC";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "i", $id_anfitrion);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $seguidores = [];

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $seguidores[] = $fila;
    }

    mysqli_stmt_close($stmt);

    return $seguidores;
}


function contarSeguidoresAnfitrion($id_anfitrion)
{
    $db = dbConnect();

    $sql = "SELECT COUNT(*) AS total
            FROM seguimientos
            WHERE id_anfitrion = ?";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_anfitrion);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $fila = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($stmt);

    return (int) $fila['total'];
}



function obtenerEventosDeAnfitrionesSeguidos($id_cliente)
{
    $db = dbConnect();

    $sql = "SELECT
                e.*,
                u.display_name,
                u.nombre_usuario
            FROM seguimientos s
            INNER JOIN eventos e
                ON e.id_usuario = s.id_anfitrion
            INNER JOIN users u
                ON u.id_usuario = s.id_anfitrion
            WHERE s.id_cliente = ?
            AND e.estado = 'aprobado'
            AND u.rol = 'anfitrion'
            ORDER BY e.fecha_publicacion DESC";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $eventos = [];

    while ($fila = mysqli_fetch_assoc($resultado)) {
        $eventos[] = $fila;
    }

    mysqli_stmt_close($stmt);

    return $eventos;
}

?>