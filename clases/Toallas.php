<?php
require_once "Conexion.php";

class Toallas {
	// Retención de historial: cambia aquí fácilmente (ej: 2 MONTH, 90 DAY, 6 MONTH).
	private const HISTORIAL_RETENCION_CANTIDAD = 1;
	private const HISTORIAL_RETENCION_UNIDAD = 'MONTH';

	private function limitarObservacion($texto, $max = 255) {
		$texto = trim((string)$texto);
		if ($texto === '') return '';
		if (strlen($texto) <= $max) return $texto;
		if ($max <= 3) return substr($texto, 0, $max);
		return substr($texto, 0, $max - 3) . '...';
	}

	private function conexion() {
		$c = new conectar();
		return $c->conexion();
	}

	private function asegurarTablas($conexion) {
		@mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS toallas_stock (
			id_stock INT AUTO_INCREMENT PRIMARY KEY,
			id_hotel INT NOT NULL,
			tipo_toalla VARCHAR(100) NOT NULL,
			cantidad_actual INT NOT NULL DEFAULT 0,
			fecha_actualizacion DATETIME NOT NULL,
			UNIQUE KEY uq_toallas_hotel_tipo (id_hotel, tipo_toalla),
			FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel)
		)");

		@mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS toallas_movimientos (
			id_movimiento INT AUTO_INCREMENT PRIMARY KEY,
			id_hotel INT NOT NULL,
			id_usuario INT NOT NULL,
			tipo_toalla VARCHAR(100) NOT NULL,
			tipo_movimiento ENUM('ENTRADA','SALIDA') NOT NULL,
			estado_cierre ENUM('DEVUELTO','NO_DEVUELTO') NOT NULL DEFAULT 'DEVUELTO',
			cantidad INT NOT NULL,
			cantidad_entregada INT NULL COMMENT 'Cantidad total originalmente entregada en la solicitud del día',
			observaciones VARCHAR(255) NULL,
			fecha_movimiento DATETIME NOT NULL,
			FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel),
			FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
		)");
		$chkEstadoCierre = @mysqli_query($conexion, "SHOW COLUMNS FROM toallas_movimientos LIKE 'estado_cierre'");
		if (!$chkEstadoCierre || mysqli_num_rows($chkEstadoCierre) === 0) {
			@mysqli_query($conexion, "ALTER TABLE toallas_movimientos ADD COLUMN estado_cierre ENUM('DEVUELTO','NO_DEVUELTO') NOT NULL DEFAULT 'DEVUELTO' AFTER tipo_movimiento");
		}
		$chkCantidadEntregada = @mysqli_query($conexion, "SHOW COLUMNS FROM toallas_movimientos LIKE 'cantidad_entregada'");
		if (!$chkCantidadEntregada || mysqli_num_rows($chkCantidadEntregada) === 0) {
			@mysqli_query($conexion, "ALTER TABLE toallas_movimientos ADD COLUMN cantidad_entregada INT NULL AFTER cantidad");
		}

		@mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS toallas_solicitudes (
			id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
			id_hotel INT NOT NULL,
			id_usuario_registro INT NOT NULL,
			numero_habitacion VARCHAR(20) NOT NULL,
			nombre_huesped VARCHAR(120) NOT NULL,
			cantidad INT NOT NULL,
			cantidad_regresada INT NOT NULL DEFAULT 0,
			observaciones VARCHAR(255) NULL,
			fecha_solicitud DATETIME NOT NULL,
			FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel),
			FOREIGN KEY (id_usuario_registro) REFERENCES usuarios(id_usuario)
		)");
		$chkRegresada = @mysqli_query($conexion, "SHOW COLUMNS FROM toallas_solicitudes LIKE 'cantidad_regresada'");
		if (!$chkRegresada || mysqli_num_rows($chkRegresada) === 0) {
			@mysqli_query($conexion, "ALTER TABLE toallas_solicitudes ADD COLUMN cantidad_regresada INT NOT NULL DEFAULT 0 AFTER cantidad");
		}

		// Campos específicos de entrega a huésped
		$chkHabitacion = @mysqli_query($conexion, "SHOW COLUMNS FROM toallas_movimientos LIKE 'numero_habitacion'");
		if (!$chkHabitacion || mysqli_num_rows($chkHabitacion) === 0) {
			@mysqli_query($conexion, "ALTER TABLE toallas_movimientos ADD COLUMN numero_habitacion VARCHAR(20) NULL AFTER cantidad");
		}
		$chkHuesped = @mysqli_query($conexion, "SHOW COLUMNS FROM toallas_movimientos LIKE 'nombre_huesped'");
		if (!$chkHuesped || mysqli_num_rows($chkHuesped) === 0) {
			@mysqli_query($conexion, "ALTER TABLE toallas_movimientos ADD COLUMN nombre_huesped VARCHAR(120) NULL AFTER numero_habitacion");
		}

		@mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS toallas_configuracion (
			id_config INT AUTO_INCREMENT PRIMARY KEY,
			id_hotel INT NOT NULL,
			retencion_cantidad INT NOT NULL DEFAULT 1,
			retencion_unidad VARCHAR(10) NOT NULL DEFAULT 'MONTH',
			fecha_actualizacion DATETIME NOT NULL,
			UNIQUE KEY uq_toallas_config_hotel (id_hotel),
			FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel)
		)");
	}

	private function tieneColumna($conexion, $tabla, $columna) {
		$tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);
		$columna = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$columna);
		if ($tabla === '' || $columna === '') return false;
		$q = @mysqli_query($conexion, "SHOW COLUMNS FROM `$tabla` LIKE '$columna'");
		return ($q && mysqli_num_rows($q) > 0);
	}

	private function limpiarHistorialAntiguo($conexion, $idHotel = null) {
		$idHotel = ($idHotel === null) ? null : (int)$idHotel;
		$whereHotel = ($idHotel !== null && $idHotel > 0) ? " AND id_hotel='" . $idHotel . "'" : "";
		$config = $this->obtenerConfiguracionRetencion(($idHotel !== null && $idHotel > 0) ? $idHotel : 1);
		$cantidad = isset($config['cantidad']) ? (int)$config['cantidad'] : (int)self::HISTORIAL_RETENCION_CANTIDAD;
		if ($cantidad <= 0) $cantidad = (int)self::HISTORIAL_RETENCION_CANTIDAD;
		$unidad = isset($config['unidad']) ? strtoupper(trim((string)$config['unidad'])) : strtoupper(trim((string)self::HISTORIAL_RETENCION_UNIDAD));
		$unidadesPermitidas = array('DAY', 'MONTH', 'YEAR');
		if (!in_array($unidad, $unidadesPermitidas, true)) {
			$unidad = strtoupper(trim((string)self::HISTORIAL_RETENCION_UNIDAD));
		}
		// Borra registros más antiguos que la retención configurada.
		@mysqli_query($conexion, "DELETE FROM toallas_movimientos WHERE fecha_movimiento < DATE_SUB(NOW(), INTERVAL $cantidad $unidad)$whereHotel");
	}

	private function inicioVentanaOperativa() {
		$ahora = new DateTime('now');
		$inicio = new DateTime('today 01:00:00');
		if ($ahora < $inicio) {
			$inicio->modify('-1 day');
		}
		return $inicio->format('Y-m-d H:i:s');
	}

	public function obtenerConfiguracionRetencion($idHotel) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		if ($idHotel <= 0) $idHotel = 1;
		$res = mysqli_query($conexion, "SELECT retencion_cantidad, retencion_unidad FROM toallas_configuracion WHERE id_hotel='$idHotel' LIMIT 1");
		if ($res && ($row = mysqli_fetch_assoc($res))) {
			return array(
				'cantidad' => (int)$row['retencion_cantidad'],
				'unidad' => strtoupper(trim((string)$row['retencion_unidad']))
			);
		}
		return array(
			'cantidad' => (int)self::HISTORIAL_RETENCION_CANTIDAD,
			'unidad' => strtoupper(trim((string)self::HISTORIAL_RETENCION_UNIDAD))
		);
	}

	public function actualizarConfiguracionRetencion($idHotel, $cantidad, $unidad) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		$cantidad = (int)$cantidad;
		$unidad = strtoupper(trim((string)$unidad));
		$unidadesPermitidas = array('DAY', 'MONTH', 'YEAR');
		if ($idHotel <= 0) $idHotel = 1;
		if ($cantidad <= 0) $cantidad = 1;
		if (!in_array($unidad, $unidadesPermitidas, true)) $unidad = 'MONTH';
		$fecha = date('Y-m-d H:i:s');

		$sql = "INSERT INTO toallas_configuracion (id_hotel, retencion_cantidad, retencion_unidad, fecha_actualizacion)
				VALUES ('$idHotel', '$cantidad', '$unidad', '$fecha')
				ON DUPLICATE KEY UPDATE retencion_cantidad=VALUES(retencion_cantidad), retencion_unidad=VALUES(retencion_unidad), fecha_actualizacion=VALUES(fecha_actualizacion)";
		$ok = mysqli_query($conexion, $sql);
		if ($ok) {
			return array('ok' => true, 'mensaje' => 'Configuración de retención actualizada.');
		}
		return array('ok' => false, 'mensaje' => 'No se pudo actualizar la configuración de retención.');
	}

	public function eliminarMovimientoHistorial($idHotel, $idMovimiento) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		$idMovimiento = (int)$idMovimiento;
		if ($idHotel <= 0 || $idMovimiento <= 0) {
			return array('ok' => false, 'mensaje' => 'Datos inválidos para eliminar.');
		}

		$sql = "DELETE FROM toallas_movimientos WHERE id_movimiento='$idMovimiento' AND id_hotel='$idHotel' LIMIT 1";
		$ok = mysqli_query($conexion, $sql);
		if ($ok && mysqli_affected_rows($conexion) > 0) {
			return array('ok' => true, 'mensaje' => 'Registro de historial eliminado correctamente.');
		}
		return array('ok' => false, 'mensaje' => 'No se encontró el registro o no se pudo eliminar.');
	}

	public function borrarTodoHistorialConPassword($idHotel, $idUsuario, $passwordPlano) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		$idUsuario = (int)$idUsuario;
		$passwordPlano = trim((string)$passwordPlano);
		if ($idHotel <= 0 || $idUsuario <= 0 || $passwordPlano === '') {
			return array('ok' => false, 'mensaje' => 'Debes ingresar la contraseña para confirmar.');
		}

		$hash = sha1($passwordPlano);
		$hashEsc = mysqli_real_escape_string($conexion, $hash);
		$val = mysqli_query($conexion, "SELECT id_usuario FROM usuarios WHERE id_usuario='$idUsuario' AND rol='TOALLEROS' AND password='$hashEsc' LIMIT 1");
		if (!$val || mysqli_num_rows($val) === 0) {
			return array('ok' => false, 'mensaje' => 'Contraseña incorrecta. No se eliminaron registros.');
		}

		$ok = mysqli_query($conexion, "DELETE FROM toallas_movimientos WHERE id_hotel='$idHotel'");
		if ($ok) {
			$total = mysqli_affected_rows($conexion);

			// Si ya no quedan filas en historial, reiniciar contador de IDs.
			$chk = mysqli_query($conexion, "SELECT COUNT(*) AS c FROM toallas_movimientos");
			$totalRestante = 0;
			if ($chk && ($row = mysqli_fetch_assoc($chk))) {
				$totalRestante = (int)$row['c'];
			}
			if ($totalRestante === 0) {
				@mysqli_query($conexion, "ALTER TABLE toallas_movimientos AUTO_INCREMENT = 1");
			}

			return array('ok' => true, 'mensaje' => 'Historial eliminado correctamente. Registros borrados: ' . max(0, (int)$total) . '.');
		}
		return array('ok' => false, 'mensaje' => 'No se pudo eliminar el historial.');
	}

	public function obtenerResumenToallas($idHotel) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		if ($idHotel <= 0) $idHotel = 1;

		$totales = array(
			'entregadas_activas' => 0,
			'pendientes_activas' => 0,
			'no_devueltas_historial' => 0,
			'entregadas_archivadas' => 0,
			'total_entregadas' => 0,
			'total_faltantes' => 0
		);
		$inicioVentana = mysqli_real_escape_string($conexion, $this->inicioVentanaOperativa());

		$r1 = mysqli_query($conexion, "SELECT COALESCE(SUM(cantidad),0) AS total FROM toallas_solicitudes WHERE id_hotel='$idHotel' AND fecha_solicitud >= '$inicioVentana'");
		if ($r1 && ($row = mysqli_fetch_assoc($r1))) {
			$totales['entregadas_activas'] = (int)$row['total'];
		}

		$r2 = mysqli_query($conexion, "SELECT COALESCE(SUM(GREATEST(cantidad - cantidad_regresada, 0)),0) AS total FROM toallas_solicitudes WHERE id_hotel='$idHotel' AND fecha_solicitud >= '$inicioVentana'");
		if ($r2 && ($row = mysqli_fetch_assoc($r2))) {
			$totales['pendientes_activas'] = (int)$row['total'];
		}

		$usaEstadoCierre = $this->tieneColumna($conexion, 'toallas_movimientos', 'estado_cierre');
		$whereNoDevuelto = $usaEstadoCierre ? "estado_cierre='NO_DEVUELTO'" : "observaciones LIKE '%Archivado sin devolución%'";
		$r3 = mysqli_query($conexion, "SELECT COALESCE(SUM(cantidad),0) AS total FROM toallas_movimientos WHERE id_hotel='$idHotel' AND fecha_movimiento >= '$inicioVentana' AND $whereNoDevuelto");
		if ($r3 && ($row = mysqli_fetch_assoc($r3))) {
			$totales['no_devueltas_historial'] = (int)$row['total'];
		}

		$r4 = mysqli_query($conexion, "SELECT COALESCE(SUM(COALESCE(cantidad_entregada, cantidad)),0) AS total FROM toallas_movimientos WHERE id_hotel='$idHotel' AND fecha_movimiento >= '$inicioVentana'");
		if ($r4 && ($row = mysqli_fetch_assoc($r4))) {
			$totales['entregadas_archivadas'] = (int)$row['total'];
		}

		$totales['total_entregadas'] = $totales['entregadas_activas'] + $totales['entregadas_archivadas'];
		$totales['total_faltantes'] = $totales['pendientes_activas'] + $totales['no_devueltas_historial'];

		return $totales;
	}

	public function obtenerIdHotelArco($conexion) {
		$res = mysqli_query($conexion, "SELECT id_hotel FROM hoteles WHERE LOWER(nombre) LIKE '%arco%' LIMIT 1");
		if ($res && ($row = mysqli_fetch_row($res))) {
			return (int)$row[0];
		}
		return 1;
	}

	public function puedeOperarEnArco($idHotelSesion) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idArco = $this->obtenerIdHotelArco($conexion);
		return ((int)$idHotelSesion === (int)$idArco);
	}

	public function obtenerMovimientosHotel($idHotel, $limite = 50) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		$limite = max(1, (int)$limite);
		$this->limpiarHistorialAntiguo($conexion, $idHotel);
		$estadoCierreSql = $this->tieneColumna($conexion, 'toallas_movimientos', 'estado_cierre')
			? "m.estado_cierre"
			: "CASE WHEN m.observaciones LIKE '%Archivado sin devolución%' THEN 'NO_DEVUELTO' ELSE 'DEVUELTO' END";
		$sql = "SELECT m.id_movimiento, m.tipo_toalla, m.tipo_movimiento, $estadoCierreSql AS estado_cierre, m.cantidad, m.numero_habitacion, m.nombre_huesped, m.observaciones, m.fecha_movimiento,
					   CONCAT(u.nombre, ' ', u.apellido) as nombre_usuario
				FROM toallas_movimientos m
				INNER JOIN usuarios u ON m.id_usuario = u.id_usuario
				WHERE m.id_hotel='$idHotel'
				ORDER BY m.id_movimiento DESC
				LIMIT $limite";
		return mysqli_query($conexion, $sql);
	}

	public function obtenerSolicitudesActivasHotel($idHotel, $limite = 100) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		$limite = max(1, (int)$limite);
		$inicioVentana = mysqli_real_escape_string($conexion, $this->inicioVentanaOperativa());
		$sql = "SELECT s.id_solicitud, s.numero_habitacion, s.nombre_huesped, s.cantidad, s.cantidad_regresada, s.observaciones, s.fecha_solicitud,
					   CONCAT(u.nombre, ' ', u.apellido) as usuario_registro
				FROM toallas_solicitudes s
				INNER JOIN usuarios u ON s.id_usuario_registro = u.id_usuario
				WHERE s.id_hotel='$idHotel' AND s.fecha_solicitud >= '$inicioVentana'
				ORDER BY s.id_solicitud DESC
				LIMIT $limite";
		return mysqli_query($conexion, $sql);
	}

	public function registrarSolicitudEntrega($idHotel, $idUsuario, $numeroHabitacion, $nombreHuesped, $cantidad, $observaciones) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		$idUsuario = (int)$idUsuario;
		$cantidad = (int)$cantidad;
		$numeroHabitacion = trim((string)$numeroHabitacion);
		$nombreHuesped = trim((string)$nombreHuesped);
		$observaciones = trim((string)$observaciones);
		if ($cantidad <= 0 || $numeroHabitacion === '' || $nombreHuesped === '') {
			return array('ok' => false, 'mensaje' => 'Debes capturar habitación, huésped y cantidad.');
		}

		$habitacionEsc = mysqli_real_escape_string($conexion, $numeroHabitacion);
		$huespedEsc = mysqli_real_escape_string($conexion, $nombreHuesped);
		$obsEsc = mysqli_real_escape_string($conexion, $observaciones);
		$fecha = date('Y-m-d H:i:s');
		$sql = "INSERT INTO toallas_solicitudes (id_hotel, id_usuario_registro, numero_habitacion, nombre_huesped, cantidad, observaciones, fecha_solicitud)
				VALUES ('$idHotel', '$idUsuario', '$habitacionEsc', '$huespedEsc', '$cantidad', " . ($obsEsc === '' ? "NULL" : "'$obsEsc'") . ", '$fecha')";
		if (mysqli_query($conexion, $sql)) {
			return array('ok' => true, 'mensaje' => 'Solicitud de toallas registrada.');
		}
		return array('ok' => false, 'mensaje' => 'Error al registrar la solicitud.');
	}

	public function registrarDevolucionSolicitud($idHotel, $idSolicitud, $cantidadRegresa, $idUsuarioArchivador) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		$idSolicitud = (int)$idSolicitud;
		$cantidadRegresa = (int)$cantidadRegresa;
		$idUsuarioArchivador = (int)$idUsuarioArchivador;
		if ($idSolicitud <= 0 || $cantidadRegresa <= 0) {
			return array('ok' => false, 'mensaje' => 'Ingresa una cantidad válida de toallas devueltas.');
		}

		mysqli_begin_transaction($conexion);
		try {
			$res = mysqli_query($conexion, "SELECT * FROM toallas_solicitudes WHERE id_solicitud='$idSolicitud' AND id_hotel='$idHotel' LIMIT 1 FOR UPDATE");
			if (!$res || mysqli_num_rows($res) === 0) {
				mysqli_rollback($conexion);
				return array('ok' => false, 'mensaje' => 'No se encontró la solicitud.');
			}
			$row = mysqli_fetch_assoc($res);
			$cantidadTotal = (int)$row['cantidad'];
			$regresadaActual = (int)$row['cantidad_regresada'];
			$pendientes = max(0, $cantidadTotal - $regresadaActual);
			if ($pendientes <= 0) {
				mysqli_rollback($conexion);
				return array('ok' => false, 'mensaje' => 'Esta solicitud ya no tiene toallas pendientes.');
			}
			if ($cantidadRegresa > $pendientes) {
				mysqli_rollback($conexion);
				return array('ok' => false, 'mensaje' => 'La devolución excede las toallas pendientes.');
			}

			$nuevaRegresada = $regresadaActual + $cantidadRegresa;
			mysqli_query($conexion, "UPDATE toallas_solicitudes SET cantidad_regresada='$nuevaRegresada' WHERE id_solicitud='$idSolicitud' AND id_hotel='$idHotel' LIMIT 1");

			$restantes = $cantidadTotal - $nuevaRegresada;
			if ($restantes <= 0) {
				// Solicitud completada: pasa al historial y elimina de pendientes.
				$tipoToallaEsc = 'TOALLA_HUESPED';
				$tipoMovimiento = 'SALIDA';
				$cantidad = $cantidadTotal;
				$habitacionEsc = mysqli_real_escape_string($conexion, (string)$row['numero_habitacion']);
				$huespedEsc = mysqli_real_escape_string($conexion, (string)$row['nombre_huesped']);
				$obsOriginal = isset($row['observaciones']) ? trim((string)$row['observaciones']) : '';
				$obsFinal = ($obsOriginal === '') ? 'Proceso finalizado: huésped regresó toallas.' : ($obsOriginal . ' | Proceso finalizado: huésped regresó toallas.');
				$obsFinal = $this->limitarObservacion($obsFinal, 255);
				$obsFinalEsc = mysqli_real_escape_string($conexion, $obsFinal);
				$fecha = date('Y-m-d H:i:s');

				$usaEstadoCierre = $this->tieneColumna($conexion, 'toallas_movimientos', 'estado_cierre');
				if ($usaEstadoCierre) {
					mysqli_query($conexion, "INSERT INTO toallas_movimientos (id_hotel, id_usuario, tipo_toalla, tipo_movimiento, estado_cierre, cantidad, cantidad_entregada, numero_habitacion, nombre_huesped, observaciones, fecha_movimiento)
						VALUES ('$idHotel', '$idUsuarioArchivador', '$tipoToallaEsc', '$tipoMovimiento', 'DEVUELTO', '$cantidad', '$cantidadTotal', '$habitacionEsc', '$huespedEsc', " . ($obsFinalEsc === '' ? "NULL" : "'$obsFinalEsc'") . ", '$fecha')");
				} else {
					mysqli_query($conexion, "INSERT INTO toallas_movimientos (id_hotel, id_usuario, tipo_toalla, tipo_movimiento, cantidad, cantidad_entregada, numero_habitacion, nombre_huesped, observaciones, fecha_movimiento)
						VALUES ('$idHotel', '$idUsuarioArchivador', '$tipoToallaEsc', '$tipoMovimiento', '$cantidad', '$cantidadTotal', '$habitacionEsc', '$huespedEsc', " . ($obsFinalEsc === '' ? "NULL" : "'$obsFinalEsc'") . ", '$fecha')");
				}
				mysqli_query($conexion, "DELETE FROM toallas_solicitudes WHERE id_solicitud='$idSolicitud' AND id_hotel='$idHotel' LIMIT 1");
				mysqli_commit($conexion);
				return array('ok' => true, 'mensaje' => 'Devolución completa. Solicitud finalizada y enviada al historial.');
			}

			mysqli_commit($conexion);
			return array('ok' => true, 'mensaje' => 'Devolución registrada. Aún quedan ' . $restantes . ' toallas pendientes.');
		} catch (Exception $e) {
			mysqli_rollback($conexion);
			return array('ok' => false, 'mensaje' => 'Error al registrar devolución. Detalle: ' . $e->getMessage());
		}
	}

	public function archivarSinDevolucion($idHotel, $idSolicitud, $idUsuarioArchivador) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		$idSolicitud = (int)$idSolicitud;
		$idUsuarioArchivador = (int)$idUsuarioArchivador;
		if ($idSolicitud <= 0) {
			return array('ok' => false, 'mensaje' => 'Solicitud inválida.');
		}

		mysqli_begin_transaction($conexion);
		try {
			$res = mysqli_query($conexion, "SELECT * FROM toallas_solicitudes WHERE id_solicitud='$idSolicitud' AND id_hotel='$idHotel' LIMIT 1 FOR UPDATE");
			if (!$res || mysqli_num_rows($res) === 0) {
				mysqli_rollback($conexion);
				return array('ok' => false, 'mensaje' => 'No se encontró la solicitud.');
			}
			$row = mysqli_fetch_assoc($res);
			$tipoToallaEsc = 'TOALLA_HUESPED';
			$tipoMovimiento = 'SALIDA';
			$cantidadTotal = (int)$row['cantidad'];
			$cantidadRegresada = isset($row['cantidad_regresada']) ? (int)$row['cantidad_regresada'] : 0;
			$cantidad = max(0, $cantidadTotal - $cantidadRegresada);
			if ($cantidad <= 0) {
				mysqli_rollback($conexion);
				return array('ok' => false, 'mensaje' => 'No hay toallas pendientes para archivar sin devolución.');
			}
			$habitacionEsc = mysqli_real_escape_string($conexion, (string)$row['numero_habitacion']);
			$huespedEsc = mysqli_real_escape_string($conexion, (string)$row['nombre_huesped']);
			$obsOriginal = isset($row['observaciones']) ? trim((string)$row['observaciones']) : '';
			$obsFinal = ($obsOriginal === '') ? 'Archivado sin devolución de toallas por parte del huésped.' : ($obsOriginal . ' | Archivado sin devolución de toallas por parte del huésped.');
			$obsFinal = $this->limitarObservacion($obsFinal, 255);
			$obsFinalEsc = mysqli_real_escape_string($conexion, $obsFinal);
			$fecha = date('Y-m-d H:i:s');

			$usaEstadoCierre = $this->tieneColumna($conexion, 'toallas_movimientos', 'estado_cierre');
			if ($usaEstadoCierre) {
				mysqli_query($conexion, "INSERT INTO toallas_movimientos (id_hotel, id_usuario, tipo_toalla, tipo_movimiento, estado_cierre, cantidad, cantidad_entregada, numero_habitacion, nombre_huesped, observaciones, fecha_movimiento)
					VALUES ('$idHotel', '$idUsuarioArchivador', '$tipoToallaEsc', '$tipoMovimiento', 'NO_DEVUELTO', '$cantidad', '$cantidadTotal', '$habitacionEsc', '$huespedEsc', " . ($obsFinalEsc === '' ? "NULL" : "'$obsFinalEsc'") . ", '$fecha')");
			} else {
				mysqli_query($conexion, "INSERT INTO toallas_movimientos (id_hotel, id_usuario, tipo_toalla, tipo_movimiento, cantidad, cantidad_entregada, numero_habitacion, nombre_huesped, observaciones, fecha_movimiento)
					VALUES ('$idHotel', '$idUsuarioArchivador', '$tipoToallaEsc', '$tipoMovimiento', '$cantidad', '$cantidadTotal', '$habitacionEsc', '$huespedEsc', " . ($obsFinalEsc === '' ? "NULL" : "'$obsFinalEsc'") . ", '$fecha')");
			}
			mysqli_query($conexion, "DELETE FROM toallas_solicitudes WHERE id_solicitud='$idSolicitud' AND id_hotel='$idHotel' LIMIT 1");
			mysqli_commit($conexion);
			return array('ok' => true, 'mensaje' => 'Solicitud archivada sin devolución y enviada al historial en estado rojo.');
		} catch (Exception $e) {
			mysqli_rollback($conexion);
			return array('ok' => false, 'mensaje' => 'Error al archivar sin devolución. Detalle: ' . $e->getMessage());
		}
	}

	public function reactivarDesdeHistorialNoDevuelto($idHotel, $idMovimiento, $idUsuario) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		$idMovimiento = (int)$idMovimiento;
		$idUsuario = (int)$idUsuario;
		if ($idMovimiento <= 0) {
			return array('ok' => false, 'mensaje' => 'Movimiento inválido.');
		}

		mysqli_begin_transaction($conexion);
		try {
			$usaEstadoCierre = $this->tieneColumna($conexion, 'toallas_movimientos', 'estado_cierre');
			$whereNoDevuelto = $usaEstadoCierre ? "AND estado_cierre='NO_DEVUELTO'" : "AND observaciones LIKE '%Archivado sin devolución%'";
			$res = mysqli_query($conexion, "SELECT * FROM toallas_movimientos WHERE id_movimiento='$idMovimiento' AND id_hotel='$idHotel' $whereNoDevuelto LIMIT 1 FOR UPDATE");
			if (!$res || mysqli_num_rows($res) === 0) {
				mysqli_rollback($conexion);
				return array('ok' => false, 'mensaje' => 'Solo se pueden reactivar registros no devueltos.');
			}
			$row = mysqli_fetch_assoc($res);
			$habitacionEsc = mysqli_real_escape_string($conexion, (string)$row['numero_habitacion']);
			$huespedEsc = mysqli_real_escape_string($conexion, (string)$row['nombre_huesped']);
			$cantidad = (int)$row['cantidad'];
			$obsOriginal = isset($row['observaciones']) ? trim((string)$row['observaciones']) : '';
			$obsNueva = ($obsOriginal === '') ? 'Solicitud reactivada desde historial NO_DEVUELTO.' : ($obsOriginal . ' | Solicitud reactivada desde historial NO_DEVUELTO.');
			$obsNueva = $this->limitarObservacion($obsNueva, 255);
			$obsNuevaEsc = mysqli_real_escape_string($conexion, $obsNueva);
			$fechaAhora = date('Y-m-d H:i:s');

			mysqli_query($conexion, "INSERT INTO toallas_solicitudes (id_hotel, id_usuario_registro, numero_habitacion, nombre_huesped, cantidad, cantidad_regresada, observaciones, fecha_solicitud)
				VALUES ('$idHotel', '$idUsuario', '$habitacionEsc', '$huespedEsc', '$cantidad', '0', " . ($obsNuevaEsc === '' ? "NULL" : "'$obsNuevaEsc'") . ", '$fechaAhora')");

			mysqli_query($conexion, "DELETE FROM toallas_movimientos WHERE id_movimiento='$idMovimiento' AND id_hotel='$idHotel' LIMIT 1");
			mysqli_commit($conexion);
			return array('ok' => true, 'mensaje' => 'Solicitud reactivada y enviada nuevamente a toallas activas.');
		} catch (Exception $e) {
			mysqli_rollback($conexion);
			return array('ok' => false, 'mensaje' => 'Error al reactivar la solicitud. Detalle: ' . $e->getMessage());
		}
	}

	public function archivarSolicitud($idHotel, $idSolicitud, $idUsuarioArchivador) {
		$conexion = $this->conexion();
		$this->asegurarTablas($conexion);
		$idHotel = (int)$idHotel;
		$idSolicitud = (int)$idSolicitud;
		$idUsuarioArchivador = (int)$idUsuarioArchivador;
		if ($idSolicitud <= 0) {
			return array('ok' => false, 'mensaje' => 'Solicitud inválida.');
		}

		mysqli_begin_transaction($conexion);
		try {
			$res = mysqli_query($conexion, "SELECT * FROM toallas_solicitudes WHERE id_solicitud='$idSolicitud' AND id_hotel='$idHotel' LIMIT 1 FOR UPDATE");
			if (!$res || mysqli_num_rows($res) === 0) {
				mysqli_rollback($conexion);
				return array('ok' => false, 'mensaje' => 'No se encontró la solicitud.');
			}
			$row = mysqli_fetch_assoc($res);
			$tipoToallaEsc = 'TOALLA_HUESPED';
			$tipoMovimiento = 'SALIDA';
			$cantidad = (int)$row['cantidad'];
			$habitacionEsc = mysqli_real_escape_string($conexion, (string)$row['numero_habitacion']);
			$huespedEsc = mysqli_real_escape_string($conexion, (string)$row['nombre_huesped']);
			$obsOriginal = isset($row['observaciones']) ? trim((string)$row['observaciones']) : '';
			$obsFinal = $this->limitarObservacion($obsOriginal, 255);
			$obsFinalEsc = mysqli_real_escape_string($conexion, $obsFinal);
			$fecha = date('Y-m-d H:i:s');

			mysqli_query($conexion, "INSERT INTO toallas_movimientos (id_hotel, id_usuario, tipo_toalla, tipo_movimiento, cantidad, cantidad_entregada, numero_habitacion, nombre_huesped, observaciones, fecha_movimiento)
				VALUES ('$idHotel', '$idUsuarioArchivador', '$tipoToallaEsc', '$tipoMovimiento', '$cantidad', '$cantidad', '$habitacionEsc', '$huespedEsc', " . ($obsFinalEsc === '' ? "NULL" : "'$obsFinalEsc'") . ", '$fecha')");

			mysqli_query($conexion, "DELETE FROM toallas_solicitudes WHERE id_solicitud='$idSolicitud' AND id_hotel='$idHotel' LIMIT 1");
			mysqli_commit($conexion);
			return array('ok' => true, 'mensaje' => 'Solicitud archivada y enviada al historial.');
		} catch (Exception $e) {
			mysqli_rollback($conexion);
			return array('ok' => false, 'mensaje' => 'Error al archivar la solicitud. Detalle: ' . $e->getMessage());
		}
	}

	public function registrarEntregaHuesped($idHotel, $idUsuario, $numeroHabitacion, $nombreHuesped, $cantidad, $observaciones) {
		return $this->registrarSolicitudEntrega($idHotel, $idUsuario, $numeroHabitacion, $nombreHuesped, $cantidad, $observaciones);
	}
}
?>
