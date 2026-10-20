<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/AltasBajas.php";

	$obj= new altasBajas();

	$datos=array(
		$_POST['equipoAlta'],
		$_SESSION['iduser'],
		$_POST['departamentoAlta'],
		$_POST['motivoAlta'],
		$_POST['observacionesAlta']
	);

	echo $obj->registrarAlta($datos);
 ?>
