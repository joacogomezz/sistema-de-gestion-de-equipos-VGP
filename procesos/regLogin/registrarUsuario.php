<?php 

	require_once "../../clases/Conexion.php";
	require_once "../../clases/Usuarios.php";

	$obj= new usuarios();
	$c = new conectar();
	$conexion = $c->conexion();

	$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
	if ($usuario === '') {
		echo 'Usuario invalido.';
		exit;
	}

	$usuarioEsc = mysqli_real_escape_string($conexion, $usuario);
	$chk = mysqli_query($conexion, "SELECT id_usuario FROM usuarios WHERE email='$usuarioEsc' LIMIT 1");
	if ($chk && mysqli_num_rows($chk) > 0) {
		echo 'Ese usuario ya existe, usa otro.';
		exit;
	}

	$pass=sha1($_POST['password']);
	$id_hotel = isset($_POST['id_hotel']) ? (int) $_POST['id_hotel'] : 1;
	$rol = isset($_POST['rol']) ? strtoupper(trim((string)$_POST['rol'])) : 'ADMIN';
	if (!in_array($rol, array('ADMIN', 'TOALLEROS'), true)) {
		$rol = 'ADMIN';
	}

	// Regla de negocio: usuarios TOALLEROS solo para Villa del Arco.
	if ($rol === 'TOALLEROS') {
		$resArco = mysqli_query($conexion, "SELECT id_hotel FROM hoteles WHERE LOWER(nombre) LIKE '%arco%' LIMIT 1");
		if ($resArco && ($rowArco = mysqli_fetch_row($resArco))) {
			$id_hotel = (int)$rowArco[0];
		} else {
			$id_hotel = 1;
		}
	}
	$datos=array(
		$_POST['nombre'],
		$_POST['apellido'],
		$_POST['usuario'],
		$pass,
		$id_hotel,
		$rol
	);

	$r = $obj->registroUsuario($datos);
	if ($r) {
		echo '1';
	} else {
		echo 'No se pudo guardar el usuario. ' . mysqli_error($conexion);
	}

 ?>
