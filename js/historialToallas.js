/* Historial de toallas: filtros, detalle, reactivar/eliminar y configuración de conservación (procesos/toallas/toallas.php) */
(function () {
'use strict';
var $ = CC.$, $$ = CC.$$, I = CC.I, esc = CC.esc, num = CC.num;
var raiz = $('#vistaHistToallas'), URL_T = '../procesos/toallas/toallas.php';
var HF = CC.lee('hist-toallas', { per: '7', hab: '', est: '' }), PAG = { n: 1, size: 25 }, D = { lista: [], retencion: '1_MONTH' };
var EST_T = { ok: ['est--ok', 'Cerrado completo'], falta: ['est--falta', 'Cerrado con faltante'] };
var RET = [['1_MONTH', '1 mes'], ['2_MONTH', '2 meses'], ['90_DAY', '90 días'], ['1_YEAR', '1 año']];

function haceDias(n) { var d = new Date(); d.setDate(d.getDate() - n); return CC.iso(d); }
function cargar() { return CC.api(URL_T, { accion: 'historial' }, 'GET').done(function (j) { if (j && j.ok) { D = j; render(); } }); }

function render() {
	var hoy = CC.iso(new Date()), desde = HF.per === 'todo' ? '' : HF.per === '0' ? hoy : haceDias(+HF.per), hasta = HF.per === '1' ? haceDias(1) : '';
	var vis = D.lista.filter(function (x) { var f = x.fecha.slice(0, 10); return (!desde || f >= desde) && (!hasta || f <= hasta) && (!HF.hab || x.hab.indexOf(HF.hab) >= 0) && (!HF.est || x.est === HF.est); });
	var pags = Math.max(1, Math.ceil(vis.length / PAG.size)); if (PAG.n > pags) PAG.n = pags;
	var ini = (PAG.n - 1) * PAG.size, items = vis.slice(ini, ini + PAG.size);
	var retTxt = (RET.filter(function (r) { return r[0] === D.retencion; })[0] || ['', D.retencion])[1];
	raiz.innerHTML = '<div class="vista"><div class="enc"><div><div class="enc__tit"><h1 class="cc-h1">Historial de toallas</h1><span class="contador"><b>' + num(vis.length) + '</b>registros</span></div><p class="sub">Entregas cerradas: devueltas completas o con faltante. Se conservan ' + esc(retTxt) + '.</p></div></div>' +
		'<div class="filtros"><label class="cc-field" style="flex-direction:row;align-items:center;gap:8px"><span class="cc-label">Fecha</span><select class="cc-select" id="hPer" style="width:auto;height:34px">' + CC.opciones([['0', 'Hoy'], ['1', 'Ayer'], ['7', 'Últimos 7 días'], ['30', 'Últimos 30 días'], ['todo', 'Todo el historial']], HF.per) + '</select></label>' +
		'<label class="cc-buscador" style="max-width:200px;flex:0 1 200px"><i data-i="hotel"></i><input class="cc-input" id="hHab" inputmode="numeric" placeholder="Habitación" value="' + esc(HF.hab) + '" aria-label="Habitación"></label>' +
		'<label class="cc-field" style="flex-direction:row;align-items:center;gap:8px"><span class="cc-label">Estatus</span><select class="cc-select" id="hEst" style="width:auto;height:34px">' + CC.opciones([['', 'Todos'], ['ok', 'Cerrado completo'], ['falta', 'Cerrado con faltante']], HF.est) + '</select></label></div>' +
		'<section class="card"><div class="tabla-scroll"><table class="cc-t"><thead><tr><th>Fecha</th><th>Habitación</th><th>Huésped</th><th class="der">Cantidad</th><th>Estatus de cierre</th><th>Usuario</th><th>Observaciones</th><th class="c-acc">Acción</th></tr></thead><tbody>' +
		(items.length ? items.map(function (x) {
			var e = EST_T[x.est];
			return '<tr style="cursor:default"><td class="num">' + CC.fcorta(x.fecha) + ' · ' + esc(x.fecha.slice(11, 16)) + '</td><td><b class="num">' + esc(x.hab) + '</b></td><td>' + esc(x.hues) + '</td><td class="der num">' + x.cant + (x.est === 'falta' ? ' <span class="mut">sin regresar</span>' : '') + '</td><td><span class="est ' + e[0] + '">' + e[1] + '</span></td><td>' + esc(x.u) + '</td>' +
				'<td class="mut" style="max-width:260px;overflow:hidden;text-overflow:ellipsis" title="' + esc(x.obs) + '">' + (x.obs ? esc(x.obs) : '—') + '</td><td><button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-det="' + x.id + '">' + I('ver') + 'Ver</button></td></tr>';
		}).join('') : '<tr><td colspan="8" class="carrito__vacio" style="height:auto">No hay registros con esos filtros.</td></tr>') + '</tbody></table></div>' +
		(pags > 1 ? '<div class="cc-pagina"><span>Mostrando ' + num(ini + 1) + '–' + num(Math.min(ini + PAG.size, vis.length)) + ' de ' + num(vis.length) + '</span><div class="pags"><button type="button" data-pg="' + (PAG.n - 1) + '" aria-label="Página anterior"' + (PAG.n <= 1 ? ' disabled' : '') + '>' + I('izquierda') + '</button><span class="cc-small" style="padding:0 8px">' + PAG.n + ' / ' + pags + '</span><button type="button" data-pg="' + (PAG.n + 1) + '" aria-label="Página siguiente"' + (PAG.n >= pags ? ' disabled' : '') + '>' + I('derecha') + '</button></div></div>' : '') + '</section>' +
		'<section class="card"><div class="card__head"><div><h2>Configuración</h2><p>Aplica a todo el historial de toallas del hotel.</p></div></div><div class="card__body"><div class="config">' +
		'<div class="cc-stack" style="gap:12px"><h3 class="cc-h3" style="margin:0">Conservación del historial</h3>' + CC.campo('Borrar automáticamente registros más antiguos que', '<select class="cc-select" id="hRet">' + CC.opciones(RET, D.retencion) + '</select>', { ayuda: 'El sistema borra solo los registros cerrados que superan este tiempo. Las toallas pendientes nunca se borran.' }) + '<div><button type="button" class="cc-btn cc-btn--secundario" id="hRetOk">' + I('check') + 'Guardar</button></div></div>' +
		'<form class="cc-stack" style="gap:12px" id="hBorF" novalidate><h3 class="cc-h3" style="margin:0">Borrar todo el historial</h3><p class="cc-small cc-muted" style="margin:0">Elimina para siempre los ' + num(D.lista.length) + ' registros cerrados. Para confirmar escribe tu contraseña de inicio de sesión.</p>' +
		CC.campo('Tu contraseña', '<div class="pass"><input class="cc-input" type="password" id="hPw" autocomplete="current-password"><button type="button" data-ver="hPw" aria-label="Mostrar contraseña" aria-pressed="false">' + I('ver') + '</button></div>', { req: 1 }) +
		'<div><button type="submit" class="cc-btn cc-btn--secundario" id="hBorOk" style="color:var(--peligro)">' + I('baja') + 'Borrar todo el historial</button></div></form></div></div></section></div>';
	CostaClara.hidratar(raiz);
	$('#hPer').onchange = function () { HF.per = this.value; PAG.n = 1; CC.guarda('hist-toallas', HF); render(); };
	$('#hEst').onchange = function () { HF.est = this.value; PAG.n = 1; CC.guarda('hist-toallas', HF); render(); };
	var tq; $('#hHab').oninput = function () { var v = this.value.replace(/[^A-Za-z0-9-]/g, ''); this.value = v; clearTimeout(tq); tq = setTimeout(function () { HF.hab = v; PAG.n = 1; render(); var i = $('#hHab'); i.focus(); i.setSelectionRange(v.length, v.length); }, 250); };
	$$('[data-pg]').forEach(function (b) { b.onclick = function () { PAG.n = +b.getAttribute('data-pg'); render(); }; });
	$$('[data-det]').forEach(function (b) { b.onclick = function () { detalle(D.lista.filter(function (x) { return x.id === +b.getAttribute('data-det'); })[0]); }; });
	$$('[data-ver]').forEach(function (b) { b.onclick = function () { var i = $('#' + b.getAttribute('data-ver')), v = i.type === 'password'; i.type = v ? 'text' : 'password'; b.setAttribute('aria-pressed', v); b.innerHTML = I(v ? 'ocultar' : 'ver'); }; });
	$('#hRetOk').onclick = function () {
		var b = this, v = $('#hRet').value; CC.ocupado(b, true);
		CC.api(URL_T, { accion: 'retencion', opcion: v }).done(function (j) { CC.ocupado(b, false); if (j && j.ok) { D.retencion = v; CC.toast('exito', 'Conservación actualizada', 'Se conservan ' + esc((RET.filter(function (r) { return r[0] === v; })[0] || [])[1]) + '.'); render(); } else CC.toast('error', 'No se pudo guardar', esc((j && j.mensaje) || '')); }).fail(function () { CC.ocupado(b, false); });
	};
	$('#hBorF').onsubmit = function (e) {
		e.preventDefault();
		var pw = $('#hPw'); if (!pw.value) { CC.errEn(pw, 'Escribe tu contraseña.'); pw.focus(); return; }
		if (!D.lista.length) { CC.toast('info', 'El historial ya está vacío', ''); return; }
		CC.confirmar({
			titulo: '¿Borrar los ' + num(D.lista.length) + ' registros?', escribir: 'BORRAR', boton: 'Borrar todo',
			texto: 'Se eliminan para siempre. Las toallas pendientes de devolución no se tocan. Si los necesitas, pide antes un respaldo a Sistemas.',
			ok: function (md, terminar, reintentar) {
				CC.api(URL_T, { accion: 'borrar_todo', password: pw.value }).done(function (j) {
					terminar(); pw.value = '';
					if (j && j.ok) { cargar(); CC.toast('exito', 'Historial borrado', esc(j.mensaje || '')); } else CC.toast('error', 'No se borró nada', esc((j && j.mensaje) || ''));
				}).fail(reintentar);
			}
		});
	};
}
function detalle(x) {
	var e = EST_T[x.est];
	CC.modal('<div class="mod-head"><h2>Hab. ' + esc(x.hab) + ' · ' + CC.fcorta(x.fecha) + '</h2><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono" data-cancel aria-label="Cerrar">' + I('cerrar') + '</button></div>' +
		'<div class="mform"><dl class="resumen-b"><div><dt>Huésped</dt><dd>' + esc(x.hues) + '</dd></div><div><dt>Estatus</dt><dd><span class="est ' + e[0] + '">' + e[1] + '</span></dd></div><div><dt>' + (x.est === 'falta' ? 'Sin regresar' : 'Toallas') + '</dt><dd class="num">' + x.cant + '</dd></div><div><dt>Cerrado</dt><dd class="num">' + CC.fcorta(x.fecha) + ', ' + esc(x.fecha.slice(11, 16)) + '</dd></div><div><dt>Cerró</dt><dd>' + esc(x.u) + '</dd></div><div><dt>Observaciones</dt><dd>' + (x.obs ? esc(x.obs) : '—') + '</dd></div></dl></div>' +
		'<div class="mod-pie">' + '<button type="button" class="cc-btn cc-btn--fantasma" id="mDel" style="color:var(--peligro)">' + I('baja') + 'Eliminar registro</button><div class="cc-row" style="gap:8px">' + (x.est === 'falta' ? '<button type="button" class="cc-btn cc-btn--secundario" id="mReact">' + I('reloj') + 'Volver a pendientes</button>' : '') + '<button type="button" class="cc-btn cc-btn--secundario" data-cancel>Cerrar</button></div></div>', { ancho: 560 });
	var r = $('#mReact');
	if (r) r.onclick = function () {
		CC.ocupado(r, true);
		CC.api(URL_T, { accion: 'reactivar', id: x.id }).done(function (j) { if (j && j.ok) { CC.cierraModal(); cargar(); CC.toast('exito', 'Hab. ' + esc(x.hab) + ' volvió a pendientes', 'Aparece otra vez en Control de toallas.'); } else { CC.ocupado(r, false); CC.toast('error', 'No se pudo reactivar', esc((j && j.mensaje) || '')); } }).fail(function () { CC.ocupado(r, false); });
	};
	$('#mDel').onclick = function () {
		CC.confirmar({
			titulo: '¿Eliminar este registro?', boton: 'Eliminar', texto: 'Hab. ' + esc(x.hab) + ' · ' + esc(x.hues) + '. Se borra para siempre del historial.',
			ok: function (md, terminar, reintentar) {
				CC.api(URL_T, { accion: 'eliminar', id: x.id }).done(function (j) { if (j && j.ok) { terminar(); cargar(); CC.toast('exito', 'Registro eliminado', ''); } else { reintentar(); CC.toast('error', 'No se pudo eliminar', esc((j && j.mensaje) || '')); } }).fail(reintentar);
			}
		});
	};
}
cargar();
})();
