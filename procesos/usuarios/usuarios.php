<?php
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../config.php";
	require_once "../../clases/Conexion.php";

	header('Content-Type: application/json; charset=utf-8');
	function responder($d, $c = 200) { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

	// Solo el Super Administrador (id 1) administra usuarios
	if (!isset($_SESSION['iduser']) || (int)$_SESSION['iduser'] !== 1) responder(array('ok' => false, 'mensaje' => 'Solo el Super Administrador puede administrar usuarios.'), 403);

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
	// Regla de negocio existente: los toalleros solo existen en Villa del Arco
	function hotelToalleros($cx) {
		$r = mysqli_query($cx, "SELECT id_hotel FROM hoteles WHERE LOWER(nombre) LIKE '%arco%' ORDER BY id_hotel LIMIT 1");
		$f = $r ? mysqli_fetch_row($r) : null;
		return $f ? (int)$f[0] : 1;
	}

	$accion = $_REQUEST['accion'] ?? 'listar';

	if ($accion === 'listar') {
		$hoteles = array();
		$r = mysqli_query($cx, "SELECT id_hotel, nombre FROM hoteles ORDER BY nombre");
		while ($r && ($f = mysqli_fetch_assoc($r))) $hoteles[] = array('id' => (int)$f['id_hotel'], 'nombre' => $f['nombre']);
		$r = mysqli_query($cx, "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.rol, u.id_hotel, u.fechaCaptura, h.nombre AS hotel
			FROM usuarios u LEFT JOIN hoteles h ON h.id_hotel = u.id_hotel ORDER BY u.id_usuario");
		$lista = array();
		while ($r && ($f = mysqli_fetch_assoc($r))) {
			$id = (int)$f['id_usuario'];
			$lista[] = array('id' => $id, 'nombre' => trim((string)$f['nombre']), 'apellido' => trim((string)$f['apellido']), 'usuario' => (string)$f['email'],
				'rol' => $id === 1 ? 'super' : (strtoupper((string)$f['rol']) === 'TOALLEROS' ? 'toallero' : 'admin'),
				'idHotel' => (int)$f['id_hotel'], 'hotel' => $id === 1 ? 'Todos los hoteles' : (string)$f['hotel'], 'alta' => (string)$f['fechaCaptura']);
		}
		responder(array('ok' => true, 'yo' => (int)$_SESSION['iduser'], 'hotelToalleros' => hotelToalleros($cx), 'hoteles' => $hoteles, 'lista' => $lista));
	}

	if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(array('ok' => false, 'mensaje' => 'Método no permitido.'), 405);

	if ($accion === 'guardar') {
		$id = (int)($_POST['id'] ?? 0);
		$nombre = trim((string)($_POST['nombre'] ?? ''));
		$apellido = trim((string)($_POST['apellido'] ?? ''));
		$usuario = trim((string)($_POST['usuario'] ?? ''));
		$pass = (string)($_POST['password'] ?? '');
		$rol = ($_POST['rol'] ?? 'admin') === 'toallero' ? 'TOALLEROS' : 'ADMIN';
		$idHotel = (int)($_POST['id_hotel'] ?? 0);
		$err = array();
		if ($nombre === '') $err['nombre'] = 'Escribe el nombre.';
		if ($apellido === '') $err['apellido'] = 'Escribe el apellido.';
		if (mb_strlen($nombre) > 50) $err['nombre'] = 'Máximo 50 caracteres.';
		if (mb_strlen($apellido) > 50) $err['apellido'] = 'Máximo 50 caracteres.';
		if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $usuario)) $err['usuario'] = 'Usa de 3 a 30 letras, números, punto, guion o guion bajo, sin espacios.';
		if ($id === 0 || $pass !== '') {
			if (strlen($pass) < 8 || !preg_match('/[a-z]/', $pass) || !preg_match('/[A-Z]/', $pass) || !preg_match('/\d/', $pass)) $err['password'] = 'Usa 8 o más caracteres con mayúsculas, minúsculas y números.';
		}
		if ($id === 1) { $rol = 'ADMIN'; }
		elseif ($rol === 'TOALLEROS') { $idHotel = hotelToalleros($cx); }
		elseif ($idHotel <= 0) { $err['hotel'] = 'Elige el hotel.'; }
		if (!$err) {
			$r = consulta($cx, "SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario <> ? LIMIT 1", 'si', array($usuario, $id));
			if ($r && mysqli_num_rows($r) > 0) $err['usuario'] = '“' . $usuario . '” ya está en uso.';
		}
		if ($err) responder(array('ok' => false, 'errores' => $err));

		if ($id > 0) {
			$ok = $pass !== ''
				? consulta($cx, "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol = ?, id_hotel = IF(? = 1, id_hotel, ?), password = ? WHERE id_usuario = ?", 'ssssiisi', array($nombre, $apellido, $usuario, $rol, $id, $idHotel, sha1($pass), $id))
				: consulta($cx, "UPDATE usuarios SET nombre = ?, apellido = ?, email = ?, rol = ?, id_hotel = IF(? = 1, id_hotel, ?) WHERE id_usuario = ?", 'ssssiii', array($nombre, $apellido, $usuario, $rol, $id, $idHotel, $id));
			if ($ok && $id === (int)$_SESSION['iduser']) { $_SESSION['usuario'] = $usuario; $_SESSION['nombre'] = $nombre; $_SESSION['apellido'] = $apellido; }
		} else {
			// Mismo formato de contraseña que usa el inicio de sesión actual
			$ok = consulta($cx, "INSERT INTO usuarios (nombre, apellido, email, password, fechaCaptura, id_hotel, rol) VALUES (?, ?, ?, ?, CURDATE(), ?, ?)", 'ssssis', array($nombre, $apellido, $usuario, sha1($pass), $idHotel, $rol));
			$id = (int)mysqli_insert_id($cx);
		}
		responder($ok ? array('ok' => true, 'id' => $id) : array('ok' => false, 'mensaje' => 'No se pudo guardar el usuario.'));
	}

	if ($accion === 'eliminar') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id === 1) responder(array('ok' => false, 'mensaje' => 'El Super Administrador no se puede eliminar.'));
		if ($id === (int)$_SESSION['iduser']) responder(array('ok' => false, 'mensaje' => 'No puedes eliminar tu propia cuenta.'));
		$ok = consulta($cx, "DELETE FROM usuarios WHERE id_usuario = ? AND id_usuario <> 1", 'i', array($id));
		responder($ok ? array('ok' => true) : array('ok' => false, 'mensaje' => 'No se pudo eliminar.'));
	}

	responder(array('ok' => false, 'mensaje' => 'Acción no válida.'), 400);
