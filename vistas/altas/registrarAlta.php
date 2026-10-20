<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/AltasBajas.php";
	require_once "../../clases/Departamentos.php";
	$c= new conectar();
	$conexion=$c->conexion();
	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	$objDept = new departamentos();
	$resultDept = $objDept->obtenerTodosDepartamentos($id_hotel);
?>

<h4>Registrar Alta de Equipo</h4>
<div class="row">
	<div class="col-sm-8">
		<form id="frmRegistrarAlta">
			<div class="row">
				<div class="col-sm-6">
					<label>Seleccionar Equipo</label>
					<select class="form-control input-sm" id="equipoAlta" name="equipoAlta">
						<option value="A">Selecciona un equipo</option>
						<?php
						$whereHotelEq = '';
						if ($id_hotel !== null) {
							$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
							if ($chk && mysqli_num_rows($chk) > 0) {
								$whereHotelEq = " INNER JOIN departamentos d ON e.id_departamento = d.id_departamento AND d.id_hotel = '" . $id_hotel . "'";
							}
						}
						$sql="SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo 
								from equipos e $whereHotelEq
								where e.estado != 'Dado de Baja'
								order by e.codigo_equipo";
						$result=mysqli_query($conexion,$sql);
						while ($equipo=mysqli_fetch_row($result)):
							?>
							<option value="<?php echo $equipo[0]; ?>">
								<?php echo htmlspecialchars($equipo[1] . ' - ' . ($equipo[2] ?: '') . ' (' . $equipo[3] . ' ' . $equipo[4] . ')'); ?>
							</option>
						<?php endwhile; ?>
					</select>
				</div>
				<div class="col-sm-6">
					<label>Departamento</label>
					<select class="form-control input-sm" id="departamentoAlta" name="departamentoAlta">
						<option value="A">Selecciona departamento</option>
						<?php
						while ($departamento=mysqli_fetch_row($resultDept)):
							?>
							<option value="<?php echo $departamento[0]; ?>"><?php echo htmlspecialchars($departamento[1]); ?></option>
						<?php endwhile; ?>
					</select>
				</div>
			</div>
			
			<div class="row">
				<div class="col-sm-12">
					<label>Motivo de la Alta</label>
					<select class="form-control input-sm" id="motivoAlta" name="motivoAlta">
						<option value="A">Selecciona motivo</option>
						<option value="Nuevo Equipo">Nuevo Equipo</option>
						<option value="Reemplazo de Equipo">Reemplazo de Equipo</option>
						<option value="Expansión de Servicios">Expansión de Servicios</option>

						<option value="Otro">Otro</option>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-12">
					<label>Observaciones</label>
					<textarea class="form-control input-sm" id="observacionesAlta" name="observacionesAlta" rows="3" placeholder="Observaciones adicionales sobre la alta del equipo"></textarea>
				</div>
			</div>

			<p></p>
			<span class="btn btn-primary" id="btnRegistrarAlta">Registrar Alta</span>
		</form>
	</div>
	<div class="col-sm-4">
		<div id="infoEquipo"></div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){
		$('#equipoAlta').change(function(){
			if($('#equipoAlta').val() != 'A'){
				$.ajax({
					type:"POST",
					data:"idEquipo=" + $('#equipoAlta').val(),
					url:"../procesos/altas/obtenerInfoEquipo.php",
					success:function(r){
						dato=jQuery.parseJSON(r);
						$('#infoEquipo').html('<div class="panel panel-info">' +
							'<div class="panel-heading">Información del Equipo</div>' +
							'<div class="panel-body">' +
							'<p><strong>Código:</strong> ' + dato.codigo_equipo + '</p>' +
							'<p><strong>Nombre:</strong> ' + dato.nombre + '</p>' +
							'<p><strong>Marca:</strong> ' + dato.marca + '</p>' +
							'<p><strong>Modelo:</strong> ' + dato.modelo + '</p>' +
							'<p><strong>Estado:</strong> ' + dato.estado + '</p>' +
							'<p><strong>Ubicación:</strong> ' + dato.ubicacion + '</p>' +
							'<p><strong>Valor:</strong> $' + dato.valor_adquisicion + '</p>' +
							'</div></div>');
					}
				});
			}else{
				$('#infoEquipo').empty();
			}
		});

		$('#btnRegistrarAlta').click(function(){
			vacios=validarFormVacio('frmRegistrarAlta');

			if(vacios > 0){
				alertify.alert("Debes llenar todos los campos!!");
				return false;
			}

			datos=$('#frmRegistrarAlta').serialize();
			$.ajax({
				type:"POST",
				data:datos,
				url:"../procesos/altas/registrarAlta.php",
				success:function(r){
					if(r==1){
						$('#frmRegistrarAlta')[0].reset();
						$('#infoEquipo').empty();
						alertify.success("Alta registrada con éxito");
					}else{
						alertify.error("Error al registrar la alta");
					}
				}
			});
		});
	});
</script>
