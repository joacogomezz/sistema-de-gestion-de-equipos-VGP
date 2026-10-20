<?php
// Bitácora de respaldos descargados. Vive en servidor/logs, carpeta que Apache no sirve.
function rutaBitacoraRespaldos() {
	return dirname(__DIR__, 2) . '/servidor/logs/respaldos.log';
}
function registrarRespaldo($quien, $bytes, $archivo) {
	$ruta = rutaBitacoraRespaldos();
	if (!is_dir(dirname($ruta))) @mkdir(dirname($ruta), 0775, true);
	$linea = json_encode(array('fecha' => date('Y-m-d H:i:s'), 'quien' => $quien, 'bytes' => (int)$bytes, 'archivo' => $archivo), JSON_UNESCAPED_UNICODE);
	@file_put_contents($ruta, $linea . "\n", FILE_APPEND | LOCK_EX);
}
function leerRespaldos($max = 10) {
	$ruta = rutaBitacoraRespaldos();
	if (!is_file($ruta)) return array();
	$lineas = @file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: array();
	$lista = array();
	foreach (array_reverse(array_slice($lineas, -$max)) as $l) {
		$d = json_decode($l, true);
		if (is_array($d)) $lista[] = $d;
	}
	return $lista;
}
