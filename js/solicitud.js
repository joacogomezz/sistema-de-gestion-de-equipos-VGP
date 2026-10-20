/* Solicitud de alta de equipos ya registrados: datos de ubicación y clasificación por equipo (procesos/solicitudAltaEquipos/solicitudes.php) */
(function () {
'use strict';
var $ = CC.$, $$ = CC.$$, I = CC.I, esc = CC.esc, num = CC.num;
var raiz = $('#vistaSolicitud'), URL_S = '../procesos/solicitudAltaEquipos/solicitudes.php', CLAVE = 'solicitud-borrador';
var HABS = ['No aplica', 'Estudio', 'Junior suite', 'Suite 1 recámara', 'Suite 2 recámaras', 'Suite presidencial', 'Penthouse'];
var COLS = [['caracteristicas', 'Características', 'w-l'], ['color', 'Color', 'w-s'], ['centro_costos', 'Centro de costos', 'cc'], ['id_fase', 'Fase', 'fase'], ['id_area', 'Área', 'area'], ['piso', 'Piso', 'w-s'], ['tipo_habitacion', 'Tipo de habitación', 'hab'], ['id_departamento', 'Departamento', 'depto']];
var S = vacia(), D = { deptos: [], areas: [], fases: [], recientes: [], c1: [], c2: [], responsable: '' }, EQ = [];

function vacia() { return { numero: '', fecha: CC.iso(new Date()), depto: '', c1: '', c2: '', filas: [] }; }
function guardar() { CC.guarda(CLAVE, { numero: S.numero, fecha: S.fecha, depto: S.depto, c1: S.c1, c2: S.c2, filas: S.filas.map(function (f) { var o = Object.assign({}, f); o.id = f.e.id; delete o.e; return o; }) }); }
function hotel() { return S.filas.length ? S.filas[0].e.idHotel : 0; }
function delHotel(l) { var h = hotel(); return l.filter(function (x) { return !h || x.idHotel === h; }).map(function (x) { return [x.id, x.nombre]; }); }

function cargar(primera) {
	return jQuery.when(CC.api(URL_S, { accion: 'listar' }, 'GET'), CC.equipos(true)).done(function (a, eqs) {
		var j = a[0] || a; if (j && j.ok) D = j; EQ = eqs || [];
		if (primera) {
			var b = CC.lee(CLAVE, null);
			if (b) { ['numero', 'fecha', 'depto', 'c1', 'c2'].forEach(function (k) { if (b[k]) S[k] = b[k]; }); (b.filas || []).forEach(function (f) { var e = EQ.filter(function (x) { return x.id === f.id; })[0]; if (e) { f.e = e; delete f.id; S.filas.push(f); } }); }
		}
		render();
	});
}

function render() {
	var h = hotel();
	raiz.innerHTML = '<div class="vista"><div class="enc"><div><h1 class="cc-h1">Solicitud de alta de equipos</h1><p class="sub">Documento para dar de alta equipos de operación. Primero registra los equipos en Equipos; aquí completas su ubicación y clasificación y generas el PDF para firmas.</p></div></div>' +
		'<section class="card"><div class="card__body"><dl class="docenc" style="margin:0">' +
		'<div><dt>Solicitud #<span style="color:var(--peligro)">*</span></dt><dd style="font-size:14px;font-weight:400"><input class="cc-input" id="sNum" maxlength="50" value="' + esc(S.numero) + '" placeholder="Ej. 198" aria-label="Número de solicitud" style="margin-top:4px;height:34px"></dd></div>' +
		'<div><dt>Fecha</dt><dd style="font-size:14px;font-weight:400"><input class="cc-input" type="date" id="sFecha" value="' + esc(S.fecha) + '" aria-label="Fecha de la solicitud" style="margin-top:4px;height:34px"></dd></div>' +
		'<div><dt>Departamento solicitante<span style="color:var(--peligro)">*</span></dt><dd style="font-size:14px;font-weight:400"><select class="cc-select" id="sDepto" aria-label="Departamento solicitante">' + CC.opciones(delHotel(D.deptos), S.depto, 'Selecciona') + '</select></dd></div>' +
		'<div><dt>Cantidad de equipos</dt><dd id="sCant">' + S.filas.length + '</dd></div></dl>' +
		'<div id="sScan" style="min-width:0"></div>' +
		'<div class="te-scroll"><table class="te"><thead><tr><th>#</th><th>Equipo</th><th>Marca / Modelo</th><th>Serie</th>' + COLS.map(function (c) { return '<th>' + c[1] + '</th>'; }).join('') + '<th>Responsable</th><th></th></tr></thead><tbody id="sBody"></tbody></table></div>' +
		'<datalist id="dlC1">' + D.c1.map(function (x) { return '<option value="' + esc(x) + '">'; }).join('') + '</datalist><datalist id="dlC2">' + D.c2.map(function (x) { return '<option value="' + esc(x) + '">'; }).join('') + '</datalist><datalist id="dlHab">' + HABS.map(function (x) { return '<option value="' + esc(x) + '">'; }).join('') + '</datalist>' +
		'<div class="g2">' + CC.campo('Clasificación 1', '<input class="cc-input" id="sC1" list="dlC1" maxlength="200" value="' + esc(S.c1) + '" placeholder="Ej. Equipo de cómputo">', { opc: 1 }) + CC.campo('Clasificación 2', '<input class="cc-input" id="sC2" list="dlC2" maxlength="200" value="' + esc(S.c2) + '" placeholder="Ej. Operativo">', { opc: 1 }) + '</div></div>' +
		'<div class="card__pie"><span class="izq">El borrador se guarda en este navegador mientras escribes.</span><button type="button" class="cc-btn cc-btn--fantasma" id="sLimpiar">Limpiar</button><button type="button" class="cc-btn cc-btn--primario" id="sGuardar">' + I('check') + 'Guardar y generar PDF</button></div></section>' +
		'<section class="card"><div class="card__head"><h2>Solicitudes recientes</h2><span class="cc-small cc-muted">' + num(D.recientes.length) + ' más recientes</span></div><div class="tabla-scroll"><table class="cc-t"><thead><tr><th>Solicitud</th><th>Fecha</th><th>Departamento</th>' + (D.superAdmin ? '<th>Hotel</th>' : '') + '<th class="der">Equipos</th><th>Clasificación</th><th>Registró</th><th>PDF</th></tr></thead><tbody>' +
		(D.recientes.length ? D.recientes.map(function (r) {
			return '<tr style="cursor:default"><td><b class="num">#' + esc(r.numero) + '</b></td><td class="num">' + CC.fcorta(r.fecha) + '</td><td>' + esc(r.depto || '—') + '</td>' + (D.superAdmin ? '<td>' + esc(r.hotel || '—') + '</td>' : '') + '<td class="der num">' + r.cantidad + '</td><td>' + esc([r.c1, r.c2].filter(Boolean).join(' · ') || '—') + '</td><td>' + esc(r.usuario || '—') + '</td>' +
				'<td><button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" data-pdf="' + r.id + '" data-num="' + esc(r.numero) + '">' + I('pdf') + 'PDF</button></td></tr>';
		}).join('') : '<tr><td colspan="8" class="mut" style="text-align:center;padding:24px">Aún no hay solicitudes.</td></tr>') + '</tbody></table></div></section></div>';
	CostaClara.hidratar(raiz);
	filas();
	CC.escaner($('#sScan'), { sm: 1, lista: EQ, ph: 'Agregar equipo por código o número de serie', filtro: function (e) { return e.estado !== 'baja' && (!h || e.idHotel === h); }, vacio: h ? 'Solo aparecen equipos del mismo hotel' : '',
		pick: function (e) {
			if (S.filas.some(function (f) { return f.e.id === e.id; })) { CC.toast('alerta', esc(e.codigo) + ' ya está en la lista', ''); return; }
			var primero = !S.filas.length;
			S.filas.push({ e: e, caracteristicas: '', color: '', centro_costos: 'CAPEX', id_fase: '', id_area: '', piso: '', tipo_habitacion: 'No aplica', id_departamento: e.idDepto || '' });
			guardar(); if (primero) render(); else filas();
			var ins = $$('#sBody tr:last-child input'); if (ins[0]) ins[0].focus();
		} });
	$('#sNum').oninput = function () { S.numero = this.value; this.classList.remove('cc-input--error'); guardar(); };
	$('#sFecha').onchange = function () { S.fecha = this.value; guardar(); };
	$('#sDepto').onchange = function () { S.depto = this.value; this.classList.remove('cc-input--error'); guardar(); };
	$('#sC1').oninput = function () { S.c1 = this.value; guardar(); };
	$('#sC2').oninput = function () { S.c2 = this.value; guardar(); };
	$('#sLimpiar').onclick = function () { if (!S.filas.length && !S.numero) return; CC.confirmar({ titulo: '¿Limpiar la solicitud?', texto: 'Se quitan los equipos y los datos capturados. No afecta a los equipos registrados.', boton: 'Limpiar', ok: function (md, terminar) { terminar(); S = vacia(); guardar(); render(); } }); };
	$('#sGuardar').onclick = enviar;
	$$('[data-pdf]').forEach(function (b) { b.onclick = function () { pdf(b.getAttribute('data-pdf'), b.getAttribute('data-num')); }; });
}
function celda(f, r, c) {
	var v = f[c[0]] == null ? '' : f[c[0]], lab = c[1] + ' de ' + f.e.codigo;
	if (c[2] === 'cc') return '<td><select data-r="' + r + '" data-k="' + c[0] + '" aria-label="' + esc(lab) + '">' + CC.opciones([['CAPEX', 'CAPEX'], ['GASTO DE OPERACION', 'Gasto de operación']], v) + '</select></td>';
	if (c[2] === 'fase' || c[2] === 'area' || c[2] === 'depto') return '<td><select data-r="' + r + '" data-k="' + c[0] + '" aria-label="' + esc(lab) + '">' + CC.opciones(delHotel(c[2] === 'fase' ? D.fases : c[2] === 'area' ? D.areas : D.deptos), v, '—') + '</select></td>';
	return '<td><input data-r="' + r + '" data-k="' + c[0] + '" value="' + esc(v) + '" aria-label="' + esc(lab) + '" class="' + c[2] + '"' + (c[2] === 'hab' ? ' list="dlHab"' : '') + '></td>';
}
function filas() {
	var b = $('#sBody');
	b.innerHTML = S.filas.length ? S.filas.map(function (f, r) {
		var e = f.e;
		return '<tr><td class="n">' + (r + 1) + '</td><td style="padding:4px 8px;min-width:200px"><b class="num" style="font-weight:600">' + esc(e.codigo) + '</b><div class="cc-small cc-muted" style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + esc(e.desc) + '</div></td>' +
			'<td style="padding:4px 8px;min-width:160px">' + esc(e.marca || '—') + '</td><td class="num" style="padding:4px 8px">' + esc(e.serie || '—') + '</td>' +
			COLS.map(function (c) { return celda(f, r, c); }).join('') + '<td style="padding:4px 8px;min-width:140px">' + esc(e.resp || D.responsable || '—') + '</td>' +
			'<td><button type="button" class="quitar-btn" data-del="' + r + '" aria-label="Quitar ' + esc(e.codigo) + '">' + I('cerrar') + '</button></td></tr>';
	}).join('') : '<tr><td colspan="' + (COLS.length + 6) + '" class="carrito__vacio">Sin equipos. Agrégalos con el buscador de arriba.</td></tr>';
	CostaClara.hidratar(b);
	$('#sCant').textContent = S.filas.length;
	b.oninput = b.onchange = function (ev) { var i = ev.target, k = i.getAttribute('data-k'); if (k) { S.filas[+i.getAttribute('data-r')][k] = i.value; guardar(); } };
	$$('[data-del]', b).forEach(function (x) { x.onclick = function () { S.filas.splice(+x.getAttribute('data-del'), 1); guardar(); if (!S.filas.length) render(); else filas(); }; });
}
function enviar() {
	var ok = true;
	if (!S.numero.trim()) { $('#sNum').classList.add('cc-input--error'); ok = false; }
	if (!S.depto) { $('#sDepto').classList.add('cc-input--error'); ok = false; }
	if (!ok) { CC.toast('error', 'Completa los datos de la solicitud', 'Número de solicitud y departamento solicitante son obligatorios.'); (S.numero.trim() ? $('#sDepto') : $('#sNum')).focus(); return; }
	if (!S.filas.length) { CC.toast('error', 'Agrega al menos un equipo', 'Búscalo por código o número de serie.'); $('#sScan input').focus(); return; }
	var btn = $('#sGuardar'); CC.ocupado(btn, true);
	var datos = { numero: S.numero.trim(), fecha: S.fecha, id_departamento: S.depto, c1: S.c1.trim(), c2: S.c2.trim(), filas: S.filas.map(function (f) { var o = { id_equipo: f.e.id }; COLS.forEach(function (c) { o[c[0]] = f[c[0]]; }); return o; }) };
	CC.api(URL_S, { accion: 'guardar', datos: JSON.stringify(datos) }).done(function (j) {
		CC.ocupado(btn, false);
		if (j && j.ok) {
			var n = datos.numero; S = vacia(); guardar();
			cargar().done(function () { pdf(j.id, n); });
			CC.toast('exito', 'Solicitud #' + esc(n) + ' guardada', datos.filas.length + (datos.filas.length === 1 ? ' equipo' : ' equipos') + '. Imprime el PDF para recabar firmas.');
			return;
		}
		if (j && j.errores) CC.toast('error', 'Revisa la solicitud', esc(Object.keys(j.errores).map(function (k) { return j.errores[k]; }).join(' ')));
		else CC.toast('error', 'No se pudo guardar', esc((j && j.mensaje) || ''));
	}).fail(function () { CC.ocupado(btn, false); });
}
function pdf(id, n) { CC.verPdf('Solicitud #' + n + ' · Alta de equipos', '../reportes/reporteSolicitudAltaEquipos.php?id=' + id); }
cargar(true);
})();
