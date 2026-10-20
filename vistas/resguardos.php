<?php
	require_once "../seguridad.php";
	requerirAutenticacion();
	$esSuperAdminRsg = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;

	$verCssEq = @filemtime(__DIR__ . '/../css/equipos.css') ?: time();
	$verCssRsg = @filemtime(__DIR__ . '/../css/resguardos.css') ?: time();
	$verJsRsg = @filemtime(__DIR__ . '/../js/resguardos.js') ?: time();
	$titulo_pagina = 'Resguardos · The Villa Group';
	$estilos_extra = '<link rel="stylesheet" href="../css/equipos.css?v=' . (int)$verCssEq . '"><link rel="stylesheet" href="../css/resguardos.css?v=' . (int)$verCssRsg . '">';
	require_once "menu.php";
?>
<div class="eq rsg">
	<div class="enc">
		<div>
			<div class="enc__tit"><h1>Resguardos</h1><span class="contador" id="contador"><b>…</b>resguardos</span></div>
			<p class="cc-muted" style="margin:4px 0 0;max-width:640px">Documento donde un empleado firma que recibe un equipo bajo su responsabilidad. Imprime el PDF, recaba la firma y súbelo escaneado.</p>
		</div>
		<div class="enc__acc">
			<button type="button" class="cc-btn cc-btn--secundario" id="btnMultiple"><i data-i="bajas-masivas"></i><span class="lbl">Resguardo múltiple</span></button>
			<button type="button" class="cc-btn cc-btn--primario" id="btnNuevo"><i data-i="mas"></i>Nuevo resguardo</button>
		</div>
	</div>

	<div class="filtros" id="filtros">
		<div class="est-firma" role="group" aria-label="Estado de firma" id="estFirma"></div>
		<label class="cc-buscador"><i data-i="buscar"></i><input class="cc-input" id="q" type="search" placeholder="Folio, código, serie o responsable" aria-label="Buscar resguardos"></label>
		<?php if ($esSuperAdminRsg): ?>
		<button type="button" class="fbtn" data-f="hotel"><i data-i="hotel"></i>Hotel <small data-v>Todos</small><i data-i="abajo"></i></button>
		<?php endif; ?>
		<button type="button" class="fbtn" data-f="area"><i data-i="areas"></i>Área <small data-v>Todas</small><i data-i="abajo"></i></button>
		<button type="button" class="fbtn" data-f="fecha"><i data-i="reloj"></i>Fecha <small data-v>Cualquiera</small><i data-i="abajo"></i></button>
		<button type="button" class="limpiar" id="limpiar" hidden>Limpiar filtros</button>
	</div>

	<section class="cc-card tabla-card">
		<div class="tabla-bar"><span id="resumen">Cargando resguardos…</span><span class="cc-small cc-muted solo-esc">Clic en una fila para ver el documento · la flecha despliega los equipos de un resguardo múltiple</span></div>
		<div class="tabla-scroll"><table class="cc-t"><thead><tr id="thead"></tr></thead><tbody id="tbody"><tr><td class="cargando-lista" colspan="12">Cargando resguardos…</td></tr></tbody></table></div>
		<div class="tarjetas" id="tarjetas"></div>
		<div class="cc-pagina"><span class="rango" id="rango"></span><div class="cc-row" style="gap:12px"><label class="cc-row" style="gap:6px">Filas <select id="size" aria-label="Filas por página"><option>25</option><option>50</option><option>100</option></select></label><div class="pags" id="pags"></div></div></div>
	</section>
</div>

<div class="mod-velo" id="modal" hidden></div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<input type="file" id="filePdf" accept="application/pdf,.pdf" hidden>
<datalist id="listaResp"></datalist>
<datalist id="listaAreas"></datalist>

<script>window.RSG_CONFIG = { superAdmin: <?php echo $esSuperAdminRsg ? 'true' : 'false'; ?> };</script>
<script src="../js/resguardos.js?v=<?php echo (int)$verJsRsg; ?>"></script>
		</main>
	</div>
</div>
</body>
</html>
