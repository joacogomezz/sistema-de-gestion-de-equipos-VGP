<?php
require_once "../seguridad.php";
requerirAutenticacion();
require_once "../clases/Hoteles.php";
$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$hotel_ctx = ($es_super_admin && isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : null;
$objHoteles = new Hoteles();
$listaHoteles = $es_super_admin ? $objHoteles->obtenerTodos() : false;
$titulo_pagina = 'Fases - Hotel Villa Group';
$estilos_extra = '<style type="text/css">
#jumbotron-fases { background: #2a2a2a !important; background-color: #2a2a2a !important; border: 2px solid #333; border-radius: 10px; color: #fff !important; }
#jumbotron-fases h1 { color: #ffffff !important; }
#jumbotron-fases p { color: #cccccc !important; }
</style>';
require_once "menu.php";
?>

<div class="container">
	<div class="jumbotron" id="jumbotron-fases">
		<h1>Fases del Hotel</h1>
		<p>Administre las fases disponibles para la solicitud de alta de equipos (ej. Fase 1, Torre A).</p>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading"><h3 class="panel-title">Fases</h3></div>
		<div class="panel-body">
			<?php if ($es_super_admin && $listaHoteles): ?>
			<div class="row" style="margin-bottom: 15px;">
				<div class="col-sm-12">
					<label style="margin-right:8px;">Ver fases de:</label>
					<div class="btn-group" role="group">
						<?php while ($h = mysqli_fetch_row($listaHoteles)): $hid = (int)$h[0]; ?>
							<button type="button" class="btn <?php echo ($hotel_ctx === $hid) ? 'btn-warning' : 'btn-default'; ?> btn-sm" onclick="seleccionarHotelFases(<?php echo $hid; ?>)">
								<?php echo htmlspecialchars($h[1]); ?>
							</button>
						<?php endwhile; ?>
					</div>
				</div>
			</div>
			<?php endif; ?>
			<button type="button" class="btn btn-primary" id="agregarFaseBtn"><span class="glyphicon glyphicon-plus"></span> Agregar Fase</button>
			<button type="button" class="btn btn-success" id="verFasesBtn"><span class="glyphicon glyphicon-list"></span> Ver Fases</button>
			<br><br>
			<div id="agregarFase"></div>
			<div id="verFases"></div>
		</div>
	</div>
</div>
<script>
var esSuperAdminFases = <?php echo $es_super_admin ? 'true' : 'false'; ?>;
var idHotelFasesCtx = <?php echo $hotel_ctx !== null ? (int)$hotel_ctx : 'null'; ?>;
function queryHotelFases() { return idHotelFasesCtx ? ('?id_hotel=' + parseInt(idHotelFasesCtx, 10)) : ''; }
function seleccionarHotelFases(idHotel) { window.location.href = 'fases.php?id_hotel=' + parseInt(idHotel, 10); }
$(document).ready(function(){
	$('#agregarFaseBtn').click(function(){ if (esSuperAdminFases && !idHotelFasesCtx) { alertify.alert('Selecciona primero un hotel para gestionar fases.'); return; } $('#verFases').hide(); $('#agregarFase').load('fases/agregarFase.php' + queryHotelFases()).show(); });
	$('#verFasesBtn').click(function(){ if (esSuperAdminFases && !idHotelFasesCtx) { alertify.alert('Selecciona primero un hotel para ver fases.'); return; } $('#agregarFase').hide(); $('#verFases').load('fases/tablaFases.php' + queryHotelFases()).show(); });
});
</script>
</div></body></html>
