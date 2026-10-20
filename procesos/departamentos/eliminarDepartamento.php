<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Departamentos.php";

	$obj= new departamentos();

	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	echo $obj->eliminaDepartamento($_POST['idDepartamento'], $id_hotel);
?>
