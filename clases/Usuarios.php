<?php 

	class usuarios{
		private function asegurarColumnaRol($conexion){
			$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'rol'");
			if (!$chk || mysqli_num_rows($chk) === 0) {
				@mysqli_query($conexion, "ALTER TABLE usuarios ADD COLUMN rol VARCHAR(30) NOT NULL DEFAULT 'ADMIN' AFTER id_hotel");
				@mysqli_query($conexion, "UPDATE usuarios SET rol='ADMIN' WHERE rol IS NULL OR TRIM(rol)=''");
			}
		}

		private function resolverHotelValido($conexion, $idHotelSolicitado){
			$idHotelSolicitado = (int)$idHotelSolicitado;
			if ($idHotelSolicitado <= 0) {
				$idHotelSolicitado = 1;
			}

			// 1) Si el hotel solicitado existe, usarlo.
			$res = mysqli_query($conexion, "SELECT id_hotel FROM hoteles WHERE id_hotel='" . $idHotelSolicitado . "' LIMIT 1");
			if ($res && mysqli_num_rows($res) > 0) {
				return $idHotelSolicitado;
			}

			// 2) Si no existe, tomar el primer hotel disponible.
			$resPrimero = mysqli_query($conexion, "SELECT id_hotel FROM hoteles ORDER BY id_hotel ASC LIMIT 1");
			if ($resPrimero && ($row = mysqli_fetch_row($resPrimero))) {
				return (int)$row[0];
			}

			// 3) Si no hay hoteles, crear base minima y usar id_hotel=1.
			@mysqli_query($conexion, "INSERT INTO hoteles (id_hotel, nombre, descripcion, activo) VALUES (1, 'Hotel', 'Hotel por defecto', 1)");
			$resDefault = mysqli_query($conexion, "SELECT id_hotel FROM hoteles WHERE id_hotel='1' LIMIT 1");
			if ($resDefault && mysqli_num_rows($resDefault) > 0) {
				return 1;
			}

			// 4) Fallback final defensivo.
			return 1;
		}

		public function registroUsuario($datos){
			$c=new conectar();
			$conexion=$c->conexion();
			$this->asegurarColumnaRol($conexion);

			$fecha=date('Y-m-d');
			$id_hotel = isset($datos[4]) ? intval($datos[4]) : 1;
			$rolSolicitado = isset($datos[5]) ? strtoupper(trim((string)$datos[5])) : 'ADMIN';
			$rolPermitido = in_array($rolSolicitado, array('ADMIN', 'TOALLEROS'), true) ? $rolSolicitado : 'ADMIN';
			$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'id_hotel'");
			$usaIdHotel = $tieneIdHotel && mysqli_num_rows($tieneIdHotel) > 0;

			$esPrimerUsuario = false;
			$chkPrimer = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios");
			if ($chkPrimer && ($rowPrimer = mysqli_fetch_assoc($chkPrimer))) {
				$esPrimerUsuario = ((int)$rowPrimer['total'] === 0);
			}

			$cols = "nombre, apellido, email, password, fechaCaptura";
			$vals = "'$datos[0]', '$datos[1]', '$datos[2]', '$datos[3]', '$fecha'";
			if ($esPrimerUsuario) {
				// Regla del sistema: el super admin se identifica por id_usuario = 1.
				$cols = "id_usuario, " . $cols;
				$vals = "'1', " . $vals;
			}
			if ($usaIdHotel) {
				$id_hotel = $this->resolverHotelValido($conexion, $id_hotel);
				$cols .= ", id_hotel";
				$vals .= ", '$id_hotel'";
			}
			$cols .= ", rol";
			$vals .= ", '" . mysqli_real_escape_string($conexion, $rolPermitido) . "'";

			$sql="INSERT into usuarios ($cols) values ($vals)";
			return mysqli_query($conexion,$sql);
		}
	public function loginUser($datos){
		$c=new conectar();
		$conexion=$c->conexion();
		$this->asegurarColumnaRol($conexion);
		$password=sha1($datos[1]);

		$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'id_hotel'");
		$campoHotel = ($tieneIdHotel && mysqli_num_rows($tieneIdHotel) > 0) ? ", id_hotel" : "";
		$sql="SELECT id_usuario, nombre, apellido, email $campoHotel, rol
				from usuarios 
			where email='$datos[0]'
			and password='$password'";
		$result=mysqli_query($conexion,$sql);

		if(mysqli_num_rows($result) > 0){
			$user_data = mysqli_fetch_assoc($result);
			
			$_SESSION['usuario'] = $user_data['email'];
			$_SESSION['iduser'] = $user_data['id_usuario'];
			$_SESSION['nombre'] = $user_data['nombre'];
			$_SESSION['apellido'] = $user_data['apellido'];
			$_SESSION['ultima_actividad'] = time();
			$_SESSION['ultima_regeneracion'] = time();
			$_SESSION['tiempo_inicio'] = time();

			if ($campoHotel !== '' && !empty($user_data['id_hotel'])) {
				$_SESSION['id_hotel'] = (int) $user_data['id_hotel'];
				require_once __DIR__ . '/Hoteles.php';
				$hoteles = new Hoteles();
				$_SESSION['nombre_hotel'] = $hoteles->getNombre($user_data['id_hotel']);
			} else {
				$_SESSION['id_hotel'] = 1;
				$_SESSION['nombre_hotel'] = 'Hotel';
			}
			$_SESSION['rol'] = !empty($user_data['rol']) ? strtoupper($user_data['rol']) : 'ADMIN';
			
			session_regenerate_id(true);
			
			return 1;
		}else{
			return 0;
		}
	}
		public function traeID($datos){
			$c=new conectar();
			$conexion=$c->conexion();

			$password=sha1($datos[1]);

			$sql="SELECT id_usuario 
					from usuarios 
					where email='$datos[0]'
					and password='$password'"; 
			$result=mysqli_query($conexion,$sql);

			return mysqli_fetch_row($result)[0];
		}

		public function obtenDatosUsuario($idusuario){

			$c=new conectar();
			$conexion=$c->conexion();
			$this->asegurarColumnaRol($conexion);

			$sql="SELECT id_usuario,
							id_hotel,
							rol,
							nombre,
							apellido,
							email
					from usuarios 
					where id_usuario='$idusuario'";
			$result=mysqli_query($conexion,$sql);

			$ver=mysqli_fetch_row($result);

			$datos=array(
						'id_usuario' => $ver[0],
							'id_hotel' => $ver[1],
							'rol' => $ver[2],
							'nombre' => $ver[3],
							'apellido' => $ver[4],
							'email' => $ver[5]
						);

			return $datos;
		}

		public function actualizaUsuario($datos){
			$c=new conectar();
			$conexion=$c->conexion();

			$nombre = mysqli_real_escape_string($conexion, $datos[1]);
			$apellido = mysqli_real_escape_string($conexion, $datos[2]);
			$email = mysqli_real_escape_string($conexion, $datos[3]);
			$id = mysqli_real_escape_string($conexion, $datos[0]);
			$setPassword = '';
			if (isset($datos[4]) && trim($datos[4]) !== '') {
				$passHash = sha1(trim($datos[4]));
				$setPassword = ", password='" . mysqli_real_escape_string($conexion, $passHash) . "'";
			}

			$sql="UPDATE usuarios set nombre='$nombre',
									apellido='$apellido',
									email='$email'
									$setPassword
						where id_usuario='$id'";
			return mysqli_query($conexion,$sql);	
		}

		public function eliminaUsuario($idusuario){
			$c=new conectar();
			$conexion=$c->conexion();
			$idusuario = (int)$idusuario;

			// Regla crítica: nunca permitir borrar al super admin (id_usuario = 1).
			if ($idusuario === 1) {
				return 'SUPER_ADMIN_PROTEGIDO';
			}

			$sql="DELETE from usuarios 
					where id_usuario='$idusuario'";
			return mysqli_query($conexion,$sql);
		}
	}

 ?>
