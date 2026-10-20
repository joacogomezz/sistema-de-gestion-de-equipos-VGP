<?php
require_once "Conexion.php";
require_once "Equipos.php";

class Resguardos {

	public function guardarResguardo($datos) {
		$c = new conectar();
		$conexion = $c->conexion();

		$id_equipo = $datos['id_equipo'];
		$id_departamento = isset($datos['id_departamento']) && $datos['id_departamento'] !== '' ? intval($datos['id_departamento']) : null;
		$area_destino = mysqli_real_escape_string($conexion, $datos['area_destino']);
		$usuario = mysqli_real_escape_string($conexion, $datos['usuario']);
		$puesto = isset($datos['puesto']) ? mysqli_real_escape_string($conexion, $datos['puesto']) : '';
		$fecha_resguardo = mysqli_real_escape_string($conexion, $datos['fecha_resguardo']);
		$numero_factura = isset($datos['numero_factura']) ? mysqli_real_escape_string($conexion, $datos['numero_factura']) : '';
		$modelo = isset($datos['modelo']) ? mysqli_real_escape_string($conexion, $datos['modelo']) : '';
		$serie = isset($datos['serie']) ? mysqli_real_escape_string($conexion, $datos['serie']) : '';
		$tipo_equipo = isset($datos['tipo_equipo']) ? mysqli_real_escape_string($conexion, $datos['tipo_equipo']) : '';

		$id_dep_sql = $id_departamento !== null ? "'$id_departamento'" : "NULL";
		$tienePuesto = @mysqli_query($conexion, "SHOW COLUMNS FROM resguardos LIKE 'puesto'");
		$usaPuesto = $tienePuesto && mysqli_num_rows($tienePuesto) > 0;
		$puestoSql = $puesto === '' ? "NULL" : "'$puesto'";

		if ($usaPuesto) {
			$sql = "INSERT INTO resguardos (id_equipo, id_departamento, area_destino, usuario, puesto, fecha_resguardo, numero_factura, modelo, serie, tipo_equipo)
					VALUES ('$id_equipo', $id_dep_sql, '$area_destino', '$usuario', $puestoSql, '$fecha_resguardo', " .
					($numero_factura === '' ? "NULL" : "'$numero_factura'") . ", " .
					($modelo === '' ? "NULL" : "'$modelo'") . ", " .
					($serie === '' ? "NULL" : "'$serie'") . ", " .
					($tipo_equipo === '' ? "NULL" : "'$tipo_equipo'") . ")";
		} else {
			$sql = "INSERT INTO resguardos (id_equipo, id_departamento, area_destino, usuario, fecha_resguardo, numero_factura, modelo, serie, tipo_equipo)
					VALUES ('$id_equipo', $id_dep_sql, '$area_destino', '$usuario', '$fecha_resguardo', " .
					($numero_factura === '' ? "NULL" : "'$numero_factura'") . ", " .
					($modelo === '' ? "NULL" : "'$modelo'") . ", " .
					($serie === '' ? "NULL" : "'$serie'") . ", " .
					($tipo_equipo === '' ? "NULL" : "'$tipo_equipo'") . ")";
		}

		$result = mysqli_query($conexion, $sql);
		if (!$result) {
			error_log("Error en guardarResguardo: " . mysqli_error($conexion));
			return array('ok' => false, 'id' => null);
		}
		$idInsertado = mysqli_insert_id($conexion);

		// Misma información del resguardo en la ficha del equipo (tabla equipos): usuario, factura y ubicación física (= área de destino)
		$objEq = new equipos();
		$objEq->ensureRegistroEquiposCols();
		$idEqInt = (int) $id_equipo;
		$numFactSet = ($numero_factura === '') ? 'NULL' : "'$numero_factura'";
		$sqlEq = "UPDATE equipos SET usuario_registro = '$usuario', numero_factura = $numFactSet, ubicacion = '$area_destino' WHERE id_equipo = '$idEqInt' LIMIT 1";
		if (!mysqli_query($conexion, $sqlEq)) {
			error_log("guardarResguardo: no se pudo sincronizar usuario_registro/numero_factura/ubicacion en equipo id=$idEqInt: " . mysqli_error($conexion));
		}

		return array('ok' => true, 'id' => $idInsertado);
	}

	public function obtenerDatosResguardo($id_resguardo) {
		$c = new conectar();
		$conexion = $c->conexion();
		$id = intval($id_resguardo);

		$tienePuesto = @mysqli_query($conexion, "SHOW COLUMNS FROM resguardos LIKE 'puesto'");
		$campoPuesto = ($tienePuesto && mysqli_num_rows($tienePuesto) > 0) ? ", r.puesto" : "";
		$sql = "SELECT r.id_resguardo, r.id_equipo, r.area_destino, r.usuario, r.fecha_resguardo, r.numero_factura, r.modelo, r.serie, r.tipo_equipo, r.fecha_registro,
						e.codigo_equipo, e.marca" . $campoPuesto . "
				FROM resguardos r
				INNER JOIN equipos e ON r.id_equipo = e.id_equipo
				WHERE r.id_resguardo = '$id'";
		$result = mysqli_query($conexion, $sql);
		if (!$result || mysqli_num_rows($result) === 0) return null;
		$d = mysqli_fetch_assoc($result);
		if (!isset($d['puesto'])) $d['puesto'] = '';
		return $d;
	}

	public function obtenerListaResguardos($id_hotel = null) {
		$c = new conectar();
		$conexion = $c->conexion();
		$tienePuesto = @mysqli_query($conexion, "SHOW COLUMNS FROM resguardos LIKE 'puesto'");
		$campoPuesto = ($tienePuesto && mysqli_num_rows($tienePuesto) > 0) ? ", r.puesto" : "";
		$tieneGrupo = @mysqli_query($conexion, "SHOW COLUMNS FROM resguardos LIKE 'id_grupo_resguardo'");
		$campoGrupo = ($tieneGrupo && mysqli_num_rows($tieneGrupo) > 0) ? ", r.id_grupo_resguardo" : "";
		$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
		$joinHotel = ($tieneIdHotel && $id_hotel !== null) ? " INNER JOIN departamentos d ON e.id_departamento = d.id_departamento AND d.id_hotel = '" . intval($id_hotel) . "'" : "";
		$sql = "SELECT r.id_resguardo, r.fecha_resguardo, r.area_destino, r.usuario, r.numero_factura, r.modelo, r.serie, r.tipo_equipo, e.codigo_equipo" . $campoPuesto . $campoGrupo . "
				FROM resguardos r
				INNER JOIN equipos e ON r.id_equipo = e.id_equipo
				$joinHotel
				ORDER BY r.fecha_registro DESC";
		return mysqli_query($conexion, $sql);
	}

	/**
	 * Lista para la vista del historial: agrupa por id_grupo_resguardo.
	 * Resguardos múltiples = una fila por grupo (factura, N equipos). Individuales = una fila cada uno.
	 * @param int|null $id_hotel
	 * @return array Lista de filas para mostrar: ['tipo'=>'individual'|'multiple', 'id_resguardo', 'codigos', 'cantidad', ...]
	 */
	public function obtenerListaResguardosAgrupada($id_hotel = null) {
		$result = $this->obtenerListaResguardos($id_hotel);
		if (!$result) return array();
		$todos = array();
		while ($row = mysqli_fetch_assoc($result)) {
			$row['id_grupo_resguardo'] = isset($row['id_grupo_resguardo']) ? $row['id_grupo_resguardo'] : null;
			$todos[] = $row;
		}
		$vistos = array();
		$agrupados = array();
		foreach ($todos as $r) {
			$id = (int) $r['id_resguardo'];
			$idGrupo = $r['id_grupo_resguardo'];
			if ($idGrupo === null || $idGrupo === '') {
				$agrupados[] = array(
					'tipo' => 'individual',
					'id_resguardo' => $id,
					'codigo_equipo' => $r['codigo_equipo'],
					'area_destino' => $r['area_destino'],
					'usuario' => $r['usuario'],
					'fecha_resguardo' => $r['fecha_resguardo'],
					'numero_factura' => $r['numero_factura'],
					'modelo' => $r['modelo'],
					'serie' => $r['serie'],
					'tipo_equipo' => $r['tipo_equipo'],
					'puesto' => isset($r['puesto']) ? $r['puesto'] : '',
					'cantidad' => 1
				);
				continue;
			}
			$idGrupo = (int) $idGrupo;
			if (isset($vistos[$idGrupo])) continue;
			$vistos[$idGrupo] = true;
			$grupo = array_filter($todos, function($x) use ($idGrupo) {
				$g = isset($x['id_grupo_resguardo']) ? $x['id_grupo_resguardo'] : null;
				return $g !== null && $g !== '' && (int)$g === $idGrupo;
			});
			$grupo = array_values($grupo);
			$primero = $grupo[0];
			$codigos = implode(', ', array_map(function($x) { return $x['codigo_equipo']; }, $grupo));
			$agrupados[] = array(
				'tipo' => 'multiple',
				'id_resguardo' => (int) $primero['id_resguardo'],
				'codigo_equipo' => $codigos,
				'area_destino' => $primero['area_destino'],
				'usuario' => $primero['usuario'],
				'fecha_resguardo' => $primero['fecha_resguardo'],
				'numero_factura' => $primero['numero_factura'],
				'modelo' => count($grupo) > 1 ? '(varios)' : $primero['modelo'],
				'serie' => count($grupo) > 1 ? '(varios)' : $primero['serie'],
				'tipo_equipo' => count($grupo) > 1 ? '(varios)' : $primero['tipo_equipo'],
				'puesto' => isset($primero['puesto']) ? $primero['puesto'] : '',
				'cantidad' => count($grupo),
				'ids_grupo' => array_map(function($x) { return (int)$x['id_resguardo']; }, $grupo)
			);
		}
		return $agrupados;
	}

	/**
	 * Asigna el mismo id_grupo_resguardo a varios resguardos (creados en una sola operación múltiple).
	 * Solo se agrupan entre sí; los individuales tienen id_grupo_resguardo = NULL.
	 * @param array $ids_resguardos Lista de id_resguardo creados en la misma operación
	 * @return bool
	 */
	public function asignarGrupoResguardos($ids_resguardos) {
		if (!is_array($ids_resguardos) || count($ids_resguardos) === 0) return false;
		$c = new conectar();
		$conexion = $c->conexion();
		$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM resguardos LIKE 'id_grupo_resguardo'");
		if (!$chk || mysqli_num_rows($chk) === 0) return true;
		$ids = array_map('intval', $ids_resguardos);
		$ids = array_filter($ids, function($x) { return $x > 0; });
		if (count($ids) === 0) return false;
		$id_grupo = min($ids);
		$lista = implode(',', $ids);
		$sql = "UPDATE resguardos SET id_grupo_resguardo = '$id_grupo' WHERE id_resguardo IN ($lista)";
		return (bool) mysqli_query($conexion, $sql);
	}

	/**
	 * Obtiene los IDs de resguardos del mismo grupo. Solo se agrupan los que tienen
	 * id_grupo_resguardo (resguardo múltiple); los individuales (NULL) devuelven solo [id].
	 * @param int $id_resguardo ID de cualquier resguardo
	 * @return array|null [id1, id2, ...] o null si el resguardo no existe
	 */
	public function obtenerIdsMismoGrupo($id_resguardo) {
		$c = new conectar();
		$conexion = $c->conexion();
		$id = intval($id_resguardo);
		if ($id <= 0) return null;
		$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM resguardos LIKE 'id_grupo_resguardo'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			$row = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT id_grupo_resguardo FROM resguardos WHERE id_resguardo = '$id' LIMIT 1"));
			if (!$row) return null;
			$id_grupo = isset($row['id_grupo_resguardo']) ? $row['id_grupo_resguardo'] : null;
			if ($id_grupo === null || $id_grupo === '') {
				return array($id);
			}
			$id_grupo = intval($id_grupo);
			$result = mysqli_query($conexion, "SELECT id_resguardo FROM resguardos WHERE id_grupo_resguardo = '$id_grupo' ORDER BY id_resguardo ASC");
			if (!$result) return array($id);
			$ids = array();
			while ($r = mysqli_fetch_row($result)) $ids[] = (int) $r[0];
			return count($ids) > 0 ? $ids : array($id);
		}
		$row = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT id_resguardo FROM resguardos WHERE id_resguardo = '$id' LIMIT 1"));
		return $row ? array($id) : null;
	}

	/**
	 * Elimina un resguardo por ID (para corregir registros erróneos).
	 * @param int $id_resguardo
	 * @return bool true si se eliminó, false en caso contrario
	 */
	public function eliminarResguardo($id_resguardo) {
		$c = new conectar();
		$conexion = $c->conexion();
		$id = intval($id_resguardo);
		if ($id <= 0) return false;
		$sql = "DELETE FROM resguardos WHERE id_resguardo = '$id' LIMIT 1";
		$result = mysqli_query($conexion, $sql);
		return $result && mysqli_affected_rows($conexion) > 0;
	}

	/**
	 * Elimina un resguardo o todo su grupo (si es múltiple).
	 * @param int $id_resguardo Cualquier ID del resguardo (o del grupo)
	 * @return array ['ok'=>bool, 'eliminados'=>int]
	 */
	public function eliminarResguardoOGrupo($id_resguardo) {
		$ids = $this->obtenerIdsMismoGrupo($id_resguardo);
		if (!$ids) return array('ok' => false, 'eliminados' => 0);
		$c = new conectar();
		$conexion = $c->conexion();
		$lista = implode(',', array_map('intval', $ids));
		$sql = "DELETE FROM resguardos WHERE id_resguardo IN ($lista)";
		$result = mysqli_query($conexion, $sql);
		$n = $result ? mysqli_affected_rows($conexion) : 0;
		return array('ok' => $result && $n > 0, 'eliminados' => $n);
	}
}
