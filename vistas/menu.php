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
	$procesosBase = dirname($vistasDir) . '/procesos/';
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

	// ===== Breadcrumbs globales (historial de navegacion) =====
	$archivoActual = basename($scriptName);
	$etiquetasBreadcrumb = array(
		'inicio.php' => 'Inicio',
		'equipos.php' => 'Equipos',
		'departamentos.php' => 'Departamentos',
		'fases.php' => 'Fases',
		'areas.php' => 'Areas',
		'resguardos.php' => 'Resguardos',
		'solicitudAltaEquipos.php' => 'Solicitud Alta de Equipos',
		'altas.php' => 'Altas',
		'bajas.php' => 'Bajas',
		'bajasMasivas.php' => 'Bajas Masivas',
		'reportes.php' => 'Reportes',
		'usuarios.php' => 'Usuarios',
		'backup.php' => 'Respaldo BD',
		'toallas.php' => 'Control Toallas',
		'historialToallas.php' => 'Historial Toallas'
	);
	$breadcrumbActual = isset($etiquetasBreadcrumb[$archivoActual]) ? $etiquetasBreadcrumb[$archivoActual] : 'Seccion';

	// Construir URL absoluta de la pagina actual dentro de /vistas/
	$rutaRelActual = ltrim((string)parse_url($scriptName, PHP_URL_PATH), '/');
	$urlActual = '/' . $rutaRelActual;

	// Historial en sesion (max 6 paginas), sin duplicar la pagina actual consecutivamente
	if (!isset($_SESSION['breadcrumb_history']) || !is_array($_SESSION['breadcrumb_history'])) {
		$_SESSION['breadcrumb_history'] = array();
	}
	$history = $_SESSION['breadcrumb_history'];
	$last = end($history);
	if (!$last || !isset($last['url']) || $last['url'] !== $urlActual) {
		$history[] = array(
			'url' => $urlActual,
			'label' => $breadcrumbActual
		);
		if (count($history) > 6) {
			$history = array_slice($history, -6);
		}
		$_SESSION['breadcrumb_history'] = $history;
	}

	// Tomar hasta 3 elementos del final para mostrar ruta util (anterior(es) + actual)
	$breadcrumbTrail = array_slice($_SESSION['breadcrumb_history'], -3);
	$rol_sesion = isset($_SESSION['rol']) ? strtoupper(trim((string)$_SESSION['rol'])) : 'ADMIN';
	$es_toalleros = ($rol_sesion === 'TOALLEROS');
	$homePage = 'inicio.php';
	?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="description" content="Sistema de Gestión de Equipos del Hotel Villa Group">
	<meta name="theme-color" content="#c29a4b">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="Villa Equipos">
	
	<title><?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) : 'Sistema de Gestión de Equipos - Hotel Villa Group'; ?></title>
	<?php if (!empty($estilos_extra)) { echo $estilos_extra; } ?>
	<?php
		$verCssBootstrap = @filemtime(__DIR__ . '/../librerias/bootstrap/css/bootstrap.min.css') ?: time();
		$verCssTheme = @filemtime(__DIR__ . '/../css/villa-group-theme.css') ?: time();
		$verCssMenu = @filemtime(__DIR__ . '/../css/menu.css') ?: time();
		$verCssAlertify = @filemtime(__DIR__ . '/../librerias/alertifyjs/css/alertify.css') ?: time();
		$verCssAlertifyTheme = @filemtime(__DIR__ . '/../librerias/alertifyjs/css/themes/default.css') ?: time();
		$verJsJquery = @filemtime(__DIR__ . '/../librerias/jquery-3.2.1.min.js') ?: time();
		$verJsBootstrap = @filemtime(__DIR__ . '/../librerias/bootstrap/js/bootstrap.min.js') ?: time();
		$verJsAlertify = @filemtime(__DIR__ . '/../librerias/alertifyjs/alertify.min.js') ?: time();
		$verJsFunciones = @filemtime(__DIR__ . '/../js/funciones.js') ?: time();
		$verJsSegNav = @filemtime(__DIR__ . '/../js/seguridad-navegacion.js') ?: time();
		$verJsPwa = @filemtime(__DIR__ . '/../js/pwa-install.js') ?: time();
	?>
	
	<!-- PWA Manifest -->
	<link rel="manifest" href="../manifest.json">
	
	<!-- Favicon e iconos para iOS (evita 404 favicon.ico) -->
	<link rel="icon" href="../img/apple-touch-icon.png" type="image/png">
	<link rel="apple-touch-icon" href="../img/apple-touch-icon.png">
	
	<link rel="stylesheet" href="../librerias/bootstrap/css/bootstrap.min.css?v=<?php echo (int)$verCssBootstrap; ?>">
	<link rel="stylesheet" href="../css/villa-group-theme.css?v=<?php echo (int)$verCssTheme; ?>">
	<link rel="stylesheet" href="../css/menu.css?v=<?php echo (int)$verCssMenu; ?>">
	<link rel="stylesheet" href="../librerias/alertifyjs/css/alertify.css?v=<?php echo (int)$verCssAlertify; ?>">
	<link rel="stylesheet" href="../librerias/alertifyjs/css/themes/default.css?v=<?php echo (int)$verCssAlertifyTheme; ?>">
	<script src="../librerias/jquery-3.2.1.min.js?v=<?php echo (int)$verJsJquery; ?>"></script>
	<script src="../librerias/bootstrap/js/bootstrap.min.js?v=<?php echo (int)$verJsBootstrap; ?>"></script>
	<script src="../librerias/alertifyjs/alertify.min.js?v=<?php echo (int)$verJsAlertify; ?>"></script>
	<script src="../js/funciones.js?v=<?php echo (int)$verJsFunciones; ?>"></script>
	<script src="../js/seguridad-navegacion.js?v=<?php echo (int)$verJsSegNav; ?>"></script>
	<script src="../js/pwa-install.js?v=<?php echo (int)$verJsPwa; ?>"></script>
	<style>
		.footer-global-villa {
			position: fixed;
			left: 0;
			right: 0;
			bottom: 0;
			z-index: 1030;
			text-align: center;
			background: rgba(15, 15, 15, 0.92);
			color: #d7d7d7;
			border-top: 1px solid #333;
			padding: 6px 10px;
			font-size: 12px;
			letter-spacing: 0.4px;
		}
		body {
			padding-bottom: 30px;
		}
	</style>
</head>
<body>

<!-- Begin Navbar -->
<div id="nav">
  <div class="navbar navbar-inverse navbar-fixed-top" data-spy="affix" data-offset-top="100">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand logo-link logo-mobile" href="<?php echo htmlspecialchars($vistasBase . $homePage); ?>"><span class="logo-wrap"><img class="logo" src="../img/p9anie6p.png" alt="Villa Group"></span></a>
      </div>
      <div id="navbar" class="collapse navbar-collapse">
        <ul class="nav navbar-nav nav-logo-row">
          <li class="logo-nav-item">
            <a class="logo-link logo-desktop" href="<?php echo htmlspecialchars($vistasBase . $homePage); ?>"><span class="logo-wrap"><img class="logo" src="../img/p9anie6p.png" alt="Villa Group"></span></a>
          </li>
        </ul>
        <ul class="nav navbar-nav navbar-right">

          <li <?php echo (basename($_SERVER['PHP_SELF']) == $homePage) ? 'class="active"' : ''; ?>><a href="<?php echo htmlspecialchars($vistasBase . $homePage); ?>"><span class="glyphicon glyphicon-home"></span> Inicio</a></li>

        <?php if (!$es_toalleros): ?>
	        <li class="dropdown">
	          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-cog"></span> Administrar Equipos <span class="caret"></span></a>
	          <ul class="dropdown-menu">
	            <li><a href="<?php echo htmlspecialchars($vistasBase); ?>equipos.php">Equipos</a></li>
	            <li><a href="<?php echo htmlspecialchars($vistasBase); ?>departamentos.php">Departamentos</a></li>
	            <li><a href="<?php echo htmlspecialchars($vistasBase); ?>fases.php">Fases</a></li>
	            <li><a href="<?php echo htmlspecialchars($vistasBase); ?>areas.php">Áreas</a></li>
	            <li><a href="<?php echo htmlspecialchars($vistasBase); ?>resguardos.php">Resguardos</a></li>
	            <li><a href="<?php echo htmlspecialchars($vistasBase); ?>solicitudAltaEquipos.php">Solicitud Alta Equipos</a></li>
	          </ul>
	        </li>

	        <li class="dropdown">
	          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-remove-circle"></span> Bajas <span class="caret"></span></a>
	          <ul class="dropdown-menu">
	            <li><a href="<?php echo htmlspecialchars($vistasBase); ?>bajas.php">Registrar Bajas</a></li>
	            <li><a href="<?php echo htmlspecialchars($vistasBase); ?>bajasMasivas.php">Bajas Masivas</a></li>
	          </ul>
	        </li>
        <?php endif; ?>

        <?php if ($es_toalleros): ?>
        	<li <?php echo (basename($_SERVER['PHP_SELF']) == 'historialToallas.php') ? 'class="active"' : ''; ?>><a href="<?php echo htmlspecialchars($vistasBase); ?>historialToallas.php"><span class="glyphicon glyphicon-list-alt"></span> Historial</a></li>
        <?php endif; ?>

      <?php
      // Solo el Super Administrador (ID = 1) puede crear usuarios y descargar respaldo de BD.
      // Los administradores de villa no ven estas opciones.
      if(isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1):
       ?>
        <li><a href="<?php echo htmlspecialchars($vistasBase); ?>usuarios.php"><span class="glyphicon glyphicon-user"></span> Administrar usuarios</a></li>
        <li><a href="<?php echo htmlspecialchars($vistasBase); ?>backup.php"><span class="glyphicon glyphicon-save-file"></span> Respaldo BD</a></li>
      <?php endif; ?>

        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-user"></span> <?php 
            if(isset($_SESSION['nombre']) && isset($_SESSION['apellido'])){
                echo htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']);
            } else {
                echo htmlspecialchars($_SESSION['usuario']);
            }
            // Super Admin (id=1) no pertenece a ninguna villa; el resto muestra su hotel
            if (isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1) {
                echo ' <span class="text-muted">| Super Administrador</span>';
            } elseif (!empty($_SESSION['nombre_hotel'])) {
                echo ' <span class="text-muted">| ' . htmlspecialchars($_SESSION['nombre_hotel']) . '</span>';
            }
          ?> <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <li><a href="<?php echo htmlspecialchars($urlSalir); ?>" id="link-salir"><span class="glyphicon glyphicon-log-out"></span> Salir</a></li>
          </ul>
        </li>

        </ul>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid" style="margin-top: 100px; padding-left: 10px; padding-right: 10px; max-width: 100%;">
	<?php if (count($breadcrumbTrail) > 1): ?>
	<nav aria-label="breadcrumb" style="margin-bottom: 12px; text-align: center;">
		<ol class="breadcrumb" style="background: #1f1f1f; border: 1px solid #333; margin-bottom: 0; display: inline-flex; align-items: center; justify-content: center;">
			<?php
				$totalCrumbs = count($breadcrumbTrail);
				for ($i = 0; $i < $totalCrumbs; $i++):
					$item = $breadcrumbTrail[$i];
					$esUltimo = ($i === $totalCrumbs - 1);
			?>
				<?php if ($esUltimo): ?>
					<li class="active" aria-current="page"><?php echo htmlspecialchars($item['label']); ?></li>
				<?php else: ?>
					<li><a href="<?php echo htmlspecialchars($item['url']); ?>"><?php echo htmlspecialchars($item['label']); ?></a></li>
				<?php endif; ?>
			<?php endfor; ?>
		</ol>
	</nav>
	<?php endif; ?>

<div class="footer-global-villa">© GESTION 2026 --AXL NAVA-- TODOS LOS DERECHOS RESERVADOS</div>