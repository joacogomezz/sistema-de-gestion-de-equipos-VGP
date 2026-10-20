<h5>Departamentos Existentes</h5>
<?php
session_start();
require_once "../../clases/Conexion.php";
require_once "../../clases/Departamentos.php";
$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$id_hotel = $es_super_admin
	? ((isset($_GET['id_hotel']) && (int)$_GET['id_hotel'] > 0) ? (int)$_GET['id_hotel'] : null)
	: (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
$obj = new departamentos();
$result = $obj->obtenerTodosDepartamentos($id_hotel);
while ($departamento=mysqli_fetch_row($result)):
	$nombre = isset($departamento[1]) ? $departamento[1] : $departamento[0];
	$desc = isset($departamento[2]) ? $departamento[2] : (isset($departamento[1]) ? $departamento[1] : '');
	?>
	<div class="panel panel-default">
		<div class="panel-heading"><?php echo htmlspecialchars($nombre); ?></div>
		<div class="panel-body">
			<p><strong>Descripción:</strong> <?php echo $desc ? $desc : 'Sin descripción'; ?></p>
		</div>
	</div>
<?php endwhile; ?>
