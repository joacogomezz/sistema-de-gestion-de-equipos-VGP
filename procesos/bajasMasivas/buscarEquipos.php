<?php 
	// Incluir archivo de seguridad
	require_once "../../seguridad.php";
	
	// Requerir autenticación
	requerirAutenticacion();
	
	require_once "../../clases/Conexion.php";
	
	$c = new conectar();
	$conexion = $c->conexion();
	
	$tipo = $_POST['tipo'];
	$valor = $_POST['valor'];
	
	if($tipo == 'codigo') {
		// Buscar por código exacto o parcial - SOLO equipos ya dados de baja individualmente
		$sql = "SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
						e.valor_adquisicion, d.nombre as departamento, e.estado, e.ubicacion
				FROM equipos e
				LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
				WHERE (e.codigo_equipo LIKE '%$valor%' OR e.codigo_equipo = '$valor')
				AND e.estado = 'Dado de Baja'
				ORDER BY e.codigo_equipo
				LIMIT 20";
	} else if($tipo == 'numero_serie') {
		// Buscar por número de serie - SOLO equipos ya dados de baja individualmente
		$sql = "SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
						e.valor_adquisicion, d.nombre as departamento, e.estado, e.ubicacion
				FROM equipos e
				LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
				WHERE (e.numero_serie LIKE '%$valor%' OR e.numero_serie = '$valor')
				AND e.estado = 'Dado de Baja'
				ORDER BY e.numero_serie
				LIMIT 20";
	}
	
	$result = mysqli_query($conexion, $sql);
	
	if(mysqli_num_rows($result) > 0) {
		echo '<div class="table-responsive">';
		echo '<table style="width: 100%; border-collapse: collapse; color: white !important;">';
		echo '<tr style="background-color: #5bc0de; color: white;">';
		echo '<th style="padding: 8px; border: 1px solid #ddd;">Código</th>';
		echo '<th style="padding: 8px; border: 1px solid #ddd;">Descripción</th>';
		echo '<th style="padding: 8px; border: 1px solid #ddd;">Marca/Modelo</th>';
		echo '<th style="padding: 8px; border: 1px solid #ddd;">Valor</th>';
		echo '<th style="padding: 8px; border: 1px solid #ddd;">Departamento</th>';
		echo '<th style="padding: 8px; border: 1px solid #ddd;">Estado</th>';
		echo '<th style="padding: 8px; border: 1px solid #ddd;">Ubicación física</th>';
		echo '<th style="padding: 8px; border: 1px solid #ddd;">Acción</th>';
		echo '</tr>';
		
		while($equipo = mysqli_fetch_row($result)) {
			// Todos los equipos mostrados están dados de baja
			$labelClass = 'label-danger';
			$estadoTexto = 'Dado de Baja';
			
			echo '<tr style="background-color: #555; color: white;">';
			echo '<td style="padding: 8px; border: 1px solid #ddd; color: white;"><strong>' . $equipo[1] . '</strong></td>';
			echo '<td style="padding: 8px; border: 1px solid #ddd; color: white;">' . $equipo[2] . '</td>';
			echo '<td style="padding: 8px; border: 1px solid #ddd; color: white;">' . $equipo[3] . ' ' . $equipo[4] . '</td>';
			echo '<td style="padding: 8px; border: 1px solid #ddd; color: white;">$' . number_format($equipo[5], 2) . '</td>';
			echo '<td style="padding: 8px; border: 1px solid #ddd; color: white;">' . ($equipo[6] ? $equipo[6] : 'No asignado') . '</td>';
			echo '<td style="padding: 8px; border: 1px solid #ddd;"><span class="label ' . $labelClass . '">' . $estadoTexto . '</span></td>';
			echo '<td style="padding: 8px; border: 1px solid #ddd; color: white;">' . ($equipo[8] ? $equipo[8] : 'N/A') . '</td>';
			echo '<td style="padding: 8px; border: 1px solid #ddd;">';
			
			// Todos los equipos mostrados se incluyen en el reporte masivo
			echo '<button type="button" class="btn btn-xs btn-info" onclick="agregarEquipo(' . 
				 $equipo[0] . ', \'' . $equipo[1] . '\', \'' . addslashes($equipo[2]) . '\', ' . 
				 $equipo[5] . ', \'' . addslashes($equipo[6]) . '\', \'' . $equipo[7] . '\')">';
			echo '<i class="glyphicon glyphicon-plus"></i> Incluir en Reporte';
			echo '</button>';
			
			echo '</td>';
			echo '</tr>';
		}
		
		echo '</table>';
		echo '</div>';
		echo '<p class="text-muted">Se encontraron ' . mysqli_num_rows($result) . ' equipos</p>';
		
	} else {
		echo '<div class="alert alert-warning">';
		echo '<i class="glyphicon glyphicon-exclamation-sign"></i> ';
		if($tipo == 'codigo') {
			echo 'No se encontraron equipos con el código: <strong>' . $valor . '</strong>';
		} else {
			echo 'No se encontraron equipos con el número de serie: <strong>' . $valor . '</strong>';
		}
		echo '</div>';
		echo '<p class="text-muted">Sugerencias:</p>';
		echo '<ul class="text-muted">';
		echo '<li>Verifica que el código o número de serie esté escrito correctamente</li>';
		echo '<li>Intenta con una búsqueda más general</li>';
		echo '<li>Los equipos ya dados de baja pueden incluirse en el reporte masivo</li>';
		echo '</ul>';
	}
?>
