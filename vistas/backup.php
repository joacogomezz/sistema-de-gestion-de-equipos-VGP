<?php 
	require_once "../config.php";
	require_once "../seguridad.php";
	requerirAutenticacion();

	if (!isset($_SESSION['iduser']) || (int)$_SESSION['iduser'] !== 1) {
		header('Location: inicio.php');
		exit;
	}

	$titulo_pagina = 'Respaldo de base de datos - Hotel Villa Group';
	$estilos_extra = '<style>
		:root { --villa-dark: #1a1a1a; --villa-darker: #0f0f0f; --villa-light: #2a2a2a; --villa-gold: #c29a4b; --villa-border: #333333; --villa-text: #ffffff; --villa-text-muted: #cccccc; }
		body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, var(--villa-dark) 0%, var(--villa-darker) 100%); min-height: 100vh; color: var(--villa-text); }
		.jumbotron { background: var(--villa-light) !important; border: 2px solid var(--villa-border) !important; border-radius: 15px !important; }
		.jumbotron h1, .jumbotron h2, .jumbotron p { color: var(--villa-gold) !important; }
		.panel { background: var(--villa-light) !important; border: 2px solid var(--villa-border) !important; border-radius: 10px !important; }
		.panel-heading { background: var(--villa-gold) !important; color: #fff !important; border-radius: 8px 8px 0 0 !important; }
		.panel-body { background: var(--villa-light) !important; color: var(--villa-text) !important; }
		.btn-respaldo { background: linear-gradient(135deg, var(--villa-gold) 0%, #a0522d 100%) !important; border: none; color: #1a1a1a; font-weight: bold; padding: 12px 24px; }
		.btn-respaldo:hover { opacity: 0.9; color: #1a1a1a; }
	</style>';
	require_once "menu.php";
?>

	<div class="jumbotron">
		<h1>Respaldo de base de datos</h1>
		<h2>Sistema de Gestión de Equipos - Hotel Villa Group</h2>
		<p>Descargue una copia de seguridad de la base de datos <strong><?php echo defined('DB_NAME') ? DB_NAME : 'hotel_equipos'; ?></strong> (usuarios, equipos, bajas, resguardos, etc.) para guardarla en un lugar seguro.</p>
	</div>

	<div class="row">
		<div class="col-sm-12">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title"><span class="glyphicon glyphicon-save-file"></span> Descargar respaldo</h3>
				</div>
				<div class="panel-body">
					<p class="text-muted">El archivo se descargará en formato SQL. Puede restaurarlo más tarde desde phpMyAdmin (Importar) o desde la consola de MySQL.</p>
					<a href="../procesos/backup/respaldar_bd.php" class="btn btn-respaldo">
						<span class="glyphicon glyphicon-download-alt"></span> Descargar respaldo ahora
					</a>
				</div>
			</div>
		</div>
	</div>

</div><!-- cierre del container del menú -->
</body>
</html>
