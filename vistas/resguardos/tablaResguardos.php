<?php
	session_start();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Resguardos.php";

	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin
		? ((isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : null)
		: (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	$obj = new Resguardos();
	$lista = $obj->obtenerListaResguardosAgrupada($id_hotel);
	$hayDatos = count($lista) > 0;
?>
<h4>Resguardos generados</h4>
<p class="text-muted small">Los resguardos <strong>múltiples</strong> se muestran en una sola fila (varios equipos, mismo documento). Los <strong>individuales</strong> aparecen una fila por equipo.</p>
<div class="row">
	<div class="col-sm-12">
		<?php if (!$hayDatos): ?>
			<div class="alert alert-info">No hay resguardos registrados aún.</div>
		<?php else: ?>
		<div class="row" style="margin-bottom: 12px;">
			<div class="col-sm-6">
				<div class="input-group">
					<span class="input-group-addon"><span class="glyphicon glyphicon-search"></span></span>
					<input type="text" class="form-control" id="buscarResguardo" placeholder="Buscar por ID, código, área, usuario, fecha, factura, modelo o serie...">
				</div>
			</div>
			<div class="col-sm-6">
				<button type="button" class="btn btn-info btn-sm" onclick="limpiarBusquedaResguardos()">
					<span class="glyphicon glyphicon-refresh"></span> Limpiar
				</button>
			</div>
		</div>
		<div class="table-responsive">
			<table class="table table-hover table-condensed table-bordered" id="tablaResguardos" style="text-align: center;">
				<caption><label>Historial de Resguardos</label></caption>
				<thead>
				<tr>
					<td><strong>Tipo</strong></td>
					<td><strong>ID</strong></td>
					<td><strong>Código(s) equipo</strong></td>
					<td><strong>Área destino</strong></td>
					<td><strong>Usuario</strong></td>
					<td><strong>Fecha resguardo</strong></td>
					<td><strong>Factura</strong></td>
					<td><strong>Modelo</strong></td>
					<td><strong>Serie</strong></td>
					<td><strong>Tipo equipo</strong></td>
					<td><strong>Imprimir</strong></td>
					<td><strong>Acciones</strong></td>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($lista as $ver): ?>
					<tr>
						<td>
							<?php if ($ver['tipo'] === 'multiple'): ?>
								<span class="label label-info">Múltiple (<?php echo (int)$ver['cantidad']; ?> equipos)</span>
							<?php else: ?>
								<span class="label label-default">Individual</span>
							<?php endif; ?>
						</td>
						<td><?php echo htmlspecialchars($ver['id_resguardo']); ?><?php if ($ver['tipo'] === 'multiple' && $ver['cantidad'] > 1): ?> <small class="text-muted">(<?php echo $ver['cantidad']; ?>)</small><?php endif; ?></td>
						<td><?php echo htmlspecialchars($ver['codigo_equipo']); ?></td>
						<td><?php echo htmlspecialchars($ver['area_destino']); ?></td>
						<td><?php echo htmlspecialchars($ver['usuario']); ?></td>
						<td><?php echo htmlspecialchars($ver['fecha_resguardo']); ?></td>
						<td><?php echo htmlspecialchars($ver['numero_factura'] ?: '-'); ?></td>
						<td><?php echo htmlspecialchars($ver['modelo'] ?: '-'); ?></td>
						<td><?php echo htmlspecialchars($ver['serie'] ?: '-'); ?></td>
						<td><?php echo htmlspecialchars($ver['tipo_equipo'] ?: '-'); ?></td>
						<td>
							<a href="../reportes/imprimirResguardo.php?id=<?php echo $ver['id_resguardo']; ?>" class="btn btn-xs btn-primary" title="Descargar PDF del resguardo" onclick="descargarPdfResguardo(this.href); return false;">
								<span class="glyphicon glyphicon-print"></span>
							</a>
						</td>
						<td>
							<button type="button" class="btn btn-danger btn-xs" onclick="eliminarResguardoOGrupo(<?php echo (int)$ver['id_resguardo']; ?>, <?php echo (int)$ver['cantidad']; ?>, '<?php echo htmlspecialchars(addslashes($ver['codigo_equipo'])); ?>', '<?php echo htmlspecialchars(addslashes($ver['area_destino'] ?? '')); ?>')" title="<?php echo $ver['tipo'] === 'multiple' ? 'Eliminar todo el grupo' : 'Eliminar resguardo'; ?>">
								<span class="glyphicon glyphicon-trash"></span> Eliminar
							</button>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</div>
</div>

<script type="text/javascript">
	function eliminarResguardoOGrupo(idResguardo, cantidad, codigoEquipo, areaDestino) {
		var msg = cantidad > 1
			? '<strong>Se eliminará el resguardo múltiple completo (' + cantidad + ' equipos):</strong><br><strong>Códigos:</strong> ' + codigoEquipo + '<br><strong>Área:</strong> ' + areaDestino
			: '<strong>Se eliminará el resguardo:</strong><br><strong>Código:</strong> ' + codigoEquipo + '<br><strong>Área destino:</strong> ' + areaDestino;
		alertify.confirm(
			'¿Eliminar este resguardo' + (cantidad > 1 ? ' (grupo completo)' : '') + '?',
			msg + '<br><br><span style="color: red;">Esta acción no se puede deshacer.</span>',
			function(){
				$.ajax({
					type: 'POST',
					url: '../procesos/resguardos/eliminarResguardoOGrupo.php',
					data: { id_resguardo: idResguardo },
					dataType: 'json',
					success: function(r){
						if (r && r.ok) {
							alertify.success(r.eliminados > 1 ? 'Se eliminaron ' + r.eliminados + ' resguardos.' : 'Resguardo eliminado correctamente.');
							var qh = (window.idHotelResguardosCtx ? ('?id_hotel=' + parseInt(window.idHotelResguardosCtx, 10)) : '');
							$('#contenido').load('resguardos/tablaResguardos.php' + qh);
						} else {
							alertify.error('Error al eliminar');
						}
					},
					error: function() {
						alertify.error('Error de comunicación con el servidor');
					}
				});
			},
			function(){
				alertify.error('Eliminación cancelada');
			}
		);
	}

	$(document).ready(function() {
		$('#buscarResguardo').on('keyup', function() {
			var valor = $.trim($(this).val()).toLowerCase();
			$('#tablaResguardos tbody tr').each(function() {
				var textoFila = $(this).text().toLowerCase();
				$(this).toggle(textoFila.indexOf(valor) > -1);
			});
		});
	});

	function limpiarBusquedaResguardos() {
		$('#buscarResguardo').val('');
		$('#tablaResguardos tbody tr').show();
	}
</script>
