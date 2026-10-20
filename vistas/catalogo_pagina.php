<?php
	// Plantilla común de Departamentos, Áreas y Fases. La página que la incluye define $tipoCatalogo.
	require_once "../seguridad.php";
	requerirAutenticacion();
	$ver = function ($ruta) { return (int)(@filemtime(__DIR__ . '/../' . $ruta) ?: time()); };
	$titulo_pagina = ucfirst($tipoCatalogo === 'areas' ? 'áreas' : $tipoCatalogo) . ' · The Villa Group';
	$estilos_extra = '<link rel="stylesheet" href="../css/equipos.css?v=' . $ver('css/equipos.css') . '"><link rel="stylesheet" href="../css/operacion.css?v=' . $ver('css/operacion.css') . '">';
	require_once "menu.php";
?>
<div class="eq op" id="vistaCatalogo" data-tipo="<?php echo htmlspecialchars($tipoCatalogo); ?>"><p class="cc-muted">Cargando…</p></div>
<div class="mod-velo" id="modal" hidden></div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script src="../js/cc-ui.js?v=<?php echo $ver('js/cc-ui.js'); ?>"></script>
<script src="../js/catalogo.js?v=<?php echo $ver('js/catalogo.js'); ?>"></script>
		</main>
	</div>
</div>
</body>
</html>
