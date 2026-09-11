<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/DBAbstract.php";




function getEventos(){
    $eventos = dbQuery("SELECT * FROM eventos");
    return $eventos;
}


function getEventoPorId($id){
    $db = dbConnect();

    $sql = "SELECT * FROM eventos WHERE id_evento = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $evento = $resultado->fetch_assoc();

    $stmt->close();
    $db->close();

    return $evento;
}


function filtrarCategoria($categoria){
    $db = dbConnect();

    $sql = "SELECT * FROM eventos WHERE categoria = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $categoria);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $eventos = $resultado->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $db->close();

    return $eventos;
}


function formatearFechaHora($fecha, $hora){
    if (!$fecha) return '';

    $meses = [
        '01' => 'ene', '02' => 'feb', '03' => 'mar', '04' => 'abr',
        '05' => 'may', '06' => 'jun', '07' => 'jul', '08' => 'ago',
        '09' => 'sep', '10' => 'oct', '11' => 'nov', '12' => 'dic',
    ];

    $ts = strtotime(trim($fecha . ' ' . ($hora ?? '')));

    if ($ts === false) {
        return htmlspecialchars($fecha);
    }

    $dia = date('d', $ts);
    $mes = $meses[date('m', $ts)] ?? '';

    $h24 = (int) date('H', $ts);
    $min = date('i', $ts);
    $ampm = $h24 >= 12 ? 'p. m.' : 'a. m.';
    $h12 = $h24 % 12;
    if ($h12 === 0) $h12 = 12;
    $horaFmt = str_pad((string) $h12, 2, '0', STR_PAD_LEFT) . ':' . $min . ' ' . $ampm;

    return "{$dia}-{$mes}, {$horaFmt}";
}


function prepararDatosEvento($evento){
    $categoriaLabels = [
        'musica'       => 'Música',
        'deporte'      => 'Deportivo',
        'feria'        => 'Feria',
        'arte'         => 'Cultural',
        'gastronomia'  => 'Gastronómico',
        'tecnologia'   => 'Tecnología',
        'otro'         => 'Otro',
    ];

    $esExterno = $evento['externo'] ?? false;

    $srcImagen = $esExterno
        ? htmlspecialchars($evento['imagen'] ?? '')
        : '/avancesMapaPosts/uploads/' . htmlspecialchars($evento['imagen'] ?? '');

    $categoriaKey = $evento['categoria'] ?? '';
    $categoriaLabel = $categoriaLabels[$categoriaKey]
        ?? ($categoriaKey !== '' ? ucfirst($categoriaKey) : 'Evento');

    $fechaHora = formatearFechaHora($evento['fecha'] ?? '', $evento['hora'] ?? '');

    $precio = $evento['precio'] ?? null;
    $esGratis = ($precio === null || $precio === '' || (float) $precio <= 0);
    $precioTexto = $esGratis
        ? 'Gratuito'
        : '$ ' . number_format((float) $precio, 0, ',', '.');
    $precioClase = $esGratis ? 'event-price free' : 'event-price';

    $organizador = htmlspecialchars($evento['organizador'] ?? '');
    $organizadorHtml = $organizador !== ''
        ? "<p class='event-organizer'>Por <strong>{$organizador}</strong></p>"
        : "";

    $idEvento = htmlspecialchars($evento['id_evento'] ?? '');

    return [
        'idEvento'        => $idEvento,
        'titulo'          => htmlspecialchars($evento['titulo'] ?? ''),
        'descripcion'     => htmlspecialchars($evento['descripcion'] ?? ''),
        'zona'            => htmlspecialchars($evento['zona'] ?? ''),
        'srcImagen'       => $srcImagen,
        'categoriaLabel'  => $categoriaLabel,
        'fechaHora'       => $fechaHora,
        'precioTexto'     => $precioTexto,
        'precioClase'     => $precioClase,
        'organizador'     => $organizador,
        'organizadorHtml' => $organizadorHtml,
    ];
}



function mostrarEventos($eventos = null){

    if ($eventos === null) {
        $eventos = getEventos();
    }

    $html = "";

    foreach ($eventos as $evento) {

        $d = prepararDatosEvento($evento);

        $html .= "
            <div class='evento event-card'>

                <div class='event-image'>
                    <img src='{$d['srcImagen']}' alt='Imagen del evento' loading='lazy'>
                    <span class='event-badge'>{$d['categoriaLabel']}</span>
                </div>

                <div class='event-body'>

                    <span class='event-date'>
                        <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                            <rect x='3' y='5' width='18' height='16' rx='2'></rect>
                            <path d='M16 3v4M8 3v4M3 10h18'></path>
                        </svg>
                        {$d['fechaHora']}
                    </span>

                    <h2 class='event-title'>{$d['titulo']}</h2>

                    {$d['organizadorHtml']}

                    <p class='event-description'>{$d['descripcion']}</p>

                    <span class='event-location'>
                        <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
                            <path d='M12 22s7-7.58 7-12A7 7 0 0 0 5 10c0 4.42 7 12 7 12z'></path>
                            <circle cx='12' cy='10' r='2.5'></circle>
                        </svg>
                        {$d['zona']}
                    </span>

                </div>

                <div class='event-side'>

                    <div class='event-price-box'>
                        <span class='event-entrada-label'>Entrada</span>
                        <span class='{$d['precioClase']}'>{$d['precioTexto']}</span>
                    </div>

                    <div class='event-actions'>
                        <a class='btn-details' href='index.php?slug=detalleEvento&id={$d['idEvento']}'>Ver detalles →</a>
                        <a class='btn-confirm' href='index.php?slug=confirmar&id={$d['idEvento']}'>✓ Confirmar asistencia</a>
                    </div>

                </div>

            </div>
        ";
    }

    return $html;
}


// ============ PANEL DEL ANFITRIÓN ============

function getDatosUsuario($id_usuario) {
    $db = dbConnect();

    $sql = "SELECT display_name, email, rol FROM users WHERE id_usuario = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    $stmt->close();
    $db->close();

    return $usuario;
}


function getDatosAnfitrion($id_usuario) {
    $db = dbConnect();

    $sql = "SELECT display_name, nombre_usuario, email, rol FROM users WHERE id_usuario = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    $stmt->close();
    $db->close();

    return $usuario;
}


function getMisEventos() {
    $id_usuario = $_SESSION["id_usuario"];

    $db = dbConnect();

    $sql = "SELECT * FROM eventos WHERE id_usuario = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $eventos = $resultado->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $db->close();

    return $eventos;
}


function getEventoDeUsuario($id_evento, $id_usuario) {
    $db = dbConnect();

    $sql = "SELECT * FROM eventos WHERE id_evento = ? AND id_usuario = ?";
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        $db->close();
        return false;
    }

    $stmt->bind_param("ii", $id_evento, $id_usuario);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $evento = $resultado->fetch_assoc();

    $stmt->close();
    $db->close();

    return $evento;
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

    $sql = "DELETE FROM eventos WHERE id_evento = ? AND id_usuario = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $id_evento, $id_usuario);

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
                <p><strong>Categoría:</strong> " . htmlspecialchars($evento['categoria']) . "</p>
                <p><strong>Fecha:</strong> " . htmlspecialchars($evento['fecha']) . "</p>
                <p><strong>Hora:</strong> " . htmlspecialchars($evento['hora']) . "</p>
                <p><strong>Ubicación:</strong> " . htmlspecialchars($evento['zona']) . "</p>

                <img src='/avancesPostsRegister/uploads/" . htmlspecialchars($evento['imagen']) . "' alt='Imagen del evento'>

                <div class='botones-evento'>
                    <a href='index.php?slug=editar_evento&id=" . $evento['id_evento'] . "'>Editar</a>
                    <a href='index.php?slug=eliminar_evento&id=" . $evento['id_evento'] . "'
                       onclick=\"return confirm('¿Seguro que querés eliminar este evento?');\">Borrar</a>
                </div>
            </div>
        ";
    }

    return $html;
}

?>