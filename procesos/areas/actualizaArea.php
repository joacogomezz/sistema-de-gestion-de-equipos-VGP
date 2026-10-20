<?php
session_start();
require_once "../../clases/Conexion.php";
require_once "../../clases/Areas.php";

$obj = new Areas();
$datos = array(
	$_POST['idArea'],
	$_POST['nombreAreaU'],
	isset($_POST['descripcionAreaU']) ? $_POST['descripcionAreaU'] : ''
);
echo $obj->actualizar($datos) ? '1' : '0';
