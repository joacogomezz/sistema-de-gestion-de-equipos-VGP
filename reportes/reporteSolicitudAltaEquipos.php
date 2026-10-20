<?php
/**
 * Reporte PDF: Solicitud para dar de alta equipos de operación.
 * Generado con Dompdf (misma librería que el resto de reportes).
 * PDF_OUTPUT evita que seguridad.php envíe headers que conflictúen con la salida PDF.
 */
define('PDF_OUTPUT', true);
ob_start();

require_once __DIR__ . "/../seguridad.php";
requerirAutenticacion();

require_once __DIR__ . "/../clases/Conexion.php";
require_once __DIR__ . "/../clases/SolicitudAltaEquipos.php";
require_once __DIR__ . "/../librerias/dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
	die('ID de solicitud no válido.');
}

$obj = new SolicitudAltaEquipos();
$cab = $obj->obtenerSolicitud($id);
if (!$cab) {
	die('Solicitud no encontrada.');
}

$detalles = $obj->obtenerDetalle($id);
$responsable = trim(($cab['nombre_usuario'] ?? '') . ' ' . ($cab['apellido_usuario'] ?? ''));

function esc($v) {
	if ($v === null || $v === '') return '';
	return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$fechaFormato = $cab['fecha_solicitud'] ? date('d/m/Y', strtotime($cab['fecha_solicitud'])) : '';

$filasDetalle = [];
while ($row = mysqli_fetch_assoc($detalles)) {
	$filasDetalle[] = $row;
}

// Logo en base64 para Dompdf
$logoHtml = '';
$baseImg = __DIR__ . '/../img/';
foreach (array('LOGO-BAJA.png', 'LOGO-BAJA.jpg', 'LOGO-BAJA.jpeg', 'hotel.jpg') as $archivo) {
	if (file_exists($baseImg . $archivo)) {
		$ext = strtolower(pathinfo($baseImg . $archivo, PATHINFO_EXTENSION));
		if ($ext === 'jpeg') $ext = 'jpg';
		if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
			$datos = @file_get_contents($baseImg . $archivo);
			if ($datos !== false) {
				$logoHtml = '<img src="data:image/' . $ext . ';base64,' . base64_encode($datos) . '" alt="Logo" style="height: 72px; max-width: 160px;">';
			}
		}
		break;
	}
}
if ($logoHtml === '') {
	$logoHtml = '<div style="height: 72px; width: 160px; background: #f0f0f0; border: 1px solid #ccc;"></div>';
}

// Tabla de equipos (thead + tbody) — títulos completos sin cortar
$headers = array(
	'DESCRIPCION',
	'MARCA',
	'MODELO',
	'SERIE',
	'CARACTERISTICAS',
	'COLOR',
	'RESPONSABLES',
	'CENTRO DE COSTOS<br>(Capex/gastos de operación)',
	'FASE',
	'AREA/NUMERO DE HABITACION',
	'PISO',
	'TIPO DE HABITACIÓN',
	'DEPARTAMENTO'
);
$tablaHead = '<tr>';
$col = 0;
foreach ($headers as $h) {
	$col++;
	$tablaHead .= '<th class="th-col-' . $col . '">' . (strpos($h, '<br>') !== false ? $h : esc($h)) . '</th>';
}
$tablaHead .= '</tr>';

$tablaBody = '';
foreach ($filasDetalle as $row) {
	$tablaBody .= '<tr>';
	$tablaBody .= '<td>' . esc($row['descripcion'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($row['marca'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($row['modelo'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($row['numero_serie'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($row['caracteristicas'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($row['color'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($responsable) . '</td>';
	$tablaBody .= '<td>' . esc($row['centro_costos'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($row['nombre_fase'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($row['nombre_area'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($row['piso'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($row['tipo_habitacion'] ?? '') . '</td>';
	$tablaBody .= '<td>' . esc($row['nombre_departamento'] ?? '') . '</td>';
	$tablaBody .= '</tr>';
}

$html = '
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Solicitud para dar de alta equipos de operación</title>
	<style>
		@page { margin-top: 15mm; margin-right: 25mm; margin-bottom: 15mm; margin-left: 25mm; }
		* { box-sizing: border-box; }
		body { font-family: Arial, sans-serif; font-size: 7px; margin: 0; padding: 8px 6px; color: #333; max-width: 100%; overflow-x: hidden; }
		.header { display: table; width: 100%; margin-bottom: 8px; max-width: 100%; table-layout: auto; }
		.header-logo { display: table-cell; width: 1%; white-space: nowrap; vertical-align: middle; padding-right: 24px; }
		.header-titulo { display: table-cell; vertical-align: middle; font-size: 12px; font-weight: bold; color: #333; padding-left: 0; text-align: left; }
		.tabla-equipos { width: 100%; max-width: 100%; border-collapse: collapse; margin: 0 0 0 0; font-size: 6px; page-break-after: avoid; }
		.tabla-equipos th, .tabla-equipos td { border: 1px solid #c9b896; padding: 4px; text-align: left; word-wrap: break-word; }
		.tabla-equipos th { background-color: #C9A562; color: #fff; font-weight: bold; white-space: normal; word-wrap: break-word; font-size: 6px; line-height: 1.35; padding: 5px 3px; text-align: center; }
		.tabla-equipos th.th-col-1 { min-width: 38px; }
		.tabla-equipos th.th-col-2 { min-width: 28px; }
		.tabla-equipos th.th-col-3 { min-width: 30px; }
		.tabla-equipos th.th-col-4 { min-width: 24px; }
		.tabla-equipos th.th-col-5 { min-width: 62px; white-space: nowrap; }
		.tabla-equipos th.th-col-6 { min-width: 24px; }
		.tabla-equipos th.th-col-7 { min-width: 48px; }
		.tabla-equipos th.th-col-8 { min-width: 42px; }
		.tabla-equipos th.th-col-9 { min-width: 22px; }
		.tabla-equipos th.th-col-10 { min-width: 50px; }
		.tabla-equipos th.th-col-11 { min-width: 20px; }
		.tabla-equipos th.th-col-12 { min-width: 42px; }
		.tabla-equipos th.th-col-13 { min-width: 48px; }
		.tabla-equipos .fila-info td { font-size: 7px; background-color: #fff; color: #333; font-weight: bold; }
		.tabla-equipos .fila-datos-localizacion th { font-size: 6px; background-color: #C9A562; color: #fff; font-weight: bold; text-align: center; }
		.tabla-conjunta { width: 100%; max-width: 100%; border-collapse: collapse; margin: 0; border: 1px solid #c9b896; page-break-before: avoid; }
		.tabla-conjunta td { border: 1px solid #c9b896; vertical-align: top; }
		.tabla-conjunta .fila-clasif td { padding: 10px 12px; font-size: 7px; background-color: #fff; color: #333; }
		.tabla-conjunta .fila-clasif label { font-weight: bold; }
		.tabla-conjunta .fila-clasif .celda-clasif-1 { width: 50%; }
		.tabla-conjunta .fila-clasif .celda-clasif-2 { width: 50%; }
		.tabla-conjunta .fila-pie td { padding: 0; }
		.tabla-conjunta .fila-pie .pie-col-izq { border-right: none; }
		.tabla-conjunta .fila-pie .pie-col-der { border-left: none; }
		.tabla-conjunta .pie-col-der { width: 42%; }
		.pie-dos-columnas { width: 100%; border-collapse: collapse; }
		.pie-dos-columnas td { border: 1px solid #c9b896; vertical-align: top; padding: 0; }
		.pie-col-izq { width: 58%; }
		.pie-col-der { width: 42%; }
		.tabla-autorizaciones { width: 100%; border-collapse: collapse; font-size: 7px; }
		.tabla-autorizaciones th, .tabla-autorizaciones td { border: 1px solid #c9b896; padding: 8px 10px; vertical-align: bottom; color: #333; }
		.tabla-autorizaciones th.fila-titulo { padding: 4px 8px; }
		.tabla-autorizaciones tr.fila-encabezados th { padding: 4px 8px; }
		.tabla-autorizaciones .fila-titulo { font-weight: bold; font-size: 8px; text-align: center; }
		.tabla-autorizaciones .fila-encabezados th { font-weight: bold; text-align: center; }
		.tabla-autorizaciones .col-firma { width: 38%; }
		.bloque-roles { font-size: 7px; color: #333; margin-top: 12px; width: 100%; }
		.bloque-roles .fila-rol { display: table; width: 62%; margin-bottom: 22px; table-layout: auto; }
		.bloque-roles .celda-label { display: table-cell; width: 1%; white-space: nowrap; vertical-align: bottom; padding-right: 2px; }
		.bloque-roles .nombre-rol { font-weight: bold; }
		.bloque-roles .celda-linea { display: table-cell; vertical-align: bottom; }
		.bloque-roles .linea-rol { border-bottom: 1px solid #c9b896; display: block; min-height: 14px; width: 100%; }
		.tabla-departamento { width: 100%; border-collapse: collapse; font-size: 7px; }
		.tabla-departamento th, .tabla-departamento td { border: 1px solid #c9b896; padding: 10px 8px; }
		.tabla-departamento tr.fila-encabezados th { padding: 4px 8px; }
		.tabla-departamento .fila-titulo { font-weight: bold; font-size: 9px; text-align: center; background-color: #fff; color: #333; }
		.tabla-departamento .fila-encabezados th { text-align: center; font-weight: bold; background-color: #fff; color: #333; }
		.tabla-departamento tr.fila-dato td { text-align: center; vertical-align: middle; padding: 36px 8px; }
		.tabla-departamento tr.fila-dato .celda-vacia { min-height: 94px; }
		.contenedor-formato { width: 100%; border: 2px solid #c9b896; padding: 14px 0 0 0; box-sizing: border-box; }
	</style>
</head>
<body>
	<div class="contenedor-formato">
	<div class="header">
		<div class="header-logo">' . $logoHtml . '</div>
		<div class="header-titulo">SOLICITUD PARA DAR DE ALTA EQUIPOS DE OPERACIÓN</div>
	</div>

	<table class="tabla-equipos">
		<thead>
			<tr class="fila-info">
				<td colspan="4"><strong>DEPARTAMENTO SOLICITANTE:</strong> ' . esc($cab['nombre_departamento_solicitante']) . '</td>
				<td colspan="3"><strong>CANTIDAD DE EQUIPOS:</strong> ' . esc($cab['cantidad_equipos']) . '</td>
				<td colspan="3"><strong>FECHA:</strong> ' . $fechaFormato . '</td>
				<td colspan="3"><strong>SOLICITUD #:</strong> ' . esc($cab['numero_solicitud']) . '</td>
			</tr>
			<tr class="fila-datos-localizacion">
				<th colspan="9">DATOS DEL EQUIPO</th>
				<th colspan="4">LOCALIZACION</th>
			</tr>
			' . $tablaHead . '
		</thead>
		<tbody>' . $tablaBody . '</tbody>
	</table>

	<table class="tabla-conjunta">
		<tr class="fila-clasif">
			<td class="celda-clasif-1"><label>Clasificación 1:</label> ' . esc($cab['clasificacion_1'] ?? '') . '</td>
			<td class="celda-clasif-2"><label>Clasificación 2:</label> ' . esc($cab['clasificacion_2'] ?? '') . '</td>
		</tr>
		<tr class="fila-pie">
			<td class="pie-col-izq" style="width:58%;">
					<table class="tabla-autorizaciones">
						<tr>
							<th colspan="2" class="fila-titulo">AUTORIZACIONES:</th>
						</tr>
						<tr class="fila-encabezados">
							<th>NOMBRE COMPLETO:</th>
							<th class="col-firma">FIRMA:</th>
						</tr>
					</table>
					<div class="bloque-roles">
						<div class="fila-rol"><div class="celda-label"><span class="nombre-rol">JEFE DE ÁREA:</span></div><div class="celda-linea"><span class="linea-rol"></span></div></div>
						<div class="fila-rol"><div class="celda-label"><span class="nombre-rol">ENCARGADO DE INVENTARIOS:</span></div><div class="celda-linea"><span class="linea-rol"></span></div></div>
						<div class="fila-rol"><div class="celda-label"><span class="nombre-rol">SUBCONTRALOR:</span></div><div class="celda-linea"><span class="linea-rol"></span></div></div>
						<div class="fila-rol"><div class="celda-label"><span class="nombre-rol">AUDITOR INTERNO:</span></div><div class="celda-linea"><span class="linea-rol"></span></div></div>
					</div>
				</td>
				<td class="pie-col-der">
					<table class="tabla-departamento">
						<tr class="fila-encabezados">
							<th>DEPARTAMENTO</th>
							<th>NOMBRE, FIRMA Y SELLO:</th>
						</tr>
						<tr class="fila-dato">
							<td>SEGURIDAD:</td>
							<td class="celda-vacia"></td>
						</tr>
						<tr class="fila-dato">
							<td>RECEPCIÓN DE MERCANCÍA:</td>
							<td class="celda-vacia"></td>
						</tr>
					</table>
				</td>
			</tr>
	</table>
	</div>
</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$nombreArchivo = 'Solicitud_Alta_Equipos_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $cab['numero_solicitud']) . '_' . date('Y-m-d') . '.pdf';
ob_end_clean();
$dompdf->stream($nombreArchivo, array("Attachment" => true));
