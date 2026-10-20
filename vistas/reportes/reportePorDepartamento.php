<?php
use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
if(isset($_SESSION['usuario'])){
	require_once "../../clases/Conexion.php";
	require_once "../../librerias/dompdf/autoload.inc.php";

	$c= new conectar();
	$conexion=$c->conexion();

	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : null);
	$whereHotel = '';
	if ($id_hotel !== null) {
		$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			$whereHotel = " AND d.id_hotel = '" . $id_hotel . "'";
		}
	}

	$sql="SELECT d.nombre as departamento, e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
					e.estado, e.valor_adquisicion
			from equipos e
			left join departamentos d on e.id_departamento = d.id_departamento
			where 1=1 " . $whereHotel . "
			order by d.nombre, e.descripcion";
	$result=mysqli_query($conexion,$sql);

	$html = '
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="utf-8">
		<title>Reporte por Departamento - Hotel</title>
		<style>
			body { font-family: Arial, sans-serif; font-size: 12px; }
			table { width: 100%; border-collapse: collapse; margin-top: 20px; }
			th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
			th { background-color: #f2f2f2; font-weight: bold; }
			.header { text-align: center; margin-bottom: 30px; }
			.header h1 { color: #333; margin-bottom: 5px; }
			.header p { color: #666; margin: 0; }
			.footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
			.departamento { background-color: #e8f4f8; font-weight: bold; }
		</style>
	</head>
	<body>
		<div class="header">
			<h1>Sistema de Gestión de Equipos - Hotel</h1>
			<p>Reporte de Equipos por Departamento</p>
			<p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
		</div>
		
		<table>
			<thead>
				<tr>
					<th>Departamento</th>
					<th>Código</th>
					<th>Equipo</th>
					<th>Categoría</th>
					<th>Marca/Modelo</th>
					<th>Estado</th>
					<th>Valor</th>
				</tr>
			</thead>
			<tbody>';

	$departamento_actual = '';
	while($row=mysqli_fetch_row($result)){
		if($departamento_actual != $row[0]){
			$departamento_actual = $row[0];
			$html .= '
				<tr class="departamento">
					<td colspan="7">' . ($row[0] ? $row[0] : 'Sin Departamento Asignado') . '</td>
				</tr>';
		}
		$html .= '
			<tr>
				<td></td>
				<td>' . $row[1] . '</td>
				<td>' . $row[2] . '</td>
				<td>N/A</td>
				<td>' . $row[3] . ' ' . $row[4] . '</td>
				<td>' . $row[5] . '</td>
				<td>$' . number_format($row[6], 2) . '</td>
			</tr>';
	}

	$html .= '
			</tbody>
		</table>
		
		<div class="footer">
			<p>Reporte generado automáticamente por el Sistema de Gestión de Equipos</p>
		</div>
	</body>
	</html>';

	$options = new Options();
	$options->set('isHtml5ParserEnabled', true);
	$options->set('isPhpEnabled', true);

	$dompdf = new Dompdf($options);
	$dompdf->loadHtml($html);
	$dompdf->setPaper('A4', 'landscape');
	$dompdf->render();
	$dompdf->stream("Reporte_Por_Departamento_" . date('Y-m-d_H-i-s') . ".pdf", array("Attachment" => false));
} else {
	header("location:../../index.php");
}
?>
