<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Departamentos.php";
	$c= new conectar();
	$conexion=$c->conexion();
	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	$objDept = new departamentos();
	$resultDept = $objDept->obtenerTodosDepartamentos($id_hotel);
	$numDepartamentos = ($resultDept && mysqli_num_rows($resultDept) > 0) ? (int)mysqli_num_rows($resultDept) : 0;
	$requiereDepartamentos = !$es_super_admin && $id_hotel !== null;
	$bloquearAltaEquipos = $requiereDepartamentos && $numDepartamentos === 0;
	$urlDepartamentos = '../departamentos.php';
?>

<?php if ($bloquearAltaEquipos): ?>
<div class="alert alert-warning" id="alertaSinDepartamentosEquipo">
	<strong><i class="glyphicon glyphicon-warning-sign"></i> Registre departamentos primero</strong><br>
	Su hotel no tiene departamentos en el catálogo. No puede agregar ni importar equipos hasta registrarlos.
	<p style="margin-top:10px;margin-bottom:0;">
		<a href="<?php echo htmlspecialchars($urlDepartamentos); ?>" class="btn btn-sm btn-primary" target="_top">
			<i class="glyphicon glyphicon-plus"></i> Ir a Departamentos
		</a>
	</p>
</div>
<?php endif; ?>

<h4>Agregar Nuevo Equipo</h4>
<div class="row">
	<div class="col-sm-8">
		<form id="frmAgregarEquipo" enctype="multipart/form-data">
			<div class="row">
				<div class="col-sm-12">
					<label>Código del Equipo (opcional)</label>
					<input type="text" class="form-control input-sm" id="codigoEquipo" name="codigoEquipo" placeholder="Ej: CVDA20SIS. Si lo dejas vacío, el sistema lo genera automáticamente">
				</div>
			</div>
			
			<div class="row">
				<div class="col-sm-12">
					<label>Descripción del Equipo *</label>
					<textarea class="form-control input-sm" id="descripcionEquipo" name="descripcionEquipo" rows="3" placeholder="Descripción detallada del equipo" required></textarea>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-6">
					<label>Marca *</label>
					<input type="text" class="form-control input-sm" id="marcaEquipo" name="marcaEquipo" placeholder="Ej: SANSUNG" required>
				</div>
				<div class="col-sm-6">
					<label>Número de Serie *</label>
					<input type="text" class="form-control input-sm" id="numeroSerie" name="numeroSerie" placeholder="Número de serie del equipo" required>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-6">
					<label>Modelo</label>
					<input type="text" class="form-control input-sm" id="modeloEquipo" name="modeloEquipo" placeholder="Ej: EM244A">
				</div>
				<div class="col-sm-6">
					<label>Estado *</label>
					<select class="form-control input-sm" id="estadoEquipo" name="estadoEquipo" required>
						<option value="Activo">Activo</option>
						<option value="En Mantenimiento">En Mantenimiento</option>
						<option value="En Reparación">En Reparación</option>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-12">
					<label>DEPARTAMENTO: *</label>
					<select class="form-control input-sm" id="ubicacionEquipo" name="ubicacionEquipo" required>
						<option value="">Selecciona un departamento</option>
						<?php
						while ($departamento=mysqli_fetch_row($resultDept)):
							?>
							<option value="<?php echo $departamento[0]; ?>"><?php echo htmlspecialchars($departamento[1]); ?></option>
						<?php endwhile; ?>
					</select>
					<small class="text-muted">Departamento al que pertenece el equipo</small>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-12">
					<label>Ubicación Física (Dónde se colocará) *</label>
					<input type="text" class="form-control input-sm" id="ubicacionFisica" name="ubicacionFisica" placeholder="Ej: Mostrador recepción, área de bar, cuarto de rack, habitación..." required>
					<small class="text-muted">Lugar concreto dentro del hotel donde se ubicará el equipo</small>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-6">
					<label>Costo *</label>
					<input type="number" step="0.01" class="form-control input-sm" id="valorEquipo" name="valorEquipo" placeholder="0.00" required>
				</div>
				<div class="col-sm-6">
					<label>Tipo de Moneda *</label>
					<select class="form-control input-sm" id="tipoMoneda" name="tipoMoneda" required>
						<option value="USD">USD - Dólar Estadounidense</option>
						<option value="MXN">MXN - Peso Mexicano</option>
						<option value="EUR">EUR - Euro</option>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-6">
					<label>Fecha de Ingreso *</label>
					<input type="date" class="form-control input-sm" id="fechaIngreso" name="fechaIngreso" required>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-6">
					<label>Usuario</label>
					<input type="text" class="form-control input-sm" id="usuarioRegistroEquipo" name="usuarioRegistroEquipo" placeholder="Opcional" maxlength="200" autocomplete="off">
				</div>
				<div class="col-sm-6">
					<label>No. factura</label>
					<input type="text" class="form-control input-sm" id="numeroFacturaEquipo" name="numeroFacturaEquipo" placeholder="Opcional" maxlength="120" autocomplete="off">
				</div>
			</div>

			<div class="row">
				<div class="col-sm-12">
					<label>Foto del Equipo *</label>
					<input type="file" id="imagenEquipo" name="imagenEquipo" accept="image/*" required>
					<small class="text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 5MB</small>
				</div>
			</div>

			<p></p>
			<span class="btn btn-primary<?php echo $bloquearAltaEquipos ? ' disabled' : ''; ?>" id="btnAgregarEquipo"<?php echo $bloquearAltaEquipos ? ' aria-disabled="true"' : ''; ?>>Agregar Equipo</span>
		</form>
	</div>
	<div class="col-sm-4">
		<div id="imgPreview"></div>
	</div>
</div>

<hr>
<div class="row">
	<div class="col-sm-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h5 style="margin:0;"><i class="glyphicon glyphicon-import"></i> Carga Masiva de Equipos (CSV de Excel)</h5>
			</div>
			<div class="panel-body">
				<form id="frmImportarEquiposCsv" enctype="multipart/form-data">
					<div class="row">
						<div class="col-sm-8">
							<label>Archivo CSV</label>
							<input type="file" class="form-control input-sm" id="archivoEquiposCsv" name="archivoEquipos" accept=".csv">
							<small class="text-muted">
								Columnas recomendadas: codigo, descripcion, marca, modelo, numero_serie, estado, <strong>departamento (obligatorio)</strong>, ubicacion_fisica, fecha_ingreso, valor, tipo_moneda, usuario (o usuario_registro), numero_factura.
								El departamento debe existir previamente en su hotel (nombre exacto o id_departamento).
							</small>
						</div>
						<div class="col-sm-4">
							<label>&nbsp;</label><br>
							<button type="button" class="btn btn-info<?php echo $bloquearAltaEquipos ? ' disabled' : ''; ?>" id="btnImportarEquiposCsv"<?php echo $bloquearAltaEquipos ? ' disabled' : ''; ?>>
								<i class="glyphicon glyphicon-upload"></i> Importar CSV
							</button>
						</div>
					</div>
				</form>
				<div id="resultadoImportacionCsv" style="margin-top:10px;"></div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	var bloquearAltaEquiposSinDept = <?php echo $bloquearAltaEquipos ? 'true' : 'false'; ?>;
	var urlDepartamentosEquipo = <?php echo json_encode($urlDepartamentos); ?>;

	function mensajeRegistrarDepartamentosPrimero() {
		alertify.alert(
			'Departamentos requeridos',
			'Su hotel no tiene departamentos registrados. Debe registrarlos primero en el menú <strong>Departamentos</strong> antes de agregar o importar equipos.',
			function() {
				if (urlDepartamentosEquipo) {
					window.top.location.href = urlDepartamentosEquipo;
				}
			}
		);
	}

	$(document).ready(function(){
		// Establecer fecha de ingreso por defecto como hoy
		var today = new Date().toISOString().split('T')[0];
		$('#fechaIngreso').val(today);
		
		// Preview de imagen
		$('#imagenEquipo').change(function(){
			var file = this.files[0];
			if (file) {
				// Validar tamaño del archivo (5MB máximo)
				if (file.size > 5 * 1024 * 1024) {
					alertify.error("El archivo es demasiado grande. Máximo 5MB permitido.");
					this.value = '';
					$('#imgPreview').empty();
					return;
				}
				
				var reader = new FileReader();
				reader.onload = function(e) {
					$('#imgPreview').html('<img src="' + e.target.result + '" class="img-thumbnail" style="max-width: 200px;">');
				}
				reader.readAsDataURL(file);
			}
		});

		$('#btnAgregarEquipo').click(function(){
			if (bloquearAltaEquiposSinDept) {
				mensajeRegistrarDepartamentosPrimero();
				return false;
			}
			console.log("Botón clickeado");
			
			vacios=validarFormVacioConArchivos('frmAgregarEquipo');
			console.log("Campos vacíos:", vacios);

			if(vacios > 0){
				alertify.alert("Debes llenar todos los campos obligatorios (marcados con *)");
				return false;
			}

			var formData = new FormData(document.getElementById('frmAgregarEquipo'));
			formData.append('accion', 'agregar');
			
			// Debug: mostrar datos del formulario
			console.log("Datos del formulario:");
			for (var pair of formData.entries()) {
				console.log(pair[0] + ': ' + pair[1]);
			}

			$.ajax({
				type:"POST",
				data:formData,
				url:"../procesos/equipos/insertaEquipo.php",
				contentType: false,
				processData: false,
				success:function(r){
					console.log("Respuesta del servidor:", r);
					var resp = (r || '').toString().trim();
					if(resp === '1'){
						$('#frmAgregarEquipo')[0].reset();
						$('#imgPreview').empty();
						$('#fechaIngreso').val(today);
						alertify.success("Equipo agregado con éxito");
					} else if (resp === 'SIN_DEPARTAMENTOS_HOTEL' || resp === 'SIN_DEPARTAMENTO') {
						mensajeRegistrarDepartamentosPrimero();
					} else {
						alertify.error(resp.indexOf('departamento') !== -1 ? resp : ("Error al agregar el equipo. " + resp));
					}
				},
				error: function(xhr, status, error) {
					console.log("Error AJAX:", error);
					console.log("Status:", status);
					console.log("Response:", xhr.responseText);
					alertify.error("Error en la comunicación con el servidor");
				}
			});
		});

		$('#btnImportarEquiposCsv').click(function(){
			if (bloquearAltaEquiposSinDept) {
				mensajeRegistrarDepartamentosPrimero();
				return false;
			}
			var input = document.getElementById('archivoEquiposCsv');
			if (!input || !input.files || !input.files[0]) {
				alertify.alert("Selecciona un archivo CSV para importar");
				return false;
			}

			var formDataCsv = new FormData(document.getElementById('frmImportarEquiposCsv'));
			$.ajax({
				type: "POST",
				url: "../procesos/equipos/importarEquiposCsv.php",
				data: formDataCsv,
				contentType: false,
				processData: false,
				dataType: "json",
				success: function(r){
					if (r && r.codigo === 'SIN_DEPARTAMENTOS_HOTEL') {
						alertify.alert('Departamentos requeridos', r.mensaje || 'Registre primero sus departamentos.', function() {
							var u = r.url_departamentos || urlDepartamentosEquipo;
							if (u) { window.top.location.href = u; }
						});
						$('#resultadoImportacionCsv').html('<div class="alert alert-warning">' + (r.mensaje || '') + '</div>');
						return;
					}
					if (r && r.ok) {
						var insertados = (typeof r.insertados !== 'undefined') ? parseInt(r.insertados, 10) : 0;
						var errores = (r.errores && r.errores.length) ? r.errores.length : 0;
						var html = '';

						if (insertados > 0 && errores === 0) {
							alertify.success(r.mensaje || "Importacion completada");
							html = '<div class="alert alert-success">' + (r.mensaje || '') + '</div>';
						} else if (insertados > 0 && errores > 0) {
							alertify.warning('Importación parcial: se insertaron ' + insertados + ' y hubo ' + errores + ' errores.');
							html = '<div class="alert alert-warning">' + (r.mensaje || '') + '</div>';
						} else {
							alertify.error('No se insertó ningún equipo. Revisa los errores del archivo.');
							html = '<div class="alert alert-danger">No se insertó ningún equipo. Revisa los errores del archivo.</div>';
						}

						if (r.errores && r.errores.length > 0) {
							html += '<div class="alert alert-warning"><strong>Filas con error:</strong><ul>';
							for (var i = 0; i < r.errores.length; i++) {
								html += '<li>' + r.errores[i] + '</li>';
							}
							html += '</ul></div>';
						}
						$('#resultadoImportacionCsv').html(html);
					} else {
						alertify.error((r && r.mensaje) ? r.mensaje : "No se pudo importar el archivo");
					}
				},
				error: function(xhr){
					alertify.error("Error al importar el CSV");
				}
			});
		});
	});
</script>
