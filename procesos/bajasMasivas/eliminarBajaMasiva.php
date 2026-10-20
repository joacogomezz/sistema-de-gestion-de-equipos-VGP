<?php 
	// Iniciar sesión si no está iniciada
	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}
	
	// Verificar que el usuario esté autenticado y sea admin (id = 1)
	if(!isset($_SESSION['iduser']) || $_SESSION['iduser'] != 1) {
		echo "0"; // No autorizado
		exit;
	}
	
	require_once "../../clases/Conexion.php";
	$c = new conectar();
	$conexion = $c->conexion();
	
	$idBaja = $_POST['idBaja'];
	
	// Verificar que el ID de baja sea válido
	if(empty($idBaja) || !is_numeric($idBaja)) {
		echo "0";
		exit;
	}
	
	// Iniciar transacción para asegurar consistencia
	mysqli_autocommit($conexion, FALSE);
	
	try {
		// Log de debug
		error_log("Intentando eliminar baja masiva ID: " . $idBaja);
		
		// Primero, obtener información de la baja masiva para verificar que existe
		$sqlVerificar = "SELECT id_baja_masiva FROM bajas_masivas WHERE id_baja_masiva = '$idBaja' LIMIT 1";
		error_log("SQL Verificar: " . $sqlVerificar);
		$resultVerificar = mysqli_query($conexion, $sqlVerificar);
		
		if(!$resultVerificar) {
			throw new Exception("Error en consulta de verificación: " . mysqli_error($conexion));
		}
		
		$numRows = mysqli_num_rows($resultVerificar);
		error_log("Registros encontrados: " . $numRows);
		
		if($numRows == 0) {
			throw new Exception("Baja masiva no encontrada");
		}
		
		// Eliminar registros relacionados en bajas_masivas_equipos
		$sqlEliminarRelaciones = "DELETE FROM bajas_masivas_equipos WHERE id_baja_masiva = '$idBaja'";
		error_log("SQL Eliminar Relaciones: " . $sqlEliminarRelaciones);
		$resultEliminarRelaciones = mysqli_query($conexion, $sqlEliminarRelaciones);
		
		if(!$resultEliminarRelaciones) {
			throw new Exception("Error al eliminar relaciones: " . mysqli_error($conexion));
		}
		
		// Eliminar el registro principal de bajas_masivas
		$sqlEliminar = "DELETE FROM bajas_masivas WHERE id_baja_masiva = '$idBaja'";
		error_log("SQL Eliminar: " . $sqlEliminar);
		$resultEliminar = mysqli_query($conexion, $sqlEliminar);
		
		if(!$resultEliminar) {
			throw new Exception("Error al eliminar registros de baja masiva: " . mysqli_error($conexion));
		}
		
		$affectedRows = mysqli_affected_rows($conexion);
		error_log("Registros eliminados de bajas_masivas: " . $affectedRows);
		
		// Confirmar transacción
		mysqli_commit($conexion);
		echo "1"; // Éxito
		
	} catch (Exception $e) {
		// Revertir transacción en caso de error
		mysqli_rollback($conexion);
		echo "0"; // Error
		error_log("Error al eliminar baja masiva: " . $e->getMessage());
		error_log("ID Baja: " . $idBaja);
		error_log("Error MySQL: " . mysqli_error($conexion));
	} finally {
		// Restaurar autocommit
		mysqli_autocommit($conexion, TRUE);
	}
?>
