# Rediseño del front — Sistema de Gestión de Equipos · The Villa Group

Cómo usar este archivo:
1. Dirección elegida: **B) "Costa Clara"** (ya incluida en el PROMPT MAESTRO).
2. Pega el PROMPT MAESTRO en Stitch (o Claude) para generar el "shell" (menú + barra superior) y el dashboard.
3. Después pega, uno por uno, los prompts de cada pantalla (sección 3). En Stitch funciona mejor 1 pantalla por prompt, en el mismo proyecto, para que respete el estilo.
4. Los datos de ejemplo son ficticios a propósito: no pegues nombres reales de usuarios ni montos reales.

---

## 1. Direcciones visuales (elige una)

### A) "Noche de Resort" — evolución del estilo actual
Tema oscuro elegante. Fondo carbón #121212, superficies #1E1E1E y #262626, acento dorado #C29A4B, secundario terracota #A0522D, texto #F5F5F5 / #B3B3B3. Tipografía: "Playfair Display" solo en títulos grandes, "Inter" para todo lo demás. Bordes redondeados 12px, sombras suaves, líneas doradas finas como detalle. Sensación: lujo, hotel de playa al atardecer. Mantiene la identidad que los usuarios ya conocen.

### B) "Costa Clara" — claro, luminoso y muy legible (RECOMENDADA para uso diario de oficina)
Tema claro con modo oscuro opcional. Fondo arena muy claro #FAF7F2, superficies blancas, acento dorado #B8893A, secundario azul mar de Cortés #1F6F8B, éxito #2E7D5B, alerta #C47F17, peligro #B3412F, texto #1F1F1F / #6B6B6B. Tipografía "Inter" (o "Manrope"); títulos semibold. Esquinas 10px, sombras mínimas, mucho espacio en blanco. Sensación: Los Cabos de día, ordenado y profesional. Ideal para tablas grandes.

### C) "Operativo Pro" — estilo SaaS moderno (tipo Linear / Notion)
Neutro gris-pizarra con modo claro y oscuro. La marca aparece solo como acento dorado #C29A4B en botones primarios, estados activos y el logo. Tipografía "Inter", tamaños compactos, alta densidad de información, iconos de línea (Lucide). Sensación: herramienta de trabajo rápida y seria; prioriza productividad sobre ambientación.

---

## 2. PROMPT MAESTRO (pegar primero)

```
Diseña una aplicación web interna (responsive: escritorio y móvil, también instalable como PWA) llamada "Sistema de Gestión de Equipos" para The Villa Group, una cadena de hoteles en Los Cabos, México. Toda la interfaz debe estar en ESPAÑOL.

PROPÓSITO: controlar el inventario de equipos (computadoras, impresoras, electrónicos, mobiliario) de 4 hoteles: Villa del Arco, Villa del Palmar, Villa la Estancia y Villa la Valencia. Registra equipos, resguardos (quién tiene cada equipo bajo su responsabilidad, con PDF firmado), solicitudes de alta, bajas individuales y masivas, y genera reportes PDF/Excel. Incluye un módulo de control de toallas para el personal de albercas.

ESTILO VISUAL — "Costa Clara":
Tema claro con modo oscuro opcional. Fondo arena muy claro #FAF7F2, superficies blancas, acento dorado #B8893A (botones primarios, elemento activo del menú), secundario azul mar de Cortés #1F6F8B (enlaces, gráficas, información), éxito #2E7D5B, alerta #C47F17, peligro #B3412F, texto principal #1F1F1F y secundario #6B6B6B, bordes #E8E2D8. Tipografía "Inter" (títulos semibold, cuerpo regular 14px, tablas 13px). Esquinas redondeadas de 10px, sombras mínimas, mucho espacio en blanco, iconos de línea. Sensación: Los Cabos de día — ordenado, luminoso y profesional, pensado para trabajar muchas horas con tablas grandes. En modo oscuro: fondo #141414, superficies #1E1E1E, mismo dorado como acento.

ESTRUCTURA GLOBAL (shell):
- Barra lateral izquierda colapsable con logo (un sol estilizado blanco) y secciones:
  · Inicio
  · INVENTARIO: Equipos, Resguardos, Solicitudes de alta
  · BAJAS: Baja individual, Bajas masivas
  · CATÁLOGOS: Departamentos, Áreas, Fases
  · Reportes
  · ADMINISTRACIÓN (solo Super Administrador): Usuarios, Respaldo de base de datos
- Barra superior: selector global de hotel (menú desplegable con los 4 hoteles + "Todos los hoteles"), buscador global por código o número de serie, y menú de usuario con nombre, rol ("Super Administrador" o nombre del hotel) y "Cerrar sesión".
- Migas de pan (breadcrumbs) debajo de la barra superior.
- Notificaciones tipo toast para éxito/error, modales de confirmación para eliminar.
- En móvil: la barra lateral se convierte en menú inferior o menú hamburguesa.

COMPONENTES CLAVE:
- Chips de estado de equipo: Activo (verde), En mantenimiento (ámbar), En reparación (naranja), Dado de baja (rojo/gris).
- Tablas con: búsqueda, filtros, paginación, columnas ocultables, acciones por fila en menú "⋯", estado vacío ilustrado.
- Formularios en tarjetas con secciones, validación en línea y campos obligatorios marcados.
- Subida de archivos con arrastrar y soltar y vista previa (fotos de equipos, PDF de factura y resguardo firmado).

PRIMERA PANTALLA A GENERAR: Dashboard (Inicio) del administrador:
- Saludo "Buenos días, [Nombre]" y hotel seleccionado.
- 4 tarjetas KPI: Equipos activos (863), En mantenimiento (31), Dados de baja (10), Total de equipos (904).
- Gráfica de equipos por departamento (top 8) y gráfica de dona por estado.
- Lista "Actividad reciente" (altas, resguardos, bajas con fecha y usuario).
- Tarjeta "Solicitudes de alta pendientes" con contador y horas transcurridas.
- Accesos rápidos: "Registrar equipo", "Nuevo resguardo", "Registrar baja", "Generar reporte".
- Sección "Reportes ejecutivos": Reporte de todas las bajas (PDF), Reporte de equipos activos (PDF).
```

---

## 3. Prompts por pantalla (pegar uno por uno, después del maestro)

### 3.1 Inicio de sesión
```
Pantalla de inicio de sesión con el mismo estilo. Fondo ilustrado/animado de mar y sol al atardecer (Los Cabos, el Arco). Tarjeta centrada con logo del sol, título "The Villa Group", subtítulo "Sistema de Gestión de Equipos", campos Usuario y Contraseña (con mostrar/ocultar), botón "Iniciar sesión", y mensajes de estado: "Sesión cerrada correctamente", "Tu sesión ha expirado, inicia sesión nuevamente", "Usuario o contraseña incorrectos". Versión móvil incluida.
```

### 3.2 Equipos — listado (pantalla más importante)
```
Pantalla "Equipos" con encabezado, contador "904 equipos" y botones "Registrar equipo" (primario), "Importar CSV" y "Exportar selección a Excel".
Filtros en fila: hotel, departamento (98 opciones, con búsqueda), estado, moneda (USD/MXN/EUR), rango de fechas de ingreso, y buscador por código/serie/descripción.
Tabla con selección múltiple (checkbox) y columnas: miniatura de foto, Código (ej. VDP-20260520-482), Descripción, Departamento, Ubicación física, Usuario responsable, Marca / Modelo, Nº de serie, Estado (chip), Valor + moneda, Fecha de ingreso, iconos de documentos (resguardo firmado PDF ✓/✗, factura ✓/✗), menú de acciones (Ver, Editar, Generar resguardo, Dar de baja, Eliminar).
Columnas configurables, filas compactas, paginación de 25/50/100. Barra flotante al seleccionar filas: "3 seleccionados — Exportar · Resguardo múltiple · Baja masiva".
Al hacer clic en una fila se abre un PANEL LATERAL de detalle: foto grande, todos los datos, documentos (subir/ver resguardo firmado y factura), historial del equipo (alta, resguardos, cambios, baja).
En móvil la tabla se convierte en tarjetas.
```

### 3.3 Registrar / editar equipo
```
Formulario "Registrar equipo" en una página o modal grande, en 3 secciones:
1) Identificación: Código (opcional, se genera automático), Descripción*, Marca*, Modelo, Número de serie*, Estado* (Activo, En mantenimiento, En reparación).
2) Ubicación y responsable: Departamento* (select con búsqueda), Ubicación física*, Usuario responsable, Hotel.
3) Compra: Costo*, Moneda* (USD, MXN, EUR), Fecha de ingreso*, Nº de factura, archivo de factura (PDF/imagen).
Zona de foto del equipo* con arrastrar y soltar y vista previa. Botones "Cancelar" y "Guardar equipo".
Pestaña o sección aparte "Importación masiva": subir archivo CSV, descargar plantilla, vista previa de filas válidas/erróneas antes de importar.
```

### 3.4 Resguardos — historial
```
Pantalla "Resguardos" (documento donde un empleado firma que recibe un equipo bajo su responsabilidad). Botones "Nuevo resguardo" y "Resguardo múltiple".
Tabla: Tipo (Individual / Múltiple con contador "Múltiple · 5 equipos"), Folio, Código(s) de equipo, Área de destino, Usuario responsable, Fecha, Factura, Modelo, Serie, Tipo de equipo, estado del PDF firmado (Pendiente de firma / Firmado), acciones (Imprimir PDF, Subir firmado, Eliminar).
Filtros por hotel, área, fecha y estado de firma.
```

### 3.5 Nuevo resguardo (individual y múltiple)
```
Formulario "Nuevo resguardo" con buscador grande "Escanea o escribe el código / número de serie" que autocompleta los datos del equipo (Marca, Modelo, Serie, Tipo, Factura) en una tarjeta de vista previa.
Campos: Área de destino, Usuario responsable (nombre completo), Puesto, Fecha del resguardo (hoy por defecto).
Variante "Resguardo múltiple": Número de factura*, Área*, Responsable*, Puesto, Fecha*, y una lista tipo carrito donde se agregan varios equipos por código, con opción de quitar cada uno.
A la derecha, vista previa del documento PDF de resguardo con logo, datos y espacios de firma. Botón "Generar PDF".
```

### 3.6 Solicitud de alta de equipos
```
Formulario "Solicitud de alta de equipos" tipo documento: encabezado con Solicitud #, Fecha, Departamento solicitante, Cantidad de equipos.
Campo "Agregar equipo por código" y tabla editable con columnas: Descripción, Marca, Modelo, Serie, Características, Color, Responsable, Centro de costos, Fase, Área, Piso, Tipo de habitación, Departamento.
Campos Clasificación 1 y 2. Botones "Guardar solicitud" y "Generar PDF". Indicador de estado: Pendiente / Atendida, con horas transcurridas.
```

### 3.7 Baja individual
```
Asistente por pasos "Registrar baja de equipo":
Paso 1 — Buscar equipo por código; mostrar tarjeta del equipo (foto, descripción, marca/modelo, serie, departamento, fecha de adquisición).
Paso 2 — Motivo* (Equipo obsoleto, Falla irreparable, Reemplazo por nuevo, Venta, Donación, Robo, Otro), Valor residual*, Destino* (Venta, Donación, Chatarra, Almacén, Otro), Observaciones.
Paso 3 — Evidencias: Foto del equipo y Foto de la etiqueta (arrastrar y soltar).
Paso 4 — Autorizaciones: Técnico que revisó*, VoBo Gerente de área*. Resumen y botón "Confirmar baja" + "Generar informe técnico PDF".
Debajo, historial de bajas individuales en tabla: Nº, Fecha, Código, Descripción, Marca/Modelo, Serie, Departamento, Motivo, Valor residual, Destino, miniaturas de fotos, Informe PDF, Acciones.
```

### 3.8 Bajas masivas
```
Pantalla "Bajas masivas" en dos columnas.
Izquierda: buscador por código o por número de serie, botones rápidos ("Agregar todos los de un departamento"), resultados para agregar.
Derecha: "Equipos a dar de baja" tipo carrito con columnas Código, Descripción, Valor, Observación por equipo (falla/motivo), Estado y quitar; total de equipos y valor total.
Datos generales: Hotel y área, Motivo de la baja masiva, Destino de los equipos. Botón "Registrar baja masiva" y "Generar formato PDF".
Pestaña "Historial de bajas masivas" con Folio, Fecha, Departamento, Total de equipos, Valor residual, Destino, PDF.
```

### 3.9 Catálogos: Departamentos, Áreas y Fases (una plantilla para los tres)
```
Pantalla de catálogo reutilizable "Departamentos" (también se usará para "Áreas" y "Fases"). Selector de hotel, buscador, botón "Nuevo departamento".
Tabla o cuadrícula de tarjetas: Nombre, Descripción, Número de equipos asignados, Fecha de creación, acciones Editar/Eliminar (con aviso si tiene equipos asignados).
Modal simple para crear/editar con Nombre* y Descripción.
```

### 3.10 Centro de reportes
```
Pantalla "Reportes" con tarjetas de reporte, cada una con icono, descripción, filtros (hotel, departamento, rango de fechas) y botones "PDF" / "Excel":
Reporte de equipos activos, Reporte por departamento, Reporte de altas, Reporte de bajas individuales, Reporte de bajas masivas, Reporte de todas las bajas, Informe de equipos seleccionados.
```

### 3.11 Usuarios (solo Super Administrador)
```
Pantalla "Usuarios": tabla con avatar de iniciales, Nombre, Apellido, Usuario, Rol (chip: Administrador / Toallero), Hotel, acciones Editar / Eliminar (el Super Administrador aparece protegido con candado, no se puede eliminar).
Modal "Nuevo usuario": Nombre, Apellido, Usuario, Contraseña (con indicador de seguridad), Hotel/Villa, Rol. Modal "Editar usuario" con "Nueva contraseña (opcional)".
```

### 3.12 Respaldo de base de datos (solo Super Administrador)
```
Pantalla "Respaldo de base de datos": tarjeta con explicación, fecha del último respaldo, tamaño aproximado, botón grande "Descargar respaldo ahora (.sql)", y recomendaciones de dónde guardarlo. Mensaje de advertencia sobre información sensible.
```

### 3.13 Toallas — vista del toallero (MÓVIL PRIMERO)
```
Vista para el rol "Toallero" (personal de alberca), diseñada primero para celular con botones grandes.
Formulario rápido "Entregar toallas": Tipo de toalla, Número de habitación, Nombre del huésped, Cantidad (botones − / +), Observaciones; botón grande "Registrar entrega".
Lista "Toallas pendientes de devolución" como tarjetas: Habitación, Huésped, Entregadas, Regresadas, Pendientes, Hora de registro y tiempo transcurrido (con color si pasa de X horas), Estatus; botón "Registrar devolución".
```

### 3.14 Historial de toallas
```
Pantalla "Historial de toallas": filtros por fecha, habitación y estatus; tabla Fecha, Habitación, Huésped, Cantidad, Estatus de cierre, Usuario, Observaciones, Acción.
Sección de configuración: "Borrar registros más antiguos que" (select de periodo) y "Contraseña de toalleros", con confirmación.
```
