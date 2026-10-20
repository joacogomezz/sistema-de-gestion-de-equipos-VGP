# Sistema de Gestión de Equipos — Hotel Villa Group

Aplicación web en **PHP** y **MySQL/MariaDB** para inventario de equipos de hotel: altas, bajas (individual y masiva), departamentos, resguardos en PDF, reportes y respaldo de base de datos. Incluye soporte **multi-hotel** y **PWA** (instalable en móvil).

---

## Stack y tecnologías

| Capa | Tecnología |
|------|------------|
| Backend | PHP (MySQLi), sesiones, `seguridad.php` |
| Base de datos | MySQL/MariaDB, script `bd/hotel_equipos.sql` |
| Frontend | HTML, Bootstrap 3, jQuery, Alertify |
| PDF | Dompdf (`librerias/dompdf/`) |
| PWA | `manifest.json`, `sw.js`, `js/pwa-install.js` |

Patrón **MVC ligero**: clases en `clases/`, vistas en `vistas/`, endpoints en `procesos/`, PDF en `reportes/`.

---

## Arranque rápido

1. Importar `bd/hotel_equipos.sql` y configurar `config.php` (o copiar desde `config.ejemplo.php`).
2. **Con XAMPP:** Apache + MySQL; abrir la URL del proyecto (ej. `http://localhost/ventasAlmacen22/`).
3. **Solo PHP:** en la carpeta del proyecto: `php -S localhost:8000` → `http://localhost:8000` (detalle en `INICIAR-SIN-XAMPP.md`).
4. En Windows puedes usar `iniciar-servidor.bat` para el servidor embebido.

Si no existe ningún usuario, el sistema redirige a `registro.php` para crear el **super administrador** (ID 1).

---

## Índice de documentación (presentación y defensa)

Usa este orden para explicar el proyecto de forma lógica.

| Orden | Archivo | Contenido |
|-------|----------|-----------|
| 1 | **README.md** (este) | Resumen, stack, arranque, índice |
| 2 | **GUIA_SISTEMA.md** | Qué problema resuelve el sistema y para qué sirve cada módulo (lenguaje de negocio) |
| 3 | **MANUAL_DE_USUARIO.md** | Operación paso a paso para usuarios finales |
| 4 | **MANUAL_TECNICO_COMPLETO.md** | Arquitectura, autenticación, base de datos, flujos, troubleshooting |
| 5 | **REPORTE_MAPA_ARCHIVOS_Y_CODIGO.md** | Mapa de carpetas y archivos clave (dónde tocar qué) |
| 6 | **INICIAR-SIN-XAMPP.md** | PHP + MariaDB sin panel XAMPP; errores típicos de conexión |
| 7 | **DESPLIEGUE_Y_ACCESO_REMOTO.md** | ngrok, acceso por red local, móvil, inicio automático al encender el PC |
| 8 | **PWA.md** | Instalación como app, HTTPS, iconos, pruebas |

---

## Archivos de configuración importantes

- **`config.php`** — Credenciales de BD y `RUTA_BASE`.
- **`seguridad.php`** — Sesión, expiración, cabeceras HTTP; las vistas autenticadas lo incluyen.

---

## Licencia y uso

Proyecto académico / interno Villa Group. Las librerías en `librerias/` conservan sus propias licencias (Bootstrap, jQuery, Dompdf, Alertify, etc.).

---

*Documentación unificada — abril 2026.*
