<?php 
	// Incluir archivo de seguridad
	require_once "../seguridad.php";
	
	// Requerir autenticación
	requerirAutenticacion();
	?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bajas Masivas - Hotel Villa Group</title>
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

		.container.container-bajas {
			width: 99% !important;
			max-width: none !important;
			box-sizing: border-box;
			padding-left: 4px;
			padding-right: 4px;
		}
		.container.container-bajas .row {
			margin-left: 0;
			margin-right: 0;
		}
		.container.container-bajas .col-sm-12 {
			padding-left: 4px;
			padding-right: 4px;
		}
	</style>
</head>
<body>
	<?php require_once "menu.php"; ?>

	<div class="container container-bajas">
		<div class="jumbotron">
			<h1>Bajas Masivas</h1>
			<h2>Sistema de Gestión de Equipos - Hotel Villa Group</h2>
			<p>Gestiona bajas masivas de equipos en el sistema.</p>
		</div>

		<div class="row">
			<div class="col-sm-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Bajas Masivas de Equipos</h3>
					</div>
					<div class="panel-body">
						<div class="row">
							<div class="col-sm-12">
								<button class="btn btn-danger" onclick="cargarRegistroBajaMasiva()">
									<span class="glyphicon glyphicon-trash"></span> Registrar Baja Masiva
								</button>
								<button class="btn btn-warning" onclick="cargarTablaBajasMasivas()">
									<span class="glyphicon glyphicon-list"></span> Ver Bajas Masivas Registradas
								</button>
							</div>
						</div>
						<br><br>
						<div id="contenido"></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		function cargarRegistroBajaMasiva(){
			$('#contenido').load('bajasMasivas/registrarBajaMasiva.php');
		}

		function cargarTablaBajasMasivas(){
			$('#contenido').load('bajasMasivas/tablaBajasMasivas.php');
		}

		// Cargar tabla de bajas masivas por defecto
		$(document).ready(function(){
			cargarTablaBajasMasivas();
		});
	</script>
</body>
</html>

<?php 
	// El archivo de seguridad ya maneja la redirección automáticamente
?>
