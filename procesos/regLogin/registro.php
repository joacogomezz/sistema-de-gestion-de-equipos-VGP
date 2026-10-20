<?php
require_once "../../clases/Conexion.php";
require_once "../../clases/Usuarios.php";

header('Content-Type: text/html; charset=utf-8');

$nombre   = isset($_POST['nombre'])   ? trim($_POST['nombre'])   : '';
$apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
$email    = isset($_POST['email'])    ? trim($_POST['email'])    : '';
$password = isset($_POST['password']) ? $_POST['password']       : '';
$id_hotel = isset($_POST['id_hotel']) ? (int) $_POST['id_hotel'] : 1;

if ($nombre === '' || $apellido === '' || $email === '' || $password === '') {
	echo 'Completa todos los campos.';
	exit;
}

if (strlen($password) < 4) {
	echo 'La contraseña debe tener al menos 4 caracteres.';
	exit;
}

$obj = new conectar();
$conexion = $obj->conexion();

$nombre   = mysqli_real_escape_string($conexion, $nombre);
$apellido = mysqli_real_escape_string($conexion, $apellido);
$email    = mysqli_real_escape_string($conexion, $email);

// Verificar si el usuario ya está registrado
$sqlCheck = "SELECT id_usuario FROM usuarios WHERE email = '$email' LIMIT 1";
$resCheck = mysqli_query($conexion, $sqlCheck);
if ($resCheck && mysqli_num_rows($resCheck) > 0) {
	echo 'Este usuario ya está registrado. Elige otro nombre de usuario.';
	exit;
}

// Contraseña con el mismo hash que usa el login (sha1)
$passwordHash = sha1($password);

$usuario = new usuarios();
$datos = array($nombre, $apellido, $email, $passwordHash, $id_hotel);
$ok = $usuario->registroUsuario($datos);

if ($ok) {
	echo '1';
} else {
	echo 'No se pudo guardar el usuario. Revisa la base de datos.';
}
