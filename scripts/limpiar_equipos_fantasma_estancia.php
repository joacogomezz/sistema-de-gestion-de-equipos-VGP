<?php
/**
 * Uso único (CLI): elimina equipos fantasma por número de serie.
 * php scripts/limpiar_equipos_fantasma_estancia.php
 * php scripts/limpiar_equipos_fantasma_estancia.php --dry-run
 */
if (php_sapi_name() !== 'cli') {
	die("Ejecutar solo desde línea de comandos.\n");
}

$dryRun = in_array('--dry-run', $argv ?? array(), true);

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/clases/Conexion.php';
require_once dirname(__DIR__) . '/clases/Equipos.php';

$series = array(
	'MXL949416K', 'MXL2036BLQ', 'MXL406384K', 'MXL7232BY1', 'MXL406385N', 'MXL4515K7F', 'MXL6221N63',
	'4CE0392N8M', '4CE0392N8K', 'MXL51532YQ', '4LE0392N78', 'MXL9373SHJ', 'MXL9373SH2', 'MXL3385BND',
	'8CN25108JQ', '8CN25108JG', '4CE0392N7J', 'MXL4123DBV', '5CD9348GNR', '2MXL6312JZ4', 'MXL4063835',
	'5CD40155HW', '5CD0015H1L', '5CD320G739', 'TCP-P069-SF', 'MXL2203761', 'MXL2503PTC', 'MXL2503JB6',
	'MXL2503PS5', 'MXL4515JY4', 'MXL1412Y7F', '5CD1018GBH', 'MXL50429PF', 'MXL949416G', 'MXL4063855', 'MXL406384T',
	'MXL50429P2', 'MX50429MG', 'MXL3492PJ7', 'MXL50429P1', 'MXL4515FSK', 'MXL406385Y', 'MXL2024B9V',
	'MXL2024BD0', 'MXL2024B8Z', '1CZ5510064', 'MXL2036BLR', 'MXL3033FPV', 'MXL3033FPD', 'MXL3033FPP',
	'MXL3033FPK', 'CND235280N', 'MXL220374T', 'MXL1412YD7', 'MXL1412YF7', 'MXL3033DZN', 'K2012N0080921',
	'M8PFCG01480735C', 'MXL419126T', '2M280202TS',
);

$c = new conectar();
$conexion = $c->conexion();
if (!$conexion) {
	die("Error de conexión: " . mysqli_connect_error() . "\n");
}

$esc = array();
foreach ($series as $s) {
	$esc[] = "'" . mysqli_real_escape_string($conexion, $s) . "'";
}
$inList = implode(',', $esc);

$sql = "SELECT e.id_equipo, e.codigo_equipo, e.numero_serie, e.id_hotel, e.id_departamento,
		d.nombre AS nombre_departamento, d.id_hotel AS hotel_depto
	FROM equipos e
	LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
	WHERE e.numero_serie IN ($inList)
	ORDER BY e.numero_serie";

$res = mysqli_query($conexion, $sql);
if (!$res) {
	die("Error consulta: " . mysqli_error($conexion) . "\n");
}

$filas = array();
while ($row = mysqli_fetch_assoc($res)) {
	$filas[] = $row;
}

$encontrados = array();
foreach ($filas as $f) {
	$encontrados[strtoupper(trim($f['numero_serie']))] = true;
}

$noEnBd = array();
foreach ($series as $s) {
	if (!isset($encontrados[strtoupper(trim($s))])) {
		$noEnBd[] = $s;
	}
}

echo ($dryRun ? "[DRY-RUN] " : '') . "Equipos encontrados: " . count($filas) . " / " . count($series) . "\n";

$obj = new equipos();
$ok = 0;
$fail = 0;

foreach ($filas as $f) {
	$id = (int)$f['id_equipo'];
	$line = sprintf(
		"id=%d serie=%s codigo=%s id_hotel=%s depto=%s hotel_depto=%s",
		$id,
		$f['numero_serie'],
		$f['codigo_equipo'] ?: '-',
		$f['id_hotel'] === null || $f['id_hotel'] === '' ? 'NULL' : $f['id_hotel'],
		$f['id_departamento'] === null || $f['id_departamento'] === '' ? 'NULL' : $f['id_departamento'],
		$f['hotel_depto'] === null || $f['hotel_depto'] === '' ? 'NULL' : $f['hotel_depto']
	);

	if ($dryRun) {
		echo "  [simular] $line\n";
		$ok++;
		continue;
	}

	$r = $obj->eliminaEquipo((string)$id);
	if ($r == 1) {
		echo "  OK eliminado: $line\n";
		$ok++;
	} else {
		echo "  ERROR al eliminar: $line\n";
		$fail++;
	}
}

if (count($noEnBd) > 0) {
	echo "\nSeries no encontradas en BD (" . count($noEnBd) . "):\n";
	foreach ($noEnBd as $s) {
		echo "  - $s\n";
	}
}

echo "\nResumen: eliminados OK=$ok, fallos=$fail, no en BD=" . count($noEnBd) . "\n";
