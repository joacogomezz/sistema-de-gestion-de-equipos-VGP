<?php 
	require_once "clases/Conexion.php";
	$obj= new conectar();
	$conexion=$obj->conexion();

	// Base URL para redirecciones (evita rutas tipo ///index.php)
	$script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
	$base_path = ($script_dir === '' || $script_dir === '/' || $script_dir === '\\') ? '' : rtrim($script_dir, '/');

	$sql="SELECT COUNT(*) as total from usuarios";
	$result=mysqli_query($conexion,$sql);
	$validar=0;
	$row=mysqli_fetch_assoc($result);
	if($row['total'] > 0){
		$validar=1;
	}
	// Primera y única vez: si no hay usuarios, ir al panel de registro del super usuario
	if ($validar == 0) {
		$redirect = $base_path ? $base_path . '/registro.php' : 'registro.php';
		header('Location: ' . $redirect);
		exit;
	}
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
	
	<title>The Villa Group - Sistema de Gestión</title>
	
	<!-- PWA Manifest -->
	<link rel="manifest" href="manifest.json">
	
	<!-- Favicon e iconos para iOS (evita 404 favicon.ico) -->
	<link rel="icon" href="img/apple-touch-icon.png" type="image/png">
	<link rel="apple-touch-icon" href="img/apple-touch-icon.png">
	
	<link rel="stylesheet" type="text/css" href="librerias/bootstrap/css/bootstrap.min.css">
	<script src="librerias/jquery-3.2.1.min.js"></script>
	<script src="js/funciones.js"></script>
	<script src="js/pwa-install.js"></script>
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
			--villa-success: #28a745;
			--villa-danger: #dc3545;
		}

		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		html {
			height: 100%;
		}
		
		body {
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			min-height: 100vh;
			height: 100%;
			margin: 0;
			padding: 0;
			display: flex;
			align-items: center;
			justify-content: center;
			overflow-x: hidden;
			position: relative;
		}

		/* ========== FONDO ANIMADO: MAR Y SOL ========== */
		.login-scene {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			z-index: -1;
			overflow: hidden;
		}
		/* Cielo (degradado atardecer) */
		.login-scene .sky {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: linear-gradient(to bottom, 
				#0a0a1a 0%, 
				#1a1a3a 25%, 
				#2d1f3d 50%, 
				#4a2c2a 75%, 
				#1e3a5f 90%,
				#0d2137 100%);
			animation: skyGlow 15s ease-in-out infinite;
		}
		@keyframes skyGlow {
			0%, 100% { opacity: 1; filter: brightness(0.9); }
			50% { opacity: 1; filter: brightness(1.05); }
		}
		/* Sol */
		.login-scene .sun {
			position: absolute;
			left: 50%;
			top: 28%;
			width: 120px;
			height: 120px;
			margin-left: -60px;
			margin-top: -60px;
			border-radius: 50%;
			background: radial-gradient(circle at 30% 30%, 
				#fff9e6 0%, 
				#ffd700 25%, 
				#f4a460 50%, 
				#e07830 100%);
			box-shadow: 
				0 0 60px 20px rgba(255, 200, 100, 0.5),
				0 0 100px 40px rgba(255, 150, 50, 0.2),
				inset -10px -10px 30px rgba(255, 220, 150, 0.4);
			animation: sunFloat 8s ease-in-out infinite;
		}
		@keyframes sunFloat {
			0%, 100% { transform: translateY(0) scale(1); opacity: 1; }
			50% { transform: translateY(-8px) scale(1.03); opacity: 0.95; }
		}
		/* Reflejo del sol en el mar */
		.login-scene .sun-reflect {
			position: absolute;
			left: 50%;
			top: 65%;
			width: 200px;
			height: 40px;
			margin-left: -100px;
			border-radius: 50%;
			background: linear-gradient(to bottom, 
				rgba(255, 200, 100, 0.4), 
				rgba(255, 150, 50, 0.1),
				transparent);
			animation: reflectShine 6s ease-in-out infinite;
		}
		@keyframes reflectShine {
			0%, 100% { opacity: 0.6; transform: scaleX(1); }
			50% { opacity: 1; transform: scaleX(1.1); }
		}
		/* Mar - capa base */
		.login-scene .sea {
			position: absolute;
			bottom: 0;
			left: 0;
			width: 100%;
			height: 45%;
			background: linear-gradient(to bottom, 
				rgba(15, 40, 70, 0.95) 0%, 
				rgba(8, 25, 50, 0.98) 50%,
				#061a30 100%);
		}
		/* Olas animadas - capa 1 */
		.login-scene .wave {
			position: absolute;
			bottom: 0;
			left: 0;
			width: 200%;
			height: 80px;
			background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 80'%3E%3Cpath fill='%231a3a5a' opacity='0.6' d='M0 40 Q300 0 600 40 T1200 40 V80 H0 Z'/%3E%3Cpath fill='%230d2137' opacity='0.8' d='M0 50 Q300 10 600 50 T1200 50 V80 H0 Z'/%3E%3C/svg%3E") repeat-x;
			background-size: 600px 80px;
			animation: waveMove 12s linear infinite;
		}
		.login-scene .wave.wave-2 {
			bottom: 5px;
			height: 70px;
			background-size: 500px 70px;
			animation-duration: 10s;
			animation-direction: reverse;
			opacity: 0.7;
		}
		.login-scene .wave.wave-3 {
			bottom: 10px;
			height: 60px;
			background-size: 550px 60px;
			animation-duration: 14s;
			opacity: 0.5;
		}
		@keyframes waveMove {
			0% { transform: translateX(0); }
			100% { transform: translateX(-50%); }
		}
		/* Brillo del agua (highlight) */
		.login-scene .sea-shine {
			position: absolute;
			bottom: 0;
			left: 0;
			width: 100%;
			height: 50%;
			background: linear-gradient(180deg, 
				transparent 0%, 
				transparent 40%,
				rgba(255, 255, 255, 0.03) 70%,
				rgba(255, 255, 255, 0.06) 100%);
			animation: waterShine 4s ease-in-out infinite;
		}
		@keyframes waterShine {
			0%, 100% { opacity: 0.8; }
			50% { opacity: 1; }
		}
		/* Overlay oscuro suave para que el login destaque */
		.login-scene .scene-overlay {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: radial-gradient(ellipse at center, transparent 0%, rgba(0,0,0,0.25) 70%, rgba(0,0,0,0.5) 100%);
			pointer-events: none;
		}

		/* Contenedor principal con animación de entrada */
		.login-container {
			position: relative;
			z-index: 1;
			width: 100%;
			max-width: 400px;
			background: transparent;
			animation: containerReveal 0.6s ease-out forwards;
		}
		@keyframes containerReveal {
			from { opacity: 0; transform: scale(0.95); }
			to { opacity: 1; transform: scale(1); }
		}
		
		.login-card {
			background: var(--villa-light) !important;
			border: 2px solid var(--villa-border);
			border-radius: 15px;
			padding: 40px;
			box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(194, 154, 75, 0.1);
			position: relative;
			overflow: hidden;
			animation: cardEntrance 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
		}
		@keyframes cardEntrance {
			from {
				opacity: 0;
				transform: translateY(40px) scale(0.96);
			}
			to {
				opacity: 1;
				transform: translateY(0) scale(1);
			}
		}

		.login-card::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 4px;
			background: linear-gradient(90deg, var(--villa-gold), var(--villa-orange), var(--villa-gold));
			background-size: 200% 100%;
			animation: lineShine 3s ease-in-out infinite;
		}
		@keyframes lineShine {
			0%, 100% { background-position: 0% 50%; }
			50% { background-position: 100% 50%; }
		}

		.logo-section {
			text-align: center;
			margin-bottom: 25px;
			animation: fadeInDown 0.6s ease-out 0.35s both;
		}
		@keyframes fadeInDown {
			from { opacity: 0; transform: translateY(-15px); }
			to { opacity: 1; transform: translateY(0); }
		}

		.logo-section .logo-box {
			width: 90px;
			height: 90px;
			margin: 0 auto 15px;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		.logo-section .logo-box img {
			width: 100%;
			height: 100%;
			object-fit: contain;
			object-position: center;
			display: block;
			animation: logoAppear 0.7s ease-out 0.4s both;
		}
		.logo-section .logo-box img {
			border-radius: 0;
		}
		@keyframes logoAppear {
			from { opacity: 0; transform: scale(0.8); }
			to { opacity: 1; transform: scale(1); }
		}

		.title {
			color: var(--villa-gold);
			font-size: 24px;
			font-weight: bold;
			margin-bottom: 5px;
			text-align: center;
		}

		.subtitle {
			color: var(--villa-text-muted);
			font-size: 14px;
			text-align: center;
			margin-bottom: 25px;
		}

		.form-group {
			margin-bottom: 20px;
			animation: formFieldIn 0.5s ease-out both;
		}
		.form-group:nth-of-type(1) { animation-delay: 0.5s; }
		.form-group:nth-of-type(2) { animation-delay: 0.6s; }
		@keyframes formFieldIn {
			from { opacity: 0; transform: translateX(-20px); }
			to { opacity: 1; transform: translateX(0); }
		}

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
			transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease, transform 0.2s ease;
		}

		.form-control:focus {
			outline: none;
			border-color: var(--villa-gold);
			box-shadow: 0 0 0 3px rgba(194, 154, 75, 0.25);
			background-color: var(--villa-light);
		}

		.form-control::placeholder {
			color: var(--villa-text-muted);
		}

		.btn-login {
			width: 100%;
			padding: 12px;
			background: linear-gradient(135deg, var(--villa-gold) 0%, var(--villa-orange) 100%);
			border: none;
			border-radius: 8px;
			color: var(--villa-dark);
			font-size: 14px;
			font-weight: bold;
			cursor: pointer;
			transition: transform 0.25s ease, box-shadow 0.25s ease, filter 0.25s ease;
			margin-bottom: 15px;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			animation: formFieldIn 0.5s ease-out 0.7s both;
		}

		.btn-login:hover {
			transform: translateY(-3px);
			box-shadow: 0 10px 30px rgba(194, 154, 75, 0.45);
		}

		.btn-login:active {
			transform: translateY(0);
		}

		.btn-register {
			width: 100%;
			padding: 12px;
			background: transparent;
			border: 2px solid var(--villa-gold);
			border-radius: 8px;
			color: var(--villa-gold);
			font-size: 14px;
			font-weight: bold;
			cursor: pointer;
			transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease, color 0.25s ease;
			text-decoration: none;
			display: inline-block;
			text-align: center;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			animation: formFieldIn 0.5s ease-out 0.8s both;
		}

		.btn-register:hover {
			background: var(--villa-gold);
			color: var(--villa-dark);
			transform: translateY(-3px);
			box-shadow: 0 10px 30px rgba(194, 154, 75, 0.4);
			text-decoration: none;
		}

		/* Mensajes del sistema: entrada y auto-ocultar a los 4 s */
		.mensaje-sistema {
			background: linear-gradient(135deg, var(--villa-gold) 0%, var(--villa-orange) 100%);
			color: var(--villa-dark);
			padding: 12px 20px;
			border-radius: 25px;
			margin: 15px 0;
			font-size: 14px;
			font-weight: 500;
			text-align: center;
			box-shadow: 0 4px 15px rgba(194, 154, 75, 0.3);
			border: 2px solid var(--villa-gold);
			animation: slideInDown 0.5s ease-out, fadeOut 0.5s ease-in 3.5s forwards;
		}
		.mensaje-sistema i {
			margin-right: 8px;
			font-size: 16px;
		}
		@keyframes slideInDown {
			from { opacity: 0; transform: translateY(-20px); }
			to { opacity: 1; transform: translateY(0); }
		}
		@keyframes fadeOut {
			to { opacity: 0; transform: translateY(-10px); }
		}

		/* Estados del botón de login (éxito/error) */
		.btn-login--success {
			background: linear-gradient(135deg, var(--villa-success) 0%, #20c997 100%) !important;
		}
		.btn-login--error {
			background: linear-gradient(135deg, var(--villa-danger) 0%, #c82333 100%) !important;
		}

		/* Responsive */
		@media (max-width: 768px) {
			.login-container {
				padding: 10px;
			}
			
			.login-card {
				padding: 25px 20px;
				margin: 10px;
			}
			
			.logo-section .logo-box {
				width: 72px;
				height: 72px;
			}
			
			.title {
				font-size: 20px;
			}
			
			.subtitle {
				font-size: 13px;
			}
			
			.mensaje-sistema {
				font-size: 12px;
				padding: 8px 12px;
			}
		}
	</style>
</head>
<body>
	<!-- Fondo animado: mar y sol -->
	<div class="login-scene">
		<div class="sky"></div>
		<div class="sun"></div>
		<div class="sun-reflect"></div>
		<div class="sea"></div>
		<div class="wave wave-1"></div>
		<div class="wave wave-2"></div>
		<div class="wave wave-3"></div>
		<div class="sea-shine"></div>
		<div class="scene-overlay"></div>
	</div>
	<!-- Contenedor de login -->
	<div class="login-container">
		<div class="login-card">
			<div class="logo-section">
				<div class="logo-box">
					<img src="img/p9anie6p.png" alt="The Villa Group">
				</div>
				<h1 class="title">The Villa Group</h1>
				<p class="subtitle">Sistema de Gestión de Equipos</p>
				<?php if(isset($_GET['mensaje'])): ?>
					<div class="mensaje-sistema">
						<?php 
						switch($_GET['mensaje']) {
							case 'sesion_cerrada':
								echo '<i class="glyphicon glyphicon-ok-circle"></i> Sesión cerrada correctamente';
								break;
							case 'sesion_expirada':
								echo '<i class="glyphicon glyphicon-warning-sign"></i> Tu sesión ha expirado. Por favor, inicia sesión nuevamente';
								break;
							case 'sesion_finalizada':
								echo '<i class="glyphicon glyphicon-ban-circle"></i> Sesión finalizada. Por favor, inicia sesión nuevamente';
								break;
							case 'acceso_denegado':
								echo '<i class="glyphicon glyphicon-lock"></i> Acceso denegado. Solo el administrador puede acceder a esta sección';
								break;
							default:
								echo '<i class="glyphicon glyphicon-info-sign"></i> Mensaje del sistema';
						}
						?>
					</div>
				<?php endif; ?>
			</div>

			<form id="frmLogin" autocomplete="on">
				<div class="form-group">
					<label for="usuario">Usuario</label>
					<input type="text" class="form-control" name="usuario" id="usuario" placeholder="Ingresa tu usuario" autocomplete="username">
				</div>

				<div class="form-group">
					<label for="password">Contraseña</label>
					<input type="password" class="form-control" name="password" id="password" placeholder="Ingresa tu contraseña" autocomplete="current-password">
				</div>

				<button type="button" class="btn-login" id="entrarSistema">
					<i class="glyphicon glyphicon-log-in"></i> Iniciar Sesión
				</button>

				<?php if(!$validar): ?>
				<a href="registro.php" class="btn-register">
					<i class="glyphicon glyphicon-user"></i> Registrar Usuario
				</a>
				<?php endif; ?>
			</form>
		</div>
	</div>
</body>
</html>

<script type="text/javascript">
	$(document).ready(function(){
		var $btn = $('#entrarSistema');
		var textoInicial = $btn.html();

		// Focus en campos (opcional, para futuros estilos .focused)
		$('.form-control').focus(function(){ $(this).parent().addClass('focused'); })
			.blur(function(){ $(this).parent().removeClass('focused'); });

		function restaurarBoton() {
			$btn.html(textoInicial).removeClass('btn-login--success btn-login--error').prop('disabled', false);
		}

		// Enviar login
		function enviarLogin() {
			if (validarFormVacio('frmLogin') > 0) {
				alert("Debes llenar todos los campos.");
				return;
			}
			$btn.html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Iniciando...').prop('disabled', true).removeClass('btn-login--success btn-login--error');

			$.ajax({
				type: "POST",
				data: $('#frmLogin').serialize(),
				url: "procesos/regLogin/login.php",
				success: function(r) {
					if (r == 1) {
						$btn.html('<i class="glyphicon glyphicon-ok"></i> ¡Bienvenido!').addClass('btn-login--success');
						setTimeout(function(){ window.location = "vistas/inicio.php"; }, 1000);
					} else {
						$btn.html('<i class="glyphicon glyphicon-remove"></i> Error de acceso').addClass('btn-login--error');
						setTimeout(restaurarBoton, 2000);
					}
				},
				error: function() {
					$btn.html('<i class="glyphicon glyphicon-remove"></i> Error de conexión').addClass('btn-login--error');
					setTimeout(restaurarBoton, 2000);
				}
			});
		}

		$btn.on('click', enviarLogin);

		// Enter en usuario o contraseña para enviar
		$('#usuario, #password').on('keypress', function(e) {
			if (e.which === 13) { e.preventDefault(); enviarLogin(); }
		});

		// Limpiar URL si hay mensaje (el mensaje ya se muestra en PHP en la tarjeta)
		var urlParams = new URLSearchParams(window.location.search);
		if (urlParams.get('mensaje')) {
			window.history.replaceState({}, document.title, window.location.pathname);
		}

		// Seguridad: evitar uso del botón atrás en página de login
		function limpiarHistorialLogin() {
			if (window.history && window.history.pushState) {
				for (var i = 0; i < 5; i++) { window.history.pushState(null, null, window.location.href); }
				window.history.replaceState(null, null, window.location.href);
			}
		}
		function redirigirAlLoginSeguro() {
			limpiarHistorialLogin();
			var base = '<?php echo addslashes($base_path); ?>';
			var url = (base ? base + '/' : '') + 'index.php?mensaje=sesion_finalizada';
			window.location.replace(url);
		}
		window.onpopstate = function() { redirigirAlLoginSeguro(); };
		document.addEventListener('keydown', function(e) {
			if (e.altKey && (e.keyCode === 37 || e.keyCode === 39)) {
				e.preventDefault();
				redirigirAlLoginSeguro();
			}
		});
		limpiarHistorialLogin();
		setInterval(limpiarHistorialLogin, 5000);
	});
</script>