/* Catálogos (Departamentos, Áreas, Fases) con datos de procesos/catalogos/catalogo.php */
(function () {
'use strict';
var $ = CC.$, $$ = CC.$$, I = CC.I, esc = CC.esc, num = CC.num, sinAc = CC.sinAc;
var raiz = $('#vistaCatalogo'), tipo = raiz.getAttribute('data-tipo');
var CATS = {
	departamentos: { plural: 'Departamentos', sing: 'departamento', art: 'un', nuevo: 'Nuevo departamento', ico: 'departamentos', ej: 'Ej. Experiencia del huésped', uso: 'equipos', sub: 'Catálogo que se usa al registrar equipos y resguardos. Cada hotel tiene el suyo.' },
	areas: { plural: 'Áreas', sing: 'área', art: 'un', nuevo: 'Nueva área', ico: 'areas', ej: 'Ej. Spa · Cabina 4', uso: 'equipos en solicitudes', sub: 'Catálogo que se usa en las solicitudes de alta de equipos. Cada hotel tiene el suyo.' },
	fases: { plural: 'Fases', sing: 'fase', art: 'una', nuevo: 'Nueva fase', ico: 'fases', ej: 'Ej. Fase 4', uso: 'equipos en solicitudes', sub: 'Catálogo que se usa en las solicitudes de alta de equipos. Cada hotel tiene el suyo.' }
};
var c = CATS[tipo];
var URL = '../procesos/catalogos/catalogo.php';
var S = { vista: CC.lee('cat-vista', 'tabla'), q: '', idHotel: 0 };
var D = { lista: [], hotel: '', hoteles: [], superAdmin: false };

function cargar() {
	return CC.api(URL, { accion: 'listar', tipo: tipo, id_hotel: S.idHotel }, 'GET').done(function (j) {
		if (!j || !j.ok) return;
		D = j; S.idHotel = j.idHotel; render();
	});
}
function render() {
	var q = sinAc(S.q.trim()), lista = D.lista;
	var vis = lista.filter(function (x) { return !q || sinAc(x.nombre + ' ' + x.desc).indexOf(q) >= 0; });
	var usoTxt = c.uso === 'equipos' ? 'Equipos asignados' : 'Equipos en solicitudes';
	var cuerpo;
	if (!lista.length) {
		cuerpo = '<section class="card vacio"><b style="font-size:15px">Todavía no hay ' + c.plural.toLowerCase() + ' en ' + esc(D.hotel) + '</b><span class="cc-muted">Crea ' + c.art + ' ' + c.sing + ' para empezar.</span></section>';
	} else if (!vis.length) {
		cuerpo = '<section class="card vacio"><b style="font-size:15px">No hay ' + c.plural.toLowerCase() + ' que coincidan con “' + esc(S.q) + '”</b><button type="button" class="cc-btn cc-btn--secundario" id="catLimpiar">Limpiar búsqueda</button></section>';
	} else if (S.vista === 'tabla') {
		cuerpo = '<section class="card"><div class="tabla-scroll"><table class="cc-t"><thead><tr><th>Nombre</th><th>Descripción</th><th class="der">' + usoTxt + '</th><th>Fecha de creación</th><th class="c-acc" style="width:96px">Acciones</th></tr></thead><tbody>' +
			vis.map(function (x) {
				return '<tr style="cursor:default"><td><b style="font-weight:500">' + esc(x.nombre) + '</b></td><td class="mut" style="max-width:420px;overflow:hidden;text-overflow:ellipsis" title="' + esc(x.desc) + '">' + (x.desc ? esc(x.desc) : '<i>Sin descripción</i>') + '</td>' +
					'<td class="der"><span class="neq' + (x.n ? '' : ' cero') + '">' + num(x.n) + '</span></td><td class="num">' + CC.fcorta(x.fecha) + '</td>' +
					'<td><span class="accs"><button type="button" class="acc-btn" data-ed="' + x.id + '" aria-label="Editar ' + esc(x.nombre) + '">' + I('editar') + '</button><button type="button" class="acc-btn" data-el="' + x.id + '" aria-label="Eliminar ' + esc(x.nombre) + '" style="color:var(--peligro)">' + I('baja') + '</button></span></td></tr>';
			}).join('') + '</tbody></table></div></section>';
	} else {
		cuerpo = '<div class="cat-tarjetas">' + vis.map(function (x) {
			return '<article class="cat"><div class="cc-row" style="gap:10px;flex-wrap:nowrap"><span class="thumb tn-dor">' + I(c.ico) + '</span><h3>' + esc(x.nombre) + '</h3></div><p>' + (x.desc ? esc(x.desc) : '<i>Sin descripción</i>') + '</p>' +
				'<div class="cat__pie"><span class="neq' + (x.n ? '' : ' cero') + '">' + I('equipos') + num(x.n) + ' ' + c.uso + '</span><span class="cc-small cc-muted">Desde ' + CC.fcorta(x.fecha) + '</span></div>' +
				'<div class="cc-row" style="gap:6px"><button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" data-ed="' + x.id + '">' + I('editar') + 'Editar</button><button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-el="' + x.id + '" style="color:var(--peligro)">' + I('baja') + 'Eliminar</button></div></article>';
		}).join('') + '</div>';
	}
	raiz.innerHTML = '<div class="vista"><div class="enc"><div><div class="enc__tit"><h1 class="cc-h1">' + c.plural + '</h1><span class="contador"><b>' + num(lista.length) + '</b>en ' + esc(D.hotel) + '</span></div><p class="sub">' + c.sub + '</p></div>' +
		'<button type="button" class="cc-btn cc-btn--primario" id="catNuevo">' + I('mas') + c.nuevo + '</button></div>' +
		'<div class="filtros">' + (D.superAdmin ? '<select class="cc-select" id="catHotel" style="width:auto;height:34px" aria-label="Hotel">' + CC.opciones(D.hoteles.map(function (h) { return [h.id, h.nombre]; }), D.idHotel) + '</select>' : '') +
		'<label class="cc-buscador"><i data-i="buscar"></i><input class="cc-input" id="catQ" type="search" placeholder="Buscar ' + c.plural.toLowerCase() + '" value="' + esc(S.q) + '" aria-label="Buscar"></label><span style="flex:1"></span>' +
		'<div class="seg-tabs" role="tablist" aria-label="Vista"><button type="button" role="tab" data-cv="tabla" aria-selected="' + (S.vista === 'tabla') + '">' + I('columnas') + 'Tabla</button><button type="button" role="tab" data-cv="tarjetas" aria-selected="' + (S.vista === 'tarjetas') + '">' + I('equipos') + 'Tarjetas</button></div></div>' +
		cuerpo + '</div>';
	CostaClara.hidratar(raiz);
	var sh = $('#catHotel'); if (sh) sh.onchange = function () { S.idHotel = +this.value; cargar(); };
	var tq; $('#catQ').oninput = function () { var v = this.value; clearTimeout(tq); tq = setTimeout(function () { S.q = v; render(); var i = $('#catQ'); i.focus(); i.setSelectionRange(v.length, v.length); }, 200); };
	$$('[data-cv]').forEach(function (b) { b.onclick = function () { S.vista = b.getAttribute('data-cv'); CC.guarda('cat-vista', S.vista); render(); }; });
	var lim = $('#catLimpiar'); if (lim) lim.onclick = function () { S.q = ''; render(); };
	$('#catNuevo').onclick = function () { formulario(null); };
	$$('[data-ed]').forEach(function (b) { b.onclick = function () { formulario(porId(b.getAttribute('data-ed'))); }; });
	$$('[data-el]').forEach(function (b) { b.onclick = function () { eliminar(porId(b.getAttribute('data-el'))); }; });
}
function porId(id) { id = +id; return D.lista.filter(function (x) { return x.id === id; })[0]; }

function formulario(x) {
	var ed = !!x;
	CC.modal('<div class="mod-head"><h2>' + (ed ? 'Editar ' + c.sing : c.nuevo) + '</h2><button type="button" class="cc-btn cc-btn--fantasma cc-btn--icono" data-cancel aria-label="Cerrar">' + I('cerrar') + '</button></div>' +
		'<form class="mform" id="catF" novalidate>' + CC.campo('Nombre', '<input class="cc-input" id="cfN" maxlength="100" value="' + esc(x ? x.nombre : '') + '" placeholder="' + c.ej + '">', { req: 1 }) +
		CC.campo('Descripción', '<textarea class="cc-input" id="cfD" maxlength="200" placeholder="Para qué se usa, en una frase">' + esc(x ? x.desc : '') + '</textarea>', { opc: 1, ayuda: '<span id="cfC">' + (x ? x.desc.length : 0) + '</span>/200' }) +
		'<p class="cc-small cc-muted" style="margin:0">Hotel: ' + esc(D.hotel) + (ed ? ' · ' + num(x.n) + ' ' + c.uso : '') + '</p></form>' +
		'<div class="mod-pie" style="justify-content:flex-end"><button type="button" class="cc-btn cc-btn--secundario" data-cancel>Cancelar</button><button type="button" class="cc-btn cc-btn--primario" id="cfGuardar">' + I('check') + (ed ? 'Guardar cambios' : 'Crear ' + c.sing) + '</button></div>', { ancho: 480 });
	$('#cfD').oninput = function () { $('#cfC').textContent = this.value.length; };
	$('#cfN').oninput = function () { CC.errEn(this, ''); };
	$('#catF').onsubmit = function (e) { e.preventDefault(); $('#cfGuardar').click(); };
	$('#cfGuardar').onclick = function () {
		var n = $('#cfN').value.trim().replace(/\s+/g, ' '), d = $('#cfD').value.trim(), btn = this;
		if (!n) { CC.errEn($('#cfN'), 'Escribe el nombre.'); $('#cfN').focus(); return; }
		CC.ocupado(btn, true);
		CC.api(URL, { accion: 'guardar', tipo: tipo, id_hotel: D.idHotel, id: ed ? x.id : 0, nombre: n, descripcion: d }).done(function (j) {
			if (j && j.ok) { CC.cierraModal(); cargar(); CC.toast('exito', ed ? 'Cambios guardados en “' + esc(n) + '”' : (c.sing.charAt(0).toUpperCase() + c.sing.slice(1)) + ' “' + esc(n) + '” creado', ed ? '' : 'Ya aparece en los formularios de ' + esc(D.hotel) + '.'); }
			else { CC.ocupado(btn, false); if (j && j.campo === 'nombre') { CC.errEn($('#cfN'), j.mensaje); $('#cfN').focus(); } else CC.toast('error', 'No se pudo guardar', esc((j && j.mensaje) || '')); }
		}).fail(function () { CC.ocupado(btn, false); });
	};
}

function eliminar(x) {
	if (x.n) {
		var otros = D.lista.filter(function (y) { return y !== x; });
		if (!otros.length) { CC.toast('alerta', '“' + esc(x.nombre) + '” tiene ' + num(x.n) + ' ' + c.uso, 'Crea otro ' + c.sing + ' para moverlos antes de eliminarlo.'); return; }
		CC.confirmar({
			titulo: '“' + esc(x.nombre) + '” tiene ' + num(x.n) + ' ' + c.uso, icono: 'alerta', tono: 'cc-tono-alerta', boton: 'Mover y eliminar',
			texto: 'No se puede dejar equipos sin ' + c.sing + '. Elige a cuál moverlos y después se elimina “' + esc(x.nombre) + '”.',
			extra: CC.campo('Mover a', '<select class="cc-select" id="cfMov">' + CC.opciones(otros.map(function (y) { return [y.id, y.nombre]; }), '', 'Selecciona') + '</select>', { req: 1 }),
			validar: function () { var s = $('#cfMov'); return CC.errEn(s, s.value ? '' : 'Elige a dónde moverlos.'); },
			ok: function (md, terminar, reintentar) {
				var dst = porId($('#cfMov').value);
				CC.api(URL, { accion: 'eliminar', tipo: tipo, id_hotel: D.idHotel, id: x.id, mover_a: dst.id }).done(function (j) {
					if (j && j.ok) { terminar(); cargar(); CC.toast('exito', '“' + esc(x.nombre) + '” eliminado', num(j.movidos) + ' ' + c.uso + ' movidos a “' + esc(dst.nombre) + '”.'); }
					else { reintentar(); CC.toast('error', 'No se pudo eliminar', esc((j && j.mensaje) || '')); }
				}).fail(reintentar);
			}
		});
		return;
	}
	CC.confirmar({
		titulo: '¿Eliminar “' + esc(x.nombre) + '”?', boton: 'Eliminar',
		texto: 'No tiene ' + c.uso + '. Dejará de aparecer en los formularios de ' + esc(D.hotel) + '. No se puede deshacer.',
		ok: function (md, terminar, reintentar) {
			CC.api(URL, { accion: 'eliminar', tipo: tipo, id_hotel: D.idHotel, id: x.id }).done(function (j) {
				if (j && j.ok) { terminar(); cargar(); CC.toast('exito', '“' + esc(x.nombre) + '” eliminado', ''); }
				else { reintentar(); CC.toast('error', 'No se pudo eliminar', esc((j && j.mensaje) || '')); }
			}).fail(reintentar);
		}
	});
}

var hotelUrl = +new URLSearchParams(location.search).get('id_hotel');
if (hotelUrl) S.idHotel = hotelUrl;
cargar();
})();
