<?php
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Equipos.php";

	header('Content-Type: application/json; charset=utf-8');

	if (!isset($_POST['codigo']) || trim($_POST['codigo']) === '') {
		echo json_encode(array('error' => 'Indique el código o el número de serie del equipo'));
		exit;
	}

	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	if ($es_super_admin && isset($_POST['id_hotel']) && (int)$_POST['id_hotel'] > 0) {
		$id_hotel = (int)$_POST['id_hotel'];
	}
	$obj = new equipos();
	$datos = $obj->obtenerEquipoPorCodigo(trim($_POST['codigo']), $id_hotel);

	if ($datos) {
		echo json_encode($datos);
	} else {
		echo json_encode(array('error' => 'No se encontró equipo con ese código o número de serie, o está dado de baja'));
	}
