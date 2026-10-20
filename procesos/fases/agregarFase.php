<?php
session_start();
require_once "../../clases/Conexion.php";
require_once "../../clases/Fases.php";

$obj = new Fases();
$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$id_hotel = (isset($_SESSION['id_hotel']) && (int)$_SESSION['id_hotel'] > 0) ? (int)$_SESSION['id_hotel'] : 1;
if ($es_super_admin && isset($_POST['id_hotel_context']) && (int)$_POST['id_hotel_context'] > 0) {
	$id_hotel = (int)$_POST['id_hotel_context'];
}
$datos = array(
	$id_hotel,
	$_POST['nombreFase'],
	isset($_POST['descripcionFase']) ? $_POST['descripcionFase'] : ''
);
echo $obj->agregar($datos) ? '1' : '0';
