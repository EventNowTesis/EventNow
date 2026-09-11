<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DBAbstract.php";


function newUserAnfi($email, $nombre, $nombre_usuario, $pass, $rol)
{
    $db = dbConnect();

    $password = password_hash($pass, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users 
            (email, display_name, nombre_usuario, password, rol, fechaRegistro)
            VALUES (?, ?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        die("Error preparando consulta: " . mysqli_error($db));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $email,
        $nombre,
        $nombre_usuario,
        $password,
        $rol
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Error creando usuario: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    mysqli_close($db);

    return true;
}


function newUserVisitante($email, $nombre, $pass, $rol)
{
    $db = dbConnect();

    $password = password_hash($pass, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users 
            (email, display_name, password, rol, fechaRegistro)
            VALUES (?, ?, ?, ?, NOW())";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        die("Error preparando consulta: " . mysqli_error($db));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $email,
        $nombre,
        $password,
        $rol
    );

    if (!mysqli_stmt_execute($stmt)) {
        die("Error creando usuario: " . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
    mysqli_close($db);

    return true;
}


function getUserByEmail($email)
{
    $db = dbConnect();

    $sql = "SELECT id_usuario, email, display_name, nombre_usuario, password, rol, fechaRegistro
            FROM users
            WHERE email = ?
            LIMIT 1";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        die("Error preparando consulta: " . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, "s", $email);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $usuario = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($db);

    return $usuario ?: false;
}


function getUserByUsername($nombre_usuario)
{
    $db = dbConnect();

    $sql = "SELECT id_usuario, email, display_name, nombre_usuario, password, rol, fechaRegistro
            FROM users
            WHERE nombre_usuario = ?
            LIMIT 1";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        die("Error preparando consulta: " . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, "s", $nombre_usuario);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $usuario = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($db);

    return $usuario ?: false;
}


function getUserById($id)
{
    $db = dbConnect();

    $sql = "SELECT id_usuario, email, display_name, nombre_usuario, password, rol, fechaRegistro
            FROM users
            WHERE id_usuario = ?
            LIMIT 1";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        die("Error preparando consulta: " . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, "i", $id);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $usuario = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($db);

    return $usuario ?: false;
}


function getUserByEmailOrUsername($dato)
{
    $db = dbConnect();

    $sql = "SELECT id_usuario, email, display_name, nombre_usuario, password, rol, fechaRegistro
            FROM users
            WHERE email = ? OR nombre_usuario = ?
            LIMIT 1";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        die("Error preparando consulta: " . mysqli_error($db));
    }

    mysqli_stmt_bind_param($stmt, "ss", $dato, $dato);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $usuario = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    mysqli_close($db);

    return $usuario ?: false;
}



// ==========================================
// GESTIÓN DE USUARIOS (SOLO PARA UN ADMIN)
// ==========================================

function getTodosLosUsuarios() {

    $db = dbConnect();

    $sql = "SELECT id_usuario, email, rol, fechaRegistro 
            FROM users 
            ORDER BY fechaRegistro DESC";

    $result = mysqli_query($db, $sql);

    if (!$result) {
        mysqli_close($db);
        return [];
    }

    $usuarios = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_close($db);

    return $usuarios;
}


function eliminarUsuario($id_usuario) {

    $db = dbConnect();

    $sql = "DELETE FROM users WHERE id_usuario = ?";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        $db->close();
        return false;
    }

    $stmt->bind_param("i", $id_usuario);

    $resultado = $stmt->execute();

    $stmt->close();
    $db->close();

    return $resultado;
}

?>