<?php
/**
 * Respaldo de la base de datos hotel_equipos
 * Solo accesible por el administrador (id_usuario = 1)
 */
require_once __DIR__ . '/../../seguridad.php';
requerirAutenticacion();

if (!isset($_SESSION['iduser']) || (int)$_SESSION['iduser'] !== 1) {
	header('Location: ../../vistas/inicio.php');
	exit;
}

require_once __DIR__ . '/../../clases/Conexion.php';
$c = new conectar();
$conexion = $c->conexion();

if (!$conexion) {
	header('Content-Type: text/plain; charset=utf-8');
	echo 'Error: no se pudo conectar a la base de datos.';
	exit;
}

$nombre_bd = DB_NAME;

$sql = "-- Respaldo de base de datos: $nombre_bd\n";
$sql .= "-- Generado: " . date('Y-m-d H:i:s') . "\n";
$sql .= "-- Sistema de Gestión de Equipos - Hotel Villa Group\n\n";
$sql .= "CREATE DATABASE IF NOT EXISTS `$nombre_bd`;\nUSE `$nombre_bd`;\n\n";

$res_tablas = mysqli_query($conexion, "SHOW TABLES");
if (!$res_tablas) {
	header('Content-Type: text/plain; charset=utf-8');
	echo 'Error al listar tablas.';
	exit;
}

$tablas = array();
while ($row = mysqli_fetch_array($res_tablas)) {
	$tablas[] = $row[0];
}

foreach ($tablas as $tabla) {
	$res_create = mysqli_query($conexion, "SHOW CREATE TABLE `$tabla`");
	if (!$res_create) continue;
	$row_create = mysqli_fetch_row($res_create);
	$sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
	$sql .= $row_create[1] . ";\n\n";

	$res_data = mysqli_query($conexion, "SELECT * FROM `$tabla`");
	if (!$res_data || mysqli_num_rows($res_data) === 0) {
		$sql .= "\n";
		continue;
	}

	$num_campos = mysqli_num_fields($res_data);
	$nombres_campos = array();
	for ($i = 0; $i < $num_campos; $i++) {
		$field = mysqli_fetch_field_direct($res_data, $i);
		$nombres_campos[] = "`" . $field->name . "`";
	}
	$lista_campos = implode(', ', $nombres_campos);

	while ($fila = mysqli_fetch_row($res_data)) {
		$valores = array();
		foreach ($fila as $valor) {
			if ($valor === null) {
				$valores[] = 'NULL';
			} else {
				$valores[] = "'" . mysqli_real_escape_string($conexion, $valor) . "'";
			}
		}
		$sql .= "INSERT INTO `$tabla` ($lista_campos) VALUES (" . implode(', ', $valores) . ");\n";
	}
	$sql .= "\n";
}

$nombre_archivo = DB_NAME . '_respaldo_' . date('Y-m-d_H-i') . '.sql';

header('Content-Type: application/octet-stream; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Content-Length: ' . strlen($sql));

echo $sql;
exit;
