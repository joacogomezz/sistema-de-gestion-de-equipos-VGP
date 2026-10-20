<?php 
	// Incluir archivo de seguridad
	require_once "../seguridad.php";
	
	// Requerir autenticación
	requerirAutenticacion();
	?>
	
	<?php 
	// Verificar que sea el usuario administrador (ID = 1)
	if($_SESSION['iduser'] != 1){
		header('Location: ../index.php?mensaje=acceso_denegado');
		exit();
	}
	?>
	
	<?php
		$titulo_pagina = 'Administrar Usuarios - Hotel Villa Group';
		require_once "menu.php";
	?>
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

			.form-control, input[type="text"], input[type="password"], input[type="email"] {
				background: var(--villa-dark) !important;
				border: 2px solid var(--villa-border) !important;
				color: var(--villa-text) !important;
				border-radius: 8px !important;
			}

			.form-control:focus, input[type="text"]:focus, input[type="password"]:focus, input[type="email"]:focus {
				background: var(--villa-dark) !important;
				border-color: var(--villa-gold) !important;
				box-shadow: 0 0 10px rgba(194, 154, 75, 0.3) !important;
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

			.btn-info {
				background: var(--villa-orange) !important;
				border: 2px solid var(--villa-orange) !important;
				color: var(--villa-white) !important;
				border-radius: 8px !important;
				font-weight: bold !important;
			}

			.btn-info:hover {
				background: var(--villa-orange-hover) !important;
				border-color: var(--villa-orange-hover) !important;
				transform: translateY(-2px) !important;
				box-shadow: 0 5px 15px rgba(160, 82, 45, 0.4) !important;
			}

			.btn-warning {
				background: var(--villa-gold) !important;
				border: 2px solid var(--villa-gold) !important;
				color: var(--villa-white) !important;
				border-radius: 8px !important;
				font-weight: bold !important;
			}

			.btn-warning:hover {
				background: var(--villa-gold-hover) !important;
				border-color: var(--villa-gold-hover) !important;
				transform: translateY(-2px) !important;
				box-shadow: 0 5px 15px rgba(194, 154, 75, 0.4) !important;
			}

			label {
				color: var(--villa-gold) !important;
				font-weight: bold !important;
				margin-bottom: 8px !important;
			}

			.modal-content {
				background: var(--villa-light) !important;
				border: 2px solid var(--villa-border) !important;
				border-radius: 15px !important;
			}

			.modal-header {
				background: var(--villa-gold) !important;
				border-color: var(--villa-border) !important;
				border-radius: 13px 13px 0 0 !important;
			}

			.modal-title {
				color: var(--villa-white) !important;
				font-weight: bold !important;
			}

			.modal-body {
				background: var(--villa-light) !important;
				color: var(--villa-text) !important;
			}

			.modal-footer {
				background: var(--villa-light) !important;
				border-color: var(--villa-border) !important;
			}
		</style>

		<div class="container">
			<div class="jumbotron">
				<h1>Administrar Usuarios</h1>
				<h2>Sistema de Gestión de Equipos - Hotel Villa Group</h2>
				<p>Gestiona los usuarios del sistema, crea nuevas cuentas y administra permisos de acceso.</p>
			</div>

			<div class="row">
				<div class="col-sm-4">
					<div class="panel panel-primary">
						<div class="panel-heading">
							<h3 class="panel-title">Registrar Nuevo Usuario</h3>
						</div>
						<div class="panel-body">
							<form id="frmRegistro">
								<div class="form-group">
									<label>Nombre</label>
									<input type="text" class="form-control" name="nombre" id="nombre" placeholder="Ingresa el nombre">
								</div>
								<div class="form-group">
									<label>Apellido</label>
									<input type="text" class="form-control" name="apellido" id="apellido" placeholder="Ingresa el apellido">
								</div>
								<div class="form-group">
									<label>Usuario</label>
									<input type="text" class="form-control" name="usuario" id="usuario" placeholder="Ingresa el usuario">
								</div>
								<div class="form-group">
									<label>Password</label>
									<input type="password" class="form-control" name="password" id="password" placeholder="Ingresa la contraseña">
								</div>
								<div class="form-group">
									<label>Hotel / Villa</label>
									<select class="form-control" name="id_hotel" id="id_hotel" required>
										<option value="">Selecciona el hotel</option>
										<?php
										require_once "../clases/Hoteles.php";
										$objH = new Hoteles();
										$resH = $objH->obtenerTodos();
										if ($resH) {
											while ($row = mysqli_fetch_row($resH)) {
												echo '<option value="' . (int)$row[0] . '">' . htmlspecialchars($row[1]) . '</option>';
											}
										}
										?>
									</select>
									<small class="text-muted">Asigna el usuario a la villa donde trabajará</small>
								</div>
								<div class="form-group">
									<label>Rol</label>
									<select class="form-control" name="rol" id="rolUsuario" required>
										<option value="ADMIN">Administrador de Sistema</option>
										<option value="TOALLEROS">Toalleros (solo Villa del Arco)</option>
									</select>
									<small class="text-muted">El rol Toalleros queda restringido al hotel Villa del Arco y al módulo de toallas.</small>
								</div>
								<button type="button" class="btn btn-primary btn-block" id="registro">
									<i class="glyphicon glyphicon-user"></i> Registrar Usuario
								</button>
							</form>
						</div>
					</div>
				</div>
				<div class="col-sm-8">
					<div class="panel panel-info">
						<div class="panel-heading">
							<h3 class="panel-title">Usuarios Registrados</h3>
						</div>
						<div class="panel-body">
							<div id="tablaUsuariosLoad"></div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Modal -->
		<div class="modal fade" id="actualizaUsuarioModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
			<div class="modal-dialog modal-sm" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel">Actualiza Usuario</h4>
					</div>
					<div class="modal-body">
						<form id="frmRegistroU">
							<input type="text" hidden="" id="idUsuario" name="idUsuario">
							<div class="form-group">
								<label>Nombre</label>
								<input type="text" class="form-control" name="nombreU" id="nombreU" placeholder="Ingresa el nombre">
							</div>
							<div class="form-group">
								<label>Apellido</label>
								<input type="text" class="form-control" name="apellidoU" id="apellidoU" placeholder="Ingresa el apellido">
							</div>
							<div class="form-group">
								<label>Usuario</label>
								<input type="text" class="form-control" name="usuarioU" id="usuarioU" placeholder="Ingresa el usuario">
							</div>
							<div class="form-group">
								<label>Nueva Password (opcional)</label>
								<input type="password" class="form-control" name="passwordU" id="passwordU" placeholder="Deja vacio para no cambiarla">
								<small class="text-muted">Utiliza este campo cuando necesites restablecer la contraseña del usuario.</small>
							</div>
						</form>
					</div>
					<div class="modal-footer">
						<button id="btnActualizaUsuario" type="button" class="btn btn-warning" data-dismiss="modal">
							<i class="glyphicon glyphicon-edit"></i> Actualiza Usuario
						</button>
					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			function agregaDatosUsuario(idusuario){
				$.ajax({
					type:"POST",
					data:"idusuario=" + idusuario,
					url:"../procesos/usuarios/obtenDatosUsuario.php",
					success:function(r){
						dato=jQuery.parseJSON(r);
						$('#idUsuario').val(dato['id_usuario']);
						$('#nombreU').val(dato['nombre']);
						$('#apellidoU').val(dato['apellido']);
						$('#usuarioU').val(dato['email']);
						$('#passwordU').val('');
					}
				});
			}

			function eliminarUsuario(idusuario){
				alertify.confirm('¿Desea eliminar este usuario?', function(){ 
					$.ajax({
						type:"POST",
						data:"idusuario=" + idusuario,
						url:"../procesos/usuarios/eliminarUsuario.php",
						success:function(r){
							if(r==1){
								$('#tablaUsuariosLoad').load('usuarios/tablaUsuarios.php');
								alertify.success("Eliminado con exito!!");
							}else if(r === 'SUPER_ADMIN_PROTEGIDO'){
								alertify.error("El usuario super admin no se puede eliminar.");
							}else if(r === 'NO_AUTO_ELIMINAR'){
								alertify.error("No puedes eliminar tu propio usuario.");
							}else if(r === 'SIN_PERMISO'){
								alertify.error("No tienes permiso para eliminar usuarios.");
							}else{
								alertify.error("No se pudo eliminar :(");
							}
						}
					});
				}, function(){ 
					alertify.error('Cancelo !')
				});
			}
		</script>

		<script type="text/javascript">
			$(document).ready(function(){
				$('#btnActualizaUsuario').click(function(){
					datos=$('#frmRegistroU').serialize();
					$.ajax({
						type:"POST",
						data:datos,
						url:"../procesos/usuarios/actualizaUsuario.php",
						success:function(r){
							if(r==1){
								$('#tablaUsuariosLoad').load('usuarios/tablaUsuarios.php');
								alertify.success("Actualizado con exito :D");
							}else{
								alertify.error("No se pudo actualizar :(");
							}
						}
					});
				});
			});
		</script>

		<script type="text/javascript">
			$(document).ready(function(){
				function aplicarReglaRolToalleros() {
					var rol = $('#rolUsuario').val();
					if (rol === 'TOALLEROS') {
						var $selectHotel = $('#id_hotel');
						var idArco = null;
						$selectHotel.find('option').each(function(){
							var txt = ($(this).text() || '').toLowerCase();
							if (txt.indexOf('arco') !== -1) {
								idArco = $(this).val();
							}
						});
						if (idArco) {
							$selectHotel.val(idArco);
						}
						$selectHotel.prop('disabled', true);
					} else {
						$('#id_hotel').prop('disabled', false);
					}
				}

				$('#rolUsuario').on('change', aplicarReglaRolToalleros);
				aplicarReglaRolToalleros();

				$('#tablaUsuariosLoad').load('usuarios/tablaUsuarios.php');

				$('#registro').click(function(){
					vacios=validarFormVacio('frmRegistro');
					if(vacios > 0){
						alertify.alert("Debes llenar todos los campos!!");
						return false;
					}
					if (!$('#id_hotel').length || $('#id_hotel').val() === ''){
						alertify.alert("Selecciona el hotel al que pertenecerá el usuario.");
						return false;
					}
					// Si está deshabilitado por rol, habilitar temporalmente para que serialize lo envíe.
					var hotelDisabled = $('#id_hotel').prop('disabled');
					if (hotelDisabled) {
						$('#id_hotel').prop('disabled', false);
					}

					datos=$('#frmRegistro').serialize();
					if (hotelDisabled) {
						$('#id_hotel').prop('disabled', true);
					}
					$.ajax({
						type:"POST",
						data:datos,
						url:"../procesos/regLogin/registrarUsuario.php",
						success:function(r){
							if(r==1){
								$('#frmRegistro')[0].reset();
								$('#tablaUsuariosLoad').load('usuarios/tablaUsuarios.php');
								alertify.success("Agregado con exito");
							}else{
								alertify.error(r ? r : "Fallo al agregar :(");
							}
						}
					});
				});
			});
		</script>
	</div><!-- cierre del container del menú -->
<?php 
	// El archivo de seguridad ya maneja la redirección automáticamente
?>