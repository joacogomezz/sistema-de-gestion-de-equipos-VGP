// Service Worker para Sistema de Gestión de Equipos - Villa Group
// Versión: 1.1.0 (limpieza de caches legacy)

const CACHE_NAME = 'villa-equipos-v2';
const RUNTIME_CACHE = 'villa-runtime-v2';

// Archivos estáticos para cachear (rutas relativas)
const STATIC_ASSETS = [
  './',
  './index.php',
  './registro.php',
  './css/villa-group-theme.css',
  './css/menu.css',
  './librerias/bootstrap/css/bootstrap.min.css',
  './librerias/jquery-3.2.1.min.js',
  './librerias/bootstrap/js/bootstrap.min.js',
  './js/funciones.js',
  './js/seguridad-navegacion.js',
  './librerias/alertifyjs/alertify.min.js',
  './librerias/alertifyjs/css/alertify.css',
  './img/hotel.jpg',
  './manifest.json'
];

// Instalación del Service Worker
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Instalando...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Cacheando archivos estáticos');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('[Service Worker] Instalación completada');
        return self.skipWaiting(); // Activar inmediatamente
      })
      .catch((error) => {
        console.error('[Service Worker] Error en instalación:', error);
      })
  );
});

// Activación del Service Worker
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activando...');
  
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          // Eliminar caches antiguos
          if (cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE) {
            console.log('[Service Worker] Eliminando cache antiguo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
    .then(() => {
      console.log('[Service Worker] Activación completada');
      return self.clients.claim(); // Tomar control de todas las páginas
    })
  );
});

// Estrategia de Cache: Network First, Fallback to Cache
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Solo cachear requests GET
  if (request.method !== 'GET') {
    return;
  }

  // No cachear requests a procesos PHP (siempre necesitan servidor)
  if (url.pathname.includes('/procesos/')) {
    return;
  }

  // Estrategia: Network First para páginas dinámicas
  if (url.pathname.endsWith('.php')) {
    event.respondWith(
      fetch(request)
        .then((response) => {
          // Si la red funciona, actualizar cache y devolver respuesta
          const responseClone = response.clone();
          caches.open(RUNTIME_CACHE).then((cache) => {
            cache.put(request, responseClone);
          });
          return response;
        })
        .catch(() => {
          // Si falla la red, intentar desde cache
          return caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
          // Si no hay cache, mostrar página offline
          return caches.match('./index.php');
          });
        })
    );
    return;
  }

  // Estrategia: Cache First para recursos estáticos
  event.respondWith(
    caches.match(request)
      .then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        
        // Si no está en cache, buscar en red
        return fetch(request)
          .then((response) => {
            // Solo cachear respuestas válidas
            if (!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }

            const responseToCache = response.clone();
            caches.open(RUNTIME_CACHE).then((cache) => {
              cache.put(request, responseToCache);
            });

            return response;
          })
          .catch(() => {
            // Si falla todo, devolver respuesta offline básica
            if (request.destination === 'image') {
              return new Response('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="#1a1a1a"/><text x="50%" y="50%" text-anchor="middle" fill="#c29a4b" font-size="16">Imagen no disponible</text></svg>', {
                headers: { 'Content-Type': 'image/svg+xml' }
              });
            }
          });
      })
  );
});

// Manejo de mensajes desde la app
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'CLEAR_CACHE') {
    caches.delete(CACHE_NAME);
    caches.delete(RUNTIME_CACHE);
  }
});

// Sincronización en segundo plano (para cuando vuelva la conexión)
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-data') {
    event.waitUntil(
      // Aquí puedes agregar lógica para sincronizar datos pendientes
      console.log('[Service Worker] Sincronizando datos...')
    );
  }
});

