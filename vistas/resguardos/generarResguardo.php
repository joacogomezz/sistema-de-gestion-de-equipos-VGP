<?php
	require_once "../../clases/Conexion.php";
?>
<h4>Generar Resguardo de Equipo</h4>
<p class="text-muted">Introduzca el <strong>código</strong> o el <strong>número de serie</strong> del equipo dado de alta para cargar los datos y completar el resguardo. Si el equipo ya tiene <strong>usuario (responsable)</strong> y <strong>factura</strong> guardados en su ficha, esos valores se precargarán; puede modificarlos si lo necesita.</p>

<div class="row">
	<div class="col-sm-8">
		<form id="frmResguardo">
			<input type="hidden" id="idHotelContextResguardo" value="<?php echo (isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : ''; ?>">
			<div class="form-group">
				<label for="codigoEquipo">Código o número de serie</label>
				<div class="input-group">
					<input type="text" class="form-control" id="codigoEquipo" name="codigoEquipo" placeholder="Ej: EQ-001 o serie del equipo" autocomplete="off">
					<span class="input-group-btn">
						<button type="button" class="btn btn-primary" id="btnBuscarEquipo">Buscar</button>
					</span>
				</div>
				<input type="hidden" id="id_equipo" name="id_equipo">
				<input type="hidden" id="id_departamento" name="id_departamento">
			</div>

			<div id="datosEquipo" style="display: none;">
				<div class="form-group">
					<label>Área de destino</label>
					<input type="text" class="form-control" id="area_destino" name="area_destino" placeholder="Introduzca el área de destino">
				</div>
				<div class="form-group">
					<label for="usuario">Usuario (nombre completo)</label>
					<input type="text" class="form-control" id="usuario" name="usuario" placeholder="Introduzca el nombre completo del responsable">
				</div>
				<div class="form-group">
					<label for="puesto">Puesto</label>
					<input type="text" class="form-control" id="puesto" name="puesto" placeholder="Ej: Supervisor de Cajeros">
				</div>
				<div class="form-group">
					<label for="fecha_resguardo">Fecha del resguardo</label>
					<input type="date" class="form-control" id="fecha_resguardo" name="fecha_resguardo" value="<?php echo date('Y-m-d'); ?>">
				</div>
				<div class="form-group">
					<label for="numero_factura">Factura</label>
					<input type="text" class="form-control" id="numero_factura" name="numero_factura" placeholder="Introduzca el número de factura">
				</div>
				<div class="form-group">
					<label>Marca</label>
					<input type="text" class="form-control" id="marca" name="marca" readonly>
				</div>
				<div class="form-group">
					<label>Modelo del equipo</label>
					<input type="text" class="form-control" id="modelo" name="modelo" readonly>
				</div>
				<div class="form-group">
					<label>Serie</label>
					<input type="text" class="form-control" id="serie" name="serie" readonly>
				</div>
				<div class="form-group">
					<label for="tipo_equipo">Tipo</label>
					<input type="text" class="form-control" id="tipo_equipo" name="tipo_equipo" placeholder="Introduzca el tipo de equipo (ej: Laptop, Monitor, Impresora)">
				</div>
				<button type="button" class="btn btn-success" id="btnGuardarResguardo">
					<span class="glyphicon glyphicon-save"></span> Guardar e imprimir resguardo
				</button>
			</div>
		</form>
	</div>
	<div class="col-sm-4">
		<div id="mensajeEquipo"></div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){
		function buscarEquipo(){
			var codigo = $('#codigoEquipo').val().trim();
			if (codigo === '') {
				alertify.warning('Introduzca el código o el número de serie del equipo');
				return;
			}
			$.ajax({
				type: 'POST',
				url: '../procesos/resguardos/obtenerEquipoPorCodigo.php',
				data: { codigo: codigo, id_hotel: $('#idHotelContextResguardo').val() },
				dataType: 'json',
				success: function(r){
					if (r.error) {
						$('#mensajeEquipo').html('<div class="alert alert-danger">' + r.error + '</div>');
						$('#datosEquipo').hide();
						$('#id_equipo').val('');
						return;
					}
					$('#id_equipo').val(r.id_equipo);
					$('#id_departamento').val(r.id_departamento || '');
					var uFact = (typeof r.numero_factura !== 'undefined' && r.numero_factura) ? String(r.numero_factura).trim() : '';
					var uNom = (typeof r.usuario_registro !== 'undefined' && r.usuario_registro) ? String(r.usuario_registro).trim() : '';
					$('#usuario').val(uNom);
					$('#numero_factura').val(uFact);
					$('#puesto').val(''); // Puesto es el cargo (ej. Supervisor), no el nombre del responsable
					$('#tipo_equipo').val(r.descripcion || ''); // Tipo = descripción del equipo (dato del alta)
					$('#marca').val(r.marca || '');
					$('#modelo').val(r.modelo || '');
					$('#serie').val(r.numero_serie || '');
					var msgEx = '<div class="alert alert-success">Equipo encontrado. El <strong>área de destino</strong> se precarga con la ubicación física del equipo (puede editarla). ';
					if (uNom || uFact) {
						msgEx += '<strong>Usuario</strong> y/o <strong>factura</strong> registrados en la ficha del equipo se han precargado (puede modificarlos). ';
					}
					msgEx += 'Complete los datos que falten y guarde.</div>';
					$('#mensajeEquipo').html(msgEx);
					$('#datosEquipo').show();
				},
				error: function(){
					$('#mensajeEquipo').html('<div class="alert alert-danger">Error al buscar el equipo.</div>');
					$('#datosEquipo').hide();
				}
			});
		}

		$('#btnBuscarEquipo').click(buscarEquipo);
		$('#codigoEquipo').on('keypress', function(e){
			if (e.which === 13) { e.preventDefault(); buscarEquipo(); }
		});

		$('#btnGuardarResguardo').click(function(){
			var usuario = $('#usuario').val().trim();
			if (usuario === '') {
				alertify.alert('Debe introducir el nombre completo del usuario (responsable).');
				return;
			}
			var fecha = $('#fecha_resguardo').val();
			if (!fecha) {
				alertify.alert('Debe indicar la fecha del resguardo.');
				return;
			}
			if (!$('#id_equipo').val()) {
				alertify.alert('Primero busque un equipo por código o número de serie.');
				return;
			}

			var datos = {
				id_equipo: $('#id_equipo').val(),
				id_departamento: $('#id_departamento').val() || '',
				area_destino: $('#area_destino').val(),
				usuario: usuario,
				puesto: $('#puesto').val().trim(),
				fecha_resguardo: fecha,
				numero_factura: $('#numero_factura').val().trim(),
				modelo: $('#modelo').val().trim(),
				serie: $('#serie').val().trim(),
				tipo_equipo: $('#tipo_equipo').val().trim()
			};

			$.ajax({
				type: 'POST',
				url: '../procesos/resguardos/guardarResguardo.php',
				data: datos,
				dataType: 'json',
				success: function(r){
					if (r.ok) {
						alertify.success(r.mensaje);
						$('#frmResguardo')[0].reset();
						$('#id_equipo').val('');
						$('#id_departamento').val('');
						$('#datosEquipo').hide();
						$('#mensajeEquipo').empty();
						$('#fecha_resguardo').val('<?php echo date("Y-m-d"); ?>');
						if (r.id_resguardo) {
							descargarPdfResguardo('../reportes/reporteResguardo.php?id=' + r.id_resguardo);
						}
					} else {
						alertify.error(r.mensaje || 'Error al guardar el resguardo');
					}
				},
				error: function(){
					alertify.error('Error de conexión al guardar el resguardo');
				}
			});
		});
	});
</script>
