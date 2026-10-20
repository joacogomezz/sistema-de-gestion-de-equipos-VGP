<?php 
	session_start();
	require_once "../../config.php";
	require_once "../../clases/Conexion.php";
	require_once "../../clases/Equipos.php";
	require_once "../../clases/Departamentos.php";
	require_once "../../clases/Hoteles.php";
	$c= new conectar();
	$conexion=$c->conexion();
	$objEquiposMigr = new equipos();
	$objEquiposMigr->ensureRegistroEquiposCols();
	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	// Super admin: filtrar por hotel seleccionado (GET); si no hay selección, solo se muestra el selector
	if ($es_super_admin) {
		$id_hotel = (isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : null;
		$objHoteles = new Hoteles();
		$listaHoteles = $objHoteles->obtenerTodos();
		$nombre_hotel_actual = $id_hotel ? $objHoteles->getNombre($id_hotel) : '';
	} else {
		$id_hotel = isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null;
		$listaHoteles = false;
		$nombre_hotel_actual = '';
	}
	$objDept = new departamentos();
	$resultDept = $objDept->obtenerTodosDepartamentos($id_hotel);

	// Función para obtener la ruta correcta de la foto
	function obtenerRutaFotoEquipo($rutaRelativa) {
		if(empty($rutaRelativa)) { return false; }
		
		$nombreArchivo = basename($rutaRelativa);
		
		if (defined('RUTA_BASE') && RUTA_BASE !== '') {
			$rutaBase = rtrim(RUTA_BASE, '/') . '/';
		} else {
			$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
			if (strpos($scriptName, '/ventasAlmacen22/') !== false) { $rutaBase = '/ventasAlmacen22/'; }
			elseif (strpos($scriptName, '/ventasAlmacen/') !== false) { $rutaBase = '/ventasAlmacen/'; }
			else { $rutaBase = '/'; }
		}
		
		// Probar diferentes rutas posibles
		$rutas = array(
			$rutaBase . $rutaRelativa,                 // Ruta absoluta completa
			$rutaBase . 'archivos/equipos/' . $nombreArchivo,  // Ruta absoluta desde raíz web
			$rutaBase . 'archivos/' . $nombreArchivo,  // Ruta absoluta desde raíz web
			'archivos/equipos/' . $nombreArchivo,      // Ruta directa desde raíz
			'archivos/' . $nombreArchivo,              // Ruta directa desde raíz
			'../../archivos/equipos/' . $nombreArchivo, // Ruta relativa desde vistas/equipos/
			'../../archivos/' . $nombreArchivo,        // Ruta relativa desde vistas/equipos/
			'../archivos/equipos/' . $nombreArchivo,   // Ruta relativa desde vistas/
			'../archivos/' . $nombreArchivo,           // Ruta relativa desde vistas/
			$rutaRelativa,                             // Ruta original
			'../../' . $rutaRelativa,                  // Ruta relativa completa
			'../' . $rutaRelativa,                     // Ruta relativa desde vistas/
		);
		
		foreach($rutas as $ruta) {
			// Para rutas absolutas, verificar si el archivo existe físicamente
			if(strpos($ruta, '/') === 0) {
				$rutaFisica = $_SERVER['DOCUMENT_ROOT'] . $ruta;
				if(file_exists($rutaFisica) && is_readable($rutaFisica)) { 
					return $ruta; 
				}
			} else {
				// Para rutas relativas, verificar directamente
				if(file_exists($ruta) && is_readable($ruta)) { 
					return $ruta; 
				}
			}
		}
		return false;
	}

	/** Archivo de factura escaneada en archivos/facturas_equipos/equipo_{id}.{ext} */
	function obtenerArchivoFacturaEscaneada($idEquipo) {
		$id = (int)$idEquipo;
		if ($id <= 0) {
			return array('existe' => false, 'rel' => '', 'fisica' => '', 'nombre' => '', 'mtime' => 0);
		}
		$exts = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp');
		$base = __DIR__ . '/../../archivos/facturas_equipos/';
		foreach ($exts as $ext) {
			$nom = 'equipo_' . $id . '.' . $ext;
			$fis = $base . $nom;
			if (file_exists($fis) && is_file($fis)) {
				return array(
					'existe' => true,
					'rel' => '../archivos/facturas_equipos/' . $nom,
					'fisica' => $fis,
					'nombre' => $nom,
					'mtime' => filemtime($fis)
				);
			}
		}
		return array('existe' => false, 'rel' => '', 'fisica' => '', 'nombre' => '', 'mtime' => 0);
	}

	$whereHotel = '';
	if ($id_hotel !== null) {
		$hid = (int)$id_hotel;
		$chkEqHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM equipos LIKE 'id_hotel'");
		$chkDeptHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
		if ($chkEqHotel && mysqli_num_rows($chkEqHotel) > 0) {
			$whereHotel = " AND (e.id_hotel = '$hid' OR (e.id_hotel IS NULL AND d.id_hotel = '$hid')) ";
		} elseif ($chkDeptHotel && mysqli_num_rows($chkDeptHotel) > 0) {
			$whereHotel = " AND d.id_hotel = '$hid' ";
		}
	}
	$chkNumeroEquipo = @mysqli_query($conexion, "SHOW COLUMNS FROM equipos LIKE 'numero_equipo_hotel'");
	$tieneNumeroEquipoHotel = $chkNumeroEquipo && mysqli_num_rows($chkNumeroEquipo) > 0;
	$campoNumeroEquipo = $tieneNumeroEquipoHotel ? "e.numero_equipo_hotel, " : "";
	// Solo ejecutar consulta de equipos si no es super admin o si ya hay un hotel seleccionado
	$mostrarTabla = !$es_super_admin || $id_hotel !== null;
	if ($mostrarTabla) {
		$sql="SELECT e.id_equipo, " . $campoNumeroEquipo . "e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
						e.numero_serie, e.estado, e.ubicacion, e.valor_adquisicion, 
						e.tipo_moneda, e.fecha_ingreso, d.nombre as departamento,
						e.usuario_registro, e.numero_factura, e.foto_equipo
				from equipos e
				left join departamentos d on e.id_departamento = d.id_departamento
				where 1=1 $whereHotel
				order by e.id_equipo DESC";
		$result=mysqli_query($conexion,$sql);
	} else {
		$result = false;
	}
	$offsetNum = $tieneNumeroEquipoHotel ? 1 : 0; // desplazamiento de índices si hay numero_equipo_hotel
?>

<?php if ($es_super_admin && $listaHoteles): ?>
<!-- Selector de hotel para Super Admin -->
<div class="row" style="margin-bottom: 20px;">
	<div class="col-sm-12">
		<label class="control-label" style="margin-right: 10px;">Ver equipos de:</label>
		<div class="btn-group" role="group">
			<?php 
			while ($rowH = mysqli_fetch_row($listaHoteles)) {
				$hid = (int)$rowH[0];
				$hnom = htmlspecialchars($rowH[1]);
				$activo = ($id_hotel === $hid);
				echo '<button type="button" class="btn ' . ($activo ? 'btn-warning' : 'btn-default') . ' btn-sm" onclick="cargarEquiposPorHotel(' . $hid . ')">' . $hnom . '</button>';
			}
			mysqli_data_seek($listaHoteles, 0); // reset para usar de nuevo si hace falta
			?>
		</div>
		<?php if ($id_hotel === null): ?>
		<p class="text-muted" style="margin-top: 12px;"><span class="glyphicon glyphicon-info-sign"></span> Seleccione un hotel para ver sus equipos.</p>
		<?php else: ?>
		<p class="text-muted" style="margin-top: 8px;">Mostrando equipos de <strong><?php echo htmlspecialchars($nombre_hotel_actual); ?></strong></p>
		<?php endif; ?>
	</div>
</div>
<script type="text/javascript">
	// Debe estar siempre definida para que los botones de hotel funcionen al hacer clic
	function cargarEquiposPorHotel(idHotel) {
		if (!idHotel) return;
		$('#verEquipos').load('equipos/tablaEquipos.php?id_hotel=' + parseInt(idHotel, 10));
	}
</script>
<?php endif; ?>

<h4>Equipos Registrados<?php echo $nombre_hotel_actual ? ' - ' . htmlspecialchars($nombre_hotel_actual) : ''; ?></h4>
<?php if ($es_super_admin && $id_hotel): ?>
	<span id="equiposIdHotel" data-id-hotel="<?php echo (int)$id_hotel; ?>" style="display:none;"></span>
<?php endif; ?>

<?php if ($es_super_admin && $id_hotel === null): ?>
	<p class="text-info">Elija un hotel en los botones de arriba para ver la lista de equipos.</p>
<?php else: ?>
<!-- Barra de búsqueda -->
<div class="row" style="margin-bottom: 15px;">
	<div class="col-sm-6">
		<div class="input-group">
			<span class="input-group-addon">
				<span class="glyphicon glyphicon-search"></span>
			</span>
			<input type="text" class="form-control" id="buscarEquipo" placeholder="Código, descripción, departamento, ubicación física, marca, modelo o serie...">
		</div>
		<p class="text-muted small" style="margin-top:6px;margin-bottom:0;"><span class="glyphicon glyphicon-info-sign"></span> Clic en una <strong>fila</strong> (fuera de casillas, archivos o botones) para ver la ficha completa del equipo.</p>
	</div>
	<div class="col-sm-6">
		<button type="button" class="btn btn-info btn-sm" onclick="limpiarBusqueda()">
			<span class="glyphicon glyphicon-refresh"></span> Limpiar
		</button>
	</div>
</div>
<div class="row" style="margin-bottom: 12px;">
	<div class="col-sm-12">
		<button type="button" class="btn btn-danger btn-sm" id="btnPdfEquiposSeleccionados" title="Solo filas marcadas con la casilla">
			<span class="glyphicon glyphicon-save"></span> Excel equipos marcados
		</button>
		<button type="button" class="btn btn-default btn-sm" id="btnPdfEquiposBusqueda" title="Todas las filas visibles (según el texto de búsqueda)">
			<span class="glyphicon glyphicon-filter"></span> Excel resultados de búsqueda
		</button>
		<button type="button" class="btn btn-default btn-sm" id="btnMarcarVisiblesEquipos">
			<span class="glyphicon glyphicon-check"></span> Marcar visibles
		</button>
		<button type="button" class="btn btn-default btn-sm" id="btnDesmarcarTodosEquipos">
			<span class="glyphicon glyphicon-unchecked"></span> Desmarcar todos
		</button>
		<span class="text-muted small" style="margin-left:8px;">Use la casilla al inicio de cada fila o marque los visibles tras filtrar; se descarga un archivo Excel (.xls).</span>
	</div>
</div>

<form id="formPdfEquiposSeleccion" method="post" action="../reportes/reporteEquiposSeleccionados.php" target="_blank" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;" autocomplete="off">
	<input type="hidden" name="ids" id="inpPdfIdsEquipos" value="">
	<?php if ($es_super_admin && $id_hotel): ?>
	<input type="hidden" name="id_hotel" value="<?php echo (int)$id_hotel; ?>">
	<?php endif; ?>
</form>

<style>
	#verEquipos {
		width: 100%;
	}
	#verEquipos .table-responsive {
		overflow-x: auto;
		overflow-y: visible;
		-webkit-overflow-scrolling: touch;
		overflow-anchor: none;
	}
	#tablaEquipos tbody {
		overflow-anchor: none;
	}
	#tablaEquipos tr.fila-equipo-placeholder td {
		padding: 0 !important;
		border-color: transparent !important;
		background: transparent !important;
		vertical-align: top;
	}
	/* Diálogo Alertify — advertencia eliminar equipo / documentos (pantalla completa) */
	.alertify.alertify-equipo-advertencia {
		position: fixed !important;
		top: 0 !important;
		left: 0 !important;
		right: 0 !important;
		bottom: 0 !important;
		width: 100% !important;
		height: 100% !important;
		z-index: 20000 !important;
		margin: 0 !important;
		padding: 0 !important;
	}
	.alertify.alertify-equipo-advertencia .ajs-modal {
		position: fixed !important;
		top: 0 !important;
		left: 0 !important;
		right: 0 !important;
		bottom: 0 !important;
		width: 100% !important;
		height: 100% !important;
		padding: 0 !important;
		overflow: auto !important;
	}
	.alertify.alertify-equipo-advertencia .ajs-dimmer {
		position: fixed !important;
		top: 0 !important;
		left: 0 !important;
		right: 0 !important;
		bottom: 0 !important;
		width: 100vw !important;
		height: 100vh !important;
		min-height: 100% !important;
		background: var(--velo) !important;
		opacity: 1 !important;
	}
	.alertify.alertify-equipo-advertencia .ajs-dialog {
		background: var(--superficie) !important;
		border: 1px solid var(--borde) !important;
		color: var(--texto) !important;
		border-radius: var(--radio-md) !important;
		max-width: 480px !important;
		margin: 12vh auto 5% auto !important;
	}
	.alertify.alertify-equipo-advertencia .ajs-header {
		background: var(--peligro-suave) !important;
		color: var(--peligro-texto) !important;
		border-bottom: 1px solid var(--borde) !important;
		font-weight: 700 !important;
		font-size: 16px !important;
	}
	.alertify.alertify-equipo-advertencia .ajs-body {
		background: var(--superficie) !important;
		color: var(--texto) !important;
	}
	.alertify.alertify-equipo-advertencia .ajs-body .ajs-content {
		color: var(--texto) !important;
	}
	.alertify.alertify-equipo-advertencia .ajs-footer {
		background: var(--superficie-hundida) !important;
		border-top: 1px solid var(--borde) !important;
	}
	.alertify.alertify-equipo-advertencia .ajs-footer .ajs-buttons.ajs-primary .ajs-button {
		background: var(--peligro) !important;
		border-color: var(--peligro) !important;
		color: #FFFFFF !important;
	}
	.alertify.alertify-equipo-advertencia .ajs-footer .ajs-buttons.ajs-auxiliary .ajs-button {
		background: var(--superficie) !important;
		border-color: var(--borde-fuerte) !important;
		color: var(--texto) !important;
	}
	#tablaEquipos.tabla-equipos-compacta {
		width: 100%;
		max-width: 100%;
		table-layout: fixed;
		font-size: 12px;
		margin-bottom: 0;
	}
	#tablaEquipos td, #tablaEquipos th {
		white-space: normal;
		word-break: break-word;
		overflow-wrap: anywhere;
		line-height: 1.3;
		vertical-align: middle;
		padding: 6px 5px !important;
	}
	#tablaEquipos thead th {
		font-size: 11px !important;
		font-weight: 500;
		padding: 8px 5px !important;
		white-space: normal;
		hyphens: auto;
	}
	#tablaEquipos .col-check { width: 26px; max-width: 26px; }
	#tablaEquipos .col-num { width: 28px; max-width: 32px; }
	#tablaEquipos .col-imagen {
		width: 46px;
		max-width: 46px;
		box-sizing: border-box;
	}
	#tablaEquipos td.col-imagen {
		overflow: hidden;
		vertical-align: middle;
		text-align: center;
		padding: 2px 1px;
		line-height: 0;
	}
	#tablaEquipos td.col-imagen img {
		display: block;
		margin: 0 auto;
		max-width: 42px;
		max-height: 42px;
		width: auto;
		height: auto;
		object-fit: contain;
		border-radius: 4px;
		box-sizing: border-box;
		cursor: pointer;
	}
	#tablaEquipos td.col-imagen .glyphicon {
		font-size: 18px !important;
		line-height: 1;
		display: inline-block;
		vertical-align: middle;
		padding: 2px 0;
	}
	#tablaEquipos .col-codigo {
		width: 8%;
		font-size: 11.5px;
		font-weight: 500;
		word-break: break-all;
	}
	#tablaEquipos .col-descripcion { width: 5.5%; }
	#tablaEquipos .col-depto { width: 5.5%; }
	#tablaEquipos .col-ubic { width: 5.5%; }
	#tablaEquipos .col-usuario { width: 5%; }
	#tablaEquipos .col-nfactura { width: 4.5%; }
	#tablaEquipos .col-marca-modelo { width: 7%; }
	#tablaEquipos .col-serie { width: 5.5%; }
	#tablaEquipos .col-estado { width: 4.5%; }
	#tablaEquipos .col-valor { width: 5%; }
	#tablaEquipos .col-moneda { width: 3%; }
	#tablaEquipos .col-fecha { width: 4.5%; }
	#tablaEquipos .col-resguardo,
	#tablaEquipos .col-factura {
		width: 6.5%;
		min-width: 72px;
		vertical-align: top;
	}
	#tablaEquipos .col-resguardo .cell-docs,
	#tablaEquipos .col-factura .cell-docs {
		display: flex;
		flex-direction: column;
		align-items: stretch;
		gap: 2px;
	}
	#tablaEquipos .col-resguardo .pdf-input,
	#tablaEquipos .col-factura .pdf-input {
		width: 100%;
		max-width: 100%;
		font-size: 8px !important;
		line-height: 1;
		padding: 1px 0;
		height: auto;
		min-height: 18px;
	}
	#tablaEquipos .col-resguardo .pdf-actions,
	#tablaEquipos .col-factura .pdf-actions {
		display: flex;
		flex-direction: row;
		flex-wrap: wrap;
		gap: 2px;
		justify-content: center;
	}
	#tablaEquipos .btn-doc-xs {
		padding: 1px 4px;
		font-size: 9px;
		line-height: 1.2;
		min-width: 22px;
	}
	#tablaEquipos .btn-doc-xs .glyphicon {
		font-size: 10px;
		margin: 0;
	}
	#tablaEquipos .label-estado-xs {
		display: inline-block;
		font-size: 11px;
		padding: 2px 6px;
		line-height: 1.2;
		max-width: 100%;
		white-space: normal;
		text-align: center;
	}
	#tablaEquipos .doc-sin-archivo {
		font-size: 11px;
		color: var(--texto-secundario);
	}
	#tablaEquipos .col-accion-editar,
	#tablaEquipos .col-accion-eliminar {
		width: 34px;
		min-width: 34px;
		max-width: 34px;
		padding: 2px 1px !important;
	}
	#tablaEquipos .col-accion-editar .btn,
	#tablaEquipos .col-accion-eliminar .btn {
		padding: 2px 4px;
	}
	#tablaEquipos td.col-accion-editar,
	#tablaEquipos th.col-accion-editar {
		position: sticky;
		right: 34px;
		z-index: 3;
		background: var(--superficie) !important;
	}
	#tablaEquipos td.col-accion-eliminar,
	#tablaEquipos th.col-accion-eliminar {
		position: sticky;
		right: 0;
		z-index: 4;
		background: var(--superficie) !important;
	}
	#tablaEquipos thead th.col-accion-editar,
	#tablaEquipos thead th.col-accion-eliminar {
		background: var(--superficie-hundida) !important;
	}

	@media (max-width: 1600px) {
		#tablaEquipos.tabla-equipos-compacta {
			font-size: 11.5px;
		}
		#tablaEquipos .col-codigo { font-size: 11px; }
	}

	@media (max-width: 768px) {
		#tablaEquipos.tabla-equipos-compacta {
			font-size: 11px;
		}
	}
	#tablaEquipos tbody tr.fila-equipo-tabla {
		cursor: pointer;
	}
	#tablaEquipos tbody tr.fila-equipo-tabla:hover {
		background-color: var(--dorado-suave) !important;
	}
	#modalVerFichaEquipo .modal-content {
		background: var(--superficie);
		color: var(--texto);
		border: 1px solid var(--borde);
	}
	#modalVerFichaEquipo .modal-header {
		border-bottom-color: var(--borde);
		background: transparent;
	}
	#modalVerFichaEquipo .modal-title { color: var(--texto); }
	#modalVerFichaEquipo .modal-footer {
		border-top-color: var(--borde);
		background: var(--superficie-hundida);
	}
	#modalVerFichaEquipo .vf-panel {
		background: var(--superficie);
		border: 1px solid var(--borde);
		border-radius: var(--radio-md);
		padding: 12px 14px;
		margin-bottom: 12px;
	}
	#modalVerFichaEquipo .vf-panel h5 {
		margin: 0 0 10px 0;
		color: var(--texto);
		font-size: 15px;
		font-weight: 600;
		border-bottom: 1px solid var(--borde);
		padding-bottom: 6px;
	}
	#modalVerFichaEquipo .vf-row {
		display: flex;
		flex-wrap: wrap;
		margin: 0 -6px 6px -6px;
	}
	#modalVerFichaEquipo .vf-field {
		flex: 1 1 45%;
		min-width: 200px;
		padding: 0 6px;
		margin-bottom: 8px;
	}
	#modalVerFichaEquipo .vf-label {
		display: block;
		font-size: 11px;
		color: var(--texto-secundario);
		text-transform: uppercase;
		letter-spacing: 0.04em;
		margin-bottom: 2px;
	}
	#modalVerFichaEquipo .vf-value {
		font-size: 14px;
		color: var(--texto);
		word-break: break-word;
	}
	#modalVerFichaEquipo .vf-foto-wrap {
		text-align: center;
		margin-bottom: 12px;
	}
	#modalVerFichaEquipo .vf-foto-wrap img {
		max-width: 100%;
		max-height: 220px;
		border-radius: var(--radio-md);
		border: 1px solid var(--borde);
	}
	#modalVerFichaEquipo .vf-acciones-doc .btn { margin-right: 6px; margin-bottom: 4px; }
	/* Filas de una línea con desplazamiento horizontal; las acciones quedan fijas a la derecha */
	#tablaEquipos.tabla-equipos-compacta { table-layout: auto; min-width: 1560px; }
	#tablaEquipos td, #tablaEquipos th { white-space: nowrap; word-break: normal; overflow-wrap: normal; }
	#tablaEquipos td.col-descripcion, #tablaEquipos td.col-ubicacion-fisica { white-space: normal; min-width: 190px; }
	#tablaEquipos td.col-marca-modelo { white-space: normal; min-width: 150px; }
	#tablaEquipos td.col-codigo { word-break: normal; }
	#tablaEquipos .col-accion-editar .btn, #tablaEquipos .col-accion-eliminar .btn { min-height: 28px; padding: 2px 8px; }
</style>

<div class="row">
	<div class="col-sm-12">
		<div class="table-responsive">
			<table class="table table-hover table-condensed table-bordered tabla-equipos-compacta" id="tablaEquipos" style="text-align: center;">
				<caption><label>Equipos del Hotel</label></caption>
				<thead>
				<tr>
					<th class="col-check" style="text-align:center;" title="Seleccionar filas para Excel"><input type="checkbox" id="chkSeleccionarEquiposVisibles" title="Marcar o desmarcar visibles"></th>
					<th class="col-num" title="Número">Nº</th>
					<th class="col-imagen" title="Foto del equipo">Img</th>
					<th class="col-codigo" title="Código del equipo">Código</th>
					<th class="col-descripcion" title="Descripción">Desc.</th>
					<th class="col-depto" title="Departamento">Depto.</th>
					<th class="col-ubic" title="Ubicación física">Ubic.</th>
					<th class="col-usuario" title="Usuario (resguardo / registro)">Usuario</th>
					<th class="col-nfactura" title="Número de factura">Nº fact.</th>
					<th class="col-marca-modelo" title="Marca y modelo">Marca/Mod.</th>
					<th class="col-serie" title="Número de serie">Serie</th>
					<th class="col-estado" title="Estado">Estado</th>
					<th class="col-valor" title="Valor de adquisición">Valor</th>
					<th class="col-moneda" title="Tipo de moneda (USD, MXN…)">Mon.</th>
					<th class="col-fecha" title="Fecha de ingreso">Fecha</th>
					<th class="col-resguardo" title="Resguardo firmado (PDF)">Resg.</th>
					<th class="col-factura" title="Factura escaneada">Fact.</th>
					<th class="col-accion-editar" title="Editar equipo">Ed.</th>
					<th class="col-accion-eliminar" title="Eliminar equipo">El.</th>
				</tr>
				</thead>
				<tbody>
				<?php if ($result): while($ver=mysqli_fetch_row($result)): 
					$o = $offsetNum; // … depto=11+o, usuario=12+o, factura=13+o, foto=14+o
					$descCompleta = isset($ver[2 + $o]) ? (string)$ver[2 + $o] : '';
					$ubiFila = isset($ver[7 + $o]) ? trim((string)$ver[7 + $o]) : '';
				?>
					<tr class="fila-equipo-tabla" data-id-equipo="<?php echo (int)$ver[0]; ?>" data-descripcion-completa="<?php echo htmlspecialchars($descCompleta, ENT_QUOTES, 'UTF-8'); ?>">
						<td class="col-check" style="text-align:center;">
							<input type="checkbox" class="chk-equipo" value="<?php echo (int)$ver[0]; ?>" title="Incluir en Excel">
						</td>
						<td class="col-num"><?php echo $tieneNumeroEquipoHotel && isset($ver[1]) && $ver[1] !== null && $ver[1] !== '' ? (int)$ver[1] : (int)$ver[0]; ?></td>
						<td class="col-imagen">
							<?php 
							$rutaImagenEquipo = $ver[14 + $o];
							$rutaFinalImagen = obtenerRutaFotoEquipo($rutaImagenEquipo);
							if($rutaFinalImagen): ?>
								<img src="<?php echo $rutaFinalImagen; ?>" alt="Foto del equipo"
									 data-toggle="modal" data-target="#modalImagen" onclick="mostrarImagen('<?php echo $rutaFinalImagen; ?>')"
									 title="Click para ampliar">
							<?php else: ?>
								<span class="glyphicon glyphicon-picture" style="color: var(--texto-secundario);" title="Ruta original: <?php echo $rutaImagenEquipo; ?>"></span>
							<?php endif; ?>
						</td>
						<td class="col-codigo"><?php echo $ver[1 + $o]; ?></td>
						<td class="col-descripcion"><?php echo $ver[2 + $o]; ?></td>
						<td class="col-depto"><?php echo isset($ver[11 + $o]) && $ver[11 + $o] ? htmlspecialchars((string)$ver[11 + $o], ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>'; ?></td>
						<td class="col-ubicacion-fisica"><?php echo $ubiFila !== '' ? htmlspecialchars($ubiFila, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>'; ?></td>
						<td class="col-usuario"><?php
							$uReg = isset($ver[12 + $o]) ? trim((string)$ver[12 + $o]) : '';
							echo $uReg !== '' ? htmlspecialchars($uReg, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>';
						?></td>
						<td class="col-nfactura"><?php
							$nFac = isset($ver[13 + $o]) ? trim((string)$ver[13 + $o]) : '';
							echo $nFac !== '' ? htmlspecialchars($nFac, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>';
						?></td>
						<td class="col-marca-modelo"><?php echo $ver[3 + $o] . ' ' . $ver[4 + $o]; ?></td>
						<td class="col-serie"><?php echo $ver[5 + $o]; ?></td>
						<td class="col-estado">
							<?php 
								if($ver[6 + $o] == 'Activo'){
									echo '<span class="label label-success label-estado-xs" title="Activo">Activo</span>';
								}elseif($ver[6 + $o] == 'En Mantenimiento'){
									echo '<span class="label label-warning label-estado-xs" title="En Mantenimiento">Mant.</span>';
								}elseif($ver[6 + $o] == 'En Reparación'){
									echo '<span class="label label-info label-estado-xs" title="En Reparación">Repar.</span>';
								}elseif($ver[6 + $o] == 'Dado de Baja'){
									echo '<span class="label label-danger label-estado-xs" title="Dado de Baja">Baja</span>';
								}
							?>
						</td>
						<td class="col-valor"><?php echo $ver[9 + $o] . ' ' . number_format($ver[8 + $o], 2); ?></td>
						<td class="col-moneda"><?php echo $ver[9 + $o]; ?></td>
						<td class="col-fecha"><?php echo (!empty($ver[10 + $o]) && strtotime($ver[10 + $o]) !== false) ? date('d/m/y', strtotime($ver[10 + $o])) : ''; ?></td>
						<td class="col-resguardo">
							<?php
								$idEquipoFila = (int)$ver[0];
								$nombrePdf = 'equipo_' . $idEquipoFila . '.pdf';
								$rutaPdfRel = '../archivos/resguardos_firmados/' . $nombrePdf;
								$rutaPdfFisica = __DIR__ . '/../../archivos/resguardos_firmados/' . $nombrePdf;
								$existePdfFirmado = file_exists($rutaPdfFisica);
							?>
							<div class="cell-docs">
							<input type="file" id="pdfResguardo_<?php echo $idEquipoFila; ?>" class="pdf-input" accept="application/pdf" title="Elegir PDF">
							<div class="pdf-actions">
								<button type="button" class="btn btn-info btn-xs btn-doc-xs" title="Subir resguardo firmado (PDF)" onclick="subirResguardoFirmado('<?php echo $idEquipoFila; ?>')">
									<span class="glyphicon glyphicon-upload"></span>
								</button>
								<span class="doc-links" id="docLinksResguardo_<?php echo $idEquipoFila; ?>">
								<?php if($existePdfFirmado): ?>
									<a href="<?php echo $rutaPdfRel . '?v=' . filemtime($rutaPdfFisica); ?>" class="btn btn-primary btn-xs btn-doc-xs" target="_blank" rel="noopener" title="Ver PDF">
										<span class="glyphicon glyphicon-eye-open"></span>
									</a>
									<a href="<?php echo $rutaPdfRel . '?v=' . filemtime($rutaPdfFisica); ?>" class="btn btn-success btn-xs btn-doc-xs" target="_blank" rel="noopener" title="Descargar PDF">
										<span class="glyphicon glyphicon-download-alt"></span>
									</a>
									<button type="button" class="btn btn-danger btn-xs btn-doc-xs" title="Eliminar PDF de resguardo" onclick="eliminarDocumentoEquipo('<?php echo $idEquipoFila; ?>', 'resguardo')">
										<span class="glyphicon glyphicon-trash"></span>
									</button>
								<?php else: ?>
									<span class="doc-sin-archivo" title="Sin resguardo">—</span>
								<?php endif; ?>
								</span>
							</div>
							</div>
						</td>
						<td class="col-factura">
							<?php
								$factArch = obtenerArchivoFacturaEscaneada($idEquipoFila);
								$existeFact = !empty($factArch['existe']);
								$rFactRel = $existeFact ? $factArch['rel'] : '';
								$tFact = $existeFact ? (int)$factArch['mtime'] : 0;
							?>
							<div class="cell-docs">
							<input type="file" id="facturaEquipo_<?php echo $idEquipoFila; ?>" class="pdf-input" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*" title="PDF o imagen">
							<div class="pdf-actions">
								<button type="button" class="btn btn-info btn-xs btn-doc-xs" title="Subir factura escaneada" onclick="subirFacturaEquipo('<?php echo $idEquipoFila; ?>')">
									<span class="glyphicon glyphicon-upload"></span>
								</button>
								<span class="doc-links" id="docLinksFactura_<?php echo $idEquipoFila; ?>">
								<?php if ($existeFact): ?>
									<a href="<?php echo htmlspecialchars($rFactRel . '?v=' . $tFact, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-xs btn-doc-xs" target="_blank" rel="noopener" title="Ver archivo">
										<span class="glyphicon glyphicon-eye-open"></span>
									</a>
									<a href="<?php echo htmlspecialchars($rFactRel . '?v=' . $tFact, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success btn-xs btn-doc-xs" download="<?php echo htmlspecialchars('factura_equipo_' . $idEquipoFila . '_' . basename($factArch['nombre']), ENT_QUOTES, 'UTF-8'); ?>" title="Descargar">
										<span class="glyphicon glyphicon-download-alt"></span>
									</a>
									<button type="button" class="btn btn-danger btn-xs btn-doc-xs" title="Eliminar factura escaneada" onclick="eliminarDocumentoEquipo('<?php echo $idEquipoFila; ?>', 'factura')">
										<span class="glyphicon glyphicon-trash"></span>
									</button>
								<?php else: ?>
									<span class="doc-sin-archivo" title="Sin factura">—</span>
								<?php endif; ?>
								</span>
							</div>
							</div>
						</td>
						<td class="col-accion-editar">
							<span class="btn btn-warning btn-xs" data-toggle="modal" data-target="#abremodalUpdateEquipo" onclick="agregaDato('<?php echo $ver[0]; ?>')">
								<span class="glyphicon glyphicon-pencil"></span>
							</span>
						</td>
						<td class="col-accion-eliminar">
							<span class="btn btn-danger btn-xs" onclick="eliminarEquipo('<?php echo $ver[0]; ?>', event)">
								<span class="glyphicon glyphicon-remove"></span>
							</span>
						</td>
					</tr>
				<?php endwhile; endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- Modal ficha del equipo (solo lectura) -->
<div class="modal fade" id="modalVerFichaEquipo" tabindex="-1" role="dialog" aria-labelledby="modalVerFichaLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar" ><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="modalVerFichaLabel"><span class="glyphicon glyphicon-modal-window"></span> Ficha del equipo</h4>
			</div>
			<div class="modal-body" style="max-height:70vh;overflow-y:auto;">
				<div class="vf-foto-wrap">
					<img id="vfFotoEquipo" src="" alt="Foto del equipo" style="display:none;">
					<p id="vfSinFoto" class="text-muted">Sin fotografía registrada.</p>
				</div>
				<div class="vf-panel">
					<h5>Identificación</h5>
					<div class="vf-row">
						<div class="vf-field"><span class="vf-label">ID interno</span><span class="vf-value" id="vfIdEquipo">—</span></div>
						<div class="vf-field"><span class="vf-label">Código</span><span class="vf-value" id="vfCodigo">—</span></div>
						<div class="vf-field" id="vfWrapNumHotel" style="display:none;"><span class="vf-label">Nº equipo (hotel)</span><span class="vf-value" id="vfNumHotel">—</span></div>
						<div class="vf-field" id="vfWrapHotel" style="display:none;"><span class="vf-label">Hotel</span><span class="vf-value" id="vfNombreHotel">—</span></div>
					</div>
					<div class="vf-row">
						<div class="vf-field" style="flex:1 1 100%;"><span class="vf-label">Descripción</span><span class="vf-value" id="vfDescripcion">—</span></div>
					</div>
				</div>
				<div class="vf-panel">
					<h5>Equipo</h5>
					<div class="vf-row">
						<div class="vf-field"><span class="vf-label">Marca</span><span class="vf-value" id="vfMarca">—</span></div>
						<div class="vf-field"><span class="vf-label">Modelo</span><span class="vf-value" id="vfModelo">—</span></div>
						<div class="vf-field"><span class="vf-label">Nº de serie</span><span class="vf-value" id="vfSerie">—</span></div>
						<div class="vf-field"><span class="vf-label">Estado</span><span class="vf-value" id="vfEstado">—</span></div>
					</div>
				</div>
				<div class="vf-panel">
					<h5>Ubicación y organización</h5>
					<div class="vf-row">
						<div class="vf-field"><span class="vf-label">Departamento</span><span class="vf-value" id="vfDepto">—</span></div>
						<div class="vf-field"><span class="vf-label">Ubicación física</span><span class="vf-value" id="vfUbicacion">—</span></div>
					</div>
				</div>
				<div class="vf-panel">
					<h5>Valor y fechas</h5>
					<div class="vf-row">
						<div class="vf-field"><span class="vf-label">Valor de adquisición</span><span class="vf-value" id="vfValor">—</span></div>
						<div class="vf-field"><span class="vf-label">Moneda</span><span class="vf-value" id="vfMoneda">—</span></div>
						<div class="vf-field"><span class="vf-label">Fecha de ingreso</span><span class="vf-value" id="vfFechaIngreso">—</span></div>
					</div>
				</div>
				<div class="vf-panel">
					<h5>Control (usuario y factura)</h5>
					<div class="vf-row">
						<div class="vf-field"><span class="vf-label">Usuario</span><span class="vf-value" id="vfUsuarioReg">—</span></div>
						<div class="vf-field"><span class="vf-label">Nº factura</span><span class="vf-value" id="vfNumFactura">—</span></div>
					</div>
				</div>
				<div class="vf-panel vf-acciones-doc">
					<h5>Documentos</h5>
					<p class="text-muted small" style="margin-bottom:8px;">Resguardo firmado y factura escaneada si fueron cargados en el sistema.</p>
					<a id="vfLinkResguardo" href="#" class="btn btn-primary btn-sm" target="_blank" rel="noopener" style="display:none;"><span class="glyphicon glyphicon-file"></span> Ver resguardo (PDF)</a>
					<span id="vfSinResguardo" class="text-muted small">Sin resguardo firmado.</span>
					<br style="clear:both;">
					<a id="vfLinkFactura" href="#" class="btn btn-success btn-sm" target="_blank" rel="noopener" style="display:none;margin-top:8px;"><span class="glyphicon glyphicon-picture"></span> Ver factura escaneada</a>
					<span id="vfSinFactura" class="text-muted small" style="display:inline-block;margin-top:8px;">Sin factura escaneada.</span>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
				<button type="button" class="btn btn-warning" id="vfBtnIrEditar"><span class="glyphicon glyphicon-pencil"></span> Editar este equipo</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal para Ver Imagen -->
<div class="modal fade" id="modalImagen" tabindex="-1" role="dialog" aria-labelledby="modalImagenLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="modalImagenLabel">Imagen del Equipo</h4>
			</div>
			<div class="modal-body text-center">
				<img id="imagenEquipo" src="" alt="Imagen del Equipo" style="max-width: 100%; max-height: 500px; border-radius: 8px;">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
			</div>
		</div>
	</div>
</div>

<!-- Modal para Editar Equipo -->
<div class="modal fade" id="abremodalUpdateEquipo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="myModalLabel">Editar Equipo</h4>
			</div>
			<div class="modal-body">
				<form id="frmActualizarEquipo" enctype="multipart/form-data">
					<input type="hidden" id="idEquipo" name="idEquipo">
					<input type="hidden" id="fotoEquipoActual" name="fotoEquipoActual" value="">
					<div class="row">
						<div class="col-sm-12">
							<label>Imagen del equipo</label>
							<div class="row">
								<div class="col-sm-4">
									<div id="previewImagenActual" style="margin-bottom: 8px;">
										<img id="imgEquipoActual" src="" alt="Sin imagen" style="max-width: 120px; max-height: 120px; border-radius: 8px; border: 1px solid var(--borde); object-fit: cover; display: none;">
										<span id="sinImagenActual" class="text-muted" style="display: inline-block; padding: 10px;">Sin imagen</span>
									</div>
									<label class="btn btn-default btn-sm btn-block">
										<span class="glyphicon glyphicon-picture"></span> Cambiar imagen
										<input type="file" id="imagenEquipoU" name="imagenEquipo" accept="image/*" style="display: none;">
									</label>
									<small class="text-muted">Opcional. JPG, PNG, GIF. Máx. 5MB</small>
								</div>
							</div>
						</div>
					</div>
					<hr>
					<div class="row">
						<div class="col-sm-12">
							<label>Código del Equipo *</label>
							<input type="text" class="form-control input-sm" id="codigoEquipoU" name="codigoEquipo" required>
						</div>
					</div>
					
					<div class="row">
						<div class="col-sm-12">
							<label>Descripción del Equipo *</label>
							<textarea class="form-control input-sm" id="descripcionEquipoU" name="descripcionEquipo" rows="3" required></textarea>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-6">
							<label>Marca *</label>
							<input type="text" class="form-control input-sm" id="marcaEquipoU" name="marcaEquipo" required>
						</div>
						<div class="col-sm-6">
							<label>Número de Serie *</label>
							<input type="text" class="form-control input-sm" id="numeroSerieU" name="numeroSerie" required>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-6">
							<label>Modelo</label>
							<input type="text" class="form-control input-sm" id="modeloEquipoU" name="modeloEquipo">
						</div>
						<div class="col-sm-6">
							<label>Estado *</label>
							<select class="form-control input-sm" id="estadoEquipoU" name="estadoEquipo" required>
								<option value="Activo">Activo</option>
								<option value="En Mantenimiento">En Mantenimiento</option>
								<option value="En Reparación">En Reparación</option>
								<option value="Dado de Baja">Dado de Baja</option>
							</select>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-12">
							<label>DEPARTAMENTO: *</label>
							<select class="form-control input-sm" id="ubicacionEquipoU" name="ubicacionEquipo" required>
								<option value="">Selecciona un departamento</option>
								<?php
								while ($departamento=mysqli_fetch_row($resultDept)):
									?>
									<option value="<?php echo $departamento[0]; ?>"><?php echo htmlspecialchars($departamento[1]); ?></option>
								<?php endwhile; ?>
							</select>
							<small class="text-muted">Departamento al que pertenece el equipo</small>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-12">
							<label>Ubicación Física (Dónde se colocará) *</label>
							<input type="text" class="form-control input-sm" id="ubicacionFisicaU" name="ubicacionFisica" placeholder="Ej: Mostrador recepción, área de bar, cuarto de rack..." required>
							<small class="text-muted">Lugar concreto dentro del hotel</small>
						</div>
					</div>

					<hr style="border-color:var(--borde);margin:12px 0;">
					<p class="text-muted small" style="margin-bottom:8px;"><strong>Control de equipo</strong> — mismo dato que en la tabla (usuario / factura). Puede editarlos o dejarlos vacíos.</p>
					<div class="row">
						<div class="col-sm-6">
							<label for="usuarioRegistroEquipoU">Usuario</label>
							<input type="text" class="form-control input-sm" id="usuarioRegistroEquipoU" name="usuarioRegistroEquipo" placeholder="Nombre completo (texto libre)" maxlength="200" autocomplete="off">
						</div>
						<div class="col-sm-6">
							<label for="numeroFacturaEquipoU">Número de factura</label>
							<input type="text" class="form-control input-sm" id="numeroFacturaEquipoU" name="numeroFacturaEquipo" placeholder="Ej: F-2025-001" maxlength="120" autocomplete="off">
						</div>
					</div>

					<div class="row">
						<div class="col-sm-6">
							<label>Costo *</label>
							<input type="number" step="0.01" class="form-control input-sm" id="valorEquipoU" name="valorEquipo" required>
						</div>
						<div class="col-sm-6">
							<label>Tipo de Moneda *</label>
							<select class="form-control input-sm" id="tipoMonedaU" name="tipoMoneda" required>
								<option value="USD">USD - Dólar Estadounidense</option>
								<option value="MXN">MXN - Peso Mexicano</option>
								<option value="EUR">EUR - Euro</option>
							</select>
						</div>
					</div>

					<div class="row">
						<div class="col-sm-12">
							<label>Fecha de Ingreso *</label>
							<input type="date" class="form-control input-sm" id="fechaIngresoU" name="fechaIngreso" required>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
				<button type="button" class="btn btn-primary" id="btnActualizarEquipo">Actualizar Equipo</button>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	function agregaDato(idEquipo){
		$('#imagenEquipoU').val('');
		$('#imgEquipoActual').hide().attr('src', '');
		$('#sinImagenActual').show();
		$.ajax({
			type:"POST",
			data:"idEquipo=" + idEquipo,
			url:"../procesos/equipos/obtenerEquipo.php",
			dataType:"text",
			success:function(r){
				var texto = (r === null || r === undefined) ? '' : String(r).trim();
				if(texto !== '' && texto !== '0'){
					var datos;
					try { datos = JSON.parse(texto); } catch (ex) { alertify.error('No se pudieron cargar los datos del equipo.'); return; }
					$('#idEquipo').val(datos.id_equipo);
					$('#codigoEquipoU').val(datos.codigo_equipo);
					$('#descripcionEquipoU').val(datos.descripcion);
					$('#marcaEquipoU').val(datos.marca);
					$('#modeloEquipoU').val(datos.modelo);
					$('#numeroSerieU').val(datos.numero_serie);
					$('#estadoEquipoU').val(datos.estado);
					$('#ubicacionEquipoU').val(datos.id_departamento);
					$('#ubicacionFisicaU').val(typeof datos.ubicacion !== 'undefined' && datos.ubicacion !== null ? datos.ubicacion : '');
					$('#valorEquipoU').val(datos.valor_adquisicion);
					$('#tipoMonedaU').val(datos.tipo_moneda);
					$('#fechaIngresoU').val(datos.fecha_ingreso);
					$('#usuarioRegistroEquipoU').val(typeof datos.usuario_registro !== 'undefined' && datos.usuario_registro !== null ? datos.usuario_registro : '');
					$('#numeroFacturaEquipoU').val(typeof datos.numero_factura !== 'undefined' && datos.numero_factura !== null ? datos.numero_factura : '');
					var feRaw = datos.foto_equipo;
					if (feRaw == null || String(feRaw).trim() === '' || String(feRaw).toLowerCase() === 'null') {
						feRaw = '';
					}
					if(datos.foto_equipo_url || feRaw){
						$('#fotoEquipoActual').val(feRaw);
						var urlImagen = datos.foto_equipo_url || feRaw;
						$('#imgEquipoActual').off('error load').on('error', function(){ $(this).hide(); $('#sinImagenActual').show(); }).on('load', function(){ $('#sinImagenActual').hide(); $(this).show(); }).attr('src', urlImagen);
						$('#sinImagenActual').show();
					} else {
						$('#fotoEquipoActual').val('');
						$('#sinImagenActual').show();
						$('#imgEquipoActual').hide();
					}
				}
			}
		});
	}
	// Actualizar equipo + vista previa de imagen: manejadores delegados en vistas/equipos.php (evita duplicados al recargar la tabla)

	function capturarPosicionScrollPagina() {
		var y = window.pageYOffset;
		if (y === undefined || y === null) {
			y = document.documentElement.scrollTop || document.body.scrollTop || 0;
		}
		return { x: window.pageXOffset || 0, y: y };
	}

	function restaurarPosicionScrollPagina(pos) {
		if (!pos) return;
		var y = pos.y;
		if (typeof y !== 'number') return;
		window.scrollTo(pos.x || 0, y);
		document.documentElement.scrollTop = y;
		document.body.scrollTop = y;
	}

	function reprogramarRestaurarScroll(pos) {
		restaurarPosicionScrollPagina(pos);
		requestAnimationFrame(function() { restaurarPosicionScrollPagina(pos); });
		setTimeout(function() { restaurarPosicionScrollPagina(pos); }, 0);
		setTimeout(function() { restaurarPosicionScrollPagina(pos); }, 80);
		setTimeout(function() { restaurarPosicionScrollPagina(pos); }, 250);
	}

	function quitarFilaEquipoTabla(idEquipo) {
		var $fila = $('#tablaEquipos tbody tr.fila-equipo-tabla[data-id-equipo="' + idEquipo + '"]');
		if (!$fila.length) return;
		if (document.activeElement && document.activeElement.blur) {
			try { document.activeElement.blur(); } catch (e) {}
		}
		var alto = $fila.outerHeight();
		var cols = $fila.children('td').length;
		if (alto > 0 && cols > 0) {
			var $placeholder = $('<tr class="fila-equipo-placeholder" aria-hidden="true"></tr>');
			$placeholder.append(
				$('<td/>').attr('colspan', cols).css({
					height: alto + 'px',
					padding: 0,
					margin: 0,
					border: 'none',
					lineHeight: 0,
					fontSize: 0,
					background: 'transparent'
				})
			);
			$fila.after($placeholder);
			$fila.remove();
			requestAnimationFrame(function() {
				requestAnimationFrame(function() {
					$placeholder.remove();
				});
			});
		} else {
			$fila.remove();
		}
	}

	function confirmarAdvertenciaEquipos(titulo, mensajeHtml, onAceptar, onCancelar) {
		var dlg = alertify.confirm(titulo, mensajeHtml, onAceptar, onCancelar);
		dlg.set({
			title: titulo,
			labels: { ok: 'Sí, eliminar', cancel: 'Cancelar' },
			movable: false,
			resizable: false,
			closable: true,
			closableByDimmer: false,
			onshow: function() {
				var root = this.elements && this.elements.root;
				if (!root) return;
				if (root.parentNode !== document.body) {
					document.body.appendChild(root);
				}
				root.classList.add('alertify-equipo-advertencia');
				var header = root.querySelector('.ajs-header');
				if (header) header.textContent = titulo;
			},
			onclose: function() {
				var root = this.elements && this.elements.root;
				if (root) root.classList.remove('alertify-equipo-advertencia');
			}
		});
		return dlg;
	}

	function eliminarEquipo(idEquipo, ev){
		if (ev) {
			ev.preventDefault();
			ev.stopPropagation();
		}
		idEquipo = String(idEquipo);
		var posScroll = capturarPosicionScrollPagina();

		confirmarAdvertenciaEquipos(
			'Advertencia',
			'<strong>¿Está seguro de eliminar este equipo?</strong><br><br>' +
			'Se eliminará el registro del equipo y los datos vinculados (bajas, resguardos, facturas escaneadas, etc.).<br>' +
			'<span style="color:var(--peligro);">Esta acción no se puede deshacer.</span>',
			function(){
				$.ajax({
					type:"POST",
					data:"idEquipo=" + idEquipo,
					url:"../procesos/equipos/eliminaEquipo.php",
					success:function(r){
						if(String(r).trim() === '1'){
							quitarFilaEquipoTabla(idEquipo);
							alertify.success("Equipo eliminado con éxito");
							reprogramarRestaurarScroll(posScroll);
						}else{
							alertify.error("Error al eliminar el equipo");
						}
					},
					error: function(){
						alertify.error("Error de conexión al eliminar el equipo");
					}
				});
			},
			function(){
				alertify.message('Eliminación cancelada');
			}
		);
	}

	function mostrarImagen(rutaImagen){
		document.getElementById('imagenEquipo').src = rutaImagen;
	}

	function vfTextoFicha(v) {
		if (v === null || v === undefined) return '—';
		var s = String(v).trim();
		return s === '' ? '—' : s;
	}
	function vfFechaFicha(s) {
		if (!s) return '—';
		s = String(s).trim();
		if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
			var p = s.split('-');
			return p[2] + '/' + p[1] + '/' + p[0];
		}
		return s;
	}
	function vfNumeroFicha(num) {
		var n = parseFloat(String(num).replace(',', ''));
		if (isNaN(n)) return vfTextoFicha(num);
		return n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}

	function verFichaEquipo(idEquipo) {
		idEquipo = parseInt(idEquipo, 10);
		if (!idEquipo) return;
		window._vfIdEquipoParaEditar = idEquipo;
		$.ajax({
			type: 'POST',
			url: '../procesos/equipos/obtenerEquipo.php',
			data: { idEquipo: idEquipo },
			dataType: 'json',
			success: function(d) {
				if (!d || d.id_equipo === undefined || d.id_equipo === null) {
					alertify.error('No se pudo cargar la ficha del equipo.');
					return;
				}
				$('#vfIdEquipo').text(String(d.id_equipo));
				$('#vfCodigo').text(vfTextoFicha(d.codigo_equipo));
				$('#vfDescripcion').text(vfTextoFicha(d.descripcion));
				$('#vfMarca').text(vfTextoFicha(d.marca));
				$('#vfModelo').text(vfTextoFicha(d.modelo));
				$('#vfSerie').text(vfTextoFicha(d.numero_serie));
				$('#vfEstado').text(vfTextoFicha(d.estado));
				if (d.nombre_departamento) {
					$('#vfDepto').text(d.nombre_departamento);
				} else if (d.id_departamento) {
					$('#vfDepto').text('ID ' + d.id_departamento);
				} else {
					$('#vfDepto').text('—');
				}
				$('#vfUbicacion').text(vfTextoFicha(d.ubicacion));
				$('#vfValor').text(vfNumeroFicha(d.valor_adquisicion));
				$('#vfMoneda').text(vfTextoFicha(d.tipo_moneda));
				$('#vfFechaIngreso').text(vfFechaFicha(d.fecha_ingreso));
				$('#vfUsuarioReg').text(vfTextoFicha(d.usuario_registro));
				$('#vfNumFactura').text(vfTextoFicha(d.numero_factura));
				if (d.numero_equipo_hotel !== null && d.numero_equipo_hotel !== undefined && String(d.numero_equipo_hotel) !== '') {
					$('#vfWrapNumHotel').show();
					$('#vfNumHotel').text(String(d.numero_equipo_hotel));
				} else {
					$('#vfWrapNumHotel').hide();
				}
				if (d.nombre_hotel) {
					$('#vfWrapHotel').show();
					$('#vfNombreHotel').text(d.nombre_hotel);
				} else {
					$('#vfWrapHotel').hide();
				}
				var foto = d.foto_equipo_url || '';
				if (foto) {
					$('#vfFotoEquipo').off('error').on('error', function(){ $(this).hide(); $('#vfSinFoto').show(); })
						.attr('src', foto).show();
					$('#vfSinFoto').hide();
				} else {
					$('#vfFotoEquipo').hide().attr('src', '');
					$('#vfSinFoto').show();
				}
				if (d.tiene_resguardo_firmado && d.url_resguardo_firmado) {
					$('#vfLinkResguardo').attr('href', d.url_resguardo_firmado).show();
					$('#vfSinResguardo').hide();
				} else {
					$('#vfLinkResguardo').hide();
					$('#vfSinResguardo').show();
				}
				if (d.tiene_factura_escaneada && d.url_factura_escaneada) {
					$('#vfLinkFactura').attr('href', d.url_factura_escaneada).show();
					$('#vfSinFactura').hide();
				} else {
					$('#vfLinkFactura').hide();
					$('#vfSinFactura').show();
				}
				$('#modalVerFichaEquipo').modal('show');
			},
			error: function() {
				alertify.error('Error al cargar la ficha (¿sesión vencida o equipo inexistente?).');
			}
		});
	}

	function escHtmlDoc(s) {
		return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
	}

	function botonEliminarDocumentoHtml(idEquipo, tipo) {
		return '<button type="button" class="btn btn-danger btn-xs btn-doc-xs" title="Eliminar archivo subido" onclick="eliminarDocumentoEquipo(\'' +
			escHtmlDoc(String(idEquipo)) + '\', \'' + escHtmlDoc(tipo) + '\')">' +
			'<span class="glyphicon glyphicon-trash"></span></button>';
	}

	function quitarEnlacesDocumentoEquipo(idEquipo, tipo) {
		var id = String(idEquipo);
		var $links = $('#docLinks' + (tipo === 'resguardo' ? 'Resguardo' : 'Factura') + '_' + id);
		if (!$links.length) return;
		var titulo = tipo === 'resguardo' ? 'Sin resguardo' : 'Sin factura';
		$links.html('<span class="doc-sin-archivo" title="' + escHtmlDoc(titulo) + '">—</span>');
	}

	function actualizarEnlacesDocumentoEquipo(idEquipo, tipo, r) {
		var id = String(idEquipo);
		var $links = $('#docLinks' + (tipo === 'resguardo' ? 'Resguardo' : 'Factura') + '_' + id);
		if (!$links.length) return;
		var url = r.urlVer || r.url || '';
		if (!url) return;
		var html;
		if (tipo === 'resguardo') {
			html =
				'<a href="' + escHtmlDoc(url) + '" class="btn btn-primary btn-xs btn-doc-xs" target="_blank" rel="noopener" title="Ver PDF">' +
					'<span class="glyphicon glyphicon-eye-open"></span></a>' +
				'<a href="' + escHtmlDoc(url) + '" class="btn btn-success btn-xs btn-doc-xs" target="_blank" rel="noopener" title="Descargar PDF">' +
					'<span class="glyphicon glyphicon-download-alt"></span></a>';
		} else {
			var dl = r.downloadNombre || '';
			html =
				'<a href="' + escHtmlDoc(url) + '" class="btn btn-primary btn-xs btn-doc-xs" target="_blank" rel="noopener" title="Ver archivo">' +
					'<span class="glyphicon glyphicon-eye-open"></span></a>' +
				'<a href="' + escHtmlDoc(url) + '" class="btn btn-success btn-xs btn-doc-xs" download="' + escHtmlDoc(dl) + '" title="Descargar">' +
					'<span class="glyphicon glyphicon-download-alt"></span></a>';
		}
		html += botonEliminarDocumentoHtml(id, tipo);
		$links.html(html);
	}

	function eliminarDocumentoEquipo(idEquipo, tipo) {
		idEquipo = String(idEquipo);
		var etiqueta = tipo === 'resguardo' ? 'el resguardo firmado (PDF)' : 'la factura escaneada';
		confirmarAdvertenciaEquipos(
			'Advertencia',
			'<strong>¿Está seguro de eliminar ' + etiqueta + '?</strong><br><br>' +
			'El archivo se borrará del servidor de forma permanente.<br>' +
			'<span style="color:var(--peligro);">Esta acción no se puede deshacer.</span>',
			function() {
				$.ajax({
					type: 'POST',
					url: '../procesos/equipos/eliminarDocumentoEquipo.php',
					data: { idEquipo: idEquipo, tipo: tipo },
					dataType: 'json',
					success: function(r) {
						if (r && r.ok) {
							alertify.success(r.mensaje || 'Archivo eliminado correctamente');
							quitarEnlacesDocumentoEquipo(idEquipo, tipo);
							limpiarInputDocumentoEquipo(idEquipo, tipo);
						} else {
							alertify.error((r && r.mensaje) ? r.mensaje : 'No se pudo eliminar el archivo.');
						}
					},
					error: function() {
						alertify.error('Error de conexión al eliminar el archivo.');
					}
				});
			},
			function() {
				alertify.message('Eliminación cancelada');
			}
		);
	}

	function limpiarInputDocumentoEquipo(idEquipo, tipo) {
		var inputId = tipo === 'resguardo' ? ('pdfResguardo_' + idEquipo) : ('facturaEquipo_' + idEquipo);
		var input = document.getElementById(inputId);
		if (input) input.value = '';
	}

	function subirFacturaEquipo(idEquipo){
		var input = document.getElementById('facturaEquipo_' + idEquipo);
		if (!input || !input.files || !input.files[0]) {
			alertify.alert('Selecciona un archivo (PDF o imagen escaneada).');
			return;
		}
		var archivo = input.files[0];
		var nombre = (archivo.name || '').toLowerCase();
		var ok = /\.(pdf|jpg|jpeg|png|gif|webp)$/.test(nombre);
		if (!ok) {
			alertify.alert('Use PDF, JPG, PNG, GIF o WEBP.');
			return;
		}
		var data = new FormData();
		data.append('idEquipo', idEquipo);
		data.append('facturaEquipo', archivo);
		$.ajax({
			type: 'POST',
			url: '../procesos/equipos/subirFacturaEquipo.php',
			data: data,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(r){
				if (r && r.ok) {
					alertify.success(r.mensaje || 'Archivo subido correctamente');
					actualizarEnlacesDocumentoEquipo(idEquipo, 'factura', r);
					limpiarInputDocumentoEquipo(idEquipo, 'factura');
				} else {
					alertify.error((r && r.mensaje) ? r.mensaje : 'No se pudo subir el archivo.');
				}
			},
			error: function(){
				alertify.error('Error de conexión al subir la factura.');
			}
		});
	}

	function subirResguardoFirmado(idEquipo){
		var input = document.getElementById('pdfResguardo_' + idEquipo);
		if (!input || !input.files || !input.files[0]) {
			alertify.alert('Selecciona un archivo PDF para subir.');
			return;
		}

		var archivo = input.files[0];
		var nombre = (archivo.name || '').toLowerCase();
		if (!nombre.endsWith('.pdf')) {
			alertify.alert('Solo se permiten archivos PDF.');
			return;
		}

		var data = new FormData();
		data.append('idEquipo', idEquipo);
		data.append('resguardoFirmado', archivo);

		$.ajax({
			type: 'POST',
			url: '../procesos/equipos/subirResguardoFirmado.php',
			data: data,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(r){
				if (r && r.ok) {
					alertify.success(r.mensaje || 'PDF subido correctamente');
					actualizarEnlacesDocumentoEquipo(idEquipo, 'resguardo', r);
					limpiarInputDocumentoEquipo(idEquipo, 'resguardo');
				} else {
					alertify.error((r && r.mensaje) ? r.mensaje : 'No se pudo subir el PDF.');
				}
			},
			error: function(){
				alertify.error('Error de conexión al subir el PDF.');
			}
		});
	}

	// Búsqueda + exportación Excel (selección / resultados visibles)
	$(document).ready(function() {
		$(document).on('click', '#tablaEquipos tbody tr.fila-equipo-tabla', function(e) {
			if ($(e.target).closest('input, button, a, .btn, label, .col-imagen').length) return;
			var id = $(this).data('id-equipo');
			if (id) verFichaEquipo(id);
		});

		$(document).on('click', '#vfBtnIrEditar', function() {
			var id = window._vfIdEquipoParaEditar;
			if (!id) return;
			$('#modalVerFichaEquipo').modal('hide');
			$('#modalVerFichaEquipo').one('hidden.bs.modal', function () {
				agregaDato(String(id));
				$('#abremodalUpdateEquipo').modal('show');
			});
		});

		$('#buscarEquipo').on('keyup', function() {
			var valor = $.trim($(this).val()).toLowerCase();
			$('#tablaEquipos tbody tr').each(function() {
				var $tr = $(this);
				if (valor === '') {
					$tr.show();
					return;
				}
				var num = $tr.find('td:eq(1)').text().toLowerCase();
				var codigo = $tr.find('td:eq(3)').text().toLowerCase();
				var descVisible = $tr.find('td:eq(4)').text().toLowerCase();
				var descCompleta = ($tr.attr('data-descripcion-completa') || '').toLowerCase();
				var depto = $tr.find('td:eq(5)').text().toLowerCase();
				var ubicFis = $tr.find('td:eq(6)').text().toLowerCase();
				var usuarioCol = $tr.find('td:eq(7)').text().toLowerCase();
				var facturaCol = $tr.find('td:eq(8)').text().toLowerCase();
				var marcaModelo = $tr.find('td:eq(9)').text().toLowerCase();
				var serie = $tr.find('td:eq(10)').text().toLowerCase();
				var estado = $tr.find('td:eq(11)').text().toLowerCase();
				var coincide = num.indexOf(valor) > -1
					|| codigo.indexOf(valor) > -1
					|| descVisible.indexOf(valor) > -1
					|| descCompleta.indexOf(valor) > -1
					|| depto.indexOf(valor) > -1
					|| ubicFis.indexOf(valor) > -1
					|| usuarioCol.indexOf(valor) > -1
					|| facturaCol.indexOf(valor) > -1
					|| marcaModelo.indexOf(valor) > -1
					|| serie.indexOf(valor) > -1
					|| estado.indexOf(valor) > -1;
				$tr.toggle(coincide);
			});
		});

		function enviarPdfEquiposSeleccion(ids) {
			if (!ids || ids.length === 0) {
				if (typeof alertify !== 'undefined') { alertify.error('No hay equipos para incluir en el archivo Excel.'); }
				else { alert('No hay equipos para incluir en el archivo Excel.'); }
				return;
			}
			var unicos = [];
			var visto = {};
			for (var i = 0; i < ids.length; i++) {
				var k = String(ids[i]);
				if (!visto[k]) { visto[k] = true; unicos.push(ids[i]); }
			}
			$('#inpPdfIdsEquipos').val(unicos.join(','));
			document.getElementById('formPdfEquiposSeleccion').submit();
		}

		$('#btnPdfEquiposSeleccionados').on('click', function() {
			var ids = [];
			$('#tablaEquipos tbody tr input.chk-equipo:checked').each(function() {
				ids.push($(this).val());
			});
			enviarPdfEquiposSeleccion(ids);
		});

		$('#btnPdfEquiposBusqueda').on('click', function() {
			var ids = [];
			$('#tablaEquipos tbody tr:visible').each(function() {
				var v = $(this).find('input.chk-equipo').val();
				if (v) { ids.push(v); }
			});
			enviarPdfEquiposSeleccion(ids);
		});

		$('#btnMarcarVisiblesEquipos').on('click', function() {
			$('#tablaEquipos tbody tr:visible input.chk-equipo').prop('checked', true);
		});

		$('#btnDesmarcarTodosEquipos').on('click', function() {
			$('#tablaEquipos tbody input.chk-equipo').prop('checked', false);
			$('#chkSeleccionarEquiposVisibles').prop('checked', false);
		});

		$('#chkSeleccionarEquiposVisibles').on('change', function() {
			var marcar = $(this).prop('checked');
			$('#tablaEquipos tbody tr:visible input.chk-equipo').prop('checked', marcar);
		});
	});

	function limpiarBusqueda() {
		$('#buscarEquipo').val('');
		$('#tablaEquipos tbody tr').show();
		$('#chkSeleccionarEquiposVisibles').prop('checked', false);
	}
</script>
<?php endif; ?>
