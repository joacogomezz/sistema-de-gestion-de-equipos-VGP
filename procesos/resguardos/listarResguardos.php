<?php
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../config.php";
	require_once "../../clases/Conexion.php";

	header('Content-Type: application/json; charset=utf-8');

	$cx = (new conectar())->conexion();
	$esSuperAdmin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$idHotel = $esSuperAdmin ? 0 : (isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : 0);
	$where = $esSuperAdmin ? '' : " AND (e.id_hotel = $idHotel OR (e.id_hotel IS NULL AND d.id_hotel = $idHotel))";

	$sql = "SELECT r.id_resguardo, r.id_grupo_resguardo, r.id_equipo, r.area_destino, r.usuario, r.puesto, r.fecha_resguardo,
			r.numero_factura, r.modelo, r.serie, r.tipo_equipo, r.fecha_registro,
			e.codigo_equipo, e.descripcion, COALESCE(e.id_hotel, d.id_hotel) AS id_hotel, h.nombre AS hotel
		FROM resguardos r
		INNER JOIN equipos e ON e.id_equipo = r.id_equipo
		LEFT JOIN departamentos d ON d.id_departamento = e.id_departamento
		LEFT JOIN hoteles h ON h.id_hotel = COALESCE(e.id_hotel, d.id_hotel)
		WHERE 1=1 $where
		ORDER BY r.id_resguardo DESC";
	$r = mysqli_query($cx, $sql);
	if (!$r) {
		http_response_code(500);
		echo json_encode(array('ok' => false, 'mensaje' => 'No se pudieron consultar los resguardos.'));
		exit;
	}

	// El PDF firmado se guarda por equipo; cuenta como firma de este resguardo solo si se subió después de generarlo.
	$raiz = realpath(__DIR__ . '/../..');
	$firmados = array();
	foreach (glob($raiz . '/archivos/resguardos_firmados/equipo_*.pdf') ?: array() as $f) {
		if (preg_match('/equipo_(\d+)\.pdf$/', $f, $m)) $firmados[(int)$m[1]] = filemtime($f);
	}

	$grupos = array();
	$orden = array();
	while ($f = mysqli_fetch_assoc($r)) {
		$clave = $f['id_grupo_resguardo'] ? 'g' . (int)$f['id_grupo_resguardo'] : 'r' . (int)$f['id_resguardo'];
		$idEq = (int)$f['id_equipo'];
		$registro = strtotime((string)$f['fecha_registro']) ?: 0;
		$firma = isset($firmados[$idEq]) && $firmados[$idEq] >= $registro - 60 ? $firmados[$idEq] : 0;
		$equipo = array(
			'idEquipo' => $idEq,
			'codigo' => (string)$f['codigo_equipo'],
			'tipo' => trim((string)($f['tipo_equipo'] ?: $f['descripcion'])),
			'modelo' => trim((string)$f['modelo']),
			'serie' => trim((string)$f['serie']),
			'firma' => $firma,
		);
		if (!isset($grupos[$clave])) {
			$orden[] = $clave;
			$grupos[$clave] = array(
				'id' => (int)$f['id_resguardo'],
				'hotel' => (string)$f['hotel'],
				'idHotel' => (int)$f['id_hotel'],
				'area' => trim((string)$f['area_destino']),
				'resp' => trim((string)$f['usuario']),
				'puesto' => trim((string)$f['puesto']),
				'fecha' => (string)$f['fecha_resguardo'],
				'registro' => (string)$f['fecha_registro'],
				'factura' => trim((string)$f['numero_factura']),
				'eqs' => array(),
			);
		}
		// El folio del grupo es el id más bajo (el del primer equipo del documento)
		$grupos[$clave]['id'] = min($grupos[$clave]['id'], (int)$f['id_resguardo']);
		array_unshift($grupos[$clave]['eqs'], $equipo);
	}

	$lista = array();
	foreach ($orden as $clave) {
		$g = $grupos[$clave];
		$firmas = array_map(function ($e) { return $e['firma']; }, $g['eqs']);
		$g['firmado'] = min($firmas) > 0;
		$g['ffirma'] = $g['firmado'] ? date('Y-m-d', max($firmas)) : '';
		foreach ($g['eqs'] as &$e) { $e['firmado'] = $e['firma'] > 0; unset($e['firma']); }
		unset($e);
		$lista[] = $g;
	}

	echo json_encode(array('ok' => true, 'superAdmin' => $esSuperAdmin, 'hoy' => date('Y-m-d'), 'resguardos' => $lista), JSON_UNESCAPED_UNICODE);
