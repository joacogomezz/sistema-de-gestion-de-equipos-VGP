<?php
require_once "../seguridad.php";
requerirAutenticacion();
$titulo_pagina = 'Solicitud para dar de Alta Equipos - Hotel Villa Group';
$estilos_extra = '<style>
	body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%); min-height: 100vh; color: #fff; }
	.jumbotron { background: #2a2a2a !important; border: 2px solid #333 !important; border-radius: 15px !important; padding: 28px 32px !important; margin-bottom: 28px !important; }
	.jumbotron h1, .jumbotron p { color: #c29a4b !important; }
	.jumbotron p { margin-bottom: 0; line-height: 1.5; }
	.panel { background: #2a2a2a !important; border: 2px solid #333 !important; border-radius: 10px !important; }
	.panel-heading { background: #c29a4b !important; color: #fff !important; border-radius: 8px 8px 0 0 !important; padding: 14px 18px !important; }
	.panel-body { background: #2a2a2a !important; color: #fff !important; padding: 24px 28px !important; }
	.table { color: #fff; }
	.table-bordered th, .table-bordered td { border-color: #444 !important; }
	#contenido .form-control { border-radius: 6px; }
</style>';
require_once "menu.php";
?>

	<div class="jumbotron">
		<h1>Solicitud para dar de Alta Equipos de Operación</h1>
		<p>Genere el documento PDF después de haber dado de alta los equipos en el sistema. Busque cada equipo por su código primario y complete los datos de ubicación y clasificación.</p>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading"><h3 class="panel-title">Nueva solicitud</h3></div>
		<div class="panel-body">
			<div id="contenido"></div>
		</div>
	</div>
<script>
$(document).ready(function(){ $('#contenido').load('solicitudAltaEquipos/formularioSolicitudAlta.php'); });
</script>
</div></body></html>
