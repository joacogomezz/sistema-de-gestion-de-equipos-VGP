<?php
	require_once "clases/Conexion.php";
	$obj= new conectar();
	$conexion=$obj->conexion();

	// Base URL para redirecciones (evita rutas tipo ///index.php)
	$script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
	$base_path = ($script_dir === '' || $script_dir === '/' || $script_dir === '\\') ? '' : rtrim($script_dir, '/');

	$sql="SELECT COUNT(*) as total from usuarios";
	$result=mysqli_query($conexion,$sql);
	$row=mysqli_fetch_assoc($result);
	// Primera y única vez: si no hay usuarios, ir al panel de registro del super usuario
	if ((int)$row['total'] === 0) {
		$redirect = $base_path ? $base_path . '/registro.php' : 'registro.php';
		header('Location: ' . $redirect);
		exit;
	}

	// Mensaje de estado que llega por ?mensaje= : [tipo, ícono, título, detalle]
	$avisos = array(
		'sesion_cerrada' => array('exito', 'check', 'Sesión cerrada correctamente', ''),
		'sesion_expirada' => array('alerta', 'reloj', 'Tu sesión ha expirado, inicia sesión nuevamente', 'Por seguridad cerramos la sesión tras un periodo sin actividad.'),
		'sesion_finalizada' => array('info', 'info', 'Sesión finalizada', 'Inicia sesión nuevamente para continuar.'),
		'acceso_denegado' => array('peligro', 'error', 'Acceso denegado', 'Solo el administrador puede entrar a esa sección.'),
	);
	$avisoInicial = (isset($_GET['mensaje']) && isset($avisos[$_GET['mensaje']])) ? $avisos[$_GET['mensaje']] : null;

	function escenaOlas($y, $repeticiones) {
		return '<path class="ola" d="M-240 ' . $y . str_repeat(' q20 -5 40 0 t40 0', $repeticiones) . '"/>';
	}
	function escenaSvg($p, $viewBox, $centroSol, $nubesX, $repOlas, $transformRoca) {
		$o = '<svg class="cc-escena" viewBox="' . $viewBox . '" preserveAspectRatio="xMidYMid slice" aria-hidden="true">';
		$o .= '<defs><linearGradient id="' . $p . 'cielo" x1="0" y1="0" x2="0" y2="1"><stop offset="0" class="c1"/><stop offset=".62" class="c2"/><stop offset="1" class="c3"/></linearGradient>';
		$o .= '<linearGradient id="' . $p . 'mar" x1="0" y1="0" x2="0" y2="1"><stop offset="0" class="m1"/><stop offset="1" class="m2"/></linearGradient>';
		$o .= '<radialGradient id="' . $p . 'halo"><stop offset="0" class="s1"/><stop offset="1" class="s2"/></radialGradient>';
		$o .= '<clipPath id="' . $p . 'arriba"><rect x="-400" y="-400" width="2400" height="962"/></clipPath>';
		$o .= '<path id="' . $p . 'roca" d="M940 562 L960 522 L984 474 L1008 436 L1028 388 L1046 352 L1060 304 L1076 282 L1090 300 L1100 338 L1118 360 L1134 332 L1150 302 L1164 322 L1176 362 L1192 382 L1212 362 L1232 334 L1252 352 L1276 400 L1302 382 L1332 420 L1372 442 L1440 432 L1440 562 Z M1036 562 L1038 506 Q1044 452 1074 446 Q1104 452 1110 506 L1112 562 Z M896 562 L903 504 L912 470 L921 502 L928 562 Z" fill-rule="evenodd"/></defs>';
		$o .= '<rect x="-400" y="-400" width="2400" height="962" fill="url(#' . $p . 'cielo)"/>';
		$o .= '<circle class="a-brillo" cx="' . $centroSol . '" cy="548" r="300" fill="url(#' . $p . 'halo)"/>';
		$o .= '<g class="a-nubes"><rect class="nube" x="' . $nubesX[0] . '" y="400" width="300" height="6" rx="3"/><rect class="nube" x="' . $nubesX[1] . '" y="372" width="420" height="5" rx="2.5"/><rect class="nube" x="' . $nubesX[2] . '" y="430" width="220" height="4" rx="2"/><rect class="nube" x="' . $nubesX[3] . '" y="452" width="160" height="4" rx="2"/></g>';
		$o .= '<g clip-path="url(#' . $p . 'arriba)"><circle class="sol a-sol" cx="' . $centroSol . '" cy="540" r="74"/></g>';
		$o .= '<rect x="-400" y="561" width="2400" height="800" fill="url(#' . $p . 'mar)"/>';
		// Reflejo del sol sobre el agua: [desplazamiento desde el sol, y, largo, guion]
		$reflejos = array(array(-65, 574, 90, '31 11'), array(-43, 588, 120, '42 14'), array(-21, 604, 70, '24 8'), array(-64, 620, 150, '52 18'), array(-22, 640, 60, '21 7'), array(-85, 662, 180, '63 22'), array(-43, 688, 90, '31 11'), array(-106, 716, 210, '74 25'), array(-59, 748, 110, '38 13'), array(-127, 784, 240, '84 29'));
		$o .= '<g>';
		foreach ($reflejos as $r) {
			$x1 = $centroSol + $r[0];
			$o .= '<line class="reflejo" x1="' . $x1 . '" y1="' . $r[1] . '" x2="' . ($x1 + $r[2]) . '" y2="' . $r[1] . '" stroke-dasharray="' . $r[3] . '"/>';
		}
		$o .= '</g><g class="a-olas1">';
		foreach (array(600, 640, 690, 750, 820, 900) as $y) $o .= escenaOlas($y, $repOlas);
		$o .= '</g><g class="a-olas2">';
		foreach (array(620, 668, 718, 786, 860) as $y) $o .= escenaOlas($y, $repOlas);
		$o .= '</g>';
		$o .= '<g transform="' . $transformRoca . '"><use href="#' . $p . 'roca" class="roca"/><use href="#' . $p . 'roca" class="roca" opacity=".35" transform="translate(0 1124) scale(1 -1) translate(0 562) scale(1 .5) translate(0 -562)"/></g>';
		$o .= '<g class="a-aves"><path class="ave" d="M120 250 q8 -8 16 0 q8 -8 16 0"/><path class="ave" d="M160 226 q6 -6 12 0 q6 -6 12 0"/><path class="ave" d="M96 214 q5 -5 10 0 q5 -5 10 0"/></g>';
		return $o . '</svg>';
	}
	$verCss = @filemtime(__DIR__ . '/css/costa-clara.css') ?: time();
	$verLogin = @filemtime(__DIR__ . '/css/login.css') ?: time();
	$verJs = @filemtime(__DIR__ . '/js/costa-clara.js') ?: time();
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
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
	<meta name="description" content="Sistema de Gestión de Equipos del Hotel Villa Group">
	<meta name="theme-color" content="#123F52">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<meta name="apple-mobile-web-app-title" content="Villa Equipos">
	<title>Inicio de sesión · The Villa Group</title>
	<link rel="manifest" href="manifest.json">
	<link rel="icon" href="img/apple-touch-icon.png" type="image/png">
	<link rel="apple-touch-icon" href="img/apple-touch-icon.png">
	<link rel="stylesheet" href="css/costa-clara.css?v=<?php echo (int)$verCss; ?>">
	<link rel="stylesheet" href="css/login.css?v=<?php echo (int)$verLogin; ?>">
	<script src="librerias/jquery-3.2.1.min.js"></script>
	<script src="js/pwa-install.js"></script>
	<script src="js/costa-clara.js?v=<?php echo (int)$verJs; ?>"></script>
</head>
<body>
<div class="cc-login">
	<div class="escena-box">
		<div class="esc-d"><?php echo escenaSvg('d-', '0 0 1440 900', 330, array(-90, 450, 590, 130), 26, ''); ?></div>
		<div class="esc-m"><?php echo escenaSvg('m-', '0 260 420 517', 140, array(-280, 260, 400, -60), 13, 'translate(-215 309) scale(.45)'); ?></div>
	</div>

	<form class="cc-login__card" id="frmLogin" autocomplete="on" novalidate>
		<div class="cc-login__marca">
			<span class="cc-login__logo"><img src="img/p9anie6p.png" alt=""></span>
			<div><h1 class="cc-login__titulo">The Villa Group</h1><p class="cc-login__sub">Sistema de Gestión de Equipos</p></div>
		</div>
		<div data-aviso aria-live="polite">
			<?php if ($avisoInicial): ?>
			<div class="cc-aviso cc-aviso--<?php echo $avisoInicial[0]; ?>" role="<?php echo $avisoInicial[0] === 'exito' ? 'status' : 'alert'; ?>"><i data-i="<?php echo $avisoInicial[1]; ?>"></i><div><b><?php echo htmlspecialchars($avisoInicial[2]); ?></b><?php echo htmlspecialchars($avisoInicial[3]); ?></div></div>
			<?php endif; ?>
		</div>
		<div class="cc-field">
			<label class="cc-label" for="usuario">Usuario</label>
			<div class="cc-campo-ico"><i data-i="usuario"></i><input id="usuario" name="usuario" class="cc-input" placeholder="Tu usuario" autocomplete="username" autocapitalize="none" spellcheck="false"></div>
		</div>
		<div class="cc-field">
			<label class="cc-label" for="password">Contraseña</label>
			<div class="cc-campo-ico"><i data-i="candado"></i><input id="password" name="password" class="cc-input cc-input--pass" type="password" placeholder="Tu contraseña" autocomplete="current-password"><button type="button" class="cc-ojo" data-ojo aria-label="Mostrar contraseña" aria-pressed="false"><i data-i="ver"></i></button></div>
			<span class="cc-mayus" data-mayus hidden><i data-i="alerta"></i>Bloq Mayús está activado</span>
		</div>
		<button type="submit" class="cc-btn cc-btn--primario cc-btn--bloque" id="entrarSistema">Iniciar sesión</button>
		<p class="cc-login__ayuda">¿Olvidaste tu contraseña? Solicita el cambio al departamento de Sistemas de tu hotel.</p>
	</form>

	<div class="cc-login__pie"><span>© 2026 The Villa Group · Los Cabos, B.C.S.</span><span>Uso interno</span></div>
</div>

<script>
(function(){
	var iconosExtra = {
		usuario: '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0"/>',
		candado: '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3M12 14.5v2.5"/>',
		ocultar: '<path d="M3 3l18 18"/><path d="M10.6 5.6A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3 3.8M6.4 6.9C3.9 8.6 2.5 12 2.5 12S6 18.5 12 18.5a9 9 0 0 0 4.3-1.1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>'
	};
	function icono(n){
		if (iconosExtra[n]) return '<span class="cc-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' + iconosExtra[n] + '</svg></span>';
		return CostaClara.icono(n);
	}
	document.querySelectorAll('i[data-i]').forEach(function(el){
		var t = document.createElement('span'); t.innerHTML = icono(el.getAttribute('data-i')); el.replaceWith(t.firstChild);
	});

	var form = document.getElementById('frmLogin');
	var usuario = document.getElementById('usuario');
	var pass = document.getElementById('password');
	var btn = document.getElementById('entrarSistema');
	var avisoBox = document.querySelector('[data-aviso]');
	var mayus = document.querySelector('[data-mayus]');

	function escapar(s){ var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
	function aviso(tipo, ico, titulo, detalle){
		avisoBox.innerHTML = '<div class="cc-aviso cc-aviso--' + tipo + '" role="' + (tipo === 'exito' ? 'status' : 'alert') + '">' + icono(ico) + '<div><b>' + escapar(titulo) + '</b>' + escapar(detalle || '') + '</div></div>';
	}
	function marcarError(on){ [usuario, pass].forEach(function(i){ i.classList.toggle('cc-input--error', on); }); }
	function cargando(on){ btn.classList.toggle('cc-btn--cargando', on); btn.disabled = on; }

	document.querySelector('[data-ojo]').addEventListener('click', function(){
		var ver = pass.type === 'password';
		pass.type = ver ? 'text' : 'password';
		this.setAttribute('aria-pressed', ver);
		this.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
		this.innerHTML = icono(ver ? 'ocultar' : 'ver');
	});
	pass.addEventListener('keyup', function(e){ if (e.getModifierState) mayus.hidden = !e.getModifierState('CapsLock'); });
	[usuario, pass].forEach(function(i){ i.addEventListener('input', function(){ marcarError(false); }); });

	form.addEventListener('submit', function(e){
		e.preventDefault();
		if (!usuario.value.trim() || !pass.value) {
			marcarError(true);
			aviso('alerta', 'alerta', 'Faltan datos', 'Escribe tu usuario y tu contraseña.');
			(usuario.value.trim() ? pass : usuario).focus();
			return;
		}
		cargando(true);
		$.ajax({
			type: 'POST',
			data: $(form).serialize(),
			url: 'procesos/regLogin/login.php',
			success: function(r){
				if (r == 1) {
					aviso('exito', 'check', '¡Bienvenido!', 'Entrando al sistema…');
					window.location = 'vistas/inicio.php';
				} else {
					cargando(false);
					marcarError(true);
					aviso('peligro', 'error', 'Usuario o contraseña incorrectos', 'Revisa tus datos e inténtalo de nuevo.');
					pass.value = '';
					pass.focus();
				}
			},
			error: function(){
				cargando(false);
				aviso('peligro', 'error', 'No se pudo conectar con el servidor', 'Revisa tu conexión a la red e inténtalo de nuevo.');
			}
		});
	});

	// Quita ?mensaje= de la barra de direcciones (el aviso ya se mostró)
	if (new URLSearchParams(window.location.search).get('mensaje')) {
		window.history.replaceState({}, document.title, window.location.pathname.replace(/^\/+/, '/'));
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
		window.location.replace((base ? base + '/' : '') + 'index.php?mensaje=sesion_finalizada');
	}
	window.onpopstate = function() { redirigirAlLoginSeguro(); };
	document.addEventListener('keydown', function(e) {
		if (e.altKey && (e.keyCode === 37 || e.keyCode === 39)) { e.preventDefault(); redirigirAlLoginSeguro(); }
	});
	limpiarHistorialLogin();
	setInterval(limpiarHistorialLogin, 5000);
	usuario.focus();
})();
</script>
</body>
</html>
