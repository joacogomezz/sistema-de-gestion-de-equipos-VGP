<?php 
	require_once "../../clases/Conexion.php";
	require_once "../../clases/AltasBajas.php";
	$c= new conectar();
	$conexion=$c->conexion();
?>

<h4>Registrar Baja Individual de Equipo</h4>
<div class="row">
	<div class="col-sm-8">
		<form id="frmRegistrarBaja">
			<div class="row">
				<div class="col-sm-12">
					<label>Código del Equipo *</label>
					<div class="input-group" style="position: relative;">
						<span class="input-group-addon">
							<span class="glyphicon glyphicon-search"></span>
						</span>
						<input type="text" class="form-control input-sm" id="codigoEquipoBaja" name="codigoEquipoBaja" 
							   placeholder="Ingresa el código del equipo (ej: 39205)" required 
							   autocomplete="off" 
							   autocorrect="off" 
							   autocapitalize="off" 
							   spellcheck="false"
							   data-lpignore="true">
						<input type="hidden" id="equipoBaja" name="equipoBaja" value="">
						<div id="sugerenciasEquipos" class="list-group" style="display: none; max-height: 200px; overflow-y: auto; position: absolute; top: 100%; left: 0; right: 0; z-index: 1000; "></div>
					</div>
					<small class="text-muted">Escribe el código del equipo para buscarlo automáticamente</small>
				</div>
			</div>
			
			<div class="row">
				<div class="col-sm-6">
					<label>Motivo de la Baja *</label>
					<select class="form-control input-sm" id="motivoBaja" name="motivoBaja" required>
						<option value="">Selecciona motivo</option>
						<option value="Equipo Obsoleto">Equipo Obsoleto</option>
						<option value="Falla Irreparable">Falla Irreparable</option>
						<option value="Reemplazo por Nuevo">Reemplazo por Nuevo</option>
						<option value="Venta">Venta</option>
						<option value="Donación">Donación</option>
						<option value="Robo">Robo</option>
						<option value="Otro">Otro</option>
					</select>
				</div>
				<div class="col-sm-6">
					<label>Valor Residual *</label>
					<div class="input-group">
						<span class="input-group-addon" id="monedaSimbolo">$</span>
						<input type="number" step="0.01" class="form-control input-sm" id="valorResidual" name="valorResidual" placeholder="0.00" required>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-12">
					<label>Destino del Equipo *</label>
					<select class="form-control input-sm" id="destinoEquipo" name="destinoEquipo" required>
						<option value="">Selecciona destino</option>
						<option value="Venta">Venta</option>
						<option value="Donación">Donación</option>
						<option value="Chatarra">Chatarra</option>
						<option value="Almacén">Almacén</option>
						<option value="Otro">Otro</option>
					</select>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-6">
					<label>Fecha de Adquisición</label>
					<input type="date" class="form-control input-sm" id="fechaAdquisicion" name="fechaAdquisicion">
				</div>
				<div class="col-sm-6">
					<label>&nbsp;</label>
					<div class="checkbox" style="margin-top: 10px;">
						<label><input type="checkbox" id="fechaAdquisicionNA" name="fechaAdquisicionNA" value="1"> N/A (no se conoce la fecha)</label>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-12">
					<label>Observaciones</label>
					<textarea class="form-control input-sm" id="observacionesBaja" name="observacionesBaja" rows="3" placeholder="Observaciones adicionales sobre la baja del equipo"></textarea>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-6">
					<label>Foto del Equipo</label>
					<input type="file" class="form-control input-sm" id="fotoEquipo" name="fotoEquipo" accept="image/*">
					<small class="text-muted">Formatos: JPG, PNG, GIF. Máximo 2MB</small>
					<div id="previewFotoEquipo" style="margin-top: 10px;"></div>
				</div>
				<div class="col-sm-6">
					<label>Foto de la Etiqueta</label>
					<input type="file" class="form-control input-sm" id="fotoEtiqueta" name="fotoEtiqueta" accept="image/*">
					<small class="text-muted">Formatos: JPG, PNG, GIF. Máximo 2MB</small>
					<div id="previewFotoEtiqueta" style="margin-top: 10px;"></div>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-12">
					<h4 style="color: var(--texto); font-size: 15px; font-weight: 600; margin-top: 20px; margin-bottom: 15px;">
						<span class="glyphicon glyphicon-user"></span> Responsables de la Baja
					</h4>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-6">
					<label>Técnico Revisó *</label>
					<input type="text" class="form-control input-sm" id="tecnicoReviso" name="tecnicoReviso" placeholder="Nombre del técnico" required>
				</div>
				<div class="col-sm-6">
					<label>VoBo Gerente de Área *</label>
					<input type="text" class="form-control input-sm" id="gerenteArea" name="gerenteArea" placeholder="Nombre del gerente" required>
				</div>
			</div>

			<p></p>
			<button type="submit" class="btn btn-danger btn-lg">
				<span class="glyphicon glyphicon-save"></span> Registrar Baja
			</button>
		</form>
	</div>
	<div class="col-sm-4">
		<div id="infoEquipoBaja"></div>
	</div>
</div>

<style>
	.sugerencia-equipo {
		cursor: pointer;
		padding: 8px 12px;
		border-bottom: 1px solid var(--borde);
		color: var(--texto);
	}
	.sugerencia-equipo:hover {
		background-color: var(--superficie-hundida);
		color: var(--texto);
	}
	.sugerencia-equipo:last-child {
		border-bottom: none;
	}
	#sugerenciasEquipos {
		background-color: var(--superficie);
		border: 1px solid var(--borde) !important;
		border-radius: var(--radio-md);
		box-shadow: var(--sombra-2);
	}
	#sugerenciasEquipos .list-group-item {
		border: none;
		border-radius: 0;
	}
	#sugerenciasEquipos .list-group-item:first-child {
		border-top-left-radius: 4px;
		border-top-right-radius: 4px;
	}
	#sugerenciasEquipos .list-group-item:last-child {
		border-bottom-left-radius: 4px;
		border-bottom-right-radius: 4px;
	}
</style>

<script type="text/javascript">
	$(document).ready(function(){
		var timeoutId;
		
		// Búsqueda de equipos por código
		$('#codigoEquipoBaja').on('keyup input', function(){
			var codigo = $(this).val().trim();
			console.log("Código ingresado:", codigo); // Debug
			
			// Limpiar timeout anterior
			clearTimeout(timeoutId);
			
			if(codigo.length >= 2) {
				console.log("Iniciando búsqueda para:", codigo); // Debug
				// Esperar 300ms antes de hacer la búsqueda
				timeoutId = setTimeout(function(){
					buscarEquipos(codigo);
				}, 300);
			} else {
				$('#sugerenciasEquipos').hide();
				limpiarSeleccionEquipo();
			}
		});
		
		// Ocultar sugerencias al hacer click fuera
		$(document).on('click', function(e){
			if(!$(e.target).closest('#codigoEquipoBaja, #sugerenciasEquipos').length){
				$('#sugerenciasEquipos').hide();
			}
		});
		
		// Función para buscar equipos
		function buscarEquipos(codigo) {
			console.log("Iniciando búsqueda para código:", codigo);
			
			// Mostrar indicador de carga
			$('#sugerenciasEquipos').html('<div class="list-group-item text-center"><i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Buscando equipos...</div>').show();
			
			$.ajax({
				type: "POST",
				data: "codigo=" + encodeURIComponent(codigo),
				url: "../procesos/equipos/buscarEquipos.php",
				dataType: "json",
				timeout: 10000,
				success: function(r){
					console.log("Respuesta recibida:", r);
					console.log("Tipo de respuesta:", typeof r);
					
					if(r && Array.isArray(r)) {
						if(r.length > 0) {
							console.log("Equipos encontrados:", r.length);
							mostrarSugerencias(r);
						} else {
							$('#sugerenciasEquipos').html('<div class="list-group-item text-center text-muted">No se encontraron equipos con ese código</div>').show();
						}
					} else if(r && r.error) {
						$('#sugerenciasEquipos').html('<div class="list-group-item text-center text-warning">' + r.error + '</div>').show();
					} else {
						$('#sugerenciasEquipos').html('<div class="list-group-item text-center text-muted">No se encontraron equipos con ese código</div>').show();
					}
				},
				error: function(xhr, status, error){
					console.log("Error AJAX:", {
						status: status,
						error: error,
						responseText: xhr.responseText,
						readyState: xhr.readyState
					});
					
					if(status === 'timeout') {
						$('#sugerenciasEquipos').html('<div class="list-group-item text-center text-warning">Tiempo de espera agotado</div>').show();
					} else {
						$('#sugerenciasEquipos').html('<div class="list-group-item text-center text-danger">Error de conexión: ' + error + '</div>').show();
					}
				}
			});
		}
		
		// Función para mostrar sugerencias
		function mostrarSugerencias(equipos) {
			var html = '';
			equipos.forEach(function(equipo){
				html += '<a href="#" class="list-group-item sugerencia-equipo" ' +
						'data-id="' + equipo.id_equipo + '" ' +
						'data-codigo="' + equipo.codigo_equipo + '" ' +
						'data-descripcion="' + equipo.descripcion + '" ' +
						'data-marca="' + equipo.marca + '" ' +
						'data-modelo="' + equipo.modelo + '" ' +
						'data-valor="' + equipo.valor_adquisicion + '" ' +
						'data-moneda="' + equipo.tipo_moneda + '">' +
						'<strong>' + equipo.codigo_equipo + '</strong> - ' + equipo.descripcion + 
						' (' + equipo.marca + ' ' + equipo.modelo + ')' +
						'</a>';
			});
			
			$('#sugerenciasEquipos').html(html).show();
		}
		
		// Seleccionar equipo de las sugerencias
		$(document).on('click', '.sugerencia-equipo', function(e){
			e.preventDefault();
			
			var idEquipo = $(this).data('id');
			var codigo = $(this).data('codigo');
			var descripcion = $(this).data('descripcion');
			var marca = $(this).data('marca');
			var modelo = $(this).data('modelo');
			var valor = $(this).data('valor');
			var moneda = $(this).data('moneda');
			
			// Establecer valores
			$('#equipoBaja').val(idEquipo);
			$('#codigoEquipoBaja').val(codigo);
			$('#sugerenciasEquipos').hide();
			
			// Actualizar símbolo de moneda
			var simbolo = '';
			switch(moneda) {
				case 'USD': simbolo = '$'; break;
				case 'MXN': simbolo = '$'; break;
				case 'EUR': simbolo = '€'; break;
				default: simbolo = '$';
			}
			$('#monedaSimbolo').text(simbolo);
			
			// Establecer valor residual
			$('#valorResidual').val(valor);
			
			// Mostrar información del equipo
			mostrarInfoEquipo(idEquipo);
		});
		
		// Función para mostrar información del equipo
		function mostrarInfoEquipo(idEquipo) {
			$.ajax({
				type:"POST",
				data:"idEquipo=" + idEquipo,
				url:"../procesos/equipos/obtenerEquipo.php",
				dataType: "json",
				success:function(r){
					var dato = (typeof r === 'object' && r !== null) ? r : null;
					if (!dato && typeof r === 'string' && r !== '0') {
						try { dato = JSON.parse(r); } catch (e) { dato = null; }
					}
					if(dato && dato.codigo_equipo){
						$('#infoEquipoBaja').html('<div class="panel panel-danger">' +
							'<div class="panel-heading">Información del Equipo a Dar de Baja</div>' +
							'<div class="panel-body">' +
							'<p><strong>Código:</strong> ' + dato.codigo_equipo + '</p>' +
							'<p><strong>Descripción:</strong> ' + dato.descripcion + '</p>' +
							'<p><strong>Marca:</strong> ' + dato.marca + '</p>' +
							'<p><strong>Modelo:</strong> ' + dato.modelo + '</p>' +
							'<p><strong>N° Serie:</strong> ' + dato.numero_serie + '</p>' +
							'<p><strong>Estado:</strong> ' + dato.estado + '</p>' +
							'<p><strong>Ubicación:</strong> ' + dato.ubicacion + '</p>' +
							'<p><strong>Valor Original:</strong> ' + dato.tipo_moneda + ' ' + dato.valor_adquisicion + '</p>' +
							'<p><strong>Fecha Ingreso:</strong> ' + dato.fecha_ingreso + '</p>' +
							'</div></div>');
						// Rellenar Fecha de Adquisición con la del equipo
						if (dato.fecha_ingreso) {
							$('#fechaAdquisicion').val(dato.fecha_ingreso);
							$('#fechaAdquisicionNA').prop('checked', false);
							$('#fechaAdquisicion').prop('disabled', false);
						}
					}
				},
				error: function() {
					$('#infoEquipoBaja').empty();
				}
			});
		}
		
		// Función para limpiar selección
		function limpiarSeleccionEquipo() {
			$('#equipoBaja').val('');
			$('#infoEquipoBaja').empty();
			$('#valorResidual').val('');
			$('#monedaSimbolo').text('$');
			$('#fechaAdquisicion').val('');
			$('#fechaAdquisicionNA').prop('checked', false);
			$('#fechaAdquisicion').prop('disabled', false);
		}

		// N/A Fecha de Adquisición: al marcar deshabilitar fecha; al desmarcar habilitar
		$('#fechaAdquisicionNA').on('change', function(){
			if ($(this).prop('checked')) {
				$('#fechaAdquisicion').val('').prop('disabled', true);
			} else {
				$('#fechaAdquisicion').prop('disabled', false);
			}
		});
		
		// Si el usuario escribió código pero no hizo clic en la lista, intentar resolver el equipo por código antes de validar
		function asegurarEquipoSeleccionado(callback) {
			var idEquipo = $('#equipoBaja').val();
			var codigo = $('#codigoEquipoBaja').val().trim();
			if (idEquipo !== '' && codigo !== '') {
				callback(true);
				return;
			}
			if (codigo.length < 2) {
				callback(false);
				return;
			}
			$.ajax({
				type: "POST",
				data: "codigo=" + encodeURIComponent(codigo),
				url: "../procesos/equipos/buscarEquipos.php",
				dataType: "json",
				timeout: 8000,
				success: function(r) {
					if (r && Array.isArray(r) && r.length === 1) {
						$('#equipoBaja').val(r[0].id_equipo);
						if ($('#valorResidual').val() === '' && r[0].valor_adquisicion)
							$('#valorResidual').val(r[0].valor_adquisicion);
						var moneda = (r[0].tipo_moneda || '').toUpperCase();
						var simbolo = (moneda === 'USD' || moneda === 'MXN') ? '$' : (moneda === 'EUR' ? '€' : '$');
						$('#monedaSimbolo').text(simbolo);
						mostrarInfoEquipo(r[0].id_equipo);
						callback(true);
					} else if (r && Array.isArray(r) && r.length > 1) {
						alertify.error('Varios equipos coinciden con ese código. Haz clic en uno de la lista de sugerencias.');
						$('#codigoEquipoBaja').focus();
						buscarEquipos(codigo);
						callback(false);
					} else {
						alertify.error('No se encontró un equipo con ese código. Verifica el código o selecciona uno de la lista.');
						$('#codigoEquipoBaja').focus();
						callback(false);
					}
				},
				error: function() {
					alertify.error('Error al verificar el equipo. Intenta de nuevo.');
					callback(false);
				}
			});
		}

		// Previsualización de foto del equipo
		$('#fotoEquipo').change(function(){
			var file = this.files[0];
			if(file){
				if(file.size > 2 * 1024 * 1024){ // 2MB
					alertify.error("La imagen del equipo es muy grande. Máximo 2MB");
					$(this).val('');
					$('#previewFotoEquipo').empty();
					return;
				}
				
				var reader = new FileReader();
				reader.onload = function(e){
					$('#previewFotoEquipo').html('<img src="' + e.target.result + '" style="max-width: 150px; max-height: 150px; border: 1px solid var(--borde); border-radius: var(--radio-sm);">');
				}
				reader.readAsDataURL(file);
			}
		});

		// Previsualización de foto de la etiqueta
		$('#fotoEtiqueta').change(function(){
			var file = this.files[0];
			if(file){
				if(file.size > 2 * 1024 * 1024){ // 2MB
					alertify.error("La imagen de la etiqueta es muy grande. Máximo 2MB");
					$(this).val('');
					$('#previewFotoEtiqueta').empty();
					return;
				}
				
				var reader = new FileReader();
				reader.onload = function(e){
					$('#previewFotoEtiqueta').html('<img src="' + e.target.result + '" style="max-width: 150px; max-height: 150px; border: 1px solid var(--borde); border-radius: var(--radio-sm);">');
				}
				reader.readAsDataURL(file);
			}
		});

		// Manejo del envío del formulario
		$('#frmRegistrarBaja').submit(function(e){
			e.preventDefault();
			var codigo = $('#codigoEquipoBaja').val().trim();
			if (codigo === '') {
				alertify.error('Debes ingresar el código del equipo');
				$('#codigoEquipoBaja').focus();
				return false;
			}
			// Si no hay equipo seleccionado, intentar resolver por código (un solo resultado = auto-selección)
			asegurarEquipoSeleccionado(function(ok){
				if (!ok) return;
				if ($('#equipoBaja').val() === '') {
					alertify.error('Debes seleccionar un equipo válido');
					$('#codigoEquipoBaja').focus();
					return;
				}
				var vacios = 0;
				$('#frmRegistrarBaja [required]').each(function(){
					if($(this).val() == ''){
						vacios++;
					}
				});
				if(vacios > 0){
					alertify.alert("Debes llenar todos los campos obligatorios!!");
					return;
				}
				alertify.confirm('¿Estás seguro de registrar la baja de este equipo?', function(){
					var formData = new FormData($('#frmRegistrarBaja')[0]);
					$.ajax({
						type:"POST",
						data:formData,
						url:"../procesos/bajas/registrarBaja.php",
						processData: false,
						contentType: false,
						success:function(r){
							if(r == "1" || r == 1){
								$('#frmRegistrarBaja')[0].reset();
								$('#infoEquipoBaja').empty();
								$('#previewFotoEquipo').empty();
								$('#previewFotoEtiqueta').empty();
								limpiarSeleccionEquipo();
								alertify.success("Baja registrada con éxito");
								$('#contenido').load('bajas/tablaBajas.php');
							}else{
								alertify.error("Error al registrar la baja");
							}
						},
						error: function(xhr, status, error) {
							alertify.error("Error de comunicación con el servidor");
						}
					});
				}, function(){
					alertify.error('Cancelado');
				});
			});
		});
	});
</script>
