<?php
/**
 * Clase para gestionar las Fases del hotel (catálogo para solicitud de alta de equipos).
 */
class Fases {

	public function agregar($datos) {
		$c = new conectar();
		$conexion = $c->conexion();
		$fecha = date('Y-m-d');
		$id_hotel = isset($datos[0]) ? intval($datos[0]) : 1;
		$nombre = mysqli_real_escape_string($conexion, $datos[1]);
		$descripcion = isset($datos[2]) ? "'" . mysqli_real_escape_string($conexion, $datos[2]) . "'" : "NULL";
		$sql = "INSERT INTO fases (id_hotel, nombre, descripcion, fecha_creacion) VALUES ('$id_hotel', '$nombre', $descripcion, '$fecha')";
		return mysqli_query($conexion, $sql);
	}

	public function obtenerTodos($id_hotel = null) {
		$c = new conectar();
		$conexion = $c->conexion();
		$where = "";
		if ($id_hotel !== null) {
			$id_hotel = intval($id_hotel);
			$where = " WHERE id_hotel = '$id_hotel'";
		}
		$sql = "SELECT id_fase, nombre, descripcion, activo, fecha_creacion FROM fases $where ORDER BY nombre";
		return mysqli_query($conexion, $sql);
	}

	public function obtenerDatos($id_fase) {
		$c = new conectar();
		$conexion = $c->conexion();
		$id_fase = intval($id_fase);
		$sql = "SELECT id_fase, id_hotel, nombre, descripcion, activo FROM fases WHERE id_fase = '$id_fase' LIMIT 1";
		$result = mysqli_query($conexion, $sql);
		if ($result && $row = mysqli_fetch_assoc($result)) {
			return $row;
		}
		return null;
	}

	public function actualizar($datos) {
		$c = new conectar();
		$conexion = $c->conexion();
		$id = intval($datos[0]);
		$nombre = mysqli_real_escape_string($conexion, $datos[1]);
		$descripcion = isset($datos[2]) ? "'" . mysqli_real_escape_string($conexion, $datos[2]) . "'" : "NULL";
		$sql = "UPDATE fases SET nombre = '$nombre', descripcion = $descripcion WHERE id_fase = '$id'";
		return mysqli_query($conexion, $sql);
	}

	public function eliminar($id_fase, $id_hotel = null) {
		$c = new conectar();
		$conexion = $c->conexion();
		$id_fase = intval($id_fase);
		$where = "id_fase = '$id_fase'";
		if ($id_hotel !== null) {
			$where .= " AND id_hotel = '" . intval($id_hotel) . "'";
		}
		$sql = "DELETE FROM fases WHERE $where";
		return mysqli_query($conexion, $sql);
	}
}
