<?php

require_once __DIR__ . '/../models/Landing.php';
require_once __DIR__ . '/../models/Usuarios.php';
require_once __DIR__ . '/../models/Posts.php';
require_once __DIR__ . '/../models/Notificaciones.php';
require_once __DIR__ . "/assets/tpl_engine/tpl_engine.php";
date_default_timezone_set('America/Argentina/Buenos_Aires');


function armarPaginacion($paginaActual, $totalPaginas)
{
    if ($totalPaginas <= 1) {
        return '';
    }

    $link = function ($pagina) {

        $params = $_GET;

        $params['slug'] = 'landing';
        $params['pagina'] = $pagina;

        return 'index.php?' .
            htmlspecialchars(
                http_build_query($params),
                ENT_QUOTES,
                'UTF-8'
            );
    };

    $html = '';

    if ($paginaActual > 1) {

        $html .=
            '<a class="pag-btn" href="' .
            $link($paginaActual - 1) .
            '" data-pagina="' .
            ($paginaActual - 1) .
            '">Anterior</a>';
    }


    $ultimaMostrada = 0;

    for ($i = 1; $i <= $totalPaginas; $i++) {

        $mostrar =
            ($i === 1 ||
             $i === $totalPaginas ||
             abs($i - $paginaActual) <= 1);

        if (!$mostrar) {
            continue;
        }


        if (
            $ultimaMostrada &&
            $i - $ultimaMostrada > 1
        ) {

            $html .=
                '<span class="pag-puntos">…</span>';
        }


        $clase =
            $i === $paginaActual
            ? 'pag-btn pag-activa'
            : 'pag-btn';


        $html .=
            '<a class="' .
            $clase .
            '" href="' .
            $link($i) .
            '" data-pagina="' .
            $i .
            '">' .
            $i .
            '</a>';


        $ultimaMostrada = $i;
    }


    if ($paginaActual < $totalPaginas) {

        $html .=
            '<a class="pag-btn" href="' .
            $link($paginaActual + 1) .
            '" data-pagina="' .
            ($paginaActual + 1) .
            '">Siguiente</a>';
    }


    return $html;
}



$categoriasPermitidas = [
    'musica'      => 'Música',
    'deporte'     => 'Deportes',
    'feria'       => 'Ferias',
    'arte'        => 'Arte',
    'gastronomia' => 'Gastronomía',
    'tecnologia'  => 'Tecnología',
    'otro'        => 'Otro',
];



$eventosPorPagina = 10;


$categoria =$_GET['categoria'] ?? '';

$buscar = trim($_GET['buscar'] ?? '');

$localidadSel = trim($_GET['localidad'] ?? '');

$fechaDesde = trim($_GET['fecha_desde'] ?? '');

$fechaHasta =trim($_GET['fecha_hasta'] ?? '');





$orden = $_GET['orden'] ?? 'asc';


if (!in_array($orden,['asc', 'desc', 'ranking'], true)) {

    $orden = 'asc';
}


if ($categoria !== '' && !array_key_exists($categoria, $categoriasPermitidas)) {
    echo "Categoría no válida.";
    exit;
}



$fechaDesdeObj =
    $fechaDesde !== ''
    ? DateTime::createFromFormat(
        'Y-m-d',
        $fechaDesde
    )
    : false;


$fechaHastaObj =
    $fechaHasta !== ''
    ? DateTime::createFromFormat(
        'Y-m-d',
        $fechaHasta
    )
    : false;


if (
    $fechaDesde !== '' &&
    !$fechaDesdeObj
) {

    $fechaDesde = '';
}


if (
    $fechaHasta !== '' &&
    !$fechaHastaObj
) {

    $fechaHasta = '';
}


if (
    $fechaDesde !== '' &&
    $fechaHasta !== '' &&
    $fechaHasta < $fechaDesde
) {

    $fechaHasta = '';
}



$eventos =
    getEventosFiltrados(
        $buscar,
        $categoria,
        $localidadSel,
        $fechaDesde,
        $fechaHasta
    );


$ranking = $_GET['ranking'] ?? '';

if (!in_array($ranking, ['', '1', '2', '3', '4', '5'], true)) {
    $ranking = '';
}

if ($ranking !== '') {

    $puntuacionMinima = (float) $ranking;

    $eventos = array_filter(
        $eventos,
        function ($evento) use ($puntuacionMinima) {

            if (
                !isset($evento['promedio_valoracion']) ||
                $evento['promedio_valoracion'] === null
            ) {
                return false;
            }

            return
                (float) $evento['promedio_valoracion']
                >= $puntuacionMinima;
        }
    );

    $eventos = array_values($eventos);
}


$temporalidad = $_GET['temporalidad'] ?? 'vigentes';


$temporalidadesPermitidas = [
    'vigentes',
    'pasados',
    'todos'
];


if (!in_array($temporalidad, $temporalidadesPermitidas,true)) {
    $temporalidad = 'vigentes';
}



if ($temporalidad === 'todos') {

    $eventosMostrar = $eventos;

} else {

    $eventosMostrar = array_filter(
        $eventos,
        function ($evento) use ($temporalidad) {

            $esPasado = eventoYaPaso($evento);

            return $temporalidad === 'pasados' ? $esPasado : !$esPasado;
        }
    );
}




$eventosMostrar =
    array_values($eventosMostrar);


usort(
    $eventosMostrar,
    function ($a, $b) use ($orden) {


        if ($orden === 'ranking') {


            $promedioA =
                $a['promedio_valoracion'] ?? null;


            $promedioB =
                $b['promedio_valoracion'] ?? null;


            $cantidadA =
                (int) (
                    $a['cantidad_valoraciones']
                    ?? 0
                );


            $cantidadB =
                (int) (
                    $b['cantidad_valoraciones']
                    ?? 0
                );


            

            if (
                $cantidadA === 0 &&
                $cantidadB > 0
            ) {

                return 1;
            }


            if (
                $cantidadA > 0 &&
                $cantidadB === 0
            ) {

                return -1;
            }


           //el null se convierte a 0 para poder comparar

            if ($promedioA === null) {
                $promedioA = 0;
            }


            if ($promedioB === null) {
                $promedioB = 0;
            }


           

            if ($promedioA < $promedioB) {

                return 1;
            }


            if ($promedioA > $promedioB) {

                return -1;
            }


            

            if ($cantidadA < $cantidadB) {

                return 1;
            }


            if ($cantidadA > $cantidadB) {

                return -1;
            }



            return strcmp(
                $a['fecha'] ?? '',
                $b['fecha'] ?? ''
            );
        }



 
        $cmp =
            strcmp(
                $a['fecha'] ?? '',
                $b['fecha'] ?? ''
            );


        return
            $orden === 'desc'
            ? -$cmp
            : $cmp;
    }
);




$totalEventos =
    count($eventosMostrar);


$totalPaginas =
    max(
        1,
        (int) ceil(
            $totalEventos /
            $eventosPorPagina
        )
    );


$paginaActual =
    (int) (
        $_GET['pagina'] ?? 1
    );


$paginaActual =
    min(
        max(
            $paginaActual,
            1
        ),
        $totalPaginas
    );


$eventosPagina =
    array_slice(
        $eventosMostrar,
        ($paginaActual - 1)
        * $eventosPorPagina,
        $eventosPorPagina
    );



$tpl =
    loadTPL(
        "landing",
        "Landing"
    );





$estaLogueado =
    isset($_SESSION["id_usuario"]);


$perfilSlug = '';

$usuarioNombreNav = '';



if ($estaLogueado) {


    if (
        $_SESSION['rol'] === 'visitante'
    ) {

        $perfilSlug =
            'panelVisitante';

    } elseif (
        $_SESSION['rol'] === 'admin'
    ) {

        $perfilSlug =
            'panelAdmin';

    } else {

        $perfilSlug =
            'panel';
    }


    $datosUsuarioNav =
        getUserById(
            $_SESSION['id_usuario']
        );


    $usuarioNombreNav =
        $datosUsuarioNav['display_name']
        ?? '';
}




$tpl =
    setVarTPL(
        "ESTADO_SESION",
        $estaLogueado
            ? "logueado"
            : "invitado",
        $tpl
    );


$tpl =
    setVarTPL(
        "PERFIL_SLUG",
        htmlspecialchars(
            $perfilSlug,
            ENT_QUOTES,
            'UTF-8'
        ),
        $tpl
    );


$tpl =
    setVarTPL(
        "USUARIO_NOMBRE",
        htmlspecialchars(
            $usuarioNombreNav,
            ENT_QUOTES,
            'UTF-8'
        ),
        $tpl
    );





$rolClase = '';


if ($estaLogueado) {


    if (
        $_SESSION['rol'] === 'visitante'
    ) {

        $rolClase =
            'es-visitante';

    } elseif (
        $_SESSION['rol'] !== 'admin'
    ) {

        $rolClase =
            'es-anfitrion';
    }
}


$tpl =
    setVarTPL(
        "ROL_CLASE",
        $rolClase,
        $tpl
    );





$mostrarAvisoUbicacion =
    !empty(
        $_SESSION['aviso_ubicacion']
    );


unset(
    $_SESSION['aviso_ubicacion']
);
$tpl =
    setVarTPL(
        "AVISO_UBICACION",
        $mostrarAvisoUbicacion
            ? "1"
            : "0",
        $tpl
    );




$localidades =
    getLocalidadesDisponibles();


$flags =
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP;





if ($estaLogueado) {

    $id_anfitrion =
        $_SESSION['id_usuario'];

    $rolUsuario =
        $_SESSION['rol']
        ?? 'visitante';


    $tpl =
        cargarNavbarComun(
            $tpl,
            $id_anfitrion,
            $rolUsuario
        );

} else {

    $tpl =
        setVarTPL(
            "BADGE_CLASE",
            "oculto",
            $tpl
        );


    $tpl =
        setVarTPL(
            "BADGE_NOTIFICACIONES",
            "0",
            $tpl
        );


    $tpl =
        setVarTPL(
            "LISTA_NOTIFICACIONES",
            "",
            $tpl
        );
}




$tpl =
    setVarTPL(
        "LOCALIDADES_JSON",
        json_encode(
            $localidades,
            $flags
        ),
        $tpl
    );


$tpl =
    setVarTPL(
        "LOCALIDAD_SELECCIONADA",
        htmlspecialchars(
            $localidadSel,
            ENT_QUOTES,
            'UTF-8'
        ),
        $tpl
    );


$tpl =
    setVarTPL(
        "CATEGORIA_SELECCIONADA",
        htmlspecialchars(
            $categoria,
            ENT_QUOTES,
            'UTF-8'
        ),
        $tpl
    );


$tpl =
    setVarTPL(
        "FECHA_DESDE",
        htmlspecialchars(
            $fechaDesde,
            ENT_QUOTES,
            'UTF-8'
        ),
        $tpl
    );


$tpl =
    setVarTPL(
        "FECHA_HASTA",
        htmlspecialchars(
            $fechaHasta,
            ENT_QUOTES,
            'UTF-8'
        ),
        $tpl
    );


$tpl =
    setVarTPL(
        "TEMPORALIDAD_SELECCIONADA",
        htmlspecialchars(
            $temporalidad,
            ENT_QUOTES,
            'UTF-8'
        ),
        $tpl
    );

$tpl = setVarTPL("TEMPORALIDAD_VIGENTES", $temporalidad === 'vigentes' ? 'selected' : '', $tpl);
$tpl = setVarTPL("TEMPORALIDAD_PASADOS",  $temporalidad === 'pasados'  ? 'selected' : '', $tpl);
$tpl = setVarTPL("TEMPORALIDAD_TODOS",    $temporalidad === 'todos'    ? 'selected' : '', $tpl);





$tpl =
    setVarTPL(
        "ORDEN_ASC",
        $orden === 'asc'
            ? 'selected'
            : '',
        $tpl
    );


$tpl =
    setVarTPL(
        "ORDEN_DESC",
        $orden === 'desc'
            ? 'selected'
            : '',
        $tpl
    );


$tpl =
    setVarTPL(
        "ORDEN_RANKING",
        $orden === 'ranking'
            ? 'selected'
            : '',
        $tpl
    );




$tpl =
    setVarTPL(
        "ZONAS_ACTIVAS",
        count($localidades),
        $tpl
    );


$tpl =
    setVarTPL(
        "EVENTOS",
        mostrarEventos(
            $eventosPagina
        ),
        $tpl
    );


$tpl =
    setVarTPL(
        "CANTIDAD_EVENTOS",
        $totalEventos,
        $tpl
    );


$tpl =
    setVarTPL(
        "BUSQUEDA",
        htmlspecialchars(
            $buscar,
            ENT_QUOTES,
            'UTF-8'
        ),
        $tpl
    );




$paginacionHtml =
    armarPaginacion(
        $paginaActual,
        $totalPaginas
    );


$tpl =
    setVarTPL(
        "PAGINA",
        $paginaActual,
        $tpl
    );


$tpl =
    setVarTPL(
        "PAGINACION",
        $paginacionHtml,
        $tpl
    );


$tpl =
    setVarTPL(
        "PAGINACION_ARRIBA",
        $paginacionHtml,
        $tpl
    );

$tpl = setVarTPL(
    "RANKING_1",
    $ranking === '1' ? 'selected' : '',
    $tpl
);

$tpl = setVarTPL(
    "RANKING_2",
    $ranking === '2' ? 'selected' : '',
    $tpl
);

$tpl = setVarTPL(
    "RANKING_3",
    $ranking === '3' ? 'selected' : '',
    $tpl
);

$tpl = setVarTPL(
    "RANKING_4",
    $ranking === '4' ? 'selected' : '',
    $tpl
);

$tpl = setVarTPL(
    "RANKING_5",
    $ranking === '5' ? 'selected' : '',
    $tpl
);



printTPL($tpl);

?>