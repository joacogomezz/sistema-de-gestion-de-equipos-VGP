<?php
/**
 * Redirige al PDF correcto: si el resguardo pertenece a un grupo (misma factura, fecha, usuario),
 * abre el reporte múltiple con todos los equipos; si no, abre el reporte individual.
 */
// Evita X-Frame-Options en esta respuesta para que la descarga vía iframe oculto pueda seguir el 302 hasta el PDF.
define('PDF_OUTPUT', true);
require_once "../seguridad.php";
requerirAutenticacion();

require_once "../clases/Resguardos.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
	header('Location: reporteResguardo.php?id=0');
	exit;
}

$obj = new Resguardos();
$ids = $obj->obtenerIdsMismoGrupo($id);
if (!$ids) {
	header('Location: reporteResguardo.php?id=' . $id);
	exit;
}

if (count($ids) === 1) {
	header('Location: reporteResguardo.php?id=' . $ids[0]);
	exit;
}

header('Location: reporteResguardoMultiple.php?ids=' . implode(',', $ids));
exit;
