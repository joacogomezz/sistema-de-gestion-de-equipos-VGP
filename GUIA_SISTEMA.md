# Guía del Sistema – Gestión de Equipos Hotel Villa Group

## 1. ¿De qué trata este sistema?

Este sistema es una **aplicación web de inventario y control de activos** pensada para los hoteles del **Villa Group** (Villa del Arco, Villa la Estancia, Villa del Palmar). Sirve para:

- **Saber qué equipos tiene el hotel:** computadoras, impresoras, televisores, equipos de cocina, etc., con código único, descripción, marca, modelo, serie, ubicación y valor.
- **Saber quién es responsable:** cada equipo se asigna a un **departamento** (Cocina, Recepción, Sistemas, Mantenimiento, etc.) y, cuando se entrega a una persona o área, se genera un **resguardo** en PDF que acredita al responsable.
- **Controlar el ciclo de vida:** equipos **activos**, **en mantenimiento**, **en reparación** o **dados de baja**, con registro de **bajas individuales** (motivo, valor residual, destino, fotos, firmas) y **bajas masivas** (varios equipos en un solo documento).
- **Documentar todo para auditoría:** informes técnicos en PDF por baja individual o masiva, reportes de equipos activos, por departamento, altas y bajas.

**En una frase:** es el **inventario centralizado de equipos del hotel**: qué hay, dónde está, quién lo tiene, cuándo se dio de baja o de alta, y los PDF que respaldan cada operación.

---

## 2. ¿Para qué hacemos cada cosa?

### 2.1 Registrar equipos

**Para qué:** Tener un catálogo único de cada activo con datos que permitan identificarlo y valuarlo.

**Qué hacemos:** En **Administrar Equipos → Equipos** agregamos cada equipo con:

- **Código de equipo** (único en el sistema): identificación interna.
- **Descripción, marca, modelo, número de serie.**
- **Departamento** al que pertenece (Cocina, Recepción, etc.).
- **Ubicación** física.
- **Valor de adquisición** y **tipo de moneda** (USD, MXN, EUR).
- **Foto del equipo** (opcional).
- **Estado:** Activo, En Mantenimiento, En Reparación o Dado de Baja.

Así evitamos duplicados, confusiones y tenemos un registro claro para seguros, auditorías o ventas de activos.

### 2.2 Gestionar departamentos

**Para qué:** Organizar los equipos por área del hotel y tener un responsable o representante por departamento.

**Qué hacemos:** En **Administrar Equipos → Departamentos** creamos y editamos los departamentos (nombre, descripción, responsable, representante). No hay departamentos precargados; todos se dan de alta desde aquí. Los equipos y resguardos se asocian a estos departamentos.

### 2.3 Dar de baja equipos (individual)

**Para qué:** Dejar constancia formal de que un equipo ya no se usa: por obsoleto, daño, venta, donación, etc., con motivo, valor residual, destino y evidencia (fotos, firmas).

**Qué hacemos:** En **Bajas → Registrar Bajas** buscamos el equipo por código, completamos:

- Motivo de baja, valor residual, destino del equipo.
- Fotos (equipo y/o etiqueta).
- Nombres de técnico que revisó y gerente de área (firmas conceptuales).
- Fecha de adquisición (opcional).

Al registrar, el equipo pasa a estado **Dado de Baja**. Desde la tabla de bajas se puede generar el **Informe Técnico** en PDF (fotos, firmas, observaciones) para auditoría o procedimientos internos.

### 2.4 Bajas masivas

**Para qué:** Agrupar varios equipos ya dados de baja en un solo documento oficial (por ejemplo, una baja masiva por renovación de equipo en un área).

**Qué hacemos:** Primero cada equipo debe estar dado de **baja individual**. Luego en **Bajas → Bajas Masivas** creamos una baja masiva (fecha, motivo, valor total, destino, área/hotel), agregamos los equipos con observación por cada uno y generamos el PDF “Formato de Baja” (informe técnico masivo). Así se documenta un lote completo en un solo reporte.

### 2.5 Dar de alta de nuevo (reincorporar)

**Para qué:** Cuando un equipo que estaba dado de baja vuelve a usarse (reparado, reasignado, etc.), actualizar su estado a **Activo** y dejar registro.

**Qué hacemos:** En la vista **Altas** (`vistas/altas.php`) listamos equipos en estado “Dado de Baja”, elegimos el que corresponde y lo damos de alta. El sistema cambia el estado a **Activo** y registra la operación en `altas_equipos`.

### 2.6 Emitir resguardos

**Para qué:** Tener un documento PDF que acredite quién tiene el equipo: área destino, responsable, puesto, número de factura, modelo, serie.

**Qué hacemos:**

- **Resguardo individual (un equipo):** En **Administrar Equipos → Resguardos** → **Generar Resguardo (un equipo)** se ingresa el código del equipo; se completan área destino, responsable, **puesto** (cargo, no el nombre), factura, etc. El campo **Tipo** se precarga con la **descripción del equipo** (dato del alta) y se puede ajustar. Se genera un PDF por equipo.
- **Resguardo múltiple (varios equipos, misma factura):** En **Resguardos** → **Resguardo múltiple** se ingresan los datos comunes (factura, área, responsable, puesto, fecha) y se agregan varios equipos por código. Se genera **un solo PDF** con una tabla de todos los equipos (Tipo = descripción de cada equipo, tomada del alta). En el historial, los resguardos múltiples se muestran **agrupados en una sola fila** (ej. "Múltiple (3 equipos)"); al imprimir desde ahí se abre ese único PDF. Individuales y múltiples están separados (campo `id_grupo_resguardo` en BD).
- **Historial:** En **Ver Resguardos Generados** cada resguardo individual aparece en una fila; cada grupo múltiple en una fila con etiqueta "Múltiple (N equipos)". Imprimir abre el PDF correcto (individual o agrupado). Eliminar en un múltiple borra todo el grupo.

### 2.7 Reportes en PDF

**Para qué:** Auditoría, inventario físico, contabilidad y procedimientos que exigen documentación en PDF.

**Qué hacemos:**

- **Desde Inicio:** enlaces rápidos a “Reporte de Todas las Bajas” y “Reporte de Equipos Activos” (PDFs en `reportes/`).
- **Desde la vista Reportes** (`vistas/reportes.php`): reporte de equipos, por departamento, altas, bajas individuales, bajas masivas; los informes técnicos (individual y masivo) se generan desde las tablas de **Registrar Bajas** y **Bajas Masivas** respectivamente.

Cada reporte abre o descarga un PDF generado con DOMPDF.

### 2.8 Respaldo de base de datos

**Para qué:** Tener una copia de seguridad de toda la información (equipos, usuarios, bajas, resguardos, etc.) en formato `.sql`.

**Qué hacemos:** Solo el **Super Administrador** (usuario con `id_usuario = 1`) accede a **Respaldo BD** (`vistas/backup.php`). Desde ahí se descarga una copia de la base `hotel_equipos` generada por el proceso en `procesos/backup/respaldar_bd.php`.

---

## 3. Acceso y configuración

| Concepto | Detalle |
|----------|---------|
| **URL del sistema** | Con XAMPP/Apache: `http://localhost/ventasAlmacen22/`. **Sin XAMPP** (solo PHP + MariaDB): `http://localhost:8000` tras ejecutar `php -S localhost:8000` en la carpeta del proyecto (ver **INICIAR-SIN-XAMPP.md**). La ruta base se define en `config.php` con `RUTA_BASE` (raíz = `''`). |
| **Pantalla de entrada** | `index.php`: login con email y contraseña. Si no hay usuarios, usar primero `registro.php` para crear el primer usuario (administrador, ID = 1). |
| **Configuración central** | `config.php`: DB_HOST, DB_USER, DB_PASS, DB_NAME (`hotel_equipos`) y RUTA_BASE. Si copias el proyecto a otra carpeta, revisa que `config.php` tenga la contraseña correcta de MariaDB (evita "Access denied (using password: NO)"). Existe `config.ejemplo.php` como referencia. |
| **Base de datos** | Nombre: `hotel_equipos`. Instalación única con el script `bd/hotel_equipos.sql` en MySQL/MariaDB. |

---

## 4. Estructura de la aplicación (archivos clave)

- **Entrada:** `index.php` (login), `registro.php` (primer usuario/administrador).
- **Seguridad:** `seguridad.php` — autenticación, sesión, tiempo máximo de vida (1 h), cierre por inactividad (10 min), regeneración de ID de sesión (5 min), `requerirAutenticacion()` para proteger vistas, headers de seguridad (X-Frame-Options, X-Content-Type-Options, etc.).
- **Vistas (pantallas):** en `vistas/`: `inicio.php`, `equipos.php`, `departamentos.php`, `resguardos.php`, `bajas.php`, `bajasMasivas.php`, `altas.php`, `reportes.php`, `usuarios.php`, `backup.php`. Todas incluyen `menu.php` (menú superior y estilos).
- **Subvistas:** en `vistas/equipos/`, `vistas/departamentos/`, `vistas/resguardos/`, `vistas/bajas/`, `vistas/bajasMasivas/`, `vistas/altas/`, `vistas/reportes/`, `vistas/usuarios/` (tablas, formularios de agregar/editar, registrar baja/alta, etc.).
- **Procesos (lógica/API):** en `procesos/`: `regLogin/` (login, registro), `equipos/` (insertar, actualizar, eliminar, buscar, obtener por ID), `departamentos/`, `resguardos/`, `bajas/`, `bajasMasivas/`, `altas/`, `usuarios/`, `backup/`, `salir.php`.
- **Clases:** `clases/Conexion.php`, `clases/Equipos.php`, `clases/Departamentos.php`, `clases/Usuarios.php`, `clases/Resguardos.php`, `clases/AltasBajas.php`, `clases/Hoteles.php`.
- **Reportes PDF:** en `reportes/`: `reporteEquiposActivos.php`, `reporteTodasLasBajas.php`, `reporteResguardo.php`, `reporteResguardoMultiple.php` (varios equipos en una tabla), `imprimirResguardo.php` (redirige a individual o múltiple según el grupo), `informeTecnicoBaja.php`, `informeTecnicoBajaMasiva.php`. En `vistas/reportes/` están los enlaces a reportes por equipo, departamento, altas, bajas (según `vistas/reportes.php`).
- **Librería PDF:** DOMPDF en `librerias/dompdf/`.
- **Estilos y front:** `css/villa-group-theme.css`, `css/menu.css` (tema oscuro Villa Group con acentos dorados); Bootstrap, jQuery; `js/funciones.js`, `js/seguridad-navegacion.js`, `js/pwa-install.js`.

---

## 5. Base de datos (tablas principales)

| Tabla | Uso |
|-------|-----|
| **hoteles** | Catálogo de hoteles (Villa del Arco, Villa la Estancia, Villa del Palmar). Usuarios y departamentos pertenecen a un hotel (`id_hotel`). |
| **usuarios** | `id_usuario`, `id_hotel`, nombre, apellido, email, password, fechaCaptura. El usuario con `id_usuario = 1` es el Super Administrador (acceso a usuarios y respaldo BD). |
| **departamentos** | `id_departamento`, `id_hotel`, nombre, descripcion, responsable, representante, etc. Sin datos precargados; se crean desde la pantalla Departamentos. |
| **equipos** | `id_equipo`, `id_departamento`, `codigo_equipo` (único), descripcion, marca, modelo, numero_serie, estado (Activo/En Mantenimiento/En Reparación/Dado de Baja), ubicacion, fecha_ingreso, valor_adquisicion, tipo_moneda (USD/MXN/EUR), foto_equipo, y en instalaciones multi-hotel: id_hotel, numero_equipo_hotel. |
| **altas_equipos** | Registro de cada reincorporación: id_equipo, id_usuario, id_departamento, fecha_alta, motivo_alta, observaciones. |
| **bajas_equipos** | Bajas individuales: id_equipo, fecha_baja, tipo_baja (Individual/Masiva), motivo_baja, valor_residual, destino_equipo, observaciones, foto_equipo, foto_etiqueta, tecnico_reviso, gerente_area, fecha_adquisicion (opcional), y en multi-hotel: id_hotel, numero_baja_hotel. |
| **bajas_masivas** | Cabecera de cada baja masiva: fecha_baja, hotel_area, motivo_baja, valor_residual_total, destino_equipo, observaciones, total_equipos, id_hotel, numero_baja_masiva_hotel. |
| **bajas_masivas_equipos** | Relación baja masiva ↔ equipos: id_baja_masiva, id_equipo, valor_residual_individual, observaciones por equipo. |
| **resguardos** | id_resguardo, **id_grupo_resguardo** (NULL = individual; mismo valor = mismo resguardo múltiple), id_equipo, area_destino, usuario, puesto, fecha_resguardo, numero_factura, modelo, serie, tipo_equipo (se toma de la descripción del equipo al generar), id_departamento. |

---

## 6. Módulos principales (qué hace cada uno y dónde)

| Módulo | Archivo(s) | Qué hace |
|--------|------------|----------|
| **Inicio** | `vistas/inicio.php` | Panel con totales (equipos activos, dados de baja, total), enlaces a reportes PDF (todas las bajas, equipos activos) y acciones rápidas (gestionar equipos, registrar baja, bajas masivas). Filtro por hotel si no es Super Admin. |
| **Equipos** | `vistas/equipos.php` + `vistas/equipos/` + `procesos/equipos/` | Listar y buscar equipos por código/descripción; agregar equipo (código único, datos, departamento, ubicación física, foto); cargar equipos por CSV (incluyendo `departamento` y `ubicacion_fisica`); editar (incluida foto); eliminar (solo Super Admin). Estados: Activo, En Mantenimiento, En Reparación, Dado de Baja. |
| **Departamentos** | `vistas/departamentos.php` + `vistas/departamentos/` + `procesos/departamentos/` | Ver, agregar y editar departamentos. Eliminar solo Super Admin. |
| **Resguardos** | `vistas/resguardos.php`, `vistas/resguardos/` (generarResguardo.php, generarResguardoMultiple.php, tablaResguardos.php), `procesos/resguardos/` (guardarResguardo, guardarResguardosMultiples, obtenerEquipoPorCodigo, eliminarResguardoOGrupo) | **Individual:** un equipo por resguardo; **Múltiple:** varios equipos misma factura, un PDF con tabla de todos. Tipo = descripción del equipo (precargada). Historial agrupado: una fila por múltiple con "Múltiple (N equipos)". Imprimir usa `reportes/imprimirResguardo.php` (abre individual o múltiple según corresponda). |
| **Registrar Bajas** | `vistas/bajas.php`, `vistas/bajas/` + `procesos/bajas/` | Baja **individual**: buscar equipo por código, completar motivo, valor residual, destino, fotos (equipo/etiqueta), firmas (técnico, gerente), fecha adquisición opcional; registrar. Desde la tabla de bajas se genera el **Informe Técnico** en PDF (`reportes/informeTecnicoBaja.php`). |
| **Bajas Masivas** | `vistas/bajasMasivas.php` + `vistas/bajasMasivas/` + `procesos/bajasMasivas/` | Crear baja masiva y agregar equipos ya dados de baja individual; observación por equipo; generar PDF “Formato de Baja” (`reportes/informeTecnicoBajaMasiva.php`). Eliminar baja masiva solo Super Admin. |
| **Altas** | `vistas/altas.php` + `vistas/altas/` + `procesos/altas/` | Listar equipos en estado “Dado de Baja” y volver a darlos de alta (estado pasa a Activo; se registra en `altas_equipos`). |
| **Reportes** | `vistas/reportes.php` + `vistas/reportes/` y `reportes/` | Página con enlaces a los PDF: equipos, por departamento, altas, bajas individuales, bajas masivas. Los informes técnicos (individual y masivo) se generan desde las tablas de bajas y bajas masivas. |
| **Usuarios** | `vistas/usuarios.php` + `vistas/usuarios/` + `procesos/usuarios/` | Solo Super Admin (ID 1): ver, agregar y editar usuarios; eliminar usuarios (excepto el propio y excepto el Super Administrador `ID = 1`, que está protegido). |
| **Respaldo BD** | `vistas/backup.php` + `procesos/backup/respaldar_bd.php` | Solo Super Admin: descargar copia de seguridad `.sql` de la base `hotel_equipos`. |

---

## 7. Flujo típico (paso a paso)

1. **Alta de equipo:** Menú **Administrar Equipos → Equipos** → Agregar: código único, descripción, marca, modelo, serie, departamento, ubicación, valor, moneda, foto. Estado por defecto Activo (o En Mantenimiento/En Reparación si aplica).
2. **Asignación:** El equipo queda en un departamento. Si se da de baja más adelante, su estado pasa a **Dado de Baja**.
3. **Baja individual:** **Bajas → Registrar Bajas** → buscar por código → completar motivo, valor residual, destino, fotos, firmas (técnico, gerente), fecha adquisición si aplica → Registrar. Desde la tabla de bajas se genera el **Informe Técnico** en PDF.
4. **Baja masiva:** Cada equipo debe estar ya dado de baja individual. **Bajas → Bajas Masivas** → crear baja masiva, agregar equipos con observación por cada uno → generar PDF “Formato de Baja”.
5. **Resguardo:** **Administrar Equipos → Resguardos** → **Generar Resguardo (un equipo)** o **Resguardo múltiple** (varios equipos, misma factura). Código(s), área, responsable, puesto (cargo), factura, fecha; Tipo se toma de la descripción del equipo. Generar/descargar PDF. En historial, los múltiples se ven en una fila; Imprimir abre el PDF correcto.
6. **Alta de nuevo:** Ir a **Altas** (`vistas/altas.php`) → elegir equipo dado de baja → dar de alta (estado vuelve a Activo y se registra en `altas_equipos`).
7. **Reportes:** Desde **Inicio** (reportes ejecutivos) o accediendo a `vistas/reportes.php` se elige el tipo de reporte (equipos, por departamento, altas, bajas individuales, bajas masivas); cada uno abre/descarga el PDF correspondiente. Los informes técnicos se generan desde las tablas de Registrar Bajas y Bajas Masivas.

---

## 8. Usuarios y permisos

- **Cualquier usuario autenticado** puede: registrar y editar equipos, departamentos (según implementación), resguardos, bajas individuales y masivas, altas; ver y generar reportes; eliminar resguardos.
- **Super Administrador (primer usuario registrado, `id_usuario = 1`):** además puede eliminar equipos, departamentos, bajas individuales, bajas masivas; acceder a **Usuarios** y **Respaldo BD**. El propio usuario `ID = 1` no se puede eliminar por seguridad. En el menú se muestra como “Super Administrador”; el resto de usuarios pueden ver el nombre de su hotel (multi-hotel).

La autenticación y el control de sesión están en `seguridad.php`; las vistas protegidas llaman a `requerirAutenticacion()` al inicio.

---

## 9. Tecnología (resumen)

- **Backend:** PHP; MySQL, base `hotel_equipos`.
- **Frontend:** HTML, CSS, Bootstrap, jQuery; tema Villa Group (oscuro, acentos dorados); Alertify para mensajes.
- **PDF:** DOMPDF (`librerias/dompdf/`).
- **PWA (opcional):** `manifest.json`, `sw.js`, `js/pwa-install.js` para instalación en móvil; meta theme-color y apple-mobile-web-app para aspecto app.
- **Seguridad:** sesión con tiempo máximo, inactividad, regeneración de ID; headers X-Frame-Options, X-Content-Type-Options, etc.; consultas preparadas en validación de usuario.

---

## 10. Resumen

El sistema **centraliza el inventario de equipos del hotel**: qué equipos hay, en qué departamento están, quién los tiene (resguardos individuales o múltiples), cuándo se dieron de baja o de alta, y genera los **reportes e informes técnicos en PDF** necesarios para control interno y auditoría. La configuración de BD y ruta del proyecto se centraliza en `config.php`; la estructura de datos se instala con `bd/hotel_equipos.sql`. Multi-hotel está soportado por la tabla `hoteles` y los campos `id_hotel`. Resguardos múltiples se identifican con `id_grupo_resguardo` y en el historial se muestran agrupados; el campo Tipo en resguardos se obtiene de la descripción del equipo al darlo de alta.

**Documentación adicional:** **`README.md`** (índice maestro). **INICIAR-SIN-XAMPP.md** (PHP + MariaDB sin XAMPP). **DESPLIEGUE_Y_ACCESO_REMOTO.md** (ngrok, red local, móvil, inicio automático): si usas ngrok **sin** XAMPP, el servidor PHP suele ir en el puerto **8000** → `ngrok http 8000` (no uses el puerto 3000). Con XAMPP en el 80 → `ngrok http 80`. **PWA.md** para instalación como app.
