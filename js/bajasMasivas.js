/* Bajas masivas: lista de equipos con valor residual y observación por equipo + historial (procesos/bajasMasivas/bajasMasivas.php) */
(function () {
'use strict';
var $ = CC.$, $$ = CC.$$, I = CC.I, esc = CC.esc, num = CC.num, sinAc = CC.sinAc;
var raiz = $('#vistaMasivas'), URL_M = '../procesos/bajasMasivas/bajasMasivas.php', CLAVE = 'baja-masiva-borrador';
var M = { tab: 'nueva', q: '', items: [], depto: '', motivo: '', destino: '', obs: '' }, D = { lista: [], motivos: [], destinos: [], deptos: [], superAdmin: false }, EQ = [];

function folio(h) { return 'BM-' + String(h.fecha || '').slice(0, 4) + '-' + ('00' + h.n).slice(-3); }
function guardar() { CC.guarda(CLAVE, { items: M.items.map(function (x) { return { id: x.e.id, v: x.v, obs: x.obs }; }), depto: M.depto, motivo: M.motivo, destino: M.destino, obs: M.obs }); }
function porId(id) { id = +id; return EQ.filter(function (e) { return e.id === id; })[0]; }
function hotelLista() { return M.items.length ? M.items[0].e.idHotel : 0; }
function disp() { var h = hotelLista(); return EQ.filter(function (e) { return e.estado !== 'baja' && (!h || e.idHotel === h); }); }
function enLista(e) { return M.items.some(function (x) { return x.e.id === e.id; }); }
function agrega(e, silencio) {
	if (!e || enLista(e) || e.estado === 'baja') return false;
	var h = hotelLista();
	if (h && e.idHotel !== h) { if (!silencio) CC.toast('alerta', esc(e.codigo) + ' es de ' + esc(e.hotel), 'Una baja masiva solo puede incluir equipos de un mismo hotel.'); return false; }
	M.items.push({ e: e, v: 0, obs: e.estado === 'reparacion' ? 'Falla sin reparación' : '' });
	if (!silencio) { if (M.items.length === 1) render(); else { cart(); res(); } guardar(); }
	return true;
}
function tot() { return M.items.reduce(function (s, x) { return s + (Number(x.v) || 0); }, 0); }

function cargar(primera) {
	return jQuery.when(CC.api(URL_M, { accion: 'listar' }, 'GET'), CC.equipos(true)).done(function (a, eqs) {
		var j = a[0] || a; if (j && j.ok) D = j; EQ = eqs || [];
		if (primera) {
			var b = CC.lee(CLAVE, null);
			if (b) { M.depto = b.depto || ''; M.motivo = b.motivo || ''; M.destino = b.destino || ''; M.obs = b.obs || ''; (b.items || []).forEach(function (x) { var e = porId(x.id); if (e && e.estado !== 'baja' && agrega(e, true)) { var it = M.items[M.items.length - 1]; it.v = x.v || 0; it.obs = x.obs || ''; } }); }
			// Viene de Equipos con varios seleccionados
			if (new URLSearchParams(location.search).get('desdeEquipos')) {
				var sel = []; try { sel = JSON.parse(sessionStorage.getItem('cc-baja-masiva') || '[]'); sessionStorage.removeItem('cc-baja-masiva'); } catch (e) {}
				var n = 0; sel.forEach(function (x) { if (agrega(porId(x.id), true)) n++; });
				history.replaceState(null, '', location.pathname);
				if (n) setTimeout(function () { CC.toast('exito', n + (n === 1 ? ' equipo agregado' : ' equipos agregados'), 'Desde la pantalla Equipos.'); }, 50);
				if (n < sel.length) setTimeout(function () { CC.toast('alerta', (sel.length - n) + ' no se agregaron', 'Ya estaban dados de baja o son de otro hotel.'); }, 80);
				guardar();
			}
		}
		render();
	});
}

function render() {
	var nueva = M.tab === 'nueva';
	raiz.innerHTML = '<div class="vista"><div class="enc"><div><h1 class="cc-h1">Bajas masivas</h1><p class="sub">Da de baja varios equipos en un solo formato, cada uno con su observación.</p></div><div class="seg-tabs" role="tablist"><button type="button" role="tab" data-tab="nueva" aria-selected="' + nueva + '">' + I('mas') + 'Nueva baja masiva</button><button type="button" role="tab" data-tab="hist" aria-selected="' + !nueva + '">' + I('reloj') + 'Historial · ' + D.lista.length + '</button></div></div><div id="masBody"></div></div>';
	$$('[data-tab]').forEach(function (b) { b.onclick = function () { M.tab = b.getAttribute('data-tab'); render(); }; });
	var body = $('#masBody');
	if (!nueva) {
		body.innerHTML = '<section class="card"><div class="tabla-scroll"><table class="cc-t"><thead><tr><th>Folio</th><th>Fecha</th><th>Hotel</th><th>Departamento</th><th>Motivo</th><th class="der">Equipos</th><th class="der">Valor residual</th><th>Destino</th><th>Registró</th><th>PDF</th>' + (D.superAdmin ? '<th></th>' : '') + '</tr></thead><tbody>' +
			(D.lista.length ? D.lista.map(function (h) {
				return '<tr style="cursor:default"><td><b class="num">' + folio(h) + '</b></td><td class="num">' + CC.fcorta(h.fecha) + '</td><td>' + esc(h.hotel || '—') + '</td><td style="max-width:220px;overflow:hidden;text-overflow:ellipsis" title="' + esc(h.depto) + '">' + esc(h.depto || '—') + '</td><td>' + esc(h.motivo) + '</td><td class="der num">' + num(h.total) + '</td><td class="der num">' + CC.dinero(h.valor) + '</td><td>' + esc(h.destino) + '</td><td>' + esc(h.usuario || '—') + '</td>' +
					'<td><button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" data-hp="' + h.id + '">' + I('pdf') + 'Ver PDF</button></td>' +
					(D.superAdmin ? '<td><button type="button" class="acc-btn" data-del="' + h.id + '" aria-label="Eliminar ' + folio(h) + '" style="color:var(--peligro)">' + I('baja') + '</button></td>' : '') + '</tr>';
			}).join('') : '<tr><td colspan="11" class="mut" style="text-align:center;padding:24px">Aún no hay bajas masivas registradas.</td></tr>') + '</tbody></table></div></section>';
		CostaClara.hidratar(raiz);
		$$('[data-hp]').forEach(function (b) { b.onclick = function () { var h = D.lista.filter(function (x) { return x.id === +b.getAttribute('data-hp'); })[0]; informe(h.id, folio(h), h.idHotel); }; });
		$$('[data-del]').forEach(function (b) { b.onclick = function () { eliminar(D.lista.filter(function (x) { return x.id === +b.getAttribute('data-del'); })[0]); }; });
		return;
	}
	var hotelTxt = M.items.length ? M.items[0].e.hotel : 'Se toma de los equipos agregados';
	var deptos = D.deptos.filter(function (d) { return !hotelLista() || d.idHotel === hotelLista(); });
	body.innerHTML = '<div class="dos dos--mas"><section class="card"><div class="card__head"><div><h2>Buscar equipos</h2><p>Por código, número de serie o descripción</p></div></div><div class="card__body"><label class="cc-buscador"><i data-i="buscar"></i><input class="cc-input" id="mq" placeholder="Código o número de serie" value="' + esc(M.q) + '" style="height:44px" aria-label="Buscar equipos para la baja" autocomplete="off"></label>' +
		'<div class="cc-row" style="gap:8px;flex-wrap:wrap"><button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" id="mDep">' + I('departamentos') + 'Agregar todos los de un departamento</button><button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" id="mRep">' + I('alerta') + 'Agregar los que están en mantenimiento</button></div><div class="carrito" id="mRes"></div></div></section>' +
		'<section class="card"><div class="card__head"><div><h2>Equipos a dar de baja</h2><p id="mTotTxt"></p></div><button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" id="mVaciar">Vaciar lista</button></div><div class="tabla-scroll"><table class="cc-t"><thead><tr><th>Código</th><th>Descripción</th><th class="der">Valor residual</th><th>Observación (falla / motivo)</th><th>Estado</th><th></th></tr></thead><tbody id="mCart"></tbody><tfoot id="mFoot"></tfoot></table></div>' +
		'<div class="card__body" style="border-top:1px solid var(--borde)"><h3 class="cc-h3" style="margin:0">Datos generales</h3><div class="g2">' +
		CC.campo('Hotel', '<input class="cc-input" value="' + esc(hotelTxt) + '" readonly>', { ayuda: 'Todos los equipos deben ser del mismo hotel.' }) +
		CC.campo('Departamento', '<select class="cc-select" id="mDepto">' + CC.opciones(deptos.map(function (d) { return [d.id, d.nombre]; }), M.depto, 'Varios / sin especificar') + '</select>', { opc: 1 }) +
		CC.campo('Motivo de la baja masiva', '<select class="cc-select" id="mMot">' + CC.opciones(D.motivos, M.motivo, 'Selecciona el motivo') + '</select>', { req: 1 }) +
		CC.campo('Destino de los equipos', '<select class="cc-select" id="mDes">' + CC.opciones(D.destinos, M.destino, 'Selecciona el destino') + '</select>', { req: 1 }) + '</div>' +
		CC.campo('Observaciones generales', '<textarea class="cc-input" id="mObs" maxlength="1000" placeholder="Ej. Renovación de cómputo del área administrativa, oficio 15/2026.">' + esc(M.obs) + '</textarea>', { opc: 1 }) + '</div>' +
		'<div class="card__pie" style="justify-content:flex-end"><button type="button" class="cc-btn cc-btn--peligro" id="mOk">' + I('bajas-masivas') + 'Registrar baja masiva</button></div></section></div>';
	CostaClara.hidratar(raiz);
	res(); cart();
	$('#mq').oninput = function () { M.q = this.value; res(); };
	$('#mq').onkeydown = function (e) {
		if (e.key !== 'Enter') return;
		e.preventDefault(); var v = sinAc(this.value.trim()); if (!v) return;
		var x = EQ.filter(function (q) { return sinAc(q.codigo) === v || (q.serie && sinAc(q.serie) === v); })[0];
		if (!x) { CC.toast('alerta', 'Sin coincidencia exacta', 'Elige el equipo en la lista de resultados.'); return; }
		if (x.estado === 'baja') { CC.toast('alerta', esc(x.codigo) + ' ya está dado de baja', ''); return; }
		if (enLista(x)) { CC.toast('alerta', esc(x.codigo) + ' ya está en la lista', ''); return; }
		M.q = ''; this.value = ''; if (agrega(x)) { res(); CC.toast('exito', esc(x.codigo) + ' agregado', ''); }
	};
	$('#mRep').onclick = function () { var n = 0; disp().forEach(function (e) { if ((e.estado === 'reparacion' || e.estado === 'mantenimiento') && agrega(e, true)) n++; }); guardar(); render(); CC.toast(n ? 'exito' : 'info', n ? n + ' equipos agregados' : 'No hay más equipos en mantenimiento o reparación', ''); };
	$('#mDep').onclick = porDepartamento;
	$('#mVaciar').onclick = function () { if (!M.items.length) return; M.items = []; guardar(); render(); CC.toast('info', 'Lista vaciada', ''); };
	$('#mDepto').onchange = function () { M.depto = this.value; guardar(); };
	$('#mMot').onchange = function () { M.motivo = this.value; CC.errEn(this, ''); guardar(); };
	$('#mDes').onchange = function () { M.destino = this.value; CC.errEn(this, ''); guardar(); };
	$('#mObs').oninput = function () { M.obs = this.value; guardar(); };
	$('#mOk').onclick = registrar;
}
function res() {
	var q = sinAc(M.q.trim()), l = disp().filter(function (e) { return q ? sinAc([e.codigo, e.serie, e.desc, e.marca].join(' ')).indexOf(q) >= 0 : e.estado !== 'activo'; }).slice(0, 10);
	$('#mRes').innerHTML = (q ? '' : '<div class="carrito__tot" style="background:none;color:var(--texto-secundario);font-weight:400">Sugeridos: en mantenimiento o reparación</div>') +
		(l.length ? l.map(function (e) {
			var y = enLista(e);
			return '<div class="citem">' + CC.thumb(e) + '<div style="min-width:0"><b>' + esc(e.codigo) + '</b><small>' + esc(e.desc) + ' · ' + esc(e.marca) + ' · ' + esc(e.serie || 's/serie') + ' · ' + esc(e.depto || '—') + (hotelLista() ? '' : ' · ' + esc(e.hotel)) + '</small></div>' +
				(y ? '<span class="cc-small" style="color:var(--exito-texto);display:inline-flex;gap:4px;align-items:center">' + I('check') + 'Agregado</span>' : '<button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" data-add="' + e.id + '">' + I('mas') + 'Agregar</button>') + '</div>';
		}).join('') : '<div class="carrito__vacio">' + (q ? 'Ningún equipo disponible coincide con “' + esc(M.q) + '”.' : 'No hay equipos en mantenimiento o reparación. Busca por código.') + '</div>');
	CostaClara.hidratar($('#mRes'));
	$$('[data-add]').forEach(function (b) { b.onclick = function () { agrega(porId(b.getAttribute('data-add'))); }; });
}
function pie() {
	$('#mFoot').innerHTML = M.items.length ? '<tr><td colspan="2" style="font-weight:600">' + M.items.length + (M.items.length === 1 ? ' equipo' : ' equipos') + '</td><td class="der num" style="font-weight:600">' + CC.dinero(tot()) + '</td><td colspan="3"></td></tr>' : '';
	$('#mTotTxt').textContent = M.items.length ? M.items.length + ' equipos · valor residual ' + CC.dinero(tot()) : 'Agrégalos con el buscador.';
}
function cart() {
	$('#mCart').innerHTML = M.items.length ? M.items.map(function (x, i) {
		var e = x.e;
		return '<tr style="cursor:default"><td><b class="num">' + esc(e.codigo) + '</b></td><td style="max-width:200px;overflow:hidden;text-overflow:ellipsis" title="' + esc(e.desc + ' · ' + e.marca) + '">' + esc(e.desc) + ' · ' + esc(e.marca) + '</td>' +
			'<td class="der"><input class="cc-input num" data-v="' + i + '" value="' + x.v + '" inputmode="decimal" style="height:32px;width:100px;text-align:right" aria-label="Valor residual de ' + esc(e.codigo) + '"></td>' +
			'<td><input class="cc-input" data-o="' + i + '" value="' + esc(x.obs) + '" maxlength="500" placeholder="Ej. pantalla rota" style="height:32px;min-width:180px" aria-label="Observación de ' + esc(e.codigo) + '"></td><td>' + CC.chipEst(e.estado) + '</td>' +
			'<td><button type="button" class="quitar-btn" data-q="' + i + '" aria-label="Quitar ' + esc(e.codigo) + '">' + I('cerrar') + '</button></td></tr>';
	}).join('') : '<tr><td colspan="6" class="carrito__vacio" style="height:auto">La lista está vacía.</td></tr>';
	CostaClara.hidratar($('#mCart'));
	pie();
	$$('[data-v]').forEach(function (i) { i.oninput = function () { var v = CC.parseMonto(i.value); M.items[+i.getAttribute('data-v')].v = v > 0 ? v : 0; i.classList.toggle('cc-input--error', isNaN(v) || v < 0); pie(); guardar(); }; });
	$$('[data-o]').forEach(function (i) { i.oninput = function () { M.items[+i.getAttribute('data-o')].obs = i.value; guardar(); }; });
	$$('[data-q]').forEach(function (b) { b.onclick = function () { M.items.splice(+b.getAttribute('data-q'), 1); guardar(); if (!M.items.length) render(); else { cart(); res(); } }; });
}
function porDepartamento() {
	var c = {}; disp().forEach(function (x) { var k = x.idHotel + '|' + (x.depto || 'Sin departamento'); c[k] = c[k] || { n: 0, d: x.depto || 'Sin departamento', h: x.hotel }; c[k].n++; });
	var ks = Object.keys(c).sort(function (a, b) { return c[a].d.localeCompare(c[b].d, 'es'); });
	if (!ks.length) { CC.toast('info', 'No hay equipos disponibles', ''); return; }
	CC.confirmar({
		titulo: 'Agregar todos los equipos de un departamento', texto: 'Se agregan a la lista; puedes quitar los que no se den de baja antes de registrar.', boton: 'Agregar', peligro: false, tono: 'cc-tono-mar', icono: 'departamentos',
		extra: CC.campo('Departamento', '<select class="cc-select" id="cfDep">' + CC.opciones(ks.map(function (k) { return [k, c[k].d + (hotelLista() ? '' : ' · ' + c[k].h) + ' (' + c[k].n + ')']; }), '', 'Selecciona') + '</select>'),
		validar: function () { return CC.errEn($('#cfDep'), $('#cfDep').value ? '' : 'Elige un departamento.'); },
		ok: function (md, terminar) {
			var k = $('#cfDep').value, p = k.split('|'), h = +p[0], n = 0;
			disp().forEach(function (x) { if (x.idHotel === h && (x.depto || 'Sin departamento') === p.slice(1).join('|') && agrega(x, true)) n++; });
			terminar(); guardar(); render(); CC.toast('exito', n + ' equipos de ' + esc(c[k].d) + ' agregados', '');
		}
	});
}
function registrar() {
	var ok = true;
	if (!M.motivo) ok = CC.errEn($('#mMot'), 'Elige el motivo.') && ok;
	if (!M.destino) ok = CC.errEn($('#mDes'), 'Elige el destino.') && ok;
	if (M.items.length < 2) { CC.toast('error', M.items.length ? 'Agrega al menos 2 equipos' : 'La lista está vacía', 'Para un solo equipo usa Baja individual.'); ok = false; }
	if ($$('#mCart .cc-input--error').length) { CC.toast('error', 'Revisa los valores residuales', 'Escribe solo números, por ejemplo 850.00.'); ok = false; }
	if (!ok) return;
	var n = M.items.length, v = tot();
	CC.confirmar({
		titulo: '¿Registrar la baja masiva de ' + n + ' equipos?', escribir: 'BAJA', boton: 'Registrar baja de ' + n, icono: 'bajas-masivas',
		texto: 'Valor residual total ' + CC.dinero(v) + '. Todos pasarán a “Dado de baja”.',
		ok: function (md, terminar, reintentar) {
			CC.api(URL_M, { accion: 'registrar', items: JSON.stringify(M.items.map(function (x) { return { id: x.e.id, valor: x.v, obs: x.obs.trim() }; })), id_departamento: M.depto, motivo: M.motivo, destino: M.destino, observaciones: M.obs.trim() }).done(function (j) {
				if (j && j.ok) {
					terminar(); M = { tab: 'hist', q: '', items: [], depto: '', motivo: '', destino: '', obs: '' }; guardar();
					var f = 'BM-' + new Date().getFullYear() + '-' + ('00' + j.n).slice(-3);
					cargar().done(function () { informe(j.id, f, j.idHotel); });
					CC.toast('exito', 'Baja masiva ' + f + ' registrada', j.total + ' equipos · ' + CC.dinero(j.valor) + '.');
					return;
				}
				reintentar(); if (j && j.errores) { terminar(); CC.toast('error', 'Revisa la baja masiva', esc(Object.keys(j.errores).map(function (k) { return j.errores[k]; }).join(' '))); }
				else CC.toast('error', 'No se pudo registrar', esc((j && j.mensaje) || ''));
			}).fail(reintentar);
		}
	});
}
function informe(id, fol, hotel) { CC.verPdf(fol + ' · Formato de baja masiva', '../reportes/informeTecnicoBajaMasiva.php?id_baja=' + id + (hotel ? '&id_hotel=' + hotel : '')); }
function eliminar(h) {
	CC.confirmar({
		titulo: '¿Eliminar ' + folio(h) + '?', boton: 'Eliminar registro', escribir: 'ELIMINAR',
		texto: 'Úsalo solo si la baja se capturó por error. Los ' + h.total + ' equipos volverán a estar Activos.',
		ok: function (md, terminar, reintentar) {
			CC.api(URL_M, { accion: 'eliminar', id: h.id }).done(function (j) {
				if (j && j.ok) { terminar(); cargar(); CC.toast('exito', folio(h) + ' eliminada', j.restaurados + ' equipos revisados.'); }
				else { reintentar(); CC.toast('error', 'No se pudo eliminar', esc((j && j.mensaje) || '')); }
			}).fail(reintentar);
		}
	});
}
cargar(true);
})();
