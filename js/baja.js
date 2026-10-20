/* Baja individual: asistente de 4 pasos + historial (procesos/bajas/bajas.php) */
(function () {
'use strict';
var $ = CC.$, $$ = CC.$$, I = CC.I, esc = CC.esc, num = CC.num;
var raiz = $('#vistaBaja'), URL_B = '../procesos/bajas/bajas.php';
var PAS = [['Equipo', 'Buscar por código'], ['Motivo y destino', 'Valor residual'], ['Evidencias', 'Fotos del equipo'], ['Autorizaciones', 'Revisar y confirmar']];
var B = nuevo(), D = { lista: [], motivos: [], destinos: [], tecnicos: [], gerentes: [], superAdmin: false }, EQ = [], H = { q: '', pag: 1 };

function nuevo() { return { paso: 1, max: 1, eq: null, motivo: '', valor: '', destino: '', fadq: '', obs: '', fEq: null, fEt: null, tec: '', vobo: '' }; }
function folio(h) { return 'BI-' + String(h.fecha || '').slice(0, 4) + '-' + ('000' + h.n).slice(-4); }

function cargar() {
	return jQuery.when(CC.api(URL_B, { accion: 'listar' }, 'GET'), CC.equipos(true)).done(function (a, eqs) {
		var j = a[0] || a; if (j && j.ok) D = j; EQ = eqs || [];
		var cod = new URLSearchParams(location.search).get('codigo');
		if (cod && !B.eq) { var e = EQ.filter(function (x) { return x.codigo === cod && x.estado !== 'baja'; })[0]; if (e) elegir(e); history.replaceState(null, '', location.pathname); }
		render();
	});
}
function elegir(e) { B.eq = e; B.valor = ''; B.fadq = e.fecha || ''; }

function render() {
	var mon = B.eq ? B.eq.mon : 'MXN';
	raiz.innerHTML = '<div class="vista"><div class="enc"><div><h1 class="cc-h1">Registrar baja de equipo</h1><p class="sub">Cuatro pasos. Nada cambia en el inventario hasta que confirmes la baja.</p></div></div>' +
		'<ol class="pasos-w" aria-label="Pasos">' + PAS.map(function (p, i) { var n = i + 1, on = B.paso === n, ok = n < B.paso || n < B.max; return '<li class="' + (on ? 'on' : ok ? 'ok' : '') + '"' + (on ? ' aria-current="step"' : '') + '><button type="button" data-paso="' + n + '"' + (n > B.max ? ' disabled' : '') + '><span class="num">' + (ok && !on ? I('check') : n) + '</span><span class="txt" style="min-width:0"><b>' + p[0] + '</b><small>' + p[1] + '</small></span></button></li>'; }).join('') + '</ol>' +
		'<section class="card"><div class="card__body" id="pasoBody"></div><div class="card__pie wiz-pie"><button type="button" class="cc-btn cc-btn--fantasma" id="bAtras"' + (B.paso === 1 ? ' hidden' : '') + '>' + I('izquierda') + 'Atrás</button><span style="flex:1"></span>' +
		(B.paso < 4 ? '<button type="button" class="cc-btn cc-btn--primario" id="bSig">Siguiente' + I('derecha') + '</button>' : '<button type="button" class="cc-btn cc-btn--peligro" id="bOk">' + I('baja') + 'Confirmar baja</button>') + '</div></section>' +
		historial() + '</div>';
	var body = $('#pasoBody');
	if (B.paso === 1) {
		body.innerHTML = '<div><h2 class="cc-h3" style="margin:0 0 4px">¿Qué equipo se da de baja?</h2><p class="cc-muted" style="margin:0 0 12px">Escanea la etiqueta o escribe el código, la serie o la descripción.</p></div><div id="bScan"' + (B.eq ? ' hidden' : '') + '></div><div id="bEq">' +
			(B.eq ? CC.eqCard(B.eq, '<div><dt>Departamento</dt><dd>' + esc(B.eq.depto || '—') + '</dd></div><div><dt>Fecha de ingreso</dt><dd>' + CC.fcorta(B.eq.fecha) + '</dd></div><div><dt>Valor de compra</dt><dd class="num">' + (B.eq.valor ? CC.dinero(B.eq.valor, B.eq.mon) : '—') + '</dd></div>') : '<div class="eqcard eqcard--vacio">' + I('buscar') + '<span>Aparecerán la foto, descripción, marca/modelo, serie, departamento y fecha de ingreso.</span></div>') + '</div>';
		if (!B.eq) CC.escaner($('#bScan'), { lista: EQ, filtro: function (e) { return e.estado !== 'baja'; }, vacio: 'Los equipos ya dados de baja no aparecen', pick: function (e) { elegir(e); render(); } }).input.focus();
		var q = $('[data-quitar-eq]', body); if (q) q.onclick = function () { B.eq = null; B.max = 1; render(); };
	}
	if (B.paso === 2) {
		body.innerHTML = '<div class="cc-field"><span class="cc-label" id="lMot">Motivo<span class="cc-req">*</span></span><div class="opciones" role="radiogroup" aria-labelledby="lMot" id="bMot">' + D.motivos.map(function (m) { return '<button type="button" class="opcion" role="radio" aria-checked="' + (B.motivo === m) + '" data-v="' + esc(m) + '"><span class="o"></span>' + esc(m) + '</button>'; }).join('') + '</div><span class="cc-error" hidden></span></div>' +
			'<div class="g2">' + CC.campo('Valor residual (' + mon + ')', '<div class="monto"><span class="pre">$</span><input class="cc-input" id="bVal" inputmode="decimal" placeholder="0.00" value="' + esc(B.valor) + '"></div>', { req: 1, ayuda: (B.eq.valor ? 'Valor de compra: ' + CC.dinero(B.eq.valor, mon) + '. ' : '') + 'Escribe 0 si no tiene valor.' }) +
			CC.campo('Destino', '<select class="cc-select" id="bDes">' + CC.opciones(D.destinos, B.destino, 'Selecciona el destino') + '</select>', { req: 1 }) + '</div>' +
			CC.campo('Fecha de adquisición', '<input class="cc-input" type="date" id="bFadq" value="' + esc(B.fadq) + '" max="' + CC.iso(new Date()) + '">', { opc: 1, ayuda: 'Sale en el informe técnico. Déjala vacía si no se conoce.' }) +
			CC.campo('Observaciones', '<textarea class="cc-input" id="bObs" maxlength="500" placeholder="Ej. Tarjeta madre dañada por humedad; diagnóstico de Sistemas del 15 oct.">' + esc(B.obs) + '</textarea>', { opc: 1 });
		$$('#bMot .opcion').forEach(function (b) {
			b.onclick = function () {
				B.motivo = b.getAttribute('data-v'); $$('#bMot .opcion').forEach(function (x) { x.setAttribute('aria-checked', String(x === b)); });
				$('#bMot').classList.remove('cc-input--error'); $('#bMot').nextElementSibling.hidden = true;
				if ((B.motivo === 'Venta' || B.motivo === 'Donación') && !B.destino) { B.destino = B.motivo; $('#bDes').value = B.motivo; }
			};
		});
		$('#bVal').oninput = function () { B.valor = this.value; CC.errEn(this, ''); };
		$('#bVal').onblur = function () { var v = CC.parseMonto(this.value); if (v != null && !isNaN(v) && v >= 0) this.value = v.toLocaleString('es-MX', { minimumFractionDigits: 2 }); B.valor = this.value; };
		$('#bDes').onchange = function () { B.destino = this.value; CC.errEn(this, ''); };
		$('#bFadq').onchange = function () { B.fadq = this.value; };
		$('#bObs').oninput = function () { B.obs = this.value; };
	}
	if (B.paso === 3) {
		body.innerHTML = '<div><h2 class="cc-h3" style="margin:0 0 4px">Evidencias</h2><p class="cc-muted" style="margin:0">Una foto del equipo completo y otra de la etiqueta con código y serie legibles.</p></div><div class="evid"><div class="cc-field"><span class="cc-label">Foto del equipo<span class="cc-req">*</span></span><div id="zEq"></div><span class="cc-error" hidden></span></div><div class="cc-field"><span class="cc-label">Foto de la etiqueta<span class="cc-req">*</span></span><div id="zEt"></div><span class="cc-error" hidden></span></div></div>';
		CC.zonaFoto($('#zEq'), { titulo: 'Foto del equipo', archivo: B.fEq, cambio: function (f) { B.fEq = f; $('#zEq').nextElementSibling.hidden = true; } });
		CC.zonaFoto($('#zEt'), { titulo: 'Foto de la etiqueta', icono: 'pdf', archivo: B.fEt, cambio: function (f) { B.fEt = f; $('#zEt').nextElementSibling.hidden = true; } });
	}
	if (B.paso === 4) {
		var e = B.eq, mini = B.fEq ? '<img src="' + URL.createObjectURL(B.fEq) + '" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:10px;flex:none">' : CC.thumb(e, 72);
		body.innerHTML = '<datalist id="dlTec">' + D.tecnicos.map(function (n) { return '<option value="' + esc(n) + '">'; }).join('') + '</datalist><datalist id="dlGer">' + D.gerentes.map(function (n) { return '<option value="' + esc(n) + '">'; }).join('') + '</datalist>' +
			'<div class="g2">' + CC.campo('Técnico que revisó', '<input class="cc-input" id="bTec" list="dlTec" maxlength="100" autocomplete="off" placeholder="Nombre del técnico" value="' + esc(B.tec) + '">', { req: 1 }) +
			CC.campo('Vo. Bo. Gerente de área', '<input class="cc-input" id="bVobo" list="dlGer" maxlength="100" autocomplete="off" placeholder="Nombre del gerente" value="' + esc(B.vobo) + '">', { req: 1 }) + '</div>' +
			'<div style="border:1px solid var(--borde);border-radius:var(--radio-md);padding:16px"><h3 class="cc-h3" style="margin:0 0 12px">Resumen</h3><div class="cc-row" style="gap:12px;align-items:flex-start;flex-wrap:nowrap">' + mini +
			'<dl class="resumen-b" style="flex:1"><div><dt>Equipo</dt><dd class="num">' + esc(e.codigo) + '</dd></div><div><dt>Descripción</dt><dd>' + esc(e.desc + ' · ' + e.marca) + '</dd></div><div><dt>Serie</dt><dd class="num">' + esc(e.serie || '—') + '</dd></div><div><dt>Departamento</dt><dd>' + esc(e.depto || '—') + '</dd></div>' +
			'<div><dt>Motivo</dt><dd>' + esc(B.motivo) + '</dd></div><div><dt>Destino</dt><dd>' + esc(B.destino) + '</dd></div><div><dt>Valor residual</dt><dd class="num">' + CC.dinero(CC.parseMonto(B.valor) || 0, e.mon) + '</dd></div><div><dt>Evidencias</dt><dd>2 fotos</dd></div></dl></div></div>' +
			'<div class="cc-aviso cc-aviso--alerta">' + I('alerta') + '<div><b>Al confirmar, ' + esc(e.codigo) + ' pasa a “Dado de baja”.</b>Queda registrado en el historial y podrás descargar el informe técnico para firmas.</div></div>';
		$('#bTec').oninput = function () { B.tec = this.value; CC.errEn(this, ''); };
		$('#bVobo').oninput = function () { B.vobo = this.value; CC.errEn(this, ''); };
	}
	CostaClara.hidratar(raiz);
	enlazar();
}

function historial() {
	var q = CC.sinAc(H.q.trim()), vis = D.lista.filter(function (h) { return !q || CC.sinAc([folio(h), h.codigo, h.desc, h.marca, h.serie, h.depto, h.motivo, h.destino].join(' ')).indexOf(q) >= 0; });
	var tam = 15, pags = Math.max(1, Math.ceil(vis.length / tam)); if (H.pag > pags) H.pag = pags;
	var ini = (H.pag - 1) * tam, pag = vis.slice(ini, ini + tam);
	return '<section class="card"><div class="card__head" style="flex-wrap:wrap;gap:12px"><div style="flex:1;min-width:200px"><h2>Historial de bajas individuales</h2><p>' + num(D.lista.length) + ' bajas registradas</p></div>' +
		'<label class="cc-buscador" style="max-width:300px;flex:1 1 220px"><i data-i="buscar"></i><input class="cc-input" id="hQ" type="search" placeholder="Folio, código, serie, motivo" value="' + esc(H.q) + '" aria-label="Buscar en el historial"></label></div>' +
		'<div class="tabla-scroll"><table class="cc-t"><thead><tr><th>Nº</th><th>Fecha</th><th>Código</th><th>Descripción</th><th>Marca / Modelo</th><th>Serie</th><th>Departamento</th><th>Motivo</th><th class="der">Valor residual</th><th>Destino</th><th>Fotos</th><th>Informe</th>' + (D.superAdmin ? '<th>Acciones</th>' : '') + '</tr></thead><tbody>' +
		(pag.length ? pag.map(function (h) {
			return '<tr style="cursor:default"><td><b class="num">' + folio(h) + '</b></td><td class="num">' + CC.fcorta(h.fecha) + '</td><td class="num">' + esc(h.codigo) + '</td><td>' + esc(h.desc) + '</td><td>' + esc(h.marca) + '</td><td class="num mut">' + esc(h.serie || '—') + '</td><td>' + esc(h.depto || '—') + '</td><td>' + esc(h.motivo) + '</td><td class="der num">' + CC.dinero(h.valor, h.mon) + '</td><td>' + esc(h.destino) + '</td>' +
				'<td><span class="mini-fotos">' + foto(h.fotoEq, 'Foto del equipo ' + h.codigo, 'equipos') + foto(h.fotoEt, 'Foto de la etiqueta ' + h.codigo, 'imagen') + '</span></td>' +
				'<td><button type="button" class="cc-btn cc-btn--secundario cc-btn--sm" data-inf="' + h.id + '">' + I('pdf') + 'PDF</button></td>' +
				(D.superAdmin ? '<td><button type="button" class="acc-btn" data-del="' + h.id + '" aria-label="Eliminar registro ' + folio(h) + '" style="color:var(--peligro)">' + I('baja') + '</button></td>' : '') + '</tr>';
		}).join('') : '<tr><td colspan="13" class="mut" style="text-align:center;padding:24px">' + (D.lista.length ? 'Ninguna baja coincide con la búsqueda.' : 'Aún no hay bajas individuales.') + '</td></tr>') + '</tbody></table></div>' +
		(pags > 1 ? '<div class="cc-pagina"><span>Mostrando ' + num(ini + 1) + '–' + num(Math.min(ini + tam, vis.length)) + ' de ' + num(vis.length) + '</span><div class="pags"><button type="button" data-pg="' + (H.pag - 1) + '" aria-label="Página anterior"' + (H.pag <= 1 ? ' disabled' : '') + '>' + I('izquierda') + '</button><span class="cc-small" style="padding:0 8px">' + H.pag + ' / ' + pags + '</span><button type="button" data-pg="' + (H.pag + 1) + '" aria-label="Página siguiente"' + (H.pag >= pags ? ' disabled' : '') + '>' + I('derecha') + '</button></div></div>' : '') + '</section>';
}
function foto(url, alt, ic) { return url ? '<a class="tn-neu" href="' + esc(url) + '" target="_blank" rel="noopener" title="' + esc(alt) + '"><img src="' + esc(url) + '" alt="' + esc(alt) + '" loading="lazy"></a>' : '<span class="tn-neu" title="Sin foto">' + I(ic) + '</span>'; }

function errorEn(sel, msg) { var c = $(sel); if (!c) return; var er = c.nextElementSibling; c.classList.add('cc-input--error'); er.hidden = false; er.innerHTML = I('error') + '<span>' + esc(msg) + '</span>'; }
function valida(p) {
	if (p === 1) { if (!B.eq) { var s = $('#bScan input'); s.classList.add('cc-input--error'); var er = $('#bScan .cc-error'); er.hidden = false; er.innerHTML = I('error') + '<span>Busca el equipo que se dará de baja.</span>'; s.focus(); return false; } return true; }
	if (p === 2) {
		var ok = true;
		if (!B.motivo) { errorEn('#bMot', 'Elige el motivo de la baja.'); ok = false; }
		var v = CC.parseMonto(B.valor);
		if (v == null) ok = CC.errEn($('#bVal'), 'Escribe el valor residual; 0 si no tiene.') && ok;
		else if (isNaN(v) || v < 0) ok = CC.errEn($('#bVal'), 'Escribe solo números, por ejemplo 850.00.') && ok;
		else if (B.eq.valor > 0 && v > B.eq.valor) ok = CC.errEn($('#bVal'), 'No puede ser mayor al valor de compra (' + CC.dinero(B.eq.valor, B.eq.mon) + ').') && ok;
		if (!B.destino) ok = CC.errEn($('#bDes'), 'Elige qué pasará con el equipo.') && ok;
		return ok;
	}
	if (p === 3) {
		var o3 = true;
		if (!B.fEq) { var a = $('#zEq').nextElementSibling; a.hidden = false; a.innerHTML = I('error') + '<span>Agrega la foto del equipo.</span>'; o3 = false; }
		if (!B.fEt) { var b = $('#zEt').nextElementSibling; b.hidden = false; b.innerHTML = I('error') + '<span>Agrega la foto de la etiqueta.</span>'; o3 = false; }
		return o3;
	}
	if (p === 4) {
		var o4 = true;
		if (!B.tec.trim()) o4 = CC.errEn($('#bTec'), 'Escribe quién revisó el equipo.') && o4;
		if (!B.vobo.trim()) o4 = CC.errEn($('#bVobo'), 'Escribe quién autoriza.') && o4;
		return o4;
	}
	return true;
}
function ir(n) { B.paso = n; B.max = Math.max(B.max, n); render(); $('.pasos-w').scrollIntoView({ block: 'nearest' }); }

function enlazar() {
	$$('[data-paso]').forEach(function (b) { b.onclick = function () { var n = +b.getAttribute('data-paso'); if (n > B.paso) { for (var p = B.paso; p < n; p++) if (!valida(p)) return; } ir(n); }; });
	var at = $('#bAtras'); if (at) at.onclick = function () { ir(B.paso - 1); };
	var sg = $('#bSig'); if (sg) sg.onclick = function () { if (valida(B.paso)) ir(B.paso + 1); };
	var ok = $('#bOk'); if (ok) ok.onclick = confirmar;
	var tq; $('#hQ').oninput = function () { var v = this.value; clearTimeout(tq); tq = setTimeout(function () { H.q = v; H.pag = 1; var s = $('.vista > section:last-child'); s.outerHTML = historial(); CostaClara.hidratar(raiz); enlazar(); var i = $('#hQ'); i.focus(); i.setSelectionRange(v.length, v.length); }, 200); };
	$$('[data-pg]').forEach(function (b) { b.onclick = function () { H.pag = +b.getAttribute('data-pg'); var s = $('.vista > section:last-child'); s.outerHTML = historial(); CostaClara.hidratar(raiz); enlazar(); }; });
	$$('[data-inf]').forEach(function (b) { b.onclick = function () { var h = porId(b.getAttribute('data-inf')); informe(h.id, folio(h), h.idHotel); }; });
	$$('[data-del]').forEach(function (b) { b.onclick = function () { eliminar(porId(b.getAttribute('data-del'))); }; });
}
function porId(id) { id = +id; return D.lista.filter(function (h) { return h.id === id; })[0]; }
function informe(id, fol, hotel) { CC.verPdf(fol + ' · Informe técnico de baja', 'reportes/informeTecnicoBaja.php?id_baja=' + id + (hotel ? '&id_hotel=' + hotel : '')); }

function confirmar() {
	if (!valida(4)) return;
	var e = B.eq;
	CC.confirmar({
		titulo: '¿Confirmar la baja de ' + esc(e.codigo) + '?', boton: 'Confirmar baja', icono: 'baja',
		texto: esc(e.desc + ' · ' + e.marca) + '. Motivo: ' + esc(B.motivo) + '. Destino: ' + esc(B.destino) + '. El equipo quedará como Dado de baja.',
		ok: function (md, terminar, reintentar) {
			var fd = new FormData();
			fd.append('accion', 'registrar'); fd.append('id_equipo', e.id); fd.append('motivo', B.motivo); fd.append('destino', B.destino);
			fd.append('valor', String(CC.parseMonto(B.valor))); fd.append('fecha_adquisicion', B.fadq || ''); fd.append('observaciones', B.obs.trim());
			fd.append('tecnico', B.tec.trim()); fd.append('gerente', B.vobo.trim()); fd.append('foto_equipo', B.fEq); fd.append('foto_etiqueta', B.fEt);
			jQuery.ajax({ url: URL_B, type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' }).done(function (j) {
				if (j && j.ok) {
					terminar(); B = nuevo(); cargar();
					var fol = 'BI-' + new Date().getFullYear() + '-' + ('000' + j.n).slice(-4);
					CC.toast('exito', 'Baja ' + fol + ' registrada', esc(j.codigo) + ' quedó como Dado de baja. <button type="button" class="cc-btn cc-btn--enlace cc-btn--sm" data-ver-inf="' + j.id + '" style="padding:0;height:22px">Ver informe técnico</button>');
					var t = $('[data-ver-inf="' + j.id + '"]'); if (t) t.onclick = function () { informe(j.id, fol, e.idHotel); };
					return;
				}
				reintentar();
				if (j && j.errores) { terminar(); var k = Object.keys(j.errores); CC.toast('error', 'Revisa la baja', esc(k.map(function (x) { return j.errores[x]; }).join(' '))); }
				else CC.toast('error', 'No se pudo registrar la baja', esc((j && j.mensaje) || ''));
			}).fail(function (x) { reintentar(); if (x.status !== 0) CC.toast('error', 'No se pudo registrar la baja', 'Si tu sesión expiró, vuelve a iniciarla; los datos del formulario se conservan en esta pestaña.'); });
		}
	});
}
function eliminar(h) {
	CC.confirmar({
		titulo: '¿Eliminar el registro ' + folio(h) + '?', boton: 'Eliminar registro', escribir: 'ELIMINAR',
		texto: 'Úsalo solo si la baja se capturó por error: ' + esc(h.codigo) + ' volverá a estar Activo. Las fotos se conservan en el servidor.',
		ok: function (md, terminar, reintentar) {
			CC.api(URL_B, { accion: 'eliminar', id: h.id }).done(function (j) {
				if (j && j.ok) { terminar(); cargar(); CC.toast('exito', 'Registro ' + folio(h) + ' eliminado', esc(h.codigo) + ' volvió a Activo.'); }
				else { reintentar(); CC.toast('error', 'No se pudo eliminar', esc((j && j.mensaje) || '')); }
			}).fail(reintentar);
		}
	});
}
cargar();
})();
