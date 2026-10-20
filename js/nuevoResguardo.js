/* Nuevo resguardo (individual o múltiple) con vista previa del documento. Guarda con los procesos existentes:
   procesos/resguardos/guardarResguardo.php y guardarResguardosMultiples.php */
(function () {
'use strict';
var $ = CC.$, $$ = CC.$$, I = CC.I, esc = CC.esc;
var raiz = $('#vistaNuevoRes'), CFG = JSON.parse(raiz.getAttribute('data-cfg')), HOY = CC.iso(new Date()), P = new URLSearchParams(location.search);
var R = { modo: P.get('modo') === 'mul' || P.get('multiple') ? 'mul' : 'ind', eq: null, area: '', resp: '', puesto: '', fecha: HOY, factura: '', m: { factura: '', area: '', resp: '', puesto: '', fecha: HOY, items: [] }, gen: [] };
var EQ = [], RESP = [], AREAS = [], PUESTOS = [];

function hotelSel() { var x = R.modo === 'mul' ? R.m.items[0] : R.eq; return x ? x.idHotel : 0; }
function doc() {
	var mul = R.modo === 'mul', d = mul ? R.m : R, eqs = mul ? R.m.items : (R.eq ? [R.eq] : []), n = eqs.length, hotel = (eqs[0] || {}).hotel || '';
	var fac = mul ? R.m.factura : (R.factura || (R.eq ? R.eq.nfact : ''));
	return '<article class="hoja" aria-label="Vista previa del resguardo"><div class="hoja__enc"><div class="hoja__marca"><img src="../img/p9anie6p.png" alt=""><div><b>The Villa Group</b><span>' + esc(hotel || 'Hotel') + '</span></div></div>' +
		'<div class="hoja__folio"><div class="t1">FOLIO</div><div class="t2">RSG-····</div><div style="color:var(--papel-gris)">' + CC.flarga(d.fecha) + '</div></div></div>' +
		'<h3>RESGUARDO DE EQUIPO' + (mul ? ' (MÚLTIPLE)' : '') + '</h3><dl class="hoja__datos">' +
		[['Usuario responsable', d.resp], ['Puesto', d.puesto], ['Área de destino', d.area], ['Factura', fac]].map(function (x) { return '<div><dt>' + x[0] + '</dt><dd>' + (x[1] ? esc(x[1]) : '<span style="color:var(--papel-gris)">—</span>') + '</dd></div>'; }).join('') + '</dl>' +
		'<div style="overflow-x:auto"><table><thead><tr><th>#</th><th>Código</th><th>Tipo</th><th>Marca / Modelo</th><th>Nº de serie</th></tr></thead><tbody>' +
		(n ? eqs.map(function (e, i) { return '<tr><td>' + (i + 1) + '</td><td>' + esc(e.codigo) + '</td><td>' + esc(e.desc) + '</td><td>' + esc(e.marca || '—') + '</td><td>' + esc(e.serie || '—') + '</td></tr>'; }).join('')
			: '<tr><td colspan="5" style="color:var(--papel-gris);text-align:center;padding:14px">' + (mul ? 'Agrega equipos a la lista' : 'Escanea o escribe el código del equipo') + '</td></tr>') + '</tbody></table></div>' +
		'<p>Recibo en resguardo ' + (n > 1 ? 'los ' + n + ' equipos descritos' : 'el equipo descrito') + ', en buen estado y funcionando, propiedad de The Villa Group. Me comprometo a usarlo' + (n > 1 ? 's' : '') + ' solo para actividades del hotel, a cuidarlo' + (n > 1 ? 's' : '') + ' y a reportar a Sistemas cualquier falla, daño, pérdida o robo.</p>' +
		'<div class="hoja__firmas"><div><b>' + (d.resp ? esc(d.resp) : '&nbsp;') + '</b>Recibe · ' + esc(d.puesto || 'Usuario responsable') + '</div><div><b>' + esc(CFG.usuario || ' ') + '</b>Entrega · Sistemas</div><div><b>&nbsp;</b>Vo. Bo. · Jefe de área</div></div>' +
		'<div class="hoja__pie"><span>Vista previa · el PDF oficial se genera al guardar</span><span>Página 1 de 1</span></div></article>';
}
function faltan() {
	var f = [];
	if (R.modo === 'mul') { if (R.m.items.length < 2) f.push(R.m.items.length ? 'otro equipo' : 'equipos'); if (!R.m.factura.trim()) f.push('factura'); if (!R.m.area.trim()) f.push('área'); if (!R.m.resp.trim()) f.push('responsable'); if (!R.m.fecha) f.push('fecha'); }
	else { if (!R.eq) f.push('equipo'); if (!R.area.trim()) f.push('área'); if (!R.resp.trim()) f.push('responsable'); if (!R.fecha) f.push('fecha'); }
	return f;
}
function prev() { $('#prevRes').innerHTML = doc(); var f = faltan(); $('#resFalta').textContent = f.length ? 'Falta: ' + f.join(', ') : 'Listo para generar.'; }

function render() {
	var mul = R.modo === 'mul', d = mul ? R.m : R, k = mul ? 'data-m' : 'data-r';
	var dl = '<datalist id="dlResp">' + RESP.map(function (n) { return '<option value="' + esc(n) + '">'; }).join('') + '</datalist><datalist id="dlArea">' + AREAS.map(function (n) { return '<option value="' + esc(n) + '">'; }).join('') + '</datalist><datalist id="dlPue">' + PUESTOS.map(function (n) { return '<option value="' + esc(n) + '">'; }).join('') + '</datalist>';
	var datos = CC.campo('Área de destino', '<input class="cc-input" ' + k + '="area" list="dlArea" maxlength="200" value="' + esc(d.area) + '" placeholder="Dónde quedará el equipo" autocomplete="off">', { req: 1 }) +
		CC.campo('Usuario responsable', '<input class="cc-input" ' + k + '="resp" list="dlResp" maxlength="200" value="' + esc(d.resp) + '" placeholder="Nombre completo" autocomplete="off">', { req: 1 }) +
		CC.campo('Puesto', '<input class="cc-input" ' + k + '="puesto" list="dlPue" maxlength="100" value="' + esc(d.puesto) + '" placeholder="Ej. Recepcionista" autocomplete="off">', { opc: 1 }) +
		CC.campo('Fecha del resguardo', '<input type="date" class="cc-input" ' + k + '="fecha" value="' + esc(d.fecha) + '" max="' + HOY + '">', { req: 1, ayuda: 'Hoy por defecto.' }) +
		CC.campo('Número de factura', '<input class="cc-input" ' + k + '="factura" maxlength="120" value="' + esc(d.factura) + '" placeholder="Ej. F-88213">', mul ? { req: 1 } : { opc: 1 });
	raiz.innerHTML = '<div class="vista"><div class="enc"><div><h1 class="cc-h1">Nuevo resguardo</h1><p class="sub">Asigna equipos a la persona que los recibe. El documento se arma a la derecha mientras capturas.</p></div>' +
		'<div class="seg-tabs" role="tablist"><button type="button" role="tab" data-modo="ind" aria-selected="' + !mul + '">' + I('equipos') + 'Individual</button><button type="button" role="tab" data-modo="mul" aria-selected="' + mul + '">' + I('bajas-masivas') + 'Resguardo múltiple</button></div></div>' +
		'<div class="dos dos--prev"><div style="display:flex;flex-direction:column;gap:20px;min-width:0">' +
		(mul ? '<section class="card"><div class="card__head"><div><h2>Equipos <span class="cc-muted" style="font-weight:400" id="mN">· ' + R.m.items.length + ' de ' + CFG.max + '</span></h2><p>Escanea cada etiqueta o escribe el código; se agregan a la lista. Todos del mismo hotel.</p></div></div><div class="card__body"><div id="scanM"></div><div class="carrito" id="carrito"></div></div></section>' +
			'<section class="card"><div class="card__head"><div><h2>Datos del resguardo</h2><p>Un solo documento para varios equipos que recibe la misma persona.</p></div></div><div class="card__body"><div class="g2">' + datos + '</div></div></section>'
			: '<section class="card"><div class="card__head"><div><h2>Equipo</h2><p>Con lector de código de barras, escanea la etiqueta; también puedes escribir el código, la serie o la descripción.</p></div></div><div class="card__body"><div id="scanI"' + (R.eq ? ' hidden' : '') + '></div><div id="eqI">' +
				(R.eq ? CC.eqCard(R.eq, '<div><dt>Departamento</dt><dd>' + esc(R.eq.depto || '—') + '</dd></div>' + (R.eq.resg ? '<div><dt>Resguardo actual</dt><dd>Ya tiene uno; este lo reemplaza como el más reciente</dd></div>' : '')) : '<div class="eqcard eqcard--vacio">' + I('buscar') + '<span>Aquí aparecerán marca, modelo, serie y factura del equipo.</span></div>') + '</div></div></section>' +
				'<section class="card"><div class="card__head"><div><h2>Responsable</h2><p>Quién recibe el equipo y dónde lo usará.</p></div></div><div class="card__body"><div class="g2">' + datos + '</div></div></section>') + dl +
		'</div><aside class="pegado card" style="overflow:hidden"><div class="card__head"><div><h2>Vista previa</h2><p>El folio se asigna al guardar.</p></div><span class="firma firma--pend">' + I('reloj') + 'Pendiente de firma</span></div>' +
		'<div class="mod-cuerpo" id="prevRes" style="max-height:calc(100vh - 260px);min-height:360px;background:var(--superficie-hundida);padding:16px"></div><div class="card__pie"><span class="izq" id="resFalta"></span><button type="button" class="cc-btn cc-btn--primario" id="genRes">' + I('pdf') + '<span class="lbl">Guardar y generar PDF</span></button></div></aside></div>' +
		(R.gen.length ? '<section class="card"><div class="card__head"><h2>Generados en esta sesión</h2><a class="cc-btn cc-btn--secundario cc-btn--sm" href="resguardos.php">' + I('resguardos') + 'Ir a Resguardos</a></div><div class="card__body" style="padding-top:4px;padding-bottom:4px"><ul class="hist-rep">' +
			R.gen.map(function (g) { return '<li><span class="fmt fmt--pdf">PDF</span><span style="min-width:0"><b style="font-weight:500">' + esc(g.folio) + '</b> · ' + esc(g.resp) + ' · ' + g.n + (g.n === 1 ? ' equipo' : ' equipos') + '</span><span class="est est--pend">Pendiente de firma</span><button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-pdf="' + g.id + '" data-fol="' + esc(g.folio) + '">' + I('ver') + 'Ver</button></li>'; }).join('') + '</ul></div></section>' : '') + '</div>';
	CostaClara.hidratar(raiz);
	$$('[data-modo]').forEach(function (b) { b.onclick = function () { R.modo = b.getAttribute('data-modo'); render(); }; });
	$$('[data-r]').forEach(function (i) { i.addEventListener('input', function () { R[i.getAttribute('data-r')] = i.value; CC.errEn(i, ''); prev(); }); });
	$$('[data-m]').forEach(function (i) { i.addEventListener('input', function () { R.m[i.getAttribute('data-m')] = i.value; CC.errEn(i, ''); prev(); }); });
	$$('[data-pdf]').forEach(function (b) { b.onclick = function () { pdf(b.getAttribute('data-pdf'), b.getAttribute('data-fol')); }; });
	if (!mul) {
		if (!R.eq) CC.escaner($('#scanI'), { lista: EQ, vacio: 'Revisa que el equipo esté dado de alta', pick: function (e) { elegir(e); render(); var a = $('[data-r="area"]'); if (a && !a.value) a.focus(); } }).input.focus();
		var q = $('[data-quitar-eq]'); if (q) q.onclick = function () { R.eq = null; render(); };
	} else {
		CC.escaner($('#scanM'), { sm: 1, lista: EQ, ph: 'Escanea o escribe el código / número de serie para agregar', filtro: function (e) { var h = hotelSel(); return !h || e.idHotel === h; }, vacio: hotelSel() ? 'Solo aparecen equipos del mismo hotel' : '',
			etiqueta: function (e) { return enLista(e) ? '<span class="cc-small" style="color:var(--exito-texto)">En la lista</span>' : '<span class="cc-small cc-muted">Agregar</span>'; },
			pick: function (e) { agregar(e); } });
		carrito();
	}
	prev();
	$('#genRes').onclick = generar;
}
function enLista(e) { return R.m.items.some(function (x) { return x.id === e.id; }); }
function elegir(e) { R.eq = e; if (!R.resp && e.resp) R.resp = e.resp; if (!R.area && e.ubic) R.area = e.ubic; if (!R.factura && e.nfact) R.factura = e.nfact; }
function agregar(e, silencio) {
	if (enLista(e)) { if (!silencio) CC.toast('alerta', esc(e.codigo) + ' ya está en la lista', ''); return false; }
	if (R.m.items.length >= CFG.max) { if (!silencio) CC.toast('error', 'Máximo ' + CFG.max + ' equipos', 'Un resguardo múltiple cabe en una sola hoja.'); return false; }
	var h = hotelSel(); if (h && e.idHotel !== h) { if (!silencio) CC.toast('alerta', esc(e.codigo) + ' es de ' + esc(e.hotel), 'Todos los equipos deben ser del mismo hotel.'); return false; }
	R.m.items.push(e);
	if (!R.m.resp && e.resp) R.m.resp = e.resp; if (!R.m.area && e.ubic) R.m.area = e.ubic; if (!R.m.factura && e.nfact) R.m.factura = e.nfact;
	if (!silencio) render();
	return true;
}
function carrito() {
	var c = $('#carrito'), it = R.m.items;
	c.innerHTML = it.length ? it.map(function (e, i) { return '<div class="citem">' + CC.thumb(e) + '<div style="min-width:0"><b>' + esc(e.codigo) + '</b><small>' + esc(e.desc) + ' · ' + esc(e.marca || '') + ' · ' + esc(e.serie || 's/serie') + '</small></div><button type="button" class="quitar-btn" data-qi="' + i + '" aria-label="Quitar ' + esc(e.codigo) + '">' + I('cerrar') + '</button></div>'; }).join('') + '<div class="carrito__tot"><span>Total</span><b>' + it.length + (it.length === 1 ? ' equipo' : ' equipos') + '</b></div>'
		: '<div class="carrito__vacio">La lista está vacía. Escanea el primer equipo.</div>';
	CostaClara.hidratar(c);
	$$('[data-qi]', c).forEach(function (b) { b.onclick = function () { it.splice(+b.getAttribute('data-qi'), 1); render(); }; });
}
function generar() {
	var mul = R.modo === 'mul', d = mul ? R.m : R, k = mul ? 'data-m' : 'data-r', ok = true;
	if (mul && R.m.items.length < 2) { CC.toast('error', R.m.items.length ? 'Agrega al menos 2 equipos' : 'La lista está vacía', 'Para un solo equipo usa la pestaña Individual.'); ok = false; }
	if (!mul && !R.eq) { var s = $('#scanI input'); s.classList.add('cc-input--error'); var er = $('#scanI .cc-error'); er.hidden = false; er.innerHTML = I('error') + '<span>Primero escanea o escribe el código del equipo.</span>'; ok = false; }
	[['area', 'Escribe el área a la que va el equipo.'], ['resp', 'Escribe el nombre completo de quien recibe.'], ['fecha', 'Elige la fecha.']].concat(mul ? [['factura', 'El resguardo múltiple requiere el número de factura.']] : []).forEach(function (x) { if (!String(d[x[0]] || '').trim()) { CC.errEn($('[' + k + '="' + x[0] + '"]'), x[1]); ok = false; } });
	if (!ok) { var f = $('.cc-input--error', raiz); if (f) f.focus(); return; }
	var btn = $('#genRes'); CC.ocupado(btn, true);
	var pet = mul
		? CC.api('../procesos/resguardos/guardarResguardosMultiples.php', { area_destino: d.area.trim(), usuario: d.resp.trim(), puesto: d.puesto.trim(), fecha_resguardo: d.fecha, numero_factura: d.factura.trim(), id_equipos_json: JSON.stringify(R.m.items.map(function (e) { return { id_equipo: e.id }; })) })
		: CC.api('../procesos/resguardos/guardarResguardo.php', { id_equipo: R.eq.id, id_departamento: R.eq.idDepto || '', area_destino: d.area.trim(), usuario: d.resp.trim(), puesto: d.puesto.trim(), fecha_resguardo: d.fecha, numero_factura: (d.factura || '').trim(), modelo: R.eq.modelo || '', serie: R.eq.serie || '', tipo_equipo: R.eq.desc || '' });
	pet.done(function (j) {
		CC.ocupado(btn, false);
		if (!j || !j.ok) { CC.toast('error', 'No se pudo generar el resguardo', esc((j && j.mensaje) || '')); return; }
		var id = mul ? Math.min.apply(null, j.ids || [0]) : j.id_resguardo, fol = 'RSG-' + ('000' + id).slice(-4), n = mul ? R.m.items.length : 1;
		R.gen.unshift({ id: id, folio: fol, resp: d.resp.trim(), n: n });
		if (mul) R.m = { factura: '', area: '', resp: '', puesto: '', fecha: HOY, items: [] }; else { R.eq = null; R.area = ''; R.resp = ''; R.puesto = ''; R.factura = ''; R.fecha = HOY; }
		CC.equipos(true).then(function (l) { EQ = l.filter(function (e) { return e.estado !== 'baja'; }); render(); });
		pdf(id, fol);
		CC.toast('exito', 'Resguardo ' + fol + ' generado', 'Imprímelo, recaba las firmas y súbelo firmado en Resguardos.');
	}).fail(function () { CC.ocupado(btn, false); });
}
function pdf(id, fol) { CC.verPdf(fol + ' · Resguardo', '../reportes/imprimirResguardo.php?id=' + id); }

jQuery.when(CC.equipos(), CC.api('../procesos/resguardos/listarResguardos.php', null, 'GET')).done(function (eqs, a) {
	EQ = (eqs || []).filter(function (e) { return e.estado !== 'baja'; });
	var j = a[0] || a, rs = {}, ar = {}, pu = {};
	((j && j.resguardos) || []).forEach(function (r) { if (r.resp) rs[r.resp] = 1; if (r.area) ar[r.area] = 1; if (r.puesto) pu[r.puesto] = 1; });
	RESP = Object.keys(rs).sort(); AREAS = Object.keys(ar).sort(); PUESTOS = Object.keys(pu).sort();
	// Viene de Equipos: ?codigo=X (individual) o ?multiple=A|B|C
	var buscar = function (c) { c = String(c).toUpperCase(); return EQ.filter(function (e) { return e.codigo.toUpperCase() === c || (e.serie && e.serie.toUpperCase() === c); })[0]; };
	var mult = (P.get('multiple') || '').split('|').filter(Boolean);
	if (mult.length) mult.forEach(function (c) { var e = buscar(c); if (e) agregar(e, true); });
	else if (P.get('codigo')) { var e = buscar(P.get('codigo')); if (e) elegir(e); }
	if (P.get('codigo') || mult.length || P.get('modo')) history.replaceState(null, '', location.pathname);
	render();
});
})();
