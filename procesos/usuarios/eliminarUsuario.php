<?php
require_once "../../seguridad.php";
requerirAutenticacion();
require_once "../../clases/Conexion.php";
require_once "../../clases/Usuarios.php";

if (!isset($_SESSION['iduser']) || (int)$_SESSION['iduser'] !== 1) {
	echo 'SIN_PERMISO';
	exit;
}

if (!isset($_POST['idusuario'])) {
	echo 'ID_INVALIDO';
	exit;
}

$idUsuarioEliminar = (int)$_POST['idusuario'];
$idUsuarioSesion = isset($_SESSION['iduser']) ? (int)$_SESSION['iduser'] : 0;

if ($idUsuarioEliminar <= 0) {
	echo 'ID_INVALIDO';
	exit;
}

if ($idUsuarioEliminar === 1) {
	echo 'SUPER_ADMIN_PROTEGIDO';
	exit;
}

if ($idUsuarioEliminar === $idUsuarioSesion) {
	echo 'NO_AUTO_ELIMINAR';
	exit;
}

$obj = new usuarios();
$resultado = $obj->eliminaUsuario($idUsuarioEliminar);
echo $resultado;
?>