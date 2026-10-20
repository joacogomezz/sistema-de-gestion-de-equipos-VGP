<?php
	require_once "../seguridad.php";
	requerirAutenticacion();
	require_once "../clases/Conexion.php";

	$cxEq = (new conectar())->conexion();
	$esSuperAdminEq = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$idHotelEq = $esSuperAdminEq ? 0 : (isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : 0);
	$deptosEq = mysqli_query($cxEq, "SELECT id_departamento, nombre, id_hotel FROM departamentos" . ($esSuperAdminEq ? '' : " WHERE id_hotel = $idHotelEq") . " ORDER BY nombre");

	$verCssEq = @filemtime(__DIR__ . '/../css/equipos.css') ?: time();
	$verJsEq = @filemtime(__DIR__ . '/../js/equipos.js') ?: time();
	$titulo_pagina = 'Equipos · The Villa Group';
	$estilos_extra = '<link rel="stylesheet" href="../css/equipos.css?v=' . (int)$verCssEq . '">';
	require_once "menu.php";
?>
<div class="eq">
	<div class="enc">
		<div class="enc__tit"><h1>Equipos</h1><span class="contador" id="contador"><b>…</b>equipos</span></div>
		<div class="enc__acc">
			<button type="button" class="cc-btn cc-btn--secundario" id="btnImportar"><i data-i="subir"></i><span class="lbl">Importar CSV</span></button>
			<button type="button" class="cc-btn cc-btn--secundario" id="btnExportar"><i data-i="excel"></i><span class="lbl">Exportar a Excel</span></button>
			<button type="button" class="cc-btn cc-btn--primario" id="btnRegistrar"><i data-i="mas"></i>Registrar equipo</button>
		</div>
	</div>

	<div class="filtros" id="filtros">
		<label class="cc-buscador"><i data-i="buscar"></i><input class="cc-input" id="q" type="search" placeholder="Código, serie, descripción o responsable" aria-label="Buscar equipos"></label>
		<?php if ($esSuperAdminEq): ?>
		<button type="button" class="fbtn" data-f="hotel"><i data-i="hotel"></i>Hotel <small data-v>Todos</small><i data-i="abajo"></i></button>
		<?php endif; ?>
		<button type="button" class="fbtn" data-f="depto"><i data-i="departamentos"></i>Departamento <small data-v>Todos</small><i data-i="abajo"></i></button>
		<button type="button" class="fbtn" data-f="estado">Estado <small data-v>Todos</small><i data-i="abajo"></i></button>
		<button type="button" class="fbtn" data-f="moneda">Moneda <small data-v>Todas</small><i data-i="abajo"></i></button>
		<button type="button" class="fbtn" data-f="fecha"><i data-i="reloj"></i>Fecha de ingreso <small data-v>Cualquiera</small><i data-i="abajo"></i></button>
		<button type="button" class="limpiar" id="limpiar" hidden>Limpiar filtros</button>
	</div>

	<section class="cc-card tabla-card">
		<div class="tabla-bar">
			<span id="resumen">Cargando inventario…</span>
			<div class="cc-row" style="gap:6px">
				<label class="solo-movil cc-row" style="gap:8px;font-size:13px;color:var(--texto);margin:0"><input type="checkbox" class="cb" id="cbMovil">Seleccionar página</label>
				<button type="button" class="cc-btn cc-btn--secundario cc-btn--sm solo-esc" id="btnCols"><i data-i="columnas"></i>Columnas</button>
			</div>
		</div>
		<div class="tabla-scroll"><table class="cc-t" id="tabla"><thead><tr id="thead"></tr></thead><tbody id="tbody"><tr><td class="cargando-lista" colspan="15">Cargando inventario…</td></tr></tbody></table></div>
		<div class="tarjetas" id="tarjetas"></div>
		<div class="cc-pagina"><span class="rango" id="rango"></span><div class="cc-row" style="gap:12px"><label class="cc-row" style="gap:6px">Filas <select id="size" aria-label="Filas por página"><option>25</option><option>50</option><option>100</option></select></label><div class="pags" id="pags"></div></div></div>
	</section>
</div>

<div class="flota" id="flota" hidden role="region" aria-label="Acciones sobre la selección">
	<span class="flota__n" id="flotaN">0 seleccionados</span>
	<button type="button" class="todos" id="selTodos" hidden></button>
	<span class="flota__sep"></span>
	<button type="button" id="fExportar"><i data-i="excel"></i><span class="lbl">Exportar</span></button>
	<button type="button" id="fResguardo"><i data-i="resguardos"></i><span class="lbl">Resguardo múltiple</span></button>
	<button type="button" id="fBaja" class="peligro"><i data-i="bajas-masivas"></i><span class="lbl">Baja masiva</span></button>
	<button type="button" id="fCerrar" aria-label="Quitar selección"><i data-i="cerrar"></i></button>
</div>

<div class="cc-velo-det" id="velo"></div>
<aside class="cc-panel-det" id="panel" aria-label="Detalle del equipo" aria-hidden="true"></aside>
<div class="mod-velo" id="modal" hidden></div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<input type="file" id="fileDoc" hidden>

<form id="formExcel" method="post" action="../reportes/reporteEquiposSeleccionados.php" target="_blank" hidden>
	<input type="hidden" name="ids" id="excelIds">
	<input type="hidden" name="id_hotel" id="excelHotel">
</form>

<!-- Alta de equipo e importación CSV: se reutiliza el formulario existente -->
<div class="modal fade" id="modalRegistrar" tabindex="-1" role="dialog" aria-labelledby="modalRegistrarTitulo">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="modalRegistrarTitulo">Registrar equipo</h4>
			</div>
			<div class="modal-body" id="registrarCuerpo"><p class="cc-muted">Cargando formulario…</p></div>
			<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button></div>
		</div>
	</div>
</div>

<!-- Foto ampliada -->
<div class="modal fade" id="modalImagen" tabindex="-1" role="dialog" aria-label="Foto del equipo">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">Foto del equipo</h4>
			</div>
			<div class="modal-body text-center"><img id="imagenEquipo" src="" alt="Foto del equipo" style="max-width:100%;max-height:70vh;border-radius:var(--radio-md)"></div>
		</div>
	</div>
</div>

<!-- Editar equipo (mismos campos y proceso que antes) -->
<div class="modal fade" id="abremodalUpdateEquipo" tabindex="-1" role="dialog" aria-labelledby="tituloEditarEquipo">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="tituloEditarEquipo">Editar equipo</h4>
			</div>
			<div class="modal-body">
				<form id="frmActualizarEquipo" enctype="multipart/form-data">
					<input type="hidden" id="idEquipo" name="idEquipo">
					<input type="hidden" id="fotoEquipoActual" name="fotoEquipoActual" value="">
					<div class="row">
						<div class="col-sm-4">
							<label>Foto del equipo</label>
							<div style="margin-bottom:8px">
								<img id="imgEquipoActual" src="" alt="Foto actual" style="max-width:140px;max-height:140px;border-radius:var(--radio-md);border:1px solid var(--borde);object-fit:cover;display:none">
								<span id="sinImagenActual" class="text-muted" style="display:inline-block;padding:10px 0">Sin foto</span>
							</div>
							<label class="btn btn-default btn-sm btn-block" style="margin:0">
								Cambiar foto
								<input type="file" id="imagenEquipoU" name="imagenEquipo" accept="image/*" style="display:none">
							</label>
							<small class="text-muted">Opcional. JPG, PNG o GIF, máximo 5 MB.</small>
						</div>
						<div class="col-sm-8">
							<div class="form-group"><label for="codigoEquipoU">Código del equipo *</label><input type="text" class="form-control" id="codigoEquipoU" name="codigoEquipo" required></div>
							<div class="form-group"><label for="descripcionEquipoU">Descripción *</label><textarea class="form-control" id="descripcionEquipoU" name="descripcionEquipo" rows="2" required></textarea></div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-6 form-group"><label for="marcaEquipoU">Marca *</label><input type="text" class="form-control" id="marcaEquipoU" name="marcaEquipo" required></div>
						<div class="col-sm-6 form-group"><label for="modeloEquipoU">Modelo</label><input type="text" class="form-control" id="modeloEquipoU" name="modeloEquipo"></div>
					</div>
					<div class="row">
						<div class="col-sm-6 form-group"><label for="numeroSerieU">Número de serie *</label><input type="text" class="form-control" id="numeroSerieU" name="numeroSerie" required></div>
						<div class="col-sm-6 form-group"><label for="estadoEquipoU">Estado *</label>
							<select class="form-control" id="estadoEquipoU" name="estadoEquipo" required>
								<option value="Activo">Activo</option>
								<option value="En Mantenimiento">En mantenimiento</option>
								<option value="En Reparación">En reparación</option>
								<option value="Dado de Baja">Dado de baja</option>
							</select>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-6 form-group"><label for="ubicacionEquipoU">Departamento *</label>
							<select class="form-control" id="ubicacionEquipoU" name="ubicacionEquipo" required>
								<option value="">Selecciona un departamento</option>
								<?php while ($dep = mysqli_fetch_row($deptosEq)): ?>
								<option value="<?php echo (int)$dep[0]; ?>" data-hotel="<?php echo (int)$dep[2]; ?>"><?php echo htmlspecialchars($dep[1]); ?></option>
								<?php endwhile; ?>
							</select>
						</div>
						<div class="col-sm-6 form-group"><label for="ubicacionFisicaU">Ubicación física *</label><input type="text" class="form-control" id="ubicacionFisicaU" name="ubicacionFisica" placeholder="Ej. Mostrador de recepción, cuarto de rack…" required></div>
					</div>
					<div class="row">
						<div class="col-sm-6 form-group"><label for="usuarioRegistroEquipoU">Usuario responsable</label><input type="text" class="form-control" id="usuarioRegistroEquipoU" name="usuarioRegistroEquipo" placeholder="Nombre completo" maxlength="200" autocomplete="off"></div>
						<div class="col-sm-6 form-group"><label for="numeroFacturaEquipoU">Número de factura</label><input type="text" class="form-control" id="numeroFacturaEquipoU" name="numeroFacturaEquipo" placeholder="Ej. F-2025-001" maxlength="120" autocomplete="off"></div>
					</div>
					<div class="row">
						<div class="col-sm-4 form-group"><label for="valorEquipoU">Costo *</label><input type="number" step="0.01" class="form-control" id="valorEquipoU" name="valorEquipo" required></div>
						<div class="col-sm-4 form-group"><label for="tipoMonedaU">Moneda *</label>
							<select class="form-control" id="tipoMonedaU" name="tipoMoneda" required>
								<option value="MXN">MXN · Peso mexicano</option>
								<option value="USD">USD · Dólar estadounidense</option>
								<option value="EUR">EUR · Euro</option>
							</select>
						</div>
						<div class="col-sm-4 form-group"><label for="fechaIngresoU">Fecha de ingreso *</label><input type="date" class="form-control" id="fechaIngresoU" name="fechaIngreso" required></div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary" id="btnActualizarEquipo">Guardar cambios</button>
			</div>
		</div>
	</div>
</div>

<script>window.EQ_CONFIG = { superAdmin: <?php echo $esSuperAdminEq ? 'true' : 'false'; ?> };</script>
<script src="../js/equipos.js?v=<?php echo (int)$verJsEq; ?>"></script>
		</main>
	</div>
</div>
</body>
</html>
