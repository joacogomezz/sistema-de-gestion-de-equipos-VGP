<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Departamentos.php";
	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin
		? ((isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : null)
		: (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	$obj = new departamentos();
	$result = $obj->obtenerTodosDepartamentos($id_hotel);
?>

<h4>Departamentos Registrados</h4>
<div class="row">
	<div class="col-sm-12">
		<div class="table-responsive">
			<table class="table table-hover table-condensed table-bordered" style="text-align: center;">
				<caption><label>Departamentos del Hotel</label></caption>
				<tr>
					<td><strong>Nº</strong></td>
					<td><strong>Nombre</strong></td>
					<td><strong>Descripción</strong></td>
					<td><strong>Fecha Creación</strong></td>
					<td><strong>Editar</strong></td>
					<td><strong>Eliminar</strong></td>
				</tr>
				<?php 
				$numeroFila = 0;
				while($ver=mysqli_fetch_row($result)): 
					$numeroFila++;
					$fechaCreacion = isset($ver[3]) ? $ver[3] : '';
				?>
					<tr>
						<td><?php echo $numeroFila; ?></td>
						<td><?php echo $ver[1]; ?></td>
						<td><?php echo $ver[2] ? $ver[2] : 'Sin descripción'; ?></td>
						<td><?php echo $fechaCreacion; ?></td>
						<td>
							<span class="btn btn-warning btn-xs" data-toggle="modal" data-target="#abremodalUpdateDepartamento" onclick="agregaDatoDepartamento('<?php echo $ver[0]; ?>','<?php echo addslashes($ver[1]); ?>','<?php echo addslashes($ver[2]); ?>')">
								<span class="glyphicon glyphicon-pencil"></span>
							</span>
						</td>
						<td>
							<span class="btn btn-danger btn-xs" onclick="eliminarDepartamento('<?php echo $ver[0]; ?>')">
								<span class="glyphicon glyphicon-remove"></span>
							</span>
						</td>
					</tr>
				<?php endwhile; ?>
			</table>
		</div>
	</div>
</div>

<!-- Modal para editar departamento -->
<div class="modal fade" id="abremodalUpdateDepartamento" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">Actualizar Departamento</h4>
			</div>
			<div class="modal-body">
				<form id="frmUpdateDepartamento">
					<input type="hidden" id="idHotelContextDepartamentoU" name="id_hotel_context" value="<?php echo $id_hotel !== null ? (int)$id_hotel : ''; ?>">
					<input type="text" hidden="" id="idDepartamento" name="idDepartamento">
					<div class="row">
						<div class="col-sm-12">
							<label>Nombre del Departamento</label>
							<input type="text" class="form-control input-sm" id="nombreDepartamentoU" name="nombreDepartamentoU">
						</div>
					</div>
					<div class="row">
						<div class="col-sm-12">
							<label>Descripción</label>
							<textarea class="form-control input-sm" id="descripcionDepartamentoU" name="descripcionDepartamentoU" rows="3"></textarea>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
				<button type="button" class="btn btn-primary" id="btnActualizaDepartamento" onclick="actualizaDepartamento()">Actualizar</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	function agregaDatoDepartamento(idDepartamento, nombre, descripcion){
		$('#idDepartamento').val(idDepartamento);
		$('#nombreDepartamentoU').val(nombre);
		$('#descripcionDepartamentoU').val(descripcion);
	}

	function actualizaDepartamento(){
		datos=$('#frmUpdateDepartamento').serialize();
		$.ajax({
			type:"POST",
			data:datos,
			url:"../procesos/departamentos/actualizaDepartamento.php",
			success:function(r){
				if(r==1){
					var $modal = $('#abremodalUpdateDepartamento');
					$modal.one('hidden.bs.modal', function () {
						alertify.success("Departamento actualizado con éxito");
						var qh = (window.queryHotelDepartamentos ? window.queryHotelDepartamentos() : '');
						$('#verDepartamentos').load('departamentos/tablaDepartamentos.php' + qh);
					});
					$modal.modal('hide');
				}else{
					alertify.error("Error al actualizar el departamento");
				}
			}
		});
	}

	function eliminarDepartamento(idDepartamento){
		alertify.confirm('¿Desea eliminar este departamento?', function(){
			$.ajax({
				type:"POST",
				data:"idDepartamento=" + idDepartamento,
				url:"../procesos/departamentos/eliminarDepartamento.php",
				success:function(r){
					if(r==1){
						alertify.success("Departamento eliminado con éxito");
						var qh = (window.queryHotelDepartamentos ? window.queryHotelDepartamentos() : '');
						$('#verDepartamentos').load('departamentos/tablaDepartamentos.php' + qh);
					}else{
						alertify.error("Error al eliminar el departamento");
					}
				}
			});
		}, function(){
			alertify.error('Cancelado');
		});
	}
</script>
