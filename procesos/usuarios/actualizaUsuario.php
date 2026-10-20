<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Usuarios.php";

	$obj= new usuarios;

	$datos=array(
			$_POST['idUsuario'],  
		    $_POST['nombreU'] , 
		    $_POST['apellidoU'],  
		    $_POST['usuarioU'],
		    isset($_POST['passwordU']) ? trim($_POST['passwordU']) : ''
				);  
	
	$resultado = $obj->actualizaUsuario($datos);
	
	// Si la actualización fue exitosa y es el usuario actual, actualizar la sesión
	if($resultado == 1 && $_POST['idUsuario'] == $_SESSION['iduser']){
		$_SESSION['usuario'] = $_POST['usuarioU'];
		$_SESSION['nombre'] = $_POST['nombreU'];
		$_SESSION['apellido'] = $_POST['apellidoU'];
	}
	
	echo $resultado;

 ?>