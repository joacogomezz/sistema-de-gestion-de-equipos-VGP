<?php
	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}

	// Solo usuarios autenticados pueden eliminar resguardos
	if (!isset($_SESSION['usuario']) || trim($_SESSION['usuario']) === '') {
		echo "0";
		exit;
	}

	require_once "../../clases/Resguardos.php";

	$idResguardo = isset($_POST['id_resguardo']) ? trim($_POST['id_resguardo']) : '';

	if ($idResguardo === '' || !is_numeric($idResguardo)) {
		echo "0";
		exit;
	}

	$obj = new Resguardos();
	if ($obj->eliminarResguardo($idResguardo)) {
		echo "1";
	} else {
		echo "0";
	}
?>
