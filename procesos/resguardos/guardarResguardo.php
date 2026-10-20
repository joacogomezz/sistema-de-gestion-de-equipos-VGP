<?php
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Resguardos.php";

	header('Content-Type: application/json; charset=utf-8');

	$requeridos = array('id_equipo', 'area_destino', 'usuario', 'fecha_resguardo');
	foreach ($requeridos as $campo) {
		if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
			echo json_encode(array('ok' => false, 'mensaje' => 'Faltan datos obligatorios: ' . $campo));
			exit;
		}
	}

	$datos = array(
		'id_equipo' => $_POST['id_equipo'],
		'id_departamento' => isset($_POST['id_departamento']) ? $_POST['id_departamento'] : null,
		'area_destino' => trim($_POST['area_destino']),
		'usuario' => trim($_POST['usuario']),
		'puesto' => isset($_POST['puesto']) ? trim($_POST['puesto']) : '',
		'fecha_resguardo' => trim($_POST['fecha_resguardo']),
		'numero_factura' => isset($_POST['numero_factura']) ? trim($_POST['numero_factura']) : '',
		'modelo' => isset($_POST['modelo']) ? trim($_POST['modelo']) : '',
		'serie' => isset($_POST['serie']) ? trim($_POST['serie']) : '',
		'tipo_equipo' => isset($_POST['tipo_equipo']) ? trim($_POST['tipo_equipo']) : ''
	);

	$obj = new Resguardos();
	$resultado = $obj->guardarResguardo($datos);

	if ($resultado['ok']) {
		echo json_encode(array('ok' => true, 'id_resguardo' => $resultado['id'], 'mensaje' => 'Resguardo guardado correctamente'));
	} else {
		echo json_encode(array('ok' => false, 'mensaje' => 'Error al guardar el resguardo'));
	}
