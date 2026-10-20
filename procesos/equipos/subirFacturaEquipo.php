<?php
require_once "../../seguridad.php";
requerirAutenticacion();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['idEquipo']) || (int)$_POST['idEquipo'] <= 0) {
	echo json_encode(array('ok' => false, 'mensaje' => 'ID de equipo inválido.'));
	exit;
}

if (!isset($_FILES['facturaEquipo']) || (int)$_FILES['facturaEquipo']['error'] !== 0) {
	echo json_encode(array('ok' => false, 'mensaje' => 'No se recibió el archivo.'));
	exit;
}

$idEquipo = (int)$_POST['idEquipo'];
$archivo = $_FILES['facturaEquipo'];
$nombreOriginal = isset($archivo['name']) ? strtolower($archivo['name']) : '';
$ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
if ($ext === 'jpeg') {
	$ext = 'jpg';
}

$permitidas = array('pdf', 'jpg', 'png', 'gif', 'webp');
if (!in_array($ext, $permitidas, true)) {
	echo json_encode(array('ok' => false, 'mensaje' => 'Formato no permitido. Use PDF, JPG, PNG, GIF o WEBP.'));
	exit;
}

if (!isset($archivo['size']) || (int)$archivo['size'] <= 0 || (int)$archivo['size'] > (10 * 1024 * 1024)) {
	echo json_encode(array('ok' => false, 'mensaje' => 'El archivo debe pesar máximo 10MB.'));
	exit;
}

$carpeta = realpath(__DIR__ . '/../../archivos');
if ($carpeta === false) {
	$carpeta = __DIR__ . '/../../archivos';
}
$sub = $carpeta . DIRECTORY_SEPARATOR . 'facturas_equipos';
if (!is_dir($sub)) {
	if (!mkdir($sub, 0777, true)) {
		echo json_encode(array('ok' => false, 'mensaje' => 'No se pudo crear la carpeta de facturas.'));
		exit;
	}
}

foreach ($permitidas as $e) {
	$ant = $sub . DIRECTORY_SEPARATOR . 'equipo_' . $idEquipo . '.' . $e;
	if (file_exists($ant) && is_file($ant)) {
		@unlink($ant);
	}
}

$nombreFinal = 'equipo_' . $idEquipo . '.' . $ext;
$rutaFinal = $sub . DIRECTORY_SEPARATOR . $nombreFinal;

if (!move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
	echo json_encode(array('ok' => false, 'mensaje' => 'No se pudo guardar el archivo en el servidor.'));
	exit;
}

$urlRel = '../archivos/facturas_equipos/' . $nombreFinal;
$v = filemtime($rutaFinal) ?: time();
echo json_encode(array(
	'ok' => true,
	'mensaje' => 'Factura escaneada guardada correctamente.',
	'archivo' => $nombreFinal,
	'url' => $urlRel,
	'urlVer' => $urlRel . '?v=' . $v,
	'downloadNombre' => 'factura_equipo_' . $idEquipo . '_' . $nombreFinal,
	'idEquipo' => $idEquipo
));
