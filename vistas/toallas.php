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
if (!$toallas->puedeOperarEnArco($idHotelSesion)) {
	header('Location: ../index.php?mensaje=acceso_denegado');
	exit;
}

$mensaje = '';
$tipoMensaje = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$numeroHabitacion = isset($_POST['numero_habitacion']) ? trim((string)$_POST['numero_habitacion']) : '';
	$nombreHuesped = isset($_POST['nombre_huesped']) ? trim((string)$_POST['nombre_huesped']) : '';
	$cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
	$observaciones = isset($_POST['observaciones']) ? trim((string)$_POST['observaciones']) : '';

	$res = $toallas->registrarEntregaHuesped($idHotelSesion, (int)$_SESSION['iduser'], $numeroHabitacion, $nombreHuesped, $cantidad, $observaciones);
	$mensaje = isset($res['mensaje']) ? $res['mensaje'] : 'Proceso finalizado.';
	$tipoMensaje = (!empty($res['ok'])) ? 'success' : 'danger';
}

$movs = $toallas->obtenerMovimientosHotel($idHotelSesion, 80);

$titulo_pagina = 'Control de Toallas - Hotel Arco';
require_once "menu.php";
?>
<div class="container">
	<div class="jumbotron">
		<h1>Control de Toallas</h1>
		<h3>Hotel Arco - Operación de Toalleros</h3>
		<p>Registra entregas de toallas a huéspedes por habitación.</p>
	</div>

	<?php if ($mensaje !== ''): ?>
		<div class="alert alert-<?php echo htmlspecialchars($tipoMensaje); ?>"><?php echo htmlspecialchars($mensaje); ?></div>
	<?php endif; ?>

	<div class="row">
		<div class="col-sm-5">
			<div class="panel panel-primary">
				<div class="panel-heading"><strong>Registrar Entrega a Huésped</strong></div>
				<div class="panel-body">
					<form method="POST" autocomplete="off">
						<div class="form-group">
							<label>Tipo de Toalla</label>
							<input type="text" class="form-control" value="Toalla de huésped" readonly>
						</div>
						<div class="form-group">
							<label>Número de Habitación</label>
							<input type="text" name="numero_habitacion" class="form-control" placeholder="Ej: 305B" required>
						</div>
						<div class="form-group">
							<label>Nombre del Huésped</label>
							<input type="text" name="nombre_huesped" class="form-control" placeholder="Nombre y apellido del huésped" required>
						</div>
						<div class="form-group">
							<label>Cantidad</label>
							<input type="number" min="1" name="cantidad" class="form-control" required>
						</div>
						<div class="form-group">
							<label>Observaciones</label>
							<textarea name="observaciones" class="form-control" rows="2" placeholder="Ej: Solicitud extra, cambio por toallas húmedas, etc."></textarea>
						</div>
						<button type="submit" class="btn btn-success btn-block">
							<i class="glyphicon glyphicon-ok"></i> Registrar Entrega
						</button>
					</form>
				</div>
			</div>
		</div>
		<div class="col-sm-7"></div>
	</div>

	<div class="row">
		<div class="col-sm-12">
			<div class="panel panel-default">
				<div class="panel-heading"><strong>Historial de Movimientos</strong></div>
				<div class="panel-body">
					<table class="table table-hover table-bordered table-condensed">
						<thead>
							<tr>
								<th>#</th>
								<th>Fecha</th>
								<th>Habitación</th>
								<th>Huésped</th>
								<th>Cantidad</th>
								<th>Usuario</th>
								<th>Observaciones</th>
							</tr>
						</thead>
						<tbody>
							<?php if ($movs && mysqli_num_rows($movs) > 0): ?>
								<?php while ($m = mysqli_fetch_assoc($movs)): ?>
								<tr>
									<td><?php echo (int)$m['id_movimiento']; ?></td>
									<td><?php echo htmlspecialchars($m['fecha_movimiento']); ?></td>
									<td><?php echo htmlspecialchars((string)$m['numero_habitacion']); ?></td>
									<td><?php echo htmlspecialchars((string)$m['nombre_huesped']); ?></td>
									<td><?php echo (int)$m['cantidad']; ?></td>
									<td><?php echo htmlspecialchars(trim((string)$m['nombre_usuario'])); ?></td>
									<td><?php echo htmlspecialchars((string)$m['observaciones']); ?></td>
								</tr>
								<?php endwhile; ?>
							<?php else: ?>
								<tr><td colspan="7">No hay entregas para mostrar.</td></tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
