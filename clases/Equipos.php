<?php 

	class equipos{

		/** Añade usuario_registro y numero_factura si la BD aún no los tiene (instalaciones antiguas). */
		private function asegurarColumnasRegistroEquipo($conexion) {
			static $hecho = false;
			if ($hecho) {
				return;
			}
			$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM equipos LIKE 'usuario_registro'");
			if (!$chk || mysqli_num_rows($chk) === 0) {
				@mysqli_query($conexion, "ALTER TABLE equipos ADD COLUMN usuario_registro varchar(200) NULL COMMENT 'Usuario (texto libre)' AFTER fecha_registro");
			}
			$chk2 = @mysqli_query($conexion, "SHOW COLUMNS FROM equipos LIKE 'numero_factura'");
			if (!$chk2 || mysqli_num_rows($chk2) === 0) {
				@mysqli_query($conexion, "ALTER TABLE equipos ADD COLUMN numero_factura varchar(120) NULL COMMENT 'Número de factura' AFTER usuario_registro");
			}
			$hecho = true;
		}

		public function ensureRegistroEquiposCols() {
			$c = new conectar();
			$this->asegurarColumnasRegistroEquipo($c->conexion());
		}

		private function existeCodigoEquipo($conexion, $codigo){
			$codigoEsc = mysqli_real_escape_string($conexion, $codigo);
			$sql = "SELECT id_equipo FROM equipos WHERE codigo_equipo = '$codigoEsc' LIMIT 1";
			$res = mysqli_query($conexion, $sql);
			return ($res && mysqli_num_rows($res) > 0);
		}

		private function generarCodigoAutomatico($conexion, $id_hotel = null){
			$prefijo = 'AUTO-EQ';
			if ($id_hotel !== null) {
				$prefijo .= '-H' . (int)$id_hotel;
			}

			$intentos = 0;
			do {
				$intentos++;
				$codigo = $prefijo . '-' . date('YmdHis') . '-' . mt_rand(100, 999);
				if (!$this->existeCodigoEquipo($conexion, $codigo)) {
					return $codigo;
				}
			} while ($intentos < 10);

			return $prefijo . '-' . uniqid();
		}

		/** Valores que no deben tratarse como serie única (placeholders de Excel). */
		private function numeroSerieEsSignificativo($numeroSerie) {
			$serie = trim((string)$numeroSerie);
			if ($serie === '' || strtolower($serie) === 'null') {
				return false;
			}
			$norm = strtoupper(preg_replace('/\s+/', ' ', $serie));
			$placeholders = array(
				'N/A', 'NA', 'N A', 'S/N', 'SN', 'S N', 'NO', 'NINGUNO', 'NINGUNA', 'NONE',
				'-', '--', '0', 'SIN SERIE', 'SIN NUMERO', 'SIN NUMERO DE SERIE', 'NO APLICA',
				'NOAPLICA', 'PENDIENTE', 'POR ASIGNAR', 'S/N.', 'N.A.'
			);
			return !in_array($norm, $placeholders, true);
		}

		private function sqlFiltroHotelEquipo($id_hotel, $aliasEquipo = 'e', $aliasDepto = 'd') {
			$hid = (int)$id_hotel;
			if ($hid <= 0) {
				return '';
			}
			return " AND ({$aliasEquipo}.id_hotel = '{$hid}' OR ({$aliasEquipo}.id_hotel IS NULL AND {$aliasDepto}.id_hotel = '{$hid}')) ";
		}

		private function existeNumeroSerie($conexion, $numeroSerie, $id_hotel = null){
			if (!$this->numeroSerieEsSignificativo($numeroSerie)) {
				return false;
			}
			$serieEsc = mysqli_real_escape_string($conexion, trim((string)$numeroSerie));
			if ($id_hotel !== null && (int)$id_hotel > 0) {
				$hid = (int)$id_hotel;
				$sql = "SELECT e.id_equipo FROM equipos e
						LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
						WHERE e.numero_serie = '$serieEsc'" . $this->sqlFiltroHotelEquipo($hid) . " LIMIT 1";
			} else {
				$sql = "SELECT id_equipo FROM equipos WHERE numero_serie = '$serieEsc' LIMIT 1";
			}
			$res = mysqli_query($conexion, $sql);
			return ($res && mysqli_num_rows($res) > 0);
		}

		public function esNumeroSerieSignificativo($numeroSerie) {
			return $this->numeroSerieEsSignificativo($numeroSerie);
		}

		/** Para importación CSV: devuelve codigo_equipo si la serie ya existe en el mismo hotel. */
		public function obtenerCodigoEquipoPorSerieEnHotel($numeroSerie, $id_hotel) {
			if (!$this->numeroSerieEsSignificativo($numeroSerie) || $id_hotel === null || (int)$id_hotel <= 0) {
				return null;
			}
			$c = new conectar();
			$conexion = $c->conexion();
			$serieEsc = mysqli_real_escape_string($conexion, trim((string)$numeroSerie));
			$hid = (int)$id_hotel;
			$sql = "SELECT e.codigo_equipo FROM equipos e
					LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
					WHERE e.numero_serie = '$serieEsc'" . $this->sqlFiltroHotelEquipo($hid) . " LIMIT 1";
			$res = mysqli_query($conexion, $sql);
			if ($res && ($row = mysqli_fetch_row($res))) {
				return $row[0];
			}
			return null;
		}

		/**
		 * Valida que el equipo tenga departamento existente (y del hotel de sesión si aplica).
		 * @return true|string Código de error: SIN_DEPARTAMENTO, DEPARTAMENTO_NO_EXISTE, DEPARTAMENTO_OTRO_HOTEL
		 */
		private function validarDepartamentoParaAlta($conexion, $id_departamento, $id_hotel_sesion = null) {
			if ($id_departamento === null || (int)$id_departamento <= 0) {
				return 'SIN_DEPARTAMENTO';
			}
			$idEsc = (int)$id_departamento;
			$chkHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			$usaHotelDepto = $chkHotel && mysqli_num_rows($chkHotel) > 0;
			$sql = $usaHotelDepto
				? "SELECT id_departamento, id_hotel FROM departamentos WHERE id_departamento='$idEsc' LIMIT 1"
				: "SELECT id_departamento FROM departamentos WHERE id_departamento='$idEsc' LIMIT 1";
			$res = mysqli_query($conexion, $sql);
			if (!$res || mysqli_num_rows($res) === 0) {
				return 'DEPARTAMENTO_NO_EXISTE';
			}
			if ($usaHotelDepto && $id_hotel_sesion !== null && (int)$id_hotel_sesion > 0) {
				$row = mysqli_fetch_assoc($res);
				if ((int)($row['id_hotel'] ?? 0) !== (int)$id_hotel_sesion) {
					return 'DEPARTAMENTO_OTRO_HOTEL';
				}
			}
			return true;
		}

		public function mensajeErrorDepartamentoEquipo($codigo) {
			$map = array(
				'SIN_DEPARTAMENTO' => 'Debe seleccionar un departamento válido. Registre primero sus departamentos en el menú Departamentos.',
				'DEPARTAMENTO_NO_EXISTE' => 'El departamento indicado no existe. Registre o seleccione un departamento válido.',
				'DEPARTAMENTO_OTRO_HOTEL' => 'El departamento no pertenece a su hotel.',
			);
			return isset($map[$codigo]) ? $map[$codigo] : 'Departamento no válido para registrar el equipo.';
		}

		public function insertaEquipo($datos){
			$c=new conectar();
			$conexion=$c->conexion();
			$this->asegurarColumnasRegistroEquipo($conexion);
			$toSqlNullableString = function($valor) use ($conexion) {
				$v = isset($valor) ? trim((string)$valor) : '';
				if ($v === '' || strtolower($v) === 'null') {
					return "NULL";
				}
				return "'" . mysqli_real_escape_string($conexion, $v) . "'";
			};

			$fecha=date('Y-m-d');
			$id_departamento_raw = isset($datos[1]) ? trim((string)$datos[1]) : '';
			$id_departamento = ($id_departamento_raw === '' || strtolower($id_departamento_raw) === 'null') ? null : (int)$id_departamento_raw;
			$id_hotel_sesion = isset($datos[15]) && (int)$datos[15] > 0 ? (int)$datos[15] : null;
			$valDepto = $this->validarDepartamentoParaAlta($conexion, $id_departamento, $id_hotel_sesion);
			if ($valDepto !== true) {
				return $valDepto;
			}
			$idDepartamentoSql = "'" . (int)$id_departamento . "'";
			$chkHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM equipos LIKE 'id_hotel'");
			$chkNumero = @mysqli_query($conexion, "SHOW COLUMNS FROM equipos LIKE 'numero_equipo_hotel'");
			$usaHotel = $chkHotel && mysqli_num_rows($chkHotel) > 0 && $chkNumero && mysqli_num_rows($chkNumero) > 0;

			$id_hotel = null;
			$numero = 1;
			if ($usaHotel) {
				$resD = mysqli_query($conexion, "SELECT id_hotel FROM departamentos WHERE id_departamento='" . (int)$id_departamento . "' LIMIT 1");
				if ($resD && $rowD = mysqli_fetch_row($resD)) {
					$id_hotel = (int)$rowD[0];
				}
				if ($id_hotel === null || $id_hotel <= 0) {
					return 'SIN_DEPARTAMENTO';
				}
			}
			if ($usaHotel && $id_hotel !== null) {
				$resNum = mysqli_query($conexion, "SELECT COALESCE(MAX(numero_equipo_hotel),0)+1 FROM equipos WHERE id_hotel='" . $id_hotel . "'");
				if ($resNum && $rowNum = mysqli_fetch_row($resNum)) {
					$numero = (int)$rowNum[0];
				}
			}

			$codigoEquipo = isset($datos[2]) ? trim($datos[2]) : '';
			if ($codigoEquipo === '') {
				$codigoEquipo = $this->generarCodigoAutomatico($conexion, $id_hotel);
			} elseif ($this->existeCodigoEquipo($conexion, $codigoEquipo)) {
				// Código ya usado en otro registro (p. ej. otro hotel): generar uno nuevo para este alta/importación
				$codigoEquipo = $this->generarCodigoAutomatico($conexion, $id_hotel);
			}
			$numeroSerie = isset($datos[6]) ? trim((string)$datos[6]) : '';
			if ($this->existeNumeroSerie($conexion, $numeroSerie, $id_hotel)) {
				return 'DUPLICATE_SERIE';
			}
			$descripcionSql = $toSqlNullableString(isset($datos[3]) ? $datos[3] : '');
			$marcaSql = $toSqlNullableString(isset($datos[4]) ? $datos[4] : '');
			$modeloSql = $toSqlNullableString(isset($datos[5]) ? $datos[5] : '');
			$serieSql = $toSqlNullableString(isset($datos[6]) ? $datos[6] : '');
			$estado = isset($datos[7]) && trim((string)$datos[7]) !== '' ? trim((string)$datos[7]) : 'Activo';
			$estado = mysqli_real_escape_string($conexion, $estado);
			$ubicacion = isset($datos[8]) ? trim((string)$datos[8]) : '';
			$ubicacion = mysqli_real_escape_string($conexion, $ubicacion);
			$fechaIngresoSql = (isset($datos[9]) && trim((string)$datos[9]) !== '') ? "'" . mysqli_real_escape_string($conexion, trim((string)$datos[9])) . "'" : "'" . $fecha . "'";
			$valorSql = (isset($datos[10]) && trim((string)$datos[10]) !== '') ? "'" . mysqli_real_escape_string($conexion, trim((string)$datos[10])) . "'" : "NULL";
			$tipoMoneda = isset($datos[11]) && trim((string)$datos[11]) !== '' ? trim((string)$datos[11]) : 'MXN';
			$tipoMoneda = mysqli_real_escape_string($conexion, $tipoMoneda);

			$fotoVal = ($datos[12] !== null && $datos[12] !== '') ? "'" . mysqli_real_escape_string($conexion, $datos[12]) . "'" : "NULL";
			$usuarioRegSql = $toSqlNullableString(isset($datos[13]) ? $datos[13] : '');
			$numFacturaSql = $toSqlNullableString(isset($datos[14]) ? $datos[14] : '');
			if ($usaHotel && $id_hotel !== null) {
				$sql="INSERT into equipos (id_usuario, id_departamento, id_hotel, numero_equipo_hotel, codigo_equipo, descripcion, marca, modelo, numero_serie, estado, ubicacion, fecha_ingreso, valor_adquisicion, tipo_moneda, fecha_registro, usuario_registro, numero_factura, foto_equipo)
					values ('$datos[0]', $idDepartamentoSql, '$id_hotel', '$numero', '" . mysqli_real_escape_string($conexion, $codigoEquipo) . "', $descripcionSql, $marcaSql, $modeloSql, $serieSql, '$estado', '$ubicacion', $fechaIngresoSql, $valorSql, '$tipoMoneda', '$fecha', $usuarioRegSql, $numFacturaSql, $fotoVal)";
			} else {
				$sql="INSERT into equipos (id_usuario, id_departamento, codigo_equipo, descripcion, marca, modelo, numero_serie, estado, ubicacion, fecha_ingreso, valor_adquisicion, tipo_moneda, fecha_registro, usuario_registro, numero_factura, foto_equipo)
					values ('$datos[0]', $idDepartamentoSql, '" . mysqli_real_escape_string($conexion, $codigoEquipo) . "', $descripcionSql, $marcaSql, $modeloSql, $serieSql, '$estado', '$ubicacion', $fechaIngresoSql, $valorSql, '$tipoMoneda', '$fecha', $usuarioRegSql, $numFacturaSql, $fotoVal)";
			}
			$result = mysqli_query($conexion,$sql);
			
			if(!$result){
				error_log("Error en insertaEquipo: " . mysqli_error($conexion));
				return 0;
			}
			
			return 1;
		}

		public function obtenDatosEquipo($idequipo){
			$c=new conectar();
			$conexion=$c->conexion();
			$this->asegurarColumnasRegistroEquipo($conexion);

			$sql="SELECT id_equipo, 
						codigo_equipo,
						descripcion,
						marca,
						modelo,
						numero_serie,
						estado,
						ubicacion,
						fecha_ingreso,
						valor_adquisicion,
						tipo_moneda,
						id_departamento,
						usuario_registro,
						numero_factura,
						foto_equipo
				from equipos 
				where id_equipo='$idequipo'";
			$result=mysqli_query($conexion,$sql);

			$ver=mysqli_fetch_row($result);

			$datos=array(
					"id_equipo" => $ver[0],
					"codigo_equipo" => $ver[1],
					"descripcion" => $ver[2],
					"marca" => $ver[3],
					"modelo" => $ver[4],
					"numero_serie" => $ver[5],
					"estado" => $ver[6],
					"ubicacion" => $ver[7],
					"fecha_ingreso" => $ver[8],
					"valor_adquisicion" => $ver[9],
					"tipo_moneda" => $ver[10],
					"id_departamento" => $ver[11],
					"usuario_registro" => isset($ver[12]) ? (string)$ver[12] : '',
					"numero_factura" => isset($ver[13]) ? (string)$ver[13] : '',
					"foto_equipo" => $ver[14] ?? ''
						);

			return $datos;
		}

		public function actualizaEquipo($datos){
			$c=new conectar();
			$conexion=$c->conexion();
			$this->asegurarColumnasRegistroEquipo($conexion);

			$idEquipo = mysqli_real_escape_string($conexion, trim((string)$datos[0]));
			$idDepartamentoRaw = isset($datos[1]) ? trim((string)$datos[1]) : '';
			$idDepartamentoSql = ($idDepartamentoRaw === '' || strtolower($idDepartamentoRaw) === 'null')
				? "NULL"
				: "'" . mysqli_real_escape_string($conexion, $idDepartamentoRaw) . "'";
			$codigo = mysqli_real_escape_string($conexion, trim((string)$datos[2]));
			$descripcion = mysqli_real_escape_string($conexion, trim((string)$datos[3]));
			$marca = mysqli_real_escape_string($conexion, trim((string)$datos[4]));
			$modelo = mysqli_real_escape_string($conexion, trim((string)$datos[5]));
			$numeroSerie = mysqli_real_escape_string($conexion, trim((string)$datos[6]));
			$estado = mysqli_real_escape_string($conexion, trim((string)$datos[7]));
			$ubicacion = mysqli_real_escape_string($conexion, trim((string)$datos[8]));
			$fechaIngreso = mysqli_real_escape_string($conexion, trim((string)$datos[9]));
			$valorAdquisicion = mysqli_real_escape_string($conexion, trim((string)$datos[10]));
			$tipoMoneda = mysqli_real_escape_string($conexion, trim((string)$datos[11]));
			$fotoEquipo = isset($datos[12]) ? trim($datos[12]) : '';
			$fotoVal = ($fotoEquipo === '') ? "NULL" : "'" . mysqli_real_escape_string($conexion, $fotoEquipo) . "'";
			$usuarioRegistro = mysqli_real_escape_string($conexion, isset($datos[13]) ? trim((string)$datos[13]) : '');
			$numeroFactura = mysqli_real_escape_string($conexion, isset($datos[14]) ? trim((string)$datos[14]) : '');

			$sql="UPDATE equipos set id_departamento=$idDepartamentoSql, 
									codigo_equipo='$codigo',
									descripcion='$descripcion',
									marca='$marca',
									modelo='$modelo',
									numero_serie='$numeroSerie',
									estado='$estado',
									ubicacion='$ubicacion',
									fecha_ingreso='$fechaIngreso',
									valor_adquisicion='$valorAdquisicion',
									tipo_moneda='$tipoMoneda',
									usuario_registro='$usuarioRegistro',
									numero_factura='$numeroFactura',
									foto_equipo=$fotoVal
						where id_equipo='$idEquipo'";

			$result = mysqli_query($conexion,$sql);
			
			if(!$result){
				error_log("Error en actualizaEquipo: " . mysqli_error($conexion));
				return 0;
			}
			
			return 1;
		}

		/** Comprueba que el equipo pertenezca al hotel (por id_hotel del equipo o del departamento). */
		public function equipoPerteneceAlHotel($idEquipo, $idHotel) {
			$idEquipo = (int)$idEquipo;
			$idHotel = (int)$idHotel;
			if ($idEquipo <= 0 || $idHotel <= 0) {
				return false;
			}
			$c = new conectar();
			$conexion = $c->conexion();
			$idEsc = (string)$idEquipo;
			$hotelEsc = (string)$idHotel;

			$chkHotelCol = @mysqli_query($conexion, "SHOW COLUMNS FROM equipos LIKE 'id_hotel'");
			$tieneIdHotel = $chkHotelCol && mysqli_num_rows($chkHotelCol) > 0;
			$sql = "SELECT e.id_equipo FROM equipos e
					LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
					WHERE e.id_equipo = '$idEsc' AND (";
			if ($tieneIdHotel) {
				$sql .= "e.id_hotel = '$hotelEsc' OR (e.id_hotel IS NULL AND d.id_hotel = '$hotelEsc')";
			} else {
				$sql .= "d.id_hotel = '$hotelEsc'";
			}
			$sql .= ") LIMIT 1";
			$res = mysqli_query($conexion, $sql);
			return ($res && mysqli_num_rows($res) > 0);
		}

		private function tablaExiste($conexion, $nombreTabla) {
			$nombreTabla = preg_replace('/[^a-zA-Z0-9_]/', '', $nombreTabla);
			$res = @mysqli_query($conexion, "SHOW TABLES LIKE '$nombreTabla'");
			return ($res && mysqli_num_rows($res) > 0);
		}

		private function eliminarArchivoRelativoProyecto($rutaRelativa) {
			$rutaRelativa = trim((string)$rutaRelativa);
			if ($rutaRelativa === '') {
				return;
			}
			$base = realpath(__DIR__ . '/..');
			if (!$base) {
				return;
			}
			$rutaCompleta = $base . DIRECTORY_SEPARATOR . str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, ltrim($rutaRelativa, '/\\'));
			if (file_exists($rutaCompleta) && is_file($rutaCompleta)) {
				@unlink($rutaCompleta);
			}
		}

		private function eliminarDependenciasEquipo($conexion, $idEsc) {
			if ($this->tablaExiste($conexion, 'solicitud_alta_equipos_detalle')) {
				if (!mysqli_query($conexion, "DELETE FROM solicitud_alta_equipos_detalle WHERE id_equipo='$idEsc'")) {
					throw new Exception('solicitud_alta_equipos_detalle: ' . mysqli_error($conexion));
				}
			}
			if ($this->tablaExiste($conexion, 'bajas_masivas_equipos')) {
				if (!mysqli_query($conexion, "DELETE FROM bajas_masivas_equipos WHERE id_equipo='$idEsc'")) {
					throw new Exception('bajas_masivas_equipos: ' . mysqli_error($conexion));
				}
			}
			if ($this->tablaExiste($conexion, 'bajas_equipos')) {
				$resBajas = mysqli_query($conexion, "SELECT foto_equipo, foto_etiqueta FROM bajas_equipos WHERE id_equipo='$idEsc'");
				if ($resBajas) {
					while ($baja = mysqli_fetch_assoc($resBajas)) {
						$this->eliminarArchivoRelativoProyecto($baja['foto_equipo'] ?? '');
						$this->eliminarArchivoRelativoProyecto($baja['foto_etiqueta'] ?? '');
					}
				}
				if (!mysqli_query($conexion, "DELETE FROM bajas_equipos WHERE id_equipo='$idEsc'")) {
					throw new Exception('bajas_equipos: ' . mysqli_error($conexion));
				}
			}
			if ($this->tablaExiste($conexion, 'altas_equipos')) {
				if (!mysqli_query($conexion, "DELETE FROM altas_equipos WHERE id_equipo='$idEsc'")) {
					throw new Exception('altas_equipos: ' . mysqli_error($conexion));
				}
			}
			if ($this->tablaExiste($conexion, 'resguardos')) {
				if (!mysqli_query($conexion, "DELETE FROM resguardos WHERE id_equipo='$idEsc'")) {
					throw new Exception('resguardos: ' . mysqli_error($conexion));
				}
			}
		}

		public function eliminaEquipo($idequipo){
			$c=new conectar();
			$conexion=$c->conexion();

			$idEqInt = (int)$idequipo;
			if ($idEqInt <= 0) {
				return 0;
			}
			$idEsc = mysqli_real_escape_string($conexion, (string)$idEqInt);

			$chk = mysqli_query($conexion, "SELECT id_equipo FROM equipos WHERE id_equipo='$idEsc' LIMIT 1");
			if (!$chk || mysqli_num_rows($chk) === 0) {
				return 0;
			}

			mysqli_autocommit($conexion, false);
			try {
				$this->eliminarDependenciasEquipo($conexion, $idEsc);

				$sqlFoto = "SELECT foto_equipo FROM equipos WHERE id_equipo='$idEsc'";
				$resFoto = mysqli_query($conexion, $sqlFoto);
				if ($resFoto && $fila = mysqli_fetch_row($resFoto) && !empty($fila[0])) {
					$nombreArchivo = basename(trim($fila[0]));
					$carpetaEquipos = realpath(__DIR__ . '/../archivos/equipos');
					if ($carpetaEquipos && $nombreArchivo !== '' && $nombreArchivo !== '.' && $nombreArchivo !== '..') {
						$rutaCompleta = $carpetaEquipos . DIRECTORY_SEPARATOR . $nombreArchivo;
						if (file_exists($rutaCompleta) && is_file($rutaCompleta)) {
							@unlink($rutaCompleta);
						}
					}
				}

				$dirFact = realpath(__DIR__ . '/../archivos/facturas_equipos');
				if ($dirFact) {
					foreach (array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp') as $e) {
						$rf = $dirFact . DIRECTORY_SEPARATOR . 'equipo_' . $idEqInt . '.' . $e;
						if (file_exists($rf) && is_file($rf)) {
							@unlink($rf);
						}
					}
				}
				$pdfResguardo = realpath(__DIR__ . '/../archivos/resguardos_firmados/equipo_' . $idEqInt . '.pdf');
				if ($pdfResguardo && is_file($pdfResguardo)) {
					@unlink($pdfResguardo);
				}

				if (!mysqli_query($conexion, "DELETE FROM equipos WHERE id_equipo='$idEsc'")) {
					throw new Exception(mysqli_error($conexion));
				}
				if (mysqli_affected_rows($conexion) === 0) {
					throw new Exception('No se eliminó el registro del equipo');
				}

				mysqli_commit($conexion);
				return 1;
			} catch (Exception $e) {
				mysqli_rollback($conexion);
				error_log("Error al eliminar equipo (id $idEqInt): " . $e->getMessage());
				return 0;
			} finally {
				mysqli_autocommit($conexion, true);
			}
		}

		/**
		 * Elimina el PDF de resguardo firmado o la factura escaneada del equipo.
		 * @param int|string $idEquipo
		 * @param string $tipo 'resguardo'|'factura'
		 * @return array{ok:bool,mensaje:string}
		 */
		public function eliminarDocumentoAdjuntoEquipo($idEquipo, $tipo) {
			$idEqInt = (int)$idEquipo;
			$tipo = strtolower(trim((string)$tipo));
			if ($idEqInt <= 0) {
				return array('ok' => false, 'mensaje' => 'ID de equipo inválido.');
			}
			if (!in_array($tipo, array('resguardo', 'factura'), true)) {
				return array('ok' => false, 'mensaje' => 'Tipo de documento no válido.');
			}

			$c = new conectar();
			$conexion = $c->conexion();
			$idEsc = mysqli_real_escape_string($conexion, (string)$idEqInt);
			$chk = mysqli_query($conexion, "SELECT id_equipo FROM equipos WHERE id_equipo='$idEsc' LIMIT 1");
			if (!$chk || mysqli_num_rows($chk) === 0) {
				return array('ok' => false, 'mensaje' => 'El equipo no existe.');
			}

			$eliminado = false;
			if ($tipo === 'resguardo') {
				$ruta = realpath(__DIR__ . '/../archivos/resguardos_firmados/equipo_' . $idEqInt . '.pdf');
				if ($ruta && is_file($ruta)) {
					$eliminado = @unlink($ruta);
				}
				if (!$eliminado) {
					return array('ok' => false, 'mensaje' => 'No hay resguardo firmado para eliminar.');
				}
				return array('ok' => true, 'mensaje' => 'Resguardo firmado eliminado correctamente.');
			}

			$dirFact = realpath(__DIR__ . '/../archivos/facturas_equipos');
			if ($dirFact) {
				foreach (array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp') as $ext) {
					$rf = $dirFact . DIRECTORY_SEPARATOR . 'equipo_' . $idEqInt . '.' . $ext;
					if (file_exists($rf) && is_file($rf)) {
						if (@unlink($rf)) {
							$eliminado = true;
						}
					}
				}
			}
			if (!$eliminado) {
				return array('ok' => false, 'mensaje' => 'No hay factura escaneada para eliminar.');
			}
			return array('ok' => true, 'mensaje' => 'Factura escaneada eliminada correctamente.');
		}

		public function cambiarEstadoEquipo($idequipo, $nuevoEstado){
			$c=new conectar();
			$conexion=$c->conexion();

			$sql="UPDATE equipos set estado='$nuevoEstado' 
					where id_equipo='$idequipo'";

			return mysqli_query($conexion,$sql);
		}

		public function obtenerEquiposPorEstado($estado, $id_hotel = null){
			$c=new conectar();
			$conexion=$c->conexion();

			$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			$whereHotel = ($tieneIdHotel && $id_hotel !== null) ? " AND d.id_hotel='" . intval($id_hotel) . "'" : "";
			$sql="SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
						e.estado, e.ubicacion, d.nombre as departamento
					from equipos e
					left join departamentos d on e.id_departamento = d.id_departamento
					where e.estado='$estado' $whereHotel
					order by e.fecha_ingreso DESC, e.id_equipo DESC";

			return mysqli_query($conexion,$sql);
		}

		public function buscarEquipo($termino, $id_hotel = null){
			$c=new conectar();
			$conexion=$c->conexion();
			$termino = mysqli_real_escape_string($conexion, $termino);

			$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			$whereHotel = ($tieneIdHotel && $id_hotel !== null) ? " AND d.id_hotel='" . intval($id_hotel) . "'" : "";
			$sql="SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
						e.estado, e.ubicacion, d.nombre as departamento
					from equipos e
					left join departamentos d on e.id_departamento = d.id_departamento
					where (e.codigo_equipo LIKE '%$termino%' 
					OR e.descripcion LIKE '%$termino%' 
					OR e.marca LIKE '%$termino%'
					OR e.modelo LIKE '%$termino%'
					OR e.numero_serie LIKE '%$termino%') $whereHotel
					order by e.fecha_ingreso DESC, e.id_equipo DESC";

			return mysqli_query($conexion,$sql);
		}

		/** Obtiene datos del equipo por código o número de serie (para resguardos). Incluye departamento y ubicación física (texto) para precargar área de destino. Filtra por hotel si se indica. */
		public function obtenerEquipoPorCodigo($codigo, $id_hotel = null){
			$c=new conectar();
			$conexion=$c->conexion();
			$this->ensureRegistroEquiposCols();
			$codigo = mysqli_real_escape_string($conexion, trim($codigo));
			if ($codigo === '') return null;

			$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
			$whereHotel = ($tieneIdHotel && $id_hotel !== null) ? " AND d.id_hotel='" . intval($id_hotel) . "'" : "";
			$tieneRepresentante = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'representante'");
			$campoRepresentante = ($tieneRepresentante && mysqli_num_rows($tieneRepresentante) > 0) ? ", d.representante as representante_departamento" : ", d.responsable as representante_departamento";
			$sql="SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo, e.numero_serie,
						e.id_departamento, e.ubicacion, e.usuario_registro, e.numero_factura,
						d.nombre as nombre_departamento
						$campoRepresentante
					FROM equipos e
					LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
					WHERE (e.codigo_equipo = '$codigo' OR e.numero_serie = '$codigo') AND e.estado != 'Dado de Baja' $whereHotel
					LIMIT 1";
			$result = mysqli_query($conexion, $sql);
			if (!$result || mysqli_num_rows($result) === 0) return null;

			$ver = mysqli_fetch_assoc($result);
			$representante = $ver['representante_departamento'] ?? '';
			return array(
				'id_equipo' => $ver['id_equipo'],
				'codigo_equipo' => $ver['codigo_equipo'],
				'descripcion' => $ver['descripcion'] ?? '',
				'marca' => $ver['marca'] ?? '',
				'modelo' => $ver['modelo'] ?? '',
				'numero_serie' => $ver['numero_serie'] ?? '',
				'id_departamento' => $ver['id_departamento'],
				'ubicacion' => isset($ver['ubicacion']) ? trim((string)$ver['ubicacion']) : '',
				'usuario_registro' => isset($ver['usuario_registro']) ? trim((string)$ver['usuario_registro']) : '',
				'numero_factura' => isset($ver['numero_factura']) ? trim((string)$ver['numero_factura']) : '',
				'nombre_departamento' => $ver['nombre_departamento'] ?? '',
				'representante_departamento' => $representante
			);
		}
	}

 ?>
