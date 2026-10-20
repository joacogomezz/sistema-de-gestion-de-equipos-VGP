/* Pantalla Resguardos (Costa Clara): datos de procesos/resguardos/listarResguardos.php y
   altas/bajas sobre los procesos existentes (guardarResguardo, guardarResguardosMultiples,
   eliminarResguardoOGrupo y subirResguardoFirmado). */
(function () {
'use strict';
var I = CostaClara.icono;
var CFG = window.RSG_CONFIG || {};
var MAX_MULTIPLE = 10;
function $(s, r) { return (r || document).querySelector(s); }
function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
function num(n) { return Number(n).toLocaleString('es-MX'); }
function sinAc(s) { return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }
function guarda(k, v) { try { localStorage.setItem('cc-resg-' + k, JSON.stringify(v)); } catch (e) {} }
function lee(k, d) { try { var v = localStorage.getItem('cc-resg-' + k); return v ? JSON.parse(v) : d; } catch (e) { return d; } }
function f2(n) { return (n < 10 ? '0' : '') + n; }
function iso(d) { return d.getFullYear() + '-' + f2(d.getMonth() + 1) + '-' + f2(d.getDate()); }
var MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
function fcorta(s) { if (!s) return '—'; var p = String(s).slice(0, 10).split('-'); if (p.length < 3) return esc(s); return +p[2] + ' ' + MESES[+p[1] - 1] + ' ' + p[0]; }
function dias(a, b) { return Math.max(0, Math.round((new Date(String(b).slice(0, 10) + 'T12:00:00') - new Date(String(a).slice(0, 10) + 'T12:00:00')) / 864e5)); }
function folio(r) { var s = String(r.id); while (s.length < 4) s = '0' + s; return 'RSG-' + s; }

var DATA = [], EQUIPOS = null, HOY = iso(new Date());
var S = { hotel: '', area: '', firma: '', desde: '', hasta: '', q: '', ord: { k: 'id', d: -1 }, pag: 1, size: lee('size', 25), exp: {} };
var lista = [];

/* ---------- Datos ---------- */
function cargar(despues) {
	return jQuery.getJSON('../procesos/resguardos/listarResguardos.php').done(function (j) {
		DATA = j.resguardos || []; HOY = j.hoy || HOY;
		DATA.forEach(function (r) { r.folio = folio(r); });
		listasSugeridas(); render(); if (despues) despues();
	}).fail(function () {
		$('#tbody').innerHTML = '<tr><td colspan="12"><div class="vacio"><b>No se pudieron cargar los resguardos</b><span class="cc-muted">Revisa tu conexión y recarga la página.</span></div></td></tr>';
		$('#resumen').textContent = 'Error al cargar';
	});
}
function cargarEquipos() {
	if (EQUIPOS) return jQuery.Deferred().resolve(EQUIPOS).promise();
	return jQuery.getJSON('../procesos/equipos/listarEquipos.php').then(function (j) { EQUIPOS = (j.equipos || []).filter(function (e) { return e.estado !== 'baja'; }); return EQUIPOS; });
}
function listasSugeridas() {
	var resp = {}, areas = {};
	DATA.forEach(function (r) { if (r.resp) resp[r.resp] = 1; if (r.area) areas[r.area] = 1; });
	$('#listaResp').innerHTML = Object.keys(resp).sort().map(function (v) { return '<option value="' + esc(v) + '">'; }).join('');
	$('#listaAreas').innerHTML = Object.keys(areas).sort().map(function (v) { return '<option value="' + esc(v) + '">'; }).join('');
}
function porId(id) { id = +id; for (var i = 0; i < DATA.length; i++) if (DATA[i].id === id) return DATA[i]; return null; }
function ultimoResguardoDe(idEquipo) { for (var i = 0; i < DATA.length; i++) for (var k = 0; k < DATA[i].eqs.length; k++) if (DATA[i].eqs[k].idEquipo === idEquipo) return DATA[i]; return null; }

function filtrados(sinFirma) {
	var q = sinAc(S.q.trim());
	var out = DATA.filter(function (r) {
		if (S.hotel && String(r.idHotel) !== S.hotel) return false;
		if (S.area && r.area !== S.area) return false;
		if (!sinFirma && S.firma && (S.firma === 'ok') !== r.firmado) return false;
		if (S.desde && r.fecha < S.desde) return false;
		if (S.hasta && r.fecha > S.hasta) return false;
		if (q && sinAc([r.folio, r.id, r.resp, r.area, r.puesto, r.factura].concat(r.eqs.map(function (e) { return e.codigo + ' ' + e.serie + ' ' + e.modelo; })).join(' ')).indexOf(q) < 0) return false;
		return true;
	});
	if (sinFirma) return out;
	var o = S.ord;
	out.sort(function (a, b) { var x = a[o.k], y = b[o.k]; if (typeof x === 'string') { x = sinAc(x); y = sinAc(y); } return ((x < y ? -1 : x > y ? 1 : 0) * o.d) || (b.id - a.id); });
	return out;
}

/* ---------- Celdas ---------- */
function tipoChip(r) { return r.eqs.length > 1 ? '<span class="tipo tipo--mul">' + I('bajas-masivas') + 'Múltiple · ' + r.eqs.length + ' equipos</span>' : '<span class="tipo tipo--ind">' + I('equipos') + 'Individual</span>'; }
function firmaChip(r, sub) {
	if (r.firmado) return '<span class="firma firma--ok">' + I('check') + 'Firmado</span>' + (sub ? '<span class="firma-sub">' + fcorta(r.ffirma) + '</span>' : '');
	var d = dias(r.registro || r.fecha, HOY);
	return '<span class="firma firma--pend">' + I('reloj') + 'Pendiente de firma</span>' + (sub ? '<span class="firma-sub' + (d > 7 ? ' tarde' : '') + '">' + (d === 0 ? 'Generado hoy' : 'Hace ' + d + (d === 1 ? ' día' : ' días')) + '</span>' : '');
}
function varios(r, campo) {
	if (r.eqs.length === 1) return r.eqs[0][campo] ? esc(r.eqs[0][campo]) : '<span class="mut">—</span>';
	var vs = {}; r.eqs.forEach(function (e) { vs[e[campo]] = 1; }); var k = Object.keys(vs);
	if (k.length === 1 && campo !== 'serie') return esc(k[0]) + ' <span class="mas">×' + r.eqs.length + '</span>';
	return '<span class="mut">Varios (' + r.eqs.length + ')</span>';
}
var COLS = [
	{ k: 'tipo', l: 'Tipo', fix: 'c-tipo' }, { k: 'folio', l: 'Folio', ord: 'id', fix: 'c-folio' }, { k: 'codigos', l: 'Código(s) de equipo' },
	{ k: 'area', l: 'Área de destino', ord: 'area' }, { k: 'resp', l: 'Usuario responsable', ord: 'resp' }, { k: 'fecha', l: 'Fecha', ord: 'fecha' },
	{ k: 'factura', l: 'Factura' }, { k: 'modelo', l: 'Modelo' }, { k: 'serie', l: 'Serie' }, { k: 'tequipo', l: 'Tipo de equipo' }, { k: 'firma', l: 'PDF firmado', ord: 'firmado' }
];
function celda(r, k) {
	switch (k) {
		case 'tipo': return (r.eqs.length > 1 ? '<button type="button" class="exp" data-exp="' + r.id + '" aria-expanded="' + !!S.exp[r.id] + '" aria-label="' + (S.exp[r.id] ? 'Ocultar' : 'Mostrar') + ' equipos de ' + r.folio + '">' + I('derecha') + '</button>' : '<span class="exp-vacio"></span>') + tipoChip(r);
		case 'folio': return '<b>' + r.folio + '</b>';
		case 'codigos': return '<span class="num">' + esc(r.eqs[0].codigo) + '</span>' + (r.eqs.length > 1 ? '<span class="mas">+' + (r.eqs.length - 1) + '</span>' : '');
		case 'area': return r.area ? '<span title="' + esc(r.area) + '">' + esc(r.area) + '</span>' : '<span class="mut">—</span>';
		case 'resp': return esc(r.resp) + (r.puesto ? '<span class="firma-sub">' + esc(r.puesto) + '</span>' : '');
		case 'fecha': return '<span class="num">' + fcorta(r.fecha) + '</span>';
		case 'factura': return r.factura ? '<span class="num">' + esc(r.factura) + '</span>' : '<span class="mut">—</span>';
		case 'modelo': return varios(r, 'modelo');
		case 'serie': return r.eqs.length === 1 ? '<span class="num mut">' + (esc(r.eqs[0].serie) || '—') + '</span>' : varios(r, 'serie');
		case 'tequipo': return varios(r, 'tipo');
		case 'firma': return firmaChip(r, true);
	}
	return '';
}
function acciones(r) {
	return '<span class="accs"><button type="button" class="acc-btn" data-a="pdf" data-id="' + r.id + '" title="Ver e imprimir PDF" aria-label="Ver PDF de ' + r.folio + '">' + I('impresora') + '</button>' +
		'<button type="button" class="acc-btn" data-a="subir" data-id="' + r.id + '" title="' + (r.firmado ? 'Reemplazar PDF firmado' : 'Subir PDF firmado') + '" aria-label="' + (r.firmado ? 'Reemplazar' : 'Subir') + ' PDF firmado de ' + r.folio + '"' + (r.firmado ? '' : ' style="color:var(--dorado-texto)"') + '>' + I('subir') + '</button>' +
		'<button type="button" class="acc-btn" data-a="eliminar" data-id="' + r.id + '" title="Eliminar" aria-label="Eliminar ' + r.folio + '" style="color:var(--peligro)">' + I('baja') + '</button></span>';
}

/* ---------- Render ---------- */
function pagsVis(a, t) { if (t <= 7) { var r = []; for (var i = 1; i <= t; i++) r.push(i); return r; } if (a <= 4) return [1, 2, 3, 4, 5, '…', t]; if (a >= t - 3) return [1, '…', t - 4, t - 3, t - 2, t - 1, t]; return [1, '…', a - 1, a, a + 1, '…', t]; }
function render() {
	var base = filtrados(true), nOk = base.filter(function (r) { return r.firmado; }).length, nP = base.length - nOk;
	$('#estFirma').innerHTML = [['', 'Todos', base.length, ''], ['pend', 'Pendiente de firma', nP, 'var(--alerta)'], ['ok', 'Firmado', nOk, 'var(--exito)']].map(function (b) {
		return '<button type="button" data-firma="' + b[0] + '" aria-pressed="' + (S.firma === b[0]) + '">' + (b[3] ? '<span class="dot" style="background:' + b[3] + '"></span>' : '') + b[1] + ' <b>' + num(b[2]) + '</b></button>';
	}).join('');
	lista = filtrados();
	var tot = lista.length, pags = Math.max(1, Math.ceil(tot / S.size)); if (S.pag > pags) S.pag = pags;
	var ini = (S.pag - 1) * S.size, pg = lista.slice(ini, ini + S.size);
	$('#contador').innerHTML = '<b>' + num(tot) + '</b>' + (tot === DATA.length ? 'resguardos' : 'de ' + num(DATA.length));
	$('#thead').innerHTML = COLS.map(function (c) {
		var o = c.ord && S.ord.k === c.ord;
		return '<th class="' + (c.fix ? 'fix ' + c.fix : '') + (c.ord ? ' ord' : '') + '"' + (c.ord ? ' data-ord="' + c.ord + '" tabindex="0" aria-sort="' + (o ? (S.ord.d > 0 ? 'ascending' : 'descending') : 'none') + '"' : '') + '>' + c.l + (o ? '<span class="flecha">' + (S.ord.d > 0 ? '▲' : '▼') + '</span>' : '') + '</th>';
	}).join('') + '<th class="c-acc" style="width:110px">Acciones</th>';
	if (!tot) {
		var v = DATA.length ? '<div class="vacio"><b style="font-size:15px">No hay resguardos con esos filtros</b><span class="cc-muted">Prueba con otra área o fecha, o busca por el código del equipo.</span><button type="button" class="cc-btn cc-btn--secundario" data-limpiar style="margin-top:8px">Limpiar filtros</button></div>'
			: '<div class="vacio"><b style="font-size:15px">Todavía no hay resguardos</b><span class="cc-muted">Genera el primero con “Nuevo resguardo”.</span></div>';
		$('#tbody').innerHTML = '<tr><td colspan="' + (COLS.length + 1) + '" style="height:auto;cursor:default">' + v + '</td></tr>';
		$('#tarjetas').innerHTML = '<div class="cc-card">' + v + '</div>';
	} else {
		$('#tbody').innerHTML = pg.map(function (r) {
			var h = '<tr data-row="' + r.id + '">' + COLS.map(function (c) { return '<td class="c-' + c.k + (c.fix ? ' fix ' + c.fix : '') + '">' + celda(r, c.k) + '</td>'; }).join('') + '<td class="c-acc" style="width:110px">' + acciones(r) + '</td></tr>';
			if (S.exp[r.id]) h += r.eqs.map(function (e, i) {
				return '<tr class="sub"><td class="fix c-tipo" style="padding-left:40px">Equipo ' + (i + 1) + ' de ' + r.eqs.length + '</td><td class="fix c-folio"></td><td><b class="num">' + esc(e.codigo) + '</b></td><td colspan="4"></td><td>' + esc(e.modelo) + '</td><td class="num">' + esc(e.serie) + '</td><td>' + esc(e.tipo) + '</td><td>' + (e.firmado ? '<span class="firma firma--ok">' + I('check') + 'Firmado</span>' : '<span class="firma firma--pend">' + I('reloj') + 'Pendiente</span>') + '</td><td></td></tr>';
			}).join('');
			return h;
		}).join('');
		$('#tarjetas').innerHTML = pg.map(function (r) {
			return '<article class="tj" data-row="' + r.id + '" style="grid-template-columns:minmax(0,1fr)"><div><div class="tj__fila"><span class="tj__cod">' + r.folio + '</span>' + firmaChip(r, false) + '</div>' +
				'<div class="tj__desc">' + (r.eqs.length > 1 ? r.eqs.length + ' equipos · ' + esc(r.eqs[0].codigo) + ' y ' + (r.eqs.length - 1) + ' más' : esc(r.eqs[0].tipo || r.eqs[0].codigo) + (r.eqs[0].modelo ? ' · ' + esc(r.eqs[0].modelo) : '')) + '</div>' +
				'<div class="tj__meta">' + esc(r.resp) + (r.area ? ' · ' + esc(r.area) : '') + '</div><div class="tj__meta">' + (CFG.superAdmin && r.hotel ? esc(r.hotel) + ' · ' : '') + fcorta(r.fecha) + (r.firmado ? ' · firmado ' + fcorta(r.ffirma) : '') + '</div>' +
				'<div class="tj__pie">' + tipoChip(r) + acciones(r) + '</div></div></article>';
		}).join('');
	}
	$('#resumen').textContent = tot ? 'Mostrando ' + num(ini + 1) + '–' + num(Math.min(ini + S.size, tot)) + ' de ' + num(tot) : 'Sin resultados';
	$('#rango').textContent = tot ? 'Página ' + S.pag + ' de ' + pags : '';
	var p = '<button type="button" data-pag="' + (S.pag - 1) + '" aria-label="Página anterior"' + (S.pag <= 1 ? ' disabled' : '') + '>' + I('izquierda') + '</button>';
	pagsVis(S.pag, pags).forEach(function (n) { p += n === '…' ? '<span style="padding:0 4px">…</span>' : '<button type="button" data-pag="' + n + '" class="' + (n === S.pag ? 'on' : '') + '">' + n + '</button>'; });
	p += '<button type="button" data-pag="' + (S.pag + 1) + '" aria-label="Página siguiente"' + (S.pag >= pags ? ' disabled' : '') + '>' + I('derecha') + '</button>';
	$('#pags').innerHTML = p; $('#size').value = String(S.size);
	etiquetas();
}
function nombreHotel(id) { for (var i = 0; i < DATA.length; i++) if (String(DATA[i].idHotel) === String(id)) return DATA[i].hotel; return ''; }
function etiquetas() {
	var v = { hotel: S.hotel ? nombreHotel(S.hotel) : 'Todos', area: S.area || 'Todas', fecha: (S.desde || S.hasta) ? ((S.desde ? fcorta(S.desde) : '…') + ' – ' + (S.hasta ? fcorta(S.hasta) : 'hoy')) : 'Cualquiera' };
	var on = { hotel: !!S.hotel, area: !!S.area, fecha: !!(S.desde || S.hasta) };
	$$('.fbtn').forEach(function (b) { var f = b.getAttribute('data-f'); $('[data-v]', b).textContent = v[f]; b.classList.toggle('fbtn--on', on[f]); });
	$('#limpiar').hidden = !(on.hotel || on.area || on.fecha || S.firma || S.q);
}

/* ---------- Filtros ---------- */
var pop = null;
function cierraPop() { if (pop) { pop.el.remove(); pop.btn.setAttribute('aria-expanded', 'false'); pop = null; } }
function abrePop(btn, html, w, der) {
	cierraPop();
	var el = document.createElement('div'); el.className = 'pop'; el.innerHTML = html; if (w) el.style.width = w + 'px';
	document.body.appendChild(el); CostaClara.hidratar(el);
	var r = btn.getBoundingClientRect(), x = der ? r.right - el.offsetWidth : r.left; x = Math.max(12, Math.min(x, innerWidth - el.offsetWidth - 12));
	var y = r.bottom + 6; if (y + el.offsetHeight > innerHeight - 12 && r.top - el.offsetHeight - 6 > 12) y = r.top - el.offsetHeight - 6;
	el.style.left = x + 'px'; el.style.top = y + 'px'; btn.setAttribute('aria-expanded', 'true'); pop = { el: el, btn: btn };
	var f = $('input,button', el); if (f) f.focus();
	return el;
}
function opt(v, t, on, n) { return '<button type="button" class="opt' + (on ? ' opt--on' : '') + '" data-val="' + esc(v) + '"><span class="tick">' + (on ? I('check') : '') + '</span><span>' + esc(t) + '</span>' + (n != null ? '<span class="n">' + num(n) + '</span>' : '') + '</button>'; }
function cuenta(campo) {
	var bak = S[campo]; S[campo] = ''; var c = {};
	filtrados(true).forEach(function (r) { if (!S.firma || (S.firma === 'ok') === r.firmado) { var k = campo === 'hotel' ? String(r.idHotel) : r[campo]; c[k] = (c[k] || 0) + 1; } });
	S[campo] = bak; return c;
}
var FIL = {
	hotel: function (b) {
		var c = cuenta('hotel'), hs = {}; DATA.forEach(function (r) { if (r.idHotel) hs[r.idHotel] = r.hotel; });
		var el = abrePop(b, '<div class="pop__lista">' + opt('', 'Todos los hoteles', !S.hotel) + Object.keys(hs).sort(function (a, z) { return hs[a].localeCompare(hs[z], 'es'); }).map(function (id) { return opt(id, hs[id], S.hotel === id, c[id] || 0); }).join('') + '</div>', 250);
		el.onclick = function (e) { var o = e.target.closest('.opt'); if (o) { S.hotel = o.getAttribute('data-val'); S.pag = 1; cierraPop(); render(); } };
	},
	area: function (b) {
		var c = cuenta('area'), areas = Object.keys(c).filter(Boolean).sort(function (a, z) { return a.localeCompare(z, 'es'); });
		var el = abrePop(b, '<div class="pop__buscar"><label class="cc-buscador"><i data-i="buscar"></i><input class="cc-input" placeholder="Buscar entre ' + areas.length + ' áreas" aria-label="Buscar área"></label></div><div class="pop__lista" data-l></div>', 300), inp = $('input', el), l = $('[data-l]', el);
		function pinta() { var q = sinAc(inp.value.trim()), as = areas.filter(function (a) { return !q || sinAc(a).indexOf(q) >= 0; }); l.innerHTML = (q ? '' : opt('', 'Todas las áreas', !S.area)) + (as.length ? as.map(function (a) { return opt(a, a, S.area === a, c[a]); }).join('') : '<div class="pop__vacio">Ninguna área coincide.</div>'); }
		pinta(); inp.oninput = pinta; inp.onkeydown = function (e) { if (e.key === 'Enter') { var o = $('.opt', l); if (o) o.click(); } };
		l.onclick = function (e) { var o = e.target.closest('.opt'); if (o) { S.area = o.getAttribute('data-val'); S.pag = 1; cierraPop(); render(); } };
	},
	fecha: function (b) {
		var hoy = new Date(HOY + 'T12:00:00'), lunes = new Date(hoy); lunes.setDate(hoy.getDate() - ((hoy.getDay() + 6) % 7));
		var tres = new Date(hoy); tres.setMonth(tres.getMonth() - 3); var y = hoy.getFullYear();
		var pres = [['Esta semana', iso(lunes), HOY], ['Este mes', y + '-' + f2(hoy.getMonth() + 1) + '-01', HOY], ['Últimos 3 meses', iso(tres), HOY], ['Este año', y + '-01-01', HOY], [String(y - 1), (y - 1) + '-01-01', (y - 1) + '-12-31']];
		var el = abrePop(b, '<div class="presets">' + pres.map(function (p, i) { return '<button type="button" class="cc-filtro" data-p="' + i + '">' + p[0] + '</button>'; }).join('') + '</div>' +
			'<div class="fechas"><label class="cc-field" style="margin:0"><span class="cc-label">Desde</span><input type="date" class="cc-input" id="fD" value="' + S.desde + '" max="' + HOY + '"></label><label class="cc-field" style="margin:0"><span class="cc-label">Hasta</span><input type="date" class="cc-input" id="fH" value="' + S.hasta + '" max="' + HOY + '"></label></div>' +
			'<div class="cc-error" id="fE" hidden style="padding:0 6px 6px"></div><div class="pop__pie"><button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-x>Quitar</button><button type="button" class="cc-btn cc-btn--primario cc-btn--sm" data-ok>Aplicar</button></div>', 330, true);
		el.onclick = function (e) {
			var p = e.target.closest('[data-p]'); if (p) { var v = pres[+p.getAttribute('data-p')]; $('#fD').value = v[1]; $('#fH').value = v[2]; return; }
			if (e.target.closest('[data-x]')) { S.desde = S.hasta = ''; S.pag = 1; cierraPop(); render(); }
			if (e.target.closest('[data-ok]')) { var a = $('#fD').value, z = $('#fH').value; if (a && z && a > z) { var er = $('#fE'); er.hidden = false; er.innerHTML = I('error') + '“Desde” debe ser anterior a “Hasta”.'; return; } S.desde = a; S.hasta = z; S.pag = 1; cierraPop(); render(); }
		};
	}
};
$$('.fbtn').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); b.onclick = function (e) { e.stopPropagation(); if (pop && pop.btn === b) { cierraPop(); return; } FIL[b.getAttribute('data-f')](b); }; });
document.addEventListener('click', function (e) { if (pop && !pop.el.contains(e.target)) cierraPop(); });
addEventListener('resize', cierraPop);
document.addEventListener('scroll', function (e) { if (pop && !pop.el.contains(e.target)) cierraPop(); }, true);
document.addEventListener('keydown', function (e) {
	if (e.key === 'Escape') { if (!$('#modal').hidden) { cierraModal(); return; } if (pop) { var b = pop.btn; cierraPop(); b.focus(); } }
	if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) { e.preventDefault(); $('#q').focus(); }
});
$('#estFirma').onclick = function (e) { var b = e.target.closest('[data-firma]'); if (b) { S.firma = b.getAttribute('data-firma'); S.pag = 1; render(); } };
var tq; $('#q').oninput = function () { var v = this.value; clearTimeout(tq); tq = setTimeout(function () { S.q = v; S.pag = 1; render(); }, 150); };
function limpia() { S.hotel = S.area = S.firma = S.desde = S.hasta = S.q = ''; S.pag = 1; $('#q').value = ''; render(); }
$('#limpiar').onclick = limpia;
$('#thead').onclick = function (e) { var th = e.target.closest('[data-ord]'); if (!th) return; var k = th.getAttribute('data-ord'); S.ord = S.ord.k === k ? { k: k, d: -S.ord.d } : { k: k, d: (k === 'id' || k === 'fecha') ? -1 : 1 }; render(); };
$('#thead').onkeydown = function (e) { var th = e.target.closest('[data-ord]'); if (th && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); th.click(); } };
$('#pags').onclick = function (e) { var b = e.target.closest('[data-pag]'); if (!b || b.disabled) return; S.pag = +b.getAttribute('data-pag'); render(); $('.tabla-card').scrollIntoView({ block: 'start', behavior: 'smooth' }); };
$('#size').onchange = function () { S.size = +this.value; S.pag = 1; guarda('size', S.size); render(); };
function clic(e) {
	if (e.target.closest('[data-limpiar]')) { limpia(); return; }
	var x = e.target.closest('[data-exp]'); if (x) { var id = x.getAttribute('data-exp'); S.exp[id] = !S.exp[id]; render(); var nb = $('[data-exp="' + id + '"]'); if (nb) nb.focus(); return; }
	var a = e.target.closest('[data-a]'); if (a) { e.stopPropagation(); accion(a.getAttribute('data-a'), porId(a.getAttribute('data-id'))); return; }
	if (e.target.closest('tr.sub')) return;
	var r = e.target.closest('[data-row]'); if (r) verDoc(porId(r.getAttribute('data-row')));
}
$('#tbody').onclick = clic; $('#tarjetas').onclick = clic;

/* ---------- Acciones ---------- */
var subirPara = null;
function accion(a, r) { if (!r) return; if (a === 'pdf') verDoc(r); if (a === 'subir') pedirPdf(r); if (a === 'eliminar') eliminar(r); }
function pedirPdf(r) { subirPara = r; var f = $('#filePdf'); f.value = ''; f.click(); }
$('#filePdf').onchange = function () {
	var f = this.files[0], r = subirPara; if (!f || !r) return;
	if (!/\.pdf$/i.test(f.name)) { toast('error', 'Solo se aceptan archivos PDF', 'Escanea el resguardo firmado como PDF y vuelve a subirlo.'); return; }
	if (f.size > 10 * 1048576) { toast('error', 'El archivo pesa más de 10 MB', 'Escanea a menor resolución e inténtalo de nuevo.'); return; }
	var era = r.firmado;
	toast('info', 'Subiendo PDF firmado…', esc(f.name));
	// El sistema guarda el PDF firmado por equipo: en un resguardo múltiple se adjunta a cada equipo del documento.
	var cadena = jQuery.Deferred().resolve().promise(), fallos = 0;
	r.eqs.forEach(function (e) {
		cadena = cadena.then(function () {
			var fd = new FormData(); fd.append('idEquipo', e.idEquipo); fd.append('resguardoFirmado', f);
			return jQuery.ajax({ type: 'POST', url: '../procesos/equipos/subirResguardoFirmado.php', data: fd, processData: false, contentType: false, dataType: 'json' })
				.then(function (j) { if (!j || !j.ok) fallos++; }, function () { fallos++; return jQuery.Deferred().resolve(); });
		});
	});
	cadena.always(function () {
		cierraModal();
		cargar(function () {
			if (fallos) toast('error', 'No se pudo guardar el PDF en ' + fallos + ' equipo' + (fallos === 1 ? '' : 's'), 'Inténtalo de nuevo.');
			else toast('exito', era ? 'PDF firmado reemplazado' : 'Resguardo ' + r.folio + ' firmado', esc(f.name) + ' · ' + r.eqs.length + (r.eqs.length === 1 ? ' equipo queda' : ' equipos quedan') + ' bajo responsabilidad de ' + esc(r.resp) + '.');
		});
	});
};
function eliminar(r) {
	abreModal('<div class="cc-modal__body"><span class="cc-modal__ico cc-tono-peligro">' + I('baja') + '</span><div class="cc-stack" style="gap:8px;min-width:0"><h2 class="cc-h2">¿Eliminar el resguardo ' + r.folio + '?</h2><p class="cc-muted" style="margin:0">' +
		(r.firmado ? '<b style="color:var(--texto);font-weight:500">Ya está firmado.</b> Elimínalo solo si se capturó por error; para cambiar de responsable genera un resguardo nuevo.' : 'Aún no está firmado.') +
		(r.eqs.length > 1 ? ' Se eliminará el documento completo con sus ' + r.eqs.length + ' equipos.' : '') + ' No se puede deshacer.</p></div></div>' +
		'<div class="cc-modal__pie"><button type="button" class="cc-btn cc-btn--secundario" data-cancel>Cancelar</button><button type="button" class="cc-btn cc-btn--peligro" id="mOk">Eliminar resguardo</button></div>');
	$('#mOk').onclick = function () {
		this.disabled = true;
		jQuery.post('../procesos/resguardos/eliminarResguardoOGrupo.php', { id_resguardo: r.id }, null, 'json').done(function (j) {
			cierraModal();
			if (j && j.ok) cargar(function () { toast('exito', 'Resguardo ' + r.folio + ' eliminado', j.eliminados > 1 ? j.eliminados + ' equipos liberados.' : ''); });
			else toast('error', 'No se pudo eliminar el resguardo', '¿La sesión expiró? Recarga la página.');
		}).fail(function () { cierraModal(); toast('error', 'Error de conexión', 'No se eliminó el resguardo.'); });
	};
}

/* ---------- Visor del documento ---------- */
function verDoc(r) {
	if (!r) return;
	var conFirma = r.eqs.filter(function (e) { return e.firmado; })[0];
	var urlGenerado = '../reportes/imprimirResguardo.php?id=' + r.id + '&ver=1';
	var urlFirmado = conFirma ? '../archivos/resguardos_firmados/equipo_' + conFirma.idEquipo + '.pdf?v=' + encodeURIComponent(r.ffirma || HOY) : '';
	abreModal('<div class="mod-head"><h2>' + r.folio + ' · ' + (r.eqs.length > 1 ? 'Resguardo múltiple' : 'Resguardo individual') + '</h2>' + firmaChip(r, false) +
		(urlFirmado ? '<div class="pestanas" role="group" aria-label="Documento a mostrar"><button type="button" data-ver="firmado" aria-pressed="true">PDF firmado</button><button type="button" data-ver="generado" aria-pressed="false">Documento generado</button></div>' : '') +
		'<button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono" data-cancel aria-label="Cerrar">' + I('cerrar') + '</button></div>' +
		'<div class="mod-cuerpo"><iframe id="visor" title="Documento ' + r.folio + '" src="' + esc(urlFirmado || urlGenerado) + '"></iframe></div>' +
		'<div class="mod-pie"><span class="cc-small cc-muted">' + esc(r.resp) + (r.area ? ' · ' + esc(r.area) : '') + ' · ' + fcorta(r.fecha) + (r.firmado ? ' · firmado el ' + fcorta(r.ffirma) : ' · imprime, recaba las firmas y sube el documento escaneado') + '</span>' +
		'<div class="cc-row" style="gap:8px"><button type="button" class="cc-btn cc-btn--fantasma" data-act="eliminar" style="color:var(--peligro)">' + I('baja') + 'Eliminar</button>' +
		'<button type="button" class="cc-btn cc-btn--secundario" data-act="subir">' + I('subir') + (r.firmado ? 'Reemplazar firmado' : 'Subir firmado') + '</button>' +
		'<a class="cc-btn cc-btn--primario" href="../reportes/imprimirResguardo.php?id=' + r.id + '" target="_blank" rel="noopener">' + I('descarga') + 'Descargar PDF</a></div></div>', true);
	var cab = $('#modal .pestanas');
	if (cab) cab.onclick = function (e) { var b = e.target.closest('[data-ver]'); if (!b) return; $$('button', cab).forEach(function (x) { x.setAttribute('aria-pressed', x === b); }); $('#visor').src = b.getAttribute('data-ver') === 'firmado' ? urlFirmado : urlGenerado; };
	$('#modal .mod-pie').onclick = function (e) { var b = e.target.closest('[data-act]'); if (!b) return; var a = b.getAttribute('data-act'); if (a === 'subir') pedirPdf(r); if (a === 'eliminar') { cierraModal(); eliminar(r); } };
}

/* ---------- Nuevo resguardo / múltiple ---------- */
function formRes(mult, codigosIniciales, hotelInicial) {
	cargarEquipos().done(function () { abreFormRes(mult, codigosIniciales || [], hotelInicial || ''); })
		.fail(function () { toast('error', 'No se pudo cargar la lista de equipos', 'Recarga la página e inténtalo de nuevo.'); });
}
function abreFormRes(mult, codigosIniciales, hotelInicial) {
	var sel = [], hoteles = {};
	EQUIPOS.forEach(function (e) { if (e.idHotel) hoteles[e.idHotel] = e.hotel; });
	var idsHotel = Object.keys(hoteles).sort(function (a, z) { return hoteles[a].localeCompare(hoteles[z], 'es'); });
	var hotel = String(hotelInicial || S.hotel || (CFG.superAdmin ? (idsHotel[0] || '') : ''));
	abreModal('<div class="mod-head"><h2>' + (mult ? 'Resguardo múltiple' : 'Nuevo resguardo') + '</h2><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono" data-cancel aria-label="Cerrar">' + I('cerrar') + '</button></div>' +
		'<form class="mform" id="fr" novalidate><p class="cc-muted" style="margin:0">' + (mult ? 'Un solo documento para varios equipos que recibe la misma persona. De 2 a ' + MAX_MULTIPLE + ' equipos (cabe en una hoja).' : 'Asigna un equipo a la persona que lo recibe. Después imprime el PDF para firma.') + '</p>' +
		'<div class="g2">' + (CFG.superAdmin ? '<label class="cc-field"><span class="cc-label">Hotel</span><select class="cc-select" id="rHotel">' + idsHotel.map(function (id) { return '<option value="' + id + '"' + (id === hotel ? ' selected' : '') + '>' + esc(hoteles[id]) + '</option>'; }).join('') + '</select></label>' : '') +
		'<label class="cc-field"><span class="cc-label">Fecha<span class="cc-req">*</span></span><input type="date" class="cc-input" id="rFecha" value="' + HOY + '"></label></div>' +
		'<div class="cc-field"><span class="cc-label">' + (mult ? 'Equipos' : 'Equipo') + '<span class="cc-req">*</span> <span class="cc-muted" id="rN" style="font-weight:400"></span></span><label class="cc-buscador"><i data-i="buscar"></i><input class="cc-input" id="rBus" placeholder="Busca por código, descripción, modelo o serie"></label><div class="selchips" id="rChips"></div><div class="eqlist" id="rLista" role="listbox" aria-multiselectable="' + mult + '"></div><span class="cc-error" id="eEq" hidden></span></div>' +
		'<div class="g2"><label class="cc-field"><span class="cc-label">Usuario responsable<span class="cc-req">*</span></span><input class="cc-input" id="rResp" list="listaResp" placeholder="Nombre completo de quien recibe" autocomplete="off"><span class="cc-error" id="eResp" hidden></span></label>' +
		'<label class="cc-field"><span class="cc-label">Puesto <span class="cc-muted" style="font-weight:400">· opcional</span></span><input class="cc-input" id="rPuesto" placeholder="Ej. Recepcionista"></label></div>' +
		'<div class="g2"><label class="cc-field"><span class="cc-label">Área de destino<span class="cc-req">*</span></span><input class="cc-input" id="rArea" list="listaAreas" placeholder="Dónde quedará el equipo" autocomplete="off"><span class="cc-error" id="eArea" hidden></span></label>' +
		'<label class="cc-field"><span class="cc-label">Nº de factura' + (mult ? '<span class="cc-req">*</span>' : ' <span class="cc-muted" style="font-weight:400">· opcional</span>') + '</span><input class="cc-input" id="rFac" placeholder="Ej. F-88213"><span class="cc-error" id="eFac" hidden></span></label></div></form>' +
		'<div class="mod-pie"><span class="cc-small cc-muted">Se creará como <b style="color:var(--texto);font-weight:500">Pendiente de firma</b>.</span><div class="cc-row" style="gap:8px"><button type="button" class="cc-btn cc-btn--secundario" data-cancel>Cancelar</button><button type="button" class="cc-btn cc-btn--primario" id="rOk">' + I('check') + '<span>Generar resguardo</span></button></div></div>', true);
	$('#modal .cc-modal').style.width = 'min(680px,100%)';
	function hotelActual() { return CFG.superAdmin ? $('#rHotel').value : ''; }
	function disponibles() { var h = hotelActual(); return EQUIPOS.filter(function (e) { return !h || String(e.idHotel) === h; }); }
	function pinta() {
		var q = sinAc($('#rBus').value.trim());
		var ls = disponibles().filter(function (e) { return !q || sinAc([e.codigo, e.desc, e.marca, e.serie].join(' ')).indexOf(q) >= 0; });
		var total = ls.length; ls = ls.slice(0, 80);
		$('#rLista').innerHTML = ls.length ? ls.map(function (e) {
			var on = sel.indexOf(e) >= 0, ya = ultimoResguardoDe(e.id);
			return '<div class="eqi' + (on ? ' on' : '') + '" role="option" aria-selected="' + on + '" tabindex="0" data-c="' + e.id + '">' +
				(mult ? '<input type="checkbox" class="cb" tabindex="-1"' + (on ? ' checked' : '') + ' aria-hidden="true">' : '<span style="width:16px;display:grid;place-items:center;color:var(--dorado-texto)">' + (on ? I('check') : '') + '</span>') +
				'<span class="thumb" style="width:32px;height:32px">' + (e.foto ? '<img src="' + esc(e.foto) + '" alt="" loading="lazy">' : I('equipos')) + '</span>' +
				'<div style="min-width:0"><b>' + esc(e.codigo) + '</b><small>' + esc(e.desc) + (e.marca ? ' · ' + esc(e.marca) : '') + (e.serie ? ' · ' + esc(e.serie) : '') + '</small></div>' +
				(ya ? '<span class="ya" title="Último resguardo de este equipo">Ya en ' + ya.folio + '</span>' : '') + '</div>';
		}).join('') + (total > ls.length ? '<div class="pop__vacio">Mostrando 80 de ' + num(total) + '. Escribe para acotar la búsqueda.</div>' : '') : '<div class="pop__vacio">No hay equipos que coincidan.</div>';
		$('#rChips').innerHTML = mult ? sel.map(function (e) { return '<span class="selchip">' + esc(e.codigo) + '<button type="button" data-q="' + e.id + '" aria-label="Quitar ' + esc(e.codigo) + '">' + I('cerrar') + '</button></span>'; }).join('') : '';
		$('#rN').textContent = mult ? '· ' + sel.length + ' de ' + MAX_MULTIPLE + ' seleccionados' : '';
		$('#rOk span').textContent = mult && sel.length ? 'Generar resguardo de ' + sel.length + ' equipos' : 'Generar resguardo';
	}
	function precarga(e) {
		if (!$('#rResp').value && e.resp) $('#rResp').value = e.resp;
		if (!$('#rArea').value && e.ubic) $('#rArea').value = e.ubic;
		if (!$('#rFac').value && e.nfact) $('#rFac').value = e.nfact;
	}
	function toggle(id) {
		var e = EQUIPOS.filter(function (x) { return x.id === +id; })[0]; if (!e) return;
		var i = sel.indexOf(e);
		if (mult) { if (i < 0) { if (sel.length >= MAX_MULTIPLE) { err('eEq', 'Máximo ' + MAX_MULTIPLE + ' equipos por resguardo múltiple.'); return; } sel.push(e); precarga(e); } else sel.splice(i, 1); }
		else { sel = i < 0 ? [e] : []; if (i < 0) precarga(e); }
		err('eEq', ''); pinta();
	}
	function err(id, m) { var el = $('#' + id); el.hidden = !m; el.innerHTML = m ? I('error') + m : ''; }
	pinta();
	$('#rBus').oninput = pinta;
	if (CFG.superAdmin) $('#rHotel').onchange = function () { sel = []; pinta(); };
	$('#rLista').onclick = function (e) { var o = e.target.closest('[data-c]'); if (o) toggle(o.getAttribute('data-c')); };
	$('#rLista').onkeydown = function (e) { var o = e.target.closest('[data-c]'); if (o && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); var id = o.getAttribute('data-c'); toggle(id); var n = $('[data-c="' + id + '"]'); if (n) n.focus(); } };
	$('#rChips').onclick = function (e) { var b = e.target.closest('[data-q]'); if (b) toggle(b.getAttribute('data-q')); };
	codigosIniciales.forEach(function (c) {
		var e = EQUIPOS.filter(function (x) { return x.codigo.toUpperCase() === String(c).toUpperCase() || (x.serie && x.serie.toUpperCase() === String(c).toUpperCase()); })[0];
		if (!e) return;
		if (CFG.superAdmin && String(e.idHotel) !== hotelActual()) { if (sel.length) return; $('#rHotel').value = String(e.idHotel); }
		toggle(e.id);
	});
	$('#fr').onsubmit = function (e) { e.preventDefault(); $('#rOk').click(); };
	$('#rOk').onclick = function () {
		var ok = true, resp = $('#rResp').value.trim(), area = $('#rArea').value.trim(), fac = $('#rFac').value.trim(), fecha = $('#rFecha').value;
		if (!sel.length || (mult && sel.length < 2)) { err('eEq', mult ? 'Elige al menos 2 equipos; para uno solo usa Nuevo resguardo.' : 'Elige el equipo que se entrega.'); ok = false; }
		if (!resp) { err('eResp', 'Escribe quién recibe el equipo.'); $('#rResp').classList.add('cc-input--error'); ok = false; } else { err('eResp', ''); $('#rResp').classList.remove('cc-input--error'); }
		if (!area) { err('eArea', 'Escribe el área a la que va el equipo.'); $('#rArea').classList.add('cc-input--error'); ok = false; } else { err('eArea', ''); $('#rArea').classList.remove('cc-input--error'); }
		if (mult && !fac) { err('eFac', 'El resguardo múltiple requiere el número de factura.'); $('#rFac').classList.add('cc-input--error'); ok = false; } else { err('eFac', ''); $('#rFac').classList.remove('cc-input--error'); }
		if (!fecha) { $('#rFecha').classList.add('cc-input--error'); ok = false; }
		if (!ok) return;
		var btn = this; btn.disabled = true; btn.classList.add('cc-btn--cargando');
		var peticion = mult
			? jQuery.post('../procesos/resguardos/guardarResguardosMultiples.php', { area_destino: area, usuario: resp, puesto: $('#rPuesto').value.trim(), fecha_resguardo: fecha, numero_factura: fac, id_equipos_json: JSON.stringify(sel.map(function (e) { return { id_equipo: e.id }; })) }, null, 'json')
			: jQuery.post('../procesos/resguardos/guardarResguardo.php', { id_equipo: sel[0].id, id_departamento: sel[0].idDepto || '', area_destino: area, usuario: resp, puesto: $('#rPuesto').value.trim(), fecha_resguardo: fecha, numero_factura: fac, modelo: sel[0].modelo || '', serie: sel[0].serie || '', tipo_equipo: sel[0].desc || '' }, null, 'json');
		peticion.done(function (j) {
			if (!j || !j.ok) { btn.disabled = false; btn.classList.remove('cc-btn--cargando'); toast('error', 'No se pudo generar el resguardo', esc((j && j.mensaje) || '')); return; }
			var nuevo = mult ? Math.min.apply(null, j.ids || [0]) : j.id_resguardo;
			EQUIPOS = null; cierraModal();
			S.firma = ''; S.pag = 1; S.ord = { k: 'id', d: -1 };
			cargar(function () { var r = porId(nuevo); if (r) verDoc(r); toast('exito', 'Resguardo ' + (r ? r.folio : '') + ' generado', 'Imprímelo, recaba las firmas y sube el PDF firmado.'); });
		}).fail(function () { btn.disabled = false; btn.classList.remove('cc-btn--cargando'); toast('error', 'Error de conexión', 'No se generó el resguardo.'); });
	};
}
$('#btnNuevo').onclick = function () { location.href = 'nuevoResguardo.php'; };
$('#btnMultiple').onclick = function () { location.href = 'nuevoResguardo.php?modo=mul'; };

/* ---------- Modal y toasts ---------- */
var focoPrevio = null;
function abreModal(h, grande) {
	focoPrevio = document.activeElement;
	var m = $('#modal'); m.innerHTML = '<div class="cc-modal' + (grande ? ' mod-grande' : '') + '" role="dialog" aria-modal="true">' + h + '</div>'; m.hidden = false; CostaClara.hidratar(m);
	var f = $('[data-cancel]', m); if (f) f.focus();
	m.onclick = function (e) { if (e.target === m || e.target.closest('[data-cancel]')) cierraModal(); };
}
function cierraModal() { var m = $('#modal'); m.hidden = true; m.innerHTML = ''; if (focoPrevio && focoPrevio.focus && document.contains(focoPrevio)) focoPrevio.focus(); }
function toast(tipo, tit, msg) {
	var c = { exito: ['check', 'cc-tono-exito'], error: ['error', 'cc-tono-peligro'], info: ['info', 'cc-tono-mar'] }[tipo];
	var t = document.createElement('div'); t.className = 'cc-toast'; t.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
	t.innerHTML = '<span class="cc-toast__ico ' + c[1] + '">' + I(c[0]) + '</span><div class="cc-toast__txt"><div class="cc-toast__titulo">' + tit + '</div>' + (msg ? '<div class="cc-toast__msg">' + msg + '</div>' : '') + '</div><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono cc-btn--sm" aria-label="Cerrar">' + I('cerrar') + '</button>';
	var box = $('#toasts'); box.appendChild(t); while (box.children.length > 3) box.firstChild.remove();
	var tm = setTimeout(function () { t.remove(); }, tipo === 'error' ? 9000 : 6000);
	t.onclick = function (e) { if (e.target.closest('[aria-label="Cerrar"]')) { clearTimeout(tm); t.remove(); } };
}

/* ---------- Arranque: también recibe equipos desde la pantalla de Equipos ---------- */
var params = new URLSearchParams(location.search);
// Enlaces anteriores (?codigo= / ?multiple=) ahora abren la página Nuevo resguardo
if (params.get('codigo') || params.get('multiple')) { location.replace('nuevoResguardo.php' + location.search); return; }
cargar(function () {
	var hotel = params.get('id_hotel') || '';
	var multiple = (params.get('multiple') || '').split('|').filter(function (c) { return c.trim() !== ''; });
	if (multiple.length) formRes(true, multiple, hotel);
	else if (params.get('codigo')) formRes(false, [params.get('codigo')], hotel);
	if (params.get('codigo') || multiple.length) history.replaceState({}, document.title, location.pathname);
});
})();
