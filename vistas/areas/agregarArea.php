<?php
session_start();
require_once "../../clases/Conexion.php";
require_once "../../clases/Areas.php";
$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$id_hotel = $es_super_admin
	? ((isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : null)
	: (isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : null);
$obj = new Areas();
$result = $obj->obtenerTodos($id_hotel);
?>

<h4>Agregar Nueva Área</h4>
<div class="row">
	<div class="col-sm-6">
		<form id="frmAgregarArea">
			<input type="hidden" id="idHotelContextArea" name="id_hotel_context" value="<?php echo $id_hotel !== null ? (int)$id_hotel : ''; ?>">
			<div class="row">
				<div class="col-sm-12">
					<label>Nombre del Área</label>
					<input type="text" class="form-control input-sm" id="nombreArea" name="nombreArea" placeholder="Ej: Recepción, Habitaciones" required>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<label>Descripción</label>
					<textarea class="form-control input-sm" id="descripcionArea" name="descripcionArea" rows="2" placeholder="Descripción opcional"></textarea>
				</div>
			</div>
			<p></p>
			<span class="btn btn-primary" id="btnAgregarArea">Agregar Área</span>
		</form>
	</div>
	<div class="col-sm-6">
		<h5>Áreas existentes</h5>
		<?php while ($a = mysqli_fetch_row($result)): ?>
		<div class="panel panel-default">
			<div class="panel-heading"><?php echo htmlspecialchars($a[1]); ?></div>
			<div class="panel-body">
				<p><?php echo $a[2] ? htmlspecialchars($a[2]) : 'Sin descripción'; ?></p>
			</div>
		</div>
		<?php endwhile; ?>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){
		$('#btnAgregarArea').click(function(){
			if (!$('#nombreArea').val().trim()) {
				alertify.alert("Debes ingresar el nombre del área.");
				return;
			}
			$.ajax({
				type: "POST",
				data: $('#frmAgregarArea').serialize(),
				url: "../procesos/areas/agregarArea.php",
				success: function(r){
					if (r == '1') {
						$('#frmAgregarArea')[0].reset();
						alertify.success("Área agregada con éxito");
						var qh = ($('#idHotelContextArea').val() ? ('?id_hotel=' + $('#idHotelContextArea').val()) : '');
						$('#agregarArea').load('areas/agregarArea.php' + qh);
					} else {
						alertify.error("Error al agregar el área");
					}
				}
			});
		});
	});
</script>
