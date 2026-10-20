/* Respaldo de base de datos (solo Super Administrador) */
(function () {
'use strict';
var $ = CC.$, I = CC.I, esc = CC.esc, num = CC.num;
var raiz = $('#vistaRespaldo'), D = null;

function mb(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
function fechaHora(s) { return s ? CC.fcorta(s) + ', ' + String(s).slice(11, 16) : '—'; }

function cargar() { return CC.api('../procesos/backup/infoRespaldo.php', null, 'GET').done(function (j) { if (j && j.ok) { D = j; render(); } }); }
function render() {
	var ult = D.historial[0];
	raiz.innerHTML = '<div class="vista"><div class="enc"><div><h1 class="cc-h1">Respaldo de base de datos</h1><p class="sub">Copia completa del sistema en un archivo .sql para recuperarlo si el servidor falla.</p></div></div>' +
		'<div class="cc-aviso cc-aviso--peligro" role="note">' + I('alerta') + '<div><b>El respaldo contiene información sensible.</b>Incluye nombres de empleados y huéspedes, números de habitación, valores de los equipos y las contraseñas cifradas de todos los usuarios. Trátalo como un documento confidencial.</div></div>' +
		'<section class="card resp-hero"><div class="resp-hero__main"><h2>Descargar un respaldo ahora</h2><p>Descarga uno antes de cambios grandes, como una importación masiva o una baja masiva, y al menos una vez por semana.</p>' +
		'<dl class="resp-datos" style="margin:0"><div><dt>Último respaldo</dt><dd id="rbUlt">' + (ult ? fechaHora(ult.fecha) : 'Ninguno registrado') + '</dd></div><div><dt>Tamaño aproximado</dt><dd>' + mb(ult ? ult.bytes : D.bytes) + '</dd></div><div><dt>Contenido</dt><dd>' + num(D.equipos) + ' equipos · ' + num(D.hoteles) + ' hoteles · ' + num(D.resguardos) + ' resguardos</dd></div></dl>' +
		'<div id="rbProg" hidden><div class="cc-row" style="justify-content:space-between;font-size:13px;margin-bottom:6px"><span id="rbPaso">Generando el archivo…</span></div><div class="barra-prog barra-prog--ind" role="progressbar" aria-label="Generando respaldo"><span></span></div></div>' +
		'<div><button type="button" class="cc-btn cc-btn--primario btn-xl" id="rbBtn">' + I('descarga') + 'Descargar respaldo ahora (.sql)</button></div><p class="cc-small" style="margin:0">Nombre del archivo: <span class="num">' + esc(D.archivo) + '</span></p></div>' +
		'<div class="resp-hero__side"><h3>Dónde guardarlo</h3><ul class="checks"><li>' + I('check') + '<span>En un disco externo <b>cifrado</b> que se guarde bajo llave en Sistemas.</span></li><li>' + I('check') + '<span>En la carpeta de Sistemas de la nube corporativa, con acceso solo para el equipo de Sistemas.</span></li><li>' + I('check') + '<span>Conserva al menos 3 copias en 2 lugares distintos; borra las de más de 6 meses.</span></li><li class="no">' + I('cerrar') + '<span>No lo envíes por correo, WhatsApp ni lo guardes en una USB personal o en tu escritorio.</span></li><li class="no">' + I('cerrar') + '<span>No abras ni edites el archivo .sql; un cambio puede impedir restaurarlo.</span></li></ul></div></section>' +
		'<section class="card"><div class="card__head"><h2>Respaldos descargados</h2></div><div class="tabla-scroll"><table class="cc-t"><thead><tr><th>Fecha</th><th>Generado por</th><th>Archivo</th><th class="der">Tamaño</th></tr></thead><tbody>' +
		(D.historial.length ? D.historial.map(function (h) {
			return '<tr style="cursor:default"><td class="num">' + fechaHora(h.fecha) + '</td><td>' + esc(h.quien) + '</td><td class="num mut">' + esc(h.archivo) + '</td><td class="der num">' + mb(h.bytes) + '</td></tr>';
		}).join('') : '<tr><td colspan="4" class="mut" style="text-align:center;padding:24px">Aún no hay respaldos descargados desde esta pantalla.</td></tr>') +
		'</tbody></table></div></section></div>';
	CostaClara.hidratar(raiz);
	$('#rbBtn').onclick = descargar;
}
function descargar() {
	var b = this, pr = $('#rbProg');
	CC.ocupado(b, true); pr.hidden = false;
	fetch('../procesos/backup/respaldar_bd.php', { credentials: 'same-origin' }).then(function (r) {
		var cd = r.headers.get('Content-Disposition') || '';
		// Si la sesión venció, el servidor responde con la página de inicio de sesión en lugar del archivo
		if (!r.ok || cd.indexOf('attachment') < 0) throw new Error(r.ok ? 'sesion' : 'http');
		var nombre = (cd.match(/filename="([^"]+)"/) || [])[1] || 'respaldo.sql';
		return r.blob().then(function (blob) {
			var a = document.createElement('a'), url = URL.createObjectURL(blob);
			a.href = url; a.download = nombre; document.body.appendChild(a); a.click(); a.remove();
			setTimeout(function () { URL.revokeObjectURL(url); }, 10000);
			CC.toast('exito', 'Respaldo listo · ' + mb(blob.size), 'Se guardó como <span class="num">' + esc(nombre) + '</span> en tu carpeta de descargas. Muévelo a un lugar seguro.');
			cargar();
		});
	}).catch(function (e) {
		CC.ocupado(b, false); pr.hidden = true;
		if (e.message === 'sesion') CC.toast('error', 'Tu sesión expiró', 'Vuelve a iniciar sesión y descarga el respaldo de nuevo.');
		else CC.toast('error', 'No se pudo generar el respaldo', 'Inténtalo de nuevo. Si se repite, avisa a Sistemas.');
	});
}
cargar();
})();
