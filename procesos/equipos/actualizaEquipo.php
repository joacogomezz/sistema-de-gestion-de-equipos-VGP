<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Equipos.php";

	error_reporting(E_ALL);
	ini_set('display_errors', 0);

	$obj= new equipos();
	$obj->ensureRegistroEquiposCols();

	// Verificar que la sesión esté activa
	if(!isset($_SESSION['iduser'])){
		error_log("Error: No hay sesión de usuario activa");
		echo 0;
		exit;
	}

	// Solo idEquipo es obligatorio. El resto puede venir vacío y se toma del valor actual.
	if(!isset($_POST['idEquipo']) || trim((string)$_POST['idEquipo']) === ''){
		error_log("Error: idEquipo faltante");
		echo 0;
		exit;
	}
	$idEquipo = trim((string)$_POST['idEquipo']);

	$cTmp = new conectar();
	$conTmp = $cTmp->conexion();
	$idEqEsc = mysqli_real_escape_string($conTmp, $idEquipo);
	$resActual = mysqli_query($conTmp, "SELECT codigo_equipo, descripcion, marca, modelo, numero_serie, estado, id_departamento, ubicacion, fecha_ingreso, valor_adquisicion, tipo_moneda, foto_equipo, usuario_registro, numero_factura FROM equipos WHERE id_equipo='$idEqEsc' LIMIT 1");
	if(!$resActual || mysqli_num_rows($resActual) === 0){
		error_log("Error: equipo no encontrado");
		echo 0;
		exit;
	}
	$actual = mysqli_fetch_assoc($resActual);

	$take = function($key, $fallback){
		if(!isset($_POST[$key])) return $fallback;
		$v = trim((string)$_POST[$key]);
		return ($v === '') ? $fallback : $v;
	};

	$codigoEquipo = $take('codigoEquipo', $actual['codigo_equipo']);
	$descripcionEquipo = $take('descripcionEquipo', $actual['descripcion']);
	$marcaEquipo = $take('marcaEquipo', $actual['marca']);
	$modeloEquipo = $take('modeloEquipo', $actual['modelo']);
	$numeroSerie = $take('numeroSerie', $actual['numero_serie']);
	$estadoEquipo = $take('estadoEquipo', $actual['estado']);
	$ubicacionEquipo = $take('ubicacionEquipo', (string)$actual['id_departamento']);
	$ubicacionFisica = $take('ubicacionFisica', isset($actual['ubicacion']) ? (string)$actual['ubicacion'] : '');
	$tipoMoneda = $take('tipoMoneda', $actual['tipo_moneda']);
	// Permitir dejar vacíos (borrar texto); $take() no sirve porque convierte '' en valor anterior
	if (array_key_exists('usuarioRegistroEquipo', $_POST)) {
		$usuarioRegistroEquipo = trim((string)$_POST['usuarioRegistroEquipo']);
	} else {
		$usuarioRegistroEquipo = isset($actual['usuario_registro']) ? (string)$actual['usuario_registro'] : '';
	}
	if (array_key_exists('numeroFacturaEquipo', $_POST)) {
		$numeroFacturaEquipo = trim((string)$_POST['numeroFacturaEquipo']);
	} else {
		$numeroFacturaEquipo = isset($actual['numero_factura']) ? (string)$actual['numero_factura'] : '';
	}

	// Asegurar valores válidos para ENUMs
	$estadosPermitidos = array('Activo', 'En Mantenimiento', 'En Reparación', 'Dado de Baja');
	if (!in_array($estadoEquipo, $estadosPermitidos, true)) {
		$estadoEquipo = 'Activo';
	}
	$monedasPermitidas = array('USD', 'MXN', 'EUR');
	if (!in_array($tipoMoneda, $monedasPermitidas, true)) {
		$tipoMoneda = 'MXN';
	}

	// Normalizar fecha (acepta YYYY-MM-DD o DD/MM/YYYY)
	$fechaIngreso = $take('fechaIngreso', $actual['fecha_ingreso']);
	if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaIngreso)) {
		$partesFecha = explode('/', $fechaIngreso);
		$fechaIngreso = $partesFecha[2] . '-' . $partesFecha[1] . '-' . $partesFecha[0];
	}
	if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaIngreso)) {
		$fechaIngreso = (!empty($actual['fecha_ingreso']) ? $actual['fecha_ingreso'] : date('Y-m-d'));
	}

	// Normalizar valor numérico
	$valorEquipo = str_replace(',', '', $take('valorEquipo', (string)$actual['valor_adquisicion']));
	if (!is_numeric($valorEquipo)) {
		$valorActual = str_replace(',', '', (string)$actual['valor_adquisicion']);
		$valorEquipo = is_numeric($valorActual) ? $valorActual : '0';
	}

	// Procesar imagen: si se subió una nueva, guardarla; si no, mantener la actual
	$rutaFoto = isset($_POST['fotoEquipoActual']) ? trim((string)$_POST['fotoEquipoActual']) : '';
	$lrFoto = strtolower($rutaFoto);
	if ($rutaFoto === '' || $lrFoto === 'null') {
		$rutaFoto = '';
	}
	$fotoAnteriorBd = isset($actual['foto_equipo']) ? trim((string)$actual['foto_equipo']) : '';
	if ($fotoAnteriorBd !== '' && strtolower($fotoAnteriorBd) === 'null') {
		$fotoAnteriorBd = '';
	}

	if (!empty($_FILES['imagenEquipo']) && isset($_FILES['imagenEquipo']['error'])) {
		$upErr = (int)$_FILES['imagenEquipo']['error'];
		if ($upErr === UPLOAD_ERR_OK) {
			$tmp = $_FILES['imagenEquipo']['tmp_name'] ?? '';
			if (!is_uploaded_file($tmp)) {
				echo 'No se recibió el archivo de imagen correctamente. Vuelve a elegir la foto e intenta de nuevo.';
				exit;
			}
			$nombreOriginal = basename((string)($_FILES['imagenEquipo']['name'] ?? 'foto.jpg'));
			$ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
			$permitidas = array('jpg', 'jpeg', 'png', 'gif', 'webp');
			if ($ext === 'jpeg') {
				$ext = 'jpg';
			}
			if (!in_array($ext, $permitidas, true)) {
				$mime = isset($_FILES['imagenEquipo']['type']) ? (string)$_FILES['imagenEquipo']['type'] : '';
				if (strpos($mime, 'png') !== false) {
					$ext = 'png';
				} elseif (strpos($mime, 'gif') !== false) {
					$ext = 'gif';
				} elseif (strpos($mime, 'webp') !== false) {
					$ext = 'webp';
				} else {
					$ext = 'jpg';
				}
			}
			$nombreSeguro = 'eq_' . (int)$idEquipo . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
			$carpetaRel = __DIR__ . '/../../archivos/equipos/';
			if (!is_dir($carpetaRel)) {
				mkdir($carpetaRel, 0777, true);
			}
			$rutaDestino = $carpetaRel . $nombreSeguro;
			if (!move_uploaded_file($tmp, $rutaDestino)) {
				error_log('actualizaEquipo: move_uploaded_file falló hacia ' . $rutaDestino);
				echo 'No se pudo guardar la imagen (permisos o disco). Revisa la carpeta archivos/equipos.';
				exit;
			}
			$rutaFoto = 'archivos/equipos/' . $nombreSeguro;
			// Quitar foto anterior del disco solo si era un archivo propio bajo archivos/equipos
			if ($fotoAnteriorBd !== '' && $fotoAnteriorBd !== $rutaFoto) {
				$baseAnt = basename(str_replace('\\', '/', $fotoAnteriorBd));
				if ($baseAnt !== '' && $baseAnt !== '.' && $baseAnt !== '..') {
					$antAbs = realpath(__DIR__ . '/../../archivos/equipos/' . $baseAnt);
					$dirEq = realpath(__DIR__ . '/../../archivos/equipos');
					if ($antAbs && $dirEq && strpos($antAbs, $dirEq) === 0 && is_file($antAbs) && basename($antAbs) !== $nombreSeguro) {
						@unlink($antAbs);
					}
				}
			}
		} elseif ($upErr !== UPLOAD_ERR_NO_FILE) {
			error_log('actualizaEquipo: error subida imagen código ' . $upErr);
			echo 'Error al subir la imagen (código ' . $upErr . '). Si el archivo es grande, revisa upload_max_filesize en PHP.';
			exit;
		}
	}

	// Crear array de datos en el orden correcto para la clase Equipos
	$datos=array(
		$idEquipo,                    // 0 - id_equipo
		$ubicacionEquipo,             // 1 - id_departamento
		$codigoEquipo,                // 2 - codigo_equipo
		$descripcionEquipo,           // 3 - descripcion
		$marcaEquipo,                 // 4 - marca
		$modeloEquipo,                // 5 - modelo (opcional)
		$numeroSerie,                 // 6 - numero_serie
		$estadoEquipo,                // 7 - estado
		$ubicacionFisica,             // 8 - ubicacion (texto físico)
		$fechaIngreso,                // 9 - fecha_ingreso
		$valorEquipo,                 // 10 - valor_adquisicion
		$tipoMoneda,                  // 11 - tipo_moneda
		$rutaFoto,                    // 12 - foto_equipo
		$usuarioRegistroEquipo,       // 13
		$numeroFacturaEquipo          // 14
	);

	// Verificar si el código ya existe en otro equipo
	$c = new conectar();
	$conexion = $c->conexion();
	$sql_check = "SELECT id_equipo FROM equipos WHERE codigo_equipo = '" . mysqli_real_escape_string($conexion, $codigoEquipo) . "' AND id_equipo != '" . mysqli_real_escape_string($conexion, $idEquipo) . "'";
	$result_check = mysqli_query($conexion, $sql_check);
	
	if(mysqli_num_rows($result_check) > 0) {
		error_log("Error: El código de equipo ya existe en otro registro");
		echo "El código de equipo ya existe en otro registro";
		exit;
	}

	// Validar número de serie duplicado en actualización
	$serieEsc = mysqli_real_escape_string($conexion, $numeroSerie);
	if ($numeroSerie !== '' && strtolower($numeroSerie) !== 'null') {
		$sql_serie = "SELECT codigo_equipo FROM equipos WHERE numero_serie = '$serieEsc' AND id_equipo != '" . mysqli_real_escape_string($conexion, $idEquipo) . "' LIMIT 1";
		$res_serie = mysqli_query($conexion, $sql_serie);
		if ($res_serie && ($dup = mysqli_fetch_row($res_serie))) {
			echo "Número de serie duplicado: $numeroSerie (ya existe en equipo " . ($dup[0] ?: 'sin código') . ")";
			exit;
		}
	}

	// Log para debugging
	error_log("=== Actualización de equipo ===");
	error_log("Datos a actualizar: " . print_r($datos, true));

	$resultado = $obj->actualizaEquipo($datos);
	
	if($resultado){
		error_log("Equipo actualizado exitosamente");
		header('Content-Type: text/plain; charset=utf-8');
		echo '1';
		exit;
	}
	error_log("Error al actualizar equipo");
	header('Content-Type: text/plain; charset=utf-8');
	echo '0';
