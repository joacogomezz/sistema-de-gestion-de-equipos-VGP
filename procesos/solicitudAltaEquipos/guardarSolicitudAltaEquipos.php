<?php
session_start();
require_once "../../clases/Conexion.php";
require_once "../../clases/SolicitudAltaEquipos.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || !isset($_SESSION['iduser'])) {
	echo json_encode(array('ok' => false, 'mensaje' => 'Sesión no válida'));
	exit;
}

$datos = isset($_POST['datos']) ? json_decode($_POST['datos'], true) : null;
if (!$datos || !isset($datos['detalles']) || !is_array($datos['detalles'])) {
	echo json_encode(array('ok' => false, 'mensaje' => 'Datos incompletos'));
	exit;
}

$cabecera = array(
	'id_usuario' => $_SESSION['iduser'],
	'id_departamento_solicitante' => isset($datos['id_departamento_solicitante']) ? $datos['id_departamento_solicitante'] : '',
	'numero_solicitud' => isset($datos['numero_solicitud']) ? $datos['numero_solicitud'] : '',
	'fecha_solicitud' => isset($datos['fecha_solicitud']) ? $datos['fecha_solicitud'] : date('Y-m-d'),
	'clasificacion_1' => isset($datos['clasificacion_1']) ? $datos['clasificacion_1'] : '',
	'clasificacion_2' => isset($datos['clasificacion_2']) ? $datos['clasificacion_2'] : '',
	'id_hotel' => isset($_SESSION['id_hotel']) && (int)$_SESSION['id_hotel'] > 0 ? (int)$_SESSION['id_hotel'] : ''
);

$obj = new SolicitudAltaEquipos();
$id_solicitud = $obj->guardar($cabecera, $datos['detalles']);

if ($id_solicitud) {
	echo json_encode(array('ok' => true, 'id_solicitud' => (int)$id_solicitud, 'mensaje' => 'Solicitud guardada correctamente'));
} else {
	echo json_encode(array('ok' => false, 'mensaje' => 'Error al guardar la solicitud'));
}
