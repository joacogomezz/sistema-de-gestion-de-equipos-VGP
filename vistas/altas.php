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
	<style>
		:root {
			--villa-dark: #1a1a1a;
			--villa-darker: #0f0f0f;
			--villa-light: #2a2a2a;
			--villa-white: #ffffff;
			--villa-gold: #c29a4b;
			--villa-gold-hover: #d4a85c;
			--villa-orange: #a0522d;
			--villa-orange-hover: #b05a35;
			--villa-text: #ffffff;
			--villa-text-muted: #cccccc;
			--villa-border: #333333;
		}

		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			background: linear-gradient(135deg, var(--villa-dark) 0%, var(--villa-darker) 100%);
			min-height: 100vh;
			color: var(--villa-text);
		}

		.jumbotron {
			background: var(--villa-light) !important;
			border: 2px solid var(--villa-border) !important;
			border-radius: 15px !important;
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
		}

		.jumbotron h1, .jumbotron h2, .jumbotron p {
			color: var(--villa-gold) !important;
		}

		.panel {
			background: var(--villa-light) !important;
			border: 2px solid var(--villa-border) !important;
			border-radius: 10px !important;
			box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3) !important;
		}

		.panel-heading {
			background: var(--villa-gold) !important;
			border-color: var(--villa-border) !important;
			color: var(--villa-white) !important;
			border-radius: 8px 8px 0 0 !important;
		}

		.panel-body {
			background: var(--villa-light) !important;
			color: var(--villa-text) !important;
		}

		.btn {
			margin-right: 10px;
			margin-bottom: 10px;
		}
	</style>
	<link rel="stylesheet" href="../librerias/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="../css/menu.css">
	<link rel="stylesheet" href="../css/villa-group-theme.css">
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
