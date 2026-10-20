/* Pantalla Equipos (Costa Clara): datos reales de procesos/equipos/listarEquipos.php,
   filtros/orden/paginación en el navegador y acciones sobre los procesos existentes. */
(function () {
'use strict';
var I = CostaClara.icono;
var CFG = window.EQ_CONFIG || {};
function $(s, r) { return (r || document).querySelector(s); }
function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
function guarda(k, v) { try { localStorage.setItem('cc-equipos-' + k, JSON.stringify(v)); } catch (e) {} }
function lee(k, d) { try { var v = localStorage.getItem('cc-equipos-' + k); return v ? JSON.parse(v) : d; } catch (e) { return d; } }
function num(n) { return Number(n).toLocaleString('es-MX'); }
function f2(n) { return (n < 10 ? '0' : '') + n; }
function iso(d) { return d.getFullYear() + '-' + f2(d.getMonth() + 1) + '-' + f2(d.getDate()); }
var MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
function fcorta(s) { if (!s) return '—'; var p = String(s).slice(0, 10).split('-'); if (p.length < 3) return esc(s); return +p[2] + ' ' + MESES[+p[1] - 1] + ' ' + p[0]; }
function sinAcentos(s) { return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }

var EST_N = { activo: 'Activo', mantenimiento: 'En mantenimiento', reparacion: 'En reparación', baja: 'Dado de baja' };
var TASA_USD = { USD: 1, MXN: 17.8, EUR: 0.92 };
var COLS = [
	{ k: 'foto', l: 'Foto', fix: 'c-foto' },
	{ k: 'codigo', l: 'Código', ord: 'codigo', fix: 'c-codigo', siempre: 1 },
	{ k: 'desc', l: 'Descripción', ord: 'desc' },
	{ k: 'hotel', l: 'Hotel', ord: 'hotel', soloSuper: 1 },
	{ k: 'depto', l: 'Departamento', ord: 'depto' },
	{ k: 'ubic', l: 'Ubicación física' },
	{ k: 'resp', l: 'Usuario responsable', ord: 'resp' },
	{ k: 'marca', l: 'Marca / Modelo' },
	{ k: 'serie', l: 'Nº de serie' },
	{ k: 'nfact', l: 'Nº de factura' },
	{ k: 'estado', l: 'Estado', ord: 'estado' },
	{ k: 'valor', l: 'Valor', ord: 'valorUSD', der: 1 },
	{ k: 'fecha', l: 'Fecha de ingreso', ord: 'fecha' },
	{ k: 'docs', l: 'Documentos' }
].filter(function (c) { return !c.soloSuper || CFG.superAdmin; });

var DATA = [];
var S = { hotel: '', depto: '', estados: [], moneda: '', desde: '', hasta: '', q: '', ord: { k: 'id', d: -1 }, pag: 1,
	size: lee('size', 25), sel: {}, ocultas: lee('ocultas', ['nfact']), abierta: null };
var lista = [];

/* ---------- Datos ---------- */
function cargar(despues) {
	fetch('../procesos/equipos/listarEquipos.php', { credentials: 'same-origin' })
		.then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
		.then(function (j) {
			DATA = (j.equipos || []).map(function (r) { r.valorUSD = r.valor / (TASA_USD[r.mon] || 1); return r; });
			var vivos = {}; DATA.forEach(function (r) { vivos[r.id] = 1; });
			Object.keys(S.sel).forEach(function (id) { if (!vivos[id]) delete S.sel[id]; });
			render();
			if (despues) despues();
		})
		.catch(function () {
			$('#tbody').innerHTML = '<tr><td colspan="15"><div class="vacio"><b>No se pudo cargar el inventario</b><span class="cc-muted">Revisa tu conexión y recarga la página.</span></div></td></tr>';
			$('#resumen').textContent = 'Error al cargar';
		});
}
function porId(id) { id = +id; for (var i = 0; i < DATA.length; i++) if (DATA[i].id === id) return DATA[i]; return null; }

function filtrados() {
	var q = sinAcentos(S.q.trim());
	var out = DATA.filter(function (r) {
		if (S.hotel && String(r.idHotel) !== S.hotel) return false;
		if (S.depto && r.depto !== S.depto) return false;
		if (S.estados.length && S.estados.indexOf(r.estado) < 0) return false;
		if (S.moneda && r.mon !== S.moneda) return false;
		if (S.desde && (!r.fecha || r.fecha < S.desde)) return false;
		if (S.hasta && (!r.fecha || r.fecha > S.hasta)) return false;
		if (q && sinAcentos([r.codigo, r.serie, r.desc, r.marca, r.resp, r.ubic, r.depto, r.nfact, r.num].join(' ')).indexOf(q) < 0) return false;
		return true;
	});
	var o = S.ord;
	out.sort(function (a, b) { var x = a[o.k], y = b[o.k]; if (typeof x === 'string') { x = sinAcentos(x); y = sinAcentos(y); } return (x < y ? -1 : x > y ? 1 : 0) * o.d; });
	return out;
}

/* ---------- Celdas ---------- */
function dinero(v, m) { return (m === 'EUR' ? '€' : m === 'USD' ? 'US$' : '$') + ' ' + Number(v).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function chip(e) { return '<span class="cc-chip cc-chip--' + e + '">' + EST_N[e] + '</span>'; }
function thumb(r) { return '<span class="thumb" aria-hidden="true">' + (r.foto ? '<img src="' + esc(r.foto) + '" alt="" loading="lazy">' : I('equipos')) + '</span>'; }
function docs(r) {
	return '<span class="docs"><span class="doc doc--' + (r.resg ? 'si' : 'no') + '" title="Resguardo firmado: ' + (r.resg ? 'sí' : 'falta') + '">' + I('resguardos') + (r.resg ? '✓' : '✗') + '</span>' +
		'<span class="doc doc--' + (r.fact ? 'si' : 'no') + '" title="Factura: ' + (r.fact ? 'sí' : 'falta') + '">' + I('pdf') + (r.fact ? '✓' : '✗') + '</span></span>';
}
function guion(v) { return v ? esc(v) : '<span class="mut">—</span>'; }
function celda(r, k) {
	switch (k) {
		case 'foto': return thumb(r);
		case 'codigo': return '<b>' + esc(r.codigo || ('#' + r.num)) + '</b>';
		case 'desc': return '<span title="' + esc(r.desc) + '">' + guion(r.desc) + '</span>';
		case 'hotel': return guion(r.hotel);
		case 'depto': return guion(r.depto);
		case 'ubic': return '<span title="' + esc(r.ubic) + '">' + guion(r.ubic) + '</span>';
		case 'resp': return guion(r.resp);
		case 'marca': return guion(r.marca);
		case 'serie': return '<span class="num mut">' + (esc(r.serie) || '—') + '</span>';
		case 'nfact': return guion(r.nfact);
		case 'estado': return chip(r.estado);
		case 'valor': return '<span class="num">' + dinero(r.valor, r.mon) + '</span><span class="mon">' + esc(r.mon) + '</span>';
		case 'fecha': return '<span class="num">' + fcorta(r.fecha) + '</span>';
		case 'docs': return docs(r);
	}
	return '';
}
function visibles() { return COLS.filter(function (c) { return c.siempre || S.ocultas.indexOf(c.k) < 0; }); }

/* ---------- Render ---------- */
function pagsVis(a, t) { if (t <= 7) { var r = []; for (var i = 1; i <= t; i++) r.push(i); return r; } if (a <= 4) return [1, 2, 3, 4, 5, '…', t]; if (a >= t - 3) return [1, '…', t - 4, t - 3, t - 2, t - 1, t]; return [1, '…', a - 1, a, a + 1, '…', t]; }
function render() {
	lista = filtrados();
	var tot = lista.length, pags = Math.max(1, Math.ceil(tot / S.size));
	if (S.pag > pags) S.pag = pags;
	var ini = (S.pag - 1) * S.size, pagina = lista.slice(ini, ini + S.size), cols = visibles();
	$('#contador').innerHTML = tot === DATA.length ? '<b>' + num(DATA.length) + '</b>equipos' : '<b>' + num(tot) + '</b>de ' + num(DATA.length) + ' equipos';
	var selN = Object.keys(S.sel).length, enPag = pagina.filter(function (r) { return S.sel[r.id]; }).length;

	var h = '<th class="fix c-check"><input type="checkbox" class="cb" id="cbTodos" aria-label="Seleccionar página"></th>';
	cols.forEach(function (c) {
		var o = c.ord && S.ord.k === c.ord;
		h += '<th class="' + (c.fix ? 'fix ' + c.fix : '') + (c.ord ? ' ord' : '') + (c.der ? ' der' : '') + '"' +
			(c.ord ? ' data-ord="' + c.ord + '" aria-sort="' + (o ? (S.ord.d > 0 ? 'ascending' : 'descending') : 'none') + '" tabindex="0"' : '') + '>' +
			c.l + (o ? '<span class="flecha">' + (S.ord.d > 0 ? '▲' : '▼') + '</span>' : '') + '</th>';
	});
	h += '<th class="c-acc"><span class="sr-only">Acciones</span></th>';
	$('#thead').innerHTML = h;
	var cb = $('#cbTodos'); cb.checked = pagina.length > 0 && enPag === pagina.length; cb.indeterminate = enPag > 0 && enPag < pagina.length;
	var cbm = $('#cbMovil'); cbm.checked = cb.checked; cbm.indeterminate = cb.indeterminate;

	if (!tot) {
		var vacio = DATA.length ? '<b style="font-size:15px">No encontramos equipos con esos filtros</b><span class="cc-muted">Prueba con otro departamento o estado, o busca por el número de serie completo.</span><button type="button" class="cc-btn cc-btn--secundario" data-limpiar style="margin-top:8px">Limpiar filtros</button>'
			: '<b style="font-size:15px">Todavía no hay equipos registrados</b><span class="cc-muted">Registra el primero o impórtalos desde un archivo CSV.</span>';
		$('#tbody').innerHTML = '<tr><td colspan="' + (cols.length + 2) + '" style="height:auto;cursor:default"><div class="vacio">' + vacio + '</div></td></tr>';
		$('#tarjetas').innerHTML = '<div class="cc-card vacio">' + vacio + '</div>';
	} else {
		$('#tbody').innerHTML = pagina.map(function (r) {
			var s = S.sel[r.id];
			return '<tr data-id="' + r.id + '" class="' + (s ? 'sel' : '') + (S.abierta === r.id ? ' abierta' : '') + '"><td class="fix c-check"><input type="checkbox" class="cb" data-cb="' + r.id + '" aria-label="Seleccionar ' + esc(r.codigo) + '"' + (s ? ' checked' : '') + '></td>' +
				cols.map(function (c) { return '<td class="c-' + c.k + (c.fix ? ' fix ' + c.fix : '') + (c.der ? ' der' : '') + '">' + celda(r, c.k) + '</td>'; }).join('') +
				'<td class="c-acc"><button type="button" class="acc-btn" data-menu="' + r.id + '" aria-label="Acciones de ' + esc(r.codigo) + '">' + I('puntos') + '</button></td></tr>';
		}).join('');
		$('#tarjetas').innerHTML = pagina.map(function (r) {
			var s = S.sel[r.id];
			return '<article class="tj' + (s ? ' sel' : '') + '" data-id="' + r.id + '"><input type="checkbox" class="cb" data-cb="' + r.id + '" aria-label="Seleccionar ' + esc(r.codigo) + '"' + (s ? ' checked' : '') + '>' + thumb(r) +
				'<div style="min-width:0"><div class="tj__fila"><span class="tj__cod">' + esc(r.codigo) + '</span><button type="button" class="acc-btn" data-menu="' + r.id + '" aria-label="Acciones de ' + esc(r.codigo) + '">' + I('puntos') + '</button></div>' +
				'<div class="tj__desc">' + esc(r.desc) + (r.marca ? ' · ' + esc(r.marca) : '') + '</div><div class="tj__meta">' + esc(r.depto || 'Sin departamento') + (CFG.superAdmin && r.hotel ? ' · ' + esc(r.hotel) : '') + '</div>' +
				(r.resp ? '<div class="tj__meta">' + esc(r.resp) + '</div>' : '') +
				'<div class="tj__pie">' + chip(r.estado) + '<span class="num" style="font-size:13px;font-weight:500">' + dinero(r.valor, r.mon) + '<span class="mon">' + esc(r.mon) + '</span></span></div>' +
				'<div class="tj__pie">' + docs(r) + '<span class="cc-small cc-muted num">Ingreso ' + fcorta(r.fecha) + '</span></div></div></article>';
		}).join('');
	}
	$('#resumen').textContent = tot ? ('Mostrando ' + num(ini + 1) + '–' + num(Math.min(ini + S.size, tot)) + ' de ' + num(tot)) : 'Sin resultados';
	$('#rango').textContent = tot ? ('Página ' + S.pag + ' de ' + pags) : '';
	var p = '<button type="button" data-pag="' + (S.pag - 1) + '" aria-label="Página anterior"' + (S.pag <= 1 ? ' disabled' : '') + '>' + I('izquierda') + '</button>';
	pagsVis(S.pag, pags).forEach(function (n) { p += n === '…' ? '<span style="padding:0 4px">…</span>' : '<button type="button" data-pag="' + n + '" class="' + (n === S.pag ? 'on' : '') + '"' + (n === S.pag ? ' aria-current="page"' : '') + '>' + n + '</button>'; });
	p += '<button type="button" data-pag="' + (S.pag + 1) + '" aria-label="Página siguiente"' + (S.pag >= pags ? ' disabled' : '') + '>' + I('derecha') + '</button>';
	$('#pags').innerHTML = p; $('#size').value = String(S.size);
	var fl = $('#flota'); fl.hidden = !selN; $('#flotaN').textContent = num(selN) + (selN === 1 ? ' seleccionado' : ' seleccionados');
	var st = $('#selTodos'); st.hidden = !(selN && selN < tot && enPag === pagina.length && tot > pagina.length); st.textContent = 'Seleccionar los ' + num(tot);
	etiquetas();
}
function nombreHotel(id) { for (var i = 0; i < DATA.length; i++) if (String(DATA[i].idHotel) === String(id)) return DATA[i].hotel; return ''; }
function etiquetas() {
	var v = { hotel: S.hotel ? nombreHotel(S.hotel) : 'Todos', depto: S.depto || 'Todos', estado: S.estados.length ? (S.estados.length === 1 ? EST_N[S.estados[0]] : S.estados.length + ' estados') : 'Todos', moneda: S.moneda || 'Todas', fecha: (S.desde || S.hasta) ? ((S.desde ? fcorta(S.desde) : '…') + ' – ' + (S.hasta ? fcorta(S.hasta) : 'hoy')) : 'Cualquiera' };
	var on = { hotel: !!S.hotel, depto: !!S.depto, estado: !!S.estados.length, moneda: !!S.moneda, fecha: !!(S.desde || S.hasta) };
	$$('.fbtn').forEach(function (b) { var f = b.getAttribute('data-f'); $('[data-v]', b).textContent = v[f]; b.classList.toggle('fbtn--on', on[f]); });
	$('#limpiar').hidden = !(on.hotel || on.depto || on.estado || on.moneda || on.fecha || S.q);
}

/* ---------- Popovers ---------- */
var popAbierto = null;
function cierraPop() { if (popAbierto) { popAbierto.el.remove(); popAbierto.btn.setAttribute('aria-expanded', 'false'); popAbierto = null; } }
function abrePop(btn, html, ancho, alinDer) {
	cierraPop();
	var el = document.createElement('div'); el.className = 'pop'; el.setAttribute('role', 'dialog'); el.innerHTML = html;
	if (ancho) el.style.width = ancho + 'px';
	document.body.appendChild(el); CostaClara.hidratar(el);
	var r = btn.getBoundingClientRect(), w = el.offsetWidth, h = el.offsetHeight, x = alinDer ? r.right - w : r.left;
	x = Math.max(12, Math.min(x, innerWidth - w - 12));
	var y = r.bottom + 6; if (y + h > innerHeight - 12 && r.top - h - 6 > 12) y = r.top - h - 6;
	el.style.left = x + 'px'; el.style.top = y + 'px';
	btn.setAttribute('aria-expanded', 'true'); popAbierto = { el: el, btn: btn };
	var f = $('input,button', el); if (f) f.focus();
	return el;
}
function cuenta(campo, val) {
	var bak = S[campo]; S[campo] = campo === 'estados' ? [] : '';
	var base = filtrados(); S[campo] = bak;
	var n = 0; base.forEach(function (r) { var x = campo === 'estados' ? r.estado : campo === 'hotel' ? String(r.idHotel) : campo === 'depto' ? r.depto : r.mon; if (x === val) n++; });
	return n;
}
function opt(val, txt, on, n) { return '<button type="button" class="opt' + (on ? ' opt--on' : '') + '" data-val="' + esc(val) + '"><span class="tick">' + (on ? I('check') : '') + '</span><span>' + esc(txt) + '</span>' + (n != null ? '<span class="n">' + num(n) + '</span>' : '') + '</button>'; }
function elegir(el, campo) { el.addEventListener('click', function (e) { var o = e.target.closest('.opt'); if (!o) return; S[campo] = o.getAttribute('data-val'); S.pag = 1; cierraPop(); render(); }); }
var FILTROS = {
	hotel: function (b) {
		var hoteles = {}; DATA.forEach(function (r) { if (r.idHotel) hoteles[r.idHotel] = r.hotel; });
		var el = abrePop(b, '<div class="pop__lista">' + opt('', 'Todos los hoteles', !S.hotel) + Object.keys(hoteles).sort(function (a, c) { return hoteles[a].localeCompare(hoteles[c], 'es'); }).map(function (id) { return opt(id, hoteles[id], S.hotel === id, cuenta('hotel', id)); }).join('') + '</div>', 250);
		elegir(el, 'hotel');
	},
	depto: function (b) {
		var cnt = {}, bak = S.depto; S.depto = ''; filtrados().forEach(function (r) { if (r.depto) cnt[r.depto] = (cnt[r.depto] || 0) + 1; }); S.depto = bak;
		var deptos = Object.keys(cnt).sort(function (a, c) { return a.localeCompare(c, 'es'); });
		var el = abrePop(b, '<div class="pop__buscar"><label class="cc-buscador"><i data-i="buscar"></i><input class="cc-input" placeholder="Buscar entre ' + deptos.length + ' departamentos" aria-label="Buscar departamento"></label></div><div class="pop__lista" data-l></div>', 300);
		var cont = $('[data-l]', el), inp = $('input', el);
		function pinta() {
			var q = sinAcentos(inp.value.trim());
			var ds = deptos.filter(function (d) { return !q || sinAcentos(d).indexOf(q) >= 0; });
			cont.innerHTML = (q ? '' : opt('', 'Todos los departamentos', !S.depto)) + (ds.length ? ds.map(function (d) { return opt(d, d, S.depto === d, cnt[d]); }).join('') : '<div class="pop__vacio">Ningún departamento coincide con “' + esc(inp.value) + '”.</div>');
		}
		pinta(); inp.addEventListener('input', pinta); inp.focus();
		inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { var o = $('.opt', cont); if (o) o.click(); } });
		elegir(cont, 'depto');
	},
	estado: function (b) {
		var tmp = S.estados.slice();
		var el = abrePop(b, '<div class="pop__lista">' + Object.keys(EST_N).map(function (e) { return '<button type="button" class="opt" data-val="' + e + '"><input type="checkbox" class="cb" tabindex="-1"' + (tmp.indexOf(e) >= 0 ? ' checked' : '') + ' aria-hidden="true">' + chip(e) + '<span class="n">' + num(cuenta('estados', e)) + '</span></button>'; }).join('') +
			'</div><div class="pop__pie"><button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-x>Quitar</button><button type="button" class="cc-btn cc-btn--primario cc-btn--sm" data-ok>Aplicar</button></div>', 270);
		el.addEventListener('click', function (e) {
			var o = e.target.closest('.opt');
			if (o) { var v = o.getAttribute('data-val'), i = tmp.indexOf(v); if (i < 0) tmp.push(v); else tmp.splice(i, 1); $('.cb', o).checked = i < 0; return; }
			if (e.target.closest('[data-x]')) { S.estados = []; S.pag = 1; cierraPop(); render(); }
			if (e.target.closest('[data-ok]')) { S.estados = tmp; S.pag = 1; cierraPop(); render(); }
		});
	},
	moneda: function (b) {
		var el = abrePop(b, '<div class="pop__lista">' + opt('', 'Todas las monedas', !S.moneda) + [['MXN', 'MXN · Peso mexicano'], ['USD', 'USD · Dólar estadounidense'], ['EUR', 'EUR · Euro']].map(function (m) { return opt(m[0], m[1], S.moneda === m[0], cuenta('moneda', m[0])); }).join('') + '</div>', 280);
		elegir(el, 'moneda');
	},
	fecha: function (b) {
		var hoy = new Date(), h = iso(hoy), y = hoy.getFullYear();
		var tres = new Date(hoy); tres.setMonth(tres.getMonth() - 3);
		var pres = [['Este mes', y + '-' + f2(hoy.getMonth() + 1) + '-01', h], ['Últimos 3 meses', iso(tres), h], ['Este año', y + '-01-01', h], [String(y - 1), (y - 1) + '-01-01', (y - 1) + '-12-31']];
		var el = abrePop(b, '<div class="presets">' + pres.map(function (p, i) { return '<button type="button" class="cc-filtro" data-p="' + i + '">' + p[0] + '</button>'; }).join('') + '</div>' +
			'<div class="fechas"><label class="cc-field" style="margin:0"><span class="cc-label">Desde</span><input type="date" class="cc-input" id="fDesde" value="' + S.desde + '" max="' + h + '"></label><label class="cc-field" style="margin:0"><span class="cc-label">Hasta</span><input type="date" class="cc-input" id="fHasta" value="' + S.hasta + '" max="' + h + '"></label></div>' +
			'<div class="cc-error" id="fErr" hidden style="padding:0 6px 6px"></div><div class="pop__pie"><button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-x>Quitar</button><button type="button" class="cc-btn cc-btn--primario cc-btn--sm" data-ok>Aplicar</button></div>', 330, true);
		el.addEventListener('click', function (e) {
			var p = e.target.closest('[data-p]'); if (p) { var v = pres[+p.getAttribute('data-p')]; $('#fDesde').value = v[1]; $('#fHasta').value = v[2]; return; }
			if (e.target.closest('[data-x]')) { S.desde = S.hasta = ''; S.pag = 1; cierraPop(); render(); }
			if (e.target.closest('[data-ok]')) {
				var a = $('#fDesde').value, z = $('#fHasta').value;
				if (a && z && a > z) { var er = $('#fErr'); er.hidden = false; er.innerHTML = I('error') + 'La fecha “Desde” debe ser anterior a “Hasta”.'; return; }
				S.desde = a; S.hasta = z; S.pag = 1; cierraPop(); render();
			}
		});
	}
};
$$('.fbtn').forEach(function (b) {
	b.setAttribute('aria-haspopup', 'dialog'); b.setAttribute('aria-expanded', 'false');
	b.addEventListener('click', function (e) { e.stopPropagation(); if (popAbierto && popAbierto.btn === b) { cierraPop(); return; } FILTROS[b.getAttribute('data-f')](b); });
});
document.addEventListener('click', function (e) { if (popAbierto && !popAbierto.el.contains(e.target)) cierraPop(); });
document.addEventListener('keydown', function (e) {
	if (e.key === 'Escape') {
		if (!$('#modal').hidden) { cierraModal(); return; }
		if (popAbierto) { var b = popAbierto.btn; cierraPop(); b.focus(); return; }
		if (S.abierta && !$('.modal.in')) cierraPanel();
	}
	if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) { e.preventDefault(); $('#q').focus(); }
});
addEventListener('resize', cierraPop);
document.addEventListener('scroll', function (e) { if (popAbierto && !popAbierto.el.contains(e.target)) cierraPop(); }, true);
var tq; $('#q').addEventListener('input', function () { var v = this.value; clearTimeout(tq); tq = setTimeout(function () { S.q = v; S.pag = 1; render(); }, 150); });
function limpia() { S.hotel = S.depto = S.moneda = S.desde = S.hasta = S.q = ''; S.estados = []; S.pag = 1; $('#q').value = ''; render(); }
$('#limpiar').addEventListener('click', limpia);

/* ---------- Tabla: clics ---------- */
function alternaSel(id, on) { if (on) S.sel[id] = 1; else delete S.sel[id]; }
function paginaActual() { var i = (S.pag - 1) * S.size; return lista.slice(i, i + S.size); }
function clicLista(e) {
	if (e.target.closest('[data-limpiar]')) { limpia(); return; }
	var c = e.target.closest('[data-cb]'); if (c) { e.stopPropagation(); alternaSel(c.getAttribute('data-cb'), c.checked); render(); return; }
	var celdaCheck = e.target.closest('.c-check');
	if (celdaCheck) { var cb = $('.cb', celdaCheck); if (cb && e.target !== cb) { cb.checked = !cb.checked; alternaSel(cb.getAttribute('data-cb'), cb.checked); render(); } return; }
	var m = e.target.closest('[data-menu]'); if (m) { e.stopPropagation(); menuFila(m, +m.getAttribute('data-menu')); return; }
	var t = e.target.closest('[data-id]'); if (t) abrePanel(+t.getAttribute('data-id'));
}
$('#tbody').addEventListener('click', clicLista); $('#tarjetas').addEventListener('click', clicLista);
$('#thead').addEventListener('click', function (e) {
	if (e.target.id === 'cbTodos') { var on = e.target.checked; paginaActual().forEach(function (r) { alternaSel(r.id, on); }); render(); return; }
	var th = e.target.closest('[data-ord]');
	if (th) { var k = th.getAttribute('data-ord'); S.ord = S.ord.k === k ? { k: k, d: -S.ord.d } : { k: k, d: (k === 'fecha' || k === 'valorUSD') ? -1 : 1 }; render(); }
});
$('#thead').addEventListener('keydown', function (e) { var th = e.target.closest('[data-ord]'); if (th && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); th.click(); } });
$('#cbMovil').addEventListener('change', function () { var on = this.checked; paginaActual().forEach(function (r) { alternaSel(r.id, on); }); render(); });
$('#pags').addEventListener('click', function (e) { var b = e.target.closest('[data-pag]'); if (!b || b.disabled) return; S.pag = +b.getAttribute('data-pag'); render(); $('.tabla-card').scrollIntoView({ block: 'start', behavior: 'smooth' }); });
$('#size').addEventListener('change', function () { S.size = +this.value; S.pag = 1; guarda('size', S.size); render(); });
$('#btnCols').addEventListener('click', function (e) {
	e.stopPropagation(); var b = this; if (popAbierto && popAbierto.btn === b) { cierraPop(); return; }
	var el = abrePop(b, '<div class="cc-menu__titulo">Columnas visibles</div><div class="pop__lista">' + COLS.map(function (c) { return '<label class="opt"' + (c.siempre ? ' style="opacity:.55"' : '') + '><input type="checkbox" class="cb" data-col="' + c.k + '"' + (c.siempre || S.ocultas.indexOf(c.k) < 0 ? ' checked' : '') + (c.siempre ? ' disabled' : '') + '>' + c.l + '</label>'; }).join('') +
		'</div><div class="pop__pie"><button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-rest>Restablecer</button><span class="cc-small cc-muted" style="align-self:center">Se recuerda en este navegador</span></div>', 270, true);
	el.addEventListener('change', function (ev) { var k = ev.target.getAttribute('data-col'); if (!k) return; var i = S.ocultas.indexOf(k); if (ev.target.checked) { if (i >= 0) S.ocultas.splice(i, 1); } else if (i < 0) S.ocultas.push(k); guarda('ocultas', S.ocultas); render(); });
	el.addEventListener('click', function (ev) { if (ev.target.closest('[data-rest]')) { S.ocultas = ['nfact']; guarda('ocultas', S.ocultas); $$('[data-col]', el).forEach(function (c) { c.checked = c.getAttribute('data-col') !== 'nfact'; }); render(); } });
});

/* ---------- Acciones por fila ---------- */
function itemMenu(a, ico, txt, cls, deshab) { return '<button type="button" class="cc-menu__item' + (cls ? ' ' + cls : '') + '" data-a="' + a + '" role="menuitem"' + (deshab ? ' disabled' : '') + '>' + I(ico) + txt + '</button>'; }
function menuFila(btn, id) {
	if (popAbierto && popAbierto.btn === btn) { cierraPop(); return; }
	var r = porId(id), baja = r.estado === 'baja';
	var el = abrePop(btn, '<div class="pop__lista" role="menu">' + itemMenu('ver', 'ver', 'Ver detalle') + itemMenu('editar', 'editar', 'Editar') + itemMenu('resguardo', 'resguardos', 'Generar resguardo', '', baja) +
		'<div class="cc-menu__sep"></div>' + itemMenu('baja', 'baja', baja ? 'Ya está dado de baja' : 'Dar de baja', 'cc-menu__item--peligro', baja) + itemMenu('eliminar', 'cerrar', 'Eliminar registro', 'cc-menu__item--peligro') + '</div>', 230, true);
	el.addEventListener('click', function (e) { var a = e.target.closest('[data-a]'); if (!a || a.disabled) return; cierraPop(); accion(a.getAttribute('data-a'), r); });
}
function accion(a, r) {
	if (a === 'ver') abrePanel(r.id);
	if (a === 'editar') abrirEditar(r);
	if (a === 'resguardo') location.href = 'nuevoResguardo.php?codigo=' + encodeURIComponent(r.codigo);
	if (a === 'baja') location.href = 'bajas.php?codigo=' + encodeURIComponent(r.codigo);
	if (a === 'eliminar') modalEliminar(r);
}

/* ---------- Modales propios ---------- */
var focoPrevio = null;
function abreModal(html) {
	focoPrevio = document.activeElement;
	var m = $('#modal'); m.innerHTML = '<div class="cc-modal" role="dialog" aria-modal="true">' + html + '</div>'; m.hidden = false; CostaClara.hidratar(m);
	var f = $('[data-cancel]', m); if (f) f.focus();
	m.onclick = function (e) { if (e.target === m || e.target.closest('[data-cancel]')) cierraModal(); };
}
function cierraModal() { var m = $('#modal'); m.hidden = true; m.innerHTML = ''; if (focoPrevio && focoPrevio.focus) focoPrevio.focus(); }
function confirmar(titulo, texto, boton, alAceptar) {
	abreModal('<div class="cc-modal__body"><span class="cc-modal__ico cc-tono-peligro">' + I('alerta') + '</span><div class="cc-stack" style="gap:8px;min-width:0"><h2 class="cc-h2">' + titulo + '</h2><p class="cc-muted" style="margin:0">' + texto + '</p></div></div>' +
		'<div class="cc-modal__pie"><button type="button" class="cc-btn cc-btn--secundario" data-cancel>Cancelar</button><button type="button" class="cc-btn cc-btn--peligro" id="mOk">' + boton + '</button></div>');
	$('#mOk').onclick = function () { this.disabled = true; this.classList.add('cc-btn--cargando'); alAceptar(); };
}
function modalEliminar(r) {
	confirmar('¿Eliminar ' + esc(r.codigo) + '?', 'Se borrará el registro con su foto, documentos, resguardos y bajas vinculadas. Usa <b style="color:var(--texto);font-weight:500">Dar de baja</b> si el equipo existió y salió del inventario; eliminar es solo para registros capturados por error. <b style="color:var(--texto);font-weight:500">No se puede deshacer.</b>', 'Eliminar registro', function () {
		jQuery.post('../procesos/equipos/eliminaEquipo.php', { idEquipo: r.id }).done(function (resp) {
			cierraModal();
			if (String(resp).trim() === '1') {
				DATA = DATA.filter(function (x) { return x.id !== r.id; }); delete S.sel[r.id];
				if (S.abierta === r.id) cierraPanel();
				render(); toast('exito', esc(r.codigo) + ' eliminado', '');
			} else toast('error', 'No se pudo eliminar el equipo', 'Es posible que no pertenezca a tu hotel o que la sesión haya expirado.');
		}).fail(function () { cierraModal(); toast('error', 'Error de conexión', 'No se pudo eliminar el equipo. Inténtalo de nuevo.'); });
	});
}

/* ---------- Selección: exportar, resguardo múltiple y baja masiva ---------- */
function seleccionados() { return Object.keys(S.sel).map(porId).filter(Boolean); }
function unHotel(rs, accionTxt) {
	var hs = {}; rs.forEach(function (r) { hs[r.idHotel] = 1; });
	var ids = Object.keys(hs);
	if (ids.length > 1) { toast('info', 'Elige equipos de un solo hotel', 'Para ' + accionTxt + ', filtra por hotel y selecciona solo equipos de ese hotel.'); return null; }
	return ids[0] || '';
}
function exporta(rs) {
	if (!rs.length) { toast('info', 'No hay equipos para exportar', 'Ajusta los filtros o selecciona equipos.'); return; }
	var hotel = CFG.superAdmin ? unHotel(rs, 'exportar a Excel') : '';
	if (hotel === null) return;
	$('#excelIds').value = rs.map(function (r) { return r.id; }).join(',');
	$('#excelHotel').value = hotel;
	$('#formExcel').submit();
	toast('exito', 'Generando Excel', num(rs.length) + ' equipos. El archivo se abre en otra pestaña.');
}
$('#btnExportar').onclick = function () { var rs = seleccionados(); exporta(rs.length ? rs : lista); };
$('#fExportar').onclick = function () { exporta(seleccionados()); };
$('#fResguardo').onclick = function () {
	var rs = seleccionados().filter(function (r) { return r.estado !== 'baja'; });
	if (!rs.length) { toast('info', 'No hay equipos disponibles', 'Los equipos dados de baja no pueden ir en un resguardo.'); return; }
	if (rs.length > 10) { toast('info', 'Máximo 10 equipos por resguardo', 'El resguardo múltiple cabe en una hoja con hasta 10 equipos. Seleccionaste ' + rs.length + '.'); return; }
	var hotel = unHotel(rs, 'un resguardo múltiple'); if (hotel === null) return;
	location.href = 'nuevoResguardo.php?multiple=' + encodeURIComponent(rs.map(function (r) { return r.codigo; }).join('|'));
};
$('#fBaja').onclick = function () {
	var rs = seleccionados().filter(function (r) { return r.estado !== 'baja'; });
	if (!rs.length) { toast('info', 'No hay equipos para dar de baja', 'Los seleccionados ya están dados de baja.'); return; }
	if (unHotel(rs, 'una baja masiva') === null) return;
	try { sessionStorage.setItem('cc-baja-masiva', JSON.stringify(rs.map(function (r) { return { id: r.id, codigo: r.codigo, descripcion: r.desc, valor: r.valor, categoria: r.marca, estado: EST_N[r.estado] }; }))); } catch (e) {}
	location.href = 'bajasMasivas.php?desdeEquipos=1';
};
$('#fCerrar').onclick = function () { S.sel = {}; render(); };
$('#selTodos').onclick = function () { lista.forEach(function (r) { S.sel[r.id] = 1; }); render(); };

/* ---------- Registrar / importar ---------- */
function abrirRegistrar(importar) {
	$('#modalRegistrarTitulo').textContent = importar ? 'Importar equipos desde CSV' : 'Registrar equipo';
	$('#registrarCuerpo').innerHTML = '<p class="cc-muted">Cargando formulario…</p>';
	jQuery('#modalRegistrar').modal('show');
	jQuery('#registrarCuerpo').load('equipos/agregarEquipo.php', function () {
		if (importar) { var csv = document.getElementById('frmImportarEquiposCsv'); if (csv) setTimeout(function () { csv.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 250); }
	});
}
$('#btnRegistrar').onclick = function () { abrirRegistrar(false); };
$('#btnImportar').onclick = function () { abrirRegistrar(true); };
jQuery('#modalRegistrar').on('hidden.bs.modal', function () { $('#registrarCuerpo').innerHTML = ''; cargar(); });

/* ---------- Editar (mismo proceso de siempre) ---------- */
function abrirEditar(r) {
	$('#imagenEquipoU').value = ''; $('#imgEquipoActual').style.display = 'none'; $('#sinImagenActual').style.display = '';
	$$('#ubicacionEquipoU option[data-hotel]').forEach(function (o) { var ver = !CFG.superAdmin || !r.idHotel || o.getAttribute('data-hotel') === String(r.idHotel); o.hidden = !ver; o.disabled = !ver; });
	jQuery.ajax({ type: 'POST', url: '../procesos/equipos/obtenerEquipo.php', data: { idEquipo: r.id }, dataType: 'json' }).done(function (d) {
		if (!d || !d.id_equipo) { toast('error', 'No se pudieron cargar los datos del equipo', ''); return; }
		$('#idEquipo').value = d.id_equipo; $('#codigoEquipoU').value = d.codigo_equipo || ''; $('#descripcionEquipoU').value = d.descripcion || '';
		$('#marcaEquipoU').value = d.marca || ''; $('#modeloEquipoU').value = d.modelo || ''; $('#numeroSerieU').value = d.numero_serie || '';
		$('#estadoEquipoU').value = d.estado || 'Activo'; $('#ubicacionEquipoU').value = d.id_departamento || ''; $('#ubicacionFisicaU').value = d.ubicacion || '';
		$('#valorEquipoU').value = d.valor_adquisicion || '0'; $('#tipoMonedaU').value = d.tipo_moneda || 'MXN'; $('#fechaIngresoU').value = d.fecha_ingreso || '';
		$('#usuarioRegistroEquipoU').value = d.usuario_registro || ''; $('#numeroFacturaEquipoU').value = d.numero_factura || '';
		$('#fotoEquipoActual').value = d.foto_equipo || '';
		if (d.foto_equipo_url) { var img = $('#imgEquipoActual'); img.onload = function () { img.style.display = ''; $('#sinImagenActual').style.display = 'none'; }; img.onerror = function () { img.style.display = 'none'; }; img.src = d.foto_equipo_url; }
		jQuery('#abremodalUpdateEquipo').modal('show');
	}).fail(function () { toast('error', 'No se pudieron cargar los datos del equipo', '¿La sesión expiró? Recarga la página.'); });
}
$('#imagenEquipoU').addEventListener('change', function () {
	var f = this.files && this.files[0];
	if (f && f.size > 5 * 1024 * 1024) { toast('error', 'La foto pesa más de 5 MB', 'Elige una imagen más ligera.'); this.value = ''; return; }
	if (f && /^image\//.test(f.type)) { var rd = new FileReader(); rd.onload = function (ev) { $('#imgEquipoActual').src = ev.target.result; $('#imgEquipoActual').style.display = ''; $('#sinImagenActual').style.display = 'none'; }; rd.readAsDataURL(f); }
});
$('#btnActualizarEquipo').addEventListener('click', function () {
	var form = $('#frmActualizarEquipo'), btn = this;
	if (!form.reportValidity()) return;
	btn.disabled = true;
	jQuery.ajax({ type: 'POST', url: '../procesos/equipos/actualizaEquipo.php', data: new FormData(form), processData: false, contentType: false, dataType: 'text' }).done(function (resp) {
		if (String(resp).trim() === '1') {
			var id = +$('#idEquipo').value;
			jQuery('#abremodalUpdateEquipo').modal('hide');
			toast('exito', 'Equipo actualizado', '');
			cargar(function () { if (S.abierta === id) abrePanel(id); });
		} else toast('error', 'No se pudo actualizar el equipo', esc(String(resp).trim()));
	}).fail(function () { toast('error', 'Error de conexión', 'No se guardaron los cambios.'); }).always(function () { btn.disabled = false; });
});

/* ---------- Panel de detalle ---------- */
var HI = { alta: ['mas', 'cc-tono-exito'], resguardo: ['firma', 'cc-tono-mar'], baja: ['baja', 'cc-tono-peligro'] };
var detalle = null;
function docRow(tipo) {
	var esResg = tipo === 'resguardo', nom = esResg ? 'Resguardo firmado' : 'Factura';
	var url = detalle && (esResg ? detalle.url_resguardo_firmado : detalle.url_factura_escaneada);
	if (url) {
		return '<div class="docrow"><span class="docrow__ico">' + I('pdf') + '</span><div class="docrow__txt"><b>' + nom + '</b><span>' + (esResg ? 'PDF firmado' : esc(detalle.nombre_archivo_factura || 'Archivo escaneado')) + '</span></div>' +
			'<a class="cc-btn cc-btn--secundario cc-btn--sm" href="' + esc(url) + '" target="_blank" rel="noopener">' + I('ver') + 'Ver</a>' +
			'<button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-subir="' + tipo + '">Reemplazar</button>' +
			'<button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono cc-btn--sm" data-quitar="' + tipo + '" aria-label="Eliminar ' + nom.toLowerCase() + '" style="color:var(--peligro)">' + I('baja') + '</button></div>';
	}
	return '<div class="docrow docrow--falta" data-drop="' + tipo + '"><span class="docrow__ico">' + I('subir') + '</span><div class="docrow__txt"><b>' + nom + ' · falta</b><span>Arrastra el archivo aquí o súbelo · ' + (esResg ? 'PDF' : 'PDF o imagen') + '</span></div>' +
		'<button type="button" class="cc-btn cc-btn--primario cc-btn--sm" data-subir="' + tipo + '">' + I('subir') + 'Subir</button></div>';
}
function pintaPanel(r, eventos) {
	var p = $('#panel'), baja = r.estado === 'baja';
	p.innerHTML = '<div class="cc-panel-det__head"><div style="flex:1;min-width:0"><div class="cc-row" style="gap:8px"><h2>' + esc(r.codigo) + '</h2>' + chip(r.estado) + '</div><p>' + esc(r.desc) + (r.marca ? ' · ' + esc(r.marca) : '') + '</p></div>' +
		'<button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono" data-cerrar aria-label="Cerrar detalle">' + I('cerrar') + '</button></div>' +
		'<div class="cc-panel-det__acc"><button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" data-acc="editar">' + I('editar') + 'Editar</button>' +
		(baja ? '' : '<button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" data-acc="resguardo">' + I('resguardos') + 'Generar resguardo</button><button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-acc="baja" style="color:var(--peligro)">' + I('baja') + 'Dar de baja</button>') +
		'<span style="flex:1"></span><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono cc-btn--sm" data-acc="prev" aria-label="Equipo anterior">' + I('izquierda') + '</button><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono cc-btn--sm" data-acc="next" aria-label="Equipo siguiente">' + I('derecha') + '</button></div>' +
		'<div class="cc-panel-det__body">' +
		'<div class="foto">' + (r.foto ? '<img src="' + esc(r.foto) + '" alt="Foto de ' + esc(r.codigo) + '" data-zoom>' : I('imagen') + '<span class="foto__nota">Sin foto · agrégala desde Editar</span>') + '</div>' +
		'<section class="sec"><h3>Datos del equipo</h3><dl class="datos">' +
		'<div class="ancho"><dt>Descripción</dt><dd>' + guion(r.desc) + '</dd></div>' +
		'<div><dt>Marca / Modelo</dt><dd>' + guion(r.marca) + '</dd></div><div><dt>Nº de serie</dt><dd class="num">' + guion(r.serie) + '</dd></div>' +
		(CFG.superAdmin ? '<div><dt>Hotel</dt><dd>' + guion(r.hotel) + '</dd></div>' : '') +
		'<div><dt>Departamento</dt><dd>' + guion(r.depto) + '</dd></div>' +
		'<div class="ancho"><dt>Ubicación física</dt><dd>' + guion(r.ubic) + '</dd></div>' +
		'<div><dt>Usuario responsable</dt><dd>' + guion(r.resp) + '</dd></div><div><dt>Nº de factura</dt><dd>' + guion(r.nfact) + '</dd></div>' +
		'<div><dt>Valor</dt><dd class="num">' + dinero(r.valor, r.mon) + ' <span class="mon">' + esc(r.mon) + '</span></dd></div><div><dt>Fecha de ingreso</dt><dd>' + fcorta(r.fecha) + '</dd></div>' +
		'<div><dt>Nº de equipo en el hotel</dt><dd class="num">' + esc(r.num) + '</dd></div>' +
		'</dl></section>' +
		'<section class="sec"><h3>Documentos</h3>' + (detalle ? docRow('resguardo') + docRow('factura') : '<p class="cc-muted cc-small" style="margin:0">Cargando documentos…</p>') + '</section>' +
		'<section class="sec"><h3>Historial</h3>' + (eventos === null ? '<p class="cc-muted cc-small" style="margin:0">Cargando historial…</p>' : eventos.length ? '<ul class="hist">' + eventos.map(function (h) { var c = HI[h.t] || HI.alta; return '<li><span class="hist__dot ' + c[1] + '">' + I(c[0]) + '</span><div><b>' + esc(h.txt) + '</b><small>' + fcorta(h.f) + (h.u ? ' · ' + esc(h.u) : '') + '</small></div></li>'; }).join('') + '</ul>' : '<p class="cc-muted cc-small" style="margin:0">Sin movimientos registrados.</p>') + '</section>' +
		'</div>';
	CostaClara.hidratar(p);
}
function abrePanel(id) {
	var r = porId(id); if (!r) return;
	var ya = S.abierta; S.abierta = id; detalle = null;
	pintaPanel(r, null);
	var p = $('#panel'); p.setAttribute('aria-hidden', 'false'); p.classList.add('on'); $('#velo').classList.add('on');
	$$('tr[data-id]').forEach(function (tr) { tr.classList.toggle('abierta', +tr.getAttribute('data-id') === id); });
	if (!ya) { focoPrevio = document.activeElement; var c = $('[data-cerrar]', p); if (c) c.focus(); }
	var eventos = null;
	jQuery.when(
		jQuery.ajax({ type: 'POST', url: '../procesos/equipos/obtenerEquipo.php', data: { idEquipo: id }, dataType: 'json' }).done(function (d) { if (d && d.id_equipo) detalle = d; }),
		jQuery.getJSON('../procesos/equipos/historialEquipo.php', { id: id }).done(function (h) { eventos = (h && h.eventos) || []; })
	).always(function () { if (S.abierta === id) pintaPanel(r, eventos || []); });
}
function cierraPanel() {
	var p = $('#panel'); p.classList.remove('on'); p.setAttribute('aria-hidden', 'true'); $('#velo').classList.remove('on'); S.abierta = null;
	$$('tr.abierta').forEach(function (t) { t.classList.remove('abierta'); });
	if (focoPrevio && focoPrevio.focus) focoPrevio.focus();
}
$('#velo').onclick = cierraPanel;
var subirTipo = null;
$('#panel').addEventListener('click', function (e) {
	var r = porId(S.abierta); if (!r) return;
	if (e.target.closest('[data-cerrar]')) { cierraPanel(); return; }
	if (e.target.closest('[data-zoom]')) { $('#imagenEquipo').src = r.foto; jQuery('#modalImagen').modal('show'); return; }
	var a = e.target.closest('[data-acc]');
	if (a) {
		var k = a.getAttribute('data-acc');
		if (k === 'prev' || k === 'next') { var i = lista.indexOf(r) + (k === 'next' ? 1 : -1); if (lista[i]) { var pg = Math.floor(i / S.size) + 1; if (pg !== S.pag) { S.pag = pg; render(); } abrePanel(lista[i].id); } return; }
		accion(k, r); return;
	}
	var s = e.target.closest('[data-subir]');
	if (s) { subirTipo = s.getAttribute('data-subir'); var fi = $('#fileDoc'); fi.accept = subirTipo === 'resguardo' ? 'application/pdf,.pdf' : '.pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*'; fi.value = ''; fi.click(); return; }
	var q = e.target.closest('[data-quitar]');
	if (q) {
		var tipo = q.getAttribute('data-quitar');
		confirmar('¿Eliminar ' + (tipo === 'resguardo' ? 'el resguardo firmado' : 'la factura') + '?', 'El archivo de <b style="color:var(--texto);font-weight:500">' + esc(r.codigo) + '</b> se borrará del servidor. No se puede deshacer.', 'Eliminar archivo', function () {
			jQuery.ajax({ type: 'POST', url: '../procesos/equipos/eliminarDocumentoEquipo.php', data: { idEquipo: r.id, tipo: tipo }, dataType: 'json' }).done(function (j) {
				cierraModal();
				if (j && j.ok) { if (tipo === 'resguardo') r.resg = false; else r.fact = false; render(); abrePanel(r.id); toast('exito', 'Archivo eliminado', ''); }
				else toast('error', 'No se pudo eliminar el archivo', esc((j && j.mensaje) || ''));
			}).fail(function () { cierraModal(); toast('error', 'Error de conexión', 'No se eliminó el archivo.'); });
		});
	}
});
function adjunta(tipo, f) {
	var r = porId(S.abierta); if (!r || !f) return;
	var esResg = tipo === 'resguardo';
	if (esResg ? !/\.pdf$/i.test(f.name) : !/\.(pdf|jpe?g|png|gif|webp)$/i.test(f.name)) {
		toast('error', esResg ? 'Solo se aceptan archivos PDF' : 'Formato no admitido', '“' + esc(f.name) + '” no es ' + (esResg ? 'PDF. Escanea el documento firmado como PDF.' : 'PDF ni imagen.')); return;
	}
	var fd = new FormData(); fd.append('idEquipo', r.id); fd.append(esResg ? 'resguardoFirmado' : 'facturaEquipo', f);
	toast('info', 'Subiendo ' + (esResg ? 'resguardo' : 'factura') + '…', esc(f.name));
	jQuery.ajax({ type: 'POST', url: '../procesos/equipos/' + (esResg ? 'subirResguardoFirmado.php' : 'subirFacturaEquipo.php'), data: fd, processData: false, contentType: false, dataType: 'json' }).done(function (j) {
		if (j && j.ok) { if (esResg) r.resg = true; else r.fact = true; render(); abrePanel(r.id); toast('exito', esResg ? 'Resguardo firmado guardado' : 'Factura guardada', esc(f.name)); }
		else toast('error', 'No se pudo subir el archivo', esc((j && j.mensaje) || ''));
	}).fail(function () { toast('error', 'Error de conexión', 'No se subió el archivo.'); });
}
$('#fileDoc').onchange = function () { adjunta(subirTipo, this.files[0]); };
$('#panel').addEventListener('dragover', function (e) { var d = e.target.closest('[data-drop]'); if (d) { e.preventDefault(); d.classList.add('arrastre'); } });
$('#panel').addEventListener('dragleave', function (e) { var d = e.target.closest('[data-drop]'); if (d) d.classList.remove('arrastre'); });
$('#panel').addEventListener('drop', function (e) { var d = e.target.closest('[data-drop]'); if (d) { e.preventDefault(); d.classList.remove('arrastre'); adjunta(d.getAttribute('data-drop'), e.dataTransfer.files[0]); } });

/* ---------- Toasts ---------- */
function toast(tipo, tit, msg) {
	var c = { exito: ['check', 'cc-tono-exito'], error: ['error', 'cc-tono-peligro'], info: ['info', 'cc-tono-mar'] }[tipo];
	var t = document.createElement('div'); t.className = 'cc-toast'; t.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
	t.innerHTML = '<span class="cc-toast__ico ' + c[1] + '">' + I(c[0]) + '</span><div class="cc-toast__txt"><div class="cc-toast__titulo">' + tit + '</div>' + (msg ? '<div class="cc-toast__msg">' + msg + '</div>' : '') + '</div><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono cc-btn--sm" aria-label="Cerrar">' + I('cerrar') + '</button>';
	var box = $('#toasts'); box.appendChild(t); while (box.children.length > 3) box.firstChild.remove();
	var tm = setTimeout(function () { t.remove(); }, tipo === 'error' ? 9000 : 5000);
	t.addEventListener('click', function (e) { if (e.target.closest('[aria-label="Cerrar"]')) { clearTimeout(tm); t.remove(); } });
}

/* ---------- Buscador de la barra superior y parámetros de la URL ---------- */
function buscarGlobal(texto) {
	texto = String(texto || '').trim(); if (!texto) return;
	var exacto = DATA.filter(function (x) { return x.codigo.toUpperCase() === texto.toUpperCase() || (x.serie && x.serie.toUpperCase() === texto.toUpperCase()); })[0];
	$('#q').value = texto; S.q = texto; S.pag = 1; render();
	if (exacto) abrePanel(exacto.id);
}
var formGlobal = $('.cc-top form.cc-buscador');
if (formGlobal) formGlobal.addEventListener('submit', function (e) { e.preventDefault(); buscarGlobal($('input', formGlobal).value); });
var params = new URLSearchParams(location.search);
cargar(function () {
	if (params.get('buscar')) { buscarGlobal(params.get('buscar')); if (formGlobal) $('input', formGlobal).value = params.get('buscar'); }
	if (params.get('nuevo') === '1') abrirRegistrar(false);
});
})();
