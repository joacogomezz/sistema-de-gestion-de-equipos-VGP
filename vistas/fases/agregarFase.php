<?php
session_start();
require_once "../../clases/Conexion.php";
require_once "../../clases/Fases.php";
$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$id_hotel = $es_super_admin
	? ((isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : null)
	: (isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : null);
$obj = new Fases();
$result = $obj->obtenerTodos($id_hotel);
?>

<h4>Agregar Nueva Fase</h4>
<div class="row">
	<div class="col-sm-6">
		<form id="frmAgregarFase">
			<input type="hidden" id="idHotelContextFase" name="id_hotel_context" value="<?php echo $id_hotel !== null ? (int)$id_hotel : ''; ?>">
			<div class="row">
				<div class="col-sm-12">
					<label>Nombre de la Fase</label>
					<input type="text" class="form-control input-sm" id="nombreFase" name="nombreFase" placeholder="Ej: Fase 1, Torre A" required>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<label>Descripción</label>
					<textarea class="form-control input-sm" id="descripcionFase" name="descripcionFase" rows="2" placeholder="Descripción opcional"></textarea>
				</div>
			</div>
			<p></p>
			<span class="btn btn-primary" id="btnAgregarFase">Agregar Fase</span>
		</form>
	</div>
	<div class="col-sm-6">
		<h5>Fases existentes</h5>
		<?php while ($f = mysqli_fetch_row($result)): ?>
		<div class="panel panel-default">
			<div class="panel-heading"><?php echo htmlspecialchars($f[1]); ?></div>
			<div class="panel-body">
				<p><?php echo $f[2] ? htmlspecialchars($f[2]) : 'Sin descripción'; ?></p>
			</div>
		</div>
		<?php endwhile; ?>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){
		$('#btnAgregarFase').click(function(){
			if (!$('#nombreFase').val().trim()) {
				alertify.alert("Debes ingresar el nombre de la fase.");
				return;
			}
			$.ajax({
				type: "POST",
				data: $('#frmAgregarFase').serialize(),
				url: "../procesos/fases/agregarFase.php",
				success: function(r){
					if (r == '1') {
						$('#frmAgregarFase')[0].reset();
						alertify.success("Fase agregada con éxito");
						var qh = ($('#idHotelContextFase').val() ? ('?id_hotel=' + $('#idHotelContextFase').val()) : '');
						$('#agregarFase').load('fases/agregarFase.php' + qh);
					} else {
						alertify.error("Error al agregar la fase");
					}
				}
			});
		});
	});
</script>
