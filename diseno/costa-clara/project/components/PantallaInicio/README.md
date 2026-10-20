# PantallaInicio
Dashboard (Inicio) del Super Administrador: saludo, accesos rápidos, 4 KPI, gráficas por departamento y por estado, actividad reciente, solicitudes pendientes y reportes ejecutivos.

**Orden de lectura (de arriba abajo):** saludo con hotel seleccionado → accesos rápidos (la acción más frecuente, *Registrar equipo*, es la única dorada) → KPI → gráficas → actividad y pendientes.

- Cuadrícula de 12 columnas con separación `esp-5`; margen de página `esp-6`. Las filas de gráficas y actividad van 3:2.
- Los KPI muestran cifras con números tabulares (estilo `kpi`); el pie explica el número (p. ej. *22 en mantenimiento · 9 en reparación*).
- Todo responde al **selector global de hotel**: al elegir un hotel, el subtítulo, KPI, gráficas y listas se filtran; los chips de hoteles del encabezado desaparecen.
- Un usuario de hotel (rol = nombre del hotel) ve la misma pantalla sin la sección ADMINISTRACIÓN y con el selector fijo en su hotel.
- Debajo de 1100px la fila de 4 KPI pasa a 2×2 y las filas 3:2 se apilan; en móvil ver **PantallaInicioMovil**.
