<?php 
	// Incluir archivo de seguridad
	require_once "../../seguridad.php";
	
	// Requerir autenticación
	requerirAutenticacion();
	
	require_once "../../clases/Conexion.php";
	require_once "../../clases/AltasBajas.php";

	$obj= new altasBajas();

	$equipos = json_decode($_POST['equipos'], true);
	$observacionesEquipos = isset($_POST['observacionesEquipos']) ? json_decode($_POST['observacionesEquipos'], true) : array();
	
	$datos=array(
		$_SESSION['iduser'],
		null, // departamento - se obtendrá de los equipos
		$_POST['hotelArea'], // hotel_area - nuevo campo
		$_POST['motivo'],
		0, // valorResidual - se calculará automáticamente
		$_POST['destino'],
		$_POST['observaciones']
	);

	$resultado = $obj->registrarBajaMasiva($equipos, $datos, $observacionesEquipos);

	if(count($resultado) == count($equipos)){
		echo 'success';
	}else{
		echo 'error';
	}
 ?>
