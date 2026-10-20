# Tabla
Tabla de datos para inventario: barra de herramientas (búsqueda, filtros en chip, columnas, exportar, acción primaria), filas de 44px, selección múltiple, menú "⋯" por fila y paginación.

- Texto `tabla` (13/18). Encabezado en `superficie-hundida`, fijo al hacer scroll; divisores `borde`; hover de fila `superficie-hundida`; fila seleccionada `dorado-suave`.
- La primera columna de datos es el **código** del equipo (peso 500); los números de serie y cifras van con números tabulares.
- **Filtros** como chips punteados; al activarse quedan en `dorado-suave` con el valor elegido ("Hotel: Villa del Arco").
- **Columnas ocultables** desde el botón *Columnas* (lista de casillas); la preferencia se recuerda por usuario.
- **Menú "⋯"**: Ver detalle, Editar, Asignar resguardo, Enviar a mantenimiento, separador y *Dar de baja* en `peligro` (abre ModalConfirmacion).
- Con selección múltiple la barra muestra acciones masivas (Baja masiva, Exportar selección).
- Sin resultados: **EstadoVacio** dentro del cuerpo de la tabla. En móvil, cada fila se vuelve una tarjeta.
