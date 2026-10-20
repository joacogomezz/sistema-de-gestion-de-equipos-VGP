/* Usuarios (solo Super Administrador) con datos de procesos/usuarios/usuarios.php */
(function () {
'use strict';
var $ = CC.$, $$ = CC.$$, I = CC.I, esc = CC.esc, num = CC.num, sinAc = CC.sinAc;
var raiz = $('#vistaUsuarios'), URL = '../procesos/usuarios/usuarios.php';
var ROL = { super: ['rol--super', 'candado', 'Super Administrador'], admin: ['rol--admin', 'usuarios', 'Administrador'], toallero: ['rol--toal', 'toallas', 'Toallero'] };
var COLAV = ['#1F6F8B', '#2E7D5B', '#93431A', '#7A5A1E', '#5E5650', '#185A71'];
var FTXT = ['', 'Muy débil', 'Débil', 'Aceptable', 'Segura'];
var S = { rol: '', q: '' }, D = { lista: [], hoteles: [], yo: 0, hotelToalleros: 0 };

function hash(s) { var h = 0; for (var i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) | 0; return Math.abs(h); }
function iniciales(u) { return ((u.nombre[0] || '') + (u.apellido[0] || '')).toUpperCase() || '?'; }
function fuerza(p) { var n = 0; if (p.length >= 8) n++; if (/[a-z]/.test(p) && /[A-Z]/.test(p)) n++; if (/\d/.test(p)) n++; if (/[^A-Za-z0-9]/.test(p) || p.length >= 12) n++; return p ? Math.max(1, n) : 0; }

function cargar() { return CC.api(URL, { accion: 'listar' }, 'GET').done(function (j) { if (j && j.ok) { D = j; render(); } }); }
function render() {
	var q = sinAc(S.q.trim());
	var vis = D.lista.filter(function (u) { return (!S.rol || u.rol === S.rol || (S.rol === 'admin' && u.rol === 'super')) && (!q || sinAc([u.nombre, u.apellido, u.usuario, u.hotel].join(' ')).indexOf(q) >= 0); });
	var nT = D.lista.filter(function (u) { return u.rol === 'toallero'; }).length, nA = D.lista.length - nT;
	raiz.innerHTML = '<div class="vista"><div class="enc"><div><div class="enc__tit"><h1 class="cc-h1">Usuarios</h1><span class="contador"><b>' + num(D.lista.length) + '</b>usuarios</span></div><p class="sub">Quién entra al sistema y qué puede hacer. Los toalleros solo ven Control de toallas de su hotel.</p></div>' +
		'<button type="button" class="cc-btn cc-btn--primario" id="uNuevo">' + I('mas') + 'Nuevo usuario</button></div>' +
		'<div class="filtros"><div class="seg-tabs" role="group" aria-label="Rol"><button type="button" data-rol="" aria-selected="' + (!S.rol) + '">Todos ' + D.lista.length + '</button><button type="button" data-rol="admin" aria-selected="' + (S.rol === 'admin') + '">Administradores ' + nA + '</button><button type="button" data-rol="toallero" aria-selected="' + (S.rol === 'toallero') + '">Toalleros ' + nT + '</button></div>' +
		'<label class="cc-buscador"><i data-i="buscar"></i><input class="cc-input" id="uQ" type="search" placeholder="Nombre, usuario u hotel" value="' + esc(S.q) + '" aria-label="Buscar usuarios"></label></div>' +
		'<section class="card"><div class="tabla-scroll"><table class="cc-t"><thead><tr><th></th><th>Nombre</th><th>Apellido</th><th>Usuario</th><th>Rol</th><th>Hotel</th><th>Alta</th><th class="c-acc" style="width:96px">Acciones</th></tr></thead><tbody>' +
		(vis.length ? vis.map(function (u) {
			var r = ROL[u.rol], protegido = u.rol === 'super' || u.id === D.yo;
			return '<tr style="cursor:default"><td style="width:48px"><span class="av" style="background:' + COLAV[hash(u.usuario) % COLAV.length] + '">' + esc(iniciales(u)) + '</span></td><td><b style="font-weight:500">' + esc(u.nombre) + '</b></td><td>' + esc(u.apellido) + '</td><td class="num mut">' + esc(u.usuario) + '</td>' +
				'<td><span class="rol ' + r[0] + '">' + I(r[1]) + r[2] + '</span></td><td>' + esc(u.hotel || '—') + '</td><td class="mut num">' + CC.fcorta(u.alta) + '</td><td><span class="accs">' +
				'<button type="button" class="acc-btn" data-ue="' + u.id + '" aria-label="Editar a ' + esc(u.nombre + ' ' + u.apellido) + '">' + I('editar') + '</button>' +
				(protegido ? '<span class="acc-btn" title="' + (u.rol === 'super' ? 'El Super Administrador no se puede eliminar' : 'No puedes eliminar tu propia cuenta') + '" style="cursor:default">' + I('candado') + '</span>' : '<button type="button" class="acc-btn" data-ud="' + u.id + '" aria-label="Eliminar a ' + esc(u.nombre + ' ' + u.apellido) + '" style="color:var(--peligro)">' + I('baja') + '</button>') +
				'</span></td></tr>';
		}).join('') : '<tr><td colspan="8" class="carrito__vacio" style="height:auto">Ningún usuario coincide.</td></tr>') + '</tbody></table></div></section></div>';
	CostaClara.hidratar(raiz);
	$$('[data-rol]').forEach(function (b) { b.onclick = function () { S.rol = b.getAttribute('data-rol'); render(); }; });
	var tq; $('#uQ').oninput = function () { var v = this.value; clearTimeout(tq); tq = setTimeout(function () { S.q = v; render(); var i = $('#uQ'); i.focus(); i.setSelectionRange(v.length, v.length); }, 200); };
	$('#uNuevo').onclick = function () { formulario(null); };
	$$('[data-ue]').forEach(function (b) { b.onclick = function () { formulario(porId(b.getAttribute('data-ue'))); }; });
	$$('[data-ud]').forEach(function (b) { b.onclick = function () { eliminar(porId(b.getAttribute('data-ud'))); }; });
}
function porId(id) { id = +id; return D.lista.filter(function (u) { return u.id === id; })[0]; }
function sugerir(n, a) {
	var base = sinAc((n.trim()[0] || '') + (a.trim().split(' ')[0] || '')).replace(/[^a-z0-9]/g, ''), u = base, k = 2;
	while (u && D.lista.some(function (x) { return sinAc(x.usuario) === u; })) u = base + (k++);
	return u;
}
function campoPass(id, lbl, o) {
	return CC.campo(lbl, '<div class="pass"><input class="cc-input" type="password" id="' + id + '" autocomplete="new-password"><button type="button" data-ver="' + id + '" aria-label="Mostrar contraseña" aria-pressed="false">' + I('ver') + '</button></div><div class="fuerza" data-f="' + id + '" data-n="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div><span class="fuerza-txt" data-ft="' + id + '" aria-live="polite"></span>', o);
}
function formulario(u) {
	var ed = !!u, esSuper = ed && u.rol === 'super';
	var rol = ed ? (u.rol === 'toallero' ? 'toallero' : 'admin') : 'admin', tocadoU = ed;
	var arco = D.hoteles.filter(function (h) { return h.id === D.hotelToalleros; })[0];
	CC.modal('<div class="mod-head"><h2>' + (ed ? 'Editar usuario' : 'Nuevo usuario') + '</h2><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono" data-cancel aria-label="Cerrar">' + I('cerrar') + '</button></div>' +
		'<form class="mform" id="uF" novalidate><div class="g2">' + CC.campo('Nombre', '<input class="cc-input" id="uN" maxlength="50" value="' + esc(ed ? u.nombre : '') + '" autocomplete="off">', { req: 1 }) + CC.campo('Apellido', '<input class="cc-input" id="uA" maxlength="50" value="' + esc(ed ? u.apellido : '') + '" autocomplete="off">', { req: 1 }) + '</div>' +
		CC.campo('Usuario', '<input class="cc-input" id="uU" maxlength="30" value="' + esc(ed ? u.usuario : '') + '" autocomplete="off" spellcheck="false" autocapitalize="none">', { req: 1, ayuda: 'Sin espacios ni acentos. Con este nombre inicia sesión.' }) +
		campoPass('uP', ed ? 'Nueva contraseña' : 'Contraseña', ed ? { opc: 1, ayuda: 'Déjala vacía para conservar la actual.' } : { req: 1 }) +
		(esSuper ? '<p class="cc-small cc-muted" style="margin:0">' + I('candado') + ' Super Administrador · acceso a todos los hoteles. Su rol no se puede cambiar.</p>' :
			'<div class="g2">' + CC.campo('Hotel / Villa', '<select class="cc-select" id="uH">' + CC.opciones(D.hoteles.map(function (h) { return [h.id, h.nombre]; }), ed ? u.idHotel : '', 'Selecciona') + '</select>', { req: 1 }) +
			'<div class="cc-field"><span class="cc-label" id="lRol">Rol<span class="cc-req">*</span></span><div class="opciones" role="radiogroup" aria-labelledby="lRol" id="uR" style="grid-template-columns:1fr 1fr">' +
			['admin', 'toallero'].map(function (r) { return '<button type="button" class="opcion" role="radio" data-v="' + r + '" aria-checked="' + (rol === r) + '"><span class="o"></span>' + ROL[r][2] + '</button>'; }).join('') + '</div><span class="cc-ayuda" id="uRAyuda"></span></div></div>') +
		'</form><div class="mod-pie" style="justify-content:flex-end"><button type="button" class="cc-btn cc-btn--secundario" data-cancel>Cancelar</button><button type="button" class="cc-btn cc-btn--primario" id="uOk">' + I('check') + (ed ? 'Guardar cambios' : 'Crear usuario') + '</button></div>', { ancho: 560 });
	function ayudaRol() {
		if (esSuper) return;
		$('#uRAyuda').textContent = rol === 'admin' ? 'Registra equipos, resguardos y bajas de su hotel.' : 'Solo entrega y recibe toallas en la alberca' + (arco ? ' de ' + arco.nombre : '') + '.';
		if (rol === 'toallero' && arco) { $('#uH').value = String(arco.id); $('#uH').disabled = true; } else $('#uH').disabled = false;
	}
	ayudaRol();
	$$('#uR .opcion').forEach(function (b) { b.onclick = function () { rol = b.getAttribute('data-v'); $$('#uR .opcion').forEach(function (x) { x.setAttribute('aria-checked', String(x === b)); }); ayudaRol(); }; });
	function auto() { if (!tocadoU) $('#uU').value = sugerir($('#uN').value, $('#uA').value); }
	$('#uN').oninput = function () { CC.errEn(this, ''); auto(); };
	$('#uA').oninput = function () { CC.errEn(this, ''); auto(); };
	$('#uU').oninput = function () { tocadoU = true; CC.errEn(this, ''); };
	$$('[data-ver]').forEach(function (b) { b.onclick = function () { var i = $('#' + b.getAttribute('data-ver')), v = i.type === 'password'; i.type = v ? 'text' : 'password'; b.setAttribute('aria-pressed', v); b.setAttribute('aria-label', v ? 'Ocultar contraseña' : 'Mostrar contraseña'); b.innerHTML = I(v ? 'ocultar' : 'ver'); }; });
	$('#uP').addEventListener('input', function () { var n = fuerza(this.value); $('[data-f="uP"]').setAttribute('data-n', n); $('[data-ft="uP"]').textContent = FTXT[n]; CC.errEn(this, ''); });
	$('#uF').onsubmit = function (e) { e.preventDefault(); $('#uOk').click(); };
	$('#uOk').onclick = function () {
		var btn = this, campos = { nombre: '#uN', apellido: '#uA', usuario: '#uU', password: '#uP', hotel: '#uH' };
		var datos = { accion: 'guardar', id: ed ? u.id : 0, nombre: $('#uN').value.trim(), apellido: $('#uA').value.trim(), usuario: $('#uU').value.trim(), password: $('#uP').value, rol: rol, id_hotel: esSuper ? 0 : $('#uH').value };
		CC.ocupado(btn, true);
		CC.api(URL, datos).done(function (j) {
			if (j && j.ok) {
				CC.cierraModal(); cargar();
				CC.toast('exito', ed ? 'Cambios guardados' : 'Usuario ' + esc(datos.usuario) + ' creado', ed ? (datos.password ? 'La contraseña se actualizó.' : '') : esc(datos.nombre + ' ' + datos.apellido) + ' · ' + ROL[rol][2] + '. Comparte la contraseña en persona.');
				return;
			}
			CC.ocupado(btn, false);
			if (j && j.errores) { var primero = null; Object.keys(j.errores).forEach(function (k) { var el = $(campos[k]); if (el) { CC.errEn(el, j.errores[k]); primero = primero || el; } }); if (primero) primero.focus(); }
			else CC.toast('error', 'No se pudo guardar', esc((j && j.mensaje) || ''));
		}).fail(function () { CC.ocupado(btn, false); });
	};
}
function eliminar(u) {
	CC.confirmar({
		titulo: '¿Eliminar a ' + esc(u.nombre + ' ' + u.apellido) + '?', boton: 'Eliminar usuario',
		texto: 'Ya no podrá iniciar sesión. Los equipos, resguardos y bajas que registró se conservan.',
		ok: function (md, terminar, reintentar) {
			CC.api(URL, { accion: 'eliminar', id: u.id }).done(function (j) {
				if (j && j.ok) { terminar(); cargar(); CC.toast('exito', 'Usuario ' + esc(u.usuario) + ' eliminado', ''); }
				else { reintentar(); CC.toast('error', 'No se pudo eliminar', esc((j && j.mensaje) || '')); }
			}).fail(reintentar);
		}
	});
}
cargar();
})();
