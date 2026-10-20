<?php
	// Bajas individuales: historial, registro (con fotos) y eliminación. Reemplaza a registrarBaja.php / eliminarBaja.php en la pantalla nueva.
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
	// Mismos valores que el formulario anterior, para que los reportes y el historial sigan agrupando igual
	$MOTIVOS = array('Equipo Obsoleto', 'Falla Irreparable', 'Reemplazo por Nuevo', 'Venta', 'Donación', 'Robo', 'Otro');
	$DESTINOS = array('Venta', 'Donación', 'Chatarra', 'Almacén', 'Otro');
	$accion = $_REQUEST['accion'] ?? 'listar';

	function fotoPublica($ruta) {
		$ruta = trim((string)$ruta);
		if ($ruta === '' || strpos($ruta, '..') !== false) return '';
		return is_file(dirname(__DIR__, 2) . '/' . $ruta) ? '../' . implode('/', array_map('rawurlencode', explode('/', $ruta))) : '';
	}

	if ($accion === 'listar') {
		$filtro = $idHotel ? " AND COALESCE(b.id_hotel, d.id_hotel, e.id_hotel) = $idHotel" : '';
		$r = mysqli_query($cx, "SELECT b.id_baja, b.numero_baja_hotel, b.fecha_baja, b.motivo_baja, b.valor_residual, b.destino_equipo, b.observaciones,
				b.foto_equipo, b.foto_etiqueta, b.tecnico_reviso, b.gerente_area, b.fecha_adquisicion,
				e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo, e.numero_serie, e.tipo_moneda, e.valor_adquisicion,
				d.nombre AS depto, h.nombre AS hotel, COALESCE(b.id_hotel, d.id_hotel, e.id_hotel) AS id_hotel
			FROM bajas_equipos b
			INNER JOIN equipos e ON e.id_equipo = b.id_equipo
			LEFT JOIN departamentos d ON d.id_departamento = b.id_departamento
			LEFT JOIN hoteles h ON h.id_hotel = COALESCE(b.id_hotel, d.id_hotel, e.id_hotel)
			WHERE b.tipo_baja = 'Individual' $filtro
			ORDER BY b.id_baja DESC");
		$lista = array(); $nombres = array();
		while ($r && ($f = mysqli_fetch_assoc($r))) {
			$marca = trim((string)$f['marca']); $modelo = trim((string)$f['modelo']);
			$lista[] = array(
				'id' => (int)$f['id_baja'], 'n' => $f['numero_baja_hotel'] !== null ? (int)$f['numero_baja_hotel'] : (int)$f['id_baja'],
				'fecha' => (string)$f['fecha_baja'], 'idEquipo' => (int)$f['id_equipo'], 'codigo' => (string)$f['codigo_equipo'],
				'desc' => trim((string)$f['descripcion']), 'marca' => ($marca !== '' && stripos($modelo, $marca) === 0) ? $modelo : trim($marca . ' ' . $modelo),
				'serie' => trim((string)$f['numero_serie']), 'depto' => (string)$f['depto'], 'hotel' => (string)$f['hotel'], 'idHotel' => (int)$f['id_hotel'],
				'motivo' => (string)$f['motivo_baja'], 'valor' => (float)$f['valor_residual'], 'mon' => strtoupper(trim((string)$f['tipo_moneda'])) ?: 'MXN',
				'destino' => (string)$f['destino_equipo'], 'obs' => (string)$f['observaciones'], 'tec' => (string)$f['tecnico_reviso'], 'vobo' => (string)$f['gerente_area'],
				'fotoEq' => fotoPublica($f['foto_equipo']), 'fotoEt' => fotoPublica($f['foto_etiqueta'])
			);
			foreach (array('tecnico_reviso', 'gerente_area') as $k) { $n = trim((string)$f[$k]); if ($n !== '') $nombres[$k][$n] = 1; }
		}
		responder(array('ok' => true, 'superAdmin' => $esSuper, 'motivos' => $MOTIVOS, 'destinos' => $DESTINOS, 'lista' => $lista,
			'tecnicos' => array_keys($nombres['tecnico_reviso'] ?? array()), 'gerentes' => array_keys($nombres['gerente_area'] ?? array())));
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(array('ok' => false, 'mensaje' => 'Método no permitido.'), 405);

	if ($accion === 'registrar') {
		$idEquipo = (int)($_POST['id_equipo'] ?? 0);
		$motivo = trim((string)($_POST['motivo'] ?? ''));
		$destino = trim((string)($_POST['destino'] ?? ''));
		$valorTxt = str_replace(array(',', '$', ' '), '', (string)($_POST['valor'] ?? ''));
		$obs = trim((string)($_POST['observaciones'] ?? ''));
		$tec = trim((string)($_POST['tecnico'] ?? ''));
		$vobo = trim((string)($_POST['gerente'] ?? ''));
		$fAdq = trim((string)($_POST['fecha_adquisicion'] ?? ''));
		$err = array();

		$r = consulta($cx, "SELECT e.id_equipo, e.estado, e.valor_adquisicion, e.id_departamento, COALESCE(e.id_hotel, d.id_hotel) AS id_hotel, e.codigo_equipo
			FROM equipos e LEFT JOIN departamentos d ON d.id_departamento = e.id_departamento WHERE e.id_equipo = ?", 'i', array($idEquipo));
		$eq = $r ? mysqli_fetch_assoc($r) : null;
		if (!$eq) $err['equipo'] = 'Busca el equipo que se dará de baja.';
		elseif ($eq['estado'] === 'Dado de Baja') $err['equipo'] = 'Este equipo ya está dado de baja.';
		elseif ($idHotel && (int)$eq['id_hotel'] !== $idHotel) $err['equipo'] = 'El equipo pertenece a otro hotel.';
		if (!in_array($motivo, $MOTIVOS, true)) $err['motivo'] = 'Elige el motivo de la baja.';
		if (!in_array($destino, $DESTINOS, true)) $err['destino'] = 'Elige qué pasará con el equipo.';
		if ($valorTxt === '' || !is_numeric($valorTxt) || (float)$valorTxt < 0) $err['valor'] = 'Escribe el valor residual; 0 si no tiene.';
		elseif ($eq && (float)$eq['valor_adquisicion'] > 0 && (float)$valorTxt > (float)$eq['valor_adquisicion']) $err['valor'] = 'No puede ser mayor al valor de compra.';
		if ($fAdq !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fAdq)) $err['fecha'] = 'Fecha no válida.';
		if (mb_strlen($obs) > 500) $err['observaciones'] = 'Máximo 500 caracteres.';
		if ($tec === '' || mb_strlen($tec) > 100) $err['tecnico'] = 'Escribe quién revisó el equipo.';
		if ($vobo === '' || mb_strlen($vobo) > 100) $err['gerente'] = 'Escribe quién autoriza.';

		// Fotos: obligatorias, imagen real, hasta 10 MB
		$fotos = array();
		foreach (array('foto_equipo' => 'equipo', 'foto_etiqueta' => 'etiqueta') as $campo => $pref) {
			$f = $_FILES[$campo] ?? null;
			if (!$f || $f['error'] !== UPLOAD_ERR_OK) { $err[$campo] = $pref === 'equipo' ? 'Agrega la foto del equipo.' : 'Agrega la foto de la etiqueta.'; continue; }
			$info = @getimagesize($f['tmp_name']);
			$ext = $info ? array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif')[$info[2]] ?? null : null;
			if (!$ext) { $err[$campo] = 'La foto debe ser JPG, PNG o WebP.'; continue; }
			if ($f['size'] > 10 * 1048576) { $err[$campo] = 'La foto pesa más de 10 MB.'; continue; }
			$fotos[$campo] = array($f['tmp_name'], $pref . '_' . $idEquipo . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext);
		}
		if ($err) responder(array('ok' => false, 'errores' => $err));

		$dir = dirname(__DIR__, 2) . '/archivos/bajas/';
		if (!is_dir($dir)) @mkdir($dir, 0775, true);
		$rutas = array();
		foreach ($fotos as $campo => $x) {
			if (!move_uploaded_file($x[0], $dir . $x[1])) { foreach ($rutas as $p) @unlink(dirname(__DIR__, 2) . '/' . $p); responder(array('ok' => false, 'mensaje' => 'No se pudieron guardar las fotos.')); }
			$rutas[$campo] = 'archivos/bajas/' . $x[1];
		}

		$hotelBaja = (int)$eq['id_hotel'] ?: null;
		$idDepto = (int)$eq['id_departamento'] ?: null;
		mysqli_begin_transaction($cx);
		try {
			$numero = null;
			if ($hotelBaja) {
				$rn = consulta($cx, "SELECT COALESCE(MAX(numero_baja_hotel), 0) + 1 FROM bajas_equipos WHERE id_hotel = ? FOR UPDATE", 'i', array($hotelBaja));
				$numero = $rn ? (int)mysqli_fetch_row($rn)[0] : 1;
			}
			$valor = round((float)$valorTxt, 2);
			$fAdqVal = $fAdq !== '' ? $fAdq : null;
			$obsVal = $obs !== '' ? $obs : null;
			$ok = consulta($cx, "INSERT INTO bajas_equipos (id_hotel, numero_baja_hotel, id_equipo, id_usuario, id_departamento, tipo_baja, motivo_baja, fecha_baja, valor_residual, destino_equipo, observaciones, foto_equipo, foto_etiqueta, tecnico_reviso, gerente_area, fecha_adquisicion)
				VALUES (?, ?, ?, ?, ?, 'Individual', ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?)", 'iiiiisdsssssss',
				array($hotelBaja, $numero, $idEquipo, (int)$_SESSION['iduser'], $idDepto, $motivo, $valor, $destino, $obsVal, $rutas['foto_equipo'], $rutas['foto_etiqueta'], $tec, $vobo, $fAdqVal));
			if (!$ok) throw new Exception(mysqli_error($cx));
			$idBaja = (int)mysqli_insert_id($cx);
			if (!consulta($cx, "UPDATE equipos SET estado = 'Dado de Baja' WHERE id_equipo = ?", 'i', array($idEquipo))) throw new Exception(mysqli_error($cx));
			mysqli_commit($cx);
		} catch (Throwable $e) {
			mysqli_rollback($cx);
			foreach ($rutas as $p) @unlink(dirname(__DIR__, 2) . '/' . $p);
			error_log('Baja individual: ' . $e->getMessage());
			responder(array('ok' => false, 'mensaje' => 'No se pudo registrar la baja. No se cambió nada.'));
		}
		responder(array('ok' => true, 'id' => $idBaja, 'n' => $numero ?: $idBaja, 'codigo' => $eq['codigo_equipo']));
	}

	if ($accion === 'eliminar') {
		if (!$esSuper) responder(array('ok' => false, 'mensaje' => 'Solo el Super Administrador puede eliminar bajas.'), 403);
		$id = (int)($_POST['id'] ?? 0);
		$r = consulta($cx, "SELECT id_equipo FROM bajas_equipos WHERE id_baja = ? AND tipo_baja = 'Individual'", 'i', array($id));
		$b = $r ? mysqli_fetch_assoc($r) : null;
		if (!$b) responder(array('ok' => false, 'mensaje' => 'La baja ya no existe.'));
		mysqli_begin_transaction($cx);
		// Las fotos se conservan en archivos/bajas como evidencia; solo se quita el registro y el equipo vuelve a Activo
		$ok = consulta($cx, "DELETE FROM bajas_equipos WHERE id_baja = ?", 'i', array($id))
			&& consulta($cx, "UPDATE equipos SET estado = 'Activo' WHERE id_equipo = ? AND NOT EXISTS (SELECT 1 FROM (SELECT id_equipo FROM bajas_equipos WHERE id_equipo = ?) x)
				AND NOT EXISTS (SELECT 1 FROM bajas_masivas_equipos WHERE id_equipo = ?)", 'iii', array((int)$b['id_equipo'], (int)$b['id_equipo'], (int)$b['id_equipo']));
		if ($ok) { mysqli_commit($cx); responder(array('ok' => true)); }
		mysqli_rollback($cx);
		responder(array('ok' => false, 'mensaje' => 'No se pudo eliminar.'));
	}

	responder(array('ok' => false, 'mensaje' => 'Acción no válida.'), 400);
