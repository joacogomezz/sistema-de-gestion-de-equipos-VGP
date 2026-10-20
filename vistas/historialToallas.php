<?php
require_once "../seguridad.php";
requerirAutenticacion();
require_once "../clases/Toallas.php";

$rol = isset($_SESSION['rol']) ? strtoupper(trim((string)$_SESSION['rol'])) : 'ADMIN';
if ($rol !== 'TOALLEROS') {
	header('Location: inicio.php?mensaje=acceso_denegado');
	exit;
}

$ver = function ($ruta) { return (int)(@filemtime(__DIR__ . '/../' . $ruta) ?: time()); };
$titulo_pagina = 'Historial de toallas · The Villa Group';
$estilos_extra = '<link rel="stylesheet" href="../css/equipos.css?v=' . $ver('css/equipos.css') . '"><link rel="stylesheet" href="../css/operacion.css?v=' . $ver('css/operacion.css') . '">';
require_once "menu.php";
?>
<div class="eq op" id="vistaHistToallas"><p class="cc-muted">Cargando…</p></div>
<div class="mod-velo" id="modal" hidden></div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script src="../js/cc-ui.js?v=<?php echo $ver('js/cc-ui.js'); ?>"></script>
<script src="../js/historialToallas.js?v=<?php echo $ver('js/historialToallas.js'); ?>"></script>
		</main>
	</div>
</div>
</body>
</html>
