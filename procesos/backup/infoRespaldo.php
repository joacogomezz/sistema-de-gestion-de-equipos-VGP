<?php
	require_once __DIR__ . '/../../seguridad.php';
	requerirAutenticacion();
	header('Content-Type: application/json; charset=utf-8');
	if (!isset($_SESSION['iduser']) || (int)$_SESSION['iduser'] !== 1) {
		http_response_code(403);
		echo json_encode(array('ok' => false, 'mensaje' => 'Solo el Super Administrador puede descargar respaldos.'), JSON_UNESCAPED_UNICODE);
		exit;
	}
	require_once __DIR__ . '/../../clases/Conexion.php';
	require_once __DIR__ . '/bitacora.php';
	$cx = (new conectar())->conexion();

	$uno = function ($sql) use ($cx) { $r = mysqli_query($cx, $sql); $f = $r ? mysqli_fetch_row($r) : null; return $f ? $f[0] : 0; };
	$historial = leerRespaldos(10);
	echo json_encode(array(
		'ok' => true,
		'bd' => DB_NAME,
		'bytes' => (int)$uno("SELECT COALESCE(SUM(data_length), 0) FROM information_schema.tables WHERE table_schema = DATABASE()"),
		'tablas' => (int)$uno("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()"),
		'equipos' => (int)$uno("SELECT COUNT(*) FROM equipos"),
		'hoteles' => (int)$uno("SELECT COUNT(*) FROM hoteles"),
		'resguardos' => (int)$uno("SELECT COUNT(*) FROM resguardos"),
		'historial' => $historial,
		'archivo' => DB_NAME . '_respaldo_' . date('Y-m-d_H-i') . '.sql'
	), JSON_UNESCAPED_UNICODE);
