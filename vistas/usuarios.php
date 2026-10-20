<?php
	require_once "../seguridad.php";
	requerirAutenticacion();
	if (!isset($_SESSION['iduser']) || (int)$_SESSION['iduser'] !== 1) {
		header('Location: inicio.php');
		exit;
	}
	$ver = function ($ruta) { return (int)(@filemtime(__DIR__ . '/../' . $ruta) ?: time()); };
	$titulo_pagina = 'Usuarios · The Villa Group';
	$estilos_extra = '<link rel="stylesheet" href="../css/equipos.css?v=' . $ver('css/equipos.css') . '"><link rel="stylesheet" href="../css/operacion.css?v=' . $ver('css/operacion.css') . '">';
	require_once "menu.php";
?>
<div class="eq op" id="vistaUsuarios"><p class="cc-muted">Cargando…</p></div>
<div class="mod-velo" id="modal" hidden></div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script src="../js/cc-ui.js?v=<?php echo $ver('js/cc-ui.js'); ?>"></script>
<script src="../js/usuarios.js?v=<?php echo $ver('js/usuarios.js'); ?>"></script>
		</main>
	</div>
</div>
</body>
</html>
