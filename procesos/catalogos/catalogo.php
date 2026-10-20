<?php
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../config.php";
	require_once "../../clases/Conexion.php";

	header('Content-Type: application/json; charset=utf-8');

	function responder($datos, $codigo = 200) {
		http_response_code($codigo);
		echo json_encode($datos, JSON_UNESCAPED_UNICODE);
		exit;
	}

	// tabla, llave, tabla que referencia, columna que referencia
	$TIPOS = array(
		'departamentos' => array('departamentos', 'id_departamento', 'equipos', 'id_departamento'),
		'areas' => array('areas', 'id_area', 'solicitud_alta_equipos_detalle', 'id_area'),
		'fases' => array('fases', 'id_fase', 'solicitud_alta_equipos_detalle', 'id_fase'),
	);
	$tipo = isset($_REQUEST['tipo']) ? (string)$_REQUEST['tipo'] : '';
	if (!isset($TIPOS[$tipo])) responder(array('ok' => false, 'mensaje' => 'Catálogo no válido.'), 400);
	list($tabla, $llave, $refTabla, $refCol) = $TIPOS[$tipo];

	$cx = (new conectar())->conexion();
	mysqli_set_charset($cx, 'utf8mb4');
	$esSuperAdmin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$idHotel = $esSuperAdmin
		? (isset($_REQUEST['id_hotel']) ? (int)$_REQUEST['id_hotel'] : 0)
		: (isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : 0);

	function consulta($cx, $sql, $tipos = '', $params = array()) {
		$st = mysqli_prepare($cx, $sql);
		if (!$st) return false;
		if ($tipos !== '') mysqli_stmt_bind_param($st, $tipos, ...$params);
		if (!mysqli_stmt_execute($st)) return false;
		$res = mysqli_stmt_get_result($st);
		return $res === false ? $st : $res;
	}
	function elemento($cx, $tabla, $llave, $id, $idHotel) {
		$r = consulta($cx, "SELECT $llave AS id, nombre, id_hotel FROM $tabla WHERE $llave = ? LIMIT 1", 'i', array($id));
		$f = $r ? mysqli_fetch_assoc($r) : null;
		return ($f && (int)$f['id_hotel'] === $idHotel) ? $f : null;
	}

	$accion = isset($_REQUEST['accion']) ? (string)$_REQUEST['accion'] : 'listar';

	if ($accion === 'listar') {
		$hoteles = array();
		if ($esSuperAdmin) {
			$r = mysqli_query($cx, "SELECT id_hotel, nombre FROM hoteles ORDER BY nombre");
			while ($r && ($f = mysqli_fetch_assoc($r))) $hoteles[] = array('id' => (int)$f['id_hotel'], 'nombre' => $f['nombre']);
			if ($idHotel <= 0 && $hoteles) $idHotel = $hoteles[0]['id'];
		}
		$nombreHotel = '';
		$r = consulta($cx, "SELECT nombre FROM hoteles WHERE id_hotel = ?", 'i', array($idHotel));
		if ($r && ($f = mysqli_fetch_assoc($r))) $nombreHotel = $f['nombre'];
		$r = consulta($cx, "SELECT c.$llave AS id, c.nombre, c.descripcion, c.fecha_creacion,
				(SELECT COUNT(*) FROM $refTabla x WHERE x.$refCol = c.$llave) AS n
			FROM $tabla c WHERE c.id_hotel = ? ORDER BY c.nombre", 'i', array($idHotel));
		$lista = array();
		while ($r && ($f = mysqli_fetch_assoc($r))) {
			$lista[] = array('id' => (int)$f['id'], 'nombre' => (string)$f['nombre'], 'desc' => trim((string)$f['descripcion']), 'n' => (int)$f['n'], 'fecha' => (string)$f['fecha_creacion']);
		}
		responder(array('ok' => true, 'superAdmin' => $esSuperAdmin, 'idHotel' => $idHotel, 'hotel' => $nombreHotel, 'hoteles' => $hoteles, 'lista' => $lista));
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(array('ok' => false, 'mensaje' => 'Método no permitido.'), 405);
	if ($idHotel <= 0) responder(array('ok' => false, 'mensaje' => 'Elige un hotel.'), 400);

	if ($accion === 'guardar') {
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		$nombre = trim(preg_replace('/\s+/u', ' ', (string)($_POST['nombre'] ?? '')));
		$desc = trim((string)($_POST['descripcion'] ?? ''));
		if ($nombre === '') responder(array('ok' => false, 'campo' => 'nombre', 'mensaje' => 'Escribe el nombre.'));
		if (mb_strlen($nombre) > 100 || mb_strlen($desc) > 255) responder(array('ok' => false, 'mensaje' => 'El texto es demasiado largo.'));
		$r = consulta($cx, "SELECT $llave FROM $tabla WHERE id_hotel = ? AND LOWER(TRIM(nombre)) = LOWER(?) AND $llave <> ? LIMIT 1", 'isi', array($idHotel, $nombre, $id));
		if ($r && mysqli_num_rows($r) > 0) responder(array('ok' => false, 'campo' => 'nombre', 'mensaje' => 'Ya existe “' . $nombre . '” en este hotel.'));
		if ($id > 0) {
			if (!elemento($cx, $tabla, $llave, $id, $idHotel)) responder(array('ok' => false, 'mensaje' => 'No se encontró el registro en este hotel.'), 404);
			$ok = consulta($cx, "UPDATE $tabla SET nombre = ?, descripcion = ? WHERE $llave = ? AND id_hotel = ?", 'ssii', array($nombre, $desc, $id, $idHotel));
		} elseif ($tipo === 'departamentos') {
			$idUsuario = (int)$_SESSION['iduser'];
			$ok = consulta($cx, "INSERT INTO departamentos (id_hotel, id_usuario, nombre, descripcion, responsable, fecha_creacion) VALUES (?, ?, ?, ?, '', CURDATE())", 'iiss', array($idHotel, $idUsuario, $nombre, $desc));
			$id = (int)mysqli_insert_id($cx);
		} else {
			$ok = consulta($cx, "INSERT INTO $tabla (id_hotel, nombre, descripcion, fecha_creacion) VALUES (?, ?, ?, CURDATE())", 'iss', array($idHotel, $nombre, $desc));
			$id = (int)mysqli_insert_id($cx);
		}
		responder($ok ? array('ok' => true, 'id' => $id) : array('ok' => false, 'mensaje' => 'No se pudo guardar.'));
	}

	if ($accion === 'eliminar') {
		$id = (int)($_POST['id'] ?? 0);
		$destino = (int)($_POST['mover_a'] ?? 0);
		if (!elemento($cx, $tabla, $llave, $id, $idHotel)) responder(array('ok' => false, 'mensaje' => 'No se encontró el registro en este hotel.'), 404);
		$r = consulta($cx, "SELECT COUNT(*) AS n FROM $refTabla WHERE $refCol = ?", 'i', array($id));
		$usos = $r ? (int)mysqli_fetch_assoc($r)['n'] : 0;
		if ($usos > 0 && $destino <= 0) responder(array('ok' => false, 'codigo' => 'TIENE_USOS', 'usos' => $usos, 'mensaje' => 'Primero elige a dónde mover sus equipos.'));
		if ($usos > 0 && ($destino === $id || !elemento($cx, $tabla, $llave, $destino, $idHotel))) responder(array('ok' => false, 'mensaje' => 'El destino no es válido.'));
		mysqli_begin_transaction($cx);
		$okMover = $usos === 0 || consulta($cx, "UPDATE $refTabla SET $refCol = ? WHERE $refCol = ?", 'ii', array($destino, $id));
		$okBorrar = $okMover && consulta($cx, "DELETE FROM $tabla WHERE $llave = ? AND id_hotel = ?", 'ii', array($id, $idHotel));
		if ($okMover && $okBorrar) { mysqli_commit($cx); responder(array('ok' => true, 'movidos' => $usos)); }
		mysqli_rollback($cx);
		responder(array('ok' => false, 'mensaje' => 'No se pudo eliminar.'));
	}

	responder(array('ok' => false, 'mensaje' => 'Acción no válida.'), 400);
