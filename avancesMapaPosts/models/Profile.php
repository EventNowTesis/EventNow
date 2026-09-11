<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DBAbstract.php";

function getMisEventos() {

    $id_usuario = $_SESSION["id_usuario"];

    $db = dbConnect();

    $sql = "SELECT *
            FROM eventos
            WHERE id_usuario = ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    $resultado = $stmt->get_result();

    $eventos = $resultado->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $db->close();

    return $eventos;
}






function editarMiEvento(
    $id_evento, $id_usuario, $titulo, $descripcion, $categoria,
    $fecha, $hora, $zona,
    $contactoEmail, $contactoTelefono, $contactoInstagram, $contactoWeb,
    $infoApto, $infoDuracion, $infoCapacidad, $infoAccesibilidad, $infoMascotas,
    $adicion, $nombreArchivo = null
) {
    $db = dbConnect();

    if ($nombreArchivo !== null) {
        $sql = "UPDATE eventos 
                SET titulo = ?, descripcion = ?, categoria = ?, fecha = ?, hora = ?,
                    zona = ?, contacto_email = ?, contacto_telefono = ?, contacto_instagram = ?,
                    contacto_web = ?, info_apto = ?, info_duracion = ?, info_capacidad = ?,
                    info_accesibilidad = ?, info_mascotas = ?, adicion = ?, imagen = ?,
                    fecha_modificacion = NOW()
                WHERE id_evento = ? AND id_usuario = ?";

        $stmt = $db->prepare($sql);
        if (!$stmt) { $db->close(); return false; }

        $stmt->bind_param(
            "sssssssssssssssssii",
            $titulo, $descripcion, $categoria, $fecha, $hora, $zona,
            $contactoEmail, $contactoTelefono, $contactoInstagram, $contactoWeb,
            $infoApto, $infoDuracion, $infoCapacidad, $infoAccesibilidad, $infoMascotas,
            $adicion, $nombreArchivo, $id_evento, $id_usuario
        );
    } else {
        $sql = "UPDATE eventos 
                SET titulo = ?, descripcion = ?, categoria = ?, fecha = ?, hora = ?,
                    zona = ?, contacto_email = ?, contacto_telefono = ?, contacto_instagram = ?,
                    contacto_web = ?, info_apto = ?, info_duracion = ?, info_capacidad = ?,
                    info_accesibilidad = ?, info_mascotas = ?, adicion = ?,
                    fecha_modificacion = NOW()
                WHERE id_evento = ? AND id_usuario = ?";

        $stmt = $db->prepare($sql);
        if (!$stmt) { $db->close(); return false; }

        $stmt->bind_param(
            "ssssssssssssssssii",
            $titulo, $descripcion, $categoria, $fecha, $hora, $zona,
            $contactoEmail, $contactoTelefono, $contactoInstagram, $contactoWeb,
            $infoApto, $infoDuracion, $infoCapacidad, $infoAccesibilidad, $infoMascotas,
            $adicion, $id_evento, $id_usuario
        );
    }

    $resultado = $stmt->execute();

    $stmt->close();
    $db->close();

    return $resultado;
}


function actualizarHorarioEvento($id_evento, array $horarios) {
    $db = dbConnect();

    $sqlDelete = "DELETE FROM evento_horario WHERE id_evento = ?";
    $stmtDelete = $db->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $id_evento);
    $stmtDelete->execute();
    $stmtDelete->close();

    if (!empty($horarios)) {
        $sqlInsert = "INSERT INTO evento_horario (id_evento, hora, actividad, orden) VALUES (?, ?, ?, ?)";
        $stmtInsert = $db->prepare($sqlInsert);

        $orden = 0;
        foreach ($horarios as $fila) {
            $hora = $fila['hora'] !== '' ? $fila['hora'] : null;
            $actividad = $fila['actividad'];
            $stmtInsert->bind_param("issi", $id_evento, $hora, $actividad, $orden);
            $stmtInsert->execute();
            $orden++;
        }
        $stmtInsert->close();
    }

    $db->close();
    return true;
}


function actualizarInfoExtraEvento($id_evento, array $infoExtra) {
    $db = dbConnect();

    $sqlDelete = "DELETE FROM evento_info_extra WHERE id_evento = ?";
    $stmtDelete = $db->prepare($sqlDelete);
    $stmtDelete->bind_param("i", $id_evento);
    $stmtDelete->execute();
    $stmtDelete->close();

    if (!empty($infoExtra)) {
        $sqlInsert = "INSERT INTO evento_info_extra (id_evento, clave, valor, orden) VALUES (?, ?, ?, ?)";
        $stmtInsert = $db->prepare($sqlInsert);

        $orden = 0;
        foreach ($infoExtra as $fila) {
            $clave = $fila['clave'];
            $valor = $fila['valor'];
            $stmtInsert->bind_param("issi", $id_evento, $clave, $valor, $orden);
            $stmtInsert->execute();
            $orden++;
        }
        $stmtInsert->close();
    }

    $db->close();
    return true;
}



function eliminarEvento($id_evento, $id_usuario) {

    $db = dbConnect();

    $sql = "DELETE FROM eventos
            WHERE id_evento = ?
            AND id_usuario = ?";

    $stmt = $db->prepare($sql);

    $stmt->bind_param(
        "ii",
        $id_evento,
        $id_usuario
    );

    $resultado = $stmt->execute();

    $stmt->close();
    $db->close();

    return $resultado;
}





function mostrarMisEventos() {

    $eventos = getMisEventos();

    $html = "";

    foreach ($eventos as $evento) {

        $html .= "
            <div class='evento'>

                <h2>" . htmlspecialchars($evento['titulo']) . "</h2>

                <p>" . htmlspecialchars($evento['descripcion']) . "</p>

                <p>
                    <strong>Categoría:</strong>
                    " . htmlspecialchars($evento['categoria']) . "
                </p>

                 <p><strong>Fecha:</strong> "
                . htmlspecialchars($evento['fecha']) .
                "</p>
                <p><strong>Hora:</strong> "
                . htmlspecialchars($evento['hora']) .
                "</p>

                <p>
                    <strong>Ubicación:</strong>
                    " . htmlspecialchars($evento['zona']) . "
                </p>

                <img src='/avancesMapaULTIMO/uploads/"
                    . htmlspecialchars($evento['imagen']) . "'
                    alt='Imagen del evento'>

                <div class='botones-evento'>

                    <a href='index.php?slug=editar_evento&id="
                    . $evento['id_evento'] . "'>
                        Editar
                    </a>

                    <a href='index.php?slug=eliminar_evento&id="
                    . $evento['id_evento'] . "'
                       onclick=\"return confirm('¿Seguro que querés eliminar este evento?');\">
                        Borrar
                    </a>

                </div>

            </div>
        ";
    }

    return $html;
}

?>