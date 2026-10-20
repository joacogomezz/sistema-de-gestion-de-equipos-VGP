<?php 
	// Incluir archivo de seguridad
	require_once "../seguridad.php";
	
	// Cerrar sesión de forma segura usando la función del archivo de seguridad
	cerrarSesion();
	
	// Redirigir a login
	header("Location: ../index.php?mensaje=sesion_cerrada");
	exit();

 ?>