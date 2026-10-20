/* @ds-bundle: {"format":4,"namespace":"CostaClara","components":[]} */
(function () {
  // Íconos de línea 24×24, trazo 1.75, esquinas redondeadas. Se insertan con <i data-i="nombre"></i>.
  var P = {
    inicio: '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h5v-6h4v6h5V9.5"/>',
    equipos: '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
    resguardos: '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="m9 13 2 2 4-4"/>',
    solicitudes: '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M12 11v6M9 14h6"/>',
    baja: '<path d="M3 7h18M8 7V4h8v3M6 7l1 13h10l1-13"/><path d="M10 11v6M14 11v6"/>',
    'bajas-masivas': '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/><path d="m3 17 9 5 9-5" opacity=".55"/>',
    departamentos: '<rect x="4" y="3" width="10" height="18" rx="1"/><path d="M14 9h5a1 1 0 0 1 1 1v11H14M7 7h4M7 11h4M7 15h4"/>',
    areas: '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    fases: '<circle cx="6" cy="6" r="2.5"/><circle cx="6" cy="18" r="2.5"/><circle cx="18" cy="12" r="2.5"/><path d="M6 8.5v7M8.3 7l7.4 3.8M8.3 17l7.4-3.8"/>',
    reportes: '<path d="M4 20V4M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
    usuarios: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
    respaldo: '<ellipse cx="12" cy="5.5" rx="7.5" ry="2.5"/><path d="M4.5 5.5v13c0 1.4 3.4 2.5 7.5 2.5s7.5-1.1 7.5-2.5v-13M4.5 12c0 1.4 3.4 2.5 7.5 2.5s7.5-1.1 7.5-2.5"/>',
    toallas: '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M5 15h14M5 18h14M9 3v6a3 3 0 0 0 6 0V3"/>',
    hotel: '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h3a1 1 0 0 1 1 1v11M2 21h20M8 7h4M8 11h4M8 15h4"/>',
    buscar: '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>',
    campana: '<path d="M6 16V11a6 6 0 1 1 12 0v5l1.5 2h-15z"/><path d="M10 20.5a2 2 0 0 0 4 0"/>',
    abajo: '<path d="m6 9 6 6 6-6"/>',
    derecha: '<path d="m9 6 6 6-6 6"/>',
    izquierda: '<path d="m15 6-6 6 6 6"/>',
    menu: '<path d="M4 7h16M4 12h16M4 17h16"/>',
    mas: '<path d="M12 5v14M5 12h14"/>',
    puntos: '<circle cx="5.5" cy="12" r="1.2" fill="currentColor"/><circle cx="12" cy="12" r="1.2" fill="currentColor"/><circle cx="18.5" cy="12" r="1.2" fill="currentColor"/>',
    filtro: '<path d="M4 5h16l-6 7.5V19l-4 1.5v-8z"/>',
    columnas: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16M15 4v16"/>',
    descarga: '<path d="M12 4v11M7 10.5l5 5 5-5M5 20h14"/>',
    subir: '<path d="M12 20V9M7 13.5l5-5 5 5M5 4h14"/>',
    pdf: '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
    excel: '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M4 9h16M4 15h16M10 9v12"/>',
    cerrar: '<path d="M6 6l12 12M18 6 6 18"/>',
    check: '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
    alerta: '<path d="M12 4 2.8 19.5h18.4z"/><path d="M12 10v4.5M12 17.2v.3"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.8v.3"/>',
    error: '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16v.3"/>',
    reloj: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    salir: '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 8l-4 4 4 4M6 12h10"/>',
    sol: '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/>',
    luna: '<path d="M20 14.5A8 8 0 1 1 9.5 4 6.5 6.5 0 0 0 20 14.5z"/>',
    colapsar: '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16M15.5 10l-2 2 2 2"/>',
    editar: '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
    ver: '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
    imagen: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="9.5" r="1.8"/><path d="m4 18 5.5-5.5 4 4 2.5-2.5 4.5 4.5"/>',
    impresora: '<path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 14h10v7H7z"/>',
    flecha: '<path d="M5 12h14M13 6l6 6-6 6"/>',
    usuario: '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0"/>',
    candado: '<rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3M12 14.5v2.5"/>',
    ocultar: '<path d="M3 3l18 18"/><path d="M10.6 5.6A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3 3.8M6.4 6.9C3.9 8.6 2.5 12 2.5 12S6 18.5 12 18.5a9 9 0 0 0 4.3-1.1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
    laptop: '<rect x="4" y="5" width="16" height="11" rx="1.5"/><path d="M2 19h20"/>',
    monitor: '<rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M12 16v3M8 20h8"/>',
    pos: '<rect x="5" y="3" width="14" height="10" rx="1.5"/><path d="M8 13l-2 8h12l-2-8M9 17h6"/>',
    tv: '<rect x="2.5" y="5" width="19" height="12" rx="1.5"/><path d="M8 21h8M12 17v4M9 2l3 3 3-3"/>',
    telefono: '<rect x="3" y="9" width="18" height="12" rx="2"/><path d="M7 9V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v4M8 14h2M14 14h2M8 17h2M14 17h2"/>',
    tablet: '<rect x="5" y="2.5" width="14" height="19" rx="2"/><path d="M11 18.5h2"/>',
    red: '<path d="M2.5 9a14 14 0 0 1 19 0M5.5 12.5a9.5 9.5 0 0 1 13 0M8.5 16a5 5 0 0 1 7 0"/><circle cx="12" cy="19.5" r="1" fill="currentColor"/>',
    proyector: '<rect x="2.5" y="8" width="19" height="9" rx="2"/><circle cx="16" cy="12.5" r="2.5"/><path d="M6 12.5h4M5 17v2M19 17v2"/>',
    refri: '<rect x="5" y="2.5" width="14" height="19" rx="2"/><path d="M5 9.5h14M8 5.5v2M8 12.5v3"/>',
    caja: '<rect x="3.5" y="4" width="17" height="15" rx="2"/><circle cx="12" cy="11.5" r="3.5"/><path d="M12 9.5v2l1.2 1M6 19v2M18 19v2"/>',
    escritorio: '<path d="M2.5 7h19M4 7v13M20 7v13M14 7v8h6"/><path d="M15.5 11h3"/>',
    silla: '<path d="M7 3h10v9H7zM5 12h14M12 12v5M7 21l5-4 5 4"/>',
    lavadora: '<rect x="4" y="2.5" width="16" height="19" rx="2"/><circle cx="12" cy="13" r="4.5"/><path d="M7 6h3M15.5 6h1"/>',
    firma: '<path d="M3 17c3-1 4-9 6-9s-1 9 1.5 9S14 12 15 12s0 4 2 4 3-1 4-2"/><path d="M3 21h18"/>'
  };
  function icono(n) {
    return '<span class="cc-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">' + (P[n] || P.info) + '</svg></span>';
  }
  function hidratar(raiz) {
    (raiz || document).querySelectorAll('i[data-i]').forEach(function (el) {
      var t = document.createElement('span');
      t.innerHTML = icono(el.getAttribute('data-i'));
      var s = t.firstChild;
      if (el.className) s.className += ' ' + el.className;
      el.replaceWith(s);
    });
  }
  // Tooltip para marcas de gráficas: elementos con data-tip dentro de un contenedor posicionado.
  function tooltips(raiz) {
    (raiz || document).querySelectorAll('[data-tip-box]').forEach(function (box) {
      var tip = document.createElement('div');
      tip.className = 'cc-tip';
      box.appendChild(tip);
      box.addEventListener('mousemove', function (e) {
        var m = e.target.closest('[data-tip]');
        if (!m) { tip.style.opacity = 0; return; }
        tip.textContent = m.getAttribute('data-tip');
        var r = box.getBoundingClientRect();
        tip.style.left = Math.min(e.clientX - r.left + 12, r.width - tip.offsetWidth - 4) + 'px';
        tip.style.top = (e.clientY - r.top - 34) + 'px';
        tip.style.opacity = 1;
      });
      box.addEventListener('mouseleave', function () { tip.style.opacity = 0; });
    });
  }
  window.CostaClara = { iconos: Object.keys(P), icono: icono, hidratar: hidratar, tooltips: tooltips };
})();

/* Comportamiento del shell: modo oscuro, menu colapsado y cajon movil. */
(function () {
  function guardar(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  function leer(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }

  window.CostaClara.alternarTema = function () {
    var html = document.documentElement;
    var oscuro = html.getAttribute('data-tema') !== 'oscuro';
    // Sin transiciones durante el cambio: si no, algunos fondos se quedan con el color anterior
    html.classList.add('cc-sin-transicion');
    html.setAttribute('data-tema', oscuro ? 'oscuro' : 'claro');
    void html.offsetHeight;
    setTimeout(function () { html.classList.remove('cc-sin-transicion'); }, 50);
    guardar('cc-tema', oscuro ? 'oscuro' : 'claro');
    var t = document.querySelectorAll('[data-cc-tema-texto]');
    for (var i = 0; i < t.length; i++) t[i].textContent = oscuro ? 'Modo claro' : 'Modo oscuro';
  };

  document.addEventListener('DOMContentLoaded', function () {
    var app = document.getElementById('ccApp');
    CostaClara.hidratar();
    if (!app) return;
    if (leer('cc-menu') === 'colapsado') app.classList.add('cc-app--colapsada');
    var oscuro = document.documentElement.getAttribute('data-tema') === 'oscuro';
    var t = document.querySelectorAll('[data-cc-tema-texto]');
    for (var i = 0; i < t.length; i++) t[i].textContent = oscuro ? 'Modo claro' : 'Modo oscuro';

    document.addEventListener('click', function (e) {
      if (e.target.closest('[data-cc-colapsar]')) {
        app.classList.toggle('cc-app--colapsada');
        guardar('cc-menu', app.classList.contains('cc-app--colapsada') ? 'colapsado' : 'abierto');
      } else if (e.target.closest('[data-cc-tema]')) {
        e.preventDefault();
        CostaClara.alternarTema();
      } else if (e.target.closest('[data-cc-abrir-menu]')) {
        app.classList.add('cc-app--menu-abierto');
      } else if (e.target.closest('[data-cc-cerrar-menu]')) {
        app.classList.remove('cc-app--menu-abierto');
      }
    });
  });
})();