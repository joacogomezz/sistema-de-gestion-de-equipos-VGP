<?php
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Resguardos.php";
	require_once "../../clases/Equipos.php";

	header('Content-Type: application/json; charset=utf-8');

	/** Máximo de equipos por resguardo múltiple (una hoja al imprimir el PDF). */
	define('MAX_EQUIPOS_RESGUARDO_MULTIPLE', 10);

	$requeridos = array('area_destino', 'usuario', 'fecha_resguardo', 'id_equipos_json', 'numero_factura');
	foreach ($requeridos as $campo) {
		if (!isset($_POST[$campo]) || trim($_POST[$campo]) === '') {
			echo json_encode(array('ok' => false, 'mensaje' => 'Faltan datos obligatorios: ' . $campo));
			exit;
		}
	}

	$lista = json_decode($_POST['id_equipos_json'], true);
	if (!is_array($lista) || count($lista) === 0) {
		echo json_encode(array('ok' => false, 'mensaje' => 'No se indicaron equipos.'));
		exit;
	}
	if (count($lista) > MAX_EQUIPOS_RESGUARDO_MULTIPLE) {
		echo json_encode(array(
			'ok' => false,
			'mensaje' => 'El resguardo múltiple admite como máximo ' . MAX_EQUIPOS_RESGUARDO_MULTIPLE . ' equipos por documento (para que la impresión quepa en una hoja). Quite equipos de la lista o genere otro resguardo.'
		));
		exit;
	}

	$area_destino = trim($_POST['area_destino']);
	$usuario = trim($_POST['usuario']);
	$puesto = isset($_POST['puesto']) ? trim($_POST['puesto']) : '';
	$fecha_resguardo = trim($_POST['fecha_resguardo']);
	$numero_factura = trim($_POST['numero_factura']);

	$objResguardos = new Resguardos();
	$objEquipos = new equipos();
	$ids_creados = array();
	$errores = array();

	foreach ($lista as $item) {
		$id_equipo = isset($item['id_equipo']) ? intval($item['id_equipo']) : 0;
		if ($id_equipo <= 0) continue;

		$equipo = $objEquipos->obtenDatosEquipo($id_equipo);
		if (!$equipo) {
			$errores[] = 'Equipo ID ' . $id_equipo . ' no encontrado';
			continue;
		}

		$datos = array(
			'id_equipo' => $id_equipo,
			'id_departamento' => isset($equipo['id_departamento']) ? $equipo['id_departamento'] : null,
			'area_destino' => $area_destino,
			'usuario' => $usuario,
			'puesto' => $puesto,
			'fecha_resguardo' => $fecha_resguardo,
			'numero_factura' => $numero_factura,
			'modelo' => isset($equipo['modelo']) ? $equipo['modelo'] : '',
			'serie' => isset($equipo['numero_serie']) ? $equipo['numero_serie'] : '',
			'tipo_equipo' => isset($equipo['descripcion']) ? trim($equipo['descripcion']) : ''
		);

		$resultado = $objResguardos->guardarResguardo($datos);
		if ($resultado['ok']) {
			$ids_creados[] = $resultado['id'];
		} else {
			$errores[] = 'Equipo ' . (isset($equipo['codigo_equipo']) ? $equipo['codigo_equipo'] : $id_equipo);
		}
	}

	if (count($ids_creados) === 0) {
		echo json_encode(array(
			'ok' => false,
			'mensaje' => 'No se pudo guardar ningún resguardo.' . (count($errores) > 0 ? ' ' . implode(', ', $errores) : '')
		));
		exit;
	}

	// Marcar como grupo de resguardo múltiple (solo estos se agrupan al imprimir; los individuales no)
	$objResguardos->asignarGrupoResguardos($ids_creados);

	$mensaje = count($ids_creados) === count($lista)
		? 'Se generaron ' . count($ids_creados) . ' resguardos correctamente.'
		: 'Se generaron ' . count($ids_creados) . ' de ' . count($lista) . ' resguardos.' . (count($errores) > 0 ? ' Fallaron: ' . implode(', ', $errores) : '');

	echo json_encode(array(
		'ok' => true,
		'mensaje' => $mensaje,
		'ids' => $ids_creados
	));
