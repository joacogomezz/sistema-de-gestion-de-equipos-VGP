<?php
	// Incluir archivo de seguridad
	require_once "../seguridad.php";

	// Requerir autenticación
	requerirAutenticacion();

	// Ruta base de vistas: siempre absoluta desde la raíz para que funcione en Resguardos, Bajas, etc.
	$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
	if ($scriptName === '' || $scriptName[0] !== '/') {
		$uri = str_replace('\\', '/', (string)($_SERVER['REQUEST_URI'] ?? ''));
		$uri = strtok($uri, '?');
		$scriptName = ($uri !== false && $uri !== '' && $uri[0] === '/') ? $uri : '/vistas/resguardos.php';
	}
	$vistasDir = dirname($scriptName);
	$vistasBase = $vistasDir . '/';
	// En la raíz del servidor dirname('/vistas') es '/' (o '\' en Windows): sin rtrim saldría '//procesos/'
	$procesosBase = rtrim(str_replace('\\', '/', dirname($vistasDir)), '/') . '/procesos/';
	// Asegurar rutas absolutas (empiezan por /) para que funcionen desde cualquier página
	if ($vistasBase !== '' && $vistasBase[0] !== '/') {
		$vistasBase = '/' . $vistasBase;
	}
	if ($procesosBase !== '' && $procesosBase[0] !== '/') {
		$procesosBase = '/' . $procesosBase;
	}
	// URL completa para Salir (evita que falle desde Resguardos u otras vistas)
	$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$urlSalir = $protocolo . '://' . $host . $procesosBase . 'salir.php';

	$archivoActual = basename($scriptName);
	$rol_sesion = isset($_SESSION['rol']) ? strtoupper(trim((string)$_SESSION['rol'])) : 'ADMIN';
	$es_toalleros = ($rol_sesion === 'TOALLEROS');
	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$homePage = 'inicio.php';

	// Migas de pan jerárquicas: página => [sección, etiqueta]
	$migasPaginas = array(
		'inicio.php' => array(null, 'Inicio'),
		'equipos.php' => array('Inventario', 'Equipos'),
		'resguardos.php' => array('Inventario', 'Resguardos'),
		'nuevoResguardo.php' => array('Inventario', 'Nuevo resguardo'),
		'solicitudAltaEquipos.php' => array('Inventario', 'Solicitudes de alta'),
		'altas.php' => array('Inventario', 'Altas'),
		'bajas.php' => array('Bajas', 'Baja individual'),
		'bajasMasivas.php' => array('Bajas', 'Bajas masivas'),
		'departamentos.php' => array('Catálogos', 'Departamentos'),
		'areas.php' => array('Catálogos', 'Áreas'),
		'fases.php' => array('Catálogos', 'Fases'),
		'reportes.php' => array(null, 'Reportes'),
		'usuarios.php' => array('Administración', 'Usuarios'),
		'backup.php' => array('Administración', 'Respaldo de base de datos'),
		'toallas.php' => array('Albercas', 'Control de toallas'),
		'historialToallas.php' => array('Albercas', 'Historial de toallas'),
	);
	$migaActual = isset($migasPaginas[$archivoActual]) ? $migasPaginas[$archivoActual] : array(null, 'Sección');

	$nombreUsuario = (isset($_SESSION['nombre']) && isset($_SESSION['apellido']))
		? trim($_SESSION['nombre'] . ' ' . $_SESSION['apellido'])
		: (string)($_SESSION['usuario'] ?? '');
	$partesNombre = preg_split('/\s+/', $nombreUsuario, -1, PREG_SPLIT_NO_EMPTY);
	$iniciales = '';
	foreach (array_slice($partesNombre, 0, 2) as $p) {
		$iniciales .= mb_strtoupper(mb_substr($p, 0, 1));
	}
	$nombreHotelSesion = !empty($_SESSION['nombre_hotel']) ? (string)$_SESSION['nombre_hotel'] : 'Hotel';
	if ($es_super_admin) {
		$rolUsuarioTexto = 'Super Administrador';
		$hotelBarra = 'Todos los hoteles';
	} elseif ($es_toalleros) {
		$rolUsuarioTexto = 'Toallero · ' . $nombreHotelSesion;
		$hotelBarra = $nombreHotelSesion;
	} else {
		$rolUsuarioTexto = 'Administrador · ' . $nombreHotelSesion;
		$hotelBarra = $nombreHotelSesion;
	}

	if (!function_exists('ccItemMenu')) {
		function ccItemMenu($href, $icono, $texto, $activo) {
			echo '<a href="' . htmlspecialchars($href) . '" class="cc-nav__item' . ($activo ? ' cc-nav__item--on' : '') . '"'
				. ($activo ? ' aria-current="page"' : '') . ' title="' . htmlspecialchars($texto) . '">'
				. '<i data-i="' . htmlspecialchars($icono) . '"></i><span>' . htmlspecialchars($texto) . '</span></a>';
		}
	}
	$vb = $vistasBase;
	$a = $archivoActual;
	?>

<!DOCTYPE html>
<html lang="es">
<head>
	<script>
		(function(){
			var t = null;
			try { t = localStorage.getItem('cc-tema'); } catch (e) {}
			if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) t = 'oscuro';
			document.documentElement.setAttribute('data-tema', t === 'oscuro' ? 'oscuro' : 'claro');
		})();
	</script>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="description" content="Sistema de Gestión de Equipos del Hotel Villa Group">
	<meta name="theme-color" content="#B8893A">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="default">
	<meta name="apple-mobile-web-app-title" content="Villa Equipos">

	<title><?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) : 'Sistema de Gestión de Equipos - Hotel Villa Group'; ?></title>
	<?php if (!empty($estilos_extra)) { echo $estilos_extra; } ?>
	<?php
		$verCssBootstrap = @filemtime(__DIR__ . '/../librerias/bootstrap/css/bootstrap.min.css') ?: time();
		$verCssCosta = @filemtime(__DIR__ . '/../css/costa-clara.css') ?: time();
		$verCssAlertify = @filemtime(__DIR__ . '/../librerias/alertifyjs/css/alertify.css') ?: time();
		$verCssAlertifyTheme = @filemtime(__DIR__ . '/../librerias/alertifyjs/css/themes/default.css') ?: time();
		$verJsJquery = @filemtime(__DIR__ . '/../librerias/jquery-3.2.1.min.js') ?: time();
		$verJsBootstrap = @filemtime(__DIR__ . '/../librerias/bootstrap/js/bootstrap.min.js') ?: time();
		$verJsAlertify = @filemtime(__DIR__ . '/../librerias/alertifyjs/alertify.min.js') ?: time();
		$verJsFunciones = @filemtime(__DIR__ . '/../js/funciones.js') ?: time();
		$verJsSegNav = @filemtime(__DIR__ . '/../js/seguridad-navegacion.js') ?: time();
		$verJsPwa = @filemtime(__DIR__ . '/../js/pwa-install.js') ?: time();
		$verJsCosta = @filemtime(__DIR__ . '/../js/costa-clara.js') ?: time();
	?>

	<!-- PWA Manifest -->
	<link rel="manifest" href="../manifest.json">

	<!-- Favicon e iconos para iOS (evita 404 favicon.ico) -->
	<link rel="icon" href="../img/apple-touch-icon.png" type="image/png">
	<link rel="apple-touch-icon" href="../img/apple-touch-icon.png">

	<link rel="stylesheet" href="../librerias/bootstrap/css/bootstrap.min.css?v=<?php echo (int)$verCssBootstrap; ?>">
	<link rel="stylesheet" href="../librerias/alertifyjs/css/alertify.css?v=<?php echo (int)$verCssAlertify; ?>">
	<link rel="stylesheet" href="../librerias/alertifyjs/css/themes/default.css?v=<?php echo (int)$verCssAlertifyTheme; ?>">
	<link rel="stylesheet" href="../css/costa-clara.css?v=<?php echo (int)$verCssCosta; ?>">
	<script src="../librerias/jquery-3.2.1.min.js?v=<?php echo (int)$verJsJquery; ?>"></script>
	<script src="../librerias/bootstrap/js/bootstrap.min.js?v=<?php echo (int)$verJsBootstrap; ?>"></script>
	<script src="../librerias/alertifyjs/alertify.min.js?v=<?php echo (int)$verJsAlertify; ?>"></script>
	<script src="../js/funciones.js?v=<?php echo (int)$verJsFunciones; ?>"></script>
	<script src="../js/seguridad-navegacion.js?v=<?php echo (int)$verJsSegNav; ?>"></script>
	<script src="../js/pwa-install.js?v=<?php echo (int)$verJsPwa; ?>"></script>
	<script src="../js/costa-clara.js?v=<?php echo (int)$verJsCosta; ?>"></script>
</head>
<body>

<div class="cc-app" id="ccApp">
	<div class="cc-velo-menu" data-cc-cerrar-menu></div>
	<aside class="cc-side" aria-label="Menú principal">
		<a class="cc-marca" href="<?php echo htmlspecialchars($vb . $homePage); ?>">
			<span class="cc-marca__logo"><img src="../img/p9anie6p.png" alt="The Villa Group"></span>
			<span class="cc-marca__txt"><span class="cc-marca__nombre">Gestión de Equipos</span><span class="cc-marca__sub">The Villa Group</span></span>
		</a>
		<nav class="cc-nav">
			<?php ccItemMenu($vb . $homePage, $es_toalleros ? 'toallas' : 'inicio', $es_toalleros ? 'Control de toallas' : 'Inicio', $a === $homePage); ?>

			<?php if ($es_toalleros): ?>
				<?php ccItemMenu($vb . 'historialToallas.php', 'reloj', 'Historial', $a === 'historialToallas.php'); ?>
			<?php else: ?>
				<div class="cc-nav__sec"><span>Inventario</span></div>
				<?php ccItemMenu($vb . 'equipos.php', 'equipos', 'Equipos', $a === 'equipos.php'); ?>
				<?php ccItemMenu($vb . 'resguardos.php', 'resguardos', 'Resguardos', $a === 'resguardos.php' || $a === 'nuevoResguardo.php'); ?>
				<?php ccItemMenu($vb . 'solicitudAltaEquipos.php', 'solicitudes', 'Solicitudes de alta', $a === 'solicitudAltaEquipos.php'); ?>

				<div class="cc-nav__sec"><span>Bajas</span></div>
				<?php ccItemMenu($vb . 'bajas.php', 'baja', 'Baja individual', $a === 'bajas.php'); ?>
				<?php ccItemMenu($vb . 'bajasMasivas.php', 'bajas-masivas', 'Bajas masivas', $a === 'bajasMasivas.php'); ?>

				<div class="cc-nav__sec"><span>Catálogos</span></div>
				<?php ccItemMenu($vb . 'departamentos.php', 'departamentos', 'Departamentos', $a === 'departamentos.php'); ?>
				<?php ccItemMenu($vb . 'areas.php', 'areas', 'Áreas', $a === 'areas.php'); ?>
				<?php ccItemMenu($vb . 'fases.php', 'fases', 'Fases', $a === 'fases.php'); ?>
			<?php endif; ?>

			<?php if ($es_super_admin): ?>
				<div class="cc-nav__sec"><span>Administración</span></div>
				<?php ccItemMenu($vb . 'usuarios.php', 'usuarios', 'Usuarios', $a === 'usuarios.php'); ?>
				<?php ccItemMenu($vb . 'backup.php', 'respaldo', 'Respaldo de base de datos', $a === 'backup.php'); ?>
			<?php endif; ?>
		</nav>
		<div class="cc-side__pie cc-nav">
			<button type="button" class="cc-nav__item" data-cc-tema title="Cambiar tema"><i data-i="luna"></i><span data-cc-tema-texto>Modo oscuro</span></button>
			<button type="button" class="cc-nav__item cc-solo-escritorio" data-cc-colapsar title="Colapsar menú"><i data-i="colapsar"></i><span>Colapsar menú</span></button>
			<div class="cc-side__credito">© Gestión 2026 · Axl Nava</div>
		</div>
	</aside>

	<div class="cc-main">
		<header class="cc-top">
			<button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono cc-solo-movil" data-cc-abrir-menu aria-label="Abrir menú"><i data-i="menu"></i></button>
			<span class="cc-hotel" title="Hotel de la sesión"><span class="cc-hotel__punto"><i data-i="hotel"></i></span><span class="cc-solo-escritorio"><?php echo htmlspecialchars($hotelBarra); ?></span></span>
			<?php if (!$es_toalleros): ?>
			<form class="cc-buscador cc-solo-escritorio" action="<?php echo htmlspecialchars($vb . 'equipos.php'); ?>" method="get" role="search">
				<i data-i="buscar"></i>
				<input class="cc-input" type="search" name="buscar" placeholder="Buscar equipo por código o número de serie" aria-label="Buscar equipo">
			</form>
			<?php endif; ?>
			<div style="flex:1"></div>
			<div class="dropdown">
				<a href="#" class="cc-usuario" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" style="text-decoration:none;color:inherit">
					<span class="cc-avatar"><?php echo htmlspecialchars($iniciales !== '' ? $iniciales : 'U'); ?></span>
					<div><div class="cc-usuario__nombre"><?php echo htmlspecialchars($nombreUsuario); ?></div><div class="cc-usuario__rol"><?php echo htmlspecialchars($rolUsuarioTexto); ?></div></div>
					<i data-i="abajo" class="cc-muted"></i>
				</a>
				<ul class="dropdown-menu">
					<li><a href="#" data-cc-tema><i data-i="luna"></i><span data-cc-tema-texto>Modo oscuro</span></a></li>
					<li role="separator" class="divider"></li>
					<li><a href="<?php echo htmlspecialchars($urlSalir); ?>" id="link-salir" style="color:var(--peligro)!important"><i data-i="salir"></i>Cerrar sesión</a></li>
				</ul>
			</div>
		</header>
		<nav class="cc-migas" aria-label="Migas de pan">
			<i data-i="inicio"></i>
			<?php if ($archivoActual === $homePage): ?>
				<b><?php echo htmlspecialchars($migaActual[1]); ?></b>
			<?php else: ?>
				<a href="<?php echo htmlspecialchars($vb . $homePage); ?>" style="color:inherit;font-weight:400">Inicio</a>
				<?php if ($migaActual[0] !== null): ?><i data-i="derecha"></i><span><?php echo htmlspecialchars($migaActual[0]); ?></span><?php endif; ?>
				<i data-i="derecha"></i><b><?php echo htmlspecialchars($migaActual[1]); ?></b>
			<?php endif; ?>
		</nav>
		<main class="cc-contenido">
