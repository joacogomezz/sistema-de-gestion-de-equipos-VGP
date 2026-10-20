# Progressive Web App (PWA) — Guía única

Documentación consolidada para instalar el sistema como aplicación, HTTPS, iconos y verificación. Sustituye a **GUIA_PWA.md** e **INSTRUCCIONES_PWA.md**.

---

## 1. ¿Qué aporta la PWA?

- **Instalable** en escritorio y móvil (icono propio, ventana tipo app).
- **Caché** de recursos estáticos mediante **Service Worker** (`sw.js`).
- Mejor experiencia móvil (meta `theme-color`, iconos Apple).

Las páginas PHP siguen necesitando **servidor**; el modo offline cubre sobre todo **assets** cacheados, no toda la lógica de negocio.

---

## 2. Archivos involucrados

| Archivo | Rol |
|---------|-----|
| `manifest.json` | Nombre, colores, `start_url`, iconos |
| `sw.js` | Registro de caché y estrategia `fetch` |
| `js/pwa-install.js` | Registro del SW y flujo de instalación |
| `index.php`, `registro.php`, `vistas/menu.php` | Enlaces al manifest y scripts PWA |

---

## 3. HTTPS (obligatorio fuera de localhost “seguro”)

Los navegadores exigen **HTTPS** (o `localhost` en Chrome/Edge) para instalar PWA y registrar el Service Worker de forma fiable.

- **Producción:** certificado SSL en Apache/Nginx/IIS.
- **Pruebas desde el móvil o internet:** usar **ngrok** (ver **`DESPLIEGUE_Y_ACCESO_REMOTO.md`**).
- **Solo en tu PC:** `http://localhost/...` o `http://127.0.0.1/...` suele bastar en Chrome/Edge para desarrollo.

---

## 4. Iconos

Necesitas iconos en `img/` (típicamente `icon-192x192.png`, `icon-512x512.png`, `apple-touch-icon.png`) y rutas coherentes en `manifest.json`.

**Generador incluido en el proyecto:** abre en el navegador (ajusta la URL a tu entorno):

- `http://localhost/ventasAlmacen22/generar-iconos.php`  
  o, con PHP embebido: `http://localhost:8000/generar-iconos.php`

Desde ahí puedes generar/redimensionar iconos a partir de una imagen base.

También puedes usar herramientas externas (por ejemplo [RealFaviconGenerator](https://realfavicongenerator.net/)).

---

## 5. Verificación (Chrome / Edge)

1. Abre el sistema con HTTPS o `localhost`.
2. **F12** → pestaña **Aplicación** (Application):
   - **Manifest:** sin errores, datos correctos.
   - **Service Workers:** estado *activated*.
   - **Almacenamiento en caché:** entradas del `sw.js`.
3. **Lighthouse** (F12 → Lighthouse): categoría **Progressive Web App** para una puntuación orientativa.

**Probar caché:** en **Red**, marca **Sin conexión** y recarga; los estáticos cacheados pueden mostrarse; las rutas PHP pueden fallar sin servidor.

---

## 6. Instalación en dispositivos

### Android (Chrome)

1. Abre la URL del sistema (idealmente HTTPS / ngrok).
2. Menú (⋮) → **Instalar app** o el banner de instalación.
3. Confirma; el icono aparecerá en el cajón de aplicaciones.

### iOS (Safari)

1. Abre la URL en **Safari** (no en Chrome para “Agregar a inicio”).
2. **Compartir** → **Agregar a pantalla de inicio**.
3. Confirma el nombre y **Agregar**.

### Windows (Chrome / Edge)

Icono de instalación en la barra de direcciones o menú → **Instalar esta aplicación como…**

---

## 7. Actualizar versión del Service Worker

En `sw.js`, cambia el nombre del caché (por ejemplo de `villa-equipos-v1` a `villa-equipos-v2`), guarda y recarga; el navegador descargará el nuevo worker.

Para limpiar registros durante pruebas (consola del navegador):

```javascript
navigator.serviceWorker.getRegistrations().then(function (r) {
  r.forEach(function (reg) { reg.unregister(); });
});
```

Luego recarga con **Ctrl+F5**.

---

## 8. Solución de problemas

| Síntoma | Qué revisar |
|---------|-------------|
| No aparece “Instalar” | HTTPS (o localhost en Chrome), manifest válido, iconos 192 y 512, SW activo. |
| SW no se registra | URL del `sw.js` accesible, sin errores de sintaxis, HTTPS/localhost. |
| Manifest con error | JSON válido, rutas de iconos existentes. |

---

## 9. Prueba rápida en página

Abre **`test-pwa.html`** desde la misma base URL que el sistema (por ejemplo `http://localhost:8000/test-pwa.html`) para comprobar registro del Service Worker y acceso a `manifest.json`. También puedes usar la pestaña **Aplicación** de DevTools en cualquier pantalla del sistema.

---

*Abril 2026.*
