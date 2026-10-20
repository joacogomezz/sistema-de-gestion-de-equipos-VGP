<?php
session_start();
require_once "../../clases/Conexion.php";
require_once "../../clases/Departamentos.php";
require_once "../../clases/Fases.php";
require_once "../../clases/Areas.php";

$c = new conectar();
$conexion = $c->conexion();
$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : null);

$objDept = new departamentos();
$objFases = new Fases();
$objAreas = new Areas();

$departamentos = array();
$r = $objDept->obtenerTodosDepartamentos($id_hotel);
while ($row = mysqli_fetch_row($r)) {
	$departamentos[] = array('id' => $row[0], 'nombre' => $row[1]);
}
$fases = array();
$r = $objFases->obtenerTodos($id_hotel);
while ($row = mysqli_fetch_row($r)) {
	$fases[] = array('id' => $row[0], 'nombre' => $row[1]);
}
$areas = array();
$r = $objAreas->obtenerTodos($id_hotel);
while ($row = mysqli_fetch_row($r)) {
	$areas[] = array('id' => $row[0], 'nombre' => $row[1]);
}

$nombre_responsable = (isset($_SESSION['nombre']) && isset($_SESSION['apellido']))
	? trim($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) : (isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '');
$fecha_hoy = date('Y-m-d');
?>
<style>
	#frmSolicitudAlta .form-seccion { margin-bottom: 28px; }
	#frmSolicitudAlta label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--texto); }
	#frmSolicitudAlta .row + .row { margin-top: 20px; }
	#frmSolicitudAlta .row .form-group { margin-bottom: 0; }
	#tablaEquiposSolicitud { margin-top: 12px; width: 100%; min-width: 1100px; }
	#tablaEquiposSolicitud th, #tablaEquiposSolicitud td { padding: 6px 6px !important; vertical-align: middle !important; overflow: hidden; }
	#tablaEquiposSolicitud thead th {
		background: var(--superficie-hundida) !important; color: var(--texto-secundario) !important; font-weight: 500;
		white-space: normal; word-wrap: break-word; font-size: 11px; line-height: 1.2;
		min-width: 60px; max-width: 120px;
	}
	#tablaEquiposSolicitud thead th:nth-child(1) { min-width: 90px; }
	#tablaEquiposSolicitud thead th:nth-child(5) { min-width: 95px; }
	#tablaEquiposSolicitud thead th:nth-child(8) { min-width: 100px; }
	#tablaEquiposSolicitud thead th:nth-child(12) { min-width: 95px; }
	#tablaEquiposSolicitud thead th:nth-child(13) { min-width: 95px; }
	#tablaEquiposSolicitud thead th:nth-child(14) { width: 50px; min-width: 50px; }
	#tablaEquiposSolicitud tbody td { background: var(--superficie); }
	/* Campos al 100% de la celda, texto visible, altura consistente */
	#tablaEquiposSolicitud input.form-control,
	#tablaEquiposSolicitud select.form-control {
		width: 100% !important;
		max-width: 100%;
		box-sizing: border-box !important;
		min-height: 36px !important;
		height: 36px !important;
		padding: 6px 8px !important;
		font-size: 13px !important;
		line-height: 1.4 !important;
		background-color: var(--superficie) !important;
		color: var(--texto) !important;
		border: 1px solid var(--borde-fuerte) !important;
		border-radius: var(--radio-sm) !important;
		display: block;
	}
	#tablaEquiposSolicitud input.form-control::placeholder {
		color: var(--texto-secundario) !important;
	}
	#tablaEquiposSolicitud input.form-control:focus,
	#tablaEquiposSolicitud select.form-control:focus {
		background-color: var(--superficie) !important;
		color: var(--texto) !important;
		border-color: var(--dorado) !important;
		box-shadow: 0 0 0 3px var(--dorado-suave) !important;
		outline: none;
	}
	#tablaEquiposSolicitud select.form-control option {
		background: var(--superficie);
		color: var(--texto);
	}
	#tablaEquiposSolicitud tbody td:last-child { width: 50px; text-align: center; }
	.frm-titulo-seccion { font-size: 15px; font-weight: 600; color: var(--texto); margin-bottom: 14px; padding-bottom: 8px; border-bottom: 1px solid var(--borde); }
	.btn-area-pdf { margin-top: 8px; padding: 12px 24px; font-size: 15px; }
</style>
<h4 class="frm-titulo-seccion">Datos de la solicitud</h4>
<form id="frmSolicitudAlta">
	<div class="form-seccion">
		<div class="row">
			<div class="col-sm-3">
				<label>Departamento solicitante</label>
				<select class="form-control" id="id_departamento_solicitante" name="id_departamento_solicitante" required>
					<option value="">Seleccione...</option>
					<?php foreach ($departamentos as $d): ?>
					<option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['nombre']); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="col-sm-2">
				<label>Cantidad de equipos</label>
				<input type="text" class="form-control" id="cantidad_equipos" readonly value="0">
			</div>
			<div class="col-sm-2">
				<label>Fecha</label>
				<input type="date" class="form-control" id="fecha_solicitud" name="fecha_solicitud" value="<?php echo $fecha_hoy; ?>" required>
			</div>
			<div class="col-sm-3">
				<label>Solicitud #</label>
				<input type="text" class="form-control" id="numero_solicitud" name="numero_solicitud" placeholder="Número de solicitud" required>
			</div>
		</div>
	</div>

	<div class="form-seccion">
		<div class="row">
			<div class="col-sm-8">
				<label>Agregar equipo por código primario</label>
				<div class="input-group">
					<input type="text" class="form-control" id="codigo_buscar" placeholder="Código del equipo">
					<span class="input-group-btn">
						<button type="button" class="btn btn-primary" id="btnBuscarEquipo">Buscar y agregar</button>
					</span>
				</div>
				<span id="msgBusqueda" class="help-block" style="margin-top: 8px;"></span>
			</div>
		</div>
	</div>

	<div class="form-seccion">
		<label class="frm-titulo-seccion" style="margin-bottom: 10px;">Datos del equipo (tabla)</label>
		<div class="table-responsive">
			<table class="table table-bordered" id="tablaEquiposSolicitud">
				<thead>
					<tr>
						<th>Descripción</th>
						<th>Marca</th>
						<th>Modelo</th>
						<th>Serie</th>
						<th>Características</th>
						<th>Color</th>
						<th>Responsable</th>
						<th>Centro de costos</th>
						<th>Fase</th>
						<th>Área</th>
						<th>Piso</th>
						<th>Tipo habitación</th>
						<th>Departamento</th>
						<th></th>
					</tr>
				</thead>
				<tbody id="tbodyEquipos">
				</tbody>
			</table>
		</div>
	</div>

	<div class="form-seccion">
		<div class="row">
			<div class="col-sm-6">
				<label>Clasificación 1</label>
				<input type="text" class="form-control" id="clasificacion_1" name="clasificacion_1" placeholder="Clasificación 1">
			</div>
			<div class="col-sm-6">
				<label>Clasificación 2</label>
				<input type="text" class="form-control" id="clasificacion_2" name="clasificacion_2" placeholder="Clasificación 2">
			</div>
		</div>
	</div>

	<div class="row" style="margin-top: 28px; margin-bottom: 12px;">
		<div class="col-sm-12">
			<button type="button" class="btn btn-success btn-lg btn-area-pdf" id="btnGuardarYPDF">
				<span class="glyphicon glyphicon-save"></span> Guardar y generar PDF
			</button>
		</div>
	</div>
</form>

<script>
var opcionesDepartamentos = <?php echo json_encode($departamentos); ?>;
var opcionesFases = <?php echo json_encode($fases); ?>;
var opcionesAreas = <?php echo json_encode($areas); ?>;
var nombreResponsable = <?php echo json_encode($nombre_responsable); ?>;

function actualizarCantidad() {
	var n = $('#tbodyEquipos tr').length;
	$('#cantidad_equipos').val(n);
}

function renderSelect(options, nameAttr, value) {
	var s = '<select class="form-control" name="' + nameAttr + '"><option value="">—</option>';
	value = value || '';
	options.forEach(function(o) {
		var sel = (o.id == value) ? ' selected' : '';
		s += '<option value="' + o.id + '"' + sel + '>' + (o.nombre || '').replace(/</g, '&lt;') + '</option>';
	});
	s += '</select>';
	return s;
}

function agregarFilaEquipo(datos) {
	var tr = $('<tr></tr>');
	tr.data('id_equipo', datos.id_equipo);
	tr.append('<td><input type="text" class="form-control" readonly value="' + (datos.descripcion || '').replace(/"/g, '&quot;') + '"></td>');
	tr.append('<td><input type="text" class="form-control" readonly value="' + (datos.marca || '').replace(/"/g, '&quot;') + '"></td>');
	tr.append('<td><input type="text" class="form-control" readonly value="' + (datos.modelo || '').replace(/"/g, '&quot;') + '"></td>');
	tr.append('<td><input type="text" class="form-control" readonly value="' + (datos.numero_serie || '').replace(/"/g, '&quot;') + '"></td>');
	tr.append('<td><input type="text" class="form-control" name="caracteristicas[]" placeholder="Características"></td>');
	tr.append('<td><input type="text" class="form-control" name="color[]" placeholder="Color"></td>');
	tr.append('<td><input type="text" class="form-control" readonly value="' + (nombreResponsable || '').replace(/"/g, '&quot;') + '"></td>');
	tr.append('<td><select class="form-control" name="centro_costos[]"><option value="CAPEX">CAPEX</option><option value="GASTO DE OPERACION">GASTO DE OPERACIÓN</option></select></td>');
	tr.append('<td>' + renderSelect(opcionesFases, 'id_fase[]', '') + '</td>');
	tr.append('<td>' + renderSelect(opcionesAreas, 'id_area[]', '') + '</td>');
	tr.append('<td><input type="text" class="form-control" name="piso[]" placeholder="Piso"></td>');
	tr.append('<td><input type="text" class="form-control" name="tipo_habitacion[]" placeholder="Tipo habitación"></td>');
	tr.append('<td>' + renderSelect(opcionesDepartamentos, 'id_departamento[]', '') + '</td>');
	tr.append('<td><button type="button" class="btn btn-danger btn-sm btnQuitarFila"><span class="glyphicon glyphicon-remove"></span></button></td>');
	$('#tbodyEquipos').append(tr);
	actualizarCantidad();
	$('#tablaEquiposSolicitud').find('tbody').on('click', '.btnQuitarFila', function() {
		$(this).closest('tr').remove();
		actualizarCantidad();
	});
}

$(document).ready(function(){
	$('#btnBuscarEquipo').click(function(){
		var codigo = $('#codigo_buscar').val().trim();
		if (!codigo) {
			$('#msgBusqueda').html('<span class="text-warning">Ingrese el código del equipo.</span>');
			return;
		}
		$('#msgBusqueda').html('');
		$.ajax({
			type: 'POST',
			url: '../procesos/resguardos/obtenerEquipoPorCodigo.php',
			data: { codigo: codigo },
			dataType: 'json',
			success: function(r){
				if (r.error) {
					$('#msgBusqueda').html('<span class="text-danger">' + r.error + '</span>');
					return;
				}
				var idEq = r.id_equipo;
				var ya = false;
				$('#tbodyEquipos tr').each(function(){ if ($(this).data('id_equipo') == idEq) ya = true; });
				if (ya) {
					$('#msgBusqueda').html('<span class="text-warning">Ese equipo ya está en la lista.</span>');
					return;
				}
				agregarFilaEquipo({
					id_equipo: r.id_equipo,
					descripcion: r.descripcion,
					marca: r.marca,
					modelo: r.modelo,
					numero_serie: r.numero_serie
				});
				$('#msgBusqueda').html('<span class="text-success">Equipo agregado.</span>');
				$('#codigo_buscar').val('');
			},
			error: function() {
				$('#msgBusqueda').html('<span class="text-danger">Error al buscar.</span>');
			}
		});
	});
	$('#codigo_buscar').on('keypress', function(e){ if (e.which === 13) { e.preventDefault(); $('#btnBuscarEquipo').click(); } });

	$('#btnGuardarYPDF').click(function(){
		var filas = $('#tbodyEquipos tr');
		if (filas.length === 0) {
			alertify.alert('Debe agregar al menos un equipo a la solicitud.');
			return;
		}
		if (!$('#id_departamento_solicitante').val()) {
			alertify.alert('Seleccione el departamento solicitante.');
			return;
		}
		if (!$('#numero_solicitud').val().trim()) {
			alertify.alert('Ingrese el número de solicitud.');
			return;
		}
		var detalles = [];
		filas.each(function(i){
			var $tr = $(this);
			detalles.push({
				id_equipo: $tr.data('id_equipo'),
				caracteristicas: $tr.find('input[name="caracteristicas[]"]').val() || '',
				color: $tr.find('input[name="color[]"]').val() || '',
				centro_costos: $tr.find('select[name="centro_costos[]"]').val() || 'CAPEX',
				id_fase: $tr.find('select[name="id_fase[]"]').val() || '',
				id_area: $tr.find('select[name="id_area[]"]').val() || '',
				piso: $tr.find('input[name="piso[]"]').val() || '',
				tipo_habitacion: $tr.find('input[name="tipo_habitacion[]"]').val() || '',
				id_departamento: $tr.find('select[name="id_departamento[]"]').val() || ''
			});
		});
		var payload = {
			id_departamento_solicitante: $('#id_departamento_solicitante').val(),
			numero_solicitud: $('#numero_solicitud').val().trim(),
			fecha_solicitud: $('#fecha_solicitud').val(),
			clasificacion_1: $('#clasificacion_1').val().trim(),
			clasificacion_2: $('#clasificacion_2').val().trim(),
			detalles: detalles
		};
		$.ajax({
			type: 'POST',
			url: '../procesos/solicitudAltaEquipos/guardarSolicitudAltaEquipos.php',
			data: { datos: JSON.stringify(payload) },
			dataType: 'json',
			success: function(r){
				if (r.ok && r.id_solicitud) {
					alertify.success(r.mensaje || 'Solicitud guardada.');
					var a = document.createElement('a');
					a.href = '../reportes/reporteSolicitudAltaEquipos.php?id=' + r.id_solicitud;
					a.download = 'Solicitud_Alta_Equipos.pdf';
					a.style.display = 'none';
					document.body.appendChild(a);
					a.click();
					document.body.removeChild(a);
					$('#tbodyEquipos').empty();
					$('#cantidad_equipos').val('0');
					$('#numero_solicitud').val('');
					$('#clasificacion_1').val('');
					$('#clasificacion_2').val('');
				} else {
					alertify.error(r.mensaje || 'Error al guardar.');
				}
			},
			error: function() {
				alertify.error('Error de conexión.');
			}
		});
	});
});
</script>
