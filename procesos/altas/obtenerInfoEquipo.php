<?php 
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Equipos.php";

	$obj= new equipos();

	$datos=$obj->obtenDatosEquipo($_POST['idEquipo']);

	echo json_encode($datos);
 ?>
