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

<h4>Agregar Nuevo Departamento</h4>
<div class="row">
	<div class="col-sm-6">
		<form id="frmAgregarDepartamento">
			<input type="hidden" id="idHotelContextDepartamento" name="id_hotel_context" value="<?php echo $id_hotel !== null ? (int)$id_hotel : ''; ?>">
			<div class="row">
				<div class="col-sm-12">
					<label>Nombre del Departamento</label>
					<input type="text" class="form-control input-sm" id="nombreDepartamento" name="nombreDepartamento" placeholder="Ej: Cocina y Restaurante" required>
				</div>
			</div>
			
			<div class="row">
				<div class="col-sm-12">
					<label>Descripción</label>
					<textarea class="form-control input-sm" id="descripcionDepartamento" name="descripcionDepartamento" rows="3" placeholder="Descripción del departamento"></textarea>
				</div>
			</div>

			<p></p>
			<span class="btn btn-primary" id="btnAgregarDepartamento">Agregar Departamento</span>
		</form>
	</div>
	<div class="col-sm-6">
		<div id="departamentosExistentes">
			<h5>Departamentos Existentes</h5>
			<?php
			while ($departamento=mysqli_fetch_row($result)):
				$nombre = isset($departamento[1]) && $departamento[1] !== '' ? $departamento[1] : ('Departamento #' . (int) $departamento[0]);
				?>
				<div class="panel panel-default">
					<div class="panel-heading"><?php echo htmlspecialchars($nombre); ?></div>
					<div class="panel-body">
						<p><strong>Descripción:</strong> <?php echo isset($departamento[2]) && $departamento[2] ? $departamento[2] : 'Sin descripción'; ?></p>
					</div>
				</div>
			<?php endwhile; ?>
		</div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){
		$('#btnAgregarDepartamento').click(function(){
			vacios=validarFormVacio('frmAgregarDepartamento');

			if(vacios > 0){
				alertify.alert("Debes llenar todos los campos!!");
				return false;
			}

			datos=$('#frmAgregarDepartamento').serialize();
			$.ajax({
				type:"POST",
				data:datos,
				url:"../procesos/departamentos/agregarDepartamento.php",
				success:function(r){
					if(r==1){
						$('#frmAgregarDepartamento')[0].reset();
						alertify.success("Departamento agregado con éxito");
						// Recargar la lista de departamentos existentes
						var qh = ($('#idHotelContextDepartamento').val() ? ('?id_hotel=' + $('#idHotelContextDepartamento').val()) : '');
						$('#departamentosExistentes').load('departamentos/departamentosExistentes.php' + qh);
					}else{
						alertify.error("Error al agregar el departamento");
					}
				}
			});
		});
	});
</script>
