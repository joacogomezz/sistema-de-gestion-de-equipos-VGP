<?php
	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}

	if (!isset($_SESSION['usuario']) || trim($_SESSION['usuario']) === '') {
		header('Content-Type: application/json');
		echo json_encode(array('ok' => false, 'eliminados' => 0));
		exit;
	}

	require_once "../../clases/Resguardos.php";

	$idResguardo = isset($_POST['id_resguardo']) ? (int) $_POST['id_resguardo'] : 0;
	if ($idResguardo <= 0) {
		header('Content-Type: application/json');
		echo json_encode(array('ok' => false, 'eliminados' => 0));
		exit;
	}

	$obj = new Resguardos();
	$r = $obj->eliminarResguardoOGrupo($idResguardo);
	header('Content-Type: application/json');
	echo json_encode(array('ok' => $r['ok'], 'eliminados' => $r['eliminados']));
