<?php 
	require_once "../../seguridad.php";
	requerirAutenticacion();
	require_once "../../clases/Conexion.php";
	require_once "../../clases/AltasBajas.php";
	
	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	$nombre_hotel = isset($_SESSION['nombre_hotel']) ? trim($_SESSION['nombre_hotel']) : '';
	$objAltasBajas = new altasBajas();
	$result = $objAltasBajas->obtenerTodasBajasMasivas($id_hotel, $nombre_hotel);
	$c = new conectar();
	$conexion = $c->conexion();
	$chkNumeroBm = @mysqli_query($conexion, "SHOW COLUMNS FROM bajas_masivas LIKE 'numero_baja_masiva_hotel'");
	$tieneNumeroBajaMasivaHotel = $chkNumeroBm && mysqli_num_rows($chkNumeroBm) > 0;
	$offsetBm = $tieneNumeroBajaMasivaHotel ? 1 : 0;
?>

<h4>Bajas Masivas Registradas</h4>
<div class="row">
	<div class="col-sm-12">
		<div id="verBajasMasivas">
<style>
	#verBajasMasivas { width: 100%; }
	#verBajasMasivas .table-responsive {
		overflow-x: visible;
		overflow-y: visible;
	}
	#tablaBajasMasivas {
		width: 100%;
		table-layout: fixed;
		font-size: 11px;
	}
	#tablaBajasMasivas caption {
		caption-side: top;
		color: #ccc;
		text-align: left;
		padding-bottom: 6px;
	}
	#tablaBajasMasivas thead th {
		background-color: #c29a4b;
		color: #fff;
		font-weight: bold;
		text-align: center;
		vertical-align: middle;
	}
	#tablaBajasMasivas td, #tablaBajasMasivas th {
		white-space: normal;
		word-break: normal;
		overflow-wrap: anywhere;
		line-height: 1.2;
		vertical-align: middle;
		padding: 6px 4px;
	}
	#tablaBajasMasivas th:nth-child(1), #tablaBajasMasivas td:nth-child(1) { width: 5%; }
	#tablaBajasMasivas th:nth-child(2), #tablaBajasMasivas td:nth-child(2) { width: 8%; }
	#tablaBajasMasivas th:nth-child(3), #tablaBajasMasivas td:nth-child(3) { width: 12%; }
	#tablaBajasMasivas th:nth-child(4), #tablaBajasMasivas td:nth-child(4) { width: 7%; }
	#tablaBajasMasivas th:nth-child(5), #tablaBajasMasivas td:nth-child(5) { width: 14%; }
	#tablaBajasMasivas th:nth-child(6), #tablaBajasMasivas td:nth-child(6) { width: 8%; }
	#tablaBajasMasivas th:nth-child(7), #tablaBajasMasivas td:nth-child(7) { width: 9%; }
	#tablaBajasMasivas th:nth-child(8), #tablaBajasMasivas td:nth-child(8) { width: 8%; }
	#tablaBajasMasivas th:nth-child(9), #tablaBajasMasivas td:nth-child(9) { width: 14%; }
	#tablaBajasMasivas th:nth-child(10), #tablaBajasMasivas td:nth-child(10) { width: 8%; }
	#tablaBajasMasivas th:nth-child(11), #tablaBajasMasivas td:nth-child(11) { width: 9%; }
</style>
		<div class="table-responsive">
			<table id="tablaBajasMasivas" class="table table-hover table-condensed table-bordered" style="text-align: center;">
				<caption><label>Historial de Bajas Masivas de Equipos</label></caption>
				<thead>
				<tr>
					<th><strong>Nº</strong></th>
					<th><strong>Fecha</strong></th>
					<th><strong>Usuario</strong></th>
					<th><strong>Tipo</strong></th>
					<th><strong>Motivo</strong></th>
					<th><strong>Total Equipos</strong></th>
					<th><strong>Valor Residual</strong></th>
					<th><strong>Destino</strong></th>
					<th><strong>Observaciones</strong></th>
					<th><strong>Informe</strong></th>
					<th><strong>Acciones</strong></th>
				</tr>
				</thead>
				<tbody>
				<?php while($ver=mysqli_fetch_row($result)): 
					$obm = $offsetBm; // fecha=1+obm, motivo=2+obm, valor=3+obm, destino=4+obm, observ=5+obm, total=6+obm, hotel_area=7+obm, nombre=8+obm
				?>
					<tr>
						<td><?php echo $tieneNumeroBajaMasivaHotel && isset($ver[1]) && $ver[1] !== null && $ver[1] !== '' ? (int)$ver[1] : (int)$ver[0]; ?></td>
						<td><?php echo $ver[1 + $obm]; ?></td>
						<td><?php echo isset($ver[8 + $obm]) && $ver[8 + $obm] ? $ver[8 + $obm] : 'No asignado'; ?></td>
						<td>
							<span class="label label-danger">Masiva</span>
						</td>
						<td><?php echo $ver[2 + $obm]; ?></td>
						<td>
							<span class="badge"><?php echo $ver[6 + $obm]; ?></span>
						</td>
						<td>$<?php echo number_format($ver[3 + $obm], 2); ?></td>
						<td><?php echo $ver[4 + $obm]; ?></td>
						<td><?php echo isset($ver[5 + $obm]) && $ver[5 + $obm] ? $ver[5 + $obm] : 'Sin observaciones'; ?></td>
						<td>
							<?php 
							$params = 'id_baja=' . (int)$ver[0];
							if (!$es_super_admin && isset($_SESSION['id_hotel'])) $params .= '&id_hotel=' . (int)$_SESSION['id_hotel'];
							?>
							<a href="../reportes/informeTecnicoBajaMasiva.php?<?php echo $params; ?>" target="_blank" class="btn btn-info btn-xs">
								<span class="glyphicon glyphicon-file"></span> Generar
							</a>
						</td>
						<td>
							<?php if(isset($_SESSION['iduser']) && $_SESSION['iduser'] == 1): ?>
								<button type="button" class="btn btn-danger btn-xs" onclick="eliminarBajaMasiva(<?php echo $ver[0]; ?>, '<?php echo addslashes($ver[2 + $obm]); ?>', <?php echo (int)$ver[6 + $obm]; ?>)" title="Eliminar registro de baja masiva">
									<span class="glyphicon glyphicon-trash"></span> Eliminar
								</button>
							<?php else: ?>
								<span class="text-muted">-</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endwhile; ?>
				</tbody>
			</table>
		</div>
		</div><!-- #verBajasMasivas -->
	</div>
</div>

<script type="text/javascript">
	// Función para eliminar baja masiva
	function eliminarBajaMasiva(idBaja, motivo, totalEquipos) {
		alertify.confirm(
			'¿Estás seguro de eliminar el registro de baja masiva?',
			'<strong>Advertencia:</strong><br>' +
			'Se eliminará permanentemente el registro de baja masiva:<br>' +
			'<strong>Motivo:</strong> ' + motivo + '<br>' +
			'<strong>Total de equipos:</strong> ' + totalEquipos + '<br><br>' +
			'<span style="color: red;">Esta acción no se puede deshacer.</span>',
			function(){
				// Confirmación positiva - proceder con la eliminación
				$.ajax({
					type: "POST",
					data: "idBaja=" + idBaja,
					url: "../procesos/bajasMasivas/eliminarBajaMasiva.php",
					success: function(r){
						if(r == "1" || r == 1){
							alertify.success("Registro de baja masiva eliminado correctamente");
							// Recargar la tabla de bajas masivas
							$('#contenido').load('bajasMasivas/tablaBajasMasivas.php');
						} else {
							alertify.error("Error al eliminar el registro de baja masiva");
						}
					},
					error: function(xhr, status, error) {
						console.log("Error AJAX:", error);
						alertify.error("Error de comunicación con el servidor");
					}
				});
			},
			function(){
				// Confirmación negativa - cancelar
				alertify.error('Eliminación cancelada');
			}
		);
	}
</script>
