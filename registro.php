<?php
require_once "clases/Conexion.php";

$obj = new conectar();
$conexion = $obj->conexion();

// Verificar si ya hay usuarios (solo permitir registro cuando no hay ninguno, como en index)
$sql = "SELECT COUNT(*) as total FROM usuarios";
$result = mysqli_query($conexion, $sql);
$validar = 0;
$row = mysqli_fetch_assoc($result);
if ($row['total'] > 0) {
	$validar = 1;
}

// Base path para enlaces y redirecciones (evita rutas tipo ///index.php)
$script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$base_path = ($script_dir === '' || $script_dir === '/' || $script_dir === '\\') ? '' : rtrim($script_dir, '/');
$url_index = $base_path ? $base_path . '/index.php' : 'index.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="description" content="Registro de usuario - Sistema de Gestión de Equipos del Hotel Villa Group">
	<meta name="theme-color" content="#c29a4b">
	<title>Registro de usuario - The Villa Group</title>
	<link rel="icon" href="img/apple-touch-icon.png" type="image/png">
	<link rel="stylesheet" type="text/css" href="librerias/bootstrap/css/bootstrap.min.css">
	<script src="librerias/jquery-3.2.1.min.js"></script>
	<script src="js/funciones.js"></script>
	<style>
		:root {
			--villa-dark: #1a1a1a;
			--villa-light: #2a2a2a;
			--villa-white: #ffffff;
			--villa-gold: #c29a4b;
			--villa-orange: #a0522d;
			--villa-text: #ffffff;
			--villa-text-muted: #cccccc;
			--villa-border: #333333;
			--villa-success: #28a745;
			--villa-danger: #dc3545;
		}
		* { margin: 0; padding: 0; box-sizing: border-box; }
		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			background: linear-gradient(135deg, #0a0a1a 0%, #1a1a3a 50%, #0d2137 100%);
			padding: 20px;
		}
		.registro-container {
			width: 100%;
			max-width: 420px;
		}
		.registro-card {
			position: relative;
			background: var(--villa-light);
			border: 2px solid var(--villa-border);
			border-top: 4px solid var(--villa-gold);
			border-radius: 15px;
			padding: 35px;
			box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
		}
		.logo-section {
			text-align: center;
			margin-bottom: 25px;
		}
		.logo-section img {
			width: 70px;
			height: 70px;
			border-radius: 12px;
			border: 2px solid var(--villa-gold);
			object-fit: contain;
		}
		.titulo-registro {
			color: var(--villa-gold);
			font-size: 22px;
			font-weight: bold;
			margin: 15px 0 5px;
			text-align: center;
		}
		.subtitulo-registro {
			color: var(--villa-text-muted);
			font-size: 13px;
			text-align: center;
			margin-bottom: 25px;
		}
		.form-group { margin-bottom: 18px; }
		.form-group label {
			display: block;
			margin-bottom: 6px;
			color: var(--villa-text);
			font-weight: 500;
			font-size: 13px;
		}
		.form-control {
			width: 100%;
			padding: 12px 15px;
			background-color: var(--villa-dark);
			border: 2px solid var(--villa-border);
			border-radius: 8px;
			font-size: 14px;
			color: var(--villa-text);
		}
		.form-control:focus {
			outline: none;
			border-color: var(--villa-gold);
			box-shadow: 0 0 0 3px rgba(194, 154, 75, 0.25);
		}
		.form-control::placeholder { color: var(--villa-text-muted); }
		select.form-control { cursor: pointer; }
		.btn-registro {
			width: 100%;
			padding: 12px;
			background: linear-gradient(135deg, var(--villa-gold) 0%, var(--villa-orange) 100%);
			border: none;
			border-radius: 8px;
			color: var(--villa-dark);
			font-size: 14px;
			font-weight: bold;
			cursor: pointer;
			margin-top: 10px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		.btn-registro:hover {
			opacity: 0.95;
			color: var(--villa-dark);
		}
		.btn-volver {
			display: block;
			text-align: center;
			margin-top: 20px;
			color: var(--villa-gold);
			text-decoration: none;
			font-size: 14px;
		}
		.btn-volver:hover { color: var(--villa-text); text-decoration: none; }
		.mensaje-sistema {
			padding: 10px 15px;
			border-radius: 8px;
			margin-bottom: 15px;
			font-size: 13px;
			text-align: center;
		}
		.mensaje-sistema.error { background: rgba(220, 53, 69, 0.3); color: #ffb3b3; border: 1px solid var(--villa-danger); }
		.mensaje-sistema.ok { background: rgba(40, 167, 69, 0.3); color: #b3ffb3; border: 1px solid var(--villa-success); }
	</style>
</head>
<body>
	<div class="registro-container">
		<div class="registro-card">
			<div class="logo-section">
				<img src="img/p9anie6p.png" alt="The Villa Group">
				<h1 class="titulo-registro">Registro del super usuario</h1>
				<p class="subtitulo-registro">Primera vez: crea la cuenta del administrador</p>
			</div>

			<?php if ($validar): ?>
				<div class="mensaje-sistema ok">
					Ya existen usuarios en el sistema. Solo se permite registrar el primer usuario desde aquí.
				</div>
				<a href="<?php echo htmlspecialchars($url_index); ?>" class="btn-volver">&larr; Volver al inicio de sesión</a>
			<?php else: ?>
				<div id="mensajeAjax"></div>
				<form id="frmRegistro" autocomplete="on">
					<div class="form-group">
						<label for="nombre">Nombre</label>
						<input type="text" class="form-control" name="nombre" id="nombre" placeholder="Nombre" autocomplete="given-name" required>
					</div>
					<div class="form-group">
						<label for="apellido">Apellido</label>
						<input type="text" class="form-control" name="apellido" id="apellido" placeholder="Apellido" autocomplete="family-name" required>
					</div>
					<div class="form-group">
						<label for="usuario">Usuario</label>
						<input type="text" class="form-control" name="email" id="usuario" placeholder="Nombre de usuario para iniciar sesión" autocomplete="username" required>
					</div>
					<div class="form-group">
						<label for="password">Contraseña</label>
						<input type="password" class="form-control" name="password" id="password" placeholder="Contraseña" autocomplete="new-password" required>
					</div>
					<div class="form-group">
						<label for="password2">Confirmar contraseña</label>
						<input type="password" class="form-control" name="password2" id="password2" placeholder="Repetir contraseña" autocomplete="new-password" required>
					</div>
					<button type="submit" class="btn-registro" id="btnRegistro">
						Registrar usuario
					</button>
				</form>
				<a href="<?php echo htmlspecialchars($url_index); ?>" class="btn-volver">&larr; Volver al inicio de sesión</a>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>

<?php if (!$validar): ?>
<script type="text/javascript">
$(document).ready(function(){
	$('#frmRegistro').on('submit', function(e){
		e.preventDefault();
		var pass = $('#password').val();
		var pass2 = $('#password2').val();
		if (pass !== pass2) {
			$('#mensajeAjax').html('<div class="mensaje-sistema error">Las contraseñas no coinciden.</div>');
			return;
		}
		if (pass.length < 4) {
			$('#mensajeAjax').html('<div class="mensaje-sistema error">La contraseña debe tener al menos 4 caracteres.</div>');
			return;
		}
		if (validarFormVacio('frmRegistro') > 0) {
			$('#mensajeAjax').html('<div class="mensaje-sistema error">Completa todos los campos.</div>');
			return;
		}
		var $btn = $('#btnRegistro');
		$btn.prop('disabled', true).text('Guardando...');
		$('#mensajeAjax').html('');
		$.ajax({
			type: 'POST',
			url: 'procesos/regLogin/registro.php',
			data: $('#frmRegistro').serialize(),
			success: function(r) {
				r = r.trim();
				if (r === '1') {
					$('#mensajeAjax').html('<div class="mensaje-sistema ok">Usuario registrado correctamente. Redirigiendo...</div>');
					setTimeout(function(){ window.location = '<?php echo addslashes($url_index); ?>'; }, 1500);
				} else {
					$('#mensajeAjax').html('<div class="mensaje-sistema error">' + (r || 'Error al registrar. Intenta de nuevo.') + '</div>');
					$btn.prop('disabled', false).text('Registrar usuario');
				}
			},
			error: function() {
				$('#mensajeAjax').html('<div class="mensaje-sistema error">Error de conexión. Intenta de nuevo.</div>');
				$btn.prop('disabled', false).text('Registrar usuario');
			}
		});
	});
});
</script>
<?php endif; ?>
