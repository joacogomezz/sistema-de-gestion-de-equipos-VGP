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

	$sql="SELECT b.id_baja, b.fecha_baja, d.nombre as departamento, b.tipo_baja, 
					b.motivo_baja, b.valor_residual, b.destino_equipo, b.observaciones,
					COUNT(e.id_equipo) as total_equipos
			from bajas_equipos b
			left join departamentos d on b.id_departamento = d.id_departamento
			inner join equipos e on b.id_equipo = e.id_equipo
			where b.tipo_baja = 'Masiva' " . $whereHotel . "
			group by b.id_baja, b.fecha_baja, d.nombre, b.tipo_baja, b.motivo_baja, 
					 b.valor_residual, b.destino_equipo, b.observaciones
			order by b.fecha_baja desc";
	$result=mysqli_query($conexion,$sql);

	$html = '
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="utf-8">
		<title>Reporte de Bajas Masivas - Hotel</title>
		<style>
			body { font-family: Arial, sans-serif; font-size: 12px; }
			table { width: 100%; border-collapse: collapse; margin-top: 20px; }
			th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
			th { background-color: #f2f2f2; font-weight: bold; }
			.header { text-align: center; margin-bottom: 30px; }
			.header h1 { color: #333; margin-bottom: 5px; }
			.header p { color: #666; margin: 0; }
			.footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
		</style>
	</head>
	<body>
		<div class="header">
			<h1>Sistema de Gestión de Equipos - Hotel</h1>
			<p>Reporte de Bajas Masivas de Equipos</p>
			<p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
		</div>
		
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Fecha Baja</th>
					<th>Departamento</th>
					<th>Tipo</th>
					<th>Motivo</th>
					<th>Total Equipos</th>
					<th>Valor Residual</th>
					<th>Destino</th>
					<th>Observaciones</th>
				</tr>
			</thead>
			<tbody>';

	while($row=mysqli_fetch_row($result)){
		$html .= '
			<tr>
				<td>' . $row[0] . '</td>
				<td>' . $row[1] . '</td>
				<td>' . ($row[2] ? $row[2] : 'No asignado') . '</td>
				<td>' . $row[3] . '</td>
				<td>' . $row[4] . '</td>
				<td>' . $row[8] . '</td>
				<td>$' . number_format($row[5], 2) . '</td>
				<td>' . $row[6] . '</td>
				<td>' . ($row[7] ? $row[7] : 'Sin observaciones') . '</td>
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
	$dompdf->setPaper('A4', 'portrait');
	$dompdf->render();
	$dompdf->stream("Reporte_Bajas_Masivas_" . date('Y-m-d_H-i-s') . ".pdf", array("Attachment" => false));
} else {
	header("location:../../index.php");
}
?>
