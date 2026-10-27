<?php

require_once __DIR__ . '/DBAbstract.php';

/* Line-up / horario del evento (tabla evento_horario) */
function obtenerHorariosEvento($id_evento)
{
    $db = dbConnect();

    $stmt = $db->prepare(
        "SELECT hora, actividad
         FROM evento_horario
         WHERE id_evento = ?
         ORDER BY orden ASC"
    );

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $id_evento);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $filas;
}

/* Otros datos del evento (tabla evento_info_extra) */
function obtenerInfoExtraEvento($id_evento)
{
    $db = dbConnect();

    $stmt = $db->prepare(
        "SELECT clave, valor
         FROM evento_info_extra
         WHERE id_evento = ?
         ORDER BY orden ASC"
    );

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $id_evento);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $filas;
}

/*
 * Reemplaza TODO el line-up del evento por la lista nueva.
 * $horarios: lista de ['hora' => 'HH:MM' | '', 'actividad' => string]
 */
function reemplazarHorariosEvento($id_evento, array $horarios)
{
    $db = dbConnect();
    $db->begin_transaction();

    $del = $db->prepare("DELETE FROM evento_horario WHERE id_evento = ?");
    if (!$del) {
        $db->rollback();
        return false;
    }
    $del->bind_param("i", $id_evento);
    if (!$del->execute()) {
        $del->close();
        $db->rollback();
        return false;
    }
    $del->close();

    if (!empty($horarios)) {
        $ins = $db->prepare(
            "INSERT INTO evento_horario (id_evento, hora, actividad, orden)
             VALUES (?, ?, ?, ?)"
        );
        if (!$ins) {
            $db->rollback();
            return false;
        }

        $orden = 0;
        foreach ($horarios as $fila) {
            $hora = ($fila['hora'] ?? '') !== '' ? $fila['hora'] : null;
            $actividad = $fila['actividad'];
            $ins->bind_param("issi", $id_evento, $hora, $actividad, $orden);

            if (!$ins->execute()) {
                $ins->close();
                $db->rollback();
                return false;
            }
            $orden++;
        }
        $ins->close();
    }

    $db->commit();
    return true;
}

/*
 * Reemplaza TODOS los "otros datos" del evento por la lista nueva.
 * $infoExtra: lista de ['clave' => string, 'valor' => string]
 */
function reemplazarInfoExtraEvento($id_evento, array $infoExtra)
{
    $db = dbConnect();
    $db->begin_transaction();

    $del = $db->prepare("DELETE FROM evento_info_extra WHERE id_evento = ?");
    if (!$del) {
        $db->rollback();
        return false;
    }
    $del->bind_param("i", $id_evento);
    if (!$del->execute()) {
        $del->close();
        $db->rollback();
        return false;
    }
    $del->close();

    if (!empty($infoExtra)) {
        $ins = $db->prepare(
            "INSERT INTO evento_info_extra (id_evento, clave, valor, orden)
             VALUES (?, ?, ?, ?)"
        );
        if (!$ins) {
            $db->rollback();
            return false;
        }

        $orden = 0;
        foreach ($infoExtra as $fila) {
            $clave = $fila['clave'];
            $valor = $fila['valor'];
            $ins->bind_param("issi", $id_evento, $clave, $valor, $orden);

            if (!$ins->execute()) {
                $ins->close();
                $db->rollback();
                return false;
            }
            $orden++;
        }
        $ins->close();
    }

    $db->commit();
    return true;
}

?>