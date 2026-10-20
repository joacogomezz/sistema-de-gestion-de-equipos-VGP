<?php
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../config.php";
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Equipos.php";

	function obtenerUrlFotoEquipo($rutaRelativa) {
		$rutaRelativa = $rutaRelativa ?? '';
		if(trim($rutaRelativa) === '') return '';
		$nombreArchivo = basename($rutaRelativa);
		if (defined('RUTA_BASE') && RUTA_BASE !== '') {
			$rutaBase = rtrim(RUTA_BASE, '/') . '/';
		} else {
			$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
			if (strpos($scriptName, '/ventasAlmacen22/') !== false) { $rutaBase = '/ventasAlmacen22/'; }
			elseif (strpos($scriptName, '/ventasAlmacen/') !== false) { $rutaBase = '/ventasAlmacen/'; }
			else { $rutaBase = '/'; }
		}
		// Comprobar si el archivo existe (ruta relativa a este script: procesos/equipos/ -> ../../archivos/equipos/)
		$rutaFisicaRelativa = realpath(__DIR__ . '/../../archivos/equipos/' . $nombreArchivo);
		if($rutaFisicaRelativa && file_exists($rutaFisicaRelativa) && is_readable($rutaFisicaRelativa))
			return $rutaBase . 'archivos/equipos/' . $nombreArchivo;
		// Probar rutas absolutas desde DOCUMENT_ROOT
		$candidatos = array(
			$rutaBase . $rutaRelativa,
			$rutaBase . 'archivos/equipos/' . $nombreArchivo,
			$rutaBase . ltrim($rutaRelativa, '/'),
			'/archivos/equipos/' . $nombreArchivo
		);
		foreach($candidatos as $ruta) {
			$rutaFisica = (strpos($ruta, '/') === 0) ? ($_SERVER['DOCUMENT_ROOT'] . $ruta) : (__DIR__ . '/../../' . $ruta);
			if(file_exists($rutaFisica) && is_readable($rutaFisica))
				return (strpos($ruta, '/') === 0) ? $ruta : ($rutaBase . ltrim($rutaRelativa, '/'));
		}
		return $rutaBase . 'archivos/equipos/' . $nombreArchivo;
	}

	$obj= new equipos();

	if(!isset($_POST['idEquipo']) || empty($_POST['idEquipo'])){
		header('Content-Type: text/plain; charset=utf-8');
		echo '0';
		exit;
	}

	$idEquipo = $_POST['idEquipo'];
	$datos = $obj->obtenDatosEquipo($idEquipo);
	
	if($datos){
		// Normalizar fecha para input type="date" (YYYY-MM-DD)
		if (isset($datos['fecha_ingreso']) && trim((string)$datos['fecha_ingreso']) !== '') {
			$f = trim((string)$datos['fecha_ingreso']);
			if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $f)) {
				$partes = explode('/', $f);
				$datos['fecha_ingreso'] = $partes[2] . '-' . $partes[1] . '-' . $partes[0];
			}
		}
		// Normalizar valor para input number
		if (isset($datos['valor_adquisicion'])) {
			$v = str_replace(',', '', (string)$datos['valor_adquisicion']);
			$datos['valor_adquisicion'] = is_numeric($v) ? $v : '0';
		}
		$fe = isset($datos['foto_equipo']) ? trim((string)$datos['foto_equipo']) : '';
		if ($fe === '' || strtolower($fe) === 'null') {
			$fe = '';
		}
		$datos['foto_equipo'] = $fe;
		$datos['foto_equipo_url'] = obtenerUrlFotoEquipo($fe);

		$c = new conectar();
		$cx = $c->conexion();
		$id = (int)$datos['id_equipo'];
		$datos['nombre_departamento'] = '';
		if (!empty($datos['id_departamento'])) {
			$iddep = (int)$datos['id_departamento'];
			$rd = mysqli_query($cx, "SELECT nombre FROM departamentos WHERE id_departamento = '$iddep' LIMIT 1");
			if ($rd && $rowd = mysqli_fetch_row($rd)) {
				$datos['nombre_departamento'] = (string)$rowd[0];
			}
		}
		$datos['id_hotel'] = null;
		$datos['numero_equipo_hotel'] = null;
		$datos['nombre_hotel'] = '';
		$chkH = @mysqli_query($cx, "SHOW COLUMNS FROM equipos LIKE 'id_hotel'");
		if ($chkH && mysqli_num_rows($chkH) > 0) {
			$rh = mysqli_fetch_assoc(mysqli_query($cx, "SELECT id_hotel, numero_equipo_hotel FROM equipos WHERE id_equipo = '$id' LIMIT 1"));
			if ($rh) {
				$datos['id_hotel'] = isset($rh['id_hotel']) ? $rh['id_hotel'] : null;
				$datos['numero_equipo_hotel'] = isset($rh['numero_equipo_hotel']) ? $rh['numero_equipo_hotel'] : null;
				if (!empty($datos['id_hotel'])) {
					$ih = (int)$datos['id_hotel'];
					$rn = mysqli_query($cx, "SELECT nombre FROM hoteles WHERE id_hotel = '$ih' LIMIT 1");
					if ($rn && $rown = mysqli_fetch_row($rn)) {
						$datos['nombre_hotel'] = (string)$rown[0];
					}
				}
			}
		}
		$pdfRes = __DIR__ . '/../../archivos/resguardos_firmados/equipo_' . $id . '.pdf';
		$datos['tiene_resguardo_firmado'] = file_exists($pdfRes);
		$datos['url_resguardo_firmado'] = $datos['tiene_resguardo_firmado']
			? ('../archivos/resguardos_firmados/equipo_' . $id . '.pdf?v=' . (int)filemtime($pdfRes))
			: '';
		$datos['tiene_factura_escaneada'] = false;
		$datos['url_factura_escaneada'] = '';
		$datos['nombre_archivo_factura'] = '';
		$extsF = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp');
		$baseF = __DIR__ . '/../../archivos/facturas_equipos/';
		foreach ($extsF as $ex) {
			$fp = $baseF . 'equipo_' . $id . '.' . $ex;
			if (file_exists($fp) && is_file($fp)) {
				$datos['tiene_factura_escaneada'] = true;
				$datos['url_factura_escaneada'] = '../archivos/facturas_equipos/equipo_' . $id . '.' . $ex . '?v=' . (int)filemtime($fp);
				$datos['nombre_archivo_factura'] = 'equipo_' . $id . '.' . $ex;
				break;
			}
		}

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($datos);
		exit;
	}else{
		header('Content-Type: text/plain; charset=utf-8');
		echo '0';
		exit;
	}
