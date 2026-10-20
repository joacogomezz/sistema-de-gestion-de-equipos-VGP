<?php 
	// Incluir archivo de seguridad
	require_once "../seguridad.php";
	
	// Requerir autenticación
	requerirAutenticacion();

	$rol_sesion = isset($_SESSION['rol']) ? strtoupper(trim((string)$_SESSION['rol'])) : 'ADMIN';
	if ($rol_sesion === 'TOALLEROS') {
		require_once "../clases/Toallas.php";
		$toallas = new Toallas();
		$id_hotel_toallas = isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : 1;
		$esAjaxToallas = isset($_POST['ajax_toallas']) && (string)$_POST['ajax_toallas'] === '1';
		$mensaje = '';
		$tipoMensaje = 'info';
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			$accionToallas = isset($_POST['accion_toallas']) ? trim((string)$_POST['accion_toallas']) : 'registrar';
			if ($accionToallas === 'devolver') {
				$idSolicitud = isset($_POST['id_solicitud']) ? (int)$_POST['id_solicitud'] : 0;
				$cantidadDevuelta = isset($_POST['cantidad_devuelta']) ? (int)$_POST['cantidad_devuelta'] : 0;
				$res = $toallas->registrarDevolucionSolicitud($id_hotel_toallas, $idSolicitud, $cantidadDevuelta, (int)$_SESSION['iduser']);
			} elseif ($accionToallas === 'archivar_sin_devolucion') {
				$idSolicitud = isset($_POST['id_solicitud']) ? (int)$_POST['id_solicitud'] : 0;
				$res = $toallas->archivarSinDevolucion($id_hotel_toallas, $idSolicitud, (int)$_SESSION['iduser']);
			} elseif ($accionToallas === 'archivar') {
				$idSolicitud = isset($_POST['id_solicitud']) ? (int)$_POST['id_solicitud'] : 0;
				$res = $toallas->archivarSolicitud($id_hotel_toallas, $idSolicitud, (int)$_SESSION['iduser']);
			} else {
				$numeroHabitacion = isset($_POST['numero_habitacion']) ? trim((string)$_POST['numero_habitacion']) : '';
				$nombreHuesped = isset($_POST['nombre_huesped']) ? trim((string)$_POST['nombre_huesped']) : '';
				$cantidad = isset($_POST['cantidad']) ? (int)$_POST['cantidad'] : 0;
				$observaciones = isset($_POST['observaciones']) ? trim((string)$_POST['observaciones']) : '';
				$res = $toallas->registrarSolicitudEntrega($id_hotel_toallas, (int)$_SESSION['iduser'], $numeroHabitacion, $nombreHuesped, $cantidad, $observaciones);
			}
			$mensaje = isset($res['mensaje']) ? $res['mensaje'] : 'Proceso finalizado.';
			$tipoMensaje = (!empty($res['ok'])) ? 'success' : 'danger';
			if ($esAjaxToallas) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode(array(
					'ok' => !empty($res['ok']),
					'mensaje' => $mensaje,
					'tipo' => $tipoMensaje
				));
				exit;
			}
		}
		$solicitudes = $toallas->obtenerSolicitudesActivasHotel($id_hotel_toallas, 120);
		$resumenToallas = $toallas->obtenerResumenToallas($id_hotel_toallas);
		$baseNoDevueltasHistorial = isset($resumenToallas['no_devueltas_historial']) ? (int)$resumenToallas['no_devueltas_historial'] : 0;
		$baseEntregadasArchivadas = isset($resumenToallas['entregadas_archivadas']) ? (int)$resumenToallas['entregadas_archivadas'] : 0;
		$titulo_pagina = 'Inicio - Control de Toallas';
		require_once "menu.php";
		?>
		<div class="container" id="toallasMainContainer">
			<style>
				/* Estilos específicos para tabla de toallas en tema oscuro */
				.tabla-toallas thead th {
					background: #a86f2e !important;
					color: #fff !important;
					border-color: #704a1f !important;
				}
				.tabla-toallas tbody td {
					color: #f5f5f5 !important;
					border-color: #333 !important;
				}
				.tabla-toallas tbody tr.success td,
				.tabla-toallas tbody tr.success:hover td {
					background-color: #1f4d2f !important; /* verde oscuro */
				}
				.tabla-toallas tbody tr.danger td,
				.tabla-toallas tbody tr.danger:hover td {
					background-color: #5a1f24 !important; /* rojo oscuro */
				}
				.tabla-toallas input.form-control {
					background: #1a1a1a !important;
					color: #fff !important;
					border: 1px solid #555 !important;
				}
			</style>
			<div class="jumbotron">
				<h1>Inicio - Control de Toallas</h1>
				<h3>Hotel Arco</h3>
				<p>Registra entregas de toallas a huéspedes por habitación.</p>
			</div>
			<div id="toallasAjaxAlert">
				<?php if ($mensaje !== ''): ?>
					<div class="alert alert-<?php echo htmlspecialchars($tipoMensaje); ?>"><?php echo htmlspecialchars($mensaje); ?></div>
				<?php endif; ?>
			</div>
			<style>
				/* Ajustes generales módulo toalleros */
				.toallas-top-row .panel-heading {
					word-break: break-word;
				}
				.toallas-alert-entregadas,
				.toallas-alert-faltantes {
					border-radius: 10px;
				}
				/* Cajas personalizadas (sin estilos alert de Bootstrap) */
				.toallas-alert-entregadas {
					background: #222 !important;
					border: 1px solid #2f2f2f !important;
					color: #fff !important;
					padding: 16px 14px !important;
				}
				.toallas-alert-faltantes {
					background: #222 !important;
					border: 1px solid #2f2f2f !important;
					color: #fff !important;
					padding: 18px 10px !important;
					display: flex;
					flex-direction: column;
					align-items: center;
					justify-content: center;
					text-align: center;
				}
				.tabla-toallas thead th {
					white-space: nowrap;
				}
				.tabla-toallas td,
				.tabla-toallas th {
					vertical-align: middle !important;
				}

				@media (min-width: 768px) {
					.toallas-top-row {
						display: flex;
						align-items: stretch;
					}
					.toallas-top-row > [class*="col-sm-"] {
						display: flex;
					}
					.toallas-top-row .panel {
						width: 100%;
						margin-bottom: 0;
					}
					#toallasResumenPanel {
						display: flex;
						flex-direction: column;
					}
					#toallasResumenPanel .panel-body {
						flex: 1;
						display: flex;
						flex-direction: column;
						justify-content: space-between;
					}
					.toallas-alert-entregadas {
						min-height: 72px;
						font-size: 20px;
						font-weight: 700;
					}
					.toallas-alert-entregadas #resumenToallasEntregadas {
						color: #2dd48a !important;
					}
					.toallas-alert-faltantes {
						flex: 1;
						min-height: 300px;
					}
					.toallas-alert-faltantes .titulo-faltantes {
						font-size: 24px;
						font-weight: 700;
						line-height: 1.2;
						color: #fff !important;
					}
					.toallas-alert-faltantes .numero-faltantes {
						font-size: 76px;
						font-weight: 900;
						line-height: 1;
						margin-top: 8px;
						color: #ff5b5b !important;
					}
				/* Color de números en cualquier tamaño (incluye móvil) */
				.toallas-alert-entregadas #resumenToallasEntregadas {
					color: #2dd48a !important;
				}
				.toallas-alert-faltantes #resumenToallasPendientes {
					color: #ff5b5b !important;
				}
				}
				@media (max-width: 767px) {
					.toallas-top-row {
						display: block;
					}
					.toallas-top-row .col-sm-5,
					.toallas-top-row .col-sm-7 {
						width: 100%;
						float: none;
					}
					#toallasResumenPanel {
						margin-top: 12px;
					}
					.toallas-alert-entregadas {
						min-height: auto;
						padding: 12px 10px !important;
						font-size: 16px;
					}
					.toallas-alert-faltantes {
						min-height: 180px;
						padding: 14px 10px !important;
						align-items: center !important;
						justify-content: center !important;
						text-align: center !important;
					}
					.toallas-alert-faltantes .titulo-faltantes {
						font-size: 20px;
					}
					.toallas-alert-faltantes .numero-faltantes {
						font-size: 56px;
						width: 100%;
						text-align: center;
					}
					.tabla-toallas {
						font-size: 12px;
					}
					.tabla-toallas .btn {
						width: 100%;
						margin-top: 4px;
						white-space: normal;
					}
					.tabla-toallas input.form-control {
						width: 100% !important;
						min-width: 80px;
					}
				}
				/* En cualquier tamaño, el bloque rojo domina el contenedor del resumen */
				#toallasResumenPanel .panel-body {
					display: flex;
					flex-direction: column;
				}
				.toallas-alert-faltantes {
					flex: 1;
					min-height: 280px;
				}
				/* Forzar color de números en todos los tamaños (incluye celular) */
				#toallasResumenPanel #resumenToallasEntregadas {
					color: #2dd48a !important;
					font-size: 42px !important;
					font-weight: 900 !important;
					line-height: 1.05 !important;
					display: inline-block;
					margin-left: 8px;
				}
				#toallasResumenPanel #resumenToallasPendientes {
					color: #ff5b5b !important;
					font-size: 96px !important;
					font-weight: 900 !important;
					line-height: 1 !important;
					display: inline-block;
				}
			</style>
			<div class="row toallas-top-row">
				<div class="col-sm-5">
					<div class="panel panel-primary">
						<div class="panel-heading"><strong>Registrar Entrega a Huésped</strong></div>
						<div class="panel-body">
							<form method="POST" autocomplete="off" class="js-toallas-form">
								<input type="hidden" name="accion_toallas" value="registrar">
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

				<div class="col-sm-7">
					<div class="panel panel-info" id="toallasResumenPanel">
						<div class="panel-heading"><strong>Resumen en Tiempo Real</strong></div>
						<div class="panel-body" style="text-align:left;">
							<div class="toallas-alert-entregadas" style="margin-bottom:10px;">
								<strong class="texto-entregadas">Cantidad de toallas entregadas:</strong>
								<span id="resumenToallasEntregadas"><?php echo isset($resumenToallas['total_entregadas']) ? (int)$resumenToallas['total_entregadas'] : 0; ?></span>
							</div>
							<div class="toallas-alert-faltantes" style="margin-bottom:0;">
								<div class="titulo-faltantes">Cantidad de toallas faltantes</div>
								<div class="numero-faltantes" id="resumenToallasPendientes"><?php echo isset($resumenToallas['total_faltantes']) ? (int)$resumenToallas['total_faltantes'] : 0; ?></div>
							</div>
							<span id="resumenToallasNoDevueltas" style="display:none;"><?php echo $baseNoDevueltasHistorial; ?></span>
							<span id="resumenToallasArchivadasBase" style="display:none;"><?php echo $baseEntregadasArchivadas; ?></span>
						</div>
					</div>
				</div>
			</div>

			<div class="panel panel-default" style="margin-top: 18px;" id="toallasListadoPanel">
				<div class="panel-heading"><strong>Listado General de Solicitudes</strong></div>
				<div class="panel-body">
					<div class="row" style="margin-bottom:10px;">
						<div class="col-sm-6">
							<input type="text" id="filtroSolicitudesToallas" class="form-control input-sm" placeholder="Buscar en solicitudes (habitación, huésped, estatus, observaciones...)">
						</div>
					</div>
					<div class="table-responsive">
					<table class="table table-hover table-bordered table-condensed tabla-toallas">
						<thead>
							<tr>
								<th>Habitación</th>
								<th>Huésped</th>
								<th>Entregadas</th>
								<th>Regresadas</th>
								<th>Pendientes</th>
								<th>Registro</th>
								<th>Tiempo</th>
								<th>Estatus</th>
								<th>Devolución</th>
								<th>Acción</th>
							</tr>
						</thead>
						<tbody>
							<?php if ($solicitudes && mysqli_num_rows($solicitudes) > 0): ?>
								<?php while ($s = mysqli_fetch_assoc($solicitudes)): ?>
									<?php
										$entregadas = (int)$s['cantidad'];
										$regresadas = (int)$s['cantidad_regresada'];
										$pendientes = max(0, $entregadas - $regresadas);
										$tsSolicitud = strtotime((string)$s['fecha_solicitud']);
										$horasTranscurridas = ($tsSolicitud !== false) ? ((time() - $tsSolicitud) / 3600) : 0;
										$esVencida = ($horasTranscurridas >= 8);
										$estadoTexto = $esVencida ? 'VENCIDA (+8h)' : 'ACTIVA';
										$tsSolicitudMs = ($tsSolicitud !== false) ? ((int)$tsSolicitud * 1000) : 0;
										$filaClase = $esVencida ? 'danger fila-solicitud-toalla' : 'success fila-solicitud-toalla';
									?>
									<tr class="<?php echo $filaClase; ?>" data-request-ts="<?php echo (int)$tsSolicitudMs; ?>">
										<td><?php echo htmlspecialchars((string)$s['numero_habitacion']); ?></td>
										<td><?php echo htmlspecialchars((string)$s['nombre_huesped']); ?></td>
										<td class="valor-entregadas"><?php echo $entregadas; ?></td>
										<td><?php echo $regresadas; ?></td>
										<td class="valor-pendientes"><strong><?php echo $pendientes; ?></strong></td>
										<td><?php echo htmlspecialchars((string)$s['fecha_solicitud']); ?></td>
										<td class="tiempo-transcurrido-toalla"><?php echo number_format(max(0, $horasTranscurridas), 1); ?>h</td>
										<td class="estado-solicitud-toalla"><?php echo htmlspecialchars($estadoTexto); ?></td>
										<td>
											<form method="POST" class="form-inline js-toallas-form" style="margin:0;">
												<input type="hidden" name="accion_toallas" value="devolver">
												<input type="hidden" name="id_solicitud" value="<?php echo (int)$s['id_solicitud']; ?>">
												<input type="number" min="1" max="<?php echo $pendientes; ?>" name="cantidad_devuelta" class="form-control input-sm" style="width:80px;" required>
												<button type="submit" class="btn btn-warning btn-sm">Registrar devolución</button>
											</form>
											<form method="POST" class="form-inline js-toallas-form" style="margin-top:6px;">
												<input type="hidden" name="accion_toallas" value="archivar_sin_devolucion">
												<input type="hidden" name="id_solicitud" value="<?php echo (int)$s['id_solicitud']; ?>">
												<button type="submit" class="btn btn-danger btn-sm">Archivar sin devolución</button>
											</form>
										</td>
									</tr>
								<?php endwhile; ?>
							<?php else: ?>
								<tr>
									<td colspan="10" class="text-center">No hay solicitudes activas por el momento.</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
					</div>
				</div>
			</div>
		</div>
		</div><!-- cierre del container del menú -->
		<script type="text/javascript">
			(function(){
				function escapeHtml(s){
					return String(s || '')
						.replace(/&/g, '&amp;')
						.replace(/</g, '&lt;')
						.replace(/>/g, '&gt;')
						.replace(/"/g, '&quot;')
						.replace(/'/g, '&#039;');
				}

				function mostrarAlertaToallas(tipo, mensaje){
					var box = document.getElementById('toallasAjaxAlert');
					if (!box) return;
					box.innerHTML = '<div class="alert alert-' + (tipo || 'info') + '">' + escapeHtml(mensaje || '') + '</div>';
				}

				function refrescarPanelesToallas(){
					fetch(window.location.href, { credentials: 'same-origin' })
						.then(function(r){ return r.text(); })
						.then(function(html){
							var parser = new DOMParser();
							var doc = parser.parseFromString(html, 'text/html');
							var nuevoResumen = doc.getElementById('toallasResumenPanel');
							var nuevoListado = doc.getElementById('toallasListadoPanel');
							var resumen = document.getElementById('toallasResumenPanel');
							var listado = document.getElementById('toallasListadoPanel');
							if (nuevoResumen && resumen) resumen.outerHTML = nuevoResumen.outerHTML;
							if (nuevoListado && listado) listado.outerHTML = nuevoListado.outerHTML;
							inicializarFormulariosAjaxToallas();
							actualizarTarjetasToallas();
							actualizarResumenToallas();
						});
				}

				function inicializarFormulariosAjaxToallas(){
					var forms = document.querySelectorAll('.js-toallas-form');
					for (var i = 0; i < forms.length; i++) {
						(function(form){
							form.addEventListener('submit', function(e){
								e.preventDefault();
								var datos = new FormData(form);
								datos.append('ajax_toallas', '1');
								fetch(window.location.href, {
									method: 'POST',
									body: datos,
									credentials: 'same-origin',
									headers: { 'X-Requested-With': 'XMLHttpRequest' }
								})
								.then(function(r){ return r.json(); })
								.then(function(resp){
									mostrarAlertaToallas((resp && resp.tipo) ? resp.tipo : 'info', (resp && resp.mensaje) ? resp.mensaje : 'Proceso finalizado.');
									var accion = datos.get('accion_toallas');
									if (accion === 'registrar' && resp && resp.ok) {
										form.reset();
									}
									refrescarPanelesToallas();
								})
								.catch(function(){
									mostrarAlertaToallas('danger', 'Error de comunicación. Intenta nuevamente.');
								});
							}, { once: true });
						})(forms[i]);
					}
				}

				function formatoTiempo(ms){
					if (ms < 0) ms = 0;
					var totalSeg = Math.floor(ms / 1000);
					var h = Math.floor(totalSeg / 3600);
					var m = Math.floor((totalSeg % 3600) / 60);
					var s = totalSeg % 60;
					function pad(n){ return (n < 10 ? '0' : '') + n; }
					return h + ':' + pad(m) + ':' + pad(s);
				}

				function actualizarTarjetasToallas(){
					var tarjetas = document.querySelectorAll('.fila-solicitud-toalla');
					var ahora = Date.now();
					for (var i = 0; i < tarjetas.length; i++) {
						var tarjeta = tarjetas[i];
						var ts = parseInt(tarjeta.getAttribute('data-request-ts') || '0', 10);
						if (!ts) continue;
						var diff = ahora - ts;
						var vencida = diff >= (8 * 60 * 60 * 1000);

						var tiempoEl = tarjeta.querySelector('.tiempo-transcurrido-toalla');
						if (tiempoEl) tiempoEl.textContent = formatoTiempo(diff);

						var estadoEl = tarjeta.querySelector('.estado-solicitud-toalla');
						if (estadoEl) estadoEl.textContent = vencida ? 'VENCIDA (+8h)' : 'ACTIVA';

						if (vencida) {
							tarjeta.classList.remove('success');
							tarjeta.classList.add('danger');
						} else {
							tarjeta.classList.remove('danger');
							tarjeta.classList.add('success');
						}
					}
				}

				function actualizarResumenToallas(){
					var filas = document.querySelectorAll('.fila-solicitud-toalla');
					var totalEntregadas = 0;
					var totalPendientes = 0;
					var noDevueltasEl = document.getElementById('resumenToallasNoDevueltas');
					var archivadasEl = document.getElementById('resumenToallasArchivadasBase');
					var noDevueltasHistorial = parseInt((noDevueltasEl ? noDevueltasEl.textContent : '0').replace(/[^\d]/g, ''), 10);
					var entregadasArchivadas = parseInt((archivadasEl ? archivadasEl.textContent : '0').replace(/[^\d]/g, ''), 10);
					if (isNaN(noDevueltasHistorial)) noDevueltasHistorial = 0;
					if (isNaN(entregadasArchivadas)) entregadasArchivadas = 0;
					for (var i = 0; i < filas.length; i++) {
						var fila = filas[i];
						var entregadasEl = fila.querySelector('.valor-entregadas');
						var pendientesEl = fila.querySelector('.valor-pendientes');
						var entregadas = parseInt((entregadasEl ? entregadasEl.textContent : '0').replace(/[^\d]/g, ''), 10);
						var pendientes = parseInt((pendientesEl ? pendientesEl.textContent : '0').replace(/[^\d]/g, ''), 10);
						if (!isNaN(entregadas)) totalEntregadas += entregadas;
						if (!isNaN(pendientes)) totalPendientes += pendientes;
					}
					totalEntregadas += entregadasArchivadas;
					totalPendientes += noDevueltasHistorial;
					var resumenEntregadas = document.getElementById('resumenToallasEntregadas');
					var resumenPendientes = document.getElementById('resumenToallasPendientes');
					var resumenNoDevueltas = document.getElementById('resumenToallasNoDevueltas');
					if (resumenEntregadas) resumenEntregadas.textContent = totalEntregadas;
					if (resumenPendientes) resumenPendientes.textContent = totalPendientes;
					if (resumenNoDevueltas) resumenNoDevueltas.textContent = noDevueltasHistorial;
				}

				function inicializarFiltroSolicitudesToallas(){
					var input = document.getElementById('filtroSolicitudesToallas');
					if (!input || input.getAttribute('data-ready') === '1') return;
					input.setAttribute('data-ready', '1');
					input.addEventListener('keyup', function(){
						var filtro = (input.value || '').toLowerCase();
						var filas = document.querySelectorAll('.tabla-toallas tbody tr');
						for (var i = 0; i < filas.length; i++) {
							var fila = filas[i];
							var texto = (fila.textContent || fila.innerText || '').toLowerCase();
							fila.style.display = (texto.indexOf(filtro) !== -1) ? '' : 'none';
						}
					});
				}

				actualizarTarjetasToallas();
				actualizarResumenToallas();
				inicializarFiltroSolicitudesToallas();
				inicializarFormulariosAjaxToallas();
				setInterval(function(){
					actualizarTarjetasToallas();
					actualizarResumenToallas();
				}, 1000);
			})();
		</script>
		</body>
		</html>
		<?php
		exit;
	}
	?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Sistema de Gestión de Equipos - Hotel Villa Group</title>
	<style>
		:root {
			--villa-dark: #1a1a1a;
			--villa-darker: #0f0f0f;
			--villa-light: #2a2a2a;
			--villa-white: #ffffff;
			--villa-gold: #c29a4b;
			--villa-gold-hover: #d4a85c;
			--villa-orange: #a0522d;
			--villa-orange-hover: #b05a35;
			--villa-text: #ffffff;
			--villa-text-muted: #cccccc;
			--villa-border: #333333;
		}

		html, body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			background: linear-gradient(135deg, var(--villa-dark) 0%, var(--villa-darker) 100%);
			min-height: 100vh;
			height: 100%;
			margin: 0;
			padding: 0;
			color: var(--villa-text);
		}
		
		/* Asegurar que el fondo cubra toda la página */
		html {
			background: linear-gradient(135deg, var(--villa-dark) 0%, var(--villa-darker) 100%);
			background-attachment: fixed;
		}
		
		/* Eliminar cualquier margen o padding que pueda causar espacios blancos */
		* {
			box-sizing: border-box;
		}
		
		/* Asegurar que el contenedor principal también tenga el fondo correcto */
		.container {
			background: transparent;
		}
		
		/* Sobrescribir el padding-top del menu.css para eliminar espacios blancos */
		body {
			padding-top: 0 !important;
		}
		
		/* Asegurar que el navbar no deje espacios blancos */
		.navbar {
			background: var(--villa-dark) !important;
		}
		
		/* Asegurar que el fondo se mantenga fijo durante el scroll */
		body {
			background-attachment: fixed !important;
			background-repeat: no-repeat !important;
			background-size: cover !important;
		}

		.jumbotron {
			background: var(--villa-light) !important;
			border: 2px solid var(--villa-border) !important;
			border-radius: 15px !important;
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
		}

		.jumbotron h1, .jumbotron h2, .jumbotron p {
			color: var(--villa-gold) !important;
		}

		.panel {
			background: var(--villa-light) !important;
			border: 2px solid var(--villa-border) !important;
			border-radius: 10px !important;
			box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3) !important;
		}

		.panel-heading {
			background: var(--villa-gold) !important;
			border-color: var(--villa-border) !important;
			color: var(--villa-white) !important;
			border-radius: 8px 8px 0 0 !important;
		}

		.panel-body {
			background: var(--villa-light) !important;
			color: var(--villa-text) !important;
			text-align: center !important;
		}

		.panel-body h2 {
			color: var(--villa-gold) !important;
			font-size: 2.5em !important;
			font-weight: bold !important;
			margin-bottom: 10px !important;
		}

		.panel-body p {
			color: var(--villa-text-muted) !important;
			font-size: 14px !important;
			margin: 0 !important;
		}

		.panel-danger .panel-heading {
			background: #dc3545 !important;
			color: var(--villa-white) !important;
		}

		.panel-info .panel-heading {
			background: var(--villa-orange) !important;
			color: var(--villa-white) !important;
		}

		.panel-default .panel-heading {
			background: var(--villa-gold) !important;
			color: var(--villa-white) !important;
		}

		.list-group-item {
			background: var(--villa-light) !important;
			border: 2px solid var(--villa-border) !important;
			color: var(--villa-text) !important;
			transition: all 0.3s ease !important;
			margin-bottom: 5px !important;
		}

		.list-group-item:hover {
			background: var(--villa-gold) !important;
			color: var(--villa-white) !important;
			transform: translateX(5px) !important;
			box-shadow: 0 5px 15px rgba(194, 154, 75, 0.3) !important;
		}

		.list-group-item .glyphicon {
			margin-right: 10px !important;
			color: var(--villa-gold) !important;
		}

		.list-group-item:hover .glyphicon {
			color: var(--villa-white) !important;
		}

		.actions-container {
			max-width: 400px !important;
			margin: 0 auto !important;
		}
	</style>
</head>
<body>
	<?php require_once "menu.php"; ?>

	<div class="container">
		<div class="jumbotron">
			<h1>Sistema de Gestión de Equipos</h1>
			<h2>Hotel Villa Group</h2>
			<p>Bienvenido al sistema de gestión de equipos del Hotel Villa Group. Aquí podrás administrar todos los equipos, registrar altas y bajas, y gestionar el inventario de manera eficiente.</p>
		</div>

		<div class="row">
			<div class="col-sm-4">
				<div class="panel panel-primary">
					<div class="panel-heading">
						<h3 class="panel-title">Equipos Activos</h3>
					</div>
					<div class="panel-body">
						<?php
							require_once "../clases/Conexion.php";
							$c = new conectar();
							$conexion = $c->conexion();
							$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
							$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
							$whereHotel = '';
							if ($id_hotel !== null) {
								$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
								if ($chk && mysqli_num_rows($chk) > 0) {
									$whereHotel = " AND e.id_departamento IN (SELECT id_departamento FROM departamentos WHERE id_hotel = '" . $id_hotel . "')";
								}
							}
							$sql = "SELECT COUNT(*) as total FROM equipos e WHERE e.estado='Activo' $whereHotel";
							$result = mysqli_query($conexion, $sql);
							$ver = mysqli_fetch_row($result);
						?>
						<h2><?php echo (int) $ver[0]; ?></h2>
						<p>Equipos en funcionamiento</p>
					</div>
				</div>
			</div>

			<div class="col-sm-4">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Dados de Baja</h3>
					</div>
					<div class="panel-body">
						<?php
							$sql = "SELECT COUNT(*) as total FROM equipos e WHERE e.estado='Dado de Baja' $whereHotel";
							$result = mysqli_query($conexion, $sql);
							$ver = mysqli_fetch_row($result);
						?>
						<h2><?php echo (int) $ver[0]; ?></h2>
						<p>Equipos dados de baja</p>
					</div>
				</div>
			</div>

			<div class="col-sm-4">
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3 class="panel-title">Total Equipos</h3>
					</div>
					<div class="panel-body">
						<?php
							$sql = "SELECT COUNT(*) as total FROM equipos e WHERE 1=1 $whereHotel";
							$result = mysqli_query($conexion, $sql);
							$ver = mysqli_fetch_row($result);
						?>
						<h2><?php echo (int) $ver[0]; ?></h2>
						<p>Total de equipos registrados</p>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-12">
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="glyphicon glyphicon-file"></i> Reportes Ejecutivos</h3>
					</div>
					<div class="panel-body">
						<div class="actions-container">
							<div class="list-group">
								<a href="#" class="list-group-item" onclick="descargarReporteDirecto('../reportes/reporteTodasLasBajas.php','reporte_todas_las_bajas.pdf'); return false;">
									<span class="glyphicon glyphicon-remove-circle"></span> Reporte de Todas las Bajas del Sistema
									<span class="badge" style="background-color: #d9534f;">PDF</span>
								</a>
								<a href="#" class="list-group-item" onclick="descargarReporteDirecto('../reportes/reporteEquiposActivos.php','reporte_equipos_activos.pdf'); return false;">
									<span class="glyphicon glyphicon-ok-circle"></span> Reporte de Equipos Activos del Sistema
									<span class="badge" style="background-color: #5cb85c;">PDF</span>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-sm-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Acciones Rápidas</h3>
					</div>
					<div class="panel-body">
						<div class="actions-container">
							<div class="list-group">
								<a href="equipos.php" class="list-group-item">
									<span class="glyphicon glyphicon-cog"></span> Gestionar Equipos
								</a>
								<a href="solicitudAltaEquipos.php" class="list-group-item">
									<span class="glyphicon glyphicon-file"></span> Solicitud para dar de Alta Equipos
								</a>
								<a href="bajas.php" class="list-group-item">
									<span class="glyphicon glyphicon-minus"></span> Registrar Baja de Equipo
								</a>
								<a href="bajasMasivas.php" class="list-group-item">
									<span class="glyphicon glyphicon-list"></span> Bajas Masivas
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		function descargarReporteDirecto(url, nombreArchivo) {
			fetch(url, { credentials: 'same-origin' })
				.then(function(res){
					if (!res.ok) throw new Error('No se pudo generar el reporte');
					return res.blob();
				})
				.then(function(blob){
					var objectUrl = window.URL.createObjectURL(blob);
					var a = document.createElement('a');
					a.href = objectUrl;
					a.download = nombreArchivo || 'reporte.pdf';
					document.body.appendChild(a);
					a.click();
					document.body.removeChild(a);
					window.URL.revokeObjectURL(objectUrl);
				})
				.catch(function(){
					if (typeof alertify !== 'undefined') {
						alertify.error('Error al descargar el reporte.');
					} else {
						alert('Error al descargar el reporte.');
					}
				});
		}
	</script>
</body>
</html>