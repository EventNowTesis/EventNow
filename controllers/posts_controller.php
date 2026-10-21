<?php

require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";


if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=login");
    exit;
}

if ($_SESSION['rol'] !== 'anfitrion') {
    header("Location: index.php?slug=landing");
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

    $latEvento = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float) $_POST['lat'] : null;
    $lonEvento = isset($_POST['lon']) && $_POST['lon'] !== '' ? (float) $_POST['lon'] : null;

    if ($latEvento !== null && ($latEvento < -90 || $latEvento > 90)) {
        $latEvento = null;
    }
    if ($lonEvento !== null && ($lonEvento < -180 || $lonEvento > 180)) {
        $lonEvento = null;
    }

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

    $hoy = new DateTime('today');
    $fechaEventoObj = DateTime::createFromFormat('Y-m-d', $fechaEvento);

    if (!$fechaEventoObj || $fechaEventoObj < $hoy) {
        echo "La fecha del evento no puede ser anterior a hoy.";
        exit;
    }

    if ($fechaEventoObj == $hoy && $horaEvento < date('H:i')) {
        echo "La hora del evento no puede ser anterior a la hora actual.";
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

    $MIN_IMAGENES = 2;
    $MAX_IMAGENES = 6;
    $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $limiteBytes = 5 * 1024 * 1024; // 5 MB por imagen

    $archivos = [];

    if (isset($_FILES['imagenes']) && is_array($_FILES['imagenes']['name'])) {
        foreach ($_FILES['imagenes']['name'] as $i => $nombreOriginal) {
            if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $archivos[] = [
                'name' => $nombreOriginal,
                'tmp_name' => $_FILES['imagenes']['tmp_name'][$i],
                'size' => $_FILES['imagenes']['size'][$i],
                'error' => $_FILES['imagenes']['error'][$i],
            ];
        }
    }

    if (count($archivos) < $MIN_IMAGENES) {
        echo "Tenés que subir al menos {$MIN_IMAGENES} imágenes.";
        exit;
    }

    if (count($archivos) > $MAX_IMAGENES) {
        echo "Podés subir como máximo {$MAX_IMAGENES} imágenes.";
        exit;
    }

    foreach ($archivos as $archivo) {
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            echo "Ocurrió un error al subir una de las imágenes.";
            exit;
        }

        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $permitidas, true)) {
            echo "Formato de archivo no permitido. ";
            echo "Formatos válidos: " . implode(', ', $permitidas) . ".";
            exit;
        }

        if ($archivo['size'] > $limiteBytes) {
            echo "Cada imagen puede pesar como máximo 5 MB.";
            exit;
        }
    }
    $localidad = trim($_POST['localidad'] ?? '');

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
    $adicion = trim($_POST['adicion'] ?? '');

    //guarda las imagenes en carpeta uploads/1.png
    $carpetaEvento = 'evt_' . str_replace('.', '', uniqid('', true));
    $carpetaUploads = __DIR__ . '/../uploads/';
    $rutaCarpetaEvento = $carpetaUploads . $carpetaEvento . '/';

    if (!is_dir($carpetaUploads)) {
        if (!mkdir($carpetaUploads, 0777, true)) {
            echo "No se pudo crear la carpeta uploads.";
            exit;
        }
    }

    if (!mkdir($rutaCarpetaEvento, 0777, true)) {
        echo "No se pudo crear la carpeta del evento.";
        exit;
    }

    $primeraImagen = null;

    foreach ($archivos as $i => $archivo) {
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nombreImagen = ($i + 1) . '.' . $ext;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaCarpetaEvento . $nombreImagen)) {
            // si falla una, borramos lo que ya se había guardado
            array_map('unlink', glob($rutaCarpetaEvento . '*'));
            rmdir($rutaCarpetaEvento);
            echo "Ocurrió un error al subir las imágenes.";
            exit;
        }

        if ($primeraImagen === null) {
            $primeraImagen = $nombreImagen;
        }
    }

    $nombreArchivo = $carpetaEvento . '/' . $primeraImagen;

    $datos = [
        'titulo' => $titulo,
        'descripcion' => $descripcion,
        'categoria' => $categoria,
        'fechaEvento' => $fechaEvento,
        'horaEvento' => $horaEvento,
        'nombreArchivo' => $nombreArchivo,
        'zona' => $zona,
        'localidad' => $localidad,
        'lat' => $latEvento,
        'lon' => $lonEvento,
        'contacto_email' => $contactoEmail !== '' ? $contactoEmail : null,
        'contacto_telefono' => $contactoTelefono !== '' ? $contactoTelefono : null,
        'contacto_instagram' => $contactoInstagram !== '' ? $contactoInstagram : null,
        'contacto_web' => $contactoWeb !== '' ? $contactoWeb : null,
        'info_apto' => $infoApto !== '' ? $infoApto : null,
        'info_duracion' => $infoDuracion !== '' ? $infoDuracion : null,
        'info_capacidad' => $infoCapacidad !== '' ? $infoCapacidad : null,
        'info_accesibilidad' => $infoAccesibilidad !== '' ? $infoAccesibilidad : null,
        'info_mascotas' => $infoMascotas !== '' ? $infoMascotas : null,
        'adicion' => $adicion !== '' ? $adicion : null,
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
        //si no se guardó en la base, borramos la carpeta con las imágenes
        array_map('unlink', glob($rutaCarpetaEvento . '*'));
        rmdir($rutaCarpetaEvento);
        echo "No se pudo guardar la publicación.";
        exit;
    }

    header("Location: index.php?slug=panel");
    exit;

} else {

    $id_usuario = $_SESSION["id_usuario"];
    $rolUsuario = $_SESSION["rol"];

    $usuario = getUserById($id_usuario);

    $tpl = loadTPL("posts", "Publicar evento");
    $tpl = cargarNavbarComun($tpl, $id_usuario, $rolUsuario);
    $tpl = setVarTPL("USUARIO_NOMBRE", htmlspecialchars($usuario['display_name'] ?? '', ENT_QUOTES, 'UTF-8'), $tpl);
    $tpl = setVarTPL("PERFIL_SLUG", "panel", $tpl);
    $tpl = setVarTPL("ESTADO_SESION", "logueado", $tpl);
    $tpl = setVarTPL("ROL_CLASE", "es-anfitrion", $tpl);

    printTPL($tpl);
}

?>