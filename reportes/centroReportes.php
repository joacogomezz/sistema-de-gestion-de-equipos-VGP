<?php
/**
 * Centro de reportes: un solo generador con filtros (hotel, departamento, fechas) en PDF (Dompdf) o Excel (.xls como tabla HTML,
 * el mismo formato que reporteEquiposSeleccionados.php). Solo lectura.
 */
define('PDF_OUTPUT', true);
use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . "/../seguridad.php";
requerirAutenticacion();
require_once __DIR__ . "/../clases/Conexion.php";

$cx = (new conectar())->conexion();
mysqli_set_charset($cx, 'utf8mb4');

$esSuper = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$tipo = $_GET['tipo'] ?? '';
$fmt = ($_GET['fmt'] ?? 'pdf') === 'xls' ? 'xls' : 'pdf';
$idHotel = $esSuper ? (int)($_GET['hotel'] ?? 0) : (int)($_SESSION['id_hotel'] ?? 0);
$idDepto = (int)($_GET['depto'] ?? 0);
$fecha = function ($k) { $v = (string)($_GET[$k] ?? ''); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : ''; };
$desde = $fecha('desde'); $hasta = $fecha('hasta');

$TITULOS = array('activos' => 'Reporte de equipos activos', 'depto' => 'Reporte por departamento', 'altas' => 'Reporte de altas',
	'bi' => 'Reporte de bajas individuales', 'bm' => 'Reporte de bajas masivas', 'todas' => 'Reporte de todas las bajas');
if (!isset($TITULOS[$tipo])) { http_response_code(400); header('Content-Type: text/plain; charset=utf-8'); echo 'Tipo de reporte no válido.'; exit; }
if ($tipo === 'depto' && !$idDepto) { http_response_code(400); header('Content-Type: text/plain; charset=utf-8'); echo 'Elige el departamento.'; exit; }

$e = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$fc = function ($f) { return $f && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $f, $m) ? $m[3] . '/' . $m[2] . '/' . $m[1] : ''; };
$mm = function ($marca, $modelo) { $marca = trim((string)$marca); $modelo = trim((string)$modelo); return ($marca !== '' && stripos($modelo, $marca) === 0) ? $modelo : trim($marca . ' ' . $modelo); };
$dinero = function ($v, $mon = 'MXN') { return ($mon === 'USD' ? 'US$' : ($mon === 'EUR' ? '€' : '$')) . ' ' . number_format((float)$v, 2); };

// Condiciones comunes (valores enteros o fechas validadas: seguros para interpolar)
$hotelEq = "COALESCE(e.id_hotel, d.id_hotel)";
$cond = function ($colHotel, $colDepto, $colFecha) use ($idHotel, $idDepto, $desde, $hasta) {
	$w = '';
	if ($idHotel) $w .= " AND $colHotel = $idHotel";
	if ($idDepto && $colDepto) $w .= " AND $colDepto = $idDepto";
	if ($colFecha && $desde) $w .= " AND $colFecha >= '$desde'";
	if ($colFecha && $hasta) $w .= " AND $colFecha <= '$hasta'";
	return $w;
};
$q = function ($sql) use ($cx) { $r = mysqli_query($cx, $sql); $o = array(); while ($r && ($f = mysqli_fetch_assoc($r))) $o[] = $f; if (!$r) error_log('centroReportes: ' . mysqli_error($cx)); return $o; };

$cols = array(); $filas = array(); $pie = null; $resumen = array();
if ($tipo === 'activos' || $tipo === 'depto' || $tipo === 'altas') {
	$w = $tipo === 'activos' ? " AND e.estado = 'Activo'" : ($tipo === 'altas' ? '' : " AND e.estado <> 'Dado de Baja'");
	$w .= $cond($hotelEq, 'e.id_departamento', $tipo === 'altas' ? 'e.fecha_ingreso' : '');
	$rows = $q("SELECT e.codigo_equipo, e.descripcion, e.marca, e.modelo, e.numero_serie, e.estado, e.ubicacion, e.usuario_registro, e.fecha_ingreso, e.numero_factura,
			e.valor_adquisicion, e.tipo_moneda, d.nombre AS depto, h.nombre AS hotel
		FROM equipos e LEFT JOIN departamentos d ON d.id_departamento = e.id_departamento LEFT JOIN hoteles h ON h.id_hotel = $hotelEq
		WHERE 1=1 $w ORDER BY " . ($tipo === 'altas' ? 'e.fecha_ingreso DESC, e.id_equipo DESC' : 'd.nombre, e.descripcion, e.codigo_equipo'));
	$cols = $tipo === 'altas' ? array('Fecha de ingreso', 'Código', 'Descripción', 'Marca / Modelo', 'Serie', 'Departamento', 'Factura', 'Valor')
		: array('Código', 'Descripción', 'Marca / Modelo', 'Serie', 'Departamento', 'Ubicación', 'Responsable', $tipo === 'depto' ? 'Estado' : 'Ingreso', 'Valor');
	$tot = array();
	foreach ($rows as $r) {
		$mon = $r['tipo_moneda'] ?: 'MXN'; $tot[$mon] = ($tot[$mon] ?? 0) + (float)$r['valor_adquisicion'];
		$filas[] = $tipo === 'altas'
			? array($fc($r['fecha_ingreso']), $r['codigo_equipo'], $r['descripcion'], $mm($r['marca'], $r['modelo']), $r['numero_serie'], $r['depto'], $r['numero_factura'], $dinero($r['valor_adquisicion'], $mon))
			: array($r['codigo_equipo'], $r['descripcion'], $mm($r['marca'], $r['modelo']), $r['numero_serie'], $r['depto'], $r['ubicacion'], $r['usuario_registro'], $tipo === 'depto' ? $r['estado'] : $fc($r['fecha_ingreso']), $dinero($r['valor_adquisicion'], $mon));
	}
	$resumen[] = array('Equipos', number_format(count($rows)));
	foreach ($tot as $m => $v) $resumen[] = array('Valor total ' . $m, $dinero($v, $m));
}
if ($tipo === 'bi' || $tipo === 'todas') {
	$rows = $q("SELECT b.id_baja, b.numero_baja_hotel, b.fecha_baja, b.motivo_baja, b.destino_equipo, b.valor_residual, b.tecnico_reviso, b.gerente_area,
			e.codigo_equipo, e.descripcion, e.marca, e.modelo, e.numero_serie, e.tipo_moneda, d.nombre AS depto
		FROM bajas_equipos b INNER JOIN equipos e ON e.id_equipo = b.id_equipo LEFT JOIN departamentos d ON d.id_departamento = COALESCE(b.id_departamento, e.id_departamento)
		WHERE b.tipo_baja = 'Individual'" . $cond('COALESCE(b.id_hotel, d.id_hotel, e.id_hotel)', 'd.id_departamento', 'b.fecha_baja') . " ORDER BY b.fecha_baja DESC, b.id_baja DESC");
	foreach ($rows as $r) {
		$fol = 'BI-' . substr($r['fecha_baja'], 0, 4) . '-' . str_pad($r['numero_baja_hotel'] ?: $r['id_baja'], 4, '0', STR_PAD_LEFT);
		$fila = array($fol, $fc($r['fecha_baja']), $r['codigo_equipo'], $r['descripcion'] . ' · ' . $mm($r['marca'], $r['modelo']), $r['depto'], $r['motivo_baja'], $r['destino_equipo'], $dinero($r['valor_residual'], $r['tipo_moneda'] ?: 'MXN'));
		if ($tipo === 'bi') $fila = array_merge($fila, array($r['tecnico_reviso'], $r['gerente_area']));
		else array_unshift($fila, 'Individual');
		$filas[] = array('_f' => $r['fecha_baja'], 'v' => (float)$r['valor_residual'], 'c' => $fila);
	}
}
if ($tipo === 'bm' || $tipo === 'todas') {
	$rows = $q("SELECT bm.id_baja_masiva, bm.numero_baja_masiva_hotel, bm.fecha_baja, bm.motivo_baja, bm.destino_equipo, x.valor_residual_individual, x.observaciones,
			e.codigo_equipo, e.descripcion, e.marca, e.modelo, e.tipo_moneda, d.nombre AS depto
		FROM bajas_masivas bm INNER JOIN bajas_masivas_equipos x ON x.id_baja_masiva = bm.id_baja_masiva INNER JOIN equipos e ON e.id_equipo = x.id_equipo
		LEFT JOIN departamentos d ON d.id_departamento = e.id_departamento LEFT JOIN hoteles h2 ON h2.nombre = bm.hotel_area
		WHERE 1=1" . $cond('COALESCE(bm.id_hotel, h2.id_hotel, e.id_hotel)', 'e.id_departamento', 'bm.fecha_baja') . " ORDER BY bm.fecha_baja DESC, bm.id_baja_masiva DESC, e.codigo_equipo");
	$folios = array();
	foreach ($rows as $r) {
		$fol = 'BM-' . substr($r['fecha_baja'], 0, 4) . '-' . str_pad($r['numero_baja_masiva_hotel'] ?: $r['id_baja_masiva'], 3, '0', STR_PAD_LEFT);
		$folios[$fol] = 1;
		$fila = $tipo === 'bm'
			? array($fol, $fc($r['fecha_baja']), $r['codigo_equipo'], $r['descripcion'] . ' · ' . $mm($r['marca'], $r['modelo']), $r['depto'], $r['motivo_baja'], $r['destino_equipo'], $dinero($r['valor_residual_individual'], $r['tipo_moneda'] ?: 'MXN'), $r['observaciones'])
			: array('Masiva', $fol, $fc($r['fecha_baja']), $r['codigo_equipo'], $r['descripcion'] . ' · ' . $mm($r['marca'], $r['modelo']), $r['depto'], $r['motivo_baja'], $r['destino_equipo'], $dinero($r['valor_residual_individual'], $r['tipo_moneda'] ?: 'MXN'));
		$filas[] = array('_f' => $r['fecha_baja'], 'v' => (float)$r['valor_residual_individual'], 'c' => $fila);
	}
	if ($tipo === 'bm') $resumen[] = array('Folios', number_format(count($folios)));
}
if (in_array($tipo, array('bi', 'bm', 'todas'), true)) {
	usort($filas, function ($a, $b) { return strcmp($b['_f'], $a['_f']); });
	$resumen[] = array('Equipos dados de baja', number_format(count($filas)));
	$resumen[] = array('Valor residual total', $dinero(array_sum(array_column($filas, 'v'))));
	$filas = array_column($filas, 'c');
	$cols = $tipo === 'bi' ? array('Folio', 'Fecha', 'Código', 'Equipo', 'Departamento', 'Motivo', 'Destino', 'Valor residual', 'Técnico', 'Vo. Bo.')
		: ($tipo === 'bm' ? array('Folio', 'Fecha', 'Código', 'Equipo', 'Departamento', 'Motivo', 'Destino', 'Valor residual', 'Observación')
		: array('Tipo', 'Folio', 'Fecha', 'Código', 'Equipo', 'Departamento', 'Motivo', 'Destino', 'Valor residual'));
}

// Encabezado: filtros aplicados en palabras
$nombre = function ($tabla, $col, $id) use ($cx) { if (!$id) return ''; $r = mysqli_query($cx, "SELECT nombre FROM $tabla WHERE $col = " . (int)$id); $f = $r ? mysqli_fetch_row($r) : null; return $f ? $f[0] : ''; };
$hotelTxt = $idHotel ? $nombre('hoteles', 'id_hotel', $idHotel) : 'Todos los hoteles';
$filtros = array_filter(array($hotelTxt, $idDepto ? 'Departamento: ' . $nombre('departamentos', 'id_departamento', $idDepto) : '',
	($desde || $hasta) && in_array($tipo, array('altas', 'bi', 'bm', 'todas'), true) ? 'Periodo: ' . ($desde ? $fc($desde) : 'inicio') . ' a ' . ($hasta ? $fc($hasta) : 'hoy') : ''));
$titulo = $TITULOS[$tipo];
$archivo = preg_replace('/[^A-Za-z0-9_-]+/', '_', $titulo . '_' . $hotelTxt) . '_' . date('Y-m-d');

$tabla = '<table class="t"><thead><tr>' . implode('', array_map(function ($c) use ($e) { return '<th>' . $e($c) . '</th>'; }, $cols)) . '</tr></thead><tbody>' .
	($filas ? implode('', array_map(function ($f) use ($e) { return '<tr>' . implode('', array_map(function ($c) use ($e) { return '<td>' . $e($c) . '</td>'; }, $f)) . '</tr>'; }, $filas))
		: '<tr><td colspan="' . count($cols) . '" style="text-align:center;color:#777;padding:14px">Sin registros con estos filtros.</td></tr>') . '</tbody></table>';
$res = '<table class="r"><tr>' . implode('', array_map(function ($x) use ($e) { return '<td><span>' . $e($x[0]) . '</span><b>' . $e($x[1]) . '</b></td>'; }, $resumen)) . '</tr></table>';

if ($fmt === 'xls') {
	header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
	header('Content-Disposition: attachment; filename="' . $archivo . '.xls"');
	header('Cache-Control: no-cache, must-revalidate');
	echo "\xEF\xBB\xBF<html><head><meta charset=\"UTF-8\"></head><body><h3>" . $e($titulo) . '</h3><p>' . $e(implode(' · ', $filtros)) . ' · Generado ' . date('d/m/Y H:i') . '</p>' .
		'<table border="1"><thead><tr>' . implode('', array_map(function ($c) use ($e) { return '<th style="background:#F2EDE4">' . $e($c) . '</th>'; }, $cols)) . '</tr></thead><tbody>' .
		implode('', array_map(function ($f) use ($e) { return '<tr>' . implode('', array_map(function ($c) use ($e) { return '<td style="mso-number-format:\'\@\'">' . $e($c) . '</td>'; }, $f)) . '</tr>'; }, $filas)) . '</tbody></table></body></html>';
	exit;
}

@set_time_limit(180);
@ini_set('memory_limit', '512M');
require_once __DIR__ . "/../librerias/dompdf/autoload.inc.php";
$logo = '';
foreach (array(__DIR__ . '/../img/hotel.jpg', __DIR__ . '/../img/logo.png') as $ruta) {
	if (is_file($ruta)) { $logo = '<img src="data:image/' . (substr($ruta, -3) === 'png' ? 'png' : 'jpeg') . ';base64,' . base64_encode(file_get_contents($ruta)) . '" style="height:46px">'; break; }
}
$html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
	@page{margin:28px 30px 40px}
	body{font-family:DejaVu Sans,Arial,sans-serif;font-size:9px;color:#1F1F1F}
	.enc{width:100%;border-bottom:2px solid #1F1F1F;padding-bottom:8px;margin-bottom:10px}
	.enc td{vertical-align:middle}
	h1{font-size:15px;margin:0 0 2px}
	.sub{color:#6B6B6B;font-size:9px}
	.r{width:100%;border-collapse:separate;border-spacing:6px 0;margin:0 -6px 10px}
	.r td{background:#F7F3EC;border:1px solid #E8E2D8;border-radius:4px;padding:6px 8px}
	.r span{display:block;color:#6B6B6B;font-size:8px;text-transform:uppercase;letter-spacing:.04em}
	.r b{font-size:12px}
	.t{width:100%;border-collapse:collapse}
	.t th{text-align:left;font-weight:bold;padding:5px 4px;border-top:1px solid #1F1F1F;border-bottom:1px solid #1F1F1F;background:#F7F3EC}
	.t td{padding:4px;border-bottom:1px solid #E8E2D8;vertical-align:top}
	.pie{position:fixed;bottom:-26px;left:0;right:0;font-size:8px;color:#6B6B6B}
</style></head><body>
	<table class="enc"><tr><td style="width:60px">' . $logo . '</td><td><h1>' . $e($titulo) . '</h1><div class="sub">The Villa Group · ' . $e(implode(' · ', $filtros)) . '</div></td>
	<td style="text-align:right" class="sub">Generado el ' . date('d/m/Y H:i') . '<br>por ' . $e(trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''))) . '</td></tr></table>' .
	$res . $tabla . '<div class="pie">Sistema de Gestión de Equipos · ' . $e($titulo) . '</div></body></html>';

$op = new Options();
$op->set('isRemoteEnabled', false);
$op->set('defaultFont', 'DejaVu Sans');
$pdf = new Dompdf($op);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('letter', count($cols) > 7 ? 'landscape' : 'portrait');
$pdf->render();
$canvas = $pdf->getCanvas();
$canvas->page_text($canvas->get_width() - 90, $canvas->get_height() - 28, 'Página {PAGE_NUM} de {PAGE_COUNT}', null, 7, array(0.42, 0.42, 0.42));
$pdf->stream($archivo . '.pdf', array('Attachment' => empty($_GET['ver'])));
