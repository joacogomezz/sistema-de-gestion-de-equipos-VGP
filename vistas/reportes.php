<?php 
	// Incluir archivo de seguridad
	require_once "../seguridad.php";
	
	// Requerir autenticación
	requerirAutenticacion();
?>
<!DOCTYPE html>
<html>
<head>
	<title>Reportes del Sistema</title>
	<?php require_once "menu.php"; ?>
</head>
<body>

	<div class="container">
		 <h1>Reportes del Sistema de Gestión de Equipos</h1>
		 <div class="row">
		 	<div class="col-sm-12">
		 		<div class="panel panel-primary">
		 			<div class="panel-heading">
		 				<h3 class="panel-title">Reportes Disponibles</h3>
		 			</div>
		 			<div class="panel-body">
		 				<div class="row">
		 					<div class="col-sm-4">
		 						<div class="panel panel-info">
		 							<div class="panel-heading">
		 								<h4 class="panel-title">Reporte de Equipos</h4>
		 							</div>
		 							<div class="panel-body">
		 								<p>Genera un reporte completo de todos los equipos registrados en el sistema.</p>
		 								<a href="reportes/reporteEquipos.php" class="btn btn-info btn-block" target="_blank">
		 									<span class="glyphicon glyphicon-list"></span> Generar Reporte
		 								</a>
		 							</div>
		 						</div>
		 					</div>
		 					<div class="col-sm-4">
		 						<div class="panel panel-success">
		 							<div class="panel-heading">
		 								<h4 class="panel-title">Reporte de Altas</h4>
		 							</div>
		 							<div class="panel-body">
		 								<p>Reporte detallado de todas las altas de equipos registradas.</p>
		 								<a href="reportes/reporteAltas.php" class="btn btn-success btn-block" target="_blank">
		 									<span class="glyphicon glyphicon-plus"></span> Generar Reporte
		 								</a>
		 							</div>
		 						</div>
		 					</div>
		 										<div class="col-sm-4">
						<div class="panel panel-warning">
							<div class="panel-heading">
								<h4 class="panel-title">Reporte de Bajas Individuales</h4>
							</div>
							<div class="panel-body">
								<p>Reporte de todas las bajas individuales de equipos.</p>
								<a href="reportes/reporteBajasIndividuales.php" class="btn btn-warning btn-block" target="_blank">
									<span class="glyphicon glyphicon-minus"></span> Generar Reporte
								</a>
							</div>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="panel panel-info">
							<div class="panel-heading">
								<h4 class="panel-title">Informe Técnico Individual</h4>
							</div>
							<div class="panel-body">
								<p>Informe técnico de baja individual de equipos.</p>
								<p><small>Nota: Este informe se genera desde la tabla de bajas individuales</small></p>
							</div>
						</div>
					</div>
		 				</div>
		 				<div class="row">
		 										<div class="col-sm-4">
						<div class="panel panel-danger">
							<div class="panel-heading">
								<h4 class="panel-title">Reporte de Bajas Masivas</h4>
							</div>
							<div class="panel-body">
								<p>Reporte de todas las bajas masivas de equipos registradas.</p>
								<a href="reportes/reporteBajasMasivas.php" class="btn btn-danger btn-block" target="_blank">
									<span class="glyphicon glyphicon-remove"></span> Generar Reporte
								</a>
							</div>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="panel panel-info">
							<div class="panel-heading">
								<h4 class="panel-title">Informe Técnico Masivo</h4>
							</div>
							<div class="panel-body">
								<p>Informe técnico de baja masiva de equipos.</p>
								<p><small>Nota: Este informe se genera desde la tabla de bajas masivas</small></p>
							</div>
						</div>
					</div>
		 					<div class="col-sm-4">
		 						<div class="panel panel-default">
		 							<div class="panel-heading">
		 								<h4 class="panel-title">Reporte por Departamento</h4>
		 							</div>
		 							<div class="panel-body">
		 								<p>Reporte de equipos agrupados por departamento.</p>
		 								<a href="reportes/reportePorDepartamento.php" class="btn btn-default btn-block" target="_blank">
		 									<span class="glyphicon glyphicon-building"></span> Generar Reporte
		 								</a>
		 							</div>
		 						</div>
		 					</div>
		 				</div>
		 			</div>
		 		</div>
		 	</div>
		 </div>
	</div>
</body>
</html>

<?php 
	// El archivo de seguridad ya maneja la redirección automáticamente
?>
