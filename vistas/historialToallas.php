<?php
require_once "../seguridad.php";
requerirAutenticacion();
require_once "../clases/Toallas.php";

$rol = isset($_SESSION['rol']) ? strtoupper(trim((string)$_SESSION['rol'])) : 'ADMIN';
if ($rol !== 'TOALLEROS') {
	header('Location: inicio.php?mensaje=acceso_denegado');
	exit;
}

$toallas = new Toallas();
$idHotelSesion = isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : 1;

$mensaje = '';
$tipoMensaje = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$accionToallas = isset($_POST['accion_toallas']) ? trim((string)$_POST['accion_toallas']) : '';
	if ($accionToallas === 'reactivar_no_devuelto') {
		$idMovimiento = isset($_POST['id_movimiento']) ? (int)$_POST['id_movimiento'] : 0;
		$res = $toallas->reactivarDesdeHistorialNoDevuelto($idHotelSesion, $idMovimiento, (int)$_SESSION['iduser']);
		$mensaje = isset($res['mensaje']) ? $res['mensaje'] : 'Proceso finalizado.';
		$tipoMensaje = !empty($res['ok']) ? 'success' : 'danger';
	} elseif ($accionToallas === 'eliminar_movimiento') {
		$idMovimiento = isset($_POST['id_movimiento']) ? (int)$_POST['id_movimiento'] : 0;
		$res = $toallas->eliminarMovimientoHistorial($idHotelSesion, $idMovimiento);
		$mensaje = isset($res['mensaje']) ? $res['mensaje'] : 'Proceso finalizado.';
		$tipoMensaje = !empty($res['ok']) ? 'success' : 'danger';
	} elseif ($accionToallas === 'borrar_todo_historial') {
		$passwordConfirmacion = isset($_POST['password_toallero']) ? (string)$_POST['password_toallero'] : '';
		$res = $toallas->borrarTodoHistorialConPassword($idHotelSesion, (int)$_SESSION['iduser'], $passwordConfirmacion);
		$mensaje = isset($res['mensaje']) ? $res['mensaje'] : 'Proceso finalizado.';
		$tipoMensaje = !empty($res['ok']) ? 'success' : 'danger';
	} elseif ($accionToallas === 'actualizar_retencion') {
		$retencionOpcion = isset($_POST['retencion_opcion']) ? trim((string)$_POST['retencion_opcion']) : '1_MONTH';
		$partes = explode('_', $retencionOpcion);
		$cantidad = isset($partes[0]) ? (int)$partes[0] : 1;
		$unidad = isset($partes[1]) ? strtoupper(trim((string)$partes[1])) : 'MONTH';
		$res = $toallas->actualizarConfiguracionRetencion($idHotelSesion, $cantidad, $unidad);
		$mensaje = isset($res['mensaje']) ? $res['mensaje'] : 'Proceso finalizado.';
		$tipoMensaje = !empty($res['ok']) ? 'success' : 'danger';
	}
}

$configRetencion = $toallas->obtenerConfiguracionRetencion($idHotelSesion);
$retencionSeleccionada = ((int)$configRetencion['cantidad']) . '_' . strtoupper((string)$configRetencion['unidad']);
$movs = $toallas->obtenerMovimientosHotel($idHotelSesion, 200);

$titulo_pagina = 'Historial de Toallas - Hotel Arco';
require_once "menu.php";
?>
<div class="container">
	<style>
		/* Responsivo módulo historial toallas */
		.tabla-historial-toallas thead th {
			white-space: nowrap;
		}
		.tabla-historial-toallas td,
		.tabla-historial-toallas th {
			vertical-align: middle !important;
		}
		@media (max-width: 767px) {
			#filtroHistorialToallas {
				margin-bottom: 8px;
			}
			.tabla-historial-toallas {
				font-size: 12px;
			}
			.tabla-historial-toallas .btn {
				width: 100%;
				margin-top: 4px;
				white-space: normal;
			}
			.panel-body .form-inline {
				display: block;
			}
			.panel-body .form-inline > * {
				margin-bottom: 6px;
			}
		}
	</style>
	<div class="jumbotron">
		<h1>Historial de Toallas</h1>
		<h3>Hotel Arco</h3>
		<p>Consulta las entregas registradas por habitación y huésped.</p>
	</div>
	<?php if ($mensaje !== ''): ?>
		<div class="alert alert-<?php echo htmlspecialchars($tipoMensaje); ?>"><?php echo htmlspecialchars($mensaje); ?></div>
	<?php endif; ?>
	<div class="panel panel-info">
		<div class="panel-heading"><strong>Configuración de Retención de Historial</strong></div>
		<div class="panel-body" style="text-align:left;">
			<form method="POST" class="form-inline" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
				<input type="hidden" name="accion_toallas" value="actualizar_retencion">
				<label for="retencion_opcion" style="margin:0;">Borrar registros más antiguos que:</label>
				<select id="retencion_opcion" name="retencion_opcion" class="form-control input-sm">
					<option value="1_MONTH" <?php echo ($retencionSeleccionada === '1_MONTH') ? 'selected' : ''; ?>>1 mes</option>
					<option value="2_MONTH" <?php echo ($retencionSeleccionada === '2_MONTH') ? 'selected' : ''; ?>>2 meses</option>
					<option value="90_DAY" <?php echo ($retencionSeleccionada === '90_DAY') ? 'selected' : ''; ?>>90 días</option>
					<option value="1_YEAR" <?php echo ($retencionSeleccionada === '1_YEAR') ? 'selected' : ''; ?>>1 año</option>
				</select>
				<button type="submit" class="btn btn-primary btn-sm">Guardar</button>
			</form>
		</div>
	</div>
	<div class="panel panel-danger">
		<div class="panel-heading"><strong>Borrar Todo el Historial de Toallas</strong></div>
		<div class="panel-body" style="text-align:left;">
			<form method="POST" class="form-inline" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;" onsubmit="return confirmarBorradoMasivoToallas();">
				<input type="hidden" name="accion_toallas" value="borrar_todo_historial">
				<label for="password_toallero" style="margin:0;">Contraseña de toalleros:</label>
				<input type="password" id="password_toallero" name="password_toallero" class="form-control input-sm" placeholder="Ingresa contraseña" required>
				<button type="submit" class="btn btn-danger btn-sm">Borrar todo</button>
			</form>
			<small class="text-danger" style="display:block;margin-top:8px;">Esta acción elimina todos los registros del historial del hotel actual.</small>
		</div>
	</div>

	<div class="panel panel-default">
		<div class="panel-heading"><strong>Historial de Movimientos</strong></div>
		<div class="panel-body">
			<div class="row" style="margin-bottom:10px;">
				<div class="col-sm-6">
					<input type="text" id="filtroHistorialToallas" class="form-control input-sm" placeholder="Buscar en historial (habitación, huésped, usuario, estatus, observaciones...)">
				</div>
			</div>
			<style>
				.tabla-historial-toallas tbody tr.no-devuelto td {
					background-color: #5a1f24 !important;
					color: #fff !important;
				}
				.tabla-historial-toallas tbody tr.devuelto td {
					background-color: #1f4d2f !important;
					color: #fff !important;
				}
			</style>
			<div class="table-responsive">
			<table class="table table-hover table-bordered table-condensed tabla-historial-toallas">
				<thead>
					<tr>
						<th>#</th>
						<th>Fecha</th>
						<th>Habitación</th>
						<th>Huésped</th>
						<th>Cantidad</th>
						<th>Estatus Cierre</th>
						<th>Usuario</th>
						<th>Observaciones</th>
						<th>Acción</th>
					</tr>
				</thead>
				<tbody>
					<?php if ($movs && mysqli_num_rows($movs) > 0): ?>
						<?php while ($m = mysqli_fetch_assoc($movs)): ?>
							<?php
								$estadoCierre = isset($m['estado_cierre']) ? strtoupper((string)$m['estado_cierre']) : 'DEVUELTO';
								$filaClase = ($estadoCierre === 'NO_DEVUELTO') ? 'no-devuelto' : 'devuelto';
							?>
							<tr class="<?php echo $filaClase; ?>">
								<td><?php echo (int)$m['id_movimiento']; ?></td>
								<td><?php echo htmlspecialchars((string)$m['fecha_movimiento']); ?></td>
								<td><?php echo htmlspecialchars((string)$m['numero_habitacion']); ?></td>
								<td><?php echo htmlspecialchars((string)$m['nombre_huesped']); ?></td>
								<td><?php echo (int)$m['cantidad']; ?></td>
								<td><?php echo ($estadoCierre === 'NO_DEVUELTO') ? 'NO DEVUELTO' : 'DEVUELTO'; ?></td>
								<td><?php echo htmlspecialchars(trim((string)$m['nombre_usuario'])); ?></td>
								<td><?php echo htmlspecialchars((string)$m['observaciones']); ?></td>
								<td>
									<?php if ($estadoCierre === 'NO_DEVUELTO'): ?>
										<form method="POST" style="margin:0 0 6px;" onsubmit="return confirmarReactivacionToalla();">
											<input type="hidden" name="accion_toallas" value="reactivar_no_devuelto">
											<input type="hidden" name="id_movimiento" value="<?php echo (int)$m['id_movimiento']; ?>">
											<button type="submit" class="btn btn-info btn-xs">Reactivar solicitud</button>
										</form>
									<?php endif; ?>
									<form method="POST" style="margin:0;" onsubmit="return confirmarEliminarMovimientoToalla();">
										<input type="hidden" name="accion_toallas" value="eliminar_movimiento">
										<input type="hidden" name="id_movimiento" value="<?php echo (int)$m['id_movimiento']; ?>">
										<button type="submit" class="btn btn-danger btn-xs">Eliminar</button>
									</form>
								</td>
							</tr>
						<?php endwhile; ?>
					<?php else: ?>
						<tr><td colspan="9">No hay registros de toallas por el momento.</td></tr>
					<?php endif; ?>
				</tbody>
			</table>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	function confirmarReactivacionToalla() {
		return window.confirm('¿Seguro que deseas reactivar esta solicitud? Se moverá nuevamente a toallas activas.');
	}
	function confirmarEliminarMovimientoToalla() {
		return window.confirm('¿Seguro que deseas eliminar este registro del historial? Esta acción no se puede deshacer.');
	}
	function confirmarBorradoMasivoToallas() {
		return window.confirm('¿Seguro que deseas borrar TODO el historial de toallas? Esta acción no se puede deshacer.');
	}
	(function(){
		var input = document.getElementById('filtroHistorialToallas');
		if (!input) return;
		input.addEventListener('keyup', function(){
			var filtro = (input.value || '').toLowerCase();
			var filas = document.querySelectorAll('.tabla-historial-toallas tbody tr');
			for (var i = 0; i < filas.length; i++) {
				var fila = filas[i];
				var texto = (fila.textContent || fila.innerText || '').toLowerCase();
				fila.style.display = (texto.indexOf(filtro) !== -1) ? '' : 'none';
			}
		});
	})();
</script>
