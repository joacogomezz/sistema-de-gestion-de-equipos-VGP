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
		// Pantalla nueva del toallero; la anterior sigue disponible con ?clasica=1
		if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['clasica'])) {
			define('CC_TOALLAS', 1);
			require __DIR__ . '/toallas/panel.php';
			exit;
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
					background: var(--superficie-hundida) !important;
					color: var(--texto-secundario) !important;
					border-color: var(--borde) !important;
				}
				.tabla-toallas tbody td {
					color: var(--texto) !important;
					border-color: var(--borde) !important;
				}
				.tabla-toallas tbody tr.success td,
				.tabla-toallas tbody tr.success:hover td {
					background-color: var(--exito-suave) !important;
				}
				.tabla-toallas tbody tr.danger td,
				.tabla-toallas tbody tr.danger:hover td {
					background-color: var(--peligro-suave) !important;
				}
				.tabla-toallas input.form-control {
					background: var(--superficie) !important;
					color: var(--texto) !important;
					border: 1px solid var(--borde-fuerte) !important;
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
					background: var(--superficie-hundida) !important;
					border: 1px solid var(--borde) !important;
					color: var(--texto) !important;
					padding: 16px 14px !important;
				}
				.toallas-alert-faltantes {
					background: var(--superficie-hundida) !important;
					border: 1px solid var(--borde) !important;
					color: var(--texto) !important;
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
						color: var(--exito) !important;
					}
					.toallas-alert-faltantes {
						flex: 1;
						min-height: 300px;
					}
					.toallas-alert-faltantes .titulo-faltantes {
						font-size: 24px;
						font-weight: 700;
						line-height: 1.2;
						color: var(--texto) !important;
					}
					.toallas-alert-faltantes .numero-faltantes {
						font-size: 76px;
						font-weight: 900;
						line-height: 1;
						margin-top: 8px;
						color: var(--peligro) !important;
					}
				/* Color de números en cualquier tamaño (incluye móvil) */
				.toallas-alert-entregadas #resumenToallasEntregadas {
					color: var(--exito) !important;
				}
				.toallas-alert-faltantes #resumenToallasPendientes {
					color: var(--peligro) !important;
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
					color: var(--exito) !important;
					font-size: 42px !important;
					font-weight: 900 !important;
					line-height: 1.05 !important;
					display: inline-block;
					margin-left: 8px;
				}
				#toallasResumenPanel #resumenToallasPendientes {
					color: var(--peligro) !important;
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

<?php
	require_once "../clases/Conexion.php";
	$c = new conectar();
	$conexion = $c->conexion();
	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	$whereHotel = '';
	$whereHotelDirecto = '';
	if ($id_hotel !== null) {
		$whereHotel = " AND e.id_departamento IN (SELECT id_departamento FROM departamentos WHERE id_hotel = " . $id_hotel . ")";
		$whereHotelDirecto = " AND id_hotel = " . $id_hotel;
	}

	function ccValor($conexion, $sql) {
		$r = mysqli_query($conexion, $sql);
		$f = $r ? mysqli_fetch_row($r) : null;
		return $f ? (int)$f[0] : 0;
	}

	$porEstado = array('Activo' => 0, 'En Mantenimiento' => 0, 'En Reparación' => 0, 'Dado de Baja' => 0);
	$r = mysqli_query($conexion, "SELECT e.estado, COUNT(*) FROM equipos e WHERE 1=1 $whereHotel GROUP BY e.estado");
	$total = 0;
	while ($r && ($f = mysqli_fetch_row($r))) {
		$total += (int)$f[1];
		if (isset($porEstado[$f[0]])) $porEstado[$f[0]] += (int)$f[1];
	}
	$activos = $porEstado['Activo'];
	$mantenimiento = $porEstado['En Mantenimiento'];
	$reparacion = $porEstado['En Reparación'];
	$bajas = $porEstado['Dado de Baja'];
	$numDeptos = ccValor($conexion, "SELECT COUNT(*) FROM departamentos WHERE 1=1 $whereHotelDirecto");
	$bajasMes = ccValor($conexion, "SELECT COUNT(*) FROM bajas_equipos WHERE fecha_baja >= DATE_FORMAT(CURDATE(), '%Y-%m-01') $whereHotelDirecto");

	$porDepto = array();
	$r = mysqli_query($conexion, "SELECT d.nombre, COUNT(*) n FROM equipos e JOIN departamentos d ON d.id_departamento = e.id_departamento WHERE 1=1 $whereHotel GROUP BY d.id_departamento, d.nombre ORDER BY n DESC LIMIT 8");
	while ($r && ($f = mysqli_fetch_row($r))) $porDepto[] = array($f[0], (int)$f[1]);
	$maxDepto = $porDepto ? max(1, $porDepto[0][1]) : 1;

	// Actividad reciente: se combinan las últimas altas, resguardos, bajas y solicitudes.
	$actividad = array();
	$r = mysqli_query($conexion, "SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo, d.nombre, e.fecha_registro FROM equipos e LEFT JOIN departamentos d ON d.id_departamento = e.id_departamento WHERE e.fecha_registro IS NOT NULL $whereHotel ORDER BY e.id_equipo DESC LIMIT 6");
	while ($r && ($f = mysqli_fetch_row($r))) {
		$actividad[] = array('tipo' => 'alta', 'orden' => $f[6] . ' 00:00:00', 'fecha' => $f[6], 'hora' => false,
			'titulo' => 'Alta de equipo', 'codigo' => $f[1], 'detalle' => trim($f[2] . ' · ' . trim($f[3] . ' ' . $f[4]), ' ·'), 'meta' => (string)$f[5]);
	}
	$r = mysqli_query($conexion, "SELECT r.id_resguardo, e.codigo_equipo, r.usuario, r.area_destino, r.fecha_registro FROM resguardos r JOIN equipos e ON e.id_equipo = r.id_equipo WHERE 1=1 $whereHotel ORDER BY r.fecha_registro DESC, r.id_resguardo DESC LIMIT 6");
	while ($r && ($f = mysqli_fetch_row($r))) {
		$actividad[] = array('tipo' => 'resguardo', 'orden' => $f[4], 'fecha' => $f[4], 'hora' => true,
			'titulo' => 'Resguardo de', 'codigo' => $f[1], 'detalle' => 'para ' . $f[2], 'meta' => (string)$f[3]);
	}
	$r = mysqli_query($conexion, "SELECT b.id_baja, e.codigo_equipo, e.descripcion, b.motivo_baja, d.nombre, b.fecha_baja FROM bajas_equipos b LEFT JOIN equipos e ON e.id_equipo = b.id_equipo LEFT JOIN departamentos d ON d.id_departamento = b.id_departamento WHERE 1=1" . ($id_hotel !== null ? " AND b.id_hotel = $id_hotel" : '') . " ORDER BY b.fecha_baja DESC, b.id_baja DESC LIMIT 6");
	while ($r && ($f = mysqli_fetch_row($r))) {
		$actividad[] = array('tipo' => 'baja', 'orden' => $f[5] . ' 00:00:00', 'fecha' => $f[5], 'hora' => false,
			'titulo' => 'Baja de', 'codigo' => $f[1], 'detalle' => trim($f[2] . ($f[3] ? ' (' . $f[3] . ')' : '')), 'meta' => (string)$f[4]);
	}
	$r = mysqli_query($conexion, "SELECT bm.id_baja_masiva, bm.total_equipos, bm.motivo_baja, d.nombre, bm.fecha_baja FROM bajas_masivas bm LEFT JOIN departamentos d ON d.id_departamento = bm.id_departamento WHERE 1=1" . ($id_hotel !== null ? " AND bm.id_hotel = $id_hotel" : '') . " ORDER BY bm.fecha_baja DESC LIMIT 4");
	while ($r && ($f = mysqli_fetch_row($r))) {
		$actividad[] = array('tipo' => 'masiva', 'orden' => $f[4] . ' 00:00:00', 'fecha' => $f[4], 'hora' => false,
			'titulo' => 'Baja masiva', 'codigo' => '', 'detalle' => (int)$f[1] . ' equipos' . ($f[2] ? ' · ' . $f[2] : ''), 'meta' => (string)$f[3]);
	}
	usort($actividad, function ($x, $y) { return strcmp($y['orden'], $x['orden']); });
	$actividad = array_slice($actividad, 0, 6);

	$solicitudes = array();
	$r = mysqli_query($conexion, "SELECT s.numero_solicitud, s.cantidad_equipos, d.nombre, s.fecha_registro, s.clasificacion_1 FROM solicitud_alta_equipos s LEFT JOIN departamentos d ON d.id_departamento = s.id_departamento_solicitante WHERE 1=1" . ($id_hotel !== null ? " AND s.id_hotel = $id_hotel" : '') . " ORDER BY s.fecha_registro DESC LIMIT 4");
	while ($r && ($f = mysqli_fetch_row($r))) $solicitudes[] = $f;
	$totalSolicitudes = ccValor($conexion, "SELECT COUNT(*) FROM solicitud_alta_equipos WHERE 1=1 $whereHotelDirecto");

	$meses = array('ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic');
	$mesesLargos = array('enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
	$dias = array('domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado');
	function ccCuando($fecha, $conHora, $meses) {
		$ts = strtotime((string)$fecha);
		if ($ts === false) return '';
		$hoy = date('Y-m-d');
		$dia = date('Y-m-d', $ts);
		$hora = $conHora ? ', ' . date('H:i', $ts) : '';
		if ($dia === $hoy) return 'Hoy' . $hora;
		if ($dia === date('Y-m-d', strtotime('-1 day'))) return 'Ayer' . $hora;
		return (int)date('j', $ts) . ' ' . $meses[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
	}
	function ccPct($n, $total) { return $total > 0 ? number_format($n * 100 / $total, 1) : '0.0'; }

	$horaActual = (int)date('G');
	$saludo = $horaActual < 12 ? 'Buenos días' : ($horaActual < 19 ? 'Buenas tardes' : 'Buenas noches');
	$primerNombre = isset($_SESSION['nombre']) ? trim(explode(' ', trim((string)$_SESSION['nombre']))[0]) : '';
	$alcance = $es_super_admin ? 'Todos los hoteles' : (!empty($_SESSION['nombre_hotel']) ? $_SESSION['nombre_hotel'] : 'Hotel');
	$fechaHoy = $dias[(int)date('w')] . ' ' . (int)date('j') . ' de ' . $mesesLargos[(int)date('n') - 1] . ' de ' . date('Y');

	// Dona por estado: circunferencia de r=60.
	$circ = 2 * M_PI * 60;
	$segmentos = array(
		array('s-activo', 'bg-activo', 'Activo', $activos),
		array('s-mant', 'bg-mant', 'En mantenimiento', $mantenimiento),
		array('s-rep', 'bg-rep', 'En reparación', $reparacion),
		array('s-baja', 'bg-baja', 'Dado de baja', $bajas),
	);
	$iconosActividad = array('alta' => array('mas', 'cc-tono-exito'), 'resguardo' => array('firma', 'cc-tono-mar'), 'baja' => array('baja', 'cc-tono-peligro'), 'masiva' => array('bajas-masivas', 'cc-tono-peligro'));

	$titulo_pagina = 'Inicio - Sistema de Gestión de Equipos';
	require_once "menu.php";
?>
	<div class="cc-row" style="justify-content:space-between;align-items:flex-end">
		<div>
			<h1 style="font:600 28px/36px var(--font-sans);letter-spacing:-.01em;margin:0"><?php echo htmlspecialchars($saludo . ($primerNombre !== '' ? ', ' . $primerNombre : '')); ?></h1>
			<div class="cc-muted" style="margin-top:4px"><?php echo htmlspecialchars($alcance . ' · ' . $fechaHoy); ?></div>
		</div>
	</div>

	<div class="cc-accesos">
		<a class="cc-acceso cc-acceso--primario" href="equipos.php?nuevo=1"><span class="cc-acceso__ico"><i data-i="mas"></i></span>Registrar equipo<i data-i="derecha"></i></a>
		<a class="cc-acceso" href="nuevoResguardo.php"><span class="cc-acceso__ico cc-tono-mar"><i data-i="resguardos"></i></span>Nuevo resguardo<i data-i="derecha"></i></a>
		<a class="cc-acceso" href="bajas.php"><span class="cc-acceso__ico cc-tono-peligro"><i data-i="baja"></i></span>Registrar baja<i data-i="derecha"></i></a>
		<a class="cc-acceso" href="solicitudAltaEquipos.php"><span class="cc-acceso__ico cc-tono-dorado"><i data-i="solicitudes"></i></span>Solicitud de alta<i data-i="derecha"></i></a>
	</div>

	<div class="cc-kpis">
		<div class="cc-card cc-kpi"><div class="cc-kpi__top"><span>Equipos activos</span><span class="cc-kpi__ico cc-tono-exito"><i data-i="equipos"></i></span></div><div class="cc-kpi__valor"><?php echo number_format($activos); ?></div><div class="cc-kpi__pie"><b><?php echo ccPct($activos, $total); ?>%</b> del total</div></div>
		<div class="cc-card cc-kpi"><div class="cc-kpi__top"><span>En mantenimiento</span><span class="cc-kpi__ico cc-tono-alerta"><i data-i="alerta"></i></span></div><div class="cc-kpi__valor"><?php echo number_format($mantenimiento + $reparacion); ?></div><div class="cc-kpi__pie"><?php echo number_format($mantenimiento); ?> mantenimiento · <?php echo number_format($reparacion); ?> reparación</div></div>
		<div class="cc-card cc-kpi"><div class="cc-kpi__top"><span>Dados de baja</span><span class="cc-kpi__ico cc-tono-peligro"><i data-i="baja"></i></span></div><div class="cc-kpi__valor"><?php echo number_format($bajas); ?></div><div class="cc-kpi__pie"><b><?php echo number_format($bajasMes); ?></b> registradas este mes</div></div>
		<div class="cc-card cc-kpi"><div class="cc-kpi__top"><span>Total de equipos</span><span class="cc-kpi__ico cc-tono-mar"><i data-i="hotel"></i></span></div><div class="cc-kpi__valor"><?php echo number_format($total); ?></div><div class="cc-kpi__pie"><?php echo $es_super_admin ? '4 hoteles · ' : ''; ?><?php echo number_format($numDeptos); ?> departamentos</div></div>
	</div>

	<div class="cc-grid-32">
		<div class="cc-card">
			<div class="cc-card__head"><div><h2 class="cc-h2">Equipos por departamento</h2><div class="cc-muted cc-small">Top <?php echo count($porDepto); ?> · número de equipos registrados</div></div><a href="departamentos.php">Ver todos</a></div>
			<div class="cc-card__body">
				<?php if ($porDepto): ?>
				<div class="cc-barras" data-tip-box style="position:relative">
					<?php foreach ($porDepto as $d): ?>
					<div class="cc-barra" data-tip="<?php echo htmlspecialchars($d[0] . ': ' . $d[1] . ' equipos'); ?>"><span class="cc-barra__nombre"><?php echo htmlspecialchars($d[0]); ?></span><span class="cc-barra__pista"><span class="cc-barra__fill" style="width:<?php echo number_format($d[1] * 100 / $maxDepto, 1, '.', ''); ?>%"></span></span><span class="cc-barra__val"><?php echo number_format($d[1]); ?></span></div>
					<?php endforeach; ?>
				</div>
				<?php else: ?>
				<div class="cc-vacio"><b>Sin equipos registrados</b><span class="cc-muted cc-small">Registra el primer equipo para ver esta gráfica.</span></div>
				<?php endif; ?>
			</div>
		</div>
		<div class="cc-card">
			<div class="cc-card__head"><div><h2 class="cc-h2">Equipos por estado</h2><div class="cc-muted cc-small"><?php echo number_format($total); ?> equipos · <?php echo htmlspecialchars($alcance); ?></div></div></div>
			<div class="cc-card__body">
				<div class="cc-dona" data-tip-box style="position:relative">
					<svg viewBox="0 0 168 168" role="img" aria-label="Equipos por estado">
						<?php
							$offset = 0.0;
							foreach ($segmentos as $s) {
								if ($s[3] <= 0 || $total <= 0) continue;
								$largo = $circ * $s[3] / $total;
								echo '<circle class="seg ' . $s[0] . '" cx="84" cy="84" r="60" stroke-dasharray="' . number_format($largo, 2, '.', '') . ' ' . number_format($circ - $largo, 2, '.', '') . '" stroke-dashoffset="' . number_format(-$offset, 2, '.', '') . '" transform="rotate(-90 84 84)" data-tip="' . htmlspecialchars($s[2] . ': ' . number_format($s[3]) . ' (' . ccPct($s[3], $total) . '%)') . '"/>';
								$offset += $largo;
							}
						?>
						<text class="d-total" x="84" y="86" text-anchor="middle"><?php echo number_format($total); ?></text>
						<text class="d-lab" x="84" y="104" text-anchor="middle">equipos</text>
					</svg>
					<div class="cc-leyenda">
						<?php foreach ($segmentos as $s): ?>
						<div class="cc-leyenda__item"><span class="cc-leyenda__sw <?php echo $s[1]; ?>"></span><span><?php echo $s[2]; ?></span><span class="cc-leyenda__val"><?php echo number_format($s[3]); ?></span><span class="cc-leyenda__pct"><?php echo ccPct($s[3], $total); ?>%</span></div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="cc-grid-32" style="align-items:start">
		<div class="cc-card">
			<div class="cc-card__head"><h2 class="cc-h2">Actividad reciente</h2></div>
			<div class="cc-card__body">
				<?php if ($actividad): ?>
				<ul class="cc-actividad">
					<?php foreach ($actividad as $act): $ic = $iconosActividad[$act['tipo']]; ?>
					<li>
						<span class="cc-actividad__ico <?php echo $ic[1]; ?>"><i data-i="<?php echo $ic[0]; ?>"></i></span>
						<div class="cc-actividad__txt"><?php echo htmlspecialchars($act['titulo']); ?><?php if ($act['codigo'] !== ''): ?> <b><?php echo htmlspecialchars($act['codigo']); ?></b><?php endif; ?><?php echo $act['detalle'] !== '' ? ' · ' . htmlspecialchars($act['detalle']) : ''; ?>
							<?php if ($act['meta'] !== ''): ?><div class="cc-actividad__meta"><?php echo htmlspecialchars($act['meta']); ?></div><?php endif; ?>
						</div>
						<span class="cc-actividad__hora"><?php echo htmlspecialchars(ccCuando($act['fecha'], $act['hora'], $meses)); ?></span>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php else: ?>
				<div class="cc-vacio"><b>Sin actividad todavía</b><span class="cc-muted cc-small">Aquí aparecerán las altas, resguardos y bajas.</span></div>
				<?php endif; ?>
			</div>
		</div>
		<div class="cc-card">
			<div class="cc-card__head"><div class="cc-row" style="gap:8px"><h2 class="cc-h2">Solicitudes de alta</h2><span class="cc-contador"><?php echo number_format($totalSolicitudes); ?></span></div><a href="solicitudAltaEquipos.php">Nueva solicitud</a></div>
			<div class="cc-card__body" style="padding-top:12px;padding-bottom:12px">
				<?php if ($solicitudes): ?>
					<?php foreach ($solicitudes as $s): ?>
					<div class="cc-sol">
						<b style="font-weight:500">Solicitud <?php echo htmlspecialchars((string)$s[0]); ?><?php echo $s[4] ? ' · ' . htmlspecialchars((string)$s[4]) : ''; ?></b>
						<span class="cc-horas"><i data-i="reloj"></i><?php echo htmlspecialchars(ccCuando($s[3], true, $meses)); ?></span>
						<span class="cc-muted cc-small"><?php echo (int)$s[1]; ?> equipo<?php echo (int)$s[1] === 1 ? '' : 's'; ?><?php echo $s[2] ? ' · ' . htmlspecialchars((string)$s[2]) : ''; ?></span><span></span>
					</div>
					<?php endforeach; ?>
				<?php else: ?>
				<div class="cc-vacio" style="padding:var(--esp-5)"><b>Sin solicitudes</b><span class="cc-muted cc-small">Las solicitudes de alta aparecerán aquí.</span></div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="cc-card">
		<div class="cc-card__head"><div><h2 class="cc-h2">Reportes ejecutivos</h2><div class="cc-muted cc-small">PDF listos para dirección</div></div></div>
		<div class="cc-card__body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:var(--esp-4)">
			<div class="cc-reporte"><span class="cc-reporte__doc"><i data-i="pdf"></i>PDF</span><div class="cc-reporte__txt"><b style="font-weight:500">Reporte de todas las bajas</b><div class="cc-muted cc-small">Equipos dados de baja con motivo, fecha y destino</div></div><button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" onclick="descargarReporteDirecto(this, '../reportes/reporteTodasLasBajas.php','reporte_todas_las_bajas.pdf')"><i data-i="descarga"></i>Descargar</button></div>
			<div class="cc-reporte"><span class="cc-reporte__doc"><i data-i="pdf"></i>PDF</span><div class="cc-reporte__txt"><b style="font-weight:500">Reporte de equipos activos</b><div class="cc-muted cc-small">Inventario activo por hotel y departamento</div></div><button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" onclick="descargarReporteDirecto(this, '../reportes/reporteEquiposActivos.php','reporte_equipos_activos.pdf')"><i data-i="descarga"></i>Descargar</button></div>
		</div>
	</div>

	<script type="text/javascript">
		document.addEventListener('DOMContentLoaded', function(){ CostaClara.tooltips(); });
		function descargarReporteDirecto(boton, url, nombreArchivo) {
			boton.disabled = true;
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
					alertify.success('Reporte descargado.');
				})
				.catch(function(){
					alertify.error('No se pudo generar el reporte. Intenta de nuevo.');
				})
				.then(function(){ boton.disabled = false; });
		}
	</script>
		</main>
	</div>
</div>
</body>
</html>
