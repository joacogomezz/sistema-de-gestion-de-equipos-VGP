<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/AltasBajas.php";

	// Habilitar reporte de errores para debugging
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	$obj= new altasBajas();

	// Verificar que la sesión esté activa
	if(!isset($_SESSION['iduser'])){
		error_log("Error: No hay sesión de usuario activa");
		echo 0;
		exit;
	}

	// Verificar que se recibieron todos los datos necesarios
	$campos_requeridos = ['equipoBaja', 'motivoBaja', 'valorResidual', 'destinoEquipo', 'tecnicoReviso', 'gerenteArea'];
	
	foreach($campos_requeridos as $campo){
		if(!isset($_POST[$campo]) || empty($_POST[$campo])){
			error_log("Error: Campo requerido faltante: " . $campo);
			echo 0;
			exit;
		}
	}

	// Procesar fotos si se subieron
	$fotoEquipo = null;
	$fotoEtiqueta = null;
	
	// Crear directorio para fotos de bajas si no existe
	$directorioFotos = "../../archivos/bajas/";
	if (!file_exists($directorioFotos)) {
		mkdir($directorioFotos, 0777, true);
	}
	
	// Procesar foto del equipo
	if(isset($_FILES['fotoEquipo']) && $_FILES['fotoEquipo']['error'] == 0){
		$archivo = $_FILES['fotoEquipo'];
		$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
		$extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
		
		if(in_array($extension, $extensionesPermitidas) && $archivo['size'] <= 2 * 1024 * 1024){
			$nombreArchivo = 'equipo_' . $_POST['equipoBaja'] . '_' . time() . '.' . $extension;
			$rutaDestino = $directorioFotos . $nombreArchivo;
			
			if(move_uploaded_file($archivo['tmp_name'], $rutaDestino)){
				$fotoEquipo = 'archivos/bajas/' . $nombreArchivo;
				error_log("Foto del equipo guardada: " . $fotoEquipo);
			}
		}
	}
	
	// Procesar foto de la etiqueta
	if(isset($_FILES['fotoEtiqueta']) && $_FILES['fotoEtiqueta']['error'] == 0){
		$archivo = $_FILES['fotoEtiqueta'];
		$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
		$extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
		
		if(in_array($extension, $extensionesPermitidas) && $archivo['size'] <= 2 * 1024 * 1024){
			$nombreArchivo = 'etiqueta_' . $_POST['equipoBaja'] . '_' . time() . '.' . $extension;
			$rutaDestino = $directorioFotos . $nombreArchivo;
			
			if(move_uploaded_file($archivo['tmp_name'], $rutaDestino)){
				$fotoEtiqueta = 'archivos/bajas/' . $nombreArchivo;
				error_log("Foto de la etiqueta guardada: " . $fotoEtiqueta);
			}
		}
	}

	// Obtener el departamento del equipo original
	$c = new conectar();
	$conexion = $c->conexion();
	$sqlDepartamento = "SELECT id_departamento FROM equipos WHERE id_equipo = '" . $_POST['equipoBaja'] . "'";
	$resultDepartamento = mysqli_query($conexion, $sqlDepartamento);
	$idDepartamento = null;
	if($resultDepartamento && mysqli_num_rows($resultDepartamento) > 0){
		$rowDepartamento = mysqli_fetch_row($resultDepartamento);
		$idDepartamento = $rowDepartamento[0];
	}

	// Fecha de adquisición: si N/A o vacía se guarda NULL
	$fechaAdquisicion = null;
	if (empty($_POST['fechaAdquisicionNA']) && !empty(trim($_POST['fechaAdquisicion'] ?? ''))) {
		$fechaAdquisicion = trim($_POST['fechaAdquisicion']);
	}

	$datos=array(
		$_POST['equipoBaja'],
		$_SESSION['iduser'],
		$idDepartamento,
		'Individual',
		$_POST['motivoBaja'],
		$_POST['valorResidual'],
		$_POST['destinoEquipo'],
		$_POST['observacionesBaja'] ?? '',
		$fotoEquipo,
		$fotoEtiqueta,
		$_POST['tecnicoReviso'],
		$_POST['gerenteArea'],
		$fechaAdquisicion
	);

	// Log para debugging
	error_log("=== Registro de baja ===");
	error_log("Datos a registrar: " . print_r($datos, true));

	$resultado = $obj->registrarBaja($datos);
	
	if($resultado){
		error_log("Baja registrada exitosamente");
		echo 1;
	}else{
		error_log("Error al registrar baja");
		echo 0;
	}
 ?>
