<?php 
	// Incluir archivo de seguridad
	require_once "../../seguridad.php";
	
	// Requerir autenticación
	requerirAutenticacion();
	
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Equipos.php";

	// Verificar que solo el administrador (usuario ID 1) pueda eliminar
	if(!isset($_SESSION['iduser']) || $_SESSION['iduser'] != 1){
		error_log("Error: Usuario no autorizado para eliminar bajas. ID: " . ($_SESSION['iduser'] ?? 'No definido'));
		echo 0;
		exit;
	}

	// Verificar que se recibió el ID de la baja
	if(!isset($_POST['idBaja']) || empty($_POST['idBaja'])){
		error_log("Error: No se recibió ID de baja");
		echo 0;
		exit;
	}

	$idBaja = $_POST['idBaja'];
	$c = new conectar();
	$conexion = $c->conexion();

	// Obtener información de la baja antes de eliminar (para logs y para restaurar el equipo)
	$sqlInfo = "SELECT b.id_equipo, e.codigo_equipo, e.estado, b.foto_equipo, b.foto_etiqueta 
				FROM bajas_equipos b 
				INNER JOIN equipos e ON b.id_equipo = e.id_equipo 
				WHERE b.id_baja = '$idBaja'";
	$resultInfo = mysqli_query($conexion, $sqlInfo);
	
	if(!$resultInfo || mysqli_num_rows($resultInfo) == 0){
		error_log("Error: No se encontró la baja con ID: " . $idBaja);
		echo 0;
		exit;
	}
	
	$infoBaja = mysqli_fetch_row($resultInfo);
	$idEquipo = $infoBaja[0];
	$codigoEquipo = $infoBaja[1];
	$estadoActual = $infoBaja[2];
	$fotoEquipo = $infoBaja[3];
	$fotoEtiqueta = $infoBaja[4];

	// Iniciar transacción para asegurar consistencia
	mysqli_autocommit($conexion, false);

	try {
		// 1. Eliminar archivos de fotos si existen
		if($fotoEquipo && file_exists("../../" . $fotoEquipo)){
			unlink("../../" . $fotoEquipo);
			error_log("Foto del equipo eliminada: " . $fotoEquipo);
		}
		
		if($fotoEtiqueta && file_exists("../../" . $fotoEtiqueta)){
			unlink("../../" . $fotoEtiqueta);
			error_log("Foto de la etiqueta eliminada: " . $fotoEtiqueta);
		}

		// 2. Restaurar el estado del equipo a "Activo"
		$sqlRestaurar = "UPDATE equipos SET estado = 'Activo' WHERE id_equipo = '$idEquipo'";
		$resultRestaurar = mysqli_query($conexion, $sqlRestaurar);
		
		if(!$resultRestaurar){
			throw new Exception("Error al restaurar estado del equipo: " . mysqli_error($conexion));
		}

		// 3. Eliminar el registro de baja
		$sqlEliminar = "DELETE FROM bajas_equipos WHERE id_baja = '$idBaja'";
		$resultEliminar = mysqli_query($conexion, $sqlEliminar);
		
		if(!$resultEliminar){
			throw new Exception("Error al eliminar registro de baja: " . mysqli_error($conexion));
		}

		// Confirmar transacción
		mysqli_commit($conexion);
		
		// Log de éxito
		error_log("Baja eliminada exitosamente - ID: $idBaja, Equipo: $codigoEquipo, Usuario: " . $_SESSION['iduser']);
		
		echo 1;

	} catch (Exception $e) {
		// Revertir transacción en caso de error
		mysqli_rollback($conexion);
		error_log("Error en eliminación de baja: " . $e->getMessage());
		echo 0;
	}

	// Restaurar autocommit
	mysqli_autocommit($conexion, true);
?>



