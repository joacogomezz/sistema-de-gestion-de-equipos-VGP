<?php
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../config.php";
	require_once "../../clases/Conexion.php";

	header('Content-Type: application/json; charset=utf-8');

	$c = new conectar();
	$cx = $c->conexion();
	$esSuperAdmin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$idHotel = $esSuperAdmin ? 0 : (isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : 0);

	$where = '';
	if (!$esSuperAdmin) {
		$where = " AND (e.id_hotel = $idHotel OR (e.id_hotel IS NULL AND d.id_hotel = $idHotel))";
	}

	$sql = "SELECT e.id_equipo, e.numero_equipo_hotel, e.codigo_equipo, e.descripcion, e.marca, e.modelo, e.numero_serie,
			e.estado, e.ubicacion, e.valor_adquisicion, e.tipo_moneda, e.fecha_ingreso, e.usuario_registro, e.numero_factura,
			e.foto_equipo, e.id_departamento, d.nombre AS departamento, COALESCE(e.id_hotel, d.id_hotel) AS id_hotel, h.nombre AS hotel
		FROM equipos e
		LEFT JOIN departamentos d ON d.id_departamento = e.id_departamento
		LEFT JOIN hoteles h ON h.id_hotel = COALESCE(e.id_hotel, d.id_hotel)
		WHERE 1=1 $where
		ORDER BY e.id_equipo DESC";
	$r = mysqli_query($cx, $sql);
	if (!$r) {
		http_response_code(500);
		echo json_encode(array('ok' => false, 'mensaje' => 'No se pudo consultar el inventario.'));
		exit;
	}

	// Un solo recorrido por carpeta en lugar de comprobar archivo por archivo
	$raiz = realpath(__DIR__ . '/../..');
	$resguardos = array();
	foreach (glob($raiz . '/archivos/resguardos_firmados/equipo_*.pdf') ?: array() as $f) {
		if (preg_match('/equipo_(\d+)\.pdf$/', $f, $m)) $resguardos[(int)$m[1]] = true;
	}
	$facturas = array();
	foreach (glob($raiz . '/archivos/facturas_equipos/equipo_*.*') ?: array() as $f) {
		if (preg_match('/equipo_(\d+)\.(pdf|jpe?g|png|gif|webp)$/i', $f, $m)) $facturas[(int)$m[1]] = true;
	}
	$fotos = array();
	foreach (scandir($raiz . '/archivos/equipos') ?: array() as $f) {
		$fotos[$f] = true;
	}

	// Evita "HP HP ProDesk" cuando el modelo ya incluye la marca
	function marcaModelo($marca, $modelo) {
		$marca = trim((string)$marca);
		$modelo = trim((string)$modelo);
		if ($marca !== '' && stripos($modelo, $marca) === 0) return $modelo;
		return trim($marca . ' ' . $modelo);
	}

	$estados = array('Activo' => 'activo', 'En Mantenimiento' => 'mantenimiento', 'En Reparación' => 'reparacion', 'Dado de Baja' => 'baja');
	$lista = array();
	while ($e = mysqli_fetch_assoc($r)) {
		$id = (int)$e['id_equipo'];
		$foto = trim((string)$e['foto_equipo']);
		$archivoFoto = $foto !== '' ? basename($foto) : '';
		$fecha = trim((string)$e['fecha_ingreso']);
		if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fecha, $m)) $fecha = $m[3] . '-' . $m[2] . '-' . $m[1];
		$lista[] = array(
			'id' => $id,
			'num' => $e['numero_equipo_hotel'] !== null ? (int)$e['numero_equipo_hotel'] : $id,
			'codigo' => (string)$e['codigo_equipo'],
			'desc' => trim((string)$e['descripcion']),
			'marca' => marcaModelo($e['marca'], $e['modelo']),
			'modelo' => trim((string)$e['modelo']),
			'serie' => trim((string)$e['numero_serie']),
			'estado' => isset($estados[$e['estado']]) ? $estados[$e['estado']] : 'activo',
			'ubic' => trim((string)$e['ubicacion']),
			'resp' => trim((string)$e['usuario_registro']),
			'nfact' => trim((string)$e['numero_factura']),
			'valor' => (float)str_replace(',', '', (string)$e['valor_adquisicion']),
			'mon' => strtoupper(trim((string)$e['tipo_moneda'])) ?: 'MXN',
			'fecha' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : '',
			'idDepto' => (int)$e['id_departamento'],
			'depto' => trim((string)$e['departamento']),
			'idHotel' => (int)$e['id_hotel'],
			'hotel' => trim((string)$e['hotel']),
			'foto' => ($archivoFoto !== '' && isset($fotos[$archivoFoto])) ? '../archivos/equipos/' . rawurlencode($archivoFoto) : '',
			'resg' => isset($resguardos[$id]),
			'fact' => isset($facturas[$id]),
		);
	}

	echo json_encode(array('ok' => true, 'superAdmin' => $esSuperAdmin, 'equipos' => $lista), JSON_UNESCAPED_UNICODE);
