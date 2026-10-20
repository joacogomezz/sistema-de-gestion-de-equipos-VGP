<?php
	require_once "../seguridad.php";
	requerirAutenticacion();
	$ver = function ($ruta) { return (int)(@filemtime(__DIR__ . '/../' . $ruta) ?: time()); };
	$cfg = array('superAdmin' => isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1, 'usuario' => trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? '')), 'max' => 10);
	$titulo_pagina = 'Nuevo resguardo · The Villa Group';
	$estilos_extra = '<link rel="stylesheet" href="../css/equipos.css?v=' . $ver('css/equipos.css') . '"><link rel="stylesheet" href="../css/operacion.css?v=' . $ver('css/operacion.css') . '">';
	require_once "menu.php";
?>
<div class="eq op" id="vistaNuevoRes" data-cfg="<?php echo htmlspecialchars(json_encode($cfg, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"><p class="cc-muted">Cargando…</p></div>
<div class="mod-velo" id="modal" hidden></div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script src="../js/cc-ui.js?v=<?php echo $ver('js/cc-ui.js'); ?>"></script>
<script src="../js/nuevoResguardo.js?v=<?php echo $ver('js/nuevoResguardo.js'); ?>"></script>
		</main>
	</div>
</div>
</body>
</html>
