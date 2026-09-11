<?php



require_once __DIR__ . '/../models/Panel.php';
require_once __DIR__ . '/assets/tpl_engine/tpl_engine.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);




if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php?slug=landing");
    exit;
}

$id_usuario = $_SESSION["id_usuario"];



if (isset($_POST["btn_editar"])) {

    $id_evento = $_POST["id_evento"] ?? null;

    $titulo = trim($_POST["titulo"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $fecha = $_POST["fecha"] ?? "";
    $hora = $_POST["hora"] ?? "";
    $zona = trim($_POST["ubicacion"] ?? "");

    $contactoEmail = trim($_POST["contacto_email"] ?? "");
    $contactoTelefono = trim($_POST["contacto_telefono"] ?? "");
    $contactoInstagram = trim($_POST["contacto_instagram"] ?? "");
    $contactoWeb = trim($_POST["contacto_web"] ?? "");

    $infoApto = trim($_POST["info_apto"] ?? "");
    $infoDuracion = trim($_POST["info_duracion"] ?? "");
    $infoCapacidad = trim($_POST["info_capacidad"] ?? "");
    $infoAccesibilidad = trim($_POST["info_accesibilidad"] ?? "");
    $infoMascotas = trim($_POST["info_mascotas"] ?? "");

    $adicional = trim($_POST["adicional"] ?? "");


    

    if (!$id_evento) {
        echo "No se encontró el evento.";
        exit;
    }




    if (
        $titulo === "" ||
        $descripcion === "" ||
        $categoria === "" ||
        $fecha === "" ||
        $hora === "" ||
        $zona === ""
    ) {
        echo "Todos los campos son obligatorios.";
        exit;
    }


 

    $categoriasPermitidas = [
        "musica",
        "deporte",
        "feria",
        "arte",
        "gastronomia",
        "tecnologia",
        "otro"
    ];

    if (!in_array($categoria, $categoriasPermitidas, true)) {
        echo "Categoría no válida.";
        exit;
    }




    $evento = getEventoDeUsuario(
        $id_evento,
        $id_usuario
    );

    if (!$evento) {
        echo "No tenés permiso para editar este evento.";
        exit;
    }


 

    $resultado = editarMiEvento(
        $id_evento,
        $id_usuario,
        $titulo,
        $descripcion,
        $categoria,
        $fecha,
        $hora,
        $zona,
        $contactoEmail,
        $contactoTelefono,
        $contactoInstagram,
        $contactoWeb,
        $infoApto,
        $infoDuracion,
        $infoCapacidad,
        $infoAccesibilidad,
        $infoMascotas,
        $adicional
    );


    if (!$resultado) {
        echo "No se pudo modificar el evento.";
        exit;
    }


   

    header("Location: index.php?slug=panel");
    exit;
}






$id_evento = $_GET["id"] ?? null;


if (!$id_evento) {
    echo "No se encontró el evento.";
    exit;
}




$evento = getEventoDeUsuario(
    $id_evento,
    $id_usuario
);


if (!$evento) {
    echo "No tenés permiso para editar este evento.";
    exit;
}




$tpl = loadTPL(
    "editar_evento",
    "Editar evento"
);



$tpl = setVarTPL(
    "ID_EVENTO",
    $evento["id_evento"] ?? "",
    $tpl
);

$tpl = setVarTPL(
    "TITULO",
    htmlspecialchars($evento["titulo"] ?? ""),
    $tpl
);

$tpl = setVarTPL(
    "DESCRIPCION",
    htmlspecialchars($evento["descripcion"] ?? ""),
    $tpl
);

$tpl = setVarTPL(
    "FECHA",
    $evento["fecha"] ?? "",
    $tpl
);

$tpl = setVarTPL(
    "HORA",
    $evento["hora"] ?? "",
    $tpl
);

$tpl = setVarTPL(
    "ZONA",
    htmlspecialchars($evento["zona"] ?? ""),
    $tpl
);


// Contacto del organizador

$tpl = setVarTPL(
    "CONTACTO_EMAIL",
    htmlspecialchars($evento["contacto_email"] ?? ""),
    $tpl
);

$tpl = setVarTPL(
    "CONTACTO_TELEFONO",
    htmlspecialchars($evento["contacto_telefono"] ?? ""),
    $tpl
);

$tpl = setVarTPL(
    "CONTACTO_INSTAGRAM",
    htmlspecialchars($evento["contacto_instagram"] ?? ""),
    $tpl
);

$tpl = setVarTPL(
    "CONTACTO_WEB",
    htmlspecialchars($evento["contacto_web"] ?? ""),
    $tpl
);


// Información útil - campos de texto libre

$tpl = setVarTPL(
    "INFO_DURACION",
    htmlspecialchars($evento["info_duracion"] ?? ""),
    $tpl
);

$tpl = setVarTPL(
    "INFO_CAPACIDAD",
    htmlspecialchars($evento["info_capacidad"] ?? ""),
    $tpl
);


// Información útil - APTO PARA (select)

$opcionesApto = [
    "" => "SIN_ESPECIFICAR",
    "Todo público" => "TODO_PUBLICO",
    "Mayores de 18" => "MAYORES_18",
    "Familiar / niños" => "FAMILIAR_NINOS",
];

foreach ($opcionesApto as $valor => $sufijo) {
    $seleccionada = (($evento["info_apto"] ?? "") === $valor) ? "selected" : "";
    $tpl = setVarTPL("INFO_APTO_{$sufijo}_SELECTED", $seleccionada, $tpl);
}


// Información útil - ACCESIBILIDAD (select)

$opcionesAccesibilidad = [
    "" => "SIN_ESPECIFICAR",
    "Acceso para sillas de ruedas" => "SILLAS_RUEDAS",
    "Sin acceso adaptado" => "SIN_ACCESO",
];

foreach ($opcionesAccesibilidad as $valor => $sufijo) {
    $seleccionada = (($evento["info_accesibilidad"] ?? "") === $valor) ? "selected" : "";
    $tpl = setVarTPL("INFO_ACCESIBILIDAD_{$sufijo}_SELECTED", $seleccionada, $tpl);
}


// Información útil - MASCOTAS (select)

$opcionesMascotas = [
    "" => "SIN_ESPECIFICAR",
    "Bienvenidas con correa" => "BIENVENIDAS",
    "No se permiten mascotas" => "NO_PERMITIDAS",
];

foreach ($opcionesMascotas as $valor => $sufijo) {
    $seleccionada = (($evento["info_mascotas"] ?? "") === $valor) ? "selected" : "";
    $tpl = setVarTPL("INFO_MASCOTAS_{$sufijo}_SELECTED", $seleccionada, $tpl);
}


// A tener en cuenta

$tpl = setVarTPL(
    "ADICIONAL",
    htmlspecialchars($evento["adicional"] ?? ""),
    $tpl
);




$categorias = [
    "musica",
    "deporte",
    "feria",
    "arte",
    "gastronomia",
    "tecnologia",
    "otro"
];


foreach ($categorias as $categoria) {

    $seleccionada = "";

    if (($evento["categoria"] ?? "") === $categoria) {
        $seleccionada = "selected";
    }

    $tpl = setVarTPL(
        strtoupper($categoria),
        $seleccionada,
        $tpl
    );
}




printTPL($tpl);

?>