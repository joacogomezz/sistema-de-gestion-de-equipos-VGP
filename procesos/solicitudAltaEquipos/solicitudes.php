<?php
	// Solicitudes de alta: catálogos, solicitudes recientes y guardado (misma estructura que guardarSolicitudAltaEquipos.php).
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../config.php";
	require_once "../../clases/Conexion.php";

	header('Content-Type: application/json; charset=utf-8');
	function responder($d, $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

	$cx = (new conectar())->conexion();
	mysqli_set_charset($cx, 'utf8mb4');
	function consulta($cx, $sql, $tipos = '', $params = array()) {
		$st = mysqli_prepare($cx, $sql);
		if (!$st) return false;
		if ($tipos !== '') mysqli_stmt_bind_param($st, $tipos, ...$params);
		if (!mysqli_stmt_execute($st)) return false;
		$res = mysqli_stmt_get_result($st);
		return $res === false ? $st : $res;
	}
	function filas($cx, $sql) { $r = mysqli_query($cx, $sql); $o = array(); while ($r && ($f = mysqli_fetch_assoc($r))) $o[] = $f; return $o; }

	$esSuper = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$idHotel = $esSuper ? 0 : (int)($_SESSION['id_hotel'] ?? 0);
	$accion = $_REQUEST['accion'] ?? 'listar';

	if ($accion === 'listar') {
		$wh = $idHotel ? " WHERE id_hotel = $idHotel" : '';
		$cat = function ($tabla, $id, $extra = '') use ($cx, $wh) {
			return array_map(function ($f) use ($id) { return array('id' => (int)$f[$id], 'nombre' => $f['nombre'], 'idHotel' => (int)$f['id_hotel']); },
				filas($cx, "SELECT $id, nombre, id_hotel FROM $tabla" . $wh . ($wh ? $extra : ($extra ? ' WHERE 1=1' . $extra : '')) . " ORDER BY nombre"));
		};
		$recientes = array_map(function ($f) {
			return array('id' => (int)$f['id_solicitud'], 'numero' => (string)$f['numero_solicitud'], 'fecha' => (string)$f['fecha_solicitud'], 'cantidad' => (int)$f['cantidad_equipos'],
				'depto' => (string)$f['depto'], 'hotel' => (string)$f['hotel'], 'usuario' => trim($f['un'] . ' ' . $f['ua']), 'registro' => (string)$f['fecha_registro'],
				'c1' => (string)$f['clasificacion_1'], 'c2' => (string)$f['clasificacion_2']);
		}, filas($cx, "SELECT s.*, d.nombre AS depto, h.nombre AS hotel, u.nombre AS un, u.apellido AS ua FROM solicitud_alta_equipos s
			LEFT JOIN departamentos d ON d.id_departamento = s.id_departamento_solicitante LEFT JOIN hoteles h ON h.id_hotel = s.id_hotel
			LEFT JOIN usuarios u ON u.id_usuario = s.id_usuario" . ($idHotel ? " WHERE s.id_hotel = $idHotel" : '') . " ORDER BY s.id_solicitud DESC LIMIT 50"));
		$clas = filas($cx, "SELECT DISTINCT clasificacion_1 AS a, clasificacion_2 AS b FROM solicitud_alta_equipos");
		responder(array('ok' => true, 'superAdmin' => $esSuper,
			'deptos' => $cat('departamentos', 'id_departamento'), 'areas' => $cat('areas', 'id_area', ' AND activo = 1'), 'fases' => $cat('fases', 'id_fase', ' AND activo = 1'),
			'recientes' => $recientes,
			'c1' => array_values(array_unique(array_filter(array_merge(array('Equipo de cómputo', 'Telecomunicaciones', 'Electrónico', 'Mobiliario', 'Línea blanca', 'Herramienta'), array_column($clas, 'a'))))),
			'c2' => array_values(array_unique(array_filter(array_merge(array('Operativo', 'Administrativo', 'Uso del huésped', 'Áreas públicas'), array_column($clas, 'b'))))),
			'responsable' => trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''))));
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(array('ok' => false, 'mensaje' => 'Método no permitido.'), 405);

	if ($accion === 'guardar') {
		$d = json_decode((string)($_POST['datos'] ?? ''), true);
		if (!is_array($d)) responder(array('ok' => false, 'mensaje' => 'Datos incompletos.'));
		$numero = trim((string)($d['numero'] ?? ''));
		$fecha = trim((string)($d['fecha'] ?? ''));
		$idDep = (int)($d['id_departamento'] ?? 0);
		$c1 = mb_substr(trim((string)($d['c1'] ?? '')), 0, 200);
		$c2 = mb_substr(trim((string)($d['c2'] ?? '')), 0, 200);
		$filas = is_array($d['filas'] ?? null) ? $d['filas'] : array();
		$err = array();
		if ($numero === '' || mb_strlen($numero) > 50) $err['numero'] = 'Escribe el número de solicitud.';
		if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $err['fecha'] = 'Fecha no válida.';
		if (!$idDep) $err['depto'] = 'Elige el departamento solicitante.';
		if (!$filas) $err['filas'] = 'Agrega al menos un equipo.';
		if (count($filas) > 200) $err['filas'] = 'Máximo 200 equipos por solicitud.';
		if ($err) responder(array('ok' => false, 'errores' => $err));

		// Equipos: existen, sin repetir y del hotel del usuario; la solicitud toma el hotel de los equipos
		$hotelSol = null; $vistos = array(); $limpias = array();
		foreach ($filas as $f) {
			$id = (int)($f['id_equipo'] ?? 0);
			if (isset($vistos[$id])) continue; $vistos[$id] = 1;
			$r = consulta($cx, "SELECT e.codigo_equipo, COALESCE(e.id_hotel, dd.id_hotel) AS id_hotel FROM equipos e LEFT JOIN departamentos dd ON dd.id_departamento = e.id_departamento WHERE e.id_equipo = ?", 'i', array($id));
			$e = $r ? mysqli_fetch_assoc($r) : null;
			if (!$e) responder(array('ok' => false, 'mensaje' => 'Uno de los equipos ya no existe. Recarga la página.'));
			$h = (int)$e['id_hotel'];
			if ($idHotel && $h !== $idHotel) responder(array('ok' => false, 'mensaje' => $e['codigo_equipo'] . ' pertenece a otro hotel.'));
			if ($hotelSol === null) $hotelSol = $h; elseif ($h !== $hotelSol) responder(array('ok' => false, 'mensaje' => 'Todos los equipos deben ser del mismo hotel (' . $e['codigo_equipo'] . ' es de otro).'));
			$txt = function ($k, $max) use ($f) { $v = mb_substr(trim((string)($f[$k] ?? '')), 0, $max); return $v === '' ? null : $v; };
			$limpias[] = array($id, $txt('caracteristicas', 2000), $txt('color', 100), ($f['centro_costos'] ?? '') === 'GASTO DE OPERACION' ? 'GASTO DE OPERACION' : 'CAPEX',
				(int)($f['id_fase'] ?? 0) ?: null, (int)($f['id_area'] ?? 0) ?: null, $txt('piso', 50), $txt('tipo_habitacion', 100), (int)($f['id_departamento'] ?? 0) ?: null);
		}
		$hotelSol = $hotelSol ?: ($idHotel ?: null);
		mysqli_begin_transaction($cx);
		try {
			$n = count($limpias);
			if (!consulta($cx, "INSERT INTO solicitud_alta_equipos (id_usuario, id_departamento_solicitante, numero_solicitud, fecha_solicitud, cantidad_equipos, clasificacion_1, clasificacion_2, id_hotel) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
				'iississi', array((int)$_SESSION['iduser'], $idDep, $numero, $fecha, $n, $c1, $c2, $hotelSol))) throw new Exception(mysqli_error($cx));
			$idSol = (int)mysqli_insert_id($cx);
			foreach ($limpias as $x) {
				if (!consulta($cx, "INSERT INTO solicitud_alta_equipos_detalle (id_solicitud, id_equipo, caracteristicas, color, centro_costos, id_fase, id_area, piso, tipo_habitacion, id_departamento) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
					'iisssiissi', array($idSol, $x[0], $x[1], $x[2], $x[3], $x[4], $x[5], $x[6], $x[7], $x[8]))) throw new Exception(mysqli_error($cx));
			}
			mysqli_commit($cx);
		} catch (Throwable $e) {
			mysqli_rollback($cx);
			error_log('Solicitud de alta: ' . $e->getMessage());
			responder(array('ok' => false, 'mensaje' => 'No se pudo guardar la solicitud. No se cambió nada.'));
		}
		responder(array('ok' => true, 'id' => $idSol));
	}

	responder(array('ok' => false, 'mensaje' => 'Acción no válida.'), 400);
