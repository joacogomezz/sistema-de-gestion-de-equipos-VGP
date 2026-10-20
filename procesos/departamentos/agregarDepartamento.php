<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Departamentos.php";

	$obj= new departamentos();

	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : 1;
	if ($es_super_admin && isset($_POST['id_hotel_context']) && (int)$_POST['id_hotel_context'] > 0) {
		$id_hotel = (int)$_POST['id_hotel_context'];
	}
	$datos=array(
		$id_hotel,
		$_SESSION['iduser'],
		$_POST['nombreDepartamento'],
		$_POST['descripcionDepartamento']
	);

	echo $obj->agregarDepartamento($datos);
?>
