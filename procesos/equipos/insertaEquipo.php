<?php 
	// Incluir archivo de seguridad
	require_once "../../seguridad.php";
	
	// Requerir autenticación
	requerirAutenticacion();
	
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Equipos.php";
	require_once "../../clases/Departamentos.php";

	// Habilitar reporte de errores para debugging
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	// Log para debugging
	error_log("=== Inicio de inserción de equipo ===");
	error_log("POST data: " . print_r($_POST, true));
	error_log("FILES data: " . print_r($_FILES, true));

	$obj= new equipos();

	// Verificar que la sesión esté activa
	if(!isset($_SESSION['iduser'])){
		error_log("Error: No hay sesión de usuario activa");
		echo 0;
		exit;
	}

	// Verificar que el usuario existe en la base de datos
	$c = new conectar();
	$conexion = $c->conexion();
	$sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = '" . $_SESSION['iduser'] . "'";
	$result = mysqli_query($conexion, $sql);
	
	if(!$result || mysqli_num_rows($result) == 0) {
		error_log("Error: Usuario con ID " . $_SESSION['iduser'] . " no existe en la BD");
		// Usar usuario ID 1 como fallback
		$_SESSION['iduser'] = '1';
		error_log("Usando usuario ID 1 como fallback");
	}

	// Verificar que se recibieron todos los datos necesarios
	$campos_requeridos = ['descripcionEquipo', 'marcaEquipo', 
						 'numeroSerie', 'estadoEquipo', 'ubicacionEquipo', 'ubicacionFisica', 'fechaIngreso', 
						 'valorEquipo', 'tipoMoneda'];
	
	foreach($campos_requeridos as $campo){
		if(!isset($_POST[$campo]) || empty($_POST[$campo])){
			error_log("Error: Campo requerido faltante: " . $campo);
			echo 0;
			exit;
		}
	}

	// Procesar imagen si se subió (nombre único para evitar colisiones y fallos al sobrescribir)
	$rutaFoto = null;
	if (!empty($_FILES['imagenEquipo']) && isset($_FILES['imagenEquipo']['error']) && (int)$_FILES['imagenEquipo']['error'] === UPLOAD_ERR_OK) {
		$tmp = $_FILES['imagenEquipo']['tmp_name'] ?? '';
		if (is_uploaded_file($tmp)) {
			$nombreOriginal = basename((string)($_FILES['imagenEquipo']['name'] ?? 'foto.jpg'));
			$ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
			if ($ext === 'jpeg') {
				$ext = 'jpg';
			}
			$permitidas = array('jpg', 'jpeg', 'png', 'gif', 'webp');
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
			$uid = isset($_SESSION['iduser']) ? (int)$_SESSION['iduser'] : 0;
			$nombreSeguro = 'eq_n_' . $uid . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
			$carpetaRel = __DIR__ . '/../../archivos/equipos/';
			if (!is_dir($carpetaRel)) {
				mkdir($carpetaRel, 0777, true);
			}
			$rutaFinal = $carpetaRel . $nombreSeguro;
			if (move_uploaded_file($tmp, $rutaFinal)) {
				$rutaFoto = 'archivos/equipos/' . $nombreSeguro;
			} else {
				error_log('insertaEquipo: move_uploaded_file falló hacia ' . $rutaFinal);
			}
		}
	}

	$id_hotel_sesion = (isset($_SESSION['id_hotel']) && (int)$_SESSION['id_hotel'] > 0) ? (int)$_SESSION['id_hotel'] : null;
	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;

	if ($id_hotel_sesion !== null) {
		$objDept = new departamentos();
		if ($objDept->contarDepartamentosPorHotel($id_hotel_sesion) === 0) {
			echo 'SIN_DEPARTAMENTOS_HOTEL';
			exit;
		}
	}

	// Obtener ID del departamento
	$idDepartamento = isset($_POST['ubicacionEquipo']) ? trim((string)$_POST['ubicacionEquipo']) : '';
	if ($idDepartamento === '' || !ctype_digit($idDepartamento) || (int)$idDepartamento <= 0) {
		echo 'SIN_DEPARTAMENTO';
		exit;
	}
	$ubicacionFisica = isset($_POST['ubicacionFisica']) ? trim((string)$_POST['ubicacionFisica']) : '';
	
	// Crear array de datos con departamento
	$datos = array(
		$_SESSION['iduser'],            // 0 - id_usuario
		$idDepartamento,                // 1 - id_departamento
		isset($_POST['codigoEquipo']) ? trim($_POST['codigoEquipo']) : '', // 2 - codigo_equipo (opcional)
		$_POST['descripcionEquipo'],    // 3 - descripcion
		$_POST['marcaEquipo'],          // 4 - marca
		$_POST['modeloEquipo'] ?? '',   // 5 - modelo (opcional)
		$_POST['numeroSerie'],          // 6 - numero_serie
		$_POST['estadoEquipo'],         // 7 - estado
		$ubicacionFisica,               // 8 - ubicacion (texto: dónde se coloca físicamente)
		$_POST['fechaIngreso'],         // 9 - fecha_ingreso
		$_POST['valorEquipo'],          // 10 - valor_adquisicion
		$_POST['tipoMoneda'],           // 11 - tipo_moneda
		$rutaFoto,                      // 12 - foto_equipo
		isset($_POST['usuarioRegistroEquipo']) ? trim((string)$_POST['usuarioRegistroEquipo']) : '', // 13
		isset($_POST['numeroFacturaEquipo']) ? trim((string)$_POST['numeroFacturaEquipo']) : '',     // 14
		$id_hotel_sesion // 15
	);
	
	error_log("Datos preparados: " . print_r($datos, true));
	$resultado = $obj->insertaEquipo($datos);
	error_log("Resultado de inserción: " . $resultado);
	$serieEquipo = isset($_POST['numeroSerie']) ? trim((string)$_POST['numeroSerie']) : '';
	if ($resultado === 'DUPLICATE_SERIE') {
		echo "Número de serie duplicado: $serieEquipo";
	} elseif ($resultado === 'SIN_DEPARTAMENTOS_HOTEL') {
		echo $resultado;
	} elseif (in_array($resultado, array('SIN_DEPARTAMENTO', 'DEPARTAMENTO_NO_EXISTE', 'DEPARTAMENTO_OTRO_HOTEL'), true)) {
		echo $obj->mensajeErrorDepartamentoEquipo($resultado);
	} else {
		echo $resultado;
	}
 ?>
