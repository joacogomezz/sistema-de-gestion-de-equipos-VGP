<?php
require_once "Conexion.php";

class Hoteles {
	private function asegurarHotelesBase($conexion) {
		$existen = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM hoteles");
		$total = 0;
		if ($existen && ($row = mysqli_fetch_assoc($existen))) {
			$total = (int)$row['total'];
		}
		if ($total === 0) {
			@mysqli_query($conexion, "INSERT INTO hoteles (id_hotel, nombre, descripcion, activo) VALUES
				(1, 'Villa del Arco', 'Resort Villa del Arco', 1),
				(2, 'Villa del Palmar', 'Resort Villa del Palmar', 1),
				(3, 'Villa la Estancia', 'Resort Villa la Estancia', 1),
				(4, 'Villa la Valencia', 'Resort Villa la Valencia', 1)");
			return;
		}

		// Si solo quedó el hotel genérico por reset, completar catálogo base.
		$soloGenerico = mysqli_query($conexion, "SELECT COUNT(*) AS c FROM hoteles WHERE nombre = 'Hotel'");
		$cGenerico = 0;
		if ($soloGenerico && ($r = mysqli_fetch_assoc($soloGenerico))) {
			$cGenerico = (int)$r['c'];
		}
		if ($cGenerico > 0 && $total <= 1) {
			@mysqli_query($conexion, "UPDATE hoteles SET nombre='Villa del Arco', descripcion='Resort Villa del Arco', activo=1 WHERE id_hotel=1");
			@mysqli_query($conexion, "INSERT IGNORE INTO hoteles (id_hotel, nombre, descripcion, activo) VALUES
				(2, 'Villa del Palmar', 'Resort Villa del Palmar', 1),
				(3, 'Villa la Estancia', 'Resort Villa la Estancia', 1),
				(4, 'Villa la Valencia', 'Resort Villa la Valencia', 1)");
		}

		// Cuarto hotel: instalaciones que ya tenían solo los tres originales
		@mysqli_query($conexion, "INSERT IGNORE INTO hoteles (id_hotel, nombre, descripcion, activo) VALUES
			(4, 'Villa la Valencia', 'Resort Villa la Valencia', 1)");
	}

	/** Lista todos los hoteles activos (para selector en registro, etc.) */
	public function obtenerTodos() {
		$c = new conectar();
		$conexion = $c->conexion();
		$this->asegurarHotelesBase($conexion);
		$sql = "SELECT id_hotel, nombre, descripcion FROM hoteles WHERE activo = 1 ORDER BY nombre";
		return mysqli_query($conexion, $sql);
	}

	/** Devuelve nombre del hotel por ID */
	public function getNombre($id_hotel) {
		$c = new conectar();
		$conexion = $c->conexion();
		$id = intval($id_hotel);
		$sql = "SELECT nombre FROM hoteles WHERE id_hotel = '$id' LIMIT 1";
		$result = mysqli_query($conexion, $sql);
		if (!$result || mysqli_num_rows($result) === 0) return '';
		$row = mysqli_fetch_row($result);
		return $row[0];
	}

	/** Verifica que el id_hotel exista y esté activo */
	public function existeYActivo($id_hotel) {
		$c = new conectar();
		$conexion = $c->conexion();
		$id = intval($id_hotel);
		$sql = "SELECT 1 FROM hoteles WHERE id_hotel = '$id' AND activo = 1 LIMIT 1";
		$result = mysqli_query($conexion, $sql);
		return $result && mysqli_num_rows($result) > 0;
	}
}
