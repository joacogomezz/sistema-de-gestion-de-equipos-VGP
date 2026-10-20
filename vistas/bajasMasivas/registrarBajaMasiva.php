<?php 
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/AltasBajas.php";
	$c= new conectar();
	$conexion=$c->conexion();
	$iduser = isset($_SESSION['iduser']) ? (int)$_SESSION['iduser'] : 0;
	$es_super_admin = ($iduser === 1);
	$id_hotel = isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : null;
	$nombre_hotel = isset($_SESSION['nombre_hotel']) ? trim($_SESSION['nombre_hotel']) : '';
	$hotel_auto = (!$es_super_admin && $nombre_hotel !== '') ? $nombre_hotel : '';
?>

<h4>Registrar Baja Masiva de Equipos</h4>

<!-- Panel de búsqueda y selección -->
<div class="row">
	<div class="col-sm-12">
		<div class="panel panel-primary">
			<div class="panel-heading">
				<h4><i class="glyphicon glyphicon-search"></i> Buscar y Seleccionar Equipos</h4>
			</div>
			<div class="panel-body">
				<div class="alert alert-info">
					<i class="glyphicon glyphicon-info-sign"></i>
					<strong>Información:</strong> Puedes buscar y seleccionar equipos que ya están dados de baja individual para incluirlos en el reporte de bajas masivas. Esto te permitirá generar un documento consolidado con todos los equipos dados de baja.
				</div>
				<div class="row">
					<div class="col-sm-4">
						<label>Buscar por Código:</label>
						<div class="input-group">
							<input type="text" class="form-control" id="buscarCodigo" placeholder="Ingresa el código del equipo...">
							<span class="input-group-btn">
								<button class="btn btn-primary" type="button" id="btnBuscar">
									<i class="glyphicon glyphicon-search"></i> Buscar
								</button>
							</span>
						</div>
					</div>
					<div class="col-sm-4">
						<label>Buscar por Número de Serie:</label>
						<div class="input-group">
							<input type="text" class="form-control" id="buscarDescripcion" placeholder="Ingresa el número de serie...">
							<span class="input-group-btn">
								<button class="btn btn-info" type="button" id="btnBuscarDescripcion">
									<i class="glyphicon glyphicon-search"></i> Buscar
								</button>
							</span>
						</div>
					</div>
					<div class="col-sm-4">
						<label>Acciones Rápidas:</label><br>
						<button type="button" class="btn btn-warning btn-sm" id="btnLimpiarSeleccion">
							<i class="glyphicon glyphicon-remove"></i> Limpiar Selección
						</button>
						<button type="button" class="btn btn-success btn-sm" id="btnVerSeleccionados">
							<i class="glyphicon glyphicon-list"></i> Ver Seleccionados
						</button>
					</div>
				</div>
				<div class="row" style="margin-top: 10px;">
					<div class="col-sm-12">
						<span class="label label-info" id="contadorSeleccionados">0 equipos seleccionados</span>
						<span class="label label-success" id="valorTotalSeleccionados">Total: $0.00</span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Resultados de búsqueda -->
<div class="row">
	<div class="col-sm-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h5><i class="glyphicon glyphicon-list-alt"></i> Resultados de Búsqueda</h5>
			</div>
			<div class="panel-body">
				<div id="resultadosBusqueda" style="max-height: 300px; overflow-y: auto;">
					<p class="text-muted">Ingresa un código o número de serie para buscar equipos...</p>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Equipos seleccionados -->
<div class="row">
	<div class="col-sm-12">
		<div class="panel panel-success">
			<div class="panel-heading">
				<h5><i class="glyphicon glyphicon-check"></i> Equipos Seleccionados para Baja</h5>
			</div>
			<div class="panel-body">
				<div id="equiposSeleccionados" style="max-height: 200px; overflow-y: auto;">
					<p class="text-muted">No hay equipos seleccionados...</p>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-sm-8">
		<form id="frmRegistrarBajaMasiva">
			<input type="hidden" id="equiposSeleccionadosIds" name="equiposSeleccionadosIds" value="">
			
			<div class="row">
				<div class="col-sm-4">
					<label>Hotel y Area</label>
					<?php if ($hotel_auto !== '') { ?>
					<input type="hidden" id="hotelArea" name="hotelArea" value="<?php echo htmlspecialchars($nombre_hotel); ?>">
					<input type="text" class="form-control input-sm" value="<?php echo htmlspecialchars($nombre_hotel); ?>" readonly disabled style="background:#eee;">
					<?php } else { ?>
					<select class="form-control input-sm" id="hotelArea" name="hotelArea" required>
						<option value="A" selected>Selecciona hotel y area</option>
						<option value="Villa del Arco">Villa del Arco</option>
						<option value="Villa la Estancia">Villa la Estancia</option>
						<option value="Villa del Palmar">Villa del Palmar</option>
						<option value="Villa la Valencia">Villa la Valencia</option>
					</select>
					<?php } ?>
				</div>
				<div class="col-sm-4">
					<label>Motivo de la Baja Masiva</label>
					<select class="form-control input-sm" id="motivoBajaMasiva" name="motivoBajaMasiva">
						<option value="A">Selecciona motivo</option>
						<option value="Renovación de Equipos">Renovación de Equipos</option>
						<option value="Cierre de Departamento">Cierre de Departamento</option>
						<option value="Actualización Tecnológica">Actualización Tecnológica</option>
						<option value="Venta Masiva">Venta Masiva</option>
						<option value="Donación Masiva">Donación Masiva</option>
						<option value="Otro">Otro</option>
					</select>
				</div>
				<div class="col-sm-4">
					<label>Destino de los Equipos</label>
					<select class="form-control input-sm" id="destinoEquiposMasiva" name="destinoEquiposMasiva">
						<option value="A">Selecciona destino</option>
						<option value="Venta Masiva">Venta Masiva</option>
						<option value="Donación Masiva">Donación Masiva</option>
						<option value="Chatarra">Chatarra</option>
						<option value="Almacén">Almacén</option>
						<option value="Otro">Otro</option>
					</select>
				</div>
			</div>

			<p></p>
			<span class="btn btn-danger" id="btnRegistrarBajaMasiva">
				<i class="glyphicon glyphicon-trash"></i> Registrar Baja Masiva
			</span>
		</form>
	</div>
	<div class="col-sm-4">
		<div id="resumenBajaMasiva"></div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){
		var equiposSeleccionados = [];
		
		// Función para actualizar contadores
		function actualizarContadores() {
			$('#contadorSeleccionados').text(equiposSeleccionados.length + ' equipos seleccionados');
			
			var valorTotal = 0;
			equiposSeleccionados.forEach(function(equipo) {
				valorTotal += parseFloat(equipo.valor);
			});
			$('#valorTotalSeleccionados').text('Total: $' + valorTotal.toFixed(2));
			
			// Actualizar campo oculto
			$('#equiposSeleccionadosIds').val(JSON.stringify(equiposSeleccionados.map(e => e.id)));
			
			// Actualizar resumen
			actualizarResumen();
		}
		
		// Función para actualizar resumen
		function actualizarResumen() {
			if(equiposSeleccionados.length > 0) {
				var valorTotal = 0;
				var categorias = {};
				
				equiposSeleccionados.forEach(function(equipo) {
					valorTotal += parseFloat(equipo.valor);
					if(categorias[equipo.categoria]) {
						categorias[equipo.categoria]++;
					} else {
						categorias[equipo.categoria] = 1;
					}
				});
				
				var valorPromedio = valorTotal / equiposSeleccionados.length;
				
				var resumenHtml = '<div class="panel panel-danger">' +
					'<div class="panel-heading">Resumen de Baja Masiva</div>' +
					'<div class="panel-body">' +
					'<p><strong>Total de Equipos:</strong> ' + equiposSeleccionados.length + '</p>' +
					'<p><strong>Valor Total Original:</strong> $' + valorTotal.toFixed(2) + '</p>' +
					'<p><strong>Valor Promedio:</strong> $' + valorPromedio.toFixed(2) + '</p>' +
					'<hr><strong>Por Categoría:</strong><ul>';
				
				for(var categoria in categorias) {
					resumenHtml += '<li>' + categoria + ': ' + categorias[categoria] + ' equipos</li>';
				}
				
				resumenHtml += '</ul></div></div>';
				$('#resumenBajaMasiva').html(resumenHtml);
			} else {
				$('#resumenBajaMasiva').empty();
			}
		}
		
		// Función para mostrar equipos seleccionados
		function mostrarEquiposSeleccionados() {
			if(equiposSeleccionados.length > 0) {
				var html = '<div class="table-responsive"><table class="table table-condensed table-bordered">';
				html += '<tr><th>Código</th><th>Descripción</th><th>Valor</th><th>Observación (falla/motivo por equipo)</th><th>Estado</th><th>Acción</th></tr>';
				
				equiposSeleccionados.forEach(function(equipo, index) {
					// Determinar el color del label según el estado
					var labelClass = 'label-success';
					var estadoTexto = equipo.estado || 'Activo';
					
					if(equipo.estado == 'Dado de Baja') {
						labelClass = 'label-danger';
						estadoTexto = 'Ya Dado de Baja';
					} else if(equipo.estado == 'En Mantenimiento') {
						labelClass = 'label-warning';
						estadoTexto = 'En Mantenimiento';
					} else if(equipo.estado == 'En Reparación') {
						labelClass = 'label-info';
						estadoTexto = 'En Reparación';
					}
					
					var obsVal = (equipo.observacion || '').replace(/"/g, '&quot;');
					html += '<tr>';
					html += '<td>' + equipo.codigo + '</td>';
					html += '<td>' + equipo.descripcion + '</td>';
					html += '<td>$' + parseFloat(equipo.valor).toFixed(2) + '</td>';
					html += '<td><input type="text" class="form-control input-sm obs-equipo" data-index="' + index + '" placeholder="Observación de este equipo" value="' + obsVal + '" style="min-width:180px;"></td>';
					html += '<td><span class="label ' + labelClass + '">' + estadoTexto + '</span></td>';
					html += '<td><button type="button" class="btn btn-xs btn-danger quitar-equipo" data-index="' + index + '"><i class="glyphicon glyphicon-remove"></i></button></td>';
					html += '</tr>';
				});
				
				html += '</table></div>';
				$('#equiposSeleccionados').html(html);
			} else {
				$('#equiposSeleccionados').html('<p class="text-muted">No hay equipos seleccionados...</p>');
			}
		}
		
		// Buscar por código
		$('#btnBuscar').click(function(){
			var codigo = $('#buscarCodigo').val().trim();
			if(codigo == '') {
				alertify.alert("Ingresa un código para buscar");
				return;
			}
			
			$.ajax({
				type: "POST",
				url: "../procesos/bajasMasivas/buscarEquipos.php",
				data: { tipo: 'codigo', valor: codigo },
				success: function(response) {
					$('#resultadosBusqueda').html(response);
				}
			});
		});
		
		// Buscar por número de serie
		$('#btnBuscarDescripcion').click(function(){
			var numeroSerie = $('#buscarDescripcion').val().trim();
			if(numeroSerie == '') {
				alertify.alert("Ingresa un número de serie para buscar");
				return;
			}
			
			$.ajax({
				type: "POST",
				url: "../procesos/bajasMasivas/buscarEquipos.php",
				data: { tipo: 'numero_serie', valor: numeroSerie },
				success: function(response) {
					$('#resultadosBusqueda').html(response);
				}
			});
		});
		
		// Buscar al presionar Enter
		$('#buscarCodigo').keypress(function(e) {
			if(e.which == 13) {
				$('#btnBuscar').click();
			}
		});
		
		$('#buscarDescripcion').keypress(function(e) {
			if(e.which == 13) {
				$('#btnBuscarDescripcion').click();
			}
		});
		
		// Agregar equipo a la selección (se llama desde el resultado de búsqueda)
		window.agregarEquipo = function(id, codigo, descripcion, valor, categoria, estado) {
			// Verificar si ya está seleccionado
			var yaSeleccionado = equiposSeleccionados.some(function(equipo) {
				return equipo.id == id;
			});
			
			if(yaSeleccionado) {
				alertify.alert("Este equipo ya está seleccionado");
				return;
			}
			
			// Agregar a la selección (cada equipo tendrá su propia observación)
			equiposSeleccionados.push({
				id: id,
				codigo: codigo,
				descripcion: descripcion,
				valor: valor,
				categoria: categoria,
				estado: estado || 'Activo',
				observacion: ''
			});
			
			actualizarContadores();
			mostrarEquiposSeleccionados();
			
			// Mensaje personalizado según el estado
			if(estado == 'Dado de Baja') {
				alertify.success("Equipo ya dado de baja agregado al reporte masivo");
			} else {
				alertify.success("Equipo agregado a la selección");
			}
		};
		
		// Sincronizar observación escrita al objeto del equipo
		$(document).on('input change', '.obs-equipo', function(){
			var idx = $(this).data('index');
			if (typeof idx !== 'undefined' && equiposSeleccionados[idx]) {
				equiposSeleccionados[idx].observacion = $(this).val();
			}
		});

		// Quitar equipo de la selección
		$(document).on('click', '.quitar-equipo', function(){
			var index = $(this).data('index');
			equiposSeleccionados.splice(index, 1);
			actualizarContadores();
			mostrarEquiposSeleccionados();
			alertify.success("Equipo removido de la selección");
		});
		
		// Limpiar selección
		$('#btnLimpiarSeleccion').click(function(){
			equiposSeleccionados = [];
			actualizarContadores();
			mostrarEquiposSeleccionados();
			alertify.success("Selección limpiada");
		});
		
		// Ver seleccionados
		$('#btnVerSeleccionados').click(function(){
			mostrarEquiposSeleccionados();
		});
		
		// Registrar baja masiva
		$('#btnRegistrarBajaMasiva').click(function(){
			if(equiposSeleccionados.length == 0){
				alertify.alert("Debes seleccionar al menos un equipo!!");
				return false;
			}

			vacios=validarFormVacio('frmRegistrarBajaMasiva');

			if(vacios > 0){
				alertify.alert("Debes llenar todos los campos!!");
				return false;
			}

			alertify.confirm('¿Estás seguro de registrar la baja masiva de ' + equiposSeleccionados.length + ' equipos?', function(){
				var formData = new FormData();
				formData.append('equipos', JSON.stringify(equiposSeleccionados.map(e => e.id)));
				formData.append('observacionesEquipos', JSON.stringify(equiposSeleccionados.map(function(e){ return e.observacion || ''; })));
				formData.append('hotelArea', $('#hotelArea').val());
				formData.append('motivo', $('#motivoBajaMasiva').val());
				formData.append('destino', $('#destinoEquiposMasiva').val());
				formData.append('observaciones', ''); // Ya no se usa: cada equipo tiene su observación en la tabla

				$.ajax({
					type:"POST",
					data:formData,
					url:"../procesos/bajasMasivas/registrarBajaMasiva.php",
					contentType: false,
					processData: false,
					success:function(r){
						if(r == 'success'){
							$('#frmRegistrarBajaMasiva')[0].reset();
							equiposSeleccionados = [];
							actualizarContadores();
							mostrarEquiposSeleccionados();
							$('#resumenBajaMasiva').empty();
							alertify.success("Baja masiva registrada con éxito");
						}else{
							alertify.error("Error al registrar la baja masiva");
						}
					}
				});
			}, function(){
				alertify.error('Cancelado');
			});
		});
		
		// Inicializar
		actualizarContadores();
		mostrarEquiposSeleccionados();
	});
</script>
