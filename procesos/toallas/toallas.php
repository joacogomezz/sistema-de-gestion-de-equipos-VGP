<?php
	// Control e historial de toallas para el rol TOALLEROS (Villa del Arco). Usa la lógica existente de clases/Toallas.php.
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Toallas.php";

	header('Content-Type: application/json; charset=utf-8');
	function responder($d, $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

	$rol = strtoupper(trim((string)($_SESSION['rol'] ?? '')));
	$t = new Toallas();
	$idHotel = (int)($_SESSION['id_hotel'] ?? 0);
	if ($rol !== 'TOALLEROS' || !$t->puedeOperarEnArco($idHotel)) responder(array('ok' => false, 'mensaje' => 'Solo los toalleros de Villa del Arco usan este módulo.'), 403);
	$idUsuario = (int)$_SESSION['iduser'];
	$accion = $_REQUEST['accion'] ?? 'estado';
	$LIMITE_H = 3;

	if ($accion === 'estado') {
		$pend = array(); $tarde = 0;
		$r = $t->obtenerSolicitudesActivasHotel($idHotel, 300);
		while ($r && ($f = mysqli_fetch_assoc($r))) {
			$p = max(0, (int)$f['cantidad'] - (int)$f['cantidad_regresada']);
			if ($p <= 0) continue;
			$ts = strtotime($f['fecha_solicitud']);
			if (time() - $ts >= $LIMITE_H * 3600) $tarde++;
			$pend[] = array('id' => (int)$f['id_solicitud'], 'hab' => (string)$f['numero_habitacion'], 'hues' => (string)$f['nombre_huesped'], 'ent' => (int)$f['cantidad'],
				'reg' => (int)$f['cantidad_regresada'], 'obs' => (string)$f['observaciones'], 't' => $ts * 1000, 'u' => (string)$f['usuario_registro']);
		}
		$res = $t->obtenerResumenToallas($idHotel);
		$h = mysqli_query((new conectar())->conexion(), "SELECT nombre FROM hoteles WHERE id_hotel = $idHotel");
		responder(array('ok' => true, 'ahora' => time() * 1000, 'limite' => $LIMITE_H, 'pendientes' => $pend, 'tarde' => $tarde,
			'entregadasHoy' => (int)$res['total_entregadas'], 'porDevolver' => (int)$res['pendientes_activas'], 'faltantesHoy' => (int)$res['no_devueltas_historial'],
			'hotel' => $h && ($x = mysqli_fetch_row($h)) ? $x[0] : '', 'usuario' => trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''))));
	}

	if ($accion === 'historial') {
		$lista = array();
		$r = $t->obtenerMovimientosHotel($idHotel, 2000);
		while ($r && ($f = mysqli_fetch_assoc($r))) {
			$lista[] = array('id' => (int)$f['id_movimiento'], 'fecha' => (string)$f['fecha_movimiento'], 'hab' => (string)$f['numero_habitacion'], 'hues' => (string)$f['nombre_huesped'],
				'cant' => (int)$f['cantidad'], 'est' => $f['estado_cierre'] === 'NO_DEVUELTO' ? 'falta' : 'ok', 'u' => (string)$f['nombre_usuario'], 'obs' => (string)$f['observaciones']);
		}
		$cfg = $t->obtenerConfiguracionRetencion($idHotel);
		responder(array('ok' => true, 'lista' => $lista, 'retencion' => $cfg['cantidad'] . '_' . $cfg['unidad']));
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(array('ok' => false, 'mensaje' => 'Método no permitido.'), 405);

	if ($accion === 'entregar') {
		$hab = trim((string)($_POST['habitacion'] ?? ''));
		$hues = trim((string)($_POST['huesped'] ?? ''));
		$cant = (int)($_POST['cantidad'] ?? 0);
		$obs = mb_substr(trim((string)($_POST['observaciones'] ?? '')), 0, 255);
		$err = array();
		if (!preg_match('/^[A-Za-z0-9-]{1,10}$/', $hab)) $err['habitacion'] = $hab === '' ? 'Escribe el número de habitación.' : 'Usa solo números o letras, por ejemplo 1204.';
		if (mb_strlen($hues) < 3 || mb_strlen($hues) > 120) $err['huesped'] = 'Escribe el nombre del huésped.';
		if ($cant < 1 || $cant > 20) $err['cantidad'] = 'Entre 1 y 20 toallas.';
		if ($err) responder(array('ok' => false, 'errores' => $err));
		$res = $t->registrarSolicitudEntrega($idHotel, $idUsuario, $hab, $hues, $cant, $obs);
		responder($res);
	}
	if ($accion === 'devolver') responder($t->registrarDevolucionSolicitud($idHotel, (int)($_POST['id'] ?? 0), (int)($_POST['cantidad'] ?? 0), $idUsuario));
	if ($accion === 'faltante') responder($t->archivarSinDevolucion($idHotel, (int)($_POST['id'] ?? 0), $idUsuario));
	if ($accion === 'reactivar') responder($t->reactivarDesdeHistorialNoDevuelto($idHotel, (int)($_POST['id'] ?? 0), $idUsuario));
	if ($accion === 'eliminar') responder($t->eliminarMovimientoHistorial($idHotel, (int)($_POST['id'] ?? 0)));
	if ($accion === 'retencion') {
		$p = explode('_', (string)($_POST['opcion'] ?? ''));
		$validas = array('1_MONTH', '2_MONTH', '90_DAY', '1_YEAR');
		if (!in_array(implode('_', $p), $validas, true)) responder(array('ok' => false, 'mensaje' => 'Opción no válida.'));
		responder($t->actualizarConfiguracionRetencion($idHotel, (int)$p[0], $p[1]));
	}
	if ($accion === 'borrar_todo') responder($t->borrarTodoHistorialConPassword($idHotel, $idUsuario, (string)($_POST['password'] ?? '')));

	responder(array('ok' => false, 'mensaje' => 'Acción no válida.'), 400);
