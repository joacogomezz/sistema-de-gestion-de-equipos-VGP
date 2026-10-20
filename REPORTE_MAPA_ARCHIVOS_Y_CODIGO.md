# Mapa de archivos y código del sistema

Este documento describe **para qué sirve cada parte relevante** del proyecto (carpetas, archivos principales y bloques lógicos). Las **líneas son orientativas**: pueden cambiar si se edita el archivo; úsalas para ubicarte con la búsqueda en el editor.

**Índice general del proyecto:** consulta **`README.md`** en la raíz (stack, arranque y enlaces al resto de manuales para presentación o defensa).

**Ámbito:** código propio del sistema (PHP de aplicación, vistas, procesos, reportes, JS/CSS de la app). No se detalla el interior de `librerias/` (Bootstrap, jQuery, Dompdf, Alertify, etc.), salvo su función general.

*Actualizado — abril 2026.*

---

## 1. Panorama de carpetas

| Carpeta | Rol |
|--------|-----|
| **Raíz** | Entrada (`index.php`), registro inicial, configuración, PWA (`manifest.json`, `sw.js`) |
| **`clases/`** | Modelo de datos: consultas y reglas de negocio en PHP (MySQLi) |
| **`vistas/`** | Pantallas HTML/PHP con menú; lo que ve el usuario autenticado |
| **`procesos/`** | Endpoints AJAX/POST: login, CRUD, altas/bajas, resguardos, backup |
| **`reportes/`** | Generación de PDF (Dompdf) e impresión de resguardos |
| **`seguridad.php`** | Sesión, validación de usuario, cabeceras HTTP, redirección al login |
| **`config.php`** | Credenciales de BD y `RUTA_BASE` |
| **`bd/`** | Script SQL de creación de tablas (`hotel_equipos.sql`) |
| **`archivos/`** | Subidas: fotos de equipos, bajas, etc. |
| **`img/`** | Imágenes estáticas, logos, iconos PWA |
| **`css/`**, **`js/`** | Estilos y scripts propios del sistema |
| **`librerias/`** | Dependencias de terceros (no documentadas línea a línea aquí) |

---

## 2. Archivos en la raíz

### `config.php` (aprox. líneas 1–21)

- **10–14:** constantes `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` para MySQL/MariaDB.
- **16–21:** `RUTA_BASE`: prefijo de URL si el proyecto no está en la raíz del servidor.

### `config.ejemplo.php` (si existe)

- Plantilla para copiar a `config.php` en otro entorno (sin contraseñas reales en el repo).

### `index.php` (aprox. 640 líneas)

- **1–23:** Conexión a BD; cuenta usuarios; si **no hay ninguno**, redirige a `registro.php` (primer arranque).
- **25–~520:** Documento HTML del **login**: meta PWA, estilos embebidos, tarjeta de acceso.
- **~480–537:** Mensajes según `?mensaje=` (sesión expirada, etc.).
- **539–562:** Formulario `#frmLogin` (usuario, contraseña, botón entrar; enlace a registro solo si aún no hay usuarios).
- **565–641:** jQuery: validación, `$.ajax` a `procesos/regLogin/login.php`, redirección a `vistas/inicio.php` si responde `1`; trucos de historial para el botón “atrás” en login.

### `registro.php` (aprox. 248 líneas)

- **1–19:** Conexión; si **ya hay usuarios**, solo muestra aviso y enlace al login (no permite registrar de nuevo).
- **21–153:** HTML + CSS del formulario de **primer usuario** (super usuario).
- **164–197:** Formulario `#frmRegistro` o mensaje “ya existen usuarios”.
- **203–246:** Envío AJAX a `procesos/regLogin/registro.php`, validación de contraseñas en cliente.

### `seguridad.php` (aprox. 277 líneas)

- **10–24:** Inicio de sesión PHP (`session_start`, cookies).
- **26–117:** `validarSesion()`: comprueba usuario en sesión, tiempos máximo e inactividad, existencia en BD, actualiza `id_hotel` / `nombre_hotel`.
- **119–151:** `cerrarSesion()` y destrucción de cookie.
- **156–159:** `regenerarIdSesion()`.
- **164–184:** `establecerHeadersSeguridad()`: `X-Frame-Options`, CSP básica, caché, etc.
- **189–218:** `requerirAutenticacion()`: si no hay sesión válida, redirige a `index.php?mensaje=sesion_expirada`.
- **223–235:** utilidades CSRF (preparadas para uso futuro).
- **262–276:** Si **no** está definido `PDF_OUTPUT`, aplica cabeceras y regeneración periódica de ID de sesión. Los scripts que generan PDF definen `PDF_OUTPUT` antes de incluir este archivo para evitar conflictos con Dompdf.

### `manifest.json`

- Metadatos de la **PWA**: nombre, iconos, color de tema, `start_url`.

### `sw.js` (Service Worker)

- **4–23:** nombres de caché y lista de recursos estáticos a cachear.
- **26+:** eventos `install`, `fetch`, estrategia offline/runtime.

### `generar-iconos.php` (si existe)

- Utilidad para generar/redimensionar iconos PWA desde una imagen base.

### `iniciar-servidor.bat`

- Atajo para levantar el servidor de desarrollo PHP en Windows.

---

## 3. `clases/` — capa de datos y reglas

| Archivo | Función principal | Métodos / zonas útiles |
|--------|-------------------|-------------------------|
| **`Conexion.php`** | Abre conexión MySQL con datos de `config.php` | `__construct` lee constantes; `conexion()` devuelve el recurso mysqli |
| **`Usuarios.php`** | Altas de usuario, login, perfil | `registroUsuario`, `loginUser`, `traeID`, `obtenDatosUsuario`, `actualizaUsuario`, `eliminaUsuario` |
| **`Hoteles.php`** | Catálogo multi-hotel | `obtenerTodos`, `getNombre`, `existeYActivo` |
| **`Equipos.php`** | CRUD de equipos, búsqueda | `insertaEquipo`, `obtenDatosEquipo`, `actualizaEquipo`, `eliminaEquipo`, `cambiarEstadoEquipo`, `obtenerEquiposPorEstado`, `buscarEquipo`, `obtenerEquipoPorCodigo` |
| **`Departamentos.php`** | Departamentos por hotel | `agregarDepartamento`, `obtenDatosDepartamento`, `actualizaDepartamento`, `eliminaDepartamento`, `obtenerTodosDepartamentos`, `obtenerDepartamentosConEquipos` |
| **`Areas.php`** | Áreas (catálogo) | `agregar`, `obtenerTodos`, `obtenerDatos`, `actualizar`, `eliminar` |
| **`Fases.php`** | Fases (catálogo) | Igual patrón que `Areas` |
| **`AltasBajas.php`** | Movimientos de alta, baja individual y masiva | `registrarAlta`, `obtenerDatosAlta`, `registrarBaja`, `registrarBajaMasiva`, consultas por fechas, resúmenes, listados de bajas masivas |
| **`Resguardos.php`** | Resguardos individuales y agrupados | `guardarResguardo`, `obtenerDatosResguardo`, listas agrupadas, `asignarGrupoResguardos`, `obtenerIdsMismoGrupo`, eliminación simple o de grupo |
| **`SolicitudAltaEquipos.php`** | Solicitudes de alta de equipos (cabecera + detalle) | `guardar`, `obtenerSolicitud`, `obtenerDetalle` |

---

## 4. `vistas/` — pantallas

Patrón habitual: `require_once "../seguridad.php"` + `requerirAutenticacion();`, luego HTML. Muchas páginas incluyen **`menu.php`** (navbar, rutas absolutas según `SCRIPT_NAME`).

### `menu.php`

- Cabecera HTML común: Bootstrap, tema Villa, Alertify, `funciones.js`, `seguridad-navegacion.js`, PWA.
- **~8–28:** Cálculo de `$vistasBase` y `$procesosBase` para enlaces correctos desde cualquier subcarpeta.
- Navbar con enlaces a inicio, equipos, departamentos, áreas, fases, altas, bajas, bajas masivas, solicitud alta, resguardos, reportes, usuarios, backup, salir.

### `inicio.php`

- Dashboard de bienvenida tras login (estilos inline, tarjetas de acceso rápido según diseño actual).

### Módulos por archivo principal

| Vista | Contenido típico |
|-------|------------------|
| **`equipos.php`** | Contenedor; carga fragmentos de `equipos/agregarEquipo.php` y `equipos/tablaEquipos.php` |
| **`equipos/agregarEquipo.php`** | Formulario alta/edición de equipo |
| **`equipos/tablaEquipos.php`** | Listado, acciones, llamadas AJAX a `procesos/equipos/*` |
| **`departamentos.php`** | CRUD departamentos: `departamentosExistentes`, `agregarDepartamento`, `tablaDepartamentos` |
| **`areas.php`** | Áreas: `areas/agregarArea`, `areas/tablaAreas` |
| **`fases.php`** | Fases: `fases/agregarFase`, `fases/tablaFases` |
| **`altas.php`** | Registro de altas: `altas/registrarAlta`, `altas/tablaAltas` |
| **`bajas.php`** | Baja individual: `bajas/registrarBaja`, `bajas/tablaBajas` |
| **`bajasMasivas.php`** | Baja masiva: `bajasMasivas/registrarBajaMasiva`, `tablaBajasMasivas` |
| **`solicitudAltaEquipos.php`** | Solicitud formal de altas: `solicitudAltaEquipos/formularioSolicitudAlta` |
| **`resguardos.php`** | Resguardos: botones que cargan en `#contenido` `generarResguardo`, `generarResguardoMultiple`, `tablaResguardos` |
| **`resguardos/generarResguardo.php`** | Un equipo: búsqueda por código, formulario, guardar → PDF |
| **`resguardos/generarResguardoMultiple.php`** | Varios equipos misma factura |
| **`resguardos/tablaResguardos.php`** | Historial; enlace a PDF vía `descargarPdfResguardo` / `imprimirResguardo.php` |
| **`usuarios.php`** | Administración de usuarios del sistema |
| **`usuarios/tablaUsuarios.php`** | Tabla y acciones |
| **`reportes.php`** | Índice de enlaces a reportes en `vistas/reportes/` y PDFs en `reportes/` |
| **`reportes/reporteEquipos.php`**, **`reporteAltas.php`**, **`reportePorDepartamento.php`**, **`reporteBajasIndividuales.php`**, **`reporteBajasMasivas.php`**, **`informeTecnicoBaja.php`** | Vistas puente o listados que enlazan/generan PDFs según el caso |
| **`backup.php`** | Interfaz de respaldo de BD (POST a `procesos/backup/respaldar_bd.php`) |

---

## 5. `procesos/` — backend por carpeta

### `regLogin/`

- **`login.php`:** Recibe `usuario` y `password` por POST; usa `Usuarios::loginUser`; devuelve `1` o fallo.
- **`registro.php` / `registrarUsuario.php`:** Registro del primer usuario o flujo asociado (validar cuál usa el formulario actual).

### `equipos/`

- **`insertaEquipo.php`**, **`actualizaEquipo.php`**, **`eliminaEquipo.php`**, **`obtenerEquipo.php`:** CRUD y lectura para AJAX.
- **`buscarEquipos.php`:** Búsqueda para tablas o autocompletado.

### `departamentos/`

- **`agregarDepartamento.php`**, **`actualizaDepartamento.php`**, **`eliminarDepartamento.php`:** CRUD.

### `areas/` y `fases/`

- **`agregar*.php`**, **`actualiza*.php`**, **`eliminar*.php`:** operaciones sobre catálogos.

### `altas/` y `bajas/`

- **`registrarAlta.php`**, **`obtenerInfoEquipo.php`:** alta de equipo.
- **`registrarBaja.php`**, **`eliminarBaja.php`:** baja individual.

### `bajasMasivas/`

- **`registrarBajaMasiva.php`**, **`buscarEquipos.php`**, **`eliminarBajaMasiva.php`:** flujo masivo.

### `resguardos/`

- **`guardarResguardo.php`**, **`guardarResguardosMultiples.php`:** persisten y devuelven JSON (`id`, `ids`).
- **`obtenerEquipoPorCodigo.php`:** datos de equipo para formularios.
- **`eliminarResguardo.php`**, **`eliminarResguardoOGrupo.php`:** borrado seguro.

### `solicitudAltaEquipos/`

- **`guardarSolicitudAltaEquipos.php`:** guarda cabecera y líneas; puede disparar generación de PDF.

### `usuarios/`

- **`actualizaUsuario.php`**, **`eliminarUsuario.php`**, **`obtenDatosUsuario.php`:** gestión de cuentas.

### `backup/`

- **`respaldar_bd.php`:** exportación SQL u otro formato según implementación.

### Raíz de `procesos/`

- **`salir.php`:** Cierra sesión y redirige al login.

---

## 6. `reportes/` — PDF (Dompdf)

| Archivo | Propósito |
|---------|-----------|
| **`reporteResguardo.php`** | PDF resguardo **un** equipo (`?id=`). Define `PDF_OUTPUT` al inicio. |
| **`reporteResguardoMultiple.php`** | PDF resguardo **varios** equipos (`?ids=1,2,3`). |
| **`imprimirResguardo.php`** | Redirige al individual o múltiple según `id_grupo_resguardo`; puede definir `PDF_OUTPUT` para encadenar descarga en iframe. |
| **`reporteEquiposActivos.php`** | Listado PDF de equipos activos. |
| **`reporteTodasLasBajas.php`** | PDF de bajas. |
| **`reporteSolicitudAltaEquipos.php`** | PDF de solicitud de alta. |
| **`informeTecnicoBaja.php`**, **`informeTecnicoBajaMasiva.php`** | Informes técnicos de baja. |

Flujo típico: HTML embebido en PHP → `Dompdf` → `$dompdf->stream(..., ['Attachment' => true])`.

---

## 7. `js/` y `css/` propios

| Archivo | Función |
|---------|---------|
| **`js/funciones.js`** | `validarFormVacio`, `validarFormVacioConArchivos`, **`descargarPdfResguardo(url)`** (iframe oculto para PDF sin nueva pestaña) |
| **`js/seguridad-navegacion.js`** | Intercepta `popstate` (botón atrás) y redirige al login con mensaje |
| **`js/pwa-install.js`** | Prompt / lógica de instalación PWA |
| **`css/menu.css`** | Estilos del menú |
| **`css/villa-group-theme.css`** | Tema visual global Villa Group |

---

## 8. `bd/`, `archivos/`, `img/`

- **`bd/hotel_equipos.sql`:** Crea BD `hotel_equipos`, tablas (`hoteles`, `usuarios`, `departamentos`, `equipos`, altas, bajas, bajas masivas, resguardos, fases, areas, solicitudes…). Comentarios al inimo del archivo describen el alcance.
- **`archivos/`:** Rutas guardadas en BD para fotos y documentos subidos (equipos, bajas, etc.); no conviene borrar a mano sin actualizar la BD.
- **`img/`:** Logos, `hotel.jpg`, iconos `icon-192`, `icon-512`, `apple-touch-icon`, etc.

---

## 9. `librerias/` (resumen)

- **Bootstrap:** grid, componentes UI.
- **jQuery:** AJAX y DOM.
- **Alertify:** diálogos y notificaciones.
- **Dompdf (+ dependencias internas):** HTML → PDF en `reportes/`.
- **FPDF** (`librerias/fpdf/`): alternativa PDF si algún reporte la usa.

No hace falta leer miles de archivos internos de Dompdf para mantener el sistema: basta saber que **`reportes/*.php`** son los puntos de entrada de los PDF.

---

## 10. Cómo usar este mapa en el día a día

1. **¿Dónde está la pantalla X?** → Busca en `vistas/` el nombre del menú o del módulo.
2. **¿Qué hace el botón al guardar?** → En el `.php` de la vista, busca `url:` en `$.ajax` → archivo en `procesos/`.
3. **¿Dónde está la consulta SQL?** → Casi siempre en `clases/NombreEntidad.php`.
4. **¿Dónde se arma el PDF?** → `reportes/` con el nombre del documento.
5. **Sesión y permisos** → `seguridad.php` + comprobaciones al inicio de cada vista.

Si necesitas **líneas exactas** de un archivo concreto tras un cambio, abre el archivo y usa “Ir a línea” en el editor; este reporte te indica **qué bloque buscar** en cada zona.
