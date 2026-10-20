/* Utilidades de interfaz Costa Clara compartidas por las pantallas de operación.
   Requiere js/costa-clara.js y, en la página, #modal y #toasts. */
(function () {
'use strict';
var I = CostaClara.icono;
function $(s, r) { return (r || document).querySelector(s); }
function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
function num(n) { return Number(n).toLocaleString('es-MX'); }
function dinero(n, m) { return (m === 'USD' ? 'US$' : m === 'EUR' ? '€' : '$') + ' ' + Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function sinAc(s) { return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }
function f2(n) { return (n < 10 ? '0' : '') + n; }
function iso(d) { return d.getFullYear() + '-' + f2(d.getMonth() + 1) + '-' + f2(d.getDate()); }
var MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
var MESES_L = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
function fcorta(s) { if (!s) return '—'; var p = String(s).slice(0, 10).split('-'); if (p.length < 3 || !+p[1]) return esc(s); return +p[2] + ' ' + MESES[+p[1] - 1] + ' ' + p[0]; }
function flarga(s) { if (!s) return '—'; var p = String(s).slice(0, 10).split('-'); if (p.length < 3 || !+p[1]) return esc(s); return +p[2] + ' de ' + MESES_L[+p[1] - 1] + ' de ' + p[0]; }

var focoPrevio = null, alCerrar = null;
function modal(h, o) {
	o = o || {}; focoPrevio = document.activeElement;
	var m = $('#modal');
	m.innerHTML = '<div class="cc-modal' + (o.grande ? ' mod-grande' : '') + '" role="dialog" aria-modal="true"' + (o.ancho ? ' style="width:min(' + o.ancho + 'px,100%)"' : '') + '>' + h + '</div>';
	m.hidden = false; CostaClara.hidratar(m);
	var f = $(o.foco || 'input:not([type=hidden]),select,textarea,[data-cancel],button', m); if (f) f.focus();
	m.onclick = function (e) { if (e.target === m || e.target.closest('[data-cancel]')) cierraModal(); };
	return $('.cc-modal', m);
}
function cierraModal() {
	var m = $('#modal'); m.hidden = true; m.innerHTML = '';
	if (alCerrar) { var f = alCerrar; alCerrar = null; f(); }
	if (focoPrevio && focoPrevio.focus && document.contains(focoPrevio)) focoPrevio.focus();
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && $('#modal') && !$('#modal').hidden) cierraModal(); });

/* confirmar({titulo, texto, boton, icono, tono, peligro, escribir, extra, ok(md, terminar)}) — ok recibe terminar() para cerrar tras la petición */
function confirmar(o) {
	var md = modal('<div class="cc-modal__body"><span class="cc-modal__ico ' + (o.tono || 'cc-tono-peligro') + '">' + I(o.icono || 'alerta') + '</span><div class="cc-stack" style="gap:8px;min-width:0"><h2 class="cc-h2">' + o.titulo + '</h2><p class="cc-muted" style="margin:0">' + o.texto + '</p>' +
		(o.escribir ? campo('Escribe ' + o.escribir + ' para confirmar', '<input class="cc-input" id="cfTxt" autocomplete="off">') : '') + (o.extra || '') + '</div></div>' +
		'<div class="cc-modal__pie"><button type="button" class="cc-btn cc-btn--secundario" data-cancel>' + (o.cancelar || 'Cancelar') + '</button>' + (o.boton ? '<button type="button" class="cc-btn ' + (o.peligro === false ? 'cc-btn--primario' : 'cc-btn--peligro') + '" id="cfOk">' + o.boton + '</button>' : '') + '</div>',
		{ foco: o.escribir ? '#cfTxt' : (o.extra ? 'select,input' : '[data-cancel]') });
	var ok = $('#cfOk', md);
	if (ok) ok.onclick = function () {
		if (o.escribir && $('#cfTxt').value.trim().toUpperCase() !== o.escribir) { errEn($('#cfTxt'), 'Escribe ' + o.escribir + ' tal cual.'); $('#cfTxt').focus(); return; }
		if (o.validar && !o.validar(md)) return;
		ocupado(ok, true);
		o.ok(md, function () { cierraModal(); }, function () { ocupado(ok, false); });
	};
	return md;
}
function ocupado(btn, on) { if (!btn) return; btn.disabled = on; btn.classList.toggle('cc-btn--cargando', on); }

function toast(tipo, tit, msg) {
	var c = { exito: ['check', 'cc-tono-exito'], error: ['error', 'cc-tono-peligro'], info: ['info', 'cc-tono-mar'], alerta: ['alerta', 'cc-tono-alerta'] }[tipo];
	var t = document.createElement('div'); t.className = 'cc-toast'; t.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
	t.innerHTML = '<span class="cc-toast__ico ' + c[1] + '">' + I(c[0]) + '</span><div class="cc-toast__txt"><div class="cc-toast__titulo">' + tit + '</div>' + (msg ? '<div class="cc-toast__msg">' + msg + '</div>' : '') + '</div><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono cc-btn--sm" aria-label="Cerrar aviso">' + I('cerrar') + '</button>';
	var box = $('#toasts'); box.appendChild(t); while (box.children.length > 3) box.firstChild.remove();
	var tm = setTimeout(function () { t.remove(); }, tipo === 'error' ? 9000 : 6000);
	t.onclick = function (e) { if (e.target.closest('[aria-label="Cerrar aviso"]')) { clearTimeout(tm); t.remove(); } };
}

function errEn(el, msg) {
	if (!el) return !msg;
	var f = el.closest('.cc-field'), s = f && $('.cc-error', f);
	el.classList.toggle('cc-input--error', !!msg); el.setAttribute('aria-invalid', String(!!msg));
	if (s) { s.hidden = !msg; s.innerHTML = msg ? I('error') + '<span>' + esc(msg) + '</span>' : ''; }
	return !msg;
}
function campo(lbl, inner, o) {
	o = o || {};
	return '<label class="cc-field' + (o.cls ? ' ' + o.cls : '') + '"' + (o.attr || '') + '><span class="cc-label">' + lbl + (o.req ? '<span class="cc-req">*</span>' : '') + (o.opc ? ' <span class="cc-muted" style="font-weight:400">· opcional</span>' : '') + '</span>' + inner + (o.ayuda ? '<span class="cc-ayuda">' + o.ayuda + '</span>' : '') + '<span class="cc-error" hidden></span></label>';
}
function opciones(lista, sel, ph) {
	return (ph != null ? '<option value="">' + esc(ph) + '</option>' : '') + lista.map(function (x) { var v = typeof x === 'object' ? x[0] : x, t = typeof x === 'object' ? x[1] : x; return '<option value="' + esc(v) + '"' + (String(v) === String(sel) ? ' selected' : '') + '>' + esc(t) + '</option>'; }).join('');
}
/* Petición JSON con manejo uniforme de sesión vencida */
function api(url, datos, metodo) {
	return jQuery.ajax({ url: url, type: metodo || (datos ? 'POST' : 'GET'), data: datos || {}, dataType: 'json' }).fail(function (x) {
		if (x.status === 0) toast('error', 'Sin conexión con el servidor', 'Revisa la red e inténtalo de nuevo.');
		else if (x.responseText && /<html|<!DOCTYPE/i.test(x.responseText)) toast('error', 'Tu sesión expiró', 'Vuelve a iniciar sesión.');
	});
}
function parseMonto(s) { s = String(s == null ? '' : s).replace(/[\s$€US]/g, '').replace(/,/g, ''); if (!s) return null; var v = Number(s); return isFinite(v) ? v : NaN; }

/* ---------- Equipos: lista del hotel (procesos/equipos/listarEquipos.php), miniatura, tarjeta y buscador ---------- */
var equiposCache = null;
function equipos(recargar) {
	if (!equiposCache || recargar) equiposCache = api('../procesos/equipos/listarEquipos.php', null, 'GET').then(function (j) { return (j && j.equipos) || []; });
	return equiposCache;
}
var TIPOS_IC = [[/laptop|portatil|notebook/, 'laptop', 'tn-mar'], [/monitor|pantalla/, 'monitor', 'tn-mar'], [/impresora|multifuncional|escaner/, 'impresora', 'tn-neu'], [/telefono|conmutador/, 'telefono', 'tn-neu'],
	[/tablet|ipad/, 'tablet', 'tn-mar'], [/television|tv\b|smart/, 'tv', 'tn-mar'], [/punto de venta|pos\b|terminal|micros/, 'pos', 'tn-dor'], [/refri|minibar|frigobar|congelador/, 'refri', 'tn-exi'],
	[/silla/, 'silla', 'tn-dor'], [/switch|router|access|antena|red\b|firewall/, 'red', 'tn-neu'], [/proyector/, 'proyector', 'tn-mar'], [/escritorio|mesa/, 'escritorio', 'tn-dor'], [/lavadora|secadora/, 'lavadora', 'tn-exi']];
function tipoEq(e) { var d = sinAc(e.desc || ''); for (var i = 0; i < TIPOS_IC.length; i++) if (TIPOS_IC[i][0].test(d)) return TIPOS_IC[i]; return [null, 'equipos', 'tn-mar']; }
function thumb(e, tam) {
	var t = tipoEq(e);
	return '<span class="thumb ' + t[2] + '"' + (tam ? ' style="width:' + tam + 'px;height:' + tam + 'px"' : '') + ' aria-hidden="true">' + (e.foto ? '<img src="' + esc(e.foto) + '" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">' : I(t[1])) + '</span>';
}
var EST_N = { activo: 'Activo', mantenimiento: 'En mantenimiento', reparacion: 'En reparación', baja: 'Dado de baja' };
function chipEst(e) { return '<span class="cc-chip cc-chip--' + e + '">' + EST_N[e] + '</span>'; }
function eqCard(e, extra, sinCambiar) {
	if (!e) return '';
	return '<div class="eqcard">' + thumb(e, 72) + '<div style="min-width:0"><div class="cc-row" style="gap:8px"><h3>' + esc(e.codigo) + '</h3>' + chipEst(e.estado) + '</div><div class="cc-muted" style="font-size:13px">' + esc(e.desc) + (e.hotel ? ' · ' + esc(e.hotel) : '') + '</div>' +
		'<dl><div><dt>Marca / Modelo</dt><dd>' + esc(e.marca || '—') + '</dd></div><div><dt>Serie</dt><dd class="num">' + esc(e.serie || '—') + '</dd></div><div><dt>Factura</dt><dd class="num">' + esc(e.nfact || '—') + '</dd></div>' + (extra || '') + '</dl></div>' +
		(sinCambiar ? '' : '<button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-quitar-eq>' + I('cerrar') + 'Cambiar</button>') + '</div>';
}
/* escaner(cont, {lista, filtro(e), pick(e), etiqueta(e), vacio, ph, sm}) — busca por código, serie, descripción o marca */
function escaner(cont, o) {
	o = o || {};
	cont.innerHTML = '<div class="scan' + (o.sm ? ' scan--sm' : '') + '">' + I('buscar') + '<input class="cc-input" placeholder="' + esc(o.ph || 'Escanea o escribe el código / número de serie') + '" autocomplete="off" spellcheck="false" role="combobox" aria-expanded="false" aria-autocomplete="list" aria-label="' + esc(o.ph || 'Escanea o escribe el código o número de serie') + '"><span class="scan__hint">Enter ↵</span><div class="sug" hidden role="listbox"></div></div>' + (o.err !== false ? '<span class="cc-error" hidden></span>' : '');
	var inp = $('input', cont), sug = $('.sug', cont), act = 0, vis = [], er = $('.cc-error', cont);
	function lista() { return (o.lista || []).filter(function (e) { return !o.filtro || o.filtro(e); }); }
	function exacto(v) { v = sinAc(String(v).trim()); if (!v) return null; return lista().filter(function (e) { return sinAc(e.codigo) === v || (e.serie && sinAc(e.serie) === v); })[0] || null; }
	function filtra() { var q = sinAc(inp.value.trim()); vis = q ? lista().filter(function (e) { return sinAc([e.codigo, e.serie, e.desc, e.marca, e.modelo].join(' ')).indexOf(q) >= 0; }).slice(0, 8) : []; }
	function marca(t, q) { t = String(t || ''); var k = sinAc(t).indexOf(q); return k < 0 ? esc(t) : esc(t.slice(0, k)) + '<mark>' + esc(t.slice(k, k + q.length)) + '</mark>' + esc(t.slice(k + q.length)); }
	function pinta() {
		filtra(); var q = sinAc(inp.value.trim());
		if (!q) { sug.hidden = true; inp.setAttribute('aria-expanded', 'false'); return; }
		act = Math.min(act, Math.max(0, vis.length - 1));
		sug.innerHTML = vis.length ? vis.map(function (e, i) { return '<button type="button" class="sug__it' + (i === act ? ' act' : '') + '" role="option" data-id="' + e.id + '">' + thumb(e) + '<span style="min-width:0"><b>' + marca(e.codigo, q) + '</b><small>' + esc(e.desc) + ' · ' + esc(e.marca || '') + ' · ' + marca(e.serie || '', q) + '</small></span>' + (o.etiqueta ? o.etiqueta(e) : '<span class="cc-small cc-muted">' + esc(e.depto || e.hotel || '') + '</span>') + '</button>'; }).join('')
			: '<div class="sug__vacio">Ningún equipo coincide con “' + esc(inp.value) + '”' + (o.vacio ? '. ' + esc(o.vacio) : '.') + '</div>';
		sug.hidden = false; inp.setAttribute('aria-expanded', 'true');
	}
	function elige(e) { if (!e) return; sug.hidden = true; inp.setAttribute('aria-expanded', 'false'); if (er) { er.hidden = true; inp.classList.remove('cc-input--error'); } var r = o.pick(e); inp.value = r === false ? e.codigo : ''; }
	function porId(id) { id = +id; return lista().filter(function (e) { return e.id === id; })[0]; }
	inp.addEventListener('input', function () { act = 0; pinta(); if (er) { er.hidden = true; inp.classList.remove('cc-input--error'); } });
	inp.addEventListener('focus', function () { if (inp.value.trim()) pinta(); });
	inp.addEventListener('keydown', function (e) {
		if (e.key === 'ArrowDown') { e.preventDefault(); act = Math.min(act + 1, vis.length - 1); pinta(); }
		else if (e.key === 'ArrowUp') { e.preventDefault(); act = Math.max(0, act - 1); pinta(); }
		else if (e.key === 'Escape') { sug.hidden = true; }
		else if (e.key === 'Enter') {
			e.preventDefault(); var ex = exacto(inp.value); if (ex) { elige(ex); return; }
			if (vis[act] && !sug.hidden) { elige(vis[act]); return; }
			if (inp.value.trim() && er) { er.hidden = false; inp.classList.add('cc-input--error'); er.innerHTML = I('error') + '<span>No encontramos “' + esc(inp.value.trim()) + '”' + (o.vacio ? '. ' + esc(o.vacio) : '') + '. Revisa el código o busca por número de serie.</span>'; }
		}
	});
	sug.addEventListener('mousedown', function (e) { e.preventDefault(); });
	sug.addEventListener('click', function (e) { var b = e.target.closest('[data-id]'); if (b) elige(porId(b.getAttribute('data-id'))); });
	inp.addEventListener('blur', function () { setTimeout(function () { sug.hidden = true; }, 120); });
	return { input: inp, error: function (m) { if (er) { er.hidden = !m; er.innerHTML = m ? I('error') + '<span>' + esc(m) + '</span>' : ''; inp.classList.toggle('cc-input--error', !!m); } } };
}
/* zonaFoto(cont, {titulo, icono, archivo, cambio(file|null)}) — guarda el File para enviarlo con FormData */
function zonaFoto(cont, o) {
	var archivo = o.archivo || null, url = archivo ? URL.createObjectURL(archivo) : null;
	function pinta() {
		cont.innerHTML = url ? '<div class="prev-img"><img src="' + url + '" alt="' + esc(o.titulo) + '"><button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" data-q>' + I('cerrar') + 'Quitar</button></div>'
			: '<label class="drop" tabindex="0"><span class="drop__ico">' + I(o.icono || 'imagen') + '</span><div><b>' + esc(o.titulo) + '</b><br>Arrastra la imagen o <span class="cc-link">selecciónala</span></div><span class="cc-small">JPG o PNG · hasta 10 MB</span><input type="file" accept="image/jpeg,image/png,image/webp" capture="environment" hidden></label>';
		CostaClara.hidratar(cont);
		var lb = $('label', cont);
		if (lb) {
			var fi = $('input', lb);
			lb.onkeydown = function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fi.click(); } };
			fi.onchange = function () { pon(fi.files[0]); };
			['dragenter', 'dragover'].forEach(function (t) { lb.addEventListener(t, function (e) { e.preventDefault(); lb.classList.add('drop--on'); }); });
			['dragleave', 'drop'].forEach(function (t) { lb.addEventListener(t, function (e) { e.preventDefault(); lb.classList.remove('drop--on'); }); });
			lb.addEventListener('drop', function (e) { pon(e.dataTransfer.files[0]); });
		}
		var q = $('[data-q]', cont); if (q) q.onclick = function () { url = null; archivo = null; o.cambio(null); pinta(); };
	}
	function pon(f) {
		if (!f) return;
		if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { toast('error', 'Formato no válido', '“' + esc(f.name) + '” no es JPG, PNG o WebP.'); return; }
		if (f.size > 10 * 1048576) { toast('error', 'La imagen pesa más de 10 MB', 'Toma la foto con menor resolución.'); return; }
		archivo = f; url = URL.createObjectURL(f); o.cambio(f); pinta();
	}
	pinta();
}
/* Visor de PDF en modal (los reportes aceptan &ver=1 para mostrarse en lugar de descargarse) */
function verPdf(titulo, url) {
	modal('<div class="mod-head"><h2>' + esc(titulo) + '</h2><a class="cc-btn cc-btn--secundario cc-btn--sm" href="' + esc(url) + '" target="_blank" rel="noopener">' + I('descarga') + 'Abrir / descargar</a><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono" data-cancel aria-label="Cerrar">' + I('cerrar') + '</button></div>' +
		'<div class="mod-cuerpo" style="padding:12px;background:var(--superficie-hundida);position:relative"><div class="pdf-cargando" role="status"><i class="giro" aria-hidden="true"></i><b>Generando el PDF…</b><span class="cc-small cc-muted">Los reportes grandes pueden tardar hasta 30 segundos.</span></div>' +
		'<iframe src="' + esc(url + (url.indexOf('?') < 0 ? '?' : '&') + 'ver=1') + '" title="' + esc(titulo) + '" style="display:block;width:100%;height:min(72vh,780px);border:0;border-radius:4px;background:transparent"></iframe></div>', { grande: true, foco: '[data-cancel]' });
	var fr = $('#modal iframe');
	if (fr) fr.onload = function () { var c = $('#modal .pdf-cargando'); if (c) c.remove(); fr.style.background = '#FFFFFF'; };
}

function guarda(k, v) { try { localStorage.setItem('cc-' + k, JSON.stringify(v)); } catch (e) {} }
function lee(k, d) { try { var v = localStorage.getItem('cc-' + k); return v ? JSON.parse(v) : d; } catch (e) { return d; } }

window.CC = { $: $, $$: $$, I: I, esc: esc, num: num, dinero: dinero, sinAc: sinAc, f2: f2, iso: iso, fcorta: fcorta, flarga: flarga,
	modal: modal, cierraModal: cierraModal, alCerrarModal: function (f) { alCerrar = f; }, confirmar: confirmar, ocupado: ocupado, toast: toast,
	errEn: errEn, campo: campo, opciones: opciones, api: api, guarda: guarda, lee: lee, parseMonto: parseMonto,
	equipos: equipos, thumb: thumb, chipEst: chipEst, eqCard: eqCard, escaner: escaner, zonaFoto: zonaFoto, verPdf: verPdf };
})();
