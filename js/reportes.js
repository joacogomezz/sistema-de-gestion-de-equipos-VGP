/* Centro de reportes: filtros por tarjeta y descarga en PDF o Excel (reportes/centroReportes.php) */
(function () {
'use strict';
var $ = CC.$, $$ = CC.$$, I = CC.I, esc = CC.esc;
var raiz = $('#vistaReportes'), CAT = JSON.parse(raiz.getAttribute('data-cat')), HOY = CC.iso(new Date()), INI = HOY.slice(0, 4) + '-01-01';
var REPS = [
	{ id: 'activos', t: 'Reporte de equipos activos', d: 'Inventario activo con ubicación, responsable y valor.', ic: 'equipos', tn: 'tn-exi', f: ['hotel', 'depto'] },
	{ id: 'depto', t: 'Reporte por departamento', d: 'Equipos en uso de un departamento con su estado y responsable.', ic: 'departamentos', tn: 'tn-mar', f: ['hotel', 'depto*'] },
	{ id: 'altas', t: 'Reporte de altas', d: 'Equipos que ingresaron en el periodo, con factura y costo.', ic: 'mas', tn: 'tn-exi', f: ['hotel', 'depto', 'fechas'] },
	{ id: 'bi', t: 'Reporte de bajas individuales', d: 'Bajas con motivo, destino, valor residual y autorizaciones.', ic: 'baja', tn: 'tn-dor', f: ['hotel', 'depto', 'fechas'] },
	{ id: 'bm', t: 'Reporte de bajas masivas', d: 'Folios de bajas masivas con su lista de equipos.', ic: 'bajas-masivas', tn: 'tn-dor', f: ['hotel', 'fechas'] },
	{ id: 'todas', t: 'Reporte de todas las bajas', d: 'Individuales y masivas en un solo documento.', ic: 'reportes', tn: 'tn-neu', f: ['hotel', 'depto', 'fechas'] },
	{ id: 'sel', t: 'Informe de equipos seleccionados', d: 'Excel con la ficha de los equipos que marques en la pantalla Equipos.', ic: 'resguardos', tn: 'tn-mar', f: [], sel: true }];
var RF = CC.lee('reportes-filtros', {}), CLAVE_H = 'reportes-recientes-' + CAT.usuario;

function f(id) { RF[id] = RF[id] || { hotel: CAT.superAdmin ? '' : String((CAT.hoteles[0] || {}).id || ''), depto: '', desde: INI, hasta: HOY }; return RF[id]; }
function deptos(h) { return CAT.deptos.filter(function (d) { return !h || d.idHotel === +h; }).map(function (d) { return [d.id, d.nombre + (h || !CAT.superAdmin ? '' : ' · ' + hotelNom(d.idHotel))]; }); }
function hotelNom(id) { var h = CAT.hoteles.filter(function (x) { return x.id === +id; })[0]; return h ? h.nombre : 'Todos los hoteles'; }

function render() {
	raiz.innerHTML = '<div class="vista"><div class="enc"><div><h1 class="cc-h1">Centro de reportes</h1><p class="sub">Elige filtros en cada tarjeta y genera el reporte en PDF para imprimir o en Excel para analizar.</p></div></div><div class="reps">' + REPS.map(tarjeta).join('') + '</div>' +
		'<section class="card"><div class="card__head"><h2>Generados recientemente</h2><span class="cc-small cc-muted">En este navegador</span></div><div class="card__body" style="padding-top:8px;padding-bottom:8px"><ul class="hist-rep" id="rHist"></ul></div></section></div>';
	CostaClara.hidratar(raiz);
	hist();
	$$('.rep').forEach(enlazar);
}
function tarjeta(r) {
	var x = f(r.id), req = r.f.indexOf('depto*') >= 0;
	return '<article class="rep" data-rep="' + r.id + '"><div class="rep__top"><span class="rep__ico ' + r.tn + '">' + I(r.ic) + '</span><div><h3>' + r.t + '</h3><p>' + r.d + '</p></div></div>' +
		(r.sel ? '<div class="cc-aviso cc-aviso--info">' + I('info') + '<div><b>Se genera desde Equipos</b>Marca los equipos en la tabla y usa “Exportar a Excel” en la barra de selección.</div></div><div class="rep__pie"><a class="cc-btn cc-btn--secundario" href="equipos.php">' + I('equipos') + 'Ir a Equipos</a></div></article>' :
		'<div class="rep__fil">' +
		(CAT.superAdmin ? CC.campo('Hotel', '<select class="cc-select" data-k="hotel">' + CC.opciones(CAT.hoteles.map(function (h) { return [h.id, h.nombre]; }), x.hotel, 'Todos los hoteles') + '</select>') : '') +
		(r.f.join().indexOf('depto') >= 0 ? CC.campo('Departamento', '<select class="cc-select" data-k="depto">' + CC.opciones(deptos(x.hotel), x.depto, req ? 'Selecciona' : 'Todos') + '</select>', { req: req }) : '') +
		(r.f.indexOf('fechas') >= 0 ? CC.campo('Desde', '<input type="date" class="cc-input" data-k="desde" value="' + esc(x.desde) + '" max="' + HOY + '">') + CC.campo('Hasta', '<input type="date" class="cc-input" data-k="hasta" value="' + esc(x.hasta) + '" max="' + HOY + '">') : '') + '</div>' +
		'<div class="rep__pie"><button type="button" class="cc-btn cc-btn--secundario" data-fmt="pdf"><span class="fmt fmt--pdf">PDF</span>Ver PDF</button><button type="button" class="cc-btn cc-btn--secundario" data-fmt="xls"><span class="fmt fmt--xls">XLS</span>Descargar Excel</button></div></article>');
}
function enlazar(card) {
	var r = REPS.filter(function (x) { return x.id === card.getAttribute('data-rep'); })[0], x = f(r.id);
	$$('[data-k]', card).forEach(function (i) {
		i.onchange = function () {
			var k = i.getAttribute('data-k'); x[k] = i.value; CC.errEn(i, ''); CC.guarda('reportes-filtros', RF);
			if (k === 'hotel') { x.depto = ''; var s = $('[data-k="depto"]', card); if (s) s.innerHTML = CC.opciones(deptos(x.hotel), '', r.f.indexOf('depto*') >= 0 ? 'Selecciona' : 'Todos'); }
		};
	});
	$$('[data-fmt]', card).forEach(function (b) {
		b.onclick = function () {
			if (r.f.indexOf('depto*') >= 0 && !x.depto) { CC.errEn($('[data-k="depto"]', card), 'Elige el departamento.'); $('[data-k="depto"]', card).focus(); return; }
			if (r.f.indexOf('fechas') >= 0 && x.desde && x.hasta && x.desde > x.hasta) { CC.errEn($('[data-k="desde"]', card), 'Debe ser anterior a “Hasta”.'); return; }
			var fmt = b.getAttribute('data-fmt'), p = ['tipo=' + r.id, 'fmt=' + fmt];
			if (x.hotel && CAT.superAdmin) p.push('hotel=' + encodeURIComponent(x.hotel));
			if (x.depto && r.f.join().indexOf('depto') >= 0) p.push('depto=' + encodeURIComponent(x.depto));
			if (r.f.indexOf('fechas') >= 0) { if (x.desde) p.push('desde=' + x.desde); if (x.hasta) p.push('hasta=' + x.hasta); }
			var url = '../reportes/centroReportes.php?' + p.join('&'), dep = x.depto ? CAT.deptos.filter(function (d) { return d.id === +x.depto; })[0] : null;
			var nom = r.t + (dep ? ' · ' + dep.nombre : '');
			if (fmt === 'pdf') CC.verPdf(nom, url);
			else { var a = document.createElement('a'); a.href = url; a.download = ''; document.body.appendChild(a); a.click(); a.remove(); CC.toast('exito', 'Descargando Excel', esc(nom)); }
			var hs = CC.lee(CLAVE_H, []); hs.unshift({ t: nom, fmt: fmt, h: CAT.superAdmin ? hotelNom(x.hotel) : hotelNom((CAT.hoteles[0] || {}).id), cuando: new Date().toISOString(), url: url });
			CC.guarda(CLAVE_H, hs.slice(0, 8)); hist();
		};
	});
}
function cuando(s) { var d = new Date(s), hoy = new Date(); var hh = CC.f2(d.getHours()) + ':' + CC.f2(d.getMinutes()); return d.toDateString() === hoy.toDateString() ? 'Hoy, ' + hh : CC.fcorta(CC.iso(d)) + ', ' + hh; }
function hist() {
	var hs = CC.lee(CLAVE_H, []);
	$('#rHist').innerHTML = hs.length ? hs.slice(0, 6).map(function (h, i) {
		return '<li><span class="fmt ' + (h.fmt === 'pdf' ? 'fmt--pdf' : 'fmt--xls') + '">' + (h.fmt === 'pdf' ? 'PDF' : 'XLS') + '</span><span style="min-width:0"><b style="font-weight:500">' + esc(h.t) + '</b><span class="cc-small cc-muted" style="display:block">' + esc(h.h) + '</span></span>' +
			'<span class="cc-small cc-muted">' + cuando(h.cuando) + '</span><button type="button" class="cc-btn cc-btn--fantasma cc-btn--sm" data-rep-h="' + i + '">' + I(h.fmt === 'pdf' ? 'ver' : 'descarga') + 'Otra vez</button></li>';
	}).join('') : '<li class="cc-muted" style="justify-content:center">Aún no generas reportes desde este navegador.</li>';
	CostaClara.hidratar($('#rHist'));
	$$('[data-rep-h]').forEach(function (b) { b.onclick = function () { var h = hs[+b.getAttribute('data-rep-h')]; if (h.fmt === 'pdf') CC.verPdf(h.t, h.url); else location.href = h.url; }; });
}
render();
})();
