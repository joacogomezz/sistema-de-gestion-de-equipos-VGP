<?php 
	if (session_status() == PHP_SESSION_NONE) {
		session_start();
	}
	require_once "../../config.php";
	require_once "../../clases/Conexion.php";
	$c= new conectar();
	$conexion=$c->conexion();

	function obtenerRutaFoto($rutaRelativa) {
		if(empty($rutaRelativa)) { return false; }
		$documentRoot = $_SERVER['DOCUMENT_ROOT'];
		if (defined('RUTA_BASE') && RUTA_BASE !== '') {
			$rutaBase = rtrim(RUTA_BASE, '/') . '/';
		} else {
			$scriptName = $_SERVER['SCRIPT_NAME'];
			if (strpos($scriptName, '/ventasAlmacen22/') !== false) { $rutaBase = '/ventasAlmacen22/'; }
			elseif (strpos($scriptName, '/ventasAlmacen/') !== false) { $rutaBase = '/ventasAlmacen/'; }
			elseif (strpos($scriptName, '/htdocs/') !== false) { $rutaBase = '/htdocs/ventasAlmacen22/'; }
			else { $rutaBase = '/'; }
		}
		
		$rutas = array(
			$rutaBase . $rutaRelativa, // Ruta absoluta del servidor
			'../../' . $rutaRelativa,   // Ruta relativa desde vistas/bajas/
			'../' . $rutaRelativa,      // Ruta relativa desde vistas/
			'./' . $rutaRelativa,       // Ruta relativa desde directorio actual
			$rutaRelativa                // Ruta directa
		);
		foreach($rutas as $ruta) {
			if(strpos($ruta, '/') === 0) { // Es una ruta absoluta del servidor
				$rutaFisica = $documentRoot . $ruta;
				if(file_exists($rutaFisica) && is_readable($rutaFisica)) { return $ruta; }
			} else { // Es una ruta relativa del sistema de archivos
				if(file_exists($ruta) && is_readable($ruta)) { return $ruta; }
			}
		}
		return false;
	}

	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	$whereHotel = '';
	if ($id_hotel !== null) {
		$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			$whereHotel = " AND d.id_hotel = '" . $id_hotel . "'";
		}
	}
	$chkNumeroBaja = @mysqli_query($conexion, "SHOW COLUMNS FROM bajas_equipos LIKE 'numero_baja_hotel'");
	$tieneNumeroBajaHotel = $chkNumeroBaja && mysqli_num_rows($chkNumeroBaja) > 0;
	$campoNumeroBaja = $tieneNumeroBajaHotel ? "b.numero_baja_hotel, " : "";
	$sql="SELECT b.id_baja, " . $campoNumeroBaja . "b.fecha_baja, e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
					e.numero_serie, d.nombre as departamento, b.tipo_baja, b.motivo_baja, b.valor_residual, 
					b.destino_equipo, b.observaciones, e.id_equipo, e.tipo_moneda, b.foto_equipo, b.foto_etiqueta
			from bajas_equipos b
			inner join equipos e on b.id_equipo = e.id_equipo
			left join departamentos d on b.id_departamento = d.id_departamento
			where b.tipo_baja = 'Individual' $whereHotel
			order by b.id_baja desc";
	$result=mysqli_query($conexion,$sql);
	$offsetBaja = $tieneNumeroBajaHotel ? 1 : 0;
?>

<h4>Bajas Individuales Registradas</h4>
<div class="row">
	<div class="col-sm-12">
		<div id="verBajas">
<style>
	#verBajas { width: 100%; }
	#verBajas .table-responsive {
		overflow-x: visible;
		overflow-y: visible;
	}
	#tablaBajas {
		width: 100%;
		table-layout: fixed;
		font-size: 12px;
	}
	#tablaBajas caption {
		caption-side: top;
		color: var(--texto);
		text-align: left;
		padding-bottom: 6px;
	}
	#tablaBajas thead th {
		background-color: var(--superficie-hundida) !important;
		color: var(--texto-secundario) !important;
		font-weight: 500;
		text-align: center;
		vertical-align: middle;
	}
	#tablaBajas td, #tablaBajas th {
		white-space: normal;
		word-break: normal;
		overflow-wrap: anywhere;
		line-height: 1.3;
		vertical-align: middle;
		padding: 8px 6px !important;
	}
	#tablaBajas td.col-bj-foto,
	#tablaBajas th.col-bj-foto {
		width: 4.5%;
		max-width: 72px;
		text-align: center;
		overflow: hidden;
		padding: 4px 2px;
		line-height: 0;
	}
	#tablaBajas td.col-bj-foto img {
		display: block;
		margin: 0 auto;
		max-width: 56px;
		max-height: 56px;
		width: auto;
		height: auto;
		object-fit: contain;
		border-radius: 4px;
		cursor: pointer;
		box-sizing: border-box;
	}
	#tablaBajas td.col-bj-foto .text-muted {
		display: inline-block;
		max-width: 100%;
		line-height: 1.25;
	}
	#tablaBajas th:nth-child(1), #tablaBajas td:nth-child(1) { width: 2.5%; }
	#tablaBajas th:nth-child(2), #tablaBajas td:nth-child(2) { width: 4%; }
	#tablaBajas th:nth-child(3), #tablaBajas td:nth-child(3) { width: 6%; }
	#tablaBajas th:nth-child(4), #tablaBajas td:nth-child(4) { width: 9%; }
	#tablaBajas th:nth-child(5), #tablaBajas td:nth-child(5) { width: 9%; }
	#tablaBajas th:nth-child(6), #tablaBajas td:nth-child(6) { width: 5.5%; }
	#tablaBajas th:nth-child(7), #tablaBajas td:nth-child(7) { width: 7.5%; }
	#tablaBajas th:nth-child(8), #tablaBajas td:nth-child(8) { width: 4%; }
	#tablaBajas th:nth-child(9), #tablaBajas td:nth-child(9) { width: 8%; }
	#tablaBajas th:nth-child(10), #tablaBajas td:nth-child(10) { width: 5%; }
	#tablaBajas th:nth-child(11), #tablaBajas td:nth-child(11) { width: 5%; }
	#tablaBajas th:nth-child(12), #tablaBajas td:nth-child(12) { width: 9%; }
	#tablaBajas th:nth-child(15), #tablaBajas td:nth-child(15) { width: 6%; }
	#tablaBajas th:nth-child(16), #tablaBajas td:nth-child(16) { width: 9%; }
</style>
		<!-- Barra de búsqueda -->
		<div class="row" style="margin-bottom: 15px;">
			<div class="col-sm-6">
				<div class="input-group">
					<span class="input-group-addon">
						<span class="glyphicon glyphicon-search"></span>
					</span>
					<input type="text" class="form-control" id="buscarBaja" placeholder="Buscar por código de equipo...">
				</div>
			</div>
			<div class="col-sm-6">
				<button type="button" class="btn btn-default" id="limpiarBusqueda">
					<span class="glyphicon glyphicon-refresh"></span> Limpiar
				</button>
				<span id="resultadosBusqueda" class="text-muted" style="margin-left: 10px;"></span>
			</div>
		</div>
		
		<div class="table-responsive">
			<table id="tablaBajas" class="table table-hover table-condensed table-bordered" style="text-align: center;">
				<caption><label>Historial de Bajas Individuales de Equipos</label></caption>
				<thead>
				<tr>
					<th><strong>Nº</strong></th>
					<th><strong>Fecha</strong></th>
					<th><strong>Código</strong></th>
					<th><strong>Descripción</strong></th>
					<th><strong>Marca/Modelo</strong></th>
					<th><strong>N° Serie</strong></th>
					<th><strong>Departamento</strong></th>
					<th><strong>Tipo</strong></th>
					<th><strong>Motivo</strong></th>
					<th><strong>Valor Residual</strong></th>
					<th><strong>Destino</strong></th>
					<th><strong>Observaciones</strong></th>
					<th class="col-bj-foto"><strong>Foto Equipo</strong></th>
					<th class="col-bj-foto"><strong>Foto Etiqueta</strong></th>
					<th><strong>Informe</strong></th>
					<th><strong>Acciones</strong></th>
				</tr>
				</thead>
				<tbody>
				<?php while($ver=mysqli_fetch_row($result)): 
					$ob = $offsetBaja; // fecha=1+ob, codigo=2+ob, ..., tipo_moneda=14+ob, foto_equipo=15+ob, foto_etiqueta=16+ob
				?>
					<tr>
						<td><?php echo $tieneNumeroBajaHotel && isset($ver[1]) && $ver[1] !== null && $ver[1] !== '' ? (int)$ver[1] : (int)$ver[0]; ?></td>
						<td><?php echo date('d/m/Y', strtotime($ver[1 + $ob])); ?></td>
						<td><?php echo $ver[2 + $ob]; ?></td>
						<td><?php echo substr($ver[3 + $ob], 0, 30) . (strlen($ver[3 + $ob]) > 30 ? '...' : ''); ?></td>
						<td><?php echo $ver[4 + $ob] . ' ' . $ver[5 + $ob]; ?></td>
						<td><?php echo $ver[6 + $ob]; ?></td>
						<td><?php echo isset($ver[7 + $ob]) && $ver[7 + $ob] ? $ver[7 + $ob] : 'No asignado'; ?></td>
						<td>
							<span class="label label-danger"><?php echo $ver[8 + $ob]; ?></span>
						</td>
						<td><?php echo $ver[9 + $ob]; ?></td>
						<td><?php echo $ver[14 + $ob] . ' ' . number_format($ver[10 + $ob], 2); ?></td>
						<td><?php echo $ver[11 + $ob]; ?></td>
						<td><?php echo isset($ver[12 + $ob]) && $ver[12 + $ob] ? substr($ver[12 + $ob], 0, 20) . (strlen($ver[12 + $ob]) > 20 ? '...' : '') : 'Sin observaciones'; ?></td>
						<td class="col-bj-foto">
							<?php 
							$rutaFotoEquipo = $ver[15 + $ob];
							$rutaFinalEquipo = obtenerRutaFoto($rutaFotoEquipo);
							if($rutaFinalEquipo): ?>
								<img src="<?php echo $rutaFinalEquipo; ?>" alt="Foto del equipo"
									 onclick="mostrarImagen('<?php echo $rutaFinalEquipo; ?>', 'Foto del Equipo')"
									 title="Click para ampliar">
							<?php else: ?>
								<span class="text-muted" style="font-size: 10px; line-height: 1.2;" title="Ruta original: <?php echo htmlspecialchars($rutaFotoEquipo, ENT_QUOTES, 'UTF-8'); ?>">Sin foto</span>
							<?php endif; ?>
						</td>
						<td class="col-bj-foto">
							<?php 
							$rutaFotoEtiqueta = $ver[16 + $ob];
							$rutaFinalEtiqueta = obtenerRutaFoto($rutaFotoEtiqueta);
							if($rutaFinalEtiqueta): ?>
								<img src="<?php echo $rutaFinalEtiqueta; ?>" alt="Foto de etiqueta"
									 onclick="mostrarImagen('<?php echo $rutaFinalEtiqueta; ?>', 'Foto de la Etiqueta')"
									 title="Click para ampliar">
							<?php else: ?>
								<span class="text-muted" style="font-size: 10px; line-height: 1.2;" title="Ruta original: <?php echo htmlspecialchars($rutaFotoEtiqueta, ENT_QUOTES, 'UTF-8'); ?>">Sin foto</span>
							<?php endif; ?>
						</td>
						<td>
							<?php 
							$urlBaja = 'reportes/informeTecnicoBaja.php?id_baja=' . (int)$ver[0];
							if (isset($_SESSION['id_hotel']) && (int)$_SESSION['id_hotel'] > 0 && (!isset($_SESSION['iduser']) || (int)$_SESSION['iduser'] !== 1))
								$urlBaja .= '&id_hotel=' . (int)$_SESSION['id_hotel'];
							?>
							<a href="<?php echo $urlBaja; ?>" target="_blank" class="btn btn-info btn-xs">
								<span class="glyphicon glyphicon-file"></span> Generar
							</a>
						</td>
						<td>
							<?php if(isset($_SESSION['iduser']) && $_SESSION['iduser'] == 1): ?>
								<button type="button" class="btn btn-danger btn-xs" onclick="eliminarBaja(<?php echo $ver[0]; ?>, '<?php echo addslashes($ver[2 + $ob]); ?>')" title="Eliminar registro de baja">
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
		</div><!-- #verBajas -->
	</div>
</div>

<!-- Botón duplicado eliminado para evitar conflictos -->

<script type="text/javascript">
	$(document).ready(function(){
		// Funcionalidad de búsqueda
		$('#buscarBaja').on('keyup', function(){
			var valor = $(this).val().toLowerCase();
			var filas = $('#tablaBajas tbody tr');
			var resultados = 0;
			
			filas.each(function(){
				var codigo = $(this).find('td:eq(2)').text().toLowerCase(); // Columna de código
				var descripcion = $(this).find('td:eq(3)').text().toLowerCase(); // Columna de descripción
				var marca = $(this).find('td:eq(4)').text().toLowerCase(); // Columna de marca/modelo
				
				// Buscar en código, descripción y marca/modelo
				if(codigo.indexOf(valor) > -1 || descripcion.indexOf(valor) > -1 || marca.indexOf(valor) > -1){
					$(this).show();
					resultados++;
				} else {
					$(this).hide();
				}
			});
			
			// Mostrar contador de resultados
			if(valor.length > 0){
				$('#resultadosBusqueda').text(resultados + ' resultado(s) encontrado(s)');
			} else {
				$('#resultadosBusqueda').text('');
			}
		});
		
		// Botón limpiar búsqueda
		$('#limpiarBusqueda').click(function(){
			$('#buscarBaja').val('');
			$('#tablaBajas tbody tr').show();
			$('#resultadosBusqueda').text('');
		});
	});
	
	// Función para eliminar baja
	function eliminarBaja(idBaja, codigoEquipo) {
		alertify.confirm(
			'¿Estás seguro de eliminar el registro de baja?',
			'<strong>Advertencia:</strong><br>' +
			'Se eliminará permanentemente el registro de baja del equipo: <strong>' + codigoEquipo + '</strong><br><br>' +
			'<span style="color: red;">Esta acción no se puede deshacer.</span>',
			function(){
				// Confirmación positiva - proceder con la eliminación
				$.ajax({
					type: "POST",
					data: "idBaja=" + idBaja,
					url: "../procesos/bajas/eliminarBaja.php",
					success: function(r){
						if(r == "1" || r == 1){
							alertify.success("Registro de baja eliminado correctamente");
							// Recargar la tabla de bajas
							$('#contenido').load('bajas/tablaBajas.php');
						} else {
							alertify.error("Error al eliminar el registro de baja");
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
	
	function mostrarImagen(ruta, titulo) {
		// Crear modal para mostrar imagen ampliada
		var modal = '<div class="modal fade" id="modalImagen" tabindex="-1" role="dialog">' +
					'<div class="modal-dialog modal-lg" role="document">' +
					'<div class="modal-content">' +
					'<div class="modal-header">' +
					'<button type="button" class="close" data-dismiss="modal">&times;</button>' +
					'<h4 class="modal-title">' + titulo + '</h4>' +
					'</div>' +
					'<div class="modal-body text-center">' +
					'<img src="' + ruta + '" style="max-width: 100%; max-height: 500px;">' +
					'</div>' +
					'</div>' +
					'</div>' +
					'</div>';
		
		// Remover modal anterior si existe
		$('#modalImagen').remove();
		
		// Agregar nuevo modal y mostrarlo
		$('body').append(modal);
		$('#modalImagen').modal('show');
	}
</script>