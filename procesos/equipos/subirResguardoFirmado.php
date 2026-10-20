<?php
	// Incluir archivo de seguridad
	require_once "../../seguridad.php";
	requerirAutenticacion();

	header('Content-Type: application/json; charset=utf-8');

	if (!isset($_POST['idEquipo']) || (int)$_POST['idEquipo'] <= 0) {
		echo json_encode(array('ok' => false, 'mensaje' => 'ID de equipo inválido.'));
		exit;
	}

	if (!isset($_FILES['resguardoFirmado']) || $_FILES['resguardoFirmado']['error'] !== 0) {
		echo json_encode(array('ok' => false, 'mensaje' => 'No se recibió el archivo PDF.'));
		exit;
	}

	$idEquipo = (int)$_POST['idEquipo'];
	$archivo = $_FILES['resguardoFirmado'];
	$nombreOriginal = isset($archivo['name']) ? strtolower($archivo['name']) : '';
	$ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);

	if ($ext !== 'pdf') {
		echo json_encode(array('ok' => false, 'mensaje' => 'Solo se permiten archivos PDF.'));
		exit;
	}

	// Máximo 10MB
	if (!isset($archivo['size']) || (int)$archivo['size'] <= 0 || (int)$archivo['size'] > (10 * 1024 * 1024)) {
		echo json_encode(array('ok' => false, 'mensaje' => 'El archivo debe pesar máximo 10MB.'));
		exit;
	}

	$carpeta = '../../archivos/resguardos_firmados/';
	if (!is_dir($carpeta)) {
		if (!mkdir($carpeta, 0777, true)) {
			echo json_encode(array('ok' => false, 'mensaje' => 'No se pudo crear la carpeta de resguardos.'));
			exit;
		}
	}

	$nombreFinal = 'equipo_' . $idEquipo . '.pdf';
	$rutaFinal = $carpeta . $nombreFinal;

	if (!move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
		echo json_encode(array('ok' => false, 'mensaje' => 'No se pudo guardar el PDF en el servidor.'));
		exit;
	}

	$urlRel = '../archivos/resguardos_firmados/' . $nombreFinal;
	$v = filemtime($rutaFinal) ?: time();
	echo json_encode(array(
		'ok' => true,
		'mensaje' => 'Resguardo firmado subido correctamente.',
		'archivo' => $nombreFinal,
		'url' => $urlRel,
		'urlVer' => $urlRel . '?v=' . $v,
		'idEquipo' => $idEquipo
	));
?>
