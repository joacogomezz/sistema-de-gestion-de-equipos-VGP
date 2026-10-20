<?php 
require_once "Equipos.php";

class altasBajas{
	
	// Métodos para Altas de Equipos
	public function registrarAlta($datos){
		$c=new conectar();
		$conexion=$c->conexion();

		$fecha=date('Y-m-d');

		$sql="INSERT into altas_equipos (id_equipo,
										id_usuario,
										id_departamento,
										motivo_alta,
										fecha_alta,
										observaciones)
							values ('$datos[0]',
									'$datos[1]',
									'$datos[2]',
									'$datos[3]',
									'$fecha',
									'$datos[4]')";
		return mysqli_query($conexion,$sql);
	}

	public function obtenerDatosAlta($idalta){
		$c=new conectar();
		$conexion=$c->conexion();

		$sql="SELECT a.id_alta, a.id_equipo, a.id_departamento, a.motivo_alta, 
						a.fecha_alta, a.observaciones, e.codigo_equipo, e.descripcion, 
						e.marca, e.modelo, d.nombre as nombre_departamento
				from altas_equipos a
				inner join equipos e on a.id_equipo = e.id_equipo
				left join departamentos d on a.id_departamento = d.id_departamento
				where a.id_alta='$idalta'";
		$result=mysqli_query($conexion,$sql);

		$ver=mysqli_fetch_row($result);

		$datos=array(
			'id_alta' => $ver[0],
			'id_equipo' => $ver[1],
			'id_departamento' => $ver[2],
			'motivo_alta' => $ver[3],
			'fecha_alta' => $ver[4],
			'observaciones' => $ver[5],
			'codigo_equipo' => $ver[6],
			'descripcion' => $ver[7],
			'marca' => $ver[8],
			'modelo' => $ver[9],
			'nombre_departamento' => $ver[10]
		);

		return $datos;
	}

	// Métodos para Bajas de Equipos
	public function registrarBaja($datos){
		$c=new conectar();
		$conexion=$c->conexion();

		$fecha=date('Y-m-d');

		// Manejar valores nulos
		$id_departamento = $datos[2] ? "'$datos[2]'" : "NULL";
		$observaciones = $datos[7] ? "'$datos[7]'" : "NULL";
		$foto_equipo = $datos[8] ? "'$datos[8]'" : "NULL";
		$foto_etiqueta = $datos[9] ? "'$datos[9]'" : "NULL";
		$tecnico_reviso = $datos[10] ? "'$datos[10]'" : "NULL";
		$gerente_area = $datos[11] ? "'$datos[11]'" : "NULL";
		$fecha_adquisicion = (isset($datos[12]) && $datos[12] !== null && $datos[12] !== '') ? "'" . mysqli_real_escape_string($conexion, $datos[12]) . "'" : "NULL";

		$col_fecha_adq = mysqli_query($conexion, "SHOW COLUMNS FROM bajas_equipos LIKE 'fecha_adquisicion'");
		$tiene_fecha_adq = $col_fecha_adq && mysqli_num_rows($col_fecha_adq) > 0;
		$chkHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM bajas_equipos LIKE 'id_hotel'");
		$chkNumero = @mysqli_query($conexion, "SHOW COLUMNS FROM bajas_equipos LIKE 'numero_baja_hotel'");
		$usaHotel = $chkHotel && mysqli_num_rows($chkHotel) > 0 && $chkNumero && mysqli_num_rows($chkNumero) > 0;

		$id_hotel_sql = '';
		$numero_baja_sql = '';
		if ($usaHotel) {
			$id_hotel = null;
			if ($datos[2]) {
				$resD = mysqli_query($conexion, "SELECT id_hotel FROM departamentos WHERE id_departamento='" . mysqli_real_escape_string($conexion, $datos[2]) . "' LIMIT 1");
				if ($resD && $rowD = mysqli_fetch_row($resD)) $id_hotel = (int)$rowD[0];
			}
			if ($id_hotel === null) {
				$resE = mysqli_query($conexion, "SELECT d.id_hotel FROM equipos e INNER JOIN departamentos d ON e.id_departamento = d.id_departamento WHERE e.id_equipo='" . mysqli_real_escape_string($conexion, $datos[0]) . "' LIMIT 1");
				if ($resE && $rowE = mysqli_fetch_row($resE)) $id_hotel = (int)$rowE[0];
			}
			if ($id_hotel !== null) {
				$resNum = mysqli_query($conexion, "SELECT COALESCE(MAX(numero_baja_hotel),0)+1 FROM bajas_equipos WHERE id_hotel='" . $id_hotel . "'");
				$numero = 1;
				if ($resNum && $rowNum = mysqli_fetch_row($resNum)) $numero = (int)$rowNum[0];
				$id_hotel_sql = "id_hotel, numero_baja_hotel, ";
				$numero_baja_sql = "'$id_hotel', '$numero', ";
			}
		}

		if ($tiene_fecha_adq) {
			$sql="INSERT into bajas_equipos (" . $id_hotel_sql . "id_equipo, id_usuario, id_departamento, tipo_baja, motivo_baja, fecha_baja, valor_residual, destino_equipo, observaciones, foto_equipo, foto_etiqueta, tecnico_reviso, gerente_area, fecha_adquisicion)
				values (" . $numero_baja_sql . "'$datos[0]','$datos[1]',$id_departamento,'$datos[3]','$datos[4]','$fecha','$datos[5]','$datos[6]',$observaciones,$foto_equipo,$foto_etiqueta,$tecnico_reviso,$gerente_area,$fecha_adquisicion)";
		} else {
			$sql="INSERT into bajas_equipos (" . $id_hotel_sql . "id_equipo, id_usuario, id_departamento, tipo_baja, motivo_baja, fecha_baja, valor_residual, destino_equipo, observaciones, foto_equipo, foto_etiqueta, tecnico_reviso, gerente_area)
				values (" . $numero_baja_sql . "'$datos[0]','$datos[1]',$id_departamento,'$datos[3]','$datos[4]','$fecha','$datos[5]','$datos[6]',$observaciones,$foto_equipo,$foto_etiqueta,$tecnico_reviso,$gerente_area)";
		}
		
		error_log("SQL de inserción: " . $sql);
		$result = mysqli_query($conexion,$sql);

		if($result){
			// Cambiar estado del equipo a "Dado de Baja"
			$objEquipos = new equipos();
			$cambioEstado = $objEquipos->cambiarEstadoEquipo($datos[0], 'Dado de Baja');
			error_log("Cambio de estado del equipo: " . ($cambioEstado ? "exitoso" : "fallido"));
		} else {
			error_log("Error en inserción de baja: " . mysqli_error($conexion));
		}

		return $result;
	}

	public function registrarBajaMasiva($equipos, $datos, $observacionesEquipos = array()){
		$c=new conectar();
		$conexion=$c->conexion();

		$fecha=date('Y-m-d');
		$resultados = array();

		// Calcular valor residual total basado en los equipos seleccionados
		$valorResidualTotal = 0;
		$valorResidualIndividual = 0;
		
		if(count($equipos) > 0) {
			// Obtener el valor total de los equipos seleccionados
			$equiposIds = implode(',', $equipos);
			$sqlValores = "SELECT SUM(valor_adquisicion) as total_valor FROM equipos WHERE id_equipo IN ($equiposIds)";
			$resultValores = mysqli_query($conexion, $sqlValores);
			$rowValores = mysqli_fetch_row($resultValores);
			$valorResidualTotal = $rowValores[0];
			$valorResidualIndividual = $valorResidualTotal / count($equipos);
		}

		// Manejar departamento nulo
		$departamento = $datos[1] ? "'$datos[1]'" : "NULL";
		
		// Manejar hotel_area
		$hotelArea = $datos[2] ? "'$datos[2]'" : "NULL";

		$chkHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM bajas_masivas LIKE 'id_hotel'");
		$chkNumero = @mysqli_query($conexion, "SHOW COLUMNS FROM bajas_masivas LIKE 'numero_baja_masiva_hotel'");
		$usaHotel = $chkHotel && mysqli_num_rows($chkHotel) > 0 && $chkNumero && mysqli_num_rows($chkNumero) > 0;
		$id_hotel_sql = '';
		$numero_sql = '';
		if ($usaHotel && $datos[1]) {
			$resD = mysqli_query($conexion, "SELECT id_hotel FROM departamentos WHERE id_departamento='" . mysqli_real_escape_string($conexion, $datos[1]) . "' LIMIT 1");
			if ($resD && $rowD = mysqli_fetch_row($resD)) {
				$id_hotel = (int)$rowD[0];
				$resNum = mysqli_query($conexion, "SELECT COALESCE(MAX(numero_baja_masiva_hotel),0)+1 FROM bajas_masivas WHERE id_hotel='" . $id_hotel . "'");
				$numero = 1;
				if ($resNum && $rowNum = mysqli_fetch_row($resNum)) $numero = (int)$rowNum[0];
				$id_hotel_sql = "id_hotel, numero_baja_masiva_hotel, ";
				$numero_sql = "'$id_hotel', '$numero', ";
			}
		}

		// 1. Crear el registro principal de baja masiva
		$sql="INSERT into bajas_masivas (" . $id_hotel_sql . "id_usuario,
										id_departamento,
										hotel_area,
										fecha_baja,
										motivo_baja,
										valor_residual_total,
										destino_equipo,
										observaciones,
										total_equipos)
							values (" . $numero_sql . "'$datos[0]',
									$departamento,
									$hotelArea,
									'$fecha',
									'$datos[3]',
									'$valorResidualTotal',
									'$datos[5]',
									'$datos[6]',
									'" . count($equipos) . "')";
		
		$result = mysqli_query($conexion,$sql);
		
		if($result){
			$id_baja_masiva = mysqli_insert_id($conexion);
			
			// Comprobar si existe columna observaciones en bajas_masivas_equipos
			$tieneColumnaObservaciones = false;
			$resCol = @mysqli_query($conexion, "SHOW COLUMNS FROM bajas_masivas_equipos LIKE 'observaciones'");
			if ($resCol && mysqli_fetch_row($resCol)) {
				$tieneColumnaObservaciones = true;
			}

			// 2. Crear registros de relación para cada equipo (con observación por equipo si existe columna)
			for($i=0; $i < count($equipos); $i++){
				$obs = isset($observacionesEquipos[$i]) ? mysqli_real_escape_string($conexion, $observacionesEquipos[$i]) : '';
				$obsSql = $obs !== '' ? "'$obs'" : "NULL";
				if ($tieneColumnaObservaciones) {
					$sqlRelacion="INSERT into bajas_masivas_equipos (id_baja_masiva, id_equipo, valor_residual_individual, observaciones)
						values ('$id_baja_masiva','$equipos[$i]','$valorResidualIndividual',$obsSql)";
				} else {
					$sqlRelacion="INSERT into bajas_masivas_equipos (id_baja_masiva, id_equipo, valor_residual_individual)
						values ('$id_baja_masiva','$equipos[$i]','$valorResidualIndividual')";
				}
				$resultRelacion = mysqli_query($conexion,$sqlRelacion);

				if($resultRelacion){
					// Cambiar estado del equipo a "Dado de Baja"
					$objEquipos = new equipos();
					$objEquipos->cambiarEstadoEquipo($equipos[$i], 'Dado de Baja');
					$resultados[] = $equipos[$i];
				}
			}
		}

		return $resultados;
	}

	public function obtenerDatosBaja($idbaja){
		$c=new conectar();
		$conexion=$c->conexion();

		$sql="SELECT b.id_baja, b.id_equipo, b.id_departamento, b.tipo_baja, 
						b.motivo_baja, b.fecha_baja, b.valor_residual, 
						b.destino_equipo, b.observaciones, e.codigo_equipo, 
						e.descripcion, e.marca, e.modelo, e.valor_adquisicion,
						d.nombre as nombre_departamento
				from bajas_equipos b
				inner join equipos e on b.id_equipo = e.id_equipo
				left join departamentos d on b.id_departamento = d.id_departamento
				where b.id_baja='$idbaja'";
		$result=mysqli_query($conexion,$sql);

		$ver=mysqli_fetch_row($result);

		$datos=array(
			'id_baja' => $ver[0],
			'id_equipo' => $ver[1],
			'id_departamento' => $ver[2],
			'tipo_baja' => $ver[3],
			'motivo_baja' => $ver[4],
			'fecha_baja' => $ver[5],
			'valor_residual' => $ver[6],
			'destino_equipo' => $ver[7],
			'observaciones' => $ver[8],
			'codigo_equipo' => $ver[9],
			'descripcion' => $ver[10],
			'marca' => $ver[11],
			'modelo' => $ver[12],
			'valor_adquisicion' => $ver[13],
			'nombre_departamento' => $ver[14]
		);

		return $datos;
	}

	// Métodos para Reportes
	public function obtenerAltasPorFecha($fechaInicio, $fechaFin){
		$c=new conectar();
		$conexion=$c->conexion();

		$sql="SELECT a.id_alta, a.fecha_alta, e.codigo_equipo, e.descripcion, 
						e.marca, e.modelo, e.valor_adquisicion, d.nombre as departamento,
						a.motivo_alta, a.observaciones
				from altas_equipos a
				inner join equipos e on a.id_equipo = e.id_equipo
				left join departamentos d on a.id_departamento = d.id_departamento
				where a.fecha_alta between '$fechaInicio' and '$fechaFin'
				order by a.fecha_alta desc";

		return mysqli_query($conexion,$sql);
	}

	public function obtenerBajasPorFecha($fechaInicio, $fechaFin, $tipoBaja = null){
		$c=new conectar();
		$conexion=$c->conexion();

		$sql="SELECT b.id_baja, b.fecha_baja, b.tipo_baja, e.codigo_equipo, 
						e.descripcion, e.marca, e.modelo, e.valor_adquisicion, 
						b.valor_residual, b.destino_equipo, d.nombre as departamento,
						b.motivo_baja, b.observaciones
				from bajas_equipos b
				inner join equipos e on b.id_equipo = e.id_equipo
				left join departamentos d on b.id_departamento = d.id_departamento
				where b.fecha_baja between '$fechaInicio' and '$fechaFin'";

		if($tipoBaja){
			$sql .= " and b.tipo_baja='$tipoBaja'";
		}

		$sql .= " order by b.fecha_baja desc";

		return mysqli_query($conexion,$sql);
	}

	public function obtenerBajasMasivasPorFecha($fechaInicio, $fechaFin){
		$c=new conectar();
		$conexion=$c->conexion();

		$sql="SELECT b.id_baja, b.fecha_baja, e.codigo_equipo, e.descripcion, 
						e.marca, e.modelo, e.valor_adquisicion, b.valor_residual, 
						b.destino_equipo, d.nombre as departamento, b.motivo_baja
				from bajas_equipos b
				inner join equipos e on b.id_equipo = e.id_equipo
				left join departamentos d on b.id_departamento = d.id_departamento
				where b.tipo_baja='Masiva' 
				and b.fecha_baja between '$fechaInicio' and '$fechaFin'
				order by b.fecha_baja desc";

		return mysqli_query($conexion,$sql);
	}

	public function obtenerResumenAltasBajas($fechaInicio, $fechaFin){
		$c=new conectar();
		$conexion=$c->conexion();

		// Contar altas
		$sqlAltas="SELECT COUNT(*) as total_altas, SUM(e.valor_adquisicion) as valor_total_altas
					from altas_equipos a
					inner join equipos e on a.id_equipo = e.id_equipo
					where a.fecha_alta between '$fechaInicio' and '$fechaFin'";
		$resultAltas=mysqli_query($conexion,$sqlAltas);
		$altas=mysqli_fetch_row($resultAltas);

		// Contar bajas individuales
		$sqlBajasInd="SELECT COUNT(*) as total_bajas_ind, SUM(b.valor_residual) as valor_total_bajas_ind
						from bajas_equipos b
						where b.tipo_baja='Individual' 
						and b.fecha_baja between '$fechaInicio' and '$fechaFin'";
		$resultBajasInd=mysqli_query($conexion,$sqlBajasInd);
		$bajasInd=mysqli_fetch_row($resultBajasInd);

		// Contar bajas masivas
		$sqlBajasMas="SELECT COUNT(*) as total_bajas_mas, SUM(b.valor_residual) as valor_total_bajas_mas
						from bajas_equipos b
						where b.tipo_baja='Masiva' 
						and b.fecha_baja between '$fechaInicio' and '$fechaFin'";
		$resultBajasMas=mysqli_query($conexion,$sqlBajasMas);
		$bajasMas=mysqli_fetch_row($resultBajasMas);

		$resumen=array(
			'total_altas' => $altas[0],
			'valor_total_altas' => $altas[1],
			'total_bajas_individuales' => $bajasInd[0],
			'valor_total_bajas_ind' => $bajasInd[1],
			'total_bajas_masivas' => $bajasMas[0],
			'valor_total_bajas_mas' => $bajasMas[1]
		);

		return $resumen;
	}

	// Método para obtener equipos disponibles para baja (filtrado por hotel si aplica)
	public function obtenerEquiposDisponiblesParaBaja($id_hotel = null){
		$c=new conectar();
		$conexion=$c->conexion();

		$whereHotel = '';
		if ($id_hotel !== null) {
			$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			if ($chk && mysqli_num_rows($chk) > 0) {
				$whereHotel = " AND d.id_hotel = '" . intval($id_hotel) . "'";
			}
		}
		$sql="SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
						e.estado, e.ubicacion, e.valor_adquisicion, d.nombre as departamento
				from equipos e
				left join departamentos d on e.id_departamento = d.id_departamento
				where e.estado != 'Dado de Baja' $whereHotel
				order by e.descripcion";

		return mysqli_query($conexion,$sql);
	}

	// Método para obtener datos de una baja masiva específica
	public function obtenerDatosBajaMasiva($id_baja_masiva){
		$c=new conectar();
		$conexion=$c->conexion();

		$sql="SELECT bm.id_baja_masiva, bm.id_usuario, bm.id_departamento, 
						bm.hotel_area, bm.fecha_baja, bm.motivo_baja, bm.valor_residual_total,
						bm.destino_equipo, bm.observaciones, bm.total_equipos,
						u.nombre as nombre_usuario, u.apellido as apellido_usuario,
						d.nombre as nombre_departamento
				from bajas_masivas bm
				left join usuarios u on bm.id_usuario = u.id_usuario
				left join departamentos d on bm.id_departamento = d.id_departamento
				where bm.id_baja_masiva='$id_baja_masiva'";
		
		$result=mysqli_query($conexion,$sql);
		$ver=mysqli_fetch_row($result);

		if($ver){
			$datos=array(
				'id_baja_masiva' => $ver[0],
				'id_usuario' => $ver[1],
				'id_departamento' => $ver[2],
				'hotel_area' => $ver[3],
				'fecha_baja' => $ver[4],
				'motivo_baja' => $ver[5],
				'valor_residual_total' => $ver[6],
				'destino_equipo' => $ver[7],
				'observaciones' => $ver[8],
				'total_equipos' => $ver[9],
				'nombre_usuario' => $ver[10],
				'apellido_usuario' => $ver[11],
				'nombre_departamento' => $ver[12]
			);
			return $datos;
		}
		return false;
	}

	// Método para obtener equipos de una baja masiva (incluye observación por equipo si existe la columna)
	public function obtenerEquiposBajaMasiva($id_baja_masiva){
		$c=new conectar();
		$conexion=$c->conexion();

		$tieneColumnaObservaciones = false;
		$resCol = @mysqli_query($conexion, "SHOW COLUMNS FROM bajas_masivas_equipos LIKE 'observaciones'");
		if ($resCol && mysqli_fetch_row($resCol)) {
			$tieneColumnaObservaciones = true;
		}

		$campoObservaciones = $tieneColumnaObservaciones ? ", bme.observaciones" : "";
		$sql="SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo,
						e.estado, e.ubicacion, e.valor_adquisicion, e.fecha_ingreso,
						e.numero_serie, e.tipo_moneda, d.nombre as departamento,
						bme.valor_residual_individual" . $campoObservaciones . "
				from bajas_masivas_equipos bme
				inner join equipos e on bme.id_equipo = e.id_equipo
				left join departamentos d on e.id_departamento = d.id_departamento
				where bme.id_baja_masiva='$id_baja_masiva'
				order by e.codigo_equipo";

		return mysqli_query($conexion,$sql);
	}

	// Método para obtener todas las bajas masivas (filtrado por hotel si aplica)
	// $nombre_hotel: cuando id_hotel está definido, se usan también las filas con id_departamento NULL y hotel_area = nombre_hotel
	public function obtenerTodasBajasMasivas($id_hotel = null, $nombre_hotel = null){
		$c=new conectar();
		$conexion=$c->conexion();

		$whereHotel = '';
		if ($id_hotel !== null) {
			$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			if ($chk && mysqli_num_rows($chk) > 0) {
				$id_hotel = intval($id_hotel);
				$nombre_esc = $nombre_hotel ? "'" . mysqli_real_escape_string($conexion, $nombre_hotel) . "'" : "NULL";
				$whereHotel = " AND ( (d.id_hotel = '$id_hotel') OR (bm.id_departamento IS NULL AND bm.hotel_area = $nombre_esc) )";
			}
		}
		$chkNumero = @mysqli_query($conexion, "SHOW COLUMNS FROM bajas_masivas LIKE 'numero_baja_masiva_hotel'");
		$campoNumero = ($chkNumero && mysqli_num_rows($chkNumero) > 0) ? "bm.numero_baja_masiva_hotel, " : "";
		$sql="SELECT bm.id_baja_masiva, " . $campoNumero . "bm.fecha_baja, bm.motivo_baja,
						bm.valor_residual_total, bm.destino_equipo, bm.observaciones,
						bm.total_equipos, bm.hotel_area, u.nombre as nombre_usuario, u.apellido as apellido_usuario,
						d.nombre as nombre_departamento
				from bajas_masivas bm
				left join usuarios u on bm.id_usuario = u.id_usuario
				left join departamentos d on bm.id_departamento = d.id_departamento
				where 1=1 $whereHotel
				order by bm.id_baja_masiva desc";

		return mysqli_query($conexion,$sql);
	}
}

?>
