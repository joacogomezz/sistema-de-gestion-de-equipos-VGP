<?php
use Dompdf\Dompdf;
use Dompdf\Options;

// Incluir archivo de seguridad (reportes/ está un nivel bajo la raíz del proyecto)
require_once __DIR__ . "/../seguridad.php";

// Requerir autenticación
requerirAutenticacion();

require_once __DIR__ . "/../clases/Conexion.php";
require_once __DIR__ . "/../librerias/dompdf/autoload.inc.php";

	$c= new conectar();
	$conexion=$c->conexion();

	// Obtener el ID de la baja masiva desde la URL
	$id_baja_masiva = isset($_GET['id_baja']) ? $_GET['id_baja'] : 0;
	
	if($id_baja_masiva == 0){
		echo "Error: No se especificó la baja masiva";
		exit;
	}

	// Usar la clase AltasBajas para obtener los datos
	require_once __DIR__ . "/../clases/AltasBajas.php";
	$objAltasBajas = new altasBajas();
	
	// Obtener datos de la baja masiva
	$baja = $objAltasBajas->obtenerDatosBajaMasiva($id_baja_masiva);

	if(!$baja){
		echo "Error: No se encontró información de la baja masiva";
		exit;
	}

	// Nombre del hotel: prioridad 1) id_hotel en la URL (enlace generado con la sesión del usuario), 2) usuario en BD, 3) sesión, 4) baja
	$nombreHotelFormato = 'VDA';
	$id_hotel_uso = null;
	if (isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) {
		$id_hotel_uso = (int)$_GET['id_hotel'];
	}
	if ($id_hotel_uso === null && isset($_SESSION['iduser'])) {
		$iduser = (int)$_SESSION['iduser'];
		$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'id_hotel'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			$ru = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT id_hotel FROM usuarios WHERE id_usuario = $iduser LIMIT 1"));
			if ($ru && !empty($ru['id_hotel'])) $id_hotel_uso = (int)$ru['id_hotel'];
		}
	}
	if ($id_hotel_uso === null && isset($_SESSION['id_hotel']) && (int)$_SESSION['id_hotel'] > 0) $id_hotel_uso = (int)$_SESSION['id_hotel'];
	if ($id_hotel_uso !== null) {
		require_once __DIR__ . "/../clases/Hoteles.php";
		$hoteles = new Hoteles();
		$nom = $hoteles->getNombre($id_hotel_uso);
		if ($nom !== '') $nombreHotelFormato = strtoupper(trim($nom));
	}
	if ($nombreHotelFormato === 'VDA' && isset($_SESSION['nombre_hotel']) && trim($_SESSION['nombre_hotel']) !== '')
		$nombreHotelFormato = strtoupper(trim($_SESSION['nombre_hotel']));
	if ($nombreHotelFormato === 'VDA' && !empty($baja['hotel_area'])) $nombreHotelFormato = strtoupper($baja['hotel_area']);

	// Obtener lista de equipos de la baja masiva
	$result_equipos = $objAltasBajas->obtenerEquiposBajaMasiva($id_baja_masiva);

	// Procesar logo (Bajas Masivas: usar LOGO-BAJA en img/, respaldo hotel.jpg)
	$logoHtml = '';
	$baseImg = __DIR__ . '/../img/';
	$rutaLogo = null;
	foreach (array('LOGO-BAJA.png', 'LOGO-BAJA.jpg', 'LOGO-BAJA.jpeg', 'hotel.jpg') as $archivo) {
		if (file_exists($baseImg . $archivo)) {
			$rutaLogo = $baseImg . $archivo;
			break;
		}
	}
	if($rutaLogo) {
		try {
			$tipoLogo = strtolower(pathinfo($rutaLogo, PATHINFO_EXTENSION));
			if ($tipoLogo === 'jpeg') $tipoLogo = 'jpg';
			$datosLogo = file_get_contents($rutaLogo);
			if($datosLogo !== false) {
				$base64Logo = base64_encode($datosLogo);
				$logoHtml = '<img src="data:image/' . $tipoLogo . ';base64,' . $base64Logo . '" alt="Logo" style="height: 70px; max-width: 180px;">';
			} else {
				$logoHtml = '<div style="height: 70px; width: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; font-size: 8px;">The Villa Group</div>';
			}
		} catch (Exception $e) {
			$logoHtml = '<div style="height: 70px; width: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; font-size: 8px;">The Villa Group</div>';
		}
	} else {
		$logoHtml = '<div style="height: 70px; width: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; font-size: 8px;">The Villa Group</div>';
	}

	// Generar tabla de equipos
	$tablaEquipos = '';
	$totalCantidad = 0;
	$totalValor = 0.0; // Inicializar como float
	$contador = 0;

	// Formato moneda ejemplo: $ 1.200,00
	$fmtMoneda = function($v) { return '$ ' . number_format(floatval($v), 2, ',', '.'); };
	while($equipo = mysqli_fetch_row($result_equipos)){
		$contador++;
		$cantidad = 1;
		$totalCantidad += $cantidad;
		$valor = floatval($equipo[7] ? $equipo[7] : 0);
		$totalValor += $valor;
		// Observación por equipo (índice 13 si existe columna bme.observaciones), si no usar cabecera
		$obsEquipo = (isset($equipo[13]) && $equipo[13] !== null && trim($equipo[13]) !== '') ? $equipo[13] : ($baja['observaciones'] ? $baja['observaciones'] : 'INSERVIBLE Y SIN APARENTE ARREGLO');
		$obsEquipo = htmlspecialchars($obsEquipo, ENT_QUOTES, 'UTF-8');
		$tablaEquipos .= '
		<tr>
			<td style="text-align: center; font-size: 8px; color: #333;">' . $cantidad . '</td>
			<td style="font-size: 8px; color: #333;">' . ($equipo[1] ? $equipo[1] : 'N/A') . '</td>
			<td style="font-size: 8px; color: #333;">' . ($equipo[2] ? $equipo[2] : 'N/A') . '</td>
			<td style="font-size: 8px; color: #333;">' . trim(($equipo[3] ? $equipo[3] : '') . ' ' . ($equipo[4] ? $equipo[4] : '') . ' ' . ($equipo[9] ? $equipo[9] : '')) . '</td>
			<td style="font-size: 8px; color: #333;">' . ($equipo[11] ? $equipo[11] : 'N/A') . '</td>
			<td style="text-align: right; font-size: 8px; color: #333;">' . $fmtMoneda($valor) . '</td>
			<td style="text-align: center; font-size: 8px; color: #333;">' . ($equipo[10] ? $equipo[10] : 'N/A') . '</td>
			<td style="text-align: right; font-size: 8px; color: #333;">' . $fmtMoneda($valor) . '</td>
			<td style="font-size: 8px; color: #333;">' . $obsEquipo . '</td>
			<td style="font-size: 8px; color: #333;">' . ($baja['destino_equipo'] ? $baja['destino_equipo'] : 'N/A') . '</td>
		</tr>';
	}

	// Formato de total como en ejemplo: $8.000,00 (coma decimal, punto miles)
	$totalFormato = '$' . number_format($totalValor, 2, ',', '.');
	// Agregar fila de totales (solo cantidad y total con borde dorado abajo)
	$tablaEquipos .= '
	<tr>
		<td style="text-align: center; font-size: 8px; font-weight: bold; border-bottom: 2px solid #c29a4b;">' . $totalCantidad . '</td>
		<td colspan="6" style="font-size: 8px; border: none; border-bottom: 1px solid #ddd;"></td>
		<td style="text-align: right; font-size: 8px; font-weight: bold; border-bottom: 2px solid #c29a4b;">' . $totalFormato . '</td>
		<td colspan="2" style="border: none; border-bottom: 1px solid #ddd;"></td>
	</tr>';

	$html = '
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="utf-8">
		<title>Formato de Baja de Equipo</title>
		<style>
			@page { size: A4 landscape; margin: 14mm 14mm 14mm 22mm; }
			body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 6px 10px 6px 12px; line-height: 1.2; color: #333; box-sizing: border-box; }
			* { box-sizing: border-box; }
			.villa-gold { color: #c29a4b; }
			.tabla-baja { width: 100%; border-collapse: collapse; font-size: 8px; table-layout: fixed; }
			.tabla-baja, .tabla-baja th, .tabla-baja td { border: 1px solid #333; }
			.tabla-baja th, .tabla-baja td { padding: 3px 4px; vertical-align: top; overflow: hidden; word-wrap: break-word; }
			.tabla-baja thead th { background-color: #c29a4b; color: #fff; font-weight: bold; text-align: center; font-size: 8px; }
			.tabla-baja tbody td { color: #333; }
			.firmas { margin-top: 35px; width: 100%; border: none; table-layout: fixed; }
			.firma-section { width: 33%; padding: 18px 6px; }
			.firma-linea { border-top: 1px solid #c29a4b; width: 100%; margin: 0 0 4px 0; }
			.firma-texto { font-size: 7px; font-weight: bold; text-align: center; margin-bottom: 0; }
			.firma-subtexto { font-size: 6px; margin-top: 0; text-align: center; }
		</style>
	</head>
	<body>
		<!-- Encabezado: logo y título al lado (título junto al logo, no en la esquina) -->
		<table style="width:100%; border:none; margin-bottom:0;">
			<tr style="border:none;">
				<td style="border:none; width:1%; white-space:nowrap; vertical-align:middle;">' . $logoHtml . '</td>
				<td style="border:none; vertical-align:middle; padding-left:20px; text-align:left;">
					<span style="font-size:18px; font-weight:bold; color:#444; text-transform:uppercase; letter-spacing:0.5px;">Formato de Baja de Equipo</span>
				</td>
			</tr>
		</table>
		<!-- Solicitante, Hotel, Fecha, Área (alineados: etiquetas y datos en columnas) -->
		<table style="width:100%; border:none; margin-top:12px; margin-bottom:10px;">
			<tr style="border:none;">
				<td style="border:none; width:50%; vertical-align:top; padding-right:20px;">
					<table style="width:100%; border:none;">
						<tr style="border:none;"><td style="border:none; width:95px; font-size:10px; color:#333; font-weight:bold; vertical-align:bottom; padding-bottom:2px;">SOLICITANTE</td><td style="border:none; border-bottom:1px solid #c29a4b; font-size:10px; color:#333; vertical-align:bottom; padding-bottom:2px;">' . ($baja['nombre_usuario'] . ' ' . $baja['apellido_usuario']) . '</td></tr>
						<tr style="border:none;"><td style="border:none; width:95px; font-size:10px; color:#333; font-weight:bold; vertical-align:bottom; padding-bottom:2px;">HOTEL</td><td style="border:none; border-bottom:1px solid #c29a4b; font-size:10px; color:#333; vertical-align:bottom; padding-bottom:2px;">' . $nombreHotelFormato . '</td></tr>
					</table>
				</td>
				<td style="border:none; width:50%; vertical-align:top; padding-left:20px;">
					<table style="width:100%; border:none;">
						<tr style="border:none;"><td style="border:none; width:55px; font-size:10px; color:#333; font-weight:bold; vertical-align:bottom; padding-bottom:2px;">FECHA</td><td style="border:none; border-bottom:1px solid #c29a4b; font-size:10px; color:#333; vertical-align:bottom; padding-bottom:2px;">' . date('d/m/Y', strtotime($baja['fecha_baja'])) . '</td></tr>
						<tr style="border:none;"><td style="border:none; width:55px; font-size:10px; color:#333; font-weight:bold; vertical-align:bottom; padding-bottom:2px;">ÁREA</td><td style="border:none; border-bottom:1px solid #c29a4b; font-size:10px; color:#333; vertical-align:bottom; padding-bottom:2px;">SISTEMAS</td></tr>
					</table>
				</td>
			</tr>
		</table>
		<div style="border-bottom: 1px solid #c29a4b; margin-bottom: 12px;"></div>

		<table class="tabla-baja">
			<thead>
			<tr>
				<th width="4%">CANTIDAD</th>
				<th width="11%">CÓDIGO</th>
				<th width="14%">DESCRIPCION DEL EQUIPO</th>
				<th width="16%">MARCA, MODELO Y No DE SERIE</th>
				<th width="14%">UBICACIÓN EXACTA DONDE SE ENCONTRABA EL EQUIPO</th>
				<th width="8%">COSTO UNITARIO</th>
				<th width="8%">TIPO DE MONEDA PESOS/USD</th>
				<th width="7%">TOTAL</th>
				<th width="12%">OBSERVACIONES</th>
				<th width="6%">DESTINO FINAL</th>
			</tr>
			</thead>
			<tbody>
			' . $tablaEquipos . '
			</tbody>
		</table>

		<!-- Firmas como tabla con las 8 firmas -->
		<table class="firmas" style="border:none;">
			<tr style="border:none;">
				<td class="firma-section" style="border:none; vertical-align:top; width:33%; padding: 25px 12px;">
					<div class="firma-linea"></div>
					<div class="firma-texto">GERENTE DE ÁREA</div>
					<div class="firma-subtexto">NOMBRE Y FIRMA</div>
				</td>
				<td class="firma-section" style="border:none; vertical-align:top; width:33%; padding: 25px 12px;">
					<div class="firma-linea"></div>
					<div class="firma-texto">VERIFICACIÓN (COSTOS)</div>
					<div class="firma-subtexto">NOMBRE Y FIRMA</div>
				</td>
				<td class="firma-section" style="border:none; vertical-align:top; width:33%; padding: 25px 12px;">
					<div class="firma-linea"></div>
					<div class="firma-texto">SUBCONTRALOR</div>
					<div class="firma-subtexto">NOMBRE Y FIRMA</div>
				</td>
			</tr>
			<tr style="border:none;">
				<td class="firma-section" style="border:none; vertical-align:top; width:33%; padding: 25px 12px;">
					<div class="firma-linea"></div>
					<div class="firma-texto">AUTORIZACIÓN</div>
					<div class="firma-texto">GERENTE GENERAL</div>
					<div class="firma-subtexto">NOMBRE Y FIRMA</div>
				</td>
				<td class="firma-section" style="border:none; vertical-align:top; width:33%; padding: 25px 12px;">
					<div class="firma-linea"></div>
					<div class="firma-texto">ENCARGADO DE INVENTARIOS</div>
					<div class="firma-subtexto">NOMBRE Y FIRMA</div>
				</td>
				<td class="firma-section" style="border:none; vertical-align:top; width:33%; padding: 25px 12px;">
					<div class="firma-linea"></div>
					<div class="firma-texto">Vo. Bo. SEGURIDAD</div>
					<div class="firma-subtexto">NOMBRE Y FIRMA</div>
				</td>
			</tr>
			<tr style="border:none;">
				<td class="firma-section" style="border:none; vertical-align:top; width:33%; padding: 25px 12px;">
					<div class="firma-linea"></div>
					<div class="firma-texto">CONTRALOR GENERAL</div>
					<div class="firma-subtexto">NOMBRE Y FIRMA</div>
				</td>
				<td class="firma-section" style="border:none; vertical-align:top; width:33%; padding: 25px 12px;">
					<!-- Celda vacía para mantener el layout -->
				</td>
				<td class="firma-section" style="border:none; vertical-align:top; width:33%; padding: 25px 12px;">
					<div class="firma-linea"></div>
					<div class="firma-texto">AUDITORÍA INTERNA</div>
					<div class="firma-subtexto">NOMBRE Y FIRMA</div>
				</td>
			</tr>
		</table>
	</body>
	</html>';

	// Configurar DOMPDF
	$options = new Options();
	$options->set('isHtml5ParserEnabled', true);
	$options->set('isPhpEnabled', true);

	$dompdf = new Dompdf($options);
	$dompdf->loadHtml($html);
	$dompdf->setPaper('A4', 'landscape');
	$dompdf->render();

	// Generar nombre del archivo
	$nombreArchivo = 'Formato_Baja_Masiva_' . $id_baja_masiva . '_' . date('Y-m-d', strtotime($baja['fecha_baja'])) . '.pdf';

	// Descargar el PDF
	$dompdf->stream($nombreArchivo, array("Attachment" => true));
?>
