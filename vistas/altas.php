<?php 
	// Incluir archivo de seguridad
	require_once "../seguridad.php";
	
	// Requerir autenticación
	requerirAutenticacion();
	?>
		
 ?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Gestión de Altas - Hotel Villa Group</title>
</head>
<body>
	<?php require_once "menu.php"; ?>

	<div class="container">
		<div class="jumbotron">
			<h1>Gestión de Altas</h1>
			<h2>Sistema de Gestión de Equipos - Hotel Villa Group</h2>
			<p>Administra las altas de equipos en el sistema.</p>
		</div>

		<div class="row">
			<div class="col-sm-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Altas de Equipos</h3>
					</div>
					<div class="panel-body">
						<div class="row">
							<div class="col-sm-12">
								<button class="btn btn-primary" onclick="cargarRegistroAlta()">
									<span class="glyphicon glyphicon-plus"></span> Registrar Nueva Alta
								</button>
								<button class="btn btn-info" onclick="cargarTablaAltas()">
									<span class="glyphicon glyphicon-list"></span> Ver Altas Registradas
								</button>
							</div>
						</div>
						<br>
						<div id="contenido"></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script src="../librerias/jquery-3.2.1.min.js"></script>
	<script src="../librerias/bootstrap/js/bootstrap.min.js"></script>
	<script src="../librerias/alertifyjs/alertify.js"></script>
	<script src="../js/funciones.js"></script>

	<script type="text/javascript">
		$(document).ready(function(){
			// Cargar por defecto el formulario de registro
			$('#contenido').load('altas/registrarAlta.php');
		});

		function cargarRegistroAlta(){
			$('#contenido').load('altas/registrarAlta.php');
		}

		function cargarTablaAltas(){
			$('#contenido').load('altas/tablaAltas.php');
		}
	</script>
</body>
</html>

<?php 
	// El archivo de seguridad ya maneja la redirección automáticamente
?>
