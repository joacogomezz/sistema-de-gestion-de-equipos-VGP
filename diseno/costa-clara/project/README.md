# Costa Clara

Sistema de diseño del **Sistema de Gestión de Equipos** de The Villa Group: la aplicación web interna (escritorio, móvil y PWA) para controlar el inventario de equipos de Villa del Arco, Villa del Palmar, Villa la Estancia y Villa la Valencia — equipos, resguardos con PDF firmado, solicitudes de alta, bajas individuales y masivas, reportes PDF/Excel y el control de toallas de albercas.

*Costa Clara* es Los Cabos de día: ordenado, luminoso y profesional. Fondo de arena, superficies blancas, un dorado de sol para lo que importa y el azul del mar de Cortés para la información. Está pensado para trabajar muchas horas frente a tablas grandes.

## Principios

1. **La tabla es la protagonista.** Mucho blanco, bordes finos (`borde`) en lugar de sombras, texto de tabla a 13px. Nada compite con los datos.
2. **El dorado se gana.** `dorado` solo marca la acción principal de la vista y el elemento activo del menú. Si hay dos botones dorados, uno sobra.
3. **El estado se lee sin color.** Cada estado lleva punto, texto e ícono; los colores refuerzan, nunca son el único mensaje.
4. **Siempre en contexto de hotel.** El selector global de hotel filtra todo; cada pantalla dice en qué hotel estás.
5. **Destruir cuesta un clic más.** Toda baja o eliminación pasa por un modal que nombra el objeto y explica la consecuencia.

## Voz y contenido

Toda la interfaz está en **español de México**, con *tú* implícito y verbos en infinitivo en botones.

- Botones: verbo + objeto — *Registrar equipo*, *Nuevo resguardo*, *Dar de baja*, *Generar reporte*. Nunca "OK" o "Enviar" a secas.
- Títulos en mayúscula inicial ("Equipos por departamento"), no Title Case. Encabezados de menú en MAYÚSCULAS (`seccion-menu`).
- Códigos de equipo tal cual: `VDA-CMP-00412` (hotel–tipo–consecutivo). Prefijos: VDA, VDP, VLE, VLV.
- Fechas "20 oct 2026", horas en 24 h ("17:40"), relativas en listas ("Hace 12 min", "Ayer, 17:40"). Números con coma de miles (1,204).
- Errores que digan cómo resolver: "Este número de serie ya está registrado (VDP-CMP-00412)." en lugar de "Error de validación".
- Estados del equipo, siempre con estos nombres: **Activo**, **En mantenimiento**, **En reparación**, **Dado de baja**.

## Fundamentos visuales

**Color.** Tema claro por defecto y oscuro opcional (mismo dorado como acento).

| Rol | Claro | Oscuro | Token |
|---|---|---|---|
| Fondo de página | #FAF7F2 | #141414 | `fondo` |
| Superficie | #FFFFFF | #1E1E1E | `superficie` |
| Acento | #B8893A | #B8893A | `dorado` |
| Información / enlaces | #1F6F8B | #4FA3C2 | `mar` |
| Éxito · Alerta · Peligro | #2E7D5B · #C47F17 · #B3412F | aclarados | `exito` · `alerta` · `peligro` |
| Texto · secundario | #1F1F1F · #6B6B6B | #F2EFEA · #A8A39B | `texto` · `texto-secundario` |
| Bordes | #E8E2D8 | #333333 | `borde` |

- **Texto sobre dorado** va en `sobre-dorado` (tinta oscura, 5.2:1). El blanco sobre #B8893A solo alcanza 3.1:1 y no pasa AA a 14px.
- **Dorado como texto** (menú activo, paginación) usa `dorado-texto`, más oscuro.
- `alerta` (#C47F17) no se usa para texto pequeño sobre blanco (3.0:1); para eso existe `alerta-texto`.
- Cada color de estado tiene trío *-suave / -texto / base* para chips y avisos. En oscuro los tonos base se aclaran para mantener 4.5:1.
- Las gráficas de estado usan `grafica-*`, validados para daltonismo; no se reutilizan como colores de serie.

**Tipografía.** Inter (Google Fonts, 400/500/600) con números tabulares. Títulos semibold, cuerpo 14px regular, tablas 13px. Escala: `display` 28 · `titulo-1` 22 · `titulo-2` 17 · `titulo-3` 15 · `cuerpo` 14 · `tabla` 13 · `etiqueta` 12 · `seccion-menu` 11.

**Espacio y forma.** Escala de 4px (`esp-1`…`esp-7`). Tarjetas con relleno `esp-5`, separación de cuadrícula `esp-5`, margen de página `esp-6` (escritorio) y `esp-4` (móvil). Radio por defecto `radio-md` = 10px; chips en `radio-pill`. Sombras mínimas: `sombra-1` en tarjetas, `sombra-2` solo para lo que flota (menús, toasts, modales).

**Disposición.** Barra lateral de 248px (72px colapsada) + barra superior de 60px + migas de pan. Contenido fluido hasta 1600px. Puntos de corte: 1280 (grillas 3:2 se apilan desde 1100), 1024 (la barra lateral pasa a cajón), 640 (móvil: menú inferior, tablas como tarjetas, controles de 44px).

**Modo oscuro.** Se activa desde el menú de usuario o la barra lateral y respeta la preferencia del sistema. No se invierten colores: cada token tiene su valor oscuro propio.

## Iconografía

Íconos de línea propios, 24×24, trazo 1.75 con extremos redondeados, en `currentColor`. Se usan a 18px en menús, 16px en botones y 14px en migas. El bundle (`window.CostaClara`) los expone: escribe `<i data-i="equipos"></i>` y llama `CostaClara.hidratar()`. Nombres: inicio, equipos, resguardos, solicitudes, baja, bajas-masivas, departamentos, areas, fases, reportes, usuarios, respaldo, toallas, hotel, buscar, campana, abajo, derecha, izquierda, menu, mas, puntos, filtro, columnas, descarga, subir, pdf, excel, cerrar, check, alerta, info, error, reloj, salir, sol, luna, colapsar, editar, ver, imagen, impresora, flecha, firma.

El logo es un **sol estilizado blanco** sobre baldosa dorada (`assets/Logos`). No hay emojis en la interfaz.

## Componentes

Botón, ChipEstado, TarjetaKPI, Gráficas, Tabla, EstadoVacio, Formulario, SubirArchivo, Toast, ModalConfirmacion, BarraLateral, BarraSuperior, ActividadReciente y SolicitudesPendientes, más dos pantallas completas: **PantallaInicio** (dashboard del administrador en escritorio) y **PantallaInicioMovil**. Son clases CSS (`components/bundle.css`, prefijo `cc-`) sobre los tokens; no requieren React.

## Pendiente de diseñar

Pantallas siguientes sugeridas: Equipos (tabla + detalle), Registrar equipo, Resguardos (con firma y PDF), Solicitudes de alta, Baja individual, Bajas masivas, Catálogos, Reportes, Usuarios, Respaldo, Control de toallas e inicio de sesión.
