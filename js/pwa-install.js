// Script para manejar la instalación de la PWA
// Sistema de Gestión de Equipos - Villa Group

let deferredPrompt;
let installButton;

// Detectar cuando el navegador muestra el prompt de instalación
window.addEventListener('beforeinstallprompt', (e) => {
  console.log('[PWA] Prompt de instalación disponible');
  
  // Prevenir el prompt automático
  e.preventDefault();
  
  // Guardar el evento para usarlo después
  deferredPrompt = e;
  
  // Mostrar botón de instalación personalizado
  showInstallButton();
});

// Mostrar botón de instalación
function showInstallButton() {
  // Buscar si ya existe un botón de instalación
  installButton = document.getElementById('pwa-install-button');
  
  if (!installButton) {
    // Crear botón si no existe
    installButton = document.createElement('button');
    installButton.id = 'pwa-install-button';
    installButton.className = 'btn btn-primary';
    installButton.innerHTML = '<span class="glyphicon glyphicon-download"></span> Instalar App';
    installButton.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; box-shadow: 0 4px 15px rgba(194, 154, 75, 0.4);';
    
    // Agregar evento de clic
    installButton.addEventListener('click', installPWA);
    
    // Agregar al body
    document.body.appendChild(installButton);
  }
  
  // Mostrar el botón
  installButton.style.display = 'block';
}

// Ocultar botón de instalación
function hideInstallButton() {
  if (installButton) {
    installButton.style.display = 'none';
  }
}

// Instalar la PWA
async function installPWA() {
  if (!deferredPrompt) {
    console.log('[PWA] No hay prompt disponible');
    return;
  }
  
  // Mostrar el prompt de instalación
  deferredPrompt.prompt();
  
  // Esperar la respuesta del usuario
  const { outcome } = await deferredPrompt.userChoice;
  
  console.log(`[PWA] Usuario ${outcome === 'accepted' ? 'aceptó' : 'rechazó'} la instalación`);
  
  // Limpiar el prompt
  deferredPrompt = null;
  
  // Ocultar el botón
  hideInstallButton();
}

// Detectar si la app ya está instalada
window.addEventListener('appinstalled', () => {
  console.log('[PWA] App instalada exitosamente');
  deferredPrompt = null;
  hideInstallButton();
  
  // Mostrar notificación de éxito
  if (typeof alertify !== 'undefined') {
    alertify.success('¡App instalada correctamente!');
  }
});

// Verificar si la app está en modo standalone (ya instalada)
function isStandalone() {
  return (window.matchMedia('(display-mode: standalone)').matches) ||
         (window.navigator.standalone) ||
         document.referrer.includes('android-app://');
}

// Si ya está instalada, ocultar el botón
if (isStandalone()) {
  console.log('[PWA] App ya está instalada');
  hideInstallButton();
}

// Registrar el Service Worker (ruta desde la raíz de la app para evitar 404 en vistas/)
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    const pathname = location.pathname;
    let base = pathname.replace(/\/vistas\/.*$/, '');
    if (pathname.indexOf('/vistas/') === -1) base = base.replace(/\/[^/]*$/, '');
    const swUrl = (base && base !== '/') ? base + '/sw.js' : '/sw.js';
    const expectedScope = (base && base !== '/') ? (base + '/') : '/';

    // Limpieza agresiva de registros legacy (servidor/scope viejo) para evitar conflictos.
    navigator.serviceWorker.getRegistrations().then((registrations) => {
      const stale = registrations.filter((r) => {
        const activeUrl = r.active && r.active.scriptURL ? r.active.scriptURL : '';
        const waitingUrl = r.waiting && r.waiting.scriptURL ? r.waiting.scriptURL : '';
        const installingUrl = r.installing && r.installing.scriptURL ? r.installing.scriptURL : '';
        const scope = r.scope || '';
        const hasLegacyViewsSw = activeUrl.includes('/vistas/sw.js') ||
                                 waitingUrl.includes('/vistas/sw.js') ||
                                 installingUrl.includes('/vistas/sw.js');
        const differentScript = (activeUrl && !activeUrl.endsWith('/sw.js')) ||
                                waitingUrl.includes('/vistas/sw.js') ||
                                installingUrl.includes('/vistas/sw.js');
        const differentScope = scope && scope.indexOf(expectedScope) === -1;
        return hasLegacyViewsSw || differentScript || differentScope;
      });
      return Promise.all(stale.map((r) => r.unregister()));
    }).then(() => {
      // Limpiar caches heredadas del sistema para evitar mezclar recursos de hosts antiguos.
      if (!('caches' in window)) return;
      return caches.keys().then((keys) => {
        const toDelete = keys.filter((k) => {
          const name = String(k || '').toLowerCase();
          return name.indexOf('villa') !== -1 || name.indexOf('equipos') !== -1 || name.indexOf('runtime') !== -1;
        });
        return Promise.all(toDelete.map((k) => caches.delete(k)));
      });
    }).then(() => {
      return navigator.serviceWorker.register(swUrl);
    }).then((registration) => {
      console.log('[PWA] Service Worker registrado:', registration.scope);
      setInterval(() => registration.update(), 60000);
    }).catch((error) => {
      console.error('[PWA] Error al registrar Service Worker:', error);
    });
  });
  
  // Detectar actualizaciones del Service Worker
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    console.log('[PWA] Nueva versión del Service Worker disponible');
    
    // Mostrar notificación de actualización
    if (typeof alertify !== 'undefined') {
      alertify.success('Nueva versión disponible. Recargando...');
      
      setTimeout(() => {
        window.location.reload();
      }, 2000);
    } else {
      setTimeout(() => {
        window.location.reload();
      }, 2000);
    }
  });
} else {
  console.warn('[PWA] Service Worker no soportado en este navegador');
}

