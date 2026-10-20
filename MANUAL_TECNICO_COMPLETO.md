# 📋 MANUAL TÉCNICO COMPLETO
## Sistema de Gestión de Equipos de Hotel
### Versión actualizada — abril 2026

---

## 📂 Documentación en el repositorio

El **índice maestro** está en **`README.md`** (stack, arranque y tabla de documentos para presentación o defensa). Complementan este manual: **GUIA_SISTEMA.md** (visión de negocio), **REPORTE_MAPA_ARCHIVOS_Y_CODIGO.md** (mapa de archivos), **INICIAR-SIN-XAMPP.md**, **DESPLIEGUE_Y_ACCESO_REMOTO.md** (ngrok, red, auto-inicio) y **PWA.md**.

---

## 📚 **ÍNDICE**

1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Sistema de Autenticación](#sistema-de-autenticación)
3. [Base de Datos](#base-de-datos)
4. [Flujo de Registro de Equipos](#flujo-de-registro-de-equipos)
5. [Sistema de Imágenes](#sistema-de-imágenes)
6. [Procesos de Bajas y Altas](#procesos-de-bajas-y-altas)
7. [Sistema de Reportes](#sistema-de-reportes)
8. [Configuración y Dependencias](#configuración-y-dependencias)
9. [Guía de Troubleshooting](#guía-de-troubleshooting)
10. [Cambios Recientes](#cambios-recientes)

---

## 🏗️ **ARQUITECTURA DEL SISTEMA**

### **Estructura de Carpetas:**
```
ventasAlmacen22/
├── archivos/                 # Almacenamiento de imágenes
│   ├── bajas/               # Fotos de equipos dados de baja
│   └── equipos/             # Fotos de equipos registrados
├── bd/                      # Scripts de base de datos
│   └── hotel_equipos.sql    # Base de datos principal (único script; incluye todas las tablas y columnas)
├── clases/                  # Clases PHP (Modelo)
│   ├── Conexion.php         # Conexión a BD
│   ├── Usuarios.php         # Gestión de usuarios
│   ├── Equipos.php          # Gestión de equipos
│   ├── AltasBajas.php       # Procesos de altas/bajas
│   ├── Departamentos.php    # Gestión de departamentos
│   ├── Areas.php            # Gestión de áreas
│   ├── Fases.php            # Gestión de fases
│   ├── Hoteles.php          # Gestión de hoteles
│   ├── Resguardos.php       # Resguardos de equipos (guardar, listar, eliminar)
│   └── SolicitudAltaEquipos.php # Solicitudes de alta de equipos (guardar, obtener)
├── css/                     # Estilos CSS
│   ├── menu.css             # Estilos del menú
│   └── villa-group-theme.css # Tema del hotel
├── js/                      # JavaScript
│   ├── funciones.js         # Funciones generales
│   └── seguridad-navegacion.js # Seguridad del navegador
├── librerias/               # Librerías externas
│   ├── bootstrap/           # Framework CSS 3.3.7
│   ├── dompdf/              # Generación de PDFs
│   ├── jquery-3.2.1.min.js # jQuery
│   ├── select2/             # Select mejorado
│   └── alertifyjs/          # Notificaciones
├── procesos/                # Lógica de negocio (Controlador)
│   ├── altas/               # Procesos de altas
│   ├── bajas/               # Procesos de bajas individuales
│   ├── bajasMasivas/        # Procesos de bajas masivas (incl. eliminarBajaMasiva.php)
│   ├── departamentos/       # Gestión de departamentos
│   ├── equipos/             # Gestión de equipos
│   ├── regLogin/            # Autenticación (login.php, registro.php)
│   ├── resguardos/          # Resguardos (guardarResguardo, guardarResguardosMultiples, obtenerEquipoPorCodigo, eliminarResguardo, eliminarResguardoOGrupo)
│   ├── solicitudAltaEquipos/ # Guardar solicitud de alta (guardarSolicitudAltaEquipos.php)
│   └── usuarios/            # Gestión de usuarios
├── reportes/                # Generación de reportes PDF
│   ├── informeTecnicoBaja.php        # Reporte de bajas individuales
│   ├── informeTecnicoBajaMasiva.php  # Formato de Baja de Equipo (bajas masivas)
│   ├── reporteSolicitudAltaEquipos.php # PDF Solicitud para dar de alta equipos (Dompdf, A4 horizontal)
│   ├── reporteResguardo.php          # Resguardo individual (un equipo)
│   ├── reporteResguardoMultiple.php  # Resguardo múltiple (tabla con N equipos)
│   └── imprimirResguardo.php         # Redirige a individual o múltiple según id_grupo_resguardo
├── vistas/                  # Interfaz de usuario (Vista)
│   ├── altas/               # Vistas de altas
│   ├── bajas/               # Vistas de bajas individuales
│   ├── bajasMasivas/        # Vistas de bajas masivas
│   ├── departamentos/       # Vistas de departamentos
│   ├── equipos/             # Vistas de equipos
│   ├── reportes/            # Vistas de reportes (reporteEquipos, reportePorDepartamento, etc.)
│   ├── resguardos/          # Resguardos (generarResguardo, generarResguardoMultiple, tablaResguardos agrupada, Eliminar/Eliminar grupo)
│   ├── solicitudAltaEquipos/ # Formulario solicitud alta (formularioSolicitudAlta.php)
│   └── usuarios/            # Vistas de usuarios
├── README.md                # Índice del proyecto, stack y enlaces a toda la documentación
├── config.php               # Configuración BD y RUTA_BASE (ver config.ejemplo.php si copias proyecto)
├── config.ejemplo.php       # Plantilla de configuración (evitar Access denied al copiar carpeta)
├── index.php                # Punto de entrada principal
├── registro.php             # Registro de usuarios
├── seguridad.php            # Sistema de seguridad
├── MANUAL_DE_USUARIO.md     # Manual de usuario
├── MANUAL_TECNICO_COMPLETO.md # Este manual técnico
├── GUIA_SISTEMA.md          # Guía de negocio / qué hace cada módulo
├── REPORTE_MAPA_ARCHIVOS_Y_CODIGO.md # Mapa de archivos y código
├── INICIAR-SIN-XAMPP.md     # Guía PHP + MariaDB sin XAMPP
├── DESPLIEGUE_Y_ACCESO_REMOTO.md   # ngrok, acceso remoto, inicio automático (Windows)
├── PWA.md                   # PWA: HTTPS, iconos, instalación en dispositivo
├── iniciar-servidor.bat     # Atajo Windows: servidor embebido PHP
└── test-pwa.html            # Comprobación rápida de manifest y Service Worker
```

### **Patrón de Arquitectura:**
El sistema sigue el patrón **MVC (Modelo-Vista-Controlador)**:

- **Modelo (M):** Clases en `clases/` - Lógica de datos
- **Vista (V):** Archivos en `vistas/` - Interfaz de usuario
- **Controlador (C):** Archivos en `procesos/` - Lógica de negocio

---

## 🔐 **SISTEMA DE AUTENTICACIÓN**

### **Archivos de Seguridad:**
- **`seguridad.php`:** Sistema de autenticación centralizado. Si se define la constante `PDF_OUTPUT` antes de incluirlo, no envía headers de seguridad ni regenera sesión (evita conflictos al generar PDFs).
- **`procesos/regLogin/login.php`:** Proceso de inicio de sesión
- **`procesos/regLogin/registro.php`:** Proceso de registro (usado para el primer usuario / super administrador)
- **`registro.php`:** Página de registro del **super usuario** (solo se muestra cuando no existe ningún usuario en el sistema)

### **Flujo de Autenticación:**
1. **Primera vez:** Si no hay usuarios en BD, `index.php` redirige a `registro.php` para crear al super administrador (campo "Usuario", no email; no se elige hotel; administra todos los hoteles).
2. **Verificación de sesión:** `requerirAutenticacion()`
3. **Validación de credenciales:** Usuario/email y contraseña
4. **Creación de sesión:** `$_SESSION['iduser']`, `id_hotel`, nombre, apellido, etc.
5. **Control de acceso:** Verificación en cada página

### **Niveles de Permisos:**
- **Usuario Normal:** Acceso completo excepto eliminaciones; restringido a su hotel (`id_hotel`).
- **Super Administrador (ID = 1):** Primer usuario registrado; acceso completo incluyendo eliminaciones y gestión de todos los hoteles. Por seguridad, el propio usuario `ID = 1` no puede eliminarse.

### **Funciones de Seguridad:**
```php
// Verificar autenticación
function requerirAutenticacion() {
    if (!isset($_SESSION['iduser'])) {
        header('Location: ../index.php');
        exit;
    }
}

// Verificar permisos de administrador
if(isset($_SESSION['iduser']) && $_SESSION['iduser'] == 1) {
    // Código solo para administrador
}
```

---

## 🗄️ **BASE DE DATOS**

**Script único:** Toda la estructura se crea con `bd/hotel_equipos.sql` (incluye tablas y columnas actuales: `fecha_adquisicion` en bajas_equipos, `hotel_area` en bajas_masivas, `observaciones` en bajas_masivas_equipos, **`id_grupo_resguardo`** en resguardos para separar resguardos individuales de múltiples).

### **Estructura de Tablas:**

#### **1. usuarios**
```sql
CREATE TABLE usuarios(
    id_usuario int auto_increment primary key,
    nombre varchar(50),
    apellido varchar(50),
    email varchar(50),
    password varchar(50),
    fechaCaptura date
);
```

#### **2. departamentos**
```sql
CREATE TABLE departamentos(
    id_departamento int auto_increment primary key,
    id_usuario int NULL,
    nombre varchar(100),
    descripcion text,
    responsable varchar(100),
    fecha_creacion date,
    foreign key (id_usuario) references usuarios(id_usuario)
);
```

#### **3. equipos (Tabla Principal)**
```sql
CREATE TABLE equipos(
    id_equipo int auto_increment primary key,
    id_usuario int,
    id_departamento int,
    codigo_equipo varchar(50) unique,
    descripcion text,
    marca varchar(100),
    modelo varchar(100),
    numero_serie varchar(100),
    estado enum('Activo', 'En Mantenimiento', 'En Reparación', 'Dado de Baja') default 'Activo',
    ubicacion varchar(200),
    fecha_ingreso date,
    valor_adquisicion decimal(10,2),
    tipo_moneda enum('USD', 'MXN', 'EUR') default 'USD',
    fecha_registro date,
    foto_equipo varchar(255) NULL,
    foreign key (id_usuario) references usuarios(id_usuario),
    foreign key (id_departamento) references departamentos(id_departamento)
);
```

#### **4. bajas_equipos (Bajas Individuales)**
```sql
CREATE TABLE bajas_equipos(
    id_baja int auto_increment primary key,
    id_equipo int,
    id_usuario int,
    id_departamento int,
    fecha_baja date,
    tipo_baja enum('Individual', 'Masiva'),
    motivo_baja varchar(200),
    valor_residual decimal(10,2),
    destino_equipo varchar(100),
    observaciones text,
    foto_equipo varchar(255) NULL,
    foto_etiqueta varchar(255) NULL,
    tecnico_reviso varchar(100) NULL,
    gerente_area varchar(100) NULL,
    fecha_adquisicion date NULL COMMENT 'NULL = N/A',
    foreign key (id_equipo) references equipos(id_equipo),
    foreign key (id_usuario) references usuarios(id_usuario),
    foreign key (id_departamento) references departamentos(id_departamento)
);
```

#### **5. bajas_masivas (Bajas Masivas - Principal)**
```sql
CREATE TABLE bajas_masivas(
    id_baja_masiva int auto_increment primary key,
    id_usuario int,
    id_departamento int,
    hotel_area varchar(100) NULL,
    fecha_baja date,
    motivo_baja varchar(200),
    valor_residual_total decimal(10,2),
    destino_equipo varchar(100),
    observaciones text,
    total_equipos int,
    foreign key (id_usuario) references usuarios(id_usuario),
    foreign key (id_departamento) references departamentos(id_departamento)
);
```

#### **6. bajas_masivas_equipos (Bajas Masivas - Relaciones)**
```sql
CREATE TABLE bajas_masivas_equipos(
    id_relacion int auto_increment primary key,
    id_baja_masiva int,
    id_equipo int,
    valor_residual_individual decimal(10,2),
    observaciones text NULL COMMENT 'Observación por equipo',
    foreign key (id_baja_masiva) references bajas_masivas(id_baja_masiva),
    foreign key (id_equipo) references equipos(id_equipo)
);
```

#### **7. altas_equipos**
```sql
CREATE TABLE altas_equipos(
    id_alta int auto_increment primary key,
    id_equipo int,
    id_usuario int,
    id_departamento int,
    fecha_alta date,
    motivo_alta varchar(200),
    observaciones text,
    foreign key (id_equipo) references equipos(id_equipo),
    foreign key (id_usuario) references usuarios(id_usuario),
    foreign key (id_departamento) references departamentos(id_departamento)
);
```

### **Relaciones Principales:**
- **equipos** → **departamentos** (Muchos a Uno)
- **equipos** → **usuarios** (Muchos a Uno)
- **bajas_equipos** → **equipos** (Uno a Uno)
- **bajas_masivas** → **bajas_masivas_equipos** (Uno a Muchos)
- **bajas_masivas_equipos** → **equipos** (Muchos a Uno)

---

## 📦 **FLUJO DE REGISTRO DE EQUIPOS**

### **Proceso de Registro:**

#### **1. Frontend (Vista)**
- **Archivo:** `vistas/equipos/agregarEquipo.php`
- **Formulario:** Campos de entrada con validación HTML5
- **JavaScript:** Validación en tiempo real

#### **2. Controlador**
- **Archivo:** `procesos/equipos/insertaEquipo.php`
- **Validación:** Campos obligatorios y formato
- **Procesamiento:** Manejo de archivos e inserción

#### **3. Modelo**
- **Archivo:** `clases/Equipos.php`
- **Método:** `insertaEquipo($datos)`
- **SQL:** Inserción en tabla `equipos`

### **Flujo de Datos:**
```
Formulario → Validación → Procesamiento → Base de Datos → Respuesta
```

### **Validaciones Implementadas:**
- **Código único:** Verificación de duplicados
- **Campos obligatorios:** Validación de entrada
- **Archivos:** Tamaño y formato de imágenes
- **Tipos de datos:** Validación de tipos

---

## 📸 **SISTEMA DE IMÁGENES**

### **Estructura de Archivos:**
```
archivos/
├── equipos/                 # Fotos de equipos registrados
│   └── [archivos].jpg/png
└── bajas/                   # Fotos de equipos dados de baja
    ├── equipo_[id]_[timestamp].jpg
    └── etiqueta_[id]_[timestamp].jpg
```

### **Procesamiento de Imágenes:**
- **Subida:** `move_uploaded_file()`
- **Validación:** Tamaño máximo 2MB
- **Formatos:** JPG, PNG, GIF
- **Nomenclatura:** `equipo_[id]_[timestamp].[ext]`

### **Funciones de Imagen:**
```php
// Obtener ruta de imagen
function obtenerRutaFotoEquipo($rutaRelativa) {
    // Lógica para encontrar la imagen
    // Búsqueda en múltiples ubicaciones
    // Retorna ruta válida o false
}
```

---

## 📉 **PROCESOS DE BAJAS Y ALTAS**

### **Bajas Individuales:**

#### **Flujo:**
1. **Búsqueda:** AJAX para encontrar equipos (por código o selección)
2. **Selección:** Carga de datos del equipo; se asegura equipo válido al enviar
3. **Formulario:** Motivo, valor residual, destino, **fecha de adquisición** (opcional, con N/A), observaciones, fotos, técnico, gerente
4. **Procesamiento:** Inserción en `bajas_equipos` (incluye `fecha_adquisicion` o NULL)
5. **Actualización:** Cambio de estado del equipo

#### **Archivos Involucrados:**
- **Vista:** `vistas/bajas/registrarBaja.php`
- **Controlador:** `procesos/bajas/registrarBaja.php`
- **Modelo:** `clases/AltasBajas.php::registrarBaja()`

### **Bajas Masivas:**

#### **Flujo:**
1. **Búsqueda:** Equipos ya dados de baja individualmente (o disponibles para baja)
2. **Selección múltiple:** Agregar equipos a la lista; **cada fila tiene campo "Observación (falla/motivo por equipo)"**
3. **Información general:** Hotel y área, motivo, destino (sin textarea general de observaciones)
4. **Procesamiento:** Inserción en `bajas_masivas` y en `bajas_masivas_equipos` con **observaciones por equipo**

#### **Archivos Involucrados:**
- **Vista:** `vistas/bajasMasivas/registrarBajaMasiva.php`
- **Controlador:** `procesos/bajasMasivas/registrarBajaMasiva.php` (recibe `observacionesEquipos` JSON)
- **Modelo:** `clases/AltasBajas.php::registrarBajaMasiva($equipos, $datos, $observacionesEquipos)`
- **Consulta:** `obtenerEquiposBajaMasiva()` devuelve `bme.observaciones` si existe la columna

### **Altas de Equipos:**

#### **Flujo:**
1. **Selección:** Equipo dado de baja
2. **Información:** Motivo de la alta
3. **Procesamiento:** Inserción en `altas_equipos`
4. **Actualización:** Cambio de estado a "Activo"

### **Solicitud de Alta de Equipos (documento PDF):**

#### **Flujo:**
1. **Vista:** `vistas/solicitudAltaEquipos.php` carga `vistas/solicitudAltaEquipos/formularioSolicitudAlta.php`.
2. **Formulario:** Departamento solicitante, cantidad, fecha, número de solicitud; búsqueda de equipos por código primario (`procesos/resguardos/obtenerEquipoPorCodigo.php`); tabla de equipos con datos de ubicación (fase, área, piso, tipo habitación, departamento); Clasificación 1 y 2.
3. **Guardar:** `procesos/solicitudAltaEquipos/guardarSolicitudAltaEquipos.php` recibe JSON con cabecera y detalles; `clases/SolicitudAltaEquipos.php` guarda en BD (tablas de solicitudes y detalles).
4. **PDF:** Tras guardar, se abre `reportes/reporteSolicitudAltaEquipos.php?id=<id_solicitud>` en nueva ventana; el reporte usa Dompdf, logo desde `img/`, márgenes equilibrados para impresión.

### **Resguardos:**

#### **Flujo:**
1. **Generar individual:** Código de equipo → `obtenerEquipoPorCodigo.php`; guardar con `guardarResguardo.php`; PDF con `reporteResguardo.php?id=`. **Generar múltiple:** datos comunes + lista de códigos → `guardarResguardosMultiples.php` (asigna `id_grupo_resguardo`); PDF con `reporteResguardoMultiple.php?ids=`. **Historial:** tabla agrupada por `id_grupo_resguardo`; Imprimir usa `imprimirResguardo.php?id=` (redirige a individual o múltiple). **Eliminar:** `eliminarResguardoOGrupo.php` (borra uno o todo el grupo). Tipo en resguardos = descripción del equipo (precargada/alta).
2. **Listar:** `obtenerListaResguardos()` y `obtenerListaResguardosAgrupada()` (agrupa por `id_grupo_resguardo`); vista `tablaResguardos.php` muestra Tipo (Individual / Múltiple (N equipos)), Imprimir (vía `imprimirResguardo.php`), Eliminar (vía `eliminarResguardoOGrupo.php`).
3. **Eliminar:** `eliminarResguardoOGrupo($id)` borra un resguardo o todo el grupo si tiene `id_grupo_resguardo`; proceso `eliminarResguardoOGrupo.php` (POST `id_resguardo`, JSON ok/eliminados).

---

## 📊 **SISTEMA DE REPORTES**

### **Tecnología Utilizada:**
- **Librería:** DOMPDF
- **Formato:** PDF
- **Codificación:** UTF-8

### **Tipos de Reportes:**

#### **1. Reportes Generales:**
- **Archivo:** `vistas/reportes/reporteEquipos.php`
- **Contenido:** Lista completa de equipos
- **Filtros:** Por departamento, estado, fecha

#### **2. Informes Técnicos:**
- **Bajas Individuales:** `reportes/informeTecnicoBaja.php` — Dictamen técnico, fotos (tamaño controlado), fecha de adquisición o N/A, firmas
- **Bajas Masivas:** `reportes/informeTecnicoBajaMasiva.php` — Formato de Baja de Equipo en A4 horizontal; logo LOGO-BAJA (en `img/`); **observación por fila** desde `bajas_masivas_equipos.observaciones`; márgenes `@page` (14mm, 22mm izquierdo) y body para impresión. La tabla de bajas masivas (`vistas/bajasMasivas/tablaBajasMasivas.php`) enlaza a `reportes/informeTecnicoBajaMasiva.php?id_baja=` para que el PDF se resuelva correctamente desde la URL base del proyecto
- **Solicitud de Alta de Equipos:** `reportes/reporteSolicitudAltaEquipos.php?id=` — PDF "Solicitud para dar de alta equipos de operación" generado con **Dompdf**, A4 horizontal. Incluye: cabecera con logo (img/LOGO-BAJA o hotel.jpg), título; tabla de equipos con fila "DATOS DEL EQUIPO" / "LOCALIZACION" y columnas completas (Descripción, Marca, Modelo, Serie, Características, Color, Responsables, Centro de costos, Fase, Área/Nº de habitación, Piso, Tipo de habitación, Departamento); Clasificación 1 y 2; sección AUTORIZACIONES (NOMBRE COMPLETO, FIRMA, roles); sección DEPARTAMENTO (SEGURIDAD, RECEPCIÓN DE MERCANCÍA). Márgenes `@page` equilibrados (15mm arriba/abajo, 25mm izquierda/derecha) para impresión correcta. Se define `PDF_OUTPUT` y se usa buffer de salida para evitar conflictos con `seguridad.php`.
- **Resguardos:** `reportes/imprimirResguardo.php?id=` (redirige a `reporteResguardo.php` o `reporteResguardoMultiple.php?ids=` según grupo); Tipo en PDF = descripción del equipo

### **Generación de PDFs:**
```php
// Configuración DOMPDF
require_once 'librerias/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte.pdf");
```

---

## ⚙️ **CONFIGURACIÓN Y DEPENDENCIAS**

### **Requisitos del Servidor:**
- **PHP:** 7.4 o superior
- **MySQL:** 5.7 o MariaDB 10.3+
- **Apache:** 2.4+
- **Extensiones PHP:** mysqli, gd, fileinfo

### **Librerías Externas:**
- **Bootstrap:** 3.3.7 (CSS Framework)
- **jQuery:** 3.2.1 (JavaScript)
- **DOMPDF:** 2.0+ (Generación PDF)
- **AlertifyJS:** Notificaciones
- **Select2:** Select mejorado

### **Configuración de PHP:**
```ini
upload_max_filesize = 2M
post_max_size = 8M
max_execution_time = 30
memory_limit = 128M
```

### **Configuración de Base de Datos:**
```php
// Conexión en clases/Conexion.php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'hotel_equipos';
$charset = 'utf8';
```

---

## 🎨 **SISTEMA DE INTERFAZ Y ESTILOS**

### **Tema Villa Group:**
El sistema utiliza un tema personalizado basado en la identidad visual de Villa Group:

#### **Paleta de Colores:**
```css
:root {
    --villa-dark: #1a1a1a;        /* Fondo principal */
    --villa-darker: #0f0f0f;      /* Fondo secundario */
    --villa-light: #2a2a2a;       /* Elementos de interfaz */
    --villa-white: #ffffff;       /* Texto principal */
    --villa-gold: #c29a4b;        /* Color dorado principal */
    --villa-gold-hover: #d4a85c;  /* Dorado hover */
    --villa-orange: #a0522d;      /* Color naranja */
    --villa-orange-hover: #b05a35; /* Naranja hover */
    --villa-text: #ffffff;        /* Texto blanco */
    --villa-text-muted: #cccccc;  /* Texto secundario */
    --villa-border: #333333;      /* Bordes */
}
```

#### **Componentes Estilizados:**
- **Formularios:** Campos con bordes dorados y efectos de focus
- **Botones:** Gradientes dorados con efectos hover
- **Navegación:** Fondo oscuro con acentos dorados
- **Tablas:** Fondo oscuro sin efectos hover problemáticos
- **Modales:** Estilo consistente con el tema

#### **Responsive Design:**
- **Móviles:** Ajustes específicos para pantallas pequeñas
- **Tablets:** Optimización para dispositivos medianos
- **Desktop:** Experiencia completa en pantallas grandes

### **Sistema de Navegación:**
- **Menú Principal:** Navegación fija con logo Villa Group
- **Breadcrumbs:** Navegación contextual
- **Dropdowns:** Menús desplegables estilizados
- **Usuario:** Muestra nombre completo en lugar de email

---

## 🔧 **GUÍA DE TROUBLESHOOTING**

### **Problemas Comunes:**

#### **1. Error de Conexión a Base de Datos:**
```
Síntoma: "Error de conexión a la base de datos"
Solución: Verificar credenciales en clases/Conexion.php
```

#### **2. Imágenes No Se Muestran:**
```
Síntoma: Imágenes aparecen rotas
Solución: Verificar permisos de carpeta archivos/
```

#### **3. Error al Generar PDFs:**
```
Síntoma: "Error al generar reporte"
Solución: Verificar instalación de DOMPDF
```

#### **4. Sesión Expirada:**
```
Síntoma: Redirección constante a login
Solución: Verificar configuración de sesiones PHP
```

#### **5. Archivos No Se Suben:**
```
Síntoma: Error al subir imágenes
Solución: Verificar upload_max_filesize en PHP
```

#### **6. Estilos No Se Aplican:**
```
Síntoma: Formularios se ven sin estilos
Solución: Verificar que villa-group-theme.css esté cargado
```

#### **7. Nombre de Usuario No Se Actualiza:**
```
Síntoma: Navegación muestra nombre anterior
Solución: Verificar que actualizaUsuario.php actualice la sesión
```

#### **8. Logo Se Ve Cortado:**
```
Síntoma: Logo aparece parcialmente visible
Solución: Verificar object-fit: contain en CSS del logo
```

### **Logs del Sistema:**
- **Ubicación:** `C:\xampp\apache\logs\error.log`
- **Contenido:** Errores PHP y MySQL
- **Nivel:** Error, Warning, Notice

### **Debugging:**
```php
// Habilitar logs de error
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log personalizado
error_log("Mensaje de debug: " . $variable);
```

---

## 📝 **CAMBIOS RECIENTES**

### **Versión Abril 2026:**

#### **✅ Seguridad en eliminación de usuarios (Super Admin protegido):**
- **Backend protegido:** `procesos/usuarios/eliminarUsuario.php` ahora valida sesión, permisos y bloquea eliminar `id_usuario = 1`.
- **Regla de negocio en clase:** `clases/Usuarios.php::eliminaUsuario()` devuelve `SUPER_ADMIN_PROTEGIDO` si se intenta borrar al super administrador.
- **Protección adicional:** también se evita auto-eliminación del usuario en sesión (`NO_AUTO_ELIMINAR`).
- **UI alineada:** `vistas/usuarios/tablaUsuarios.php` desactiva el botón eliminar para `ID = 1` y muestra icono de bloqueo.

#### **✅ Importación CSV de equipos con ubicación física:**
- **Mapeo de columnas corregido:** en `procesos/equipos/importarEquiposCsv.php` se separa `departamento` de `ubicacion_fisica`.
- **Nueva columna recomendada:** `ubicacion_fisica` (también acepta `ubicacion`) para poblar el campo `equipos.ubicacion`.
- **Ayuda de interfaz actualizada:** `vistas/equipos/agregarEquipo.php` muestra el listado de columnas CSV recomendado incluyendo `ubicacion_fisica`.

### **Versión Marzo 2026:**

#### **✅ Solicitud de Alta de Equipos:**
- **Módulo completo:** Vista `solicitudAltaEquipos.php` y formulario `formularioSolicitudAlta.php`; proceso `guardarSolicitudAltaEquipos.php`; clase `SolicitudAltaEquipos.php`.
- **Formulario:** Departamento solicitante, fecha, número de solicitud; agregar equipos por código primario (AJAX a `obtenerEquipoPorCodigo.php`); tabla con datos de equipo y ubicación (fase, área, piso, tipo habitación, departamento); Clasificación 1 y 2; botón "Guardar y generar PDF".
- **PDF:** `reportes/reporteSolicitudAltaEquipos.php` con **Dompdf** (A4 horizontal). Cabecera con logo; tabla con fila "DATOS DEL EQUIPO" / "LOCALIZACION"; columnas con títulos completos (Características, Centro de costos, Área/Nº de habitación, Tipo de habitación); secciones Autorizaciones y Departamento; márgenes `@page` 15mm (arriba/abajo) y 25mm (izq/der) para impresión equilibrada.
- **Seguridad en PDF:** Constante `PDF_OUTPUT` y buffer de salida para no enviar headers de seguridad al generar el PDF.

#### **✅ Registro de Super Usuario:**
- **Primera instalación:** Si no hay usuarios, `index.php` redirige a `registro.php` (una sola vez).
- **Formulario:** Campo "Usuario" (no email) para el super administrador; no se elige hotel; el primer usuario (ID = 1) administra todos los hoteles.
- **Proceso:** `procesos/regLogin/registro.php` valida y llama a `Usuarios::registroUsuario` con `id_hotel` por defecto.

#### **✅ Reportes PDF e Impresión:**
- **Solicitud de Alta:** Márgenes equilibrados (25mm izquierda/derecha) para que el contenido no se corte al imprimir; encabezados de tabla con texto completo y ajuste de fuente para que no se trunque.
- **Bajas individuales:** Ajustes de espaciado y tamaño de fotos en el informe técnico.

---

### **Versión Febrero 2026:**

#### **✅ Base de Datos Unificada:**
- **Un solo script:** `bd/hotel_equipos.sql` contiene todas las tablas y columnas actuales
- **bajas_equipos:** Columna `fecha_adquisicion` (DATE NULL) para equipos antiguos; NULL = N/A
- **bajas_masivas:** Columna `hotel_area` ya incluida en el CREATE
- **bajas_masivas_equipos:** Columna `observaciones` (TEXT NULL) por equipo
- Eliminados scripts sueltos de ALTER; todo integrado en el principal

#### **✅ Bajas Individuales:**
- **Fecha de Adquisición:** Campo opcional con checkbox N/A; se guarda NULL en BD cuando N/A
- **Registrar baja:** Aseguramiento de equipo válido al enviar (búsqueda por código si hace falta)
- **Informe técnico PDF:** Muestra fecha de adquisición o "N/A" según `bajas_equipos.fecha_adquisicion`

#### **✅ Bajas Masivas:**
- **Observación por equipo:** Cada fila en "Equipos Seleccionados" tiene campo de texto; se guarda en `bajas_masivas_equipos.observaciones`
- **Sin textarea general:** Eliminado el campo "Observaciones" único de la baja masiva
- **PDF:** Cada fila del formato muestra su observación; márgenes `@page` (14mm, 22mm izquierdo) y estilos para impresión
- **Logo:** Uso de `img/LOGO-BAJA.png` o `.jpg` cuando existe para el informe de bajas masivas
- **Informe técnico en reportes/:** Creado `reportes/informeTecnicoBajaMasiva.php` (misma lógica que `vistas/reportes/`) con rutas `__DIR__/../` para includes; en `tablaBajasMasivas.php` el enlace pasó de `../reportes/` a `reportes/informeTecnicoBajaMasiva.php` para que el PDF se resuelva correctamente (evita 404 cuando la base URL es la carpeta del proyecto)

#### **✅ Equipos y Departamentos:**
- **Editar equipo:** Permite cambiar la foto; vista previa y envío con FormData; imagen actual cargada por URL en el modal
- **Departamentos:** Recarga de tabla con `$modal.one('hidden.bs.modal', ...)` para evitar pantalla trabada tras actualizar

#### **✅ Seguridad y Rutas:**
- **seguridad.php:** Inclusión de Conexión con `dirname(__FILE__)` para funcionar desde cualquier script que lo incluya

#### **✅ Mejoras de Interfaz y UX (Diciembre 2025):**
- **Formulario de Registro:** Completamente rediseñado con tema Villa Group
- **Login de Inicio:** Proporciones y alineación optimizadas
- **Navegación:** Actualización automática del nombre del usuario
- **Estilos CSS:** Tema Villa Group aplicado consistentemente
- **Responsive Design:** Mejorado para dispositivos móviles

#### **✅ Sistema de Autenticación Mejorado:**
- **Variables de Sesión:** Carga completa de datos del usuario (nombre, apellido, email)
- **Actualización de Sesión:** Automática cuando el usuario actualiza su perfil
- **Navegación Inteligente:** Muestra nombre completo en lugar de email
- **Recarga Automática:** Página se actualiza cuando se modifica el usuario actual

#### **✅ Correcciones de Estilos:**
- **Logo Proporcionado:** Tamaño optimizado (80px) con `object-fit: contain`
- **Espaciado Consistente:** Márgenes y padding uniformes
- **Tipografía Equilibrada:** Tamaños de fuente optimizados
- **Formularios Compactos:** Campos y botones mejor proporcionados

#### **✅ Limpieza de código no usado:**
- **Eliminado** `vistas/bajasMasivas/contenidoBajasMasivas.php`: no era cargado por nadie (bajasMasivas.php usa `#contenido` y carga directamente registrarBajaMasiva/tablaBajasMasivas).
- **Eliminado** `vistas/reportes/informeTecnicoBajaMasiva.php`: duplicado; la app usa solo `reportes/informeTecnicoBajaMasiva.php`.
- **Quitado** el panel "Reporte por Categoría" de `vistas/reportes.php`: el archivo `reportePorCategoria.php` no existía y el sistema de categorías fue eliminado anteriormente.

#### **✅ Resguardos - Eliminar:**
- **Eliminar resguardo:** En la tabla de resguardos generados se añadió la columna **Acciones** con botón **Eliminar** (para corregir registros erróneos)
- **Backend:** `clases/Resguardos.php::eliminarResguardo($id_resguardo)` (DELETE en `resguardos`); `procesos/resguardos/eliminarResguardo.php` (verifica sesión, recibe `id_resguardo` por POST, responde "1"/"0")
- **Vista:** `vistas/resguardos/tablaResguardos.php` — columna Eliminar, confirmación con alertify, recarga de tabla tras éxito

#### **✅ Eliminaciones Anteriores:**
- **Sistema de Categorías:** Removido completamente
- **Tabla `categorias_equipos`:** Eliminada
- **Tabla `imagenes_equipos`:** Eliminada
- **Clase `Categorias.php`:** Eliminada
- **Archivos de categorías:** Eliminados

#### **✅ Mejoras Funcionales:**
- **Búsqueda por código:** Implementada en bajas individuales
- **Bajas masivas:** Solo equipos ya dados de baja individualmente
- **Sistema de eliminación:** Solo para administradores
- **Búsqueda en tabla:** Filtrado por código en equipos
- **Interfaz mejorada:** Sin efectos hover problemáticos

#### **✅ Correcciones Técnicas:**
- **Rutas de imágenes:** Corregidas para reportes PDF
- **Índices de arrays:** Ajustados tras eliminación de categorías
- **Validaciones:** Mejoradas para códigos duplicados
- **Sesiones:** Corregidas en bajas masivas
- **Actualización de Usuarios:** Sesión se actualiza automáticamente

### **Estructura Actualizada:**
- **BD:** Un único archivo `bd/hotel_equipos.sql`
- **Bajas individuales:** `fecha_adquisicion` en BD y en formulario (N/A)
- **Bajas masivas:** Observación por equipo en formulario y en PDF
- **Reportes PDF:** Márgenes y observación por fila en formato de baja masiva

### **Archivos Clave Modificados/Relevantes:**
- `bd/hotel_equipos.sql` — Script único de BD (incluye tablas de solicitud de alta si aplica)
- `registro.php` — Registro del super usuario (primera vez); `procesos/regLogin/registro.php` — Proceso
- `seguridad.php` — Uso de `PDF_OUTPUT` para no enviar headers al generar PDF
- `vistas/solicitudAltaEquipos.php`, `vistas/solicitudAltaEquipos/formularioSolicitudAlta.php` — Formulario solicitud de alta
- `procesos/solicitudAltaEquipos/guardarSolicitudAltaEquipos.php` — Guardar solicitud y detalles
- `clases/SolicitudAltaEquipos.php` — Modelo; `reportes/reporteSolicitudAltaEquipos.php` — PDF con Dompdf
- `vistas/bajas/registrarBaja.php` — Fecha adquisición, N/A, asegurar equipo
- `vistas/bajasMasivas/registrarBajaMasiva.php` — Tabla con observación por equipo; enlace a `reportes/informeTecnicoBajaMasiva.php`
- `procesos/bajasMasivas/registrarBajaMasiva.php` — Recibe `observacionesEquipos`
- `clases/AltasBajas.php` — `registrarBajaMasiva(..., $observacionesEquipos)`, `obtenerEquiposBajaMasiva()` con `bme.observaciones`
- `reportes/informeTecnicoBajaMasiva.php` — Informe técnico de bajas masivas (rutas con `__DIR__`)
- `vistas/equipos/tablaEquipos.php` — Editar con foto; `procesos/equipos/obtenerEquipo.php` — URL de foto
- `vistas/departamentos.php`, `tablaDepartamentos.php` — Cierre de modal antes de recargar
- `clases/Resguardos.php` — `eliminarResguardo($id_resguardo)`; `procesos/resguardos/eliminarResguardo.php`; `vistas/resguardos/tablaResguardos.php` — Columna Acciones con Eliminar

---

## 🚀 **PLANES FUTUROS**

### **Mejoras Propuestas:**
- **Cambio de contraseña:** Para usuarios
- **Backup automático:** De base de datos
- **API REST:** Para integraciones
- **Notificaciones:** Sistema de alertas
- **Auditoría:** Log de cambios
- **Multi-idioma:** Soporte internacional

### **Optimizaciones:**
- **Caché:** Para consultas frecuentes
- **Compresión:** De imágenes
- **CDN:** Para archivos estáticos
- **Indexación:** Mejorar búsquedas

---

**© 2025-2026 Sistema de Gestión de Equipos de Hotel — Manual técnico (abril 2026)**