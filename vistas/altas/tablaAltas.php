<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	$c= new conectar();
	$conexion=$c->conexion();

	$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
	$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	$whereHotel = '';
	if ($id_hotel !== null) {
		$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			$whereHotel = " AND d.id_hotel = '" . $id_hotel . "'";
		}
	}
	$sql="SELECT a.id_alta, a.fecha_alta, e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
					d.nombre as departamento, a.motivo_alta, a.observaciones
			from altas_equipos a
			inner join equipos e on a.id_equipo = e.id_equipo
			left join departamentos d on a.id_departamento = d.id_departamento
			where 1=1 $whereHotel
			order by a.fecha_alta desc";
	$result=mysqli_query($conexion,$sql);
?>

<h4>Altas Registradas</h4>
<div class="row">
	<div class="col-sm-12">
		<div class="table-responsive">
			<table class="table table-hover table-condensed table-bordered" style="text-align: center;">
				<caption><label>Historial de Altas de Equipos</label></caption>
				<tr>
					<td><strong>ID</strong></td>
					<td><strong>Fecha</strong></td>
					<td><strong>Código</strong></td>
					<td><strong>Equipo</strong></td>
					<td><strong>Marca/Modelo</strong></td>
					<td><strong>Departamento</strong></td>
					<td><strong>Motivo</strong></td>
					<td><strong>Observaciones</strong></td>
				</tr>
				<?php while($ver=mysqli_fetch_row($result)): ?>
					<tr>
						<td><?php echo $ver[0]; ?></td>
						<td><?php echo $ver[1]; ?></td>
						<td><?php echo $ver[2]; ?></td>
						<td><?php echo $ver[3]; ?></td>
						<td><?php echo $ver[4] . ' ' . $ver[5]; ?></td>
						<td><?php echo $ver[6] ? $ver[6] : 'No asignado'; ?></td>
						<td><?php echo $ver[7]; ?></td>
						<td><?php echo $ver[8] ? $ver[8] : 'Sin observaciones'; ?></td>
					</tr>
				<?php endwhile; ?>
			</table>
		</div>
	</div>
</div>
