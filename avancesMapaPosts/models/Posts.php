<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DBAbstract.php";


/**
 * Crea un evento junto con sus datos opcionales (contacto, información útil,
 * "a tener en cuenta", horario e información extra).
 *
 * @param int   $id_usuario
 * @param array $datos      Campos simples del evento (ver claves esperadas abajo)
 * @param array $horarios   Lista de ['hora' => 'HH:MM'|null, 'actividad' => string]
 * @param array $infoExtra  Lista de ['clave' => string, 'valor' => string]
 * @return bool
 */
function crearPost(
    $id_usuario,
    array $datos,
    array $horarios = [],
    array $infoExtra = []
) {

    $db = dbConnect();
    $db->begin_transaction();


    $sql = "INSERT INTO eventos
            (
                id_usuario,
                titulo,
                descripcion,
                categoria,
                fecha,
                hora,
                imagen,
                zona,
                contacto_email,
                contacto_telefono,
                contacto_instagram,
                contacto_web,
                info_apto,
                info_duracion,
                info_capacidad,
                info_accesibilidad,
                info_mascotas,
                adicion,
                fecha_publicacion
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        $db->rollback();
        $db->close();
        return false;
    }
    $types = "i" . str_repeat("s", 17); 

    $stmt->bind_param(
        $types,
        $id_usuario,
        $datos['titulo'],
        $datos['descripcion'],
        $datos['categoria'],
        $datos['fechaEvento'],
        $datos['horaEvento'],
        $datos['nombreArchivo'],
        $datos['zona'],
        $datos['contacto_email'],
        $datos['contacto_telefono'],
        $datos['contacto_instagram'],
        $datos['contacto_web'],
        $datos['info_apto'],
        $datos['info_duracion'],
        $datos['info_capacidad'],
        $datos['info_accesibilidad'],
        $datos['info_mascotas'],
        $datos['adicion']
    );

    if (!$stmt->execute()) {
        $stmt->close();
        $db->rollback();
        $db->close();
        return false;
    }

    $id_evento = $stmt->insert_id;
    $stmt->close();


    if (!empty($horarios)) {
        $sqlHorario = "INSERT INTO evento_horario (id_evento, hora, actividad, orden)
                        VALUES (?, ?, ?, ?)";
        $stmtHorario = $db->prepare($sqlHorario);

        if (!$stmtHorario) {
            $db->rollback();
            $db->close();
            return false;
        }

        $orden = 0;
        foreach ($horarios as $fila) {
            $hora = $fila['hora'] !== '' ? $fila['hora'] : null;
            $actividad = $fila['actividad'];
            $stmtHorario->bind_param("issi", $id_evento, $hora, $actividad, $orden);

            if (!$stmtHorario->execute()) {
                $stmtHorario->close();
                $db->rollback();
                $db->close();
                return false;
            }
            $orden++;
        }
        $stmtHorario->close();
    }


    if (!empty($infoExtra)) {
        $sqlInfoExtra = "INSERT INTO evento_info_extra (id_evento, clave, valor, orden)
                          VALUES (?, ?, ?, ?)";
        $stmtInfoExtra = $db->prepare($sqlInfoExtra);

        if (!$stmtInfoExtra) {
            $db->rollback();
            $db->close();
            return false;
        }

        $orden = 0;
        foreach ($infoExtra as $fila) {
            $clave = $fila['clave'];
            $valor = $fila['valor'];
            $stmtInfoExtra->bind_param("issi", $id_evento, $clave, $valor, $orden);

            if (!$stmtInfoExtra->execute()) {
                $stmtInfoExtra->close();
                $db->rollback();
                $db->close();
                return false;
            }
            $orden++;
        }
        $stmtInfoExtra->close();
    }


    $db->commit();
    $db->close();

    return true;
}


/**
 * Trae un evento junto con su horario e información extra, para renderizar
 * la página de detalle (detalleEvento.html).
 *
 * @param int $id_evento
 * @return array|null
 */
function obtenerPostConDetalle($id_evento)
{
    $db = dbConnect();

    $sql = "SELECT * FROM eventos WHERE id_evento = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id_evento);
    $stmt->execute();
    $evento = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$evento) {
        $db->close();
        return null;
    }

    $sqlHorario = "SELECT hora, actividad FROM evento_horario
                    WHERE id_evento = ? ORDER BY orden ASC";
    $stmt = $db->prepare($sqlHorario);
    $stmt->bind_param("i", $id_evento);
    $stmt->execute();
    $evento['horario'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $sqlInfo = "SELECT clave, valor FROM evento_info_extra
                 WHERE id_evento = ? ORDER BY orden ASC";
    $stmt = $db->prepare($sqlInfo);
    $stmt->bind_param("i", $id_evento);
    $stmt->execute();
    $evento['info_extra'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $db->close();

    return $evento;
}
function getEventosPendientes() {

    $db = dbConnect();

    $sql = "SELECT e.*, u.email 
            FROM eventos e 
            JOIN users u ON e.id_usuario = u.id_usuario 
            WHERE e.estado = 'pendiente' 
            ORDER BY e.fecha_publicacion DESC";

    $result = mysqli_query($db, $sql);

    if (!$result) {
        mysqli_close($db);
        return [];
    }

    $eventos = mysqli_fetch_all($result, MYSQLI_ASSOC);

    mysqli_close($db);

    return $eventos;
}

// ==========================================
// FUNCIONES PARA CAMBIAR EL ESTADO DE UN EVENTO
// ==========================================


function cambiarEstadoEvento($id_evento, $nuevoEstado) {

    $db = dbConnect();

    $sql = "UPDATE eventos SET estado = ? WHERE id_evento = ?";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        $db->close();
        return false;
    }

    $stmt->bind_param("si", $nuevoEstado, $id_evento);

    $resultado = $stmt->execute();

    $stmt->close();
    $db->close();

    return $resultado;
}

// ==========================================
// FUNCIONES PARA CAMBIAR EL ESTADO DE UN EVENTO
// ==========================================

function eliminarEventoPorCompleto($id_evento) {
    $db = dbConnect();

    $sql = "DELETE FROM eventos WHERE id_evento = ?";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        $db->close();
        return false;
    }

    $stmt->bind_param("i", $id_evento);

    $resultado = $stmt->execute();

    $stmt->close();
    $db->close();

    return $resultado;
}

?>