<?php 
	session_start();
	require_once "../../clases/Conexion.php";
	$c= new conectar();
	$conexion=$c->conexion();

	// Super Admin (id=1) ve todos los usuarios; administradores de villa solo los de su hotel
	$es_admin = isset($_SESSION['iduser']) && (int) $_SESSION['iduser'] === 1;
	$id_hotel = $es_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
	$whereHotel = '';
	if (!$es_admin && $id_hotel !== null) {
		$chk = @mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'id_hotel'");
		if ($chk && mysqli_num_rows($chk) > 0) {
			$whereHotel = " WHERE id_hotel = '" . $id_hotel . "'";
		}
	}
	$tieneIdHotel = @mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'id_hotel'");
	$usaHotel = $tieneIdHotel && mysqli_num_rows($tieneIdHotel) > 0;
	$tieneRol = @mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'rol'");
	$campoRol = ($tieneRol && mysqli_num_rows($tieneRol) > 0) ? "COALESCE(NULLIF(u.rol,''),'ADMIN')" : "'ADMIN'";
	$campoRolSinAlias = ($tieneRol && mysqli_num_rows($tieneRol) > 0) ? "COALESCE(NULLIF(rol,''),'ADMIN')" : "'ADMIN'";
	if ($usaHotel) {
		$whereHotelAlias = str_replace('id_hotel', 'u.id_hotel', $whereHotel);
		$sql="SELECT u.id_usuario, u.nombre, u.apellido, u.email, h.nombre as nombre_hotel,
					$campoRol as rol
				from usuarios u
				left join hoteles h on u.id_hotel = h.id_hotel
				$whereHotelAlias";
	} else {
		$sql="SELECT id_usuario, nombre, apellido, email, $campoRolSinAlias as rol
				from usuarios $whereHotel";
	}
	$result=mysqli_query($conexion,$sql);

 ?>


<table class="table table-hover table-condensed table-bordered" style="text-align: center;">
	<caption><label>Usuarios</label></caption>
	<tr>
		<td>Nombre</td>
		<td>Apellido</td>
		<td>Usuario</td>
	<td>Rol</td>
		<?php if ($usaHotel): ?><td>Hotel</td><?php endif; ?>
		<td>Editar</td>
		<td>Eliminar</td>
	</tr>

	<?php while($ver=mysqli_fetch_row($result)): ?>

	<tr>
		<td><?php echo htmlspecialchars($ver[1]); ?></td>
		<td><?php echo htmlspecialchars($ver[2]); ?></td>
		<td><?php echo htmlspecialchars($ver[3]); ?></td>
		<td><?php echo htmlspecialchars(isset($ver[5]) ? $ver[5] : (isset($ver[4]) ? $ver[4] : 'ADMIN')); ?></td>
		<?php if ($usaHotel): ?><td><?php echo htmlspecialchars(isset($ver[4]) ? $ver[4] : '-'); ?></td><?php endif; ?>
		<td>
			<span data-toggle="modal" data-target="#actualizaUsuarioModal" class="btn btn-warning btn-xs" onclick="agregaDatosUsuario('<?php echo $ver[0]; ?>')">
				<span class="glyphicon glyphicon-pencil"></span>
			</span>
		</td>
		<td>
			<?php if ((int)$ver[0] === 1): ?>
				<span class="btn btn-default btn-xs disabled" title="El super admin no se puede eliminar">
					<span class="glyphicon glyphicon-lock"></span>
				</span>
			<?php else: ?>
				<span class="btn btn-danger btn-xs" onclick="eliminarUsuario('<?php echo $ver[0]; ?>')">
					<span class="glyphicon glyphicon-remove"></span>
				</span>
			<?php endif; ?>
		</td>
	</tr>
<?php endwhile; ?>
</table>