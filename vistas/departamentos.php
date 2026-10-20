<?php 
	// Incluir archivo de seguridad
	require_once "../seguridad.php";
	
	// Requerir autenticación
	requerirAutenticacion();
	require_once "../clases/Hoteles.php";
	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$hotel_ctx = ($es_super_admin && isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : null;
	$objHoteles = new Hoteles();
	$listaHoteles = $es_super_admin ? $objHoteles->obtenerTodos() : false;
	?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Gestión de Departamentos - Hotel Villa Group</title>
	<style>
		:root {
			--villa-dark: #1a1a1a;
			--villa-darker: #0f0f0f;
			--villa-light: #2a2a2a;
			--villa-white: #ffffff;
			--villa-gold: #c29a4b;
			--villa-gold-hover: #d4a85c;
			--villa-orange: #a0522d;
			--villa-orange-hover: #b05a35;
			--villa-text: #ffffff;
			--villa-text-muted: #cccccc;
			--villa-border: #333333;
		}

		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			background: linear-gradient(135deg, var(--villa-dark) 0%, var(--villa-darker) 100%);
			min-height: 100vh;
			color: var(--villa-text);
		}

		.jumbotron {
			background: var(--villa-light) !important;
			border: 2px solid var(--villa-border) !important;
			border-radius: 15px !important;
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3) !important;
		}

		.jumbotron h1, .jumbotron h2, .jumbotron p {
			color: var(--villa-gold) !important;
		}

		.panel {
			background: var(--villa-light) !important;
			border: 2px solid var(--villa-border) !important;
			border-radius: 10px !important;
			box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3) !important;
		}

		.panel-heading {
			background: var(--villa-gold) !important;
			border-color: var(--villa-border) !important;
			color: var(--villa-white) !important;
			border-radius: 8px 8px 0 0 !important;
		}

		.panel-body {
			background: var(--villa-light) !important;
			color: var(--villa-text) !important;
		}

		.btn-primary {
			background: var(--villa-gold) !important;
			border: 2px solid var(--villa-gold) !important;
			color: var(--villa-white) !important;
			border-radius: 8px !important;
			font-weight: bold !important;
		}

		.btn-primary:hover {
			background: var(--villa-gold-hover) !important;
			border-color: var(--villa-gold-hover) !important;
			transform: translateY(-2px) !important;
			box-shadow: 0 5px 15px rgba(194, 154, 75, 0.4) !important;
		}

		.btn-success {
			background: var(--villa-orange) !important;
			border: 2px solid var(--villa-orange) !important;
			color: var(--villa-white) !important;
			border-radius: 8px !important;
			font-weight: bold !important;
		}

		.btn-success:hover {
			background: var(--villa-orange-hover) !important;
			border-color: var(--villa-orange-hover) !important;
			transform: translateY(-2px) !important;
			box-shadow: 0 5px 15px rgba(160, 82, 45, 0.4) !important;
		}
	</style>
</head>
<body>
	<?php require_once "menu.php"; ?>

	<div class="container">
		<div class="jumbotron">
			<h1>Gestión de Departamentos</h1>
			<h2>Sistema de Gestión de Equipos - Hotel Villa Group</h2>
			<p>Administra los departamentos del sistema.</p>
		</div>

		<div class="row">
			<div class="col-sm-12">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Departamentos del Sistema</h3>
					</div>
					<div class="panel-body">
						<?php if ($es_super_admin && $listaHoteles): ?>
						<div class="row" style="margin-bottom: 15px;">
							<div class="col-sm-12">
								<label style="margin-right:8px;">Ver departamentos de:</label>
								<div class="btn-group" role="group">
									<?php while ($h = mysqli_fetch_row($listaHoteles)): $hid = (int)$h[0]; ?>
										<button type="button" class="btn <?php echo ($hotel_ctx === $hid) ? 'btn-warning' : 'btn-default'; ?> btn-sm" onclick="seleccionarHotelDepartamentos(<?php echo $hid; ?>)">
											<?php echo htmlspecialchars($h[1]); ?>
										</button>
									<?php endwhile; ?>
								</div>
							</div>
						</div>
						<?php endif; ?>
						<button type="button" class="btn btn-primary" id="agregarDepartamentoBtn">
							<span class="glyphicon glyphicon-plus"></span> Agregar Departamento
						</button>
						<button type="button" class="btn btn-success" id="verDepartamentosBtn">
							<span class="glyphicon glyphicon-list"></span> Ver Departamentos
						</button>
						<br><br>
						<div id="agregarDepartamento"></div>
						<div id="verDepartamentos"></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		var esSuperAdminDepartamentos = <?php echo $es_super_admin ? 'true' : 'false'; ?>;
		var idHotelDepartamentosCtx = <?php echo $hotel_ctx !== null ? (int)$hotel_ctx : 'null'; ?>;
		function queryHotelDepartamentos() {
			return idHotelDepartamentosCtx ? ('?id_hotel=' + parseInt(idHotelDepartamentosCtx, 10)) : '';
		}
		function seleccionarHotelDepartamentos(idHotel) {
			$('#agregarDepartamento, #verDepartamentos').hide();
			window.location.href = 'departamentos.php?id_hotel=' + parseInt(idHotel, 10);
		}
		$(document).ready(function(){
			$('#agregarDepartamentoBtn').click(function(){
				if (esSuperAdminDepartamentos && !idHotelDepartamentosCtx) { alertify.alert('Selecciona primero un hotel para gestionar departamentos.'); return; }
				esconderSeccionDepartamentos();
				$('#agregarDepartamento').load('departamentos/agregarDepartamento.php' + queryHotelDepartamentos());
				$('#agregarDepartamento').show();
			});
			$('#verDepartamentosBtn').click(function(){
				if (esSuperAdminDepartamentos && !idHotelDepartamentosCtx) { alertify.alert('Selecciona primero un hotel para ver departamentos.'); return; }
				esconderSeccionDepartamentos();
				$('#verDepartamentos').load('departamentos/tablaDepartamentos.php' + queryHotelDepartamentos());
				$('#verDepartamentos').show();
			});
		});

		function esconderSeccionDepartamentos(){
			$('#agregarDepartamento').hide();
			$('#verDepartamentos').hide();
		}

		// Funciones para editar y eliminar departamentos
		function agregaDatoDepartamento(idDepartamento, nombre, descripcion){
			$('#idDepartamento').val(idDepartamento);
			$('#nombreDepartamentoU').val(nombre);
			$('#descripcionDepartamentoU').val(descripcion);
		}

		function actualizaDepartamento(){
			datos=$('#frmUpdateDepartamento').serialize();
			$.ajax({
				type:"POST",
				data:datos,
				url:"../procesos/departamentos/actualizaDepartamento.php",
				success:function(r){
					if(r==1){
						var $modal = $('#abremodalUpdateDepartamento');
						$modal.one('hidden.bs.modal', function () {
							alertify.success("Departamento actualizado con éxito");
							$('#verDepartamentos').load('departamentos/tablaDepartamentos.php' + queryHotelDepartamentos());
						});
						$modal.modal('hide');
					}else{
						alertify.error("Error al actualizar el departamento");
					}
				}
			});
		}

		function eliminarDepartamento(idDepartamento){
			alertify.confirm('¿Desea eliminar este departamento?', function(){
				$.ajax({
					type:"POST",
					data:"idDepartamento=" + idDepartamento,
					url:"../procesos/departamentos/eliminarDepartamento.php",
					success:function(r){
						if(r==1){
							alertify.success("Departamento eliminado con éxito");
							$('#verDepartamentos').load('departamentos/tablaDepartamentos.php' + queryHotelDepartamentos());
						}else{
							alertify.error("Error al eliminar el departamento");
						}
					}
				});
			}, function(){
				alertify.error('Cancelado');
			});
		}
	</script>
</body>
</html>

<?php 
	// El archivo de seguridad ya maneja la redirección automáticamente
?>
