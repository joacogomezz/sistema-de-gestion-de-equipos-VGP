<?php
	require_once "../seguridad.php";
	requerirAutenticacion();
	require_once "../clases/Conexion.php";
	$ver = function ($ruta) { return (int)(@filemtime(__DIR__ . '/../' . $ruta) ?: time()); };
	$esSuper = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$idHotel = $esSuper ? 0 : (int)($_SESSION['id_hotel'] ?? 0);
	$cx = (new conectar())->conexion();
	mysqli_set_charset($cx, 'utf8mb4');
	$hoteles = array(); $deptos = array();
	$r = mysqli_query($cx, "SELECT id_hotel, nombre FROM hoteles" . ($idHotel ? " WHERE id_hotel = $idHotel" : '') . " ORDER BY nombre");
	while ($r && ($f = mysqli_fetch_assoc($r))) $hoteles[] = array('id' => (int)$f['id_hotel'], 'nombre' => $f['nombre']);
	$r = mysqli_query($cx, "SELECT id_departamento, nombre, id_hotel FROM departamentos" . ($idHotel ? " WHERE id_hotel = $idHotel" : '') . " ORDER BY nombre");
	while ($r && ($f = mysqli_fetch_assoc($r))) $deptos[] = array('id' => (int)$f['id_departamento'], 'nombre' => $f['nombre'], 'idHotel' => (int)$f['id_hotel']);
	$titulo_pagina = 'Reportes · The Villa Group';
	$estilos_extra = '<link rel="stylesheet" href="../css/equipos.css?v=' . $ver('css/equipos.css') . '"><link rel="stylesheet" href="../css/operacion.css?v=' . $ver('css/operacion.css') . '">';
	require_once "menu.php";
?>
<div class="eq op" id="vistaReportes" data-cat="<?php echo htmlspecialchars(json_encode(array('superAdmin' => $esSuper, 'hoteles' => $hoteles, 'deptos' => $deptos, 'usuario' => (int)$_SESSION['iduser']), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>"><p class="cc-muted">Cargando…</p></div>
<div class="mod-velo" id="modal" hidden></div>
<div class="toasts" id="toasts" aria-live="polite"></div>
<script src="../js/cc-ui.js?v=<?php echo $ver('js/cc-ui.js'); ?>"></script>
<script src="../js/reportes.js?v=<?php echo $ver('js/reportes.js'); ?>"></script>
		</main>
	</div>
</div>
</body>
</html>
