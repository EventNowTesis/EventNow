<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DBAbstract.php";


function newUserAnfi($email, $nombre, $nombre_usuario, $pass, $rol)
{
    if (!in_array($rol, ['anfitrion', 'visitante'], true)) {
        return false;
    }

    $db = dbConnect();

    $password = password_hash($pass, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users
        (email, display_name, nombre_usuario, password, rol, fechaRegistro, estado, email_verificado)
        VALUES (?, ?, ?, ?, ?, NOW(), 'activo', 0)";

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

    return true;
}


function newUserVisitante($email, $nombre, $pass, $rol)
{
    if (!in_array($rol, ['anfitrion', 'visitante'], true)) {
        return false;
    }

    $db = dbConnect();

    $password = password_hash($pass, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users
        (email, display_name, password, rol, fechaRegistro, estado, email_verificado)
        VALUES (?, ?, ?, ?, NOW(), 'activo', 0)";

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

    return true;
}


function getUserByEmail($email)
{
    $db = dbConnect();

    $sql = "SELECT id_usuario, email, display_name, nombre_usuario, password, rol, fechaRegistro, estado, email_verificado
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

    return $usuario ?: false;
}


function getUserByUsername($nombre_usuario)
{
    $db = dbConnect();

    $sql = "SELECT id_usuario, email, display_name, nombre_usuario, password, rol, fechaRegistro, estado
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

    return $usuario ?: false;
}

function getUserById($id)
{
    $db = dbConnect();

    $sql = "SELECT id_usuario, email, display_name, nombre_usuario, password, rol, fechaRegistro, estado
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

    return $usuario ?: false;
}


function getUserByEmailOrUsername($dato)
{
    $db = dbConnect();

    $sql = "SELECT id_usuario, email, display_name, nombre_usuario, password, rol, fechaRegistro, estado, email_verificado
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

    return $usuario ?: false;
}


function getTodosLosUsuarios()
{
    $db = dbConnect();

    $sql = "SELECT id_usuario, email, rol, fechaRegistro, estado 
            FROM users 
            ORDER BY fechaRegistro DESC";

    $result = mysqli_query($db, $sql);

    if (!$result) {
        return [];
    }

    $usuarios = mysqli_fetch_all($result, MYSQLI_ASSOC);

    return $usuarios;
}


function actualizarEstadoUsuario($id_usuario, $nuevoEstado)
{
    if (!in_array($nuevoEstado, ['activo', 'desactivado'], true)) {
        return false;
    }

    $db = dbConnect();

    $sql = "UPDATE users SET estado = ?, fecha_baja = NULL WHERE id_usuario = ? AND rol <> 'admin'";
    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "si", $nuevoEstado, $id_usuario);
    $resultado = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $resultado;
}


function eliminarUsuario($id_usuario)
{
    $db = dbConnect();

    $sql = "DELETE FROM users WHERE id_usuario = ? AND rol <> 'admin'";
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("i", $id_usuario);
    $resultado = $stmt->execute();

    $stmt->close();

    return $resultado;
}

function guardarUbicacionUsuario($id_usuario, $lat, $lon, $direccion_texto)
{
    $db = dbConnect();

    $sql = "UPDATE users SET lat = ?, lon = ?, direccion_texto = ? WHERE id_usuario = ?";
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ddsi", $lat, $lon, $direccion_texto, $id_usuario);
    $resultado = $stmt->execute();

    $stmt->close();

    return $resultado;
}


function getUbicacionUsuario($id_usuario)
{
    $db = dbConnect();

    $sql = "SELECT lat, lon, direccion_texto FROM users WHERE id_usuario = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $ubicacion = $resultado->fetch_assoc();

    $stmt->close();

    return $ubicacion;
}
function getDatosAnfitrion($id_usuario)
{
    $db = dbConnect();

    $sql = "SELECT display_name, nombre_usuario, email, rol FROM users WHERE id_usuario = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    $stmt->close();

    return $usuario;
}

function actualizarPasswordPorEmail($email, $passwordPlano)
{
    $db = dbConnect();

    $hash = password_hash($passwordPlano, PASSWORD_DEFAULT);

    $sql = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "ss", $hash, $email);
    $resultado = mysqli_stmt_execute($stmt);

    mysqli_stmt_close($stmt);

    return $resultado;
}

function desactivarCuentaPropia($id_usuario)
{
    $db = dbConnect();
    $stmt = mysqli_prepare($db, "UPDATE users SET estado = 'desactivado', fecha_baja = NOW() WHERE id_usuario = ? AND rol <> 'admin'");
    if (!$stmt)
        return false;
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function getDatosBaja($email)
{
    $db = dbConnect();
    $sql = "SELECT id_usuario, password, estado, fecha_baja,
                   (fecha_baja IS NOT NULL AND fecha_baja >= DATE_SUB(NOW(), INTERVAL 15 DAY)) AS recuperable,
                   GREATEST(0, 15 - DATEDIFF(NOW(), fecha_baja)) AS dias_restantes
            FROM users WHERE email = ? LIMIT 1";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt)
        return false;
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $fila ?: false;
}

function reactivarCuenta($id_usuario)
{
    $db = dbConnect();
    $stmt = mysqli_prepare($db, "UPDATE users SET estado = 'activo', fecha_baja = NULL WHERE id_usuario = ?");
    if (!$stmt)
        return false;
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function purgarCuentasVencidas()
{
    $db = dbConnect();
    return mysqli_query($db, "DELETE FROM users
        WHERE estado = 'desactivado' AND fecha_baja IS NOT NULL
        AND fecha_baja < DATE_SUB(NOW(), INTERVAL 15 DAY)
        AND rol <> 'admin'");
}



function cambiarRolUsuario($id_usuario, $nuevo_rol)
{
    if (!in_array($nuevo_rol, ['anfitrion', 'visitante'], true)) {
        return false;
    }

    $db = dbConnect();

    $sql = "SELECT rol, estado
            FROM users
            WHERE id_usuario = ?
            LIMIT 1";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);
    $usuario = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($stmt);

    if (!$usuario || $usuario['estado'] !== 'activo') {
        return false;
    }

    //lo puse para que no se pueda modificar el rol del admin
    if (!in_array($usuario['rol'], ['anfitrion', 'visitante'], true)) {
        return false;
    }


    if ($usuario['rol'] === $nuevo_rol) {
        return false;
    }

    $sql = "UPDATE users
            SET rol = ?
            WHERE id_usuario = ?
            AND rol = ?
            AND estado = 'activo'";

    $stmt = mysqli_prepare($db, $sql);

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sis",
        $nuevo_rol,
        $id_usuario,
        $usuario['rol']
    );

    $ok = mysqli_stmt_execute($stmt);

    if ($ok && mysqli_stmt_affected_rows($stmt) !== 1) {
        $ok = false;
    }

    mysqli_stmt_close($stmt);

    return $ok;
}



function guardarTokenVerificacion($email, $hashToken)
{
    $db = dbConnect();
    $sql = "UPDATE users
            SET token_verificacion = ?, token_expira = DATE_ADD(NOW(), INTERVAL 24 HOUR), verif_enviado = NOW()
            WHERE email = ? AND email_verificado = 0";
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt)
        return false;
    mysqli_stmt_bind_param($stmt, "ss", $hashToken, $email);
    $ok = mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) === 1;
    mysqli_stmt_close($stmt);
    return $ok;
}

function verificarEmailPorToken($token)
{
    $db = dbConnect();
    $hash = hash('sha256', $token);

    $stmt = mysqli_prepare($db, "SELECT id_usuario FROM users
        WHERE token_verificacion = ? AND token_expira > NOW() AND email_verificado = 0 LIMIT 1");
    if (!$stmt)
        return false;
    mysqli_stmt_bind_param($stmt, "s", $hash);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$fila)
        return false;

    $stmt = mysqli_prepare($db, "UPDATE users
        SET email_verificado = 1, token_verificacion = NULL, token_expira = NULL
        WHERE id_usuario = ?");
    if (!$stmt)
        return false;
    $id = (int) $fila['id_usuario'];
    mysqli_stmt_bind_param($stmt, "i", $id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function puedeReenviarVerificacion($email)
{
    $db = dbConnect();
    $stmt = mysqli_prepare($db, "SELECT (verif_enviado IS NULL OR verif_enviado < DATE_SUB(NOW(), INTERVAL 2 MINUTE)) AS ok
        FROM users WHERE email = ? AND email_verificado = 0 LIMIT 1");
    if (!$stmt)
        return false;
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return !empty($fila['ok']);
}

// Libera mails de cuentas que nunca se verificaron (token vencido)
function purgarNoVerificadosVencidos()
{
    $db = dbConnect();
    return mysqli_query($db, "DELETE FROM users
        WHERE email_verificado = 0 AND token_expira IS NOT NULL
        AND token_expira < NOW() AND rol <> 'admin'");
}

?>