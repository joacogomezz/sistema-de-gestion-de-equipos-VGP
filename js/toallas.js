/* Control de toallas del toallero: entrega, pendientes y devolución (procesos/toallas/toallas.php) */
(function () {
'use strict';
var $ = CC.$, $$ = CC.$$, I = CC.I, esc = CC.esc;
var raiz = $('#vistaToallas'), URL_T = '../procesos/toallas/toallas.php';
var TW = { hab: '', hues: '', cant: 2, obs: '', q: '' }, D = null, desfase = 0;

function ahora() { return Date.now() + desfase; }
function horasTxt(ms) { var m = Math.max(0, Math.floor(ms / 60000)), h = Math.floor(m / 60); return h ? h + ' h ' + CC.f2(m % 60) + ' min' : m + ' min'; }
function hhmm(t) { var d = new Date(t); return CC.f2(d.getHours()) + ':' + CC.f2(d.getMinutes()); }
function claseT(ms) { var h = ms / 3600000; return h >= D.limite * 2 ? 'tarde' : h >= D.limite ? 'alerta' : ''; }

function cargar() { return CC.api(URL_T, { accion: 'estado' }, 'GET').done(function (j) { if (j && j.ok) { var pri = !D; D = j; desfase = j.ahora - Date.now(); if (pri) render(); else { kpis(); lista(); } } }); }

function render() {
	raiz.innerHTML = '<div class="movil-col"><div class="toa-top">' + I('toallas') + '<div><b>Control de toallas</b><small>' + esc(D.hotel) + ' · ' + esc(D.usuario) + '</small></div></div><div class="toa-kpis" id="toaK"></div>' +
		'<section class="tcard" aria-labelledby="hEnt"><h2 id="hEnt" class="cc-h2" style="margin:0">Entregar toallas</h2>' +
		CC.campo('Número de habitación', '<input class="cc-input big-in num" id="tHab" inputmode="numeric" maxlength="10" placeholder="Ej. 1204" value="' + esc(TW.hab) + '" autocomplete="off">', { req: 1 }) +
		CC.campo('Nombre del huésped', '<input class="cc-input big-in" id="tHues" maxlength="120" placeholder="Como aparece en su pulsera o reservación" value="' + esc(TW.hues) + '" autocomplete="off" style="font-size:16px!important">', { req: 1 }) +
		'<div class="cc-field"><span class="cc-label" id="lCant">Cantidad</span><div class="stepper" role="group" aria-labelledby="lCant"><button type="button" id="tMenos" aria-label="Una toalla menos"><span style="font:600 28px/1 var(--font-sans)">−</span></button><output id="tCant" aria-live="polite"></output><button type="button" id="tMas" aria-label="Una toalla más"><span style="font:600 28px/1 var(--font-sans)">+</span></button></div></div>' +
		CC.campo('Observaciones', '<input class="cc-input" id="tObs" maxlength="255" placeholder="Ej. Pidió 2 extra para niños" value="' + esc(TW.obs) + '" style="height:48px">', { opc: 1 }) +
		'<button type="button" class="cc-btn cc-btn--primario btn-bloque" id="tReg">' + I('check') + 'Registrar entrega</button></section>' +
		'<section aria-labelledby="hPen" style="display:flex;flex-direction:column;gap:12px"><div class="cc-row" style="justify-content:space-between"><h2 id="hPen" class="cc-h2" style="margin:0">Toallas pendientes de devolución</h2><span class="cc-small cc-muted">Alerta a las ' + D.limite + ' h</span></div>' +
		'<label class="cc-buscador"><i data-i="buscar"></i><input class="cc-input" id="tQ" inputmode="numeric" placeholder="Buscar habitación" value="' + esc(TW.q) + '" style="height:48px" aria-label="Buscar habitación"></label><div id="tLista" style="display:flex;flex-direction:column;gap:12px"></div></section></div>';
	CostaClara.hidratar(raiz);
	kpis(); lista(); cant(TW.cant);
	$('#tMenos').onclick = function () { cant(TW.cant - 1); };
	$('#tMas').onclick = function () { cant(TW.cant + 1); };
	$('#tHab').oninput = function () { this.value = this.value.replace(/[^A-Za-z0-9-]/g, ''); TW.hab = this.value; CC.errEn(this, ''); };
	$('#tHues').oninput = function () { TW.hues = this.value; CC.errEn(this, ''); };
	$('#tObs').oninput = function () { TW.obs = this.value; };
	$('#tQ').oninput = function () { TW.q = this.value.trim(); lista(); };
	$('#tReg').onclick = entregar;
}
function kpis() {
	$('#toaK').innerHTML = '<div><b>' + D.entregadasHoy + '</b><small>Entregadas hoy</small></div><div><b>' + D.porDevolver + '</b><small>Por devolver</small></div>' +
		'<div style="' + (D.tarde ? 'border-color:var(--alerta);background:var(--alerta-suave);color:var(--alerta-texto)' : '') + '"><b>' + D.tarde + '</b><small style="color:inherit">Más de ' + D.limite + ' h</small></div>';
}
function lista() {
	var q = CC.sinAc(TW.q), ls = D.pendientes.filter(function (x) { return !q || CC.sinAc(x.hab + ' ' + x.hues).indexOf(q) >= 0; }).sort(function (a, b) { return a.t - b.t; });
	$('#tLista').innerHTML = ls.length ? ls.map(function (x) {
		var ms = ahora() - x.t, c = claseT(ms), p = x.ent - x.reg;
		return '<article class="tcard' + (c === 'tarde' ? ' tcard--tarde' : c === 'alerta' ? ' tcard--alerta' : '') + '"><div class="tcard__top"><div class="hab">' + esc(x.hab) + '<small>' + esc(x.hues) + '</small></div>' + (x.reg ? '<span class="est est--parc">Parcial</span>' : '<span class="est est--pend">Pendiente</span>') + '</div>' +
			'<div class="cuentas3"><div><b>' + x.ent + '</b><small>Entregadas</small></div><div><b>' + x.reg + '</b><small>Regresadas</small></div><div class="pend"><b>' + p + '</b><small>Pendientes</small></div></div>' +
			'<div class="tiempo ' + c + '">' + I(c ? 'alerta' : 'reloj') + '<span>Registro <b>' + hhmm(x.t) + '</b> · hace <b>' + horasTxt(ms) + '</b>' + (x.u ? ' · ' + esc(x.u) : '') + '</span></div>' + (x.obs ? '<div class="cc-small cc-muted">' + esc(x.obs) + '</div>' : '') +
			'<button type="button" class="cc-btn cc-btn--secundario btn-bloque" data-dev="' + x.id + '" style="height:52px!important">' + I('check') + 'Registrar devolución</button></article>';
	}).join('') : '<div class="tcard" style="align-items:center;text-align:center;color:var(--texto-secundario)">' + (q ? 'No hay toallas pendientes de “' + esc(TW.q) + '”.' : 'Todas las toallas fueron devueltas.') + '</div>';
	CostaClara.hidratar($('#tLista'));
	$$('[data-dev]').forEach(function (b) { b.onclick = function () { devolver(D.pendientes.filter(function (x) { return x.id === +b.getAttribute('data-dev'); })[0]); }; });
}
function cant(n) { TW.cant = Math.max(1, Math.min(20, n)); $('#tCant').innerHTML = TW.cant + '<small>' + (TW.cant === 1 ? 'toalla' : 'toallas') + '</small>'; $('#tMenos').disabled = TW.cant <= 1; $('#tMas').disabled = TW.cant >= 20; }

function entregar() {
	var ok = true;
	if (!/^[A-Za-z0-9-]{1,10}$/.test(TW.hab)) ok = CC.errEn($('#tHab'), TW.hab ? 'Usa solo números o letras, por ejemplo 1204.' : 'Escribe el número de habitación.') && ok;
	if (TW.hues.trim().length < 3) ok = CC.errEn($('#tHues'), 'Escribe el nombre del huésped.') && ok;
	if (!ok) { $('.cc-input--error', raiz).focus(); return; }
	var btn = $('#tReg'), ya = D.pendientes.some(function (x) { return x.hab === TW.hab; }), n = TW.cant, hab = TW.hab, hues = TW.hues.trim();
	CC.ocupado(btn, true);
	CC.api(URL_T, { accion: 'entregar', habitacion: hab, huesped: hues, cantidad: n, observaciones: TW.obs.trim() }).done(function (j) {
		CC.ocupado(btn, false);
		if (j && j.ok) {
			TW.hab = ''; TW.hues = ''; TW.obs = ''; TW.cant = 2;
			$('#tHab').value = ''; $('#tHues').value = ''; $('#tObs').value = ''; cant(2);
			cargar(); CC.toast('exito', n + (n === 1 ? ' toalla entregada' : ' toallas entregadas') + ' · Hab. ' + esc(hab), esc(hues) + (ya ? '. Esta habitación ya tenía toallas pendientes.' : ''));
			$('#tHab').focus(); return;
		}
		if (j && j.errores) { if (j.errores.habitacion) CC.errEn($('#tHab'), j.errores.habitacion); if (j.errores.huesped) CC.errEn($('#tHues'), j.errores.huesped); if (j.errores.cantidad) CC.toast('error', j.errores.cantidad, ''); }
		else CC.toast('error', 'No se pudo registrar', esc((j && j.mensaje) || ''));
	}).fail(function () { CC.ocupado(btn, false); });
}
function devolver(x) {
	var p = x.ent - x.reg, n = p;
	CC.modal('<div class="mod-head"><h2>Devolución · Hab. ' + esc(x.hab) + '</h2><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono" data-cancel aria-label="Cerrar">' + I('cerrar') + '</button></div>' +
		'<div class="mform"><p class="cc-muted" style="margin:0">' + esc(x.hues) + ' · ' + x.ent + ' entregadas, ' + x.reg + ' regresadas antes.</p><div class="cc-field"><span class="cc-label" id="lDev">¿Cuántas regresan ahora?</span><div class="stepper" role="group" aria-labelledby="lDev"><button type="button" id="dMenos" aria-label="Una menos"><span style="font:600 28px/1 var(--font-sans)">−</span></button><output id="dN" aria-live="polite"></output><button type="button" id="dMas" aria-label="Una más"><span style="font:600 28px/1 var(--font-sans)">+</span></button></div></div>' +
		'<button type="button" class="cc-btn cc-btn--primario btn-bloque" id="dOk">' + I('check') + '<span class="lbl">Registrar devolución</span></button><button type="button" class="cc-btn cc-btn--fantasma" id="dFalta" style="color:var(--peligro);height:44px">Cerrar con faltante</button></div>', { ancho: 440, foco: '#dOk' });
	function pinta() { $('#dN').innerHTML = n + '<small>de ' + p + ' pendientes</small>'; $('#dMenos').disabled = n <= 1; $('#dMas').disabled = n >= p; $('#dOk .lbl').textContent = n === p ? 'Registrar devolución completa' : 'Registrar devolución parcial'; }
	pinta();
	$('#dMenos').onclick = function () { n = Math.max(1, n - 1); pinta(); };
	$('#dMas').onclick = function () { n = Math.min(p, n + 1); pinta(); };
	$('#dOk').onclick = function () {
		var b = this; CC.ocupado(b, true);
		CC.api(URL_T, { accion: 'devolver', id: x.id, cantidad: n }).done(function (j) {
			if (j && j.ok) { CC.cierraModal(); cargar(); var completa = n === p; CC.toast('exito', completa ? 'Hab. ' + esc(x.hab) + ' sin pendientes' : n + ' regresadas · Hab. ' + esc(x.hab), completa ? 'Se cerró el registro y pasó al historial.' : 'Faltan ' + (p - n) + '.'); }
			else { CC.ocupado(b, false); CC.toast('error', 'No se pudo registrar', esc((j && j.mensaje) || '')); }
		}).fail(function () { CC.ocupado(b, false); });
	};
	$('#dFalta').onclick = function () {
		CC.confirmar({
			titulo: '¿Cerrar la habitación ' + esc(x.hab) + ' con ' + p + ' faltante' + (p === 1 ? '' : 's') + '?', texto: 'El registro pasa al historial como “Cerrado con faltante”. Avisa a Recepción para el cargo a la habitación.', boton: 'Cerrar con faltante',
			ok: function (md, terminar, reintentar) {
				CC.api(URL_T, { accion: 'faltante', id: x.id }).done(function (j) {
					if (j && j.ok) { terminar(); cargar(); CC.toast('alerta', 'Hab. ' + esc(x.hab) + ' cerrada con ' + p + ' faltante' + (p === 1 ? '' : 's'), 'Quedó en el historial.'); }
					else { reintentar(); CC.toast('error', 'No se pudo cerrar', esc((j && j.mensaje) || '')); }
				}).fail(reintentar);
			}
		});
	};
}
cargar();
setInterval(function () { if (D && $('#modal').hidden) cargar(); }, 60000);
})();
