<?php
	require_once "../../clases/Conexion.php";
?>
<h4>Resguardo múltiple (varios equipos, misma factura)</h4>
<p class="text-muted">Cuando una factura incluye varios equipos, ingrese primero los datos comunes (factura, responsable, área, fecha) y luego agregue cada equipo por <strong>código</strong> o <strong>número de serie</strong>. Se generará un resguardo por cada equipo con los mismos datos. <strong>Máximo 10 equipos</strong> por documento (así el PDF cabe en una hoja al imprimir).</p>

<div class="row">
	<div class="col-sm-8">
		<form id="frmResguardoMultiple">
			<input type="hidden" id="idHotelContextResguardoM" value="<?php echo (isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : ''; ?>">
			<div class="panel panel-default">
				<div class="panel-heading">Datos comunes (factura y responsable)</div>
				<div class="panel-body">
					<div class="form-group">
						<label for="numero_factura">Número de factura *</label>
						<input type="text" class="form-control" id="numero_factura" name="numero_factura" placeholder="Ej: F-2025-001" required>
					</div>
					<div class="form-group">
						<label for="area_destino">Área de destino *</label>
						<input type="text" class="form-control" id="area_destino" name="area_destino" placeholder="Ej: Recepción, Sistemas">
					</div>
					<div class="form-group">
						<label for="usuario">Usuario / Responsable (nombre completo) *</label>
						<input type="text" class="form-control" id="usuario" name="usuario" placeholder="Nombre del responsable del equipo">
					</div>
					<div class="form-group">
						<label for="puesto">Puesto</label>
						<input type="text" class="form-control" id="puesto" name="puesto" placeholder="Ej: Supervisor de Cajeros">
					</div>
					<div class="form-group">
						<label for="fecha_resguardo">Fecha del resguardo *</label>
						<input type="date" class="form-control" id="fecha_resguardo" name="fecha_resguardo" value="<?php echo date('Y-m-d'); ?>">
					</div>
					<p class="text-muted small">El <strong>Tipo</strong> en el PDF se toma automáticamente de la descripción de cada equipo (dato del alta).</p>
				</div>
			</div>

			<div class="panel panel-default">
				<div class="panel-heading">Equipos a incluir (código o número de serie)</div>
				<div class="panel-body">
					<div class="form-inline form-group">
						<label for="codigoEquipoAdd" class="sr-only">Código o número de serie del equipo</label>
						<input type="text" class="form-control" id="codigoEquipoAdd" placeholder="Código o número de serie" autocomplete="off" style="width: 260px;">
						<button type="button" class="btn btn-primary" id="btnAgregarEquipo">
							<span class="glyphicon glyphicon-plus"></span> Agregar equipo
						</button>
					</div>
					<p class="text-muted small">Si los equipos ya tienen <strong>usuario (responsable)</strong> y <strong>número de factura</strong> en la ficha del equipo, al agregar el primer equipo con esos datos se rellenarán los campos comunes que estén vacíos (puede editarlos). El <strong>área de destino</strong> vacía se rellena con la ubicación física del equipo. Si faltan datos, complételos manualmente; al generar el resguardo se guardan también en la tabla de equipos.</p>
					<p class="small" id="contadorEquiposResguardoM" style="margin-top:8px;"><span class="label label-default">0 / 10 equipos</span></p>
					<div id="listaEquipos" class="list-group" style="margin-top: 12px;"></div>
					<input type="hidden" id="id_equipos_json" name="id_equipos_json" value="[]">
				</div>
			</div>

			<button type="button" class="btn btn-success btn-lg" id="btnGenerarResguardos" disabled>
				<span class="glyphicon glyphicon-save"></span> Generar resguardos para los equipos listados
			</button>
		</form>
	</div>
	<div class="col-sm-4">
		<div id="mensajeResguardoMultiple"></div>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function(){
		var equiposAgregados = []; // { id_equipo, codigo_equipo, descripcion o marca/modelo }
		var MAX_EQUIPOS_RESGUARDO_M = 10;

		function actualizarListaYHidden(){
			var json = JSON.stringify(equiposAgregados);
			$('#id_equipos_json').val(json);
			var n = equiposAgregados.length;
			$('#btnGenerarResguardos').prop('disabled', n === 0);
			var $lbl = $('#contadorEquiposResguardoM .label');
			$lbl.text(n + ' / ' + MAX_EQUIPOS_RESGUARDO_M + ' equipos');
			$lbl.removeClass('label-default label-warning label-danger').addClass(n >= MAX_EQUIPOS_RESGUARDO_M ? 'label-danger' : (n >= MAX_EQUIPOS_RESGUARDO_M - 2 ? 'label-warning' : 'label-default'));
		}

		function renderLista(){
			var html = '';
			for (var i = 0; i < equiposAgregados.length; i++) {
				var e = equiposAgregados[i];
				var texto = e.codigo_equipo + (e.descripcion ? ' — ' + e.descripcion : '');
				html += '<div class="list-group-item" data-index="' + i + '">' +
					'<span class="glyphicon glyphicon-tag"></span> ' + texto +
					' <button type="button" class="btn btn-xs btn-danger pull-right btnQuitarEquipo" data-index="' + i + '">Quitar</button></div>';
			}
			$('#listaEquipos').html(html || '<p class="text-muted">No hay equipos agregados. Use el código o la serie arriba para agregar.</p>');
			actualizarListaYHidden();
		}

		$('#btnAgregarEquipo').click(function(){
			if (equiposAgregados.length >= MAX_EQUIPOS_RESGUARDO_M) {
				alertify.warning('Solo se permiten hasta ' + MAX_EQUIPOS_RESGUARDO_M + ' equipos por resguardo múltiple (una hoja al imprimir). Quite uno de la lista o genere otro documento.');
				return;
			}
			var codigo = $('#codigoEquipoAdd').val().trim();
			if (codigo === '') {
				alertify.warning('Escriba el código o el número de serie del equipo');
				return;
			}
			$.ajax({
				type: 'POST',
				url: '../procesos/resguardos/obtenerEquipoPorCodigo.php',
				data: { codigo: codigo, id_hotel: $('#idHotelContextResguardoM').val() },
				dataType: 'json',
				success: function(r){
					if (r.error) {
						$('#mensajeResguardoMultiple').html('<div class="alert alert-danger">' + r.error + '</div>');
						return;
					}
					var duplicado = equiposAgregados.some(function(e){ return String(e.id_equipo) === String(r.id_equipo); });
					if (duplicado) {
						alertify.warning('Ese equipo ya está en la lista');
						return;
					}
					$('#mensajeResguardoMultiple').empty();
					equiposAgregados.push({
						id_equipo: r.id_equipo,
						codigo_equipo: r.codigo_equipo,
						descripcion: (r.marca || '') + ' ' + (r.modelo || '')
					});
					var ubi = (typeof r.ubicacion !== 'undefined' && r.ubicacion) ? String(r.ubicacion).trim() : '';
					if (ubi && $('#area_destino').val().trim() === '') {
						$('#area_destino').val(ubi);
					}
					var uFact = (typeof r.numero_factura !== 'undefined' && r.numero_factura) ? String(r.numero_factura).trim() : '';
					var uNom = (typeof r.usuario_registro !== 'undefined' && r.usuario_registro) ? String(r.usuario_registro).trim() : '';
					if (uFact && $('#numero_factura').val().trim() === '') {
						$('#numero_factura').val(uFact);
					}
					if (uNom && $('#usuario').val().trim() === '') {
						$('#usuario').val(uNom);
					}
					$('#codigoEquipoAdd').val('').focus();
					renderLista();
				},
				error: function(){
					$('#mensajeResguardoMultiple').html('<div class="alert alert-danger">Error al buscar el equipo.</div>');
				}
			});
		});

		$('#codigoEquipoAdd').on('keypress', function(e){
			if (e.which === 13) { e.preventDefault(); $('#btnAgregarEquipo').click(); }
		});

		$(document).on('click', '.btnQuitarEquipo', function(){
			var idx = parseInt($(this).data('index'), 10);
			equiposAgregados.splice(idx, 1);
			renderLista();
		});

		$('#btnGenerarResguardos').click(function(){
			var numero_factura = $('#numero_factura').val().trim();
			var area_destino = $('#area_destino').val().trim();
			var usuario = $('#usuario').val().trim();
			var fecha_resguardo = $('#fecha_resguardo').val();
			if (!numero_factura) {
				alertify.alert('Indique el número de factura.');
				return;
			}
			if (!area_destino) {
				alertify.alert('Indique el área de destino.');
				return;
			}
			if (!usuario) {
				alertify.alert('Indique el nombre del responsable (usuario).');
				return;
			}
			if (!fecha_resguardo) {
				alertify.alert('Indique la fecha del resguardo.');
				return;
			}
			if (equiposAgregados.length === 0) {
				alertify.alert('Agregue al menos un equipo.');
				return;
			}
			if (equiposAgregados.length > MAX_EQUIPOS_RESGUARDO_M) {
				alertify.alert('Máximo ' + MAX_EQUIPOS_RESGUARDO_M + ' equipos por resguardo múltiple. Quite equipos de la lista.');
				return;
			}

			var datos = {
				numero_factura: numero_factura,
				area_destino: area_destino,
				usuario: usuario,
				puesto: $('#puesto').val().trim(),
				fecha_resguardo: fecha_resguardo,
				id_equipos_json: $('#id_equipos_json').val()
			};

			$('#btnGenerarResguardos').prop('disabled', true);
			$.ajax({
				type: 'POST',
				url: '../procesos/resguardos/guardarResguardosMultiples.php',
				data: datos,
				dataType: 'json',
				success: function(r){
					if (r.ok) {
						alertify.success(r.mensaje);
						equiposAgregados = [];
						renderLista();
						$('#numero_factura').val('');
						$('#area_destino').val('');
						$('#usuario').val('');
						$('#puesto').val('');
						$('#fecha_resguardo').val('<?php echo date("Y-m-d"); ?>');
						if (r.ids && r.ids.length > 0) {
							descargarPdfResguardo('../reportes/reporteResguardoMultiple.php?ids=' + r.ids.join(','));
						}
					} else {
						alertify.error(r.mensaje || 'Error al guardar los resguardos');
					}
					$('#btnGenerarResguardos').prop('disabled', false);
				},
				error: function(){
					alertify.error('Error de conexión.');
					$('#btnGenerarResguardos').prop('disabled', false);
				}
			});
		});

		renderLista();
	});
</script>
