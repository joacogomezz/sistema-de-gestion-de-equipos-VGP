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

<h4>Áreas registradas</h4>
<div class="table-responsive">
	<table class="table table-hover table-condensed table-bordered" style="text-align: center;">
		<tr>
			<td><strong>Nº</strong></td>
			<td><strong>Nombre</strong></td>
			<td><strong>Descripción</strong></td>
			<td><strong>Editar</strong></td>
			<td><strong>Eliminar</strong></td>
		</tr>
		<?php $n = 0; while ($ver = mysqli_fetch_row($result)): $n++; ?>
		<tr>
			<td><?php echo $n; ?></td>
			<td><?php echo htmlspecialchars($ver[1]); ?></td>
			<td><?php echo $ver[2] ? htmlspecialchars($ver[2]) : '—'; ?></td>
			<td>
				<span class="btn btn-warning btn-xs" data-toggle="modal" data-target="#modalEditarArea" onclick="cargarArea('<?php echo $ver[0]; ?>','<?php echo addslashes($ver[1]); ?>','<?php echo addslashes($ver[2] ?? ''); ?>')">
					<span class="glyphicon glyphicon-pencil"></span>
				</span>
			</td>
			<td>
				<span class="btn btn-danger btn-xs" onclick="eliminarArea('<?php echo $ver[0]; ?>')">
					<span class="glyphicon glyphicon-remove"></span>
				</span>
			</td>
		</tr>
		<?php endwhile; ?>
	</table>
</div>

<div class="modal fade" id="modalEditarArea" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Editar Área</h4>
			</div>
			<div class="modal-body">
				<form id="frmUpdateArea">
					<input type="hidden" id="idArea" name="idArea">
					<div class="form-group">
						<label>Nombre</label>
						<input type="text" class="form-control input-sm" id="nombreAreaU" name="nombreAreaU" required>
					</div>
					<div class="form-group">
						<label>Descripción</label>
						<textarea class="form-control input-sm" id="descripcionAreaU" name="descripcionAreaU" rows="2"></textarea>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
				<button type="button" class="btn btn-primary" onclick="actualizaArea()">Guardar</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	function cargarArea(id, nombre, descripcion) {
		$('#idArea').val(id);
		$('#nombreAreaU').val(nombre);
		$('#descripcionAreaU').val(descripcion || '');
	}
	function actualizaArea() {
		$.ajax({
			type: "POST",
			data: $('#frmUpdateArea').serialize(),
			url: "../procesos/areas/actualizaArea.php",
			success: function(r) {
				if (r == '1') {
					$('#modalEditarArea').modal('hide');
					alertify.success("Área actualizada");
					var qh = (window.idHotelAreasCtx ? ('?id_hotel=' + parseInt(window.idHotelAreasCtx, 10)) : '');
					$('#verAreas').load('areas/tablaAreas.php' + qh);
				} else {
					alertify.error("Error al actualizar");
				}
			}
		});
	}
	function eliminarArea(id) {
		alertify.confirm('¿Eliminar esta área?', function() {
			$.ajax({
				type: "POST",
				data: "idArea=" + id,
				url: "../procesos/areas/eliminarArea.php",
				success: function(r) {
					if (r == '1') {
						alertify.success("Área eliminada");
						var qh = (window.idHotelAreasCtx ? ('?id_hotel=' + parseInt(window.idHotelAreasCtx, 10)) : '');
						$('#verAreas').load('areas/tablaAreas.php' + qh);
					} else {
						alertify.error("Error al eliminar");
					}
				}
			});
		});
	}
</script>
