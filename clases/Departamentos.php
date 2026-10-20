<?php 

	class departamentos{
		public function agregarDepartamento($datos){
			$c=new conectar();
			$conexion=$c->conexion();

			$fecha=date('Y-m-d');
			$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			$usaIdHotel = $tieneIdHotel && mysqli_num_rows($tieneIdHotel) > 0;

			$id_hotel = $usaIdHotel && isset($datos[0]) ? intval($datos[0]) : 1;
			$id_usuario = $usaIdHotel ? $datos[1] : $datos[0];
			$nombre = $usaIdHotel ? $datos[2] : $datos[1];
			$descripcion = $usaIdHotel ? $datos[3] : $datos[2];
			$cols = "nombre, descripcion, responsable, fecha_creacion";
			$vals = "'" . mysqli_real_escape_string($conexion, $nombre) . "', '" . mysqli_real_escape_string($conexion, $descripcion) . "', '', '$fecha'";
			if ($usaIdHotel) {
				$cols = "id_hotel, id_usuario, " . $cols;
				$vals = "'$id_hotel', '" . mysqli_real_escape_string($conexion, $id_usuario) . "', " . $vals;
			} else {
				$cols = "id_usuario, " . $cols;
				$vals = "'$id_usuario', " . $vals;
			}

			$sql="INSERT into departamentos ($cols) values ($vals)";
			$resultado = mysqli_query($conexion,$sql);
			
			if($resultado){
				return 1;
			}else{
				return 0;
			}
		}

		public function obtenDatosDepartamento($iddepartamento){
			$c=new conectar();
			$conexion=$c->conexion();

			$sql="SELECT id_departamento,
							nombre,
							descripcion
					from departamentos 
					where id_departamento='$iddepartamento'";
			$result=mysqli_query($conexion,$sql);

			$ver=mysqli_fetch_row($result);

			$datos=array(
						'id_departamento' => $ver[0],
							'nombre' => $ver[1],
							'descripcion' => $ver[2]
						);

			return $datos;
		}

		public function actualizaDepartamento($datos){
			$c=new conectar();
			$conexion=$c->conexion();

			$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			$id_dep = isset($datos[0]) ? $datos[0] : '';
			$nombre = isset($datos[1]) ? $datos[1] : '';
			$descripcion = isset($datos[2]) ? $datos[2] : '';

			$whereHotel = '';
			if ($tieneIdHotel && isset($datos[3])) {
				$id_hotel = intval($datos[3]);
				$whereHotel = " AND id_hotel='$id_hotel'";
			}
			$sql="UPDATE departamentos set nombre='" . mysqli_real_escape_string($conexion, $nombre) . "',
									descripcion='" . mysqli_real_escape_string($conexion, $descripcion) . "'
						where id_departamento='$id_dep'" . $whereHotel;
			$resultado = mysqli_query($conexion,$sql);
			
			if($resultado){
				return 1;
			}else{
				return 0;
			}
		}

		public function eliminaDepartamento($iddepartamento, $id_hotel = null){
			$c=new conectar();
			$conexion=$c->conexion();

			$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			$whereHotel = ($tieneIdHotel && $id_hotel !== null) ? " AND id_hotel='" . intval($id_hotel) . "'" : "";
			$sql="DELETE from departamentos 
					where id_departamento='$iddepartamento'" . $whereHotel;
			$resultado = mysqli_query($conexion,$sql);
			
			if($resultado){
				return 1;
			}else{
				return 0;
			}
		}

		/** Cuenta departamentos registrados para un hotel (0 si no hay columna id_hotel). */
		public function contarDepartamentosPorHotel($id_hotel) {
			$hid = (int)$id_hotel;
			if ($hid <= 0) {
				return 0;
			}
			$c = new conectar();
			$conexion = $c->conexion();
			$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			if (!$tieneIdHotel || mysqli_num_rows($tieneIdHotel) === 0) {
				$res = mysqli_query($conexion, "SELECT COUNT(*) FROM departamentos");
			} else {
				$res = mysqli_query($conexion, "SELECT COUNT(*) FROM departamentos WHERE id_hotel='" . $hid . "'");
			}
			if ($res && ($row = mysqli_fetch_row($res))) {
				return (int)$row[0];
			}
			return 0;
		}

		public function obtenerTodosDepartamentos($id_hotel = null){
			$c=new conectar();
			$conexion=$c->conexion();

			$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			$whereHotel = ($tieneIdHotel && $id_hotel !== null) ? " WHERE id_hotel='" . intval($id_hotel) . "'" : "";
			$sql="SELECT id_departamento, nombre, descripcion, fecha_creacion
					from departamentos 
					$whereHotel
					order by nombre";
			return mysqli_query($conexion,$sql);
		}

		public function obtenerDepartamentosConEquipos($id_hotel = null){
			$c=new conectar();
			$conexion=$c->conexion();

			$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			$whereHotel = ($tieneIdHotel && $id_hotel !== null) ? " AND d.id_hotel='" . intval($id_hotel) . "'" : "";
			$sql="SELECT d.id_departamento, d.nombre, d.descripcion,
						COUNT(e.id_equipo) as total_equipos
					from departamentos d
					left join equipos e on d.id_departamento = e.id_departamento
					where 1=1 $whereHotel
					group by d.id_departamento
					order by d.nombre";
			return mysqli_query($conexion,$sql);
		}
	}

 ?>
