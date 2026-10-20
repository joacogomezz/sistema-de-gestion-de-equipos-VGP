<?php
require_once "../../seguridad.php";
requerirAutenticacion();
require_once "../../clases/Conexion.php";
require_once "../../clases/Equipos.php";
require_once "../../clases/Departamentos.php";

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['iduser'])) {
	echo json_encode(array('ok' => false, 'mensaje' => 'Sesion no valida.'));
	exit;
}

if (!isset($_FILES['archivoEquipos']) || $_FILES['archivoEquipos']['error'] !== 0) {
	echo json_encode(array('ok' => false, 'mensaje' => 'Debes seleccionar un archivo CSV.'));
	exit;
}

$nombre = isset($_FILES['archivoEquipos']['name']) ? strtolower($_FILES['archivoEquipos']['name']) : '';
$ext = pathinfo($nombre, PATHINFO_EXTENSION);
if ($ext !== 'csv') {
	echo json_encode(array('ok' => false, 'mensaje' => 'Formato no valido. Usa un archivo CSV (guardado desde Excel).'));
	exit;
}

$tmp = $_FILES['archivoEquipos']['tmp_name'];
$fh = @fopen($tmp, 'r');
if (!$fh) {
	echo json_encode(array('ok' => false, 'mensaje' => 'No se pudo leer el archivo.'));
	exit;
}

$primeraLineaCruda = fgets($fh);
if ($primeraLineaCruda === false) {
	fclose($fh);
	echo json_encode(array('ok' => false, 'mensaje' => 'El archivo CSV esta vacio.'));
	exit;
}
$delimiter = (substr_count($primeraLineaCruda, ';') > substr_count($primeraLineaCruda, ',')) ? ';' : ',';
rewind($fh);

function normalizar_clave($s) {
	$s = trim((string)$s);
	$s = strtolower($s);
	$s = str_replace(array('á','é','í','ó','ú','ñ'), array('a','e','i','o','u','n'), $s);
	$s = preg_replace('/[^a-z0-9]+/', '_', $s);
	$s = trim($s, '_');
	return $s;
}

function buscar_valor_col($fila, $headers, $candidatos) {
	foreach ($candidatos as $cand) {
		if (isset($headers[$cand])) {
			$idx = $headers[$cand];
			return isset($fila[$idx]) ? trim((string)$fila[$idx]) : '';
		}
	}
	return '';
}

function resolver_departamento($conexion, $valorDepartamento, $idHotel = null) {
	$valorDepartamento = trim((string)$valorDepartamento);
	if ($valorDepartamento === '' || strtolower($valorDepartamento) === 'null') {
		return null;
	}
	if (ctype_digit($valorDepartamento)) {
		return (int)$valorDepartamento;
	}

	$valorEsc = mysqli_real_escape_string($conexion, $valorDepartamento);
	$whereHotel = '';
	$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
	if ($chk && mysqli_num_rows($chk) > 0 && $idHotel !== null) {
		$whereHotel = " AND id_hotel='" . intval($idHotel) . "'";
	}
	$sql = "SELECT id_departamento FROM departamentos WHERE nombre='$valorEsc' $whereHotel LIMIT 1";
	$res = mysqli_query($conexion, $sql);
	if ($res && $row = mysqli_fetch_row($res)) {
		return (int)$row[0];
	}
	return null;
}

$c = new conectar();
$conexion = $c->conexion();
$objEquipos = new equipos();
$objEquipos->ensureRegistroEquiposCols();

$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$id_hotel_sesion = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : null);

if ($id_hotel_sesion !== null && $id_hotel_sesion > 0) {
	$objDept = new departamentos();
	$totalDeptHotel = $objDept->contarDepartamentosPorHotel($id_hotel_sesion);
	if ($totalDeptHotel === 0) {
		fclose($fh);
		echo json_encode(array(
			'ok' => false,
			'codigo' => 'SIN_DEPARTAMENTOS_HOTEL',
			'mensaje' => 'No puede importar equipos: su hotel no tiene departamentos registrados. Registre primero sus departamentos en el menú Departamentos y vuelva a importar el CSV.',
			'url_departamentos' => '../../vistas/departamentos.php'
		));
		exit;
	}
}

$primera = fgetcsv($fh, 0, $delimiter);
if (!$primera || count($primera) === 0) {
	fclose($fh);
	echo json_encode(array('ok' => false, 'mensaje' => 'El archivo CSV esta vacio.'));
	exit;
}

$headers = array();
foreach ($primera as $i => $h) {
	$headers[normalizar_clave($h)] = $i;
}

$filaNum = 1;
$insertados = 0;
$errores = array();
$seriesEnArchivo = array();

while (($fila = fgetcsv($fh, 0, $delimiter)) !== false) {
	$filaNum++;
	if (count($fila) === 1 && trim((string)$fila[0]) === '') {
		continue;
	}

	$codigo = buscar_valor_col($fila, $headers, array('codigo', 'codigo_equipo'));
	$descripcion = buscar_valor_col($fila, $headers, array('descripcion', 'descripcion_equipo'));
	$marca = buscar_valor_col($fila, $headers, array('marca'));
	$modelo = buscar_valor_col($fila, $headers, array('modelo'));
	$numeroSerie = buscar_valor_col($fila, $headers, array('numero_serie', 'n_serie', 'serie'));
	$estado = buscar_valor_col($fila, $headers, array('estado'));
	$departamentoRaw = buscar_valor_col($fila, $headers, array('id_departamento', 'departamento'));
	$ubicacionFisica = buscar_valor_col($fila, $headers, array('ubicacion_fisica', 'ubicacion'));
	$fechaIngreso = buscar_valor_col($fila, $headers, array('fecha_ingreso'));
	$valor = buscar_valor_col($fila, $headers, array('valor', 'valor_adquisicion', 'costo'));
	$tipoMoneda = buscar_valor_col($fila, $headers, array('tipo_moneda', 'moneda'));
	$usuarioReg = buscar_valor_col($fila, $headers, array('usuario', 'usuario_registro', 'usuario_equipo'));
	$numFactura = buscar_valor_col($fila, $headers, array('numero_factura', 'no_factura', 'factura', 'n_factura'));

	// Defaults para campos faltantes (permitir carga parcial)
	if ($descripcion === '') $descripcion = 'NULL';
	if ($marca === '') $marca = 'NULL';
	if ($numeroSerie === '') $numeroSerie = 'NULL';
	if ($estado === '') $estado = 'Activo';
	if ($tipoMoneda === '') $tipoMoneda = 'MXN';

	if ($departamentoRaw === '' || strtolower(trim($departamentoRaw)) === 'null') {
		$errores[] = "Fila $filaNum: la columna departamento es obligatoria. Indique el nombre o id_departamento registrado en su hotel.";
		continue;
	}

	$idDepartamento = resolver_departamento($conexion, $departamentoRaw, $id_hotel_sesion);
	if ($idDepartamento === null) {
		if ($id_hotel_sesion !== null) {
			$errores[] = "Fila $filaNum: departamento no encontrado para este hotel («$departamentoRaw»). Regístrelo en Departamentos o corrija el nombre en el CSV.";
		} else {
			$errores[] = "Fila $filaNum: departamento no encontrado («$departamentoRaw»).";
		}
		continue;
	}

	// Validar duplicado de número de serie (solo serie real, mismo hotel)
	$serieNorm = strtoupper(trim((string)$numeroSerie));
	if ($objEquipos->esNumeroSerieSignificativo($numeroSerie)) {
		if (isset($seriesEnArchivo[$serieNorm])) {
			$errores[] = "Fila $filaNum: número de serie duplicado en archivo ($numeroSerie). También aparece en fila " . $seriesEnArchivo[$serieNorm] . ".";
			continue;
		}
		$seriesEnArchivo[$serieNorm] = $filaNum;
		$codigoDup = $id_hotel_sesion !== null
			? $objEquipos->obtenerCodigoEquipoPorSerieEnHotel($numeroSerie, $id_hotel_sesion)
			: null;
		if ($codigoDup !== null) {
			$hotelTxt = $id_hotel_sesion !== null ? " en su hotel" : "";
			$errores[] = "Fila $filaNum: número de serie duplicado$hotelTxt ($numeroSerie). Ya existe en equipo " . ($codigoDup ?: 'sin código') . ".";
			continue;
		}
	}

	$datos = array(
		$_SESSION['iduser'],   // 0
		$idDepartamento,       // 1
		$codigo,               // 2 (si viene vacio, se autogenera)
		$descripcion,          // 3
		$marca,                // 4
		$modelo,               // 5
		$numeroSerie,          // 6
		$estado,               // 7
		$ubicacionFisica,      // 8 ubicacion fisica
		$fechaIngreso,         // 9
		$valor,                // 10
		$tipoMoneda,           // 11
		null,                  // 12 foto
		$usuarioReg,           // 13
		$numFactura,           // 14
		$id_hotel_sesion        // 15 id_hotel (carga masiva sin depto o refuerzo)
	);

	$r = $objEquipos->insertaEquipo($datos);
	if ($r === 'DUPLICATE_SERIE') {
		$errores[] = "Fila $filaNum: número de serie duplicado ($numeroSerie).";
	} elseif (in_array($r, array('SIN_DEPARTAMENTO', 'DEPARTAMENTO_NO_EXISTE', 'DEPARTAMENTO_OTRO_HOTEL'), true)) {
		$errores[] = "Fila $filaNum: " . $objEquipos->mensajeErrorDepartamentoEquipo($r);
	} elseif ((string)$r === '1') {
		$insertados++;
	} else {
		$errores[] = "Fila $filaNum: no se pudo insertar (verifica duplicados o formato).";
	}
}
fclose($fh);

$mensaje = "Importacion finalizada. Insertados: $insertados. Errores: " . count($errores) . ".";
echo json_encode(array(
	'ok' => true,
	'mensaje' => $mensaje,
	'insertados' => $insertados,
	'errores' => $errores
));
?>
