<?php
/**
 * Clase para gestionar la Solicitud para dar de Alta Equipos de Operación.
 * Guarda cabecera (departamento, fecha, número solicitud, clasificaciones) y detalle por equipo.
 */
class SolicitudAltaEquipos {

	/**
	 * Guarda la solicitud completa (cabecera + líneas detalle).
	 * $cabecera: id_usuario, id_departamento_solicitante, numero_solicitud, fecha_solicitud, clasificacion_1, clasificacion_2, id_hotel
	 * $detalles: array de arrays, cada uno: id_equipo, caracteristicas, color, centro_costos, id_fase, id_area, piso, tipo_habitacion, id_departamento
	 */
	public function guardar($cabecera, $detalles) {
		$c = new conectar();
		$conexion = $c->conexion();
		$cantidad = count($detalles);
		$id_usuario = intval($cabecera['id_usuario']);
		$id_depto = !empty($cabecera['id_departamento_solicitante']) ? intval($cabecera['id_departamento_solicitante']) : 'NULL';
		$numero_solicitud = mysqli_real_escape_string($conexion, $cabecera['numero_solicitud']);
		$fecha = mysqli_real_escape_string($conexion, $cabecera['fecha_solicitud']);
		$clas1 = isset($cabecera['clasificacion_1']) ? "'" . mysqli_real_escape_string($conexion, $cabecera['clasificacion_1']) . "'" : "NULL";
		$clas2 = isset($cabecera['clasificacion_2']) ? "'" . mysqli_real_escape_string($conexion, $cabecera['clasificacion_2']) . "'" : "NULL";
		$id_hotel = isset($cabecera['id_hotel']) && $cabecera['id_hotel'] !== '' ? intval($cabecera['id_hotel']) : 'NULL';

		$sql = "INSERT INTO solicitud_alta_equipos (id_usuario, id_departamento_solicitante, numero_solicitud, fecha_solicitud, cantidad_equipos, clasificacion_1, clasificacion_2, id_hotel)
				VALUES ('$id_usuario', $id_depto, '$numero_solicitud', '$fecha', '$cantidad', $clas1, $clas2, " . ($id_hotel === 'NULL' ? 'NULL' : "'$id_hotel'") . ")";
		if (!mysqli_query($conexion, $sql)) {
			return false;
		}
		$id_solicitud = mysqli_insert_id($conexion);

		foreach ($detalles as $d) {
			$id_equipo = intval($d['id_equipo']);
			$caract = isset($d['caracteristicas']) ? "'" . mysqli_real_escape_string($conexion, $d['caracteristicas']) . "'" : "NULL";
			$color = isset($d['color']) ? "'" . mysqli_real_escape_string($conexion, $d['color']) . "'" : "NULL";
			$centro = in_array($d['centro_costos'] ?? '', array('GASTO DE OPERACIÓN', 'GASTO DE OPERACION')) ? "'GASTO DE OPERACION'" : "'CAPEX'";
			$id_fase = !empty($d['id_fase']) ? intval($d['id_fase']) : 'NULL';
			$id_area = !empty($d['id_area']) ? intval($d['id_area']) : 'NULL';
			$piso = isset($d['piso']) ? "'" . mysqli_real_escape_string($conexion, $d['piso']) . "'" : "NULL";
			$tipo_hab = isset($d['tipo_habitacion']) ? "'" . mysqli_real_escape_string($conexion, $d['tipo_habitacion']) . "'" : "NULL";
			$id_dep = !empty($d['id_departamento']) ? intval($d['id_departamento']) : 'NULL';

			$sqlDet = "INSERT INTO solicitud_alta_equipos_detalle (id_solicitud, id_equipo, caracteristicas, color, centro_costos, id_fase, id_area, piso, tipo_habitacion, id_departamento)
					   VALUES ('$id_solicitud', '$id_equipo', $caract, $color, $centro, $id_fase, $id_area, $piso, $tipo_hab, $id_dep)";
			mysqli_query($conexion, $sqlDet);
		}
		return $id_solicitud;
	}

	/**
	 * Obtiene cabecera de la solicitud para el PDF.
	 */
	public function obtenerSolicitud($id_solicitud) {
		$c = new conectar();
		$conexion = $c->conexion();
		$id_solicitud = intval($id_solicitud);
		$sql = "SELECT s.id_solicitud, s.numero_solicitud, s.fecha_solicitud, s.cantidad_equipos, s.clasificacion_1, s.clasificacion_2,
					   d.nombre as nombre_departamento_solicitante,
					   u.nombre as nombre_usuario, u.apellido as apellido_usuario
				FROM solicitud_alta_equipos s
				LEFT JOIN departamentos d ON s.id_departamento_solicitante = d.id_departamento
				LEFT JOIN usuarios u ON s.id_usuario = u.id_usuario
				WHERE s.id_solicitud = '$id_solicitud' LIMIT 1";
		$result = mysqli_query($conexion, $sql);
		if ($result && $row = mysqli_fetch_assoc($result)) {
			return $row;
		}
		return null;
	}

	/**
	 * Obtiene el detalle de la solicitud con datos del equipo, fase, área y departamento para el PDF.
	 */
	public function obtenerDetalle($id_solicitud) {
		$c = new conectar();
		$conexion = $c->conexion();
		$id_solicitud = intval($id_solicitud);
		$sql = "SELECT e.codigo_equipo, e.descripcion, e.marca, e.modelo, e.numero_serie,
					   sd.caracteristicas, sd.color, sd.centro_costos, sd.piso, sd.tipo_habitacion,
					   f.nombre as nombre_fase, a.nombre as nombre_area, d.nombre as nombre_departamento,
					   sd.id_equipo
				FROM solicitud_alta_equipos_detalle sd
				INNER JOIN equipos e ON sd.id_equipo = e.id_equipo
				LEFT JOIN fases f ON sd.id_fase = f.id_fase
				LEFT JOIN areas a ON sd.id_area = a.id_area
				LEFT JOIN departamentos d ON sd.id_departamento = d.id_departamento
				WHERE sd.id_solicitud = '$id_solicitud'
				ORDER BY sd.id_detalle";
		return mysqli_query($conexion, $sql);
	}
}
