<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'config.php';

$slug = "landing";

if(isset($_GET['slug'])){
	$slug = $_GET['slug'];
}

if(!file_exists('controllers/'.$slug.'_controller.php')){
	$slug = "error404";
}

if(isset($_SESSION['id_usuario'])){

	$rutas_libres = array("landing", "profile", "logout", "editar_evento", "eliminar_evento", "posts", "detalleEvento", "panelVisitante", "panel", "panelAdmin", "Configuracionvisitante", "guardar_ubicacion", "mapa", "confirmar", "toggleFavorito", "mensajes", "notificacionesLeidas", "cancelar_evento", "valorarEvento", "eliminarCuenta", "cambiarRol", "seguirAnfitrion","publicacionesSiguiendo", "misSeguidores");
	if(!in_array($slug, $rutas_libres)){
		if ($_SESSION['rol'] == "anfitrion") {
			 $slug = "panel"; 
		} 
		elseif ($_SESSION['rol'] == "visitante") {
			 $slug = "panelVisitante"; 
		} 
        elseif($_SESSION['rol'] === "admin") {
            $slug = "panelAdmin";
        }
    }

}else{

	$rutas_protegidas = array("panel", "panelVisitante", "panelAdmin", "profile", "editar_evento", "eliminar_evento", "posts", "Configuracionvisitante", "guardar_ubicacion", "confirmar", "toggleFavorito","mensajes", "cancelar_evento", "valorarEvento", "cambiarRol", "seguirAnfitrion","publicacionesSiguiendo", "misSeguidores");

	if(in_array($slug, $rutas_protegidas)){
		$slug = "landing";
	}
}

require_once 'controllers/'.$slug.'_controller.php';

?>