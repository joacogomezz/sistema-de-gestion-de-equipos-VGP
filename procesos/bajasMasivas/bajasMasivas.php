<?php
	// Bajas masivas: historial, registro con valor residual y observación por equipo, y eliminación (solo Super Administrador).
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

	$esSuper = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$idHotel = $esSuper ? 0 : (int)($_SESSION['id_hotel'] ?? 0);
	// Mismos valores que el formulario anterior
	$MOTIVOS = array('Renovación de Equipos', 'Cierre de Departamento', 'Actualización Tecnológica', 'Venta Masiva', 'Donación Masiva', 'Otro');
	$DESTINOS = array('Venta Masiva', 'Donación Masiva', 'Chatarra', 'Almacén', 'Otro');
	$accion = $_REQUEST['accion'] ?? 'listar';

	if ($accion === 'listar') {
		$filtro = '';
		if ($idHotel) {
			$nh = consulta($cx, "SELECT nombre FROM hoteles WHERE id_hotel = ?", 'i', array($idHotel));
			$nombreHotel = $nh && ($f = mysqli_fetch_row($nh)) ? $f[0] : '';
			$filtro = " AND (bm.id_hotel = $idHotel OR d.id_hotel = $idHotel OR (bm.id_hotel IS NULL AND bm.id_departamento IS NULL AND bm.hotel_area = '" . mysqli_real_escape_string($cx, $nombreHotel) . "'))";
		}
		$r = mysqli_query($cx, "SELECT bm.id_baja_masiva, bm.numero_baja_masiva_hotel, bm.fecha_baja, bm.motivo_baja, bm.valor_residual_total, bm.destino_equipo, bm.observaciones,
				bm.total_equipos, bm.hotel_area, COALESCE(bm.id_hotel, d.id_hotel, h2.id_hotel) AS id_hotel, d.nombre AS depto, u.nombre AS un, u.apellido AS ua,
				(SELECT GROUP_CONCAT(DISTINCT dd.nombre ORDER BY dd.nombre SEPARATOR ', ') FROM bajas_masivas_equipos x JOIN equipos e ON e.id_equipo = x.id_equipo LEFT JOIN departamentos dd ON dd.id_departamento = e.id_departamento WHERE x.id_baja_masiva = bm.id_baja_masiva) AS deptos
			FROM bajas_masivas bm
			LEFT JOIN departamentos d ON d.id_departamento = bm.id_departamento
			LEFT JOIN hoteles h2 ON h2.nombre = bm.hotel_area
			LEFT JOIN usuarios u ON u.id_usuario = bm.id_usuario
			WHERE 1=1 $filtro ORDER BY bm.id_baja_masiva DESC");
		$lista = array();
		while ($r && ($f = mysqli_fetch_assoc($r))) {
			$lista[] = array('id' => (int)$f['id_baja_masiva'], 'n' => $f['numero_baja_masiva_hotel'] !== null ? (int)$f['numero_baja_masiva_hotel'] : (int)$f['id_baja_masiva'],
				'fecha' => (string)$f['fecha_baja'], 'motivo' => (string)$f['motivo_baja'], 'valor' => (float)$f['valor_residual_total'], 'destino' => (string)$f['destino_equipo'],
				'obs' => (string)$f['observaciones'], 'total' => (int)$f['total_equipos'], 'hotel' => (string)$f['hotel_area'], 'idHotel' => (int)$f['id_hotel'],
				'depto' => (string)($f['depto'] ?: $f['deptos']), 'usuario' => trim($f['un'] . ' ' . $f['ua']));
		}
		$deptos = array();
		$r = mysqli_query($cx, "SELECT id_departamento, nombre, id_hotel FROM departamentos" . ($idHotel ? " WHERE id_hotel = $idHotel" : '') . " ORDER BY nombre");
		while ($r && ($f = mysqli_fetch_assoc($r))) $deptos[] = array('id' => (int)$f['id_departamento'], 'nombre' => $f['nombre'], 'idHotel' => (int)$f['id_hotel']);
		responder(array('ok' => true, 'superAdmin' => $esSuper, 'motivos' => $MOTIVOS, 'destinos' => $DESTINOS, 'deptos' => $deptos, 'lista' => $lista));
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(array('ok' => false, 'mensaje' => 'Método no permitido.'), 405);

	if ($accion === 'registrar') {
		$items = json_decode((string)($_POST['items'] ?? '[]'), true);
		$motivo = trim((string)($_POST['motivo'] ?? ''));
		$destino = trim((string)($_POST['destino'] ?? ''));
		$obs = trim((string)($_POST['observaciones'] ?? ''));
		$idDepto = (int)($_POST['id_departamento'] ?? 0) ?: null;
		$err = array();
		if (!is_array($items) || count($items) < 2) $err['items'] = 'Agrega al menos 2 equipos. Para uno solo usa Baja individual.';
		elseif (count($items) > 500) $err['items'] = 'Máximo 500 equipos por baja masiva.';
		if (!in_array($motivo, $MOTIVOS, true)) $err['motivo'] = 'Elige el motivo.';
		if (!in_array($destino, $DESTINOS, true)) $err['destino'] = 'Elige el destino.';
		if (mb_strlen($obs) > 1000) $err['observaciones'] = 'Máximo 1000 caracteres.';
		if ($err) responder(array('ok' => false, 'errores' => $err));

		// Revisa cada equipo: existe, no está dado de baja, mismo hotel y del hotel del usuario
		$limpios = array(); $hotelBaja = null; $total = 0.0;
		foreach ($items as $it) {
			$id = (int)($it['id'] ?? 0);
			if (isset($limpios[$id])) continue;
			$r = consulta($cx, "SELECT e.codigo_equipo, e.estado, e.valor_adquisicion, COALESCE(e.id_hotel, d.id_hotel) AS id_hotel FROM equipos e LEFT JOIN departamentos d ON d.id_departamento = e.id_departamento WHERE e.id_equipo = ?", 'i', array($id));
			$e = $r ? mysqli_fetch_assoc($r) : null;
			if (!$e) responder(array('ok' => false, 'mensaje' => 'Uno de los equipos ya no existe. Recarga la página.'));
			if ($e['estado'] === 'Dado de Baja') responder(array('ok' => false, 'mensaje' => $e['codigo_equipo'] . ' ya está dado de baja. Quítalo de la lista.'));
			$h = (int)$e['id_hotel'];
			if ($idHotel && $h !== $idHotel) responder(array('ok' => false, 'mensaje' => $e['codigo_equipo'] . ' pertenece a otro hotel.'));
			if ($hotelBaja === null) $hotelBaja = $h; elseif ($h !== $hotelBaja) responder(array('ok' => false, 'mensaje' => 'Todos los equipos deben ser del mismo hotel (' . $e['codigo_equipo'] . ' es de otro).'));
			$v = str_replace(array(',', '$', ' '), '', (string)($it['valor'] ?? '0'));
			if ($v === '') $v = '0';
			if (!is_numeric($v) || (float)$v < 0) responder(array('ok' => false, 'mensaje' => 'El valor residual de ' . $e['codigo_equipo'] . ' no es válido.'));
			$o = trim((string)($it['obs'] ?? ''));
			if (mb_strlen($o) > 500) $o = mb_substr($o, 0, 500);
			$limpios[$id] = array(round((float)$v, 2), $o);
			$total += round((float)$v, 2);
		}
		if ($idDepto) {
			$r = consulta($cx, "SELECT id_hotel FROM departamentos WHERE id_departamento = ?", 'i', array($idDepto));
			$f = $r ? mysqli_fetch_row($r) : null;
			if (!$f || ($hotelBaja && (int)$f[0] !== $hotelBaja)) $idDepto = null;
		}
		$nombreHotel = '';
		if ($hotelBaja) { $r = consulta($cx, "SELECT nombre FROM hoteles WHERE id_hotel = ?", 'i', array($hotelBaja)); $f = $r ? mysqli_fetch_row($r) : null; $nombreHotel = $f ? $f[0] : ''; }

		mysqli_begin_transaction($cx);
		try {
			$numero = null;
			if ($hotelBaja) {
				$rn = consulta($cx, "SELECT COALESCE(MAX(numero_baja_masiva_hotel), 0) + 1 FROM bajas_masivas WHERE id_hotel = ? FOR UPDATE", 'i', array($hotelBaja));
				$numero = $rn ? (int)mysqli_fetch_row($rn)[0] : 1;
			}
			$n = count($limpios); $obsVal = $obs !== '' ? $obs : null; $hotelVal = $hotelBaja ?: null;
			if (!consulta($cx, "INSERT INTO bajas_masivas (id_hotel, numero_baja_masiva_hotel, id_usuario, id_departamento, hotel_area, fecha_baja, motivo_baja, valor_residual_total, destino_equipo, observaciones, total_equipos)
				VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?)", 'iiiissdssi', array($hotelVal, $numero, (int)$_SESSION['iduser'], $idDepto, $nombreHotel, $motivo, $total, $destino, $obsVal, $n))) throw new Exception(mysqli_error($cx));
			$idBm = (int)mysqli_insert_id($cx);
			foreach ($limpios as $id => $x) {
				$o = $x[1] !== '' ? $x[1] : null;
				if (!consulta($cx, "INSERT INTO bajas_masivas_equipos (id_baja_masiva, id_equipo, valor_residual_individual, observaciones) VALUES (?, ?, ?, ?)", 'iids', array($idBm, $id, $x[0], $o))) throw new Exception(mysqli_error($cx));
				if (!consulta($cx, "UPDATE equipos SET estado = 'Dado de Baja' WHERE id_equipo = ?", 'i', array($id))) throw new Exception(mysqli_error($cx));
			}
			mysqli_commit($cx);
		} catch (Throwable $e) {
			mysqli_rollback($cx);
			error_log('Baja masiva: ' . $e->getMessage());
			responder(array('ok' => false, 'mensaje' => 'No se pudo registrar la baja masiva. No se cambió nada.'));
		}
		responder(array('ok' => true, 'id' => $idBm, 'n' => $numero ?: $idBm, 'total' => $n, 'valor' => $total, 'idHotel' => $hotelBaja));
	}

	if ($accion === 'eliminar') {
		if (!$esSuper) responder(array('ok' => false, 'mensaje' => 'Solo el Super Administrador puede eliminar bajas masivas.'), 403);
		$id = (int)($_POST['id'] ?? 0);
		$r = consulta($cx, "SELECT id_equipo FROM bajas_masivas_equipos WHERE id_baja_masiva = ?", 'i', array($id));
		$eqs = array(); while ($r && ($f = mysqli_fetch_row($r))) $eqs[] = (int)$f[0];
		mysqli_begin_transaction($cx);
		$ok = consulta($cx, "DELETE FROM bajas_masivas_equipos WHERE id_baja_masiva = ?", 'i', array($id)) && consulta($cx, "DELETE FROM bajas_masivas WHERE id_baja_masiva = ?", 'i', array($id));
		// Los equipos vuelven a Activo salvo que tengan otra baja registrada
		foreach ($eqs as $e) {
			if (!$ok) break;
			$ok = consulta($cx, "UPDATE equipos SET estado = 'Activo' WHERE id_equipo = ? AND estado = 'Dado de Baja'
				AND NOT EXISTS (SELECT 1 FROM bajas_equipos WHERE id_equipo = ?) AND NOT EXISTS (SELECT 1 FROM bajas_masivas_equipos WHERE id_equipo = ?)", 'iii', array($e, $e, $e));
		}
		if ($ok) { mysqli_commit($cx); responder(array('ok' => true, 'restaurados' => count($eqs))); }
		mysqli_rollback($cx);
		responder(array('ok' => false, 'mensaje' => 'No se pudo eliminar.'));
	}

	responder(array('ok' => false, 'mensaje' => 'Acción no válida.'), 400);
