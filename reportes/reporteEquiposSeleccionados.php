<?php
/**
 * Exportación a Excel (.xls tabla HTML) de equipos seleccionados desde vistas/equipos.
 * Se abre en Microsoft Excel / LibreOffice sin librerías adicionales.
 */
require_once __DIR__ . '/../seguridad.php';
requerirAutenticacion();

require_once __DIR__ . '/../clases/Conexion.php';
require_once __DIR__ . '/../clases/Equipos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	header('Content-Type: text/plain; charset=UTF-8');
	echo 'Use el formulario de la lista de equipos para generar el archivo.';
	exit;
}

$ids_raw = isset($_POST['ids']) ? (string) $_POST['ids'] : '';
$parts = array_filter(array_map('intval', explode(',', $ids_raw)));
$ids = array_values(array_unique(array_filter($parts, static function ($x) {
	return $x > 0;
})));

if (count($ids) === 0) {
	http_response_code(400);
	header('Content-Type: text/html; charset=UTF-8');
	echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body><p>No se indicaron equipos válidos.</p><p><a href="javascript:history.back()">Volver</a></p></body></html>';
	exit;
}

const MAX_EQUIPOS_EXPORT = 400;
if (count($ids) > MAX_EQUIPOS_EXPORT) {
	$ids = array_slice($ids, 0, MAX_EQUIPOS_EXPORT);
}

$c = new conectar();
$conexion = $c->conexion();
(new equipos())->ensureRegistroEquiposCols();

$es_super_admin = isset($_SESSION['iduser']) && (int) $_SESSION['iduser'] === 1;
$postHotel = isset($_POST['id_hotel']) ? (int) $_POST['id_hotel'] : 0;
$id_hotel_filtro = null;

$chkHotelCol = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
$tieneIdHotelDept = $chkHotelCol && mysqli_num_rows($chkHotelCol) > 0;

if ($es_super_admin) {
	$id_hotel_filtro = $postHotel > 0 ? $postHotel : null;
	if ($tieneIdHotelDept && $id_hotel_filtro === null) {
		http_response_code(400);
		header('Content-Type: text/html; charset=UTF-8');
		echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title></head><body><p>Como administrador general debe elegir un hotel en la lista de equipos antes de exportar.</p><p><a href="javascript:history.back()">Volver</a></p></body></html>';
		exit;
	}
} else {
	$id_hotel_filtro = isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null;
}

$whereHotel = '';
if ($tieneIdHotelDept && $id_hotel_filtro !== null && $id_hotel_filtro > 0) {
	$whereHotel = " AND d.id_hotel = '" . (int) $id_hotel_filtro . "' ";
}

$idList = implode(',', $ids);
$orderField = 'FIELD(e.id_equipo,' . $idList . ')';

$sql = "SELECT e.codigo_equipo, e.descripcion, e.marca, e.modelo, e.numero_serie, e.estado,
		e.ubicacion, e.valor_adquisicion, e.tipo_moneda, e.fecha_ingreso, d.nombre AS departamento,
		e.usuario_registro, e.numero_factura
	FROM equipos e
	LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
	WHERE e.id_equipo IN ($idList) $whereHotel
	ORDER BY $orderField";

$result = mysqli_query($conexion, $sql);

$esc = static function ($v) {
	if ($v === null) {
		return '';
	}
	$s = (string) $v;
	// Evitar que Excel interprete el texto como fórmula (=, +, -, @)
	if ($s !== '' && ($s[0] === '=' || $s[0] === '+' || $s[0] === '-' || $s[0] === '@')) {
		$s = "'" . $s;
	}
	return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
};

$nombreAlcance = 'Hotel Villa Group';
if (!$es_super_admin && !empty($_SESSION['nombre_hotel'])) {
	$nombreAlcance = (string) $_SESSION['nombre_hotel'];
} elseif ($es_super_admin && $id_hotel_filtro) {
	$resNom = mysqli_query($conexion, "SELECT nombre FROM hoteles WHERE id_hotel='" . (int) $id_hotel_filtro . "' LIMIT 1");
	if ($resNom && $rwNom = mysqli_fetch_row($resNom)) {
		$nombreAlcance = (string) $rwNom[0];
	}
}

$nombreArchivo = 'Informe_Equipos_Seleccion_' . date('Y-m-d_His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Cache-Control: max-age=0');
header('Pragma: public');

echo "\xEF\xBB\xBF";
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>';

echo '<table border="1">';
echo '<tr><th colspan="13" style="font-size:14px;text-align:left;">Informe de equipos (selección)</th></tr>';
echo '<tr><td colspan="13"><strong>' . $esc($nombreAlcance) . '</strong> — Generado: ' . $esc(date('d/m/Y H:i:s')) . '</td></tr>';
echo '<tr></tr>';
echo '<tr>
	<th>Código</th>
	<th>Descripción</th>
	<th>Marca</th>
	<th>Modelo</th>
	<th>Nº serie</th>
	<th>Estado</th>
	<th>Departamento</th>
	<th>Ubicación</th>
	<th>Usuario</th>
	<th>No. factura</th>
	<th>Valor</th>
	<th>Moneda</th>
	<th>F. ingreso</th>
</tr>';

$n = 0;
if ($result) {
	while ($row = mysqli_fetch_assoc($result)) {
		$n++;
		$fechaIng = !empty($row['fecha_ingreso']) ? date('d/m/Y', strtotime($row['fecha_ingreso'])) : '';
		$valorNum = isset($row['valor_adquisicion']) ? (float) $row['valor_adquisicion'] : 0.0;
		$depto = $row['departamento'] ? $row['departamento'] : 'No asignado';
		echo '<tr>';
		echo '<td>' . $esc($row['codigo_equipo'] ?? '') . '</td>';
		echo '<td>' . $esc($row['descripcion'] ?? '') . '</td>';
		echo '<td>' . $esc($row['marca'] ?? '') . '</td>';
		echo '<td>' . $esc($row['modelo'] ?? '') . '</td>';
		echo '<td>' . $esc($row['numero_serie'] ?? '') . '</td>';
		echo '<td>' . $esc($row['estado'] ?? '') . '</td>';
		echo '<td>' . $esc($depto) . '</td>';
		echo '<td>' . $esc($row['ubicacion'] ?? '') . '</td>';
		echo '<td>' . $esc($row['usuario_registro'] ?? '') . '</td>';
		echo '<td>' . $esc($row['numero_factura'] ?? '') . '</td>';
		// Número reconocible por Excel (punto decimal)
		echo '<td style="mso-number-format:0.00;">' . $valorNum . '</td>';
		echo '<td>' . $esc($row['tipo_moneda'] ?? '') . '</td>';
		echo '<td>' . $esc($fechaIng) . '</td>';
		echo '</tr>';
	}
}

if ($n === 0) {
	echo '<tr><td colspan="13">No se encontraron equipos con los criterios indicados (compruebe que pertenezcan al hotel seleccionado).</td></tr>';
}

echo '</table></body></html>';
