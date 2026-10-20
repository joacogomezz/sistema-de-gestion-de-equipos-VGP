<?php
use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if(isset($_SESSION['usuario'])){
	require_once "../../clases/Conexion.php";
	require_once "../../librerias/dompdf/autoload.inc.php";

	$c= new conectar();
	$conexion=$c->conexion();

	// Obtener el ID de la baja desde la URL
	$id_baja = isset($_GET['id_baja']) ? $_GET['id_baja'] : 0;
	
	if($id_baja == 0){
		echo "Error: No se especificó la baja";
		exit;
	}

	// Verificar si los nuevos campos existen en la tabla
	$sql_check = "SHOW COLUMNS FROM bajas_equipos LIKE 'tecnico_reviso'";
	$result_check = mysqli_query($conexion, $sql_check);
	$campos_nuevos_existen = (mysqli_num_rows($result_check) > 0);

	$res_col = mysqli_query($conexion, "SHOW COLUMNS FROM bajas_equipos LIKE 'fecha_adquisicion'");
	$tiene_fecha_adquisicion = $res_col && mysqli_num_rows($res_col) > 0;

	// Obtener datos del equipo y la baja
	if ($campos_nuevos_existen) {
		$sql="SELECT e.codigo_equipo, e.descripcion, e.marca, e.modelo, e.estado, e.ubicacion,
						e.valor_adquisicion, e.fecha_ingreso, e.numero_serie, e.tipo_moneda,
						d.nombre as departamento,
						b.fecha_baja, b.motivo_baja, b.valor_residual, b.destino_equipo, b.observaciones,
						u.nombre as nombre_usuario, u.apellido as apellido_usuario,
						b.foto_equipo, b.foto_etiqueta,
						b.tecnico_reviso, b.gerente_area" . ($tiene_fecha_adquisicion ? ", b.fecha_adquisicion" : "") . "
				from equipos e
				left join departamentos d on e.id_departamento = d.id_departamento
				left join bajas_equipos b on e.id_equipo = b.id_equipo
				left join usuarios u on b.id_usuario = u.id_usuario
				where b.id_baja = '$id_baja' and b.tipo_baja = 'Individual'";
	} else {
		$sql="SELECT e.codigo_equipo, e.descripcion, e.marca, e.modelo, e.estado, e.ubicacion,
						e.valor_adquisicion, e.fecha_ingreso, e.numero_serie, e.tipo_moneda,
						d.nombre as departamento,
						b.fecha_baja, b.motivo_baja, b.valor_residual, b.destino_equipo, b.observaciones,
						u.nombre as nombre_usuario, u.apellido as apellido_usuario,
						b.foto_equipo, b.foto_etiqueta" . ($tiene_fecha_adquisicion ? ", b.fecha_adquisicion" : "") . "
				from equipos e
				left join departamentos d on e.id_departamento = d.id_departamento
				left join bajas_equipos b on e.id_equipo = b.id_equipo
				left join usuarios u on b.id_usuario = u.id_usuario
				where b.id_baja = '$id_baja' and b.tipo_baja = 'Individual'";
	}
	$result=mysqli_query($conexion,$sql);
	$equipo = mysqli_fetch_row($result);

	if(!$equipo){
		echo "Error: No se encontró información de baja para este equipo";
		exit;
	}

	// Nombre del hotel en el encabezado: URL (id_hotel), usuario en BD o sesión
	$nombreHotelFormato = 'Villa del Arco';
	$id_hotel_uso = null;
	if (isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) $id_hotel_uso = (int)$_GET['id_hotel'];
	if ($id_hotel_uso === null && isset($_SESSION['iduser'])) {
		$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'id_hotel'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			$iduser = (int)$_SESSION['iduser'];
			$ru = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT id_hotel FROM usuarios WHERE id_usuario = $iduser LIMIT 1"));
			if ($ru && !empty($ru['id_hotel'])) $id_hotel_uso = (int)$ru['id_hotel'];
		}
	}
	if ($id_hotel_uso === null && isset($_SESSION['id_hotel']) && (int)$_SESSION['id_hotel'] > 0) $id_hotel_uso = (int)$_SESSION['id_hotel'];
	if ($id_hotel_uso !== null) {
		require_once __DIR__ . "/../clases/Hoteles.php";
		$hoteles = new Hoteles();
		$nom = $hoteles->getNombre($id_hotel_uso);
		if ($nom !== '') $nombreHotelFormato = $nom;
	} elseif (isset($_SESSION['nombre_hotel']) && trim($_SESSION['nombre_hotel']) !== '') {
		$nombreHotelFormato = trim($_SESSION['nombre_hotel']);
	}

	$idx_fecha_adq = $tiene_fecha_adquisicion ? ($campos_nuevos_existen ? 22 : 20) : -1;
	$fecha_adquisicion_pdf = 'N/A';
	if ($idx_fecha_adq >= 0) {
		if (isset($equipo[$idx_fecha_adq]) && $equipo[$idx_fecha_adq] !== null && $equipo[$idx_fecha_adq] !== '') {
			$fecha_adquisicion_pdf = date("d/m/Y", strtotime($equipo[$idx_fecha_adq]));
		}
	} elseif (!empty($equipo[7])) {
		$fecha_adquisicion_pdf = date("d/m/Y", strtotime($equipo[7]));
	}

	// Determinar la calificación del equipo basada en el destino
	$calificacion = 'A'; // Por defecto
	switch($equipo[14]){ // destino_equipo
		case 'Chatarra':
		case 'Desecho':
			$calificacion = 'A';
			break;
		case 'Almacén':
		case 'Depósito':
			$calificacion = 'B';
			break;
		case 'Donación':
			$calificacion = 'C';
			break;
		default:
			$calificacion = 'A';
	}

	// Preparar HTML de las fotos con solución temporal para GD
	$fotoEtiquetaHtml = '';
	$fotoEquipoHtml = '';
	$logoHtml = '';
	
	// Procesar logo
	$rutaLogo = '../../img/hotel.jpg';
	if(file_exists($rutaLogo)) {
		try {
			$tipoLogo = strtolower(pathinfo($rutaLogo, PATHINFO_EXTENSION));
			$datosLogo = file_get_contents($rutaLogo);
			if($datosLogo !== false) {
				$base64Logo = base64_encode($datosLogo);
				$logoHtml = '<img src="data:image/' . $tipoLogo . ';base64,' . $base64Logo . '" alt="Logo" style="height: 70px; max-width: 180px;">';
			} else {
				$logoHtml = '<div style="height: 70px; width: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc;">Logo no disponible</div>';
			}
		} catch (Exception $e) {
			$logoHtml = '<div style="height: 70px; width: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc;">Error: ' . $e->getMessage() . '</div>';
		}
	} else {
		$logoHtml = '<div style="height: 70px; width: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc;">Logo no encontrado</div>';
	}
	
	// Verificar si GD está disponible
	if (extension_loaded('gd')) {
		// Procesar foto de etiqueta
		if($equipo[19] && !empty($equipo[19])) {
			$rutaImagen = $equipo[19];
			if(file_exists($rutaImagen)) {
				try {
					$tipoImagen = strtolower(pathinfo($rutaImagen, PATHINFO_EXTENSION));
					$datosImagen = file_get_contents($rutaImagen);
					if($datosImagen !== false) {
						$base64 = base64_encode($datosImagen);
						$fotoEtiquetaHtml = '<img src="data:image/' . $tipoImagen . ';base64,' . $base64 . '" alt="Foto de Etiqueta" style="max-width: 300px; max-height: 220px; margin: 0 10px; border: 1px solid #000;">';
					} else {
						$fotoEtiquetaHtml = '<p>Error: No se pudo leer el archivo de etiqueta</p>';
					}
				} catch (Exception $e) {
					$fotoEtiquetaHtml = '<p>Error procesando foto de etiqueta: ' . $e->getMessage() . '</p>';
				}
			} else {
				$fotoEtiquetaHtml = '<p>Archivo de etiqueta no encontrado: ' . $rutaImagen . '</p>';
			}
		} else {
			$fotoEtiquetaHtml = '<p>Sin foto de etiqueta</p>';
		}
		
		// Procesar foto del equipo
		if($equipo[18] && !empty($equipo[18])) {
			$rutaImagen = $equipo[18];
			if(file_exists($rutaImagen)) {
				try {
					$tipoImagen = strtolower(pathinfo($rutaImagen, PATHINFO_EXTENSION));
					$datosImagen = file_get_contents($rutaImagen);
					if($datosImagen !== false) {
						$base64 = base64_encode($datosImagen);
						$fotoEquipoHtml = '<img src="data:image/' . $tipoImagen . ';base64,' . $base64 . '" alt="Foto del Equipo" style="max-width: 300px; max-height: 220px; margin: 0 10px; border: 1px solid #000;">';
					} else {
						$fotoEquipoHtml = '<p>Error: No se pudo leer el archivo del equipo</p>';
					}
				} catch (Exception $e) {
					$fotoEquipoHtml = '<p>Error procesando foto del equipo: ' . $e->getMessage() . '</p>';
				}
			} else {
				$fotoEquipoHtml = '<p>Archivo del equipo no encontrado: ' . $rutaImagen . '</p>';
			}
		} else {
			$fotoEquipoHtml = '<p>Sin foto del equipo</p>';
		}
	} else {
		// Si GD no está disponible, mostrar información detallada de las fotos
		$fotoEtiquetaHtml = '<div style="border: 1px solid #ccc; padding: 10px; margin: 5px; background-color: #f9f9f9;">';
		$fotoEtiquetaHtml .= '<p><strong>Foto de Etiqueta:</strong></p>';
		if($equipo[19] && !empty($equipo[19])) {
			$rutaImagen = $equipo[19];
			if(file_exists($rutaImagen)) {
				$tamaño = filesize($rutaImagen);
				$fotoEtiquetaHtml .= '<p>✓ Archivo disponible</p>';
				$fotoEtiquetaHtml .= '<p>Ruta: ' . $equipo[19] . '</p>';
				$fotoEtiquetaHtml .= '<p>Tamaño: ' . number_format($tamaño) . ' bytes</p>';
				$fotoEtiquetaHtml .= '<p>Tipo: ' . mime_content_type($rutaImagen) . '</p>';
			} else {
				$fotoEtiquetaHtml .= '<p>✗ Archivo no encontrado</p>';
			}
		} else {
			$fotoEtiquetaHtml .= '<p>✗ No disponible</p>';
		}
		$fotoEtiquetaHtml .= '</div>';
		
		$fotoEquipoHtml = '<div style="border: 1px solid #ccc; padding: 10px; margin: 5px; background-color: #f9f9f9;">';
		$fotoEquipoHtml .= '<p><strong>Foto del Equipo:</strong></p>';
		if($equipo[18] && !empty($equipo[18])) {
			$rutaImagen = $equipo[18];
			if(file_exists($rutaImagen)) {
				$tamaño = filesize($rutaImagen);
				$fotoEquipoHtml .= '<p>✓ Archivo disponible</p>';
				$fotoEquipoHtml .= '<p>Ruta: ' . $equipo[18] . '</p>';
				$fotoEquipoHtml .= '<p>Tamaño: ' . number_format($tamaño) . ' bytes</p>';
				$fotoEquipoHtml .= '<p>Tipo: ' . mime_content_type($rutaImagen) . '</p>';
			} else {
				$fotoEquipoHtml .= '<p>✗ Archivo no encontrado</p>';
			}
		} else {
			$fotoEquipoHtml .= '<p>✗ No disponible</p>';
		}
		$fotoEquipoHtml .= '</div>';
	}

	$html = '
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="utf-8">
		<title>Informe Técnico de Baja de Equipo</title>
		<style>
			body { font-family: Arial, sans-serif; font-size: 11px; margin: 12px; }
			.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
			.logo { font-size: 16px; font-weight: bold; color: #333; }
			.titulo { text-align: center; font-size: 17px; font-weight: bold; margin: 8px 0; text-transform: uppercase; }
			table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
			th, td { border: 1px solid #000; padding: 4px; text-align: left; font-size: 11px; }
			.dictamen { margin-bottom: 6px; }
			.dictamen strong { text-decoration: underline; }
			.fotos { text-align: center; margin: 18px 0 10px 0; }
			.fotos img { max-width: 300px; max-height: 220px; margin: 0 10px; border: 1px solid #000; }
			.firmas { width: 100%; text-align: center; margin-top: 6px; }
			.firmas td { border: none; padding: 8px 40px; }
			.linea-firma { border-top: 1px solid #000; margin-top: 0; }
			.firma-usuario { text-align: center; margin-top: 46px; }
			h4 { font-size: 12px; font-weight: bold; margin: 8px 0 4px 0; }
		</style>
	</head>
	<body>
		<div class="header">
			<div class="logo">
				' . $logoHtml . '
			</div>
			<div style="text-align:right;">
				Departamento de sistemas<br>' . htmlspecialchars($nombreHotelFormato, ENT_QUOTES, 'UTF-8') . '
			</div>
		</div>

		<div class="titulo">INFORME TÉCNICO DE BAJA DE EQUIPO</div>

		<h4>1.- Equipo revisado:</h4>
		<table>
			<tr>
				<th>Artículo:</th>
				<td>' . ($equipo[1] ? $equipo[1] : 'N/A') . '</td>
				<th>Marca y modelo:</th>
				<td>' . ($equipo[2] ? $equipo[2] : '') . ' ' . ($equipo[3] ? $equipo[3] : '') . '</td>
			</tr>
			<tr>
				<th>Núm. serie:</th>
				<td>' . ($equipo[8] ? $equipo[8] : 'N/A') . '</td>
				<th>Núm. Inventario:</th>
				<td>' . ($equipo[0] ? $equipo[0] : 'N/A') . '</td>
			</tr>
			<tr>
				<th>Ubicación:</th>
				<td>' . ($equipo[10] ? $equipo[10] : 'SISTEMAS') . '</td>
				<th>Adquisición:</th>
				<td>' . $fecha_adquisicion_pdf . '</td>
			</tr>
		</table>

		<h4>2.- Dictamen técnico. Calificación del equipo: (' . $calificacion . ')</h4>
		<div class="dictamen">
			A: Baja como residuo para desecho (<strong>se extrae lo funcional</strong>).<br>
			B: Baja para depósito y aprovechamiento futuro de piezas o componentes.<br>
			C: Baja para donación a otras instituciones.
		</div>

		<div style="margin-top: 58px;"></div>

		<div class="fotos">
			' . $fotoEtiquetaHtml . '
			' . $fotoEquipoHtml . '
		</div>

		<h4>3.- Motivo:</h4>
		<p>' . ($equipo[15] ? $equipo[15] : "Sin especificar") . '</p>

		<div style="margin-top: 60px;"></div>

		<table class="firmas">
			<tr>
				<td style="text-align: center;">
					<div class="linea-firma"></div>
					<div style="margin-top: 5px; font-weight: bold; text-align: center;">Técnico Revisó</div>
					<div style="margin-top: 10px; text-align: center;">' . ($campos_nuevos_existen && isset($equipo[20]) && $equipo[20] ? $equipo[20] : 'CHRISTIAN MONDRAGON') . '</div>
				</td>
				<td style="text-align: center;">
					<div class="linea-firma"></div>
					<div style="margin-top: 5px; font-weight: bold; text-align: center;">VoBo Gerente de Área</div>
					<div style="margin-top: 10px; text-align: center;">' . ($campos_nuevos_existen && isset($equipo[21]) && $equipo[21] ? $equipo[21] : 'EDUARDO CRUZ MATEOS') . '</div>
				</td>
			</tr>
		</table>

		<div style="margin-top: 46px;"></div>

		<div class="firma-usuario">
			<div class="linea-firma" style="width:250px; margin: 0 auto;"></div>
			<div style="margin-top: 5px; font-weight: bold; text-align: center;">Firma de Usuario:</div>
			<div style="margin-top: 10px; text-align: center;">Nombre y Firma</div>
		</div>
	</body>
	</html>';

	// Configurar DOMPDF
	$options = new Options();
	$options->set('isHtml5ParserEnabled', true);
	$options->set('isPhpEnabled', true);

	$dompdf = new Dompdf($options);
	$dompdf->loadHtml($html);
	$dompdf->setPaper('A4', 'portrait');
	$dompdf->render();

	// Generar nombre del archivo
	$nombreArchivo = 'Informe_Baja_' . $equipo[0] . '_' . date('Y-m-d', strtotime($equipo[12])) . '.pdf';

	// Descargar el PDF
	$dompdf->stream($nombreArchivo, array("Attachment" => true));
} else {
	echo "Error: No hay sesión activa";
}
?>
