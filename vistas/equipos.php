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
	<title>Gestión de Equipos - Hotel Villa Group</title>
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

		.btn-primary {
			background: var(--villa-gold) !important;
			border: 2px solid var(--villa-gold) !important;
			color: var(--villa-white) !important;
			border-radius: 8px !important;
			font-weight: bold !important;
		}

		.btn-primary:hover {
			background: var(--villa-gold-hover) !important;
			border-color: var(--villa-gold-hover) !important;
			transform: translateY(-2px) !important;
			box-shadow: 0 5px 15px rgba(194, 154, 75, 0.4) !important;
		}

		.btn-success {
			background: var(--villa-orange) !important;
			border: 2px solid var(--villa-orange) !important;
			color: var(--villa-white) !important;
			border-radius: 8px !important;
			font-weight: bold !important;
		}

		.btn-success:hover {
			background: var(--villa-orange-hover) !important;
			border-color: var(--villa-orange-hover) !important;
			transform: translateY(-2px) !important;
			box-shadow: 0 5px 15px rgba(160, 82, 45, 0.4) !important;
		}

		/* Aprovecha más ancho en la pantalla de equipos */
		.container.container-equipos {
			width: 99% !important;
			max-width: none !important;
			box-sizing: border-box;
			padding-left: 4px;
			padding-right: 4px;
		}
		.container.container-equipos .row {
			margin-left: 0;
			margin-right: 0;
		}
		.container.container-equipos .col-sm-12 {
			padding-left: 4px;
			padding-right: 4px;
		}
	</style>
</head>
<body>
	<?php require_once "menu.php"; ?>

	<div class="container container-equipos">
		<div class="jumbotron">
			<h1>Gestión de Equipos</h1>
			<h2>Sistema de Gestión de Equipos - Hotel Villa Group</h2>
			<p>Administra todos los equipos del sistema.</p>
		</div>

		<div class="row">
			<div class="col-sm-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Equipos del Sistema</h3>
					</div>
					<div class="panel-body">
						<button type="button" class="btn btn-primary" id="agregarEquipoBtn">
							<span class="glyphicon glyphicon-plus"></span> Agregar Equipo
						</button>
						<button type="button" class="btn btn-success" id="verEquiposBtn">
							<span class="glyphicon glyphicon-list"></span> Ver Equipos
						</button>
						<br><br>
						<div id="agregarEquipo"></div>
						<div id="verEquipos"></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		$(document).ready(function(){
			$('#agregarEquipoBtn').click(function(){
				esconderSeccionEquipos();
				$('#agregarEquipo').load('equipos/agregarEquipo.php');
				$('#agregarEquipo').show();
			});
			$('#verEquiposBtn').click(function(){
				esconderSeccionEquipos();
				$('#verEquipos').load('equipos/tablaEquipos.php');
				$('#verEquipos').show();
			});

			// Delegación (un solo listener): evita handlers duplicados cada vez que se recarga tablaEquipos.php
			$(document).on('change', '#imagenEquipoU', function(){
				var file = this.files && this.files[0];
				if (file && file.size > 5 * 1024 * 1024) {
					alertify.error("El archivo es demasiado grande. Máximo 5MB.");
					this.value = '';
					return;
				}
				if (file && file.type && file.type.indexOf('image/') === 0) {
					var reader = new FileReader();
					reader.onload = function(ev) {
						$('#imgEquipoActual').off('error load').attr('src', ev.target.result).show();
						$('#sinImagenActual').hide();
					};
					reader.readAsDataURL(file);
				}
			});

			$(document).on('click', '#btnActualizarEquipo', function(){
				var $form = $('#frmActualizarEquipo');
				if (!$form.length) return false;
				var fd = new FormData($form[0]);
				$.ajax({
					type: 'POST',
					url: '../procesos/equipos/actualizaEquipo.php',
					data: fd,
					processData: false,
					contentType: false,
					cache: false,
					dataType: 'text',
					success: function(r){
						var texto = (r === null || r === undefined) ? '' : String(r).trim();
						if (texto === '1') {
							var $modal = $('#abremodalUpdateEquipo');
							var idHotel = $('#equiposIdHotel').length ? $('#equiposIdHotel').data('id-hotel') : null;
							$modal.one('hidden.bs.modal', function () {
								alertify.success("Equipo actualizado con éxito");
								var urlRecarga = idHotel ? ('equipos/tablaEquipos.php?id_hotel=' + idHotel) : 'equipos/tablaEquipos.php';
								$('#verEquipos').load(urlRecarga);
							});
							$modal.modal('hide');
						} else {
							alertify.error("Error al actualizar el equipo: " + texto);
						}
					},
					error: function(){
						alertify.error("Error de conexión al actualizar el equipo");
					}
				});
				return false;
			});
		});

		function esconderSeccionEquipos(){
			$('#agregarEquipo').hide();
			$('#verEquipos').hide();
		}
	</script>
</body>
</html>

<?php 
	// El archivo de seguridad ya maneja la redirección automáticamente
?>
