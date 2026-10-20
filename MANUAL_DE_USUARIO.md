# 📖 MANUAL DE USUARIO
## Sistema de Gestión de Equipos de Hotel
### Versión actualizada — abril 2026

---

## 📚 **ÍNDICE**

1. [Introducción al Sistema](#introducción-al-sistema)
2. [Primera Instalación](#primera-instalación)
3. [Acceso al Sistema](#acceso-al-sistema)
4. [Panel Principal](#panel-principal)
5. [Gestión de Equipos](#gestión-de-equipos)
6. [Gestión de Departamentos](#gestión-de-departamentos)
7. [Gestión de Usuarios](#gestión-de-usuarios)
8. [Bajas Individuales](#bajas-individuales)
9. [Bajas Masivas](#bajas-masivas)
10. [Procesos de Altas](#procesos-de-altas)
11. [Solicitud para dar de Alta Equipos](#solicitud-para-dar-de-alta-equipos)
12. [Resguardos](#resguardos)
13. [Sistema de Reportes](#sistema-de-reportes)
14. [Configuración del Sistema](#configuración-del-sistema)
15. [Preguntas Frecuentes](#preguntas-frecuentes)

---

## 📂 Documentación del proyecto (defensa y presentación)

Para exponer el sistema de forma ordenada (qué es, cómo se instala, arquitectura y despliegue), usa el índice de **`README.md`** en la raíz del proyecto. Allí están enlazados, entre otros:

- **GUIA_SISTEMA.md** — propósito de negocio de cada módulo  
- **MANUAL_TECNICO_COMPLETO.md** — arquitectura, BD y seguridad  
- **REPORTE_MAPA_ARCHIVOS_Y_CODIGO.md** — mapa de archivos y código  
- **DESPLIEGUE_Y_ACCESO_REMOTO.md** — ngrok, acceso remoto e inicio automático  
- **PWA.md** — instalación como aplicación en el dispositivo  
- **INICIAR-SIN-XAMPP.md** — arranque solo con PHP y MariaDB  

*Abril 2026.*

---

## 🏨 **INTRODUCCIÓN AL SISTEMA**

### **¿Qué es el Sistema de Gestión de Equipos de Hotel?**

El Sistema de Gestión de Equipos de Hotel es una aplicación web diseñada para administrar de manera eficiente todos los equipos, herramientas y activos del hotel. Permite registrar, controlar, dar de baja y generar reportes de todos los equipos del establecimiento.

### **Características Principales:**
- ✅ **Registro completo de equipos** con fotos
- ✅ **Gestión de departamentos** del hotel
- ✅ **Control de bajas** individuales y masivas
- ✅ **Sistema de reportes** en PDF
- ✅ **Interfaz intuitiva** y fácil de usar
- ✅ **Búsqueda avanzada** de equipos
- ✅ **Control de usuarios** y permisos
- ✅ **Sistema de eliminación** para administradores

### **Beneficios del Sistema:**
- 📊 **Control total** del inventario de equipos
- 📸 **Registro visual** con fotografías
- 📋 **Reportes detallados** para auditorías
- ⚡ **Procesos automatizados** de bajas y altas
- 🔍 **Búsqueda rápida** de equipos
- 📈 **Estadísticas** en tiempo real

---

## 🚀 **PRIMERA INSTALACIÓN**

### **Requisitos del Sistema:**
- **Servidor Web:** Apache 2.4+ (XAMPP/WAMP) **o** PHP 7.4+ en modo servidor (`php -S localhost:8000`)
- **PHP:** Versión 7.4 o superior (con extensión mysqli)
- **Base de Datos:** MySQL 5.7 o MariaDB 10.3+
- **Navegador:** Chrome, Firefox, Safari o Edge (versiones recientes)

### **Pasos de Instalación:**

#### **Opción A – Con XAMPP**
1. Instalar XAMPP y colocar el proyecto en `htdocs`.
2. Iniciar Apache y MySQL/MariaDB.
3. En phpMyAdmin, importar **`bd/hotel_equipos.sql`**.
4. Editar **`config.php`** (DB_HOST, DB_USER, DB_PASS, DB_NAME, RUTA_BASE).
5. Abrir la URL del sistema; si no hay usuarios, el sistema te redirigirá automáticamente a **registro.php** para crear al **super usuario** (solo la primera vez).

#### **Opción B – Sin XAMPP (solo PHP + MariaDB)**
1. Tener instalados **PHP** y **MariaDB**.
2. Crear la base `hotel_equipos` e importar **`bd/hotel_equipos.sql`**.
3. Editar **`config.php`** en la carpeta del proyecto: DB_HOST=`localhost`, DB_USER, **DB_PASS** (contraseña de MariaDB; si está vacía aparece "Access denied (using password: NO)").
4. Si MariaDB da error de autenticación, ver la sección correspondiente en **INICIAR-SIN-XAMPP.md**.
5. En la carpeta del proyecto ejecutar: `php -S localhost:8000`.
6. Abrir **http://localhost:8000**; si no hay usuarios, serás redirigido a **registro.php** para crear al super usuario.

Ver guía completa en **INICIAR-SIN-XAMPP.md**.

#### **Permisos y primer usuario**
- Dar permisos de escritura a la carpeta `archivos/` (fotos de equipos y bajas).
- El **primer usuario** se registra en la pantalla "Registro del super usuario": se usa el campo **Usuario** (nombre de usuario, no correo) y contraseña; no se elige hotel porque el super administrador gestiona todos los hoteles. Ese usuario (ID = 1) es el **Super Administrador** (eliminaciones, usuarios, respaldo BD, acceso a todos los hoteles).

---

## 🔐 **ACCESO AL SISTEMA**

### **Inicio de Sesión:**
1. Abrir el navegador web
2. Ir a la dirección del sistema (ej: `http://localhost/ventasAlmacen22`)
3. Verás la pantalla de login con el logo de Villa Group
4. Ingresar email y contraseña en los campos estilizados
5. Hacer clic en **"Iniciar Sesión"**
6. El sistema mostrará efectos visuales durante el proceso de autenticación

### **Registro del Super Usuario (solo primera vez):**
- Si no existe ningún usuario en el sistema, al entrar a la aplicación serás redirigido a **registro.php** ("Registro del super usuario").
- Completar: **Nombre**, **Apellido**, **Usuario** (nombre de usuario, no correo; debe ser único), **Contraseña**.
- No se elige hotel: el super usuario administra todos los hoteles.
- Tras registrarse, podrás iniciar sesión con ese usuario y contraseña.

### **Registro de otros usuarios (desde el panel):**
- Los usuarios adicionales los crea el administrador desde **Administrar Usuarios** en el menú (no desde la pantalla de login).

### **Características del Sistema de Login:**
- ✅ **Diseño Moderno:** Interfaz con tema Villa Group
- ✅ **Efectos Visuales:** Animaciones y transiciones suaves
- ✅ **Validación en Tiempo Real:** Verificación de campos
- ✅ **Feedback Visual:** Indicadores de éxito/error
- ✅ **Responsive:** Funciona en móviles y tablets

---

## 🏠 **PANEL PRINCIPAL**

### **Menú de Navegación:**
El sistema cuenta con un menú superior fijo con el tema Villa Group que incluye:

#### **🏠 Inicio**
- **Dashboard Principal:** Vista general del sistema
- **Estadísticas:** Resumen de equipos y actividades

#### **📦 Administrar Equipos**
- **Ver Equipos:** Lista completa de equipos registrados
- **Agregar Equipo:** Formulario para registrar nuevos equipos
- **Bajas Individuales:** Proceso de baja de equipos uno por uno
- **Bajas Masivas:** Proceso de baja de múltiples equipos

#### **🏢 Administrar Departamentos**
- **Ver Departamentos:** Lista de departamentos del hotel
- **Agregar Departamento:** Crear nuevos departamentos

#### **👥 Administrar Usuarios** (Solo Administrador)
- **Ver Usuarios:** Lista de usuarios del sistema
- **Agregar Usuario:** Registrar nuevos usuarios
- **Editar Usuario:** Modificar información de usuarios existentes
- **Eliminar Usuario:** Disponible solo para cuentas distintas al Super Administrador

#### **👤 Información del Usuario**
- **Nombre Completo:** Se muestra en la barra superior (ej: "Usuario: Juan Pérez")
- **Menú Desplegable:** Opción para cerrar sesión
- **Actualización Automática:** El nombre se actualiza cuando modificas tu perfil

#### **📄 Solicitud para dar de Alta Equipos**
- **Nueva solicitud:** Formulario para generar el documento "Solicitud para dar de alta equipos de operación". Permite agregar equipos por código primario, completar datos de ubicación (fase, área, piso, tipo habitación, departamento), Clasificación 1 y 2, y al guardar se genera el PDF listo para imprimir o firmar.

#### **📄 Resguardos**
- **Generar Resguardo:** Crear documento de resguardo por código de equipo
- **Ver Resguardos Generados:** Historial con opción de imprimir y eliminar (en caso de registro erróneo)

#### **📊 Reportes**
- **Reporte General:** Vista general de todos los equipos
- **Reporte por Departamento:** Equipos agrupados por departamento
- **Reporte de Altas:** Equipos dados de alta
- **Reporte de Bajas Individuales:** Equipos dados de baja individualmente
- **Reporte de Bajas Masivas:** Equipos dados de baja masivamente

### **Características de la Navegación:**
- ✅ **Tema Villa Group:** Colores dorados y oscuros consistentes
- ✅ **Logo Prominente:** Logo de Villa Group en la esquina superior izquierda
- ✅ **Información del Usuario:** Nombre completo visible en la barra superior
- ✅ **Menús Desplegables:** Navegación organizada por categorías
- ✅ **Responsive:** Se adapta a diferentes tamaños de pantalla

---

## 📦 **GESTIÓN DE EQUIPOS**

### **Ver Equipos Registrados**

#### **Funcionalidades de la Tabla:**
- **Búsqueda por código:** Campo de búsqueda en la parte superior
- **Información completa:** Código, descripción, marca, modelo, departamento, estado, valor
- **Imágenes:** Visualización de fotos de equipos
- **Edición:** Botón para modificar información del equipo
- **Eliminación:** Solo disponible para el administrador (usuario ID = 1)

#### **Estados de Equipos:**
- **🟢 Activo:** Equipo en funcionamiento normal
- **🟡 En Mantenimiento:** Equipo en proceso de mantenimiento
- **🔵 En Reparación:** Equipo siendo reparado
- **🔴 Dado de Baja:** Equipo retirado del servicio

### **Agregar Nuevo Equipo**

#### **Campos Obligatorios:**
- **Código del Equipo:** Identificador único (ej: 39205)
- **Descripción:** Descripción detallada del equipo
- **Marca:** Marca del equipo
- **Modelo:** Modelo específico
- **Número de Serie:** Número de serie del equipo
- **Departamento:** Área administrativa a la que pertenece el equipo
- **Ubicación Física:** Lugar exacto donde está colocado (ej: mostrador recepción, cuarto de rack, habitación)
- **Fecha de Ingreso:** Fecha de adquisición
- **Valor de Adquisición:** Costo del equipo
- **Tipo de Moneda:** USD, MXN o EUR

#### **Campos Opcionales:**
- **Foto del Equipo:** Imagen del equipo (JPG, PNG, GIF - máximo 2MB)

#### **Proceso de Registro:**
1. Completar todos los campos obligatorios
2. Subir foto del equipo (opcional)
3. Hacer clic en **"Registrar Equipo"**
4. El sistema asignará automáticamente el estado "Activo"

### **Carga Masiva de Equipos por CSV (Excel)**

Desde **Administrar Equipos → Agregar Equipo**, en el panel **Carga Masiva de Equipos (CSV de Excel)**:

1. Preparar un archivo CSV (desde Excel) con encabezados.
2. Columnas recomendadas:
   - `codigo`, `descripcion`, `marca`, `modelo`, `numero_serie`, `estado`, `departamento`, `ubicacion_fisica`, `fecha_ingreso`, `valor`, `tipo_moneda`
3. Seleccionar el archivo en **Archivo CSV**.
4. Hacer clic en **Importar CSV**.
5. Revisar el resultado:
   - Si todo salió bien, verás confirmación de importación completa.
   - Si hubo problemas en algunas filas, verás una importación parcial con el detalle de errores por fila.

**Importante:** la columna `departamento` se usa para asignar el departamento del equipo y `ubicacion_fisica` para guardar el lugar exacto donde está instalado.

### **Editar Equipo Existente**

#### **Funcionalidades:**
- **Modificar información:** Todos los campos son editables
- **Cambiar foto:** Puede reemplazar la imagen actual; la foto actual se muestra en el modal
- **Validación:** El sistema verifica que el código no esté duplicado
- **Actualización:** Los cambios se guardan inmediatamente

---

## 🏢 **GESTIÓN DE DEPARTAMENTOS**

### **Ver Departamentos**

La tabla muestra:
- **Nº:** Número de fila (siempre secuencial: 1, 2, 3…)
- **Nombre:** Nombre del departamento
- **Descripción:** Descripción detallada
- **Responsable:** Persona a cargo
- **Fecha de Creación:** Cuándo se creó
- **Acciones:** Editar o eliminar (solo administrador)

### **Agregar Departamento**

#### **Campos Requeridos:**
- **Nombre:** Nombre del departamento (ej: "Recepción", "Cocina")
- **Descripción:** Descripción del departamento
- **Responsable:** Nombre del responsable

#### **Creación de departamentos:**
El sistema no incluye departamentos precargados. Todos los departamentos (p. ej. Recepción, Cocina, Mantenimiento, Administración, Sistemas) los debe crear el usuario desde **Agregar Departamento**.

---

## 👥 **GESTIÓN DE USUARIOS**

### **Ver Usuarios Registrados**

La tabla muestra:
- **ID:** Identificador único del usuario
- **Nombre:** Nombre completo del usuario
- **Apellido:** Apellido del usuario
- **Usuario:** Dirección de correo electrónico
- **Acciones:** Editar o eliminar (solo administrador)

### **Editar Usuario Existente**

#### **Funcionalidades de Edición:**
- **Modificar Nombre:** Cambiar el nombre del usuario
- **Modificar Apellido:** Cambiar el apellido del usuario
- **Modificar Email:** Cambiar la dirección de correo
- **Actualización Automática:** Si editas tu propio perfil, la navegación se actualiza automáticamente
- **Recarga de Página:** El sistema recarga la página para mostrar los cambios

#### **Proceso de Edición:**
1. Hacer clic en el botón **"Editar"** (lápiz dorado) en la tabla de usuarios
2. Se abrirá un modal con los datos actuales del usuario
3. Modificar los campos necesarios
4. Hacer clic en **"Actualizar Usuario"**
5. El sistema mostrará confirmación de éxito
6. Si editaste tu propio perfil, la página se recargará automáticamente

### **Permisos Especiales:**
- **Usuario Administrador (ID = 1):** Primer usuario registrado
  - Puede eliminar registros de bajas individuales
  - Puede eliminar registros de bajas masivas
  - Puede eliminar usuarios (excepto al propio super administrador, que está protegido)
  - Puede eliminar departamentos
  - Puede editar cualquier usuario del sistema

### **Características del Sistema de Usuarios:**
- ✅ **Actualización de Sesión:** Los cambios se reflejan inmediatamente en la navegación
- ✅ **Nombre Completo:** Se muestra en la barra superior del sistema
- ✅ **Validación:** El sistema verifica que el email sea único
- ✅ **Feedback Visual:** Confirmaciones de éxito/error
- ✅ **Recarga Automática:** Para el usuario actual cuando se modifica su perfil

---

## 📉 **BAJAS INDIVIDUALES**

### **Proceso de Baja Individual**

#### **Paso 1: Buscar Equipo**
- **Búsqueda por código:** Escribir el código del equipo
- **Búsqueda automática:** El sistema busca mientras escribes
- **Selección:** Hacer clic en el equipo de la lista de sugerencias

#### **Paso 2: Completar Información**
- **Motivo de Baja:** Razón por la cual se da de baja
- **Valor Residual:** Valor actual del equipo
- **Destino:** Qué se hará con el equipo (Chatarra, Donación, etc.)
- **Fecha de Adquisición:** Opcional; marcar **N/A** si el equipo es muy antiguo o no se conoce la fecha
- **Observaciones:** Comentarios adicionales

#### **Paso 3: Documentación Fotográfica**
- **Foto del Equipo:** Imagen del estado actual
- **Foto de la Etiqueta:** Imagen de la etiqueta del equipo

#### **Paso 4: Responsables**
- **Técnico que Revisó:** Nombre del técnico
- **VoBo Gerente de Área:** Aprobación del gerente

#### **Paso 5: Confirmación**
- Hacer clic en **"Registrar Baja"**
- El sistema cambiará automáticamente el estado del equipo a "Dado de Baja"

### **Ver Historial de Bajas Individuales**

#### **Funcionalidades:**
- **Búsqueda:** Filtrar por código, descripción o marca
- **Información completa:** Todos los datos de la baja
- **Imágenes:** Visualización de fotos
- **Generar Reporte:** Crear PDF del registro de baja
- **Eliminar:** Solo disponible para el administrador

---

## 📉 **BAJAS MASIVAS**

### **Proceso de Baja Masiva**

#### **Paso 1: Buscar Equipos**
- **Búsqueda por código:** Escribir código del equipo
- **Búsqueda por descripción:** Escribir parte de la descripción
- **Solo equipos dados de baja:** El sistema muestra únicamente equipos ya dados de baja individualmente

#### **Paso 2: Seleccionar Equipos y Observaciones**
- **Agregar equipos:** Hacer clic en "Incluir en Reporte"
- **Lista de seleccionados:** Tabla con código, descripción, valor, **Observación (falla/motivo por equipo)**, estado y acción
- **Observación por equipo:** En cada fila hay un campo para escribir la falla o motivo específico de ese equipo (cada equipo puede tener una observación distinta)
- **Contador:** Número de equipos seleccionados y valor total

#### **Paso 3: Información General**
- **Hotel y Área:** Villa del Arco, Villa la Estancia o Villa del Palmar
- **Motivo de la Baja Masiva:** Renovación, Cierre de Departamento, Actualización Tecnológica, etc.
- **Destino de los Equipos:** Chatarra, Almacén, Donación, etc.

#### **Paso 4: Confirmación**
- Hacer clic en **"Registrar Baja Masiva"**
- El sistema creará un registro consolidado; en el PDF cada equipo mostrará su propia observación

### **Ver Historial de Bajas Masivas**

#### **Información Mostrada:**
- **ID:** Identificador de la baja masiva
- **Fecha:** Cuándo se registró
- **Usuario:** Quién la registró
- **Motivo:** Razón de la baja masiva
- **Total Equipos:** Cantidad de equipos incluidos
- **Valor Residual:** Valor total
- **Destino:** Destino de los equipos
- **Observaciones:** Comentarios
- **Generar Reporte:** Crear PDF consolidado
- **Eliminar:** Solo disponible para el administrador

---

## 📈 **PROCESOS DE ALTAS**

### **Registrar Alta de Equipo**

#### **Campos Requeridos:**
- **Equipo:** Seleccionar de la lista de equipos dados de baja
- **Motivo de Alta:** Razón por la cual se da de alta nuevamente
- **Observaciones:** Comentarios adicionales

#### **Proceso:**
1. Seleccionar el equipo de la lista
2. Completar motivo y observaciones
3. Hacer clic en **"Registrar Alta"**
4. El sistema cambiará el estado del equipo a "Activo"

### **Ver Historial de Altas**

La tabla muestra:
- **ID:** Identificador de la alta
- **Fecha:** Cuándo se registró
- **Código:** Código del equipo
- **Equipo:** Descripción del equipo
- **Marca/Modelo:** Información del equipo
- **Departamento:** Departamento asignado
- **Motivo:** Razón de la alta
- **Observaciones:** Comentarios

---

## 📄 **SOLICITUD PARA DAR DE ALTA EQUIPOS**

Este módulo permite generar el documento oficial **"Solicitud para dar de alta equipos de operación"** en PDF, con los datos de la solicitud, equipos, ubicación, clasificaciones y espacios para autorizaciones y firmas.

### **Crear una solicitud y generar el PDF**

1. Ir al menú **Solicitud para dar de Alta Equipos** (o equivalente en tu menú).
2. Completar **Datos de la solicitud:**
   - **Departamento solicitante:** Seleccionar el departamento.
   - **Fecha** y **Solicitud #:** Número o referencia de la solicitud.
3. **Agregar equipos:** En "Agregar equipo por código primario" escribir el **código del equipo** (debe estar dado de alta en el sistema) y hacer clic en **"Buscar y agregar"**. El equipo se añadirá a la tabla con sus datos; puedes completar o ajustar **Características**, **Color**, **Centro de costos**, **Fase**, **Área**, **Piso**, **Tipo habitación** y **Departamento**.
4. Completar **Clasificación 1** y **Clasificación 2** según corresponda.
5. Hacer clic en **"Guardar y generar PDF"**.
6. El sistema guardará la solicitud y abrirá en una nueva ventana el **PDF** listo para descargar o imprimir. El formato incluye logo, tabla de equipos con columnas completas (Descripción, Marca, Modelo, Serie, Características, Color, Responsables, Centro de costos, Fase, Área/Nº de habitación, Piso, Tipo de habitación, Departamento), clasificaciones, sección de Autorizaciones y sección Departamento (Nombre, firma y sello). Los márgenes del PDF están ajustados para que al imprimir el contenido no se corte por los bordes de la hoja.

### **Requisitos**
- Debe haber al menos un equipo en la tabla.
- Departamento solicitante y número de solicitud son obligatorios.

---

## 📄 **RESGUARDOS**

Los resguardos son documentos que acreditan quién tiene el equipo (área destino, responsable, puesto). Puedes generar **resguardos individuales** (un equipo por documento) o **resguardos múltiples** (varios equipos en un solo PDF, misma factura). El campo **Tipo** en el PDF se toma automáticamente de la **descripción del equipo** registrada al darlo de alta.

### **Generar Resguardo Individual (un equipo)**

1. Ir a **Administrar Equipos → Resguardos**.
2. Clic en **"Generar Resguardo (un equipo)"**.
3. Ingresar el **código del equipo** (debe estar dado de alta).
4. Completar **área destino**, **usuario** (responsable), **puesto** (cargo, ej. Supervisor), **fecha del resguardo** y opcionales (factura, modelo, serie). El **Tipo** se precarga con la descripción del equipo y se puede editar.
5. Clic en **"Guardar e imprimir resguardo"**.
6. Se abrirá el PDF del resguardo.

### **Generar Resguardo Múltiple (varios equipos, misma factura)**

1. En **Resguardos**, clic en **"Resguardo múltiple (varios equipos, misma factura)"**.
2. Completar los **datos comunes**: número de factura, área destino, usuario (responsable), puesto, fecha. El **Tipo** en el PDF se toma de la descripción de cada equipo (no se escribe a mano).
3. Agregar cada equipo por **código** (botón "Agregar equipo"); se listan los equipos que se incluirán.
4. Clic en **"Generar resguardos para los equipos listados"**.
5. Se genera **un solo PDF** con una tabla de todos los equipos (una fila por equipo).

### **Ver historial de resguardos**

- **Resguardos individuales:** una fila por equipo; columna **Tipo** = "Individual".
- **Resguardos múltiples:** una fila por grupo con etiqueta **"Múltiple (N equipos)"** y los códigos de todos los equipos.
- **Imprimir:** abre el PDF correspondiente (individual o el PDF único del grupo múltiple).
- **Eliminar:** en un resguardo múltiple elimina todo el grupo; en individual solo ese resguardo.

- Para abrir el historial: clic en **"Ver Resguardos Generados"** dentro de Resguardos. La tabla muestra una columna **Tipo** (Individual o Múltiple (N equipos)), ID, códigos de equipo, área, usuario, fecha, factura, modelo, serie, tipo de equipo, botones **Imprimir** y **Eliminar**.

---

## 📊 **SISTEMA DE REPORTES**

### **Tipos de Reportes Disponibles:**

#### **1. Reporte General**
- **Contenido:** Todos los equipos registrados
- **Formato:** PDF
- **Información:** Código, descripción, marca, modelo, departamento, estado, valor

#### **2. Reporte por Departamento**
- **Contenido:** Equipos agrupados por departamento
- **Formato:** PDF
- **Información:** Lista detallada por cada departamento

#### **3. Reporte de Altas**
- **Contenido:** Equipos dados de alta
- **Formato:** PDF
- **Información:** Historial completo de altas

#### **4. Reporte de Bajas Individuales**
- **Contenido:** Equipos dados de baja individualmente
- **Formato:** PDF
- **Información:** Historial completo de bajas individuales

#### **5. Reporte de Bajas Masivas**
- **Contenido:** Equipos dados de baja masivamente
- **Formato:** PDF
- **Información:** Historial consolidado de bajas masivas

#### **6. Solicitud de Alta de Equipos (PDF)**
- **Origen:** Se genera desde el módulo **Solicitud para dar de Alta Equipos** al hacer clic en "Guardar y generar PDF".
- **Contenido:** Documento "Solicitud para dar de alta equipos de operación" con cabecera, tabla de equipos (DATOS DEL EQUIPO / LOCALIZACION), Clasificación 1 y 2, Autorizaciones y Departamento. Diseñado para impresión con márgenes equilibrados (25 mm izquierda/derecha).

#### **7. Informes Técnicos**
- **Baja Individual:** PDF detallado con dictamen técnico, fotos, fecha de adquisición (o N/A si se marcó así) y firmas
- **Baja Masiva:** PDF tipo "Formato de Baja de Equipo" con logo, tabla de equipos (cada fila con su observación), totales y líneas de firma; preparado para impresión con márgenes adecuados. Se genera desde la tabla de bajas masivas con el botón **"Generar"** (ruta: `reportes/informeTecnicoBajaMasiva.php`)

### **Generar Reportes:**
1. Ir a la sección **"Reportes"**
2. Seleccionar el tipo de reporte deseado
3. Hacer clic en **"Generar Reporte"**
4. El PDF se abrirá en una nueva ventana
5. Descargar o imprimir según necesidad

---

## ⚙️ **CONFIGURACIÓN DEL SISTEMA**

### **Configuración de Archivos:**
- **Tamaño máximo de imagen:** 2MB
- **Formatos permitidos:** JPG, PNG, GIF
- **Ubicación de archivos:** `archivos/equipos/` y `archivos/bajas/`

### **Configuración de Base de Datos:**
- **Host:** localhost
- **Base de datos:** hotel_equipos
- **Charset:** UTF-8

### **Configuración de Seguridad:**
- **Sesiones:** Controladas por PHP
- **Autenticación:** Requerida para todas las operaciones
- **Permisos:** Diferenciados por tipo de usuario

---

## ❓ **PREGUNTAS FRECUENTES**

### **¿Cómo cambio la contraseña de mi usuario?**
Actualmente el sistema no incluye cambio de contraseña. Contacta al administrador.

### **¿Puedo eliminar registros de bajas?**
Solo el usuario administrador (primer usuario registrado) puede eliminar registros.

### **¿Qué pasa si subo una imagen muy grande?**
El sistema rechazará archivos mayores a 2MB. Redimensiona la imagen antes de subirla.

### **¿Cómo busco un equipo específico?**
Usa el campo de búsqueda en la tabla de equipos. Puedes buscar por código, descripción o marca.

### **¿Puedo editar un equipo después de registrarlo?**
Sí, haz clic en el botón "Editar" en la tabla de equipos.

### **¿Qué significa "VoBo"?**
"VoBo" significa "Visto Bueno", es la aprobación del gerente de área.

### **¿Cómo genero un reporte de un equipo específico?**
En el historial de bajas, haz clic en "Generar" para crear el informe técnico.

### **¿Puedo registrar el mismo código de equipo dos veces?**
No, cada código de equipo debe ser único en el sistema.

### **¿Qué pasa si elimino un departamento?**
Solo el administrador puede eliminar departamentos. Los equipos asociados mantendrán la referencia.

### **¿Puedo eliminar un resguardo si lo registré mal?**
Sí. En **Resguardos** → **Ver Resguardos Generados** cada fila tiene un botón **Eliminar**. Al hacer clic se pide confirmación; al aceptar, el resguardo se borra y la tabla se actualiza. Cualquier usuario con sesión iniciada puede eliminar resguardos.

### **¿Cómo sé si soy el administrador?**
El administrador es el primer usuario registrado (ID = 1) y tiene botones de eliminación visibles.

### **¿Se puede eliminar el Super Administrador?**
No. El usuario con `ID = 1` está protegido: se puede editar, pero no eliminar.

### **¿Por qué no se actualiza mi nombre en la barra superior?**
Si editas tu perfil, el sistema debería actualizar automáticamente. Si no sucede, recarga la página manualmente.

### **¿Cómo funciona la actualización automática de usuarios?**
Cuando editas tu propio perfil, el sistema actualiza la sesión y recarga la página para mostrar los cambios inmediatamente.

### **¿Por qué me lleva a "Registro del super usuario" la primera vez?**
Cuando no hay ningún usuario en el sistema, la aplicación redirige a la pantalla de registro para crear al **super administrador**. Solo ocurre una vez; después podrás iniciar sesión normalmente. Ese usuario usa el campo "Usuario" (no correo) y no elige hotel porque administra todos los hoteles.

### **¿Cómo genero el PDF de Solicitud de Alta de Equipos?**
Entra al menú **Solicitud para dar de Alta Equipos**, completa los datos de la solicitud, agrega equipos por código (Buscar y agregar), rellena Clasificación 1 y 2, y haz clic en **"Guardar y generar PDF"**. Se abrirá el PDF en una nueva ventana; puedes imprimirlo con márgenes ya ajustados para que no se corte.

### **¿Por qué el formulario de registro se ve diferente?**
El sistema ahora usa el tema Villa Group con colores dorados y oscuros para una mejor experiencia visual.

### **¿Qué pasa si el logo se ve cortado?**
El sistema ahora tiene el logo optimizado con proporciones correctas. Si se ve cortado, verifica que estés usando una versión actualizada.

---

## 📞 **SOPORTE TÉCNICO**

### **Para Reportar Problemas:**
1. Documenta el error con capturas de pantalla
2. Anota los pasos que llevaron al problema
3. Contacta al administrador del sistema

### **Mantenimiento del Sistema:**
- **Respaldos:** Realizar respaldos regulares de la base de datos
- **Actualizaciones:** Mantener el servidor web actualizado
- **Monitoreo:** Verificar el espacio en disco regularmente

---

---

## 🆕 **NOVEDADES DE LA VERSIÓN ACTUAL**

### **Abril 2026:**

#### **Seguridad en gestión de usuarios**
- ✅ **Protección del Super Administrador:** El usuario `ID = 1` ya no se puede eliminar.
- ✅ **Interfaz de usuarios:** En la tabla se deshabilita el botón de eliminar para el super administrador y se muestra como bloqueado.

#### **Carga masiva de equipos (CSV)**
- ✅ **Nuevo campo recomendado:** `ubicacion_fisica` en el archivo CSV para guardar correctamente la ubicación física del equipo.
- ✅ **Mapeo corregido en importación:** El sistema diferencia `departamento` y `ubicacion_fisica` durante la inserción.

### **Marzo 2026:**

#### **Solicitud para dar de Alta Equipos**
- ✅ **Nuevo módulo:** Desde el menú puedes ir a **Solicitud para dar de Alta Equipos**. Ahí se completa el formulario (departamento solicitante, fecha, número de solicitud), se agregan equipos por **código primario**, se completan datos de ubicación (fase, área, piso, tipo habitación, departamento) y Clasificación 1 y 2. Al hacer clic en **"Guardar y generar PDF"** se guarda la solicitud y se abre el **PDF** del documento oficial.
- ✅ **PDF listo para imprimir:** El formato incluye logo, tabla de equipos con títulos completos (Características, Centro de costos, Área/Nº de habitación, Tipo de habitación), secciones de Autorizaciones y Departamento. Los márgenes están ajustados (25 mm a izquierda y derecha) para que al imprimir el contenido no se corte por los bordes de la hoja.

#### **Registro del super usuario**
- ✅ **Primera vez:** Si no hay usuarios en el sistema, al acceder se redirige a la pantalla de **Registro del super usuario**. Se usa el campo **Usuario** (no correo) y no se elige hotel; ese usuario administra todos los hoteles.

### **Mejoras de Interfaz (Diciembre 2025 – Febrero 2026):**
- ✅ **Formulario de Registro Rediseñado:** Completamente renovado con tema Villa Group
- ✅ **Login Optimizado:** Proporciones y alineación mejoradas
- ✅ **Navegación Inteligente:** Muestra nombre completo del usuario
- ✅ **Actualización Automática:** Los cambios de perfil se reflejan inmediatamente
- ✅ **Tema Consistente:** Villa Group aplicado en todo el sistema
- ✅ **Responsive Mejorado:** Mejor experiencia en móviles y tablets

### **Otras Funcionalidades:**
- ✅ **Observación por equipo en Bajas Masivas:** Cada equipo en la baja masiva tiene su propio campo de observación (falla/motivo)
- ✅ **Fecha de Adquisición en Bajas Individuales:** Campo opcional con N/A; en el informe técnico se muestra la fecha o "N/A"
- ✅ **Editar equipo con foto:** En editar equipo puede cambiar la imagen y ver la foto actual en el modal
- ✅ **Formato de Baja Masiva (PDF):** Márgenes ajustados para impresión, observación por fila, logo LOGO-BAJA cuando está disponible
- ✅ **Base de datos unificada:** Un solo archivo `bd/hotel_equipos.sql` con todas las tablas actuales
- ✅ **Eliminar resguardo:** En "Ver Resguardos Generados" se puede eliminar un resguardo con el botón **Eliminar** y confirmación

### **Correcciones Implementadas:**
- ✅ **Estilos del Registro:** Ya no se ve "deforme" o sin estilos
- ✅ **Actualización de Usuario:** Nombre se actualiza en la navegación
- ✅ **Departamentos:** Tras actualizar un departamento la pantalla ya no se traba
- ✅ **Registro de baja individual:** Mejor manejo al buscar por código y seleccionar equipo
- ✅ **Formato de Baja Masiva (enlace):** El informe técnico de bajas masivas se genera correctamente desde la tabla

---

**© 2025-2026 Sistema de Gestión de Equipos de Hotel - Versión Actualizada**