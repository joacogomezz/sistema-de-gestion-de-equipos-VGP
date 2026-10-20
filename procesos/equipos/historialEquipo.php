<?php
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../config.php";
	require_once "../../clases/Conexion.php";

	header('Content-Type: application/json; charset=utf-8');

	$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	if ($id <= 0) {
		echo json_encode(array('ok' => false, 'eventos' => array()));
		exit;
	}

	$c = new conectar();
	$cx = $c->conexion();
	$esSuperAdmin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	if (!$esSuperAdmin) {
		$idHotel = isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : 0;
		$chk = mysqli_query($cx, "SELECT 1 FROM equipos e LEFT JOIN departamentos d ON d.id_departamento = e.id_departamento
			WHERE e.id_equipo = $id AND (e.id_hotel = $idHotel OR (e.id_hotel IS NULL AND d.id_hotel = $idHotel)) LIMIT 1");
		if (!$chk || mysqli_num_rows($chk) === 0) {
			echo json_encode(array('ok' => false, 'eventos' => array()));
			exit;
		}
	}

	$eventos = array();
	$nombreUsuario = "TRIM(CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellido,'')))";

	$r = mysqli_query($cx, "SELECT e.fecha_registro, $nombreUsuario FROM equipos e LEFT JOIN usuarios u ON u.id_usuario = e.id_usuario WHERE e.id_equipo = $id");
	if ($r && ($f = mysqli_fetch_row($r)) && $f[0]) {
		$eventos[] = array('t' => 'alta', 'txt' => 'Alta en inventario', 'f' => $f[0], 'u' => $f[1]);
	}
	$r = mysqli_query($cx, "SELECT a.fecha_alta, a.motivo_alta, $nombreUsuario FROM altas_equipos a LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario WHERE a.id_equipo = $id");
	while ($r && ($f = mysqli_fetch_row($r))) {
		$eventos[] = array('t' => 'alta', 'txt' => 'Alta registrada' . ($f[1] ? ' · ' . $f[1] : ''), 'f' => $f[0], 'u' => $f[2]);
	}
	$r = mysqli_query($cx, "SELECT r.fecha_registro, r.id_resguardo, r.usuario, r.area_destino, r.id_grupo_resguardo FROM resguardos r WHERE r.id_equipo = $id");
	while ($r && ($f = mysqli_fetch_row($r))) {
		$folio = 'RSG-' . str_pad((string)($f[4] ? (int)$f[4] : (int)$f[1]), 4, '0', STR_PAD_LEFT);
		$txt = ($f[4] ? 'Resguardo múltiple ' : 'Resguardo ') . $folio . ' para ' . trim((string)$f[2]);
		$eventos[] = array('t' => 'resguardo', 'txt' => $txt, 'f' => $f[0], 'u' => trim((string)$f[3]));
	}
	$r = mysqli_query($cx, "SELECT b.fecha_baja, b.motivo_baja, b.destino_equipo, $nombreUsuario FROM bajas_equipos b LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario WHERE b.id_equipo = $id");
	while ($r && ($f = mysqli_fetch_row($r))) {
		$eventos[] = array('t' => 'baja', 'txt' => 'Dado de baja' . ($f[1] ? ' · ' . $f[1] : '') . ($f[2] ? ' · destino: ' . $f[2] : ''), 'f' => $f[0], 'u' => $f[3]);
	}
	$r = mysqli_query($cx, "SELECT bm.fecha_baja, bm.id_baja_masiva, bm.motivo_baja, $nombreUsuario FROM bajas_masivas_equipos x JOIN bajas_masivas bm ON bm.id_baja_masiva = x.id_baja_masiva LEFT JOIN usuarios u ON u.id_usuario = bm.id_usuario WHERE x.id_equipo = $id");
	while ($r && ($f = mysqli_fetch_row($r))) {
		$eventos[] = array('t' => 'baja', 'txt' => 'Baja masiva #' . (int)$f[1] . ($f[2] ? ' · ' . $f[2] : ''), 'f' => $f[0], 'u' => $f[3]);
	}

	usort($eventos, function ($a, $b) { return strcmp((string)$b['f'], (string)$a['f']); });
	echo json_encode(array('ok' => true, 'eventos' => $eventos), JSON_UNESCAPED_UNICODE);
