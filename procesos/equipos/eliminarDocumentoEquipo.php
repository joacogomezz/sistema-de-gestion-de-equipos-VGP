<?php
session_start();
require_once "../../seguridad.php";
requerirAutenticacion();
require_once "../../clases/Conexion.php";
require_once "../../clases/Equipos.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['iduser'])) {
	echo json_encode(array('ok' => false, 'mensaje' => 'Sesión no válida.'));
	exit;
}

$idEquipo = isset($_POST['idEquipo']) ? (int)$_POST['idEquipo'] : 0;
$tipo = isset($_POST['tipo']) ? strtolower(trim((string)$_POST['tipo'])) : '';

if ($idEquipo <= 0 || !in_array($tipo, array('resguardo', 'factura'), true)) {
	echo json_encode(array('ok' => false, 'mensaje' => 'Datos incompletos o inválidos.'));
	exit;
}

$obj = new equipos();
$esSuperAdmin = (int)$_SESSION['iduser'] === 1;
if (!$esSuperAdmin) {
	$idHotelSesion = isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : 0;
	if ($idHotelSesion > 0 && !$obj->equipoPerteneceAlHotel($idEquipo, $idHotelSesion)) {
		echo json_encode(array('ok' => false, 'mensaje' => 'No tiene permiso para modificar este equipo.'));
		exit;
	}
}

$resultado = $obj->eliminarDocumentoAdjuntoEquipo($idEquipo, $tipo);
$resultado['idEquipo'] = $idEquipo;
$resultado['tipo'] = $tipo;
echo json_encode($resultado);
