<?php

require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["btn_publicar"])) {

    $id_usuario = $_SESSION["id_usuario"];


    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $fechaEvento = trim($_POST['fecha'] ?? '');
    $horaEvento = trim($_POST['hora'] ?? '');
    $zona = trim($_POST['ubicacion'] ?? '');

    $nombreArchivo = null;

    if (
        $titulo === '' ||
        $descripcion === '' ||
        $categoria === '' ||
        $fechaEvento === '' ||
        $horaEvento === '' ||
        $zona === ''
    ) {
        echo "Todos los campos son obligatorios.";
        exit;
    }

    $categoriasPermitidas = [
        'musica', 'deporte', 'feria', 'arte',
        'gastronomia', 'tecnologia', 'otro'
    ];

    if (!in_array($categoria, $categoriasPermitidas, true)) {
        echo "Categoría no válida.";
        exit;
    }

    if (
        !isset($_FILES['imagen']) ||
        empty($_FILES['imagen']['name'])
    ) {
        echo "Debés seleccionar una imagen o video.";
        exit;
    }

    $permitidasImagen = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $permitidasVideo = ['mp4', 'mov', 'webm', 'avi', 'mkv'];
    $permitidas = array_merge($permitidasImagen, $permitidasVideo);

    $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $permitidas, true)) {
        echo "Formato de archivo no permitido. ";
        echo "Formatos válidos: " . implode(', ', $permitidas) . ".";
        exit;
    }

    $tipoArchivo = in_array($ext, $permitidasVideo, true) ? 'video' : 'imagen';

    $limiteBytes = $tipoArchivo === 'video' ? 20 * 1024 * 1024 : 5 * 1024 * 1024;

    if ($_FILES['imagen']['size'] > $limiteBytes) {
        $limiteMB = $limiteBytes / (1024 * 1024);
        echo "El archivo supera el tamaño máximo permitido ({$limiteMB} MB).";
        exit;
    }

    $nombreArchivo = uniqid('evt_', true) . '.' . $ext;

    $carpetaUploads = __DIR__ . '/../uploads/';

    if (!is_dir($carpetaUploads)) {
        if (!mkdir($carpetaUploads, 0777, true)) {
            echo "No se pudo crear la carpeta uploads.";
            exit;
        }
    }

    $destino = $carpetaUploads . $nombreArchivo;

    if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
        echo "Ocurrió un error al subir el archivo.";
        exit;
    }

    $contactoEmail = trim($_POST['contacto_email'] ?? '');
    $contactoTelefono = trim($_POST['contacto_telefono'] ?? '');
    $contactoInstagram = trim($_POST['contacto_instagram'] ?? '');
    $contactoWeb = trim($_POST['contacto_web'] ?? '');

    if ($contactoEmail !== '' && !filter_var($contactoEmail, FILTER_VALIDATE_EMAIL)) {
        echo "El email de contacto no es válido.";
        exit;
    }

    if ($contactoWeb !== '' && !filter_var($contactoWeb, FILTER_VALIDATE_URL)) {
        echo "El sitio web de contacto no es válido.";
        exit;
    }

    $infoApto = trim($_POST['info_apto'] ?? '');
    $infoDuracion = trim($_POST['info_duracion'] ?? '');
    $infoCapacidad = trim($_POST['info_capacidad'] ?? '');
    $infoAccesibilidad = trim($_POST['info_accesibilidad'] ?? '');
    $infoMascotas = trim($_POST['info_mascotas'] ?? '');
    $adicional = trim($_POST['adicional'] ?? '');

    $datos = [
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'categoria' => $categoria,
        'fechaEvento' => $fechaEvento,
        'horaEvento' => $horaEvento,
        'nombreArchivo' => $nombreArchivo,
        'zona' => $zona,
        'contacto_email' => $contactoEmail !== '' ? $contactoEmail : null,
        'contacto_telefono' => $contactoTelefono !== '' ? $contactoTelefono : null,
        'contacto_instagram' => $contactoInstagram !== '' ? $contactoInstagram : null,
        'contacto_web' => $contactoWeb !== '' ? $contactoWeb : null,
        'info_apto' => $infoApto !== '' ? $infoApto : null,
        'info_duracion' => $infoDuracion !== '' ? $infoDuracion : null,
        'info_capacidad' => $infoCapacidad !== '' ? $infoCapacidad : null,
        'info_accesibilidad' => $infoAccesibilidad !== '' ? $infoAccesibilidad : null,
        'info_mascotas' => $infoMascotas !== '' ? $infoMascotas : null,
        'adicion' => $adicional !== '' ? $adicional : null,
    ];


    $horarios = [];
    $horasPost = $_POST['horario_hora'] ?? [];
    $actividadesPost = $_POST['horario_actividad'] ?? [];

    foreach ($actividadesPost as $i => $actividad) {
        $actividad = trim($actividad);
        if ($actividad === '') {
            continue;
        }
        $hora = trim($horasPost[$i] ?? '');
        $horarios[] = [
            'hora' => $hora, 
            'actividad' => $actividad,
        ];
    }


    $infoExtra = [];
    $clavesPost = $_POST['info_extra_clave'] ?? [];
    $valoresPost = $_POST['info_extra_valor'] ?? [];

    foreach ($clavesPost as $i => $clave) {
        $clave = trim($clave);
        $valor = trim($valoresPost[$i] ?? '');
        if ($clave === '' || $valor === '') {
            continue;
        }
        $infoExtra[] = [
            'clave' => $clave,
            'valor' => $valor,
        ];
    }


    $resultado = crearPost(
        $id_usuario,
        $datos,
        $horarios,
        $infoExtra
    );

    if (!$resultado) {
        if (file_exists($destino)) {
            unlink($destino);
        }
        echo "No se pudo guardar la publicación.";
        exit;
    }

    header("Location: index.php?slug=panel");
    exit;

} else {

    $tpl = loadTPL("posts", "Publicar evento");
    printTPL($tpl);
}

?>