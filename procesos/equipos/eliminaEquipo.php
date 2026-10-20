<?php 
	session_start();
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Equipos.php";

	$obj= new equipos();

	if(!isset($_SESSION['iduser'])){
		error_log("Error: No hay sesión de usuario activa");
		echo 0;
		exit;
	}

	if(!isset($_POST['idEquipo']) || trim((string)$_POST['idEquipo']) === ''){
		error_log("Error: ID de equipo no proporcionado");
		echo 0;
		exit;
	}

	$idEquipo = (int)$_POST['idEquipo'];
	if ($idEquipo <= 0) {
		echo 0;
		exit;
	}

	$esSuperAdmin = (int)$_SESSION['iduser'] === 1;
	if (!$esSuperAdmin) {
		$idHotelSesion = isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : 0;
		if ($idHotelSesion > 0 && !$obj->equipoPerteneceAlHotel($idEquipo, $idHotelSesion)) {
			error_log("Error: equipo $idEquipo no pertenece al hotel $idHotelSesion del usuario");
			echo 0;
			exit;
		}
	}

	// Log para debugging
	error_log("=== Eliminación de equipo ===");
	error_log("ID de equipo a eliminar: " . $idEquipo);

	$resultado = $obj->eliminaEquipo((string)$idEquipo);
	
	if($resultado){
		error_log("Equipo eliminado exitosamente");
		echo 1;
	}else{
		error_log("Error al eliminar equipo");
		echo 0;
	}
 ?>
