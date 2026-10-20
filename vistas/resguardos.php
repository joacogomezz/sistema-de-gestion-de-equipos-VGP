<?php
	require_once "../seguridad.php";
	requerirAutenticacion();
	require_once "../clases/Hoteles.php";
	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$hotel_ctx = ($es_super_admin && isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : null;
	$objHoteles = new Hoteles();
	$listaHoteles = $es_super_admin ? $objHoteles->obtenerTodos() : false;

	// Usar el menú como estructura única del documento para que Bootstrap y los desplegables funcionen
	$titulo_pagina = 'Resguardos - Hotel Villa Group';
	$estilos_extra = '<style>
		:root {
			--villa-dark: #1a1a1a;
			--villa-darker: #0f0f0f;
			--villa-light: #2a2a2a;
			--villa-white: #ffffff;
			--villa-gold: #c29a4b;
			--villa-gold-hover: #d4a85c;
			--villa-orange: #a0522d;
			--villa-text: #ffffff;
			--villa-text-muted: #cccccc;
			--villa-border: #333333;
		}
		body {
			font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
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
		.jumbotron h1, .jumbotron h2, .jumbotron p { color: var(--villa-gold) !important; }
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
		.panel-body { background: var(--villa-light) !important; color: var(--villa-text) !important; }
		.btn { margin-right: 10px; margin-bottom: 10px; }
	</style>';
	require_once "menu.php";
?>

	<div class="jumbotron">
		<h1>Resguardos</h1>
		<h2>Sistema de Gestión de Equipos - Hotel Villa Group</h2>
		<p>Genere los documentos de resguardo que se entregarán a las áreas correspondientes donde irá cada equipo.</p>
	</div>

	<div class="row">
		<div class="col-sm-12">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Resguardos</h3>
				</div>
				<div class="panel-body">
					<?php if ($es_super_admin && $listaHoteles): ?>
					<div class="row" style="margin-bottom: 15px;">
						<div class="col-sm-12">
							<label style="margin-right:8px;">Ver resguardos de:</label>
							<div class="btn-group" role="group">
								<?php while ($h = mysqli_fetch_row($listaHoteles)): $hid = (int)$h[0]; ?>
									<button type="button" class="btn <?php echo ($hotel_ctx === $hid) ? 'btn-warning' : 'btn-default'; ?> btn-sm" onclick="seleccionarHotelResguardos(<?php echo $hid; ?>)">
										<?php echo htmlspecialchars($h[1]); ?>
									</button>
								<?php endwhile; ?>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<div class="row">
						<div class="col-sm-12">
							<button class="btn btn-primary" onclick="cargarGenerarResguardo()">
								<span class="glyphicon glyphicon-plus"></span> Generar Resguardo (un equipo)
							</button>
							<button class="btn btn-success" onclick="cargarResguardoMultiple()">
								<span class="glyphicon glyphicon-list-alt"></span> Resguardo múltiple (varios equipos, misma factura)
							</button>
							<button class="btn btn-info" onclick="cargarTablaResguardos()">
								<span class="glyphicon glyphicon-list"></span> Ver Resguardos Generados
							</button>
						</div>
					</div>
					<br>
					<div id="contenido"></div>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		var esSuperAdminResguardos = <?php echo $es_super_admin ? 'true' : 'false'; ?>;
		var idHotelResguardosCtx = <?php echo $hotel_ctx !== null ? (int)$hotel_ctx : 'null'; ?>;
		function queryHotelResguardos() { return idHotelResguardosCtx ? ('?id_hotel=' + parseInt(idHotelResguardosCtx, 10)) : ''; }
		function seleccionarHotelResguardos(idHotel) { window.location.href = 'resguardos.php?id_hotel=' + parseInt(idHotel, 10); }
		$(document).ready(function(){
			if (esSuperAdminResguardos && !idHotelResguardosCtx) {
				$('#contenido').html('<div class="alert alert-info">Selecciona primero un hotel para gestionar los resguardos.</div>');
				return;
			}
			$('#contenido').load('resguardos/generarResguardo.php' + queryHotelResguardos());
		});
		function cargarGenerarResguardo(){
			if (esSuperAdminResguardos && !idHotelResguardosCtx) { alertify.alert('Selecciona primero un hotel.'); return; }
			$('#contenido').load('resguardos/generarResguardo.php' + queryHotelResguardos());
		}
		function cargarResguardoMultiple(){
			if (esSuperAdminResguardos && !idHotelResguardosCtx) { alertify.alert('Selecciona primero un hotel.'); return; }
			$('#contenido').load('resguardos/generarResguardoMultiple.php' + queryHotelResguardos());
		}
		function cargarTablaResguardos(){
			if (esSuperAdminResguardos && !idHotelResguardosCtx) { alertify.alert('Selecciona primero un hotel.'); return; }
			$('#contenido').load('resguardos/tablaResguardos.php' + queryHotelResguardos());
		}
	</script>
</div><!-- cierre del container del menú -->
</body>
</html>
