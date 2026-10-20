<?php
require_once "../seguridad.php";
requerirAutenticacion();
require_once "../clases/Hoteles.php";
$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$hotel_ctx = ($es_super_admin && isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : null;
$objHoteles = new Hoteles();
$listaHoteles = $es_super_admin ? $objHoteles->obtenerTodos() : false;
$titulo_pagina = 'Áreas - Hotel Villa Group';
$estilos_extra = '<style type="text/css">
#jumbotron-areas { background: #2a2a2a !important; background-color: #2a2a2a !important; border: 2px solid #333; border-radius: 10px; color: #fff !important; }
#jumbotron-areas h1 { color: #ffffff !important; }
#jumbotron-areas p { color: #cccccc !important; }
</style>';
require_once "menu.php";
?>

<div class="container">
	<div class="jumbotron" id="jumbotron-areas">
		<h1>Áreas del Hotel</h1>
		<p>Administre las áreas disponibles para la solicitud de alta de equipos (ej. Recepción, Habitaciones).</p>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading"><h3 class="panel-title">Áreas</h3></div>
		<div class="panel-body">
			<?php if ($es_super_admin && $listaHoteles): ?>
			<div class="row" style="margin-bottom: 15px;">
				<div class="col-sm-12">
					<label style="margin-right:8px;">Ver áreas de:</label>
					<div class="btn-group" role="group">
						<?php while ($h = mysqli_fetch_row($listaHoteles)): $hid = (int)$h[0]; ?>
							<button type="button" class="btn <?php echo ($hotel_ctx === $hid) ? 'btn-warning' : 'btn-default'; ?> btn-sm" onclick="seleccionarHotelAreas(<?php echo $hid; ?>)">
								<?php echo htmlspecialchars($h[1]); ?>
							</button>
						<?php endwhile; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>
			<button type="button" class="btn btn-primary" id="agregarAreaBtn"><span class="glyphicon glyphicon-plus"></span> Agregar Área</button>
			<button type="button" class="btn btn-success" id="verAreasBtn"><span class="glyphicon glyphicon-list"></span> Ver Áreas</button>
			<br><br>
			<div id="agregarArea"></div>
			<div id="verAreas"></div>
		</div>
	</div>
</div>
<script>
var esSuperAdminAreas = <?php echo $es_super_admin ? 'true' : 'false'; ?>;
var idHotelAreasCtx = <?php echo $hotel_ctx !== null ? (int)$hotel_ctx : 'null'; ?>;
function queryHotelAreas() { return idHotelAreasCtx ? ('?id_hotel=' + parseInt(idHotelAreasCtx, 10)) : ''; }
function seleccionarHotelAreas(idHotel) { window.location.href = 'areas.php?id_hotel=' + parseInt(idHotel, 10); }
$(document).ready(function(){
	$('#agregarAreaBtn').click(function(){ if (esSuperAdminAreas && !idHotelAreasCtx) { alertify.alert('Selecciona primero un hotel para gestionar áreas.'); return; } $('#verAreas').hide(); $('#agregarArea').load('areas/agregarArea.php' + queryHotelAreas()).show(); });
	$('#verAreasBtn').click(function(){ if (esSuperAdminAreas && !idHotelAreasCtx) { alertify.alert('Selecciona primero un hotel para ver áreas.'); return; } $('#agregarArea').hide(); $('#verAreas').load('areas/tablaAreas.php' + queryHotelAreas()).show(); });
});
</script>
</div></body></html>
