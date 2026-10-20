-- =============================================================================
-- ÚNICA BASE DE DATOS - Sistema de Gestión de Equipos de Hotel (Villa Group)
-- =============================================================================
-- Este es el único archivo SQL necesario. Crea la base de datos completa:
--   hoteles, usuarios, departamentos, equipos, altas_equipos, bajas_equipos,
--   bajas_masivas, bajas_masivas_equipos, resguardos, fases, areas,
--   solicitud_alta_equipos, solicitud_alta_equipos_detalle.
-- Ejecute este archivo en MySQL o MariaDB para instalar o reinstalar todo.
-- Versión actualizada - Marzo 2025
-- =============================================================================

CREATE DATABASE IF NOT EXISTS hotel_equipos;
USE hotel_equipos;

-- Tabla de hoteles (sistema multi-hotel: cada usuario y departamento pertenece a un hotel)
CREATE TABLE IF NOT EXISTS hoteles(
	id_hotel int auto_increment primary key,
	nombre varchar(100) NOT NULL COMMENT 'Nombre del hotel/resort',
	descripcion varchar(255) NULL,
	activo tinyint(1) default 1
);

-- Insertar hoteles del grupo
INSERT INTO hoteles (nombre, descripcion, activo) VALUES
('Villa del Arco', 'Resort Villa del Arco', 1),
('Villa la Estancia', 'Resort Villa la Estancia', 1),
('Villa del Palmar', 'Resort Villa del Palmar', 1),
('Villa la Valencia', 'Resort Villa la Valencia', 1);

-- Tabla de usuarios (cada usuario pertenece a un hotel)
CREATE TABLE usuarios(
	id_usuario int auto_increment primary key,
	id_hotel int NOT NULL default 1 COMMENT 'Hotel al que pertenece el usuario',
	rol varchar(30) NOT NULL default 'ADMIN' COMMENT 'Rol de acceso: ADMIN o TOALLEROS',
	nombre varchar(50),
	apellido varchar(50),
	email varchar(50),
	password varchar(50),
	fechaCaptura date,
	foreign key (id_hotel) references hoteles(id_hotel)
);

-- Tabla de stock de toallas por hotel (módulo Toalleros)
CREATE TABLE IF NOT EXISTS toallas_stock(
	id_stock int auto_increment primary key,
	id_hotel int NOT NULL,
	tipo_toalla varchar(100) NOT NULL,
	cantidad_actual int NOT NULL default 0,
	fecha_actualizacion datetime NOT NULL,
	UNIQUE KEY uq_toallas_hotel_tipo (id_hotel, tipo_toalla),
	foreign key (id_hotel) references hoteles(id_hotel)
);

-- Tabla de movimientos de toallas (entradas/salidas)
CREATE TABLE IF NOT EXISTS toallas_movimientos(
	id_movimiento int auto_increment primary key,
	id_hotel int NOT NULL,
	id_usuario int NOT NULL,
	tipo_toalla varchar(100) NOT NULL,
	tipo_movimiento enum('ENTRADA','SALIDA') NOT NULL,
	cantidad int NOT NULL,
	observaciones varchar(255) NULL,
	fecha_movimiento datetime NOT NULL,
	foreign key (id_hotel) references hoteles(id_hotel),
	foreign key (id_usuario) references usuarios(id_usuario)
);

-- Tabla de departamentos del hotel (cada departamento pertenece a un hotel)
CREATE TABLE departamentos(
	id_departamento int auto_increment primary key,
	id_hotel int NOT NULL default 1 COMMENT 'Hotel al que pertenece el departamento',
	id_usuario int NULL,
	nombre varchar(100),
	descripcion text,
	responsable varchar(100),
	representante varchar(100) NULL COMMENT 'Nombre del representante/responsable del departamento (usado en resguardos)',
	fecha_creacion date,
	foreign key (id_hotel) references hoteles(id_hotel),
	foreign key (id_usuario) references usuarios(id_usuario)
);

-- Tabla principal de equipos (SIN categorías)
CREATE TABLE equipos(
	id_equipo int auto_increment primary key,
	id_usuario int,
	id_departamento int,
	id_hotel int NULL COMMENT 'Hotel (por departamento); numeración por hotel',
	numero_equipo_hotel int NULL COMMENT 'Nº de equipo dentro del hotel (1, 2, 3...)',
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
	usuario_registro varchar(200) NULL COMMENT 'Usuario (texto libre; sin enlace a tabla usuarios)',
	numero_factura varchar(120) NULL COMMENT 'Número de factura de compra',
	foto_equipo varchar(255) NULL COMMENT 'Ruta de la foto del equipo',
	foreign key (id_usuario) references usuarios(id_usuario),
	foreign key (id_departamento) references departamentos(id_departamento)
);

-- Tabla de altas de equipos
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

-- Tabla de bajas de equipos (Bajas Individuales)
CREATE TABLE bajas_equipos(
	id_baja int auto_increment primary key,
	id_equipo int,
	id_usuario int,
	id_departamento int,
	id_hotel int NULL COMMENT 'Hotel; numeración por hotel',
	numero_baja_hotel int NULL COMMENT 'Nº de baja dentro del hotel (1, 2, 3...)',
	fecha_baja date,
	tipo_baja enum('Individual', 'Masiva'),
	motivo_baja varchar(200),
	valor_residual decimal(10,2),
	destino_equipo varchar(100),
	observaciones text,
	foto_equipo varchar(255) NULL COMMENT 'Ruta de la foto del equipo',
	foto_etiqueta varchar(255) NULL COMMENT 'Ruta de la foto de la etiqueta',
	tecnico_reviso varchar(100) NULL COMMENT 'Nombre del técnico que revisó',
	gerente_area varchar(100) NULL COMMENT 'Nombre del gerente de área',
	fecha_adquisicion date NULL COMMENT 'Fecha de adquisición del equipo; NULL = N/A',
	foreign key (id_equipo) references equipos(id_equipo),
	foreign key (id_usuario) references usuarios(id_usuario),
	foreign key (id_departamento) references departamentos(id_departamento)
);

-- Tabla de bajas masivas (Registro principal)
CREATE TABLE bajas_masivas(
	id_baja_masiva int auto_increment primary key,
	id_usuario int,
	id_departamento int,
	id_hotel int NULL COMMENT 'Hotel; numeración por hotel',
	numero_baja_masiva_hotel int NULL COMMENT 'Nº de baja masiva dentro del hotel (1, 2, 3...)',
	hotel_area varchar(100) NULL COMMENT 'Hotel y area donde se realizó la baja masiva',
	fecha_baja date,
	motivo_baja varchar(200),
	valor_residual_total decimal(10,2),
	destino_equipo varchar(100),
	observaciones text,
	total_equipos int,
	foreign key (id_usuario) references usuarios(id_usuario),
	foreign key (id_departamento) references departamentos(id_departamento)
);

-- Tabla de relaciones bajas masivas - equipos
CREATE TABLE bajas_masivas_equipos(
	id_relacion int auto_increment primary key,
	id_baja_masiva int,
	id_equipo int,
	valor_residual_individual decimal(10,2),
	observaciones text NULL COMMENT 'Observación específica del equipo en esta baja',
	foreign key (id_baja_masiva) references bajas_masivas(id_baja_masiva),
	foreign key (id_equipo) references equipos(id_equipo)
);

-- Insertar datos iniciales

-- No se insertan usuarios predefinidos
-- El primer usuario se registra a través del formulario de registro (registro.php)
-- Este usuario inicial será el administrador del sistema

-- Departamentos: no se insertan datos iniciales. Todos los departamentos los introduce el usuario desde la pantalla Departamentos.

-- Comentarios sobre la estructura:
-- 1. Se eliminaron las tablas 'categorias_equipos' e 'imagenes_equipos'
-- 2. Los equipos ahora se clasifican solo por departamentos
-- 3. Las imágenes se almacenan directamente en la tabla 'equipos' (campo 'foto_equipo')
-- 4. Las bajas masivas tienen su propia estructura de tablas
-- 5. El primer usuario registrado (ID = 1) será el administrador del sistema
-- 6. bajas_equipos incluye fecha_adquisicion (NULL = N/A) para equipos antiguos
-- 7. bajas_masivas_equipos incluye observaciones por equipo (falla/motivo de cada uno)
-- 8. fases, areas, solicitud_alta_equipos y detalle: módulo de solicitud de alta de equipos

-- Tabla de resguardos (documentos que se entregan a las áreas al dar de alta el equipo)
CREATE TABLE IF NOT EXISTS resguardos(
	id_resguardo int auto_increment primary key,
	id_grupo_resguardo int NULL COMMENT 'Si no NULL, agrupa resguardos creados juntos (múltiple). Individual = NULL',
	id_equipo int,
	id_departamento int NULL COMMENT 'Área de destino',
	area_destino varchar(200) NOT NULL COMMENT 'Nombre del área de destino',
	usuario varchar(200) NOT NULL COMMENT 'Nombre completo del responsable',
	puesto varchar(150) NULL COMMENT 'Puesto del usuario que recibe el equipo',
	fecha_resguardo date NOT NULL COMMENT 'Fecha en que se genera el resguardo',
	numero_factura varchar(100) NULL COMMENT 'Número de factura',
	modelo varchar(100) NULL COMMENT 'Modelo del equipo',
	serie varchar(100) NULL COMMENT 'Número de serie',
	tipo_equipo varchar(100) NULL COMMENT 'Tipo de equipo (ingreso manual)',
	fecha_registro datetime default CURRENT_TIMESTAMP,
	foreign key (id_equipo) references equipos(id_equipo),
	foreign key (id_departamento) references departamentos(id_departamento)
);

-- ========== Módulo Solicitud de Alta de Equipos (Fases, Áreas, Solicitudes) ==========
-- Fases del hotel (catálogo para solicitud de alta)
CREATE TABLE IF NOT EXISTS fases(
	id_fase int auto_increment primary key,
	id_hotel int NOT NULL default 1,
	nombre varchar(100) NOT NULL,
	descripcion varchar(255) NULL,
	activo tinyint(1) default 1,
	fecha_creacion date NULL,
	foreign key (id_hotel) references hoteles(id_hotel)
);

-- Áreas del hotel (catálogo para solicitud de alta)
CREATE TABLE IF NOT EXISTS areas(
	id_area int auto_increment primary key,
	id_hotel int NOT NULL default 1,
	nombre varchar(100) NOT NULL,
	descripcion varchar(255) NULL,
	activo tinyint(1) default 1,
	fecha_creacion date NULL,
	foreign key (id_hotel) references hoteles(id_hotel)
);

-- Cabecera de la solicitud de alta de equipos
CREATE TABLE IF NOT EXISTS solicitud_alta_equipos(
	id_solicitud int auto_increment primary key,
	id_usuario int NOT NULL COMMENT 'Usuario que crea la solicitud',
	id_departamento_solicitante int NULL COMMENT 'Departamento que solicita',
	numero_solicitud varchar(50) NOT NULL COMMENT 'Número de solicitud (manual)',
	fecha_solicitud date NOT NULL,
	cantidad_equipos int NOT NULL default 0,
	clasificacion_1 varchar(200) NULL,
	clasificacion_2 varchar(200) NULL,
	id_hotel int NULL,
	fecha_registro datetime default CURRENT_TIMESTAMP,
	foreign key (id_usuario) references usuarios(id_usuario),
	foreign key (id_departamento_solicitante) references departamentos(id_departamento)
);

-- Detalle: cada equipo en la solicitud
CREATE TABLE IF NOT EXISTS solicitud_alta_equipos_detalle(
	id_detalle int auto_increment primary key,
	id_solicitud int NOT NULL,
	id_equipo int NOT NULL,
	caracteristicas text NULL,
	color varchar(100) NULL,
	centro_costos enum('CAPEX','GASTO DE OPERACION') NOT NULL default 'CAPEX',
	id_fase int NULL,
	id_area int NULL,
	piso varchar(50) NULL,
	tipo_habitacion varchar(100) NULL,
	id_departamento int NULL COMMENT 'Departamento/ubicación del equipo',
	foreign key (id_solicitud) references solicitud_alta_equipos(id_solicitud) on delete cascade,
	foreign key (id_equipo) references equipos(id_equipo),
	foreign key (id_fase) references fases(id_fase),
	foreign key (id_area) references areas(id_area),
	foreign key (id_departamento) references departamentos(id_departamento)
);

-- Si su instalación ya tenía la tabla resguardos SIN la columna puesto, ejecute una vez:
-- USE hotel_equipos;
-- ALTER TABLE resguardos ADD COLUMN puesto varchar(150) NULL COMMENT 'Puesto del usuario' AFTER usuario;
--
-- Si su instalación ya tenía la tabla resguardos SIN la columna id_grupo_resguardo (separar múltiples de individuales), ejecute una vez:
-- USE hotel_equipos;
-- ALTER TABLE resguardos ADD COLUMN id_grupo_resguardo int NULL COMMENT 'Agrupa resguardos múltiples; NULL = individual' AFTER id_resguardo;

-- Si su instalación ya tenía la tabla departamentos SIN la columna representante, ejecute una vez:
-- USE hotel_equipos;
-- ALTER TABLE departamentos ADD COLUMN representante varchar(100) NULL COMMENT 'Nombre del representante (usado en resguardos)' AFTER responsable;
-- UPDATE departamentos SET representante = responsable WHERE representante IS NULL;

-- ========== MIGRACIÓN A MULTI-HOTEL (ejecutar solo si ya tiene la BD instalada) ==========
-- 1. Crear tabla hoteles si no existe e insertar los hoteles (ya incluido arriba con IF NOT EXISTS)
-- 2. Añadir id_hotel a usuarios y departamentos:
-- USE hotel_equipos;
-- CREATE TABLE IF NOT EXISTS hoteles (id_hotel int auto_increment primary key, nombre varchar(100) NOT NULL, descripcion varchar(255) NULL, activo tinyint(1) default 1);
-- INSERT IGNORE INTO hoteles (id_hotel, nombre, descripcion, activo) VALUES (1,'Villa del Arco','Resort Villa del Arco',1),(2,'Villa la Estancia','Resort Villa la Estancia',1),(3,'Villa del Palmar','Resort Villa del Palmar',1),(4,'Villa la Valencia','Resort Villa la Valencia',1);
-- ALTER TABLE usuarios ADD COLUMN id_hotel int NOT NULL default 1 AFTER id_usuario;
-- ALTER TABLE usuarios ADD FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel);
-- ALTER TABLE departamentos ADD COLUMN id_hotel int NOT NULL default 1 AFTER id_departamento;
-- ALTER TABLE departamentos ADD FOREIGN KEY (id_hotel) REFERENCES hoteles(id_hotel);
-- (Si ya tenía departamentos, quedan todos con id_hotel=1; puede duplicar departamentos para hoteles 2 y 3 si lo desea.)
--
-- Para actualizar solo los nombres de los hoteles en una instalación existente:
-- UPDATE hoteles SET nombre='Villa del Arco', descripcion='Resort Villa del Arco' WHERE id_hotel=1;
-- UPDATE hoteles SET nombre='Villa la Estancia', descripcion='Resort Villa la Estancia' WHERE id_hotel=2;
-- UPDATE hoteles SET nombre='Villa del Palmar', descripcion='Resort Villa del Palmar' WHERE id_hotel=3;
-- INSERT IGNORE INTO hoteles (id_hotel, nombre, descripcion, activo) VALUES (4,'Villa la Valencia','Resort Villa la Valencia',1);
--
-- ========== MIGRACIÓN: numeración por hotel (equipos, bajas, bajas masivas) ==========
-- Ejecutar solo si su BD ya existía y no tiene estas columnas:
-- USE hotel_equipos;
-- ALTER TABLE equipos ADD COLUMN id_hotel int NULL COMMENT 'Hotel' AFTER id_departamento;
-- ALTER TABLE equipos ADD COLUMN numero_equipo_hotel int NULL COMMENT 'Nº por hotel' AFTER id_hotel;
-- ALTER TABLE bajas_equipos ADD COLUMN id_hotel int NULL COMMENT 'Hotel' AFTER id_departamento;
-- ALTER TABLE bajas_equipos ADD COLUMN numero_baja_hotel int NULL COMMENT 'Nº por hotel' AFTER id_hotel;
-- ALTER TABLE bajas_masivas ADD COLUMN id_hotel int NULL COMMENT 'Hotel' AFTER id_departamento;
-- ALTER TABLE bajas_masivas ADD COLUMN numero_baja_masiva_hotel int NULL COMMENT 'Nº por hotel' AFTER id_hotel;
-- Rellenar y numerar (ejecutar una vez después de añadir columnas):
-- UPDATE equipos e INNER JOIN departamentos d ON e.id_departamento = d.id_departamento SET e.id_hotel = d.id_hotel;
-- UPDATE equipos e SET e.numero_equipo_hotel = (SELECT COUNT(*) FROM equipos e2 INNER JOIN departamentos d2 ON e2.id_departamento = d2.id_departamento WHERE d2.id_hotel = e.id_hotel AND (e2.numero_equipo_hotel IS NULL OR e2.id_equipo <= e.id_equipo));
-- Los registros antiguos quedarán con numero_* en NULL; en las vistas se mostrará el ID global como respaldo. Los nuevos registros obtendrán su Nº por hotel al insertar.












SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE bajas_masivas;
TRUNCATE TABLE bajas_equipos;
TRUNCATE TABLE altas_equipos;
TRUNCATE TABLE resguardos;
TRUNCATE TABLE equipos;
TRUNCATE TABLE departamentos;
TRUNCATE TABLE fases;
TRUNCATE TABLE areas;
TRUNCATE TABLE solicitud_alta_equipos;
TRUNCATE TABLE usuarios;
TRUNCATE TABLE hoteles;

SET FOREIGN_KEY_CHECKS = 1;

















