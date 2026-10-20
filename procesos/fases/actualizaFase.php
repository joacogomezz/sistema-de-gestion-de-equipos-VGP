<?php
session_start();
require_once "../../clases/Conexion.php";
require_once "../../clases/Fases.php";

$obj = new Fases();
$datos = array(
	$_POST['idFase'],
	$_POST['nombreFaseU'],
	isset($_POST['descripcionFaseU']) ? $_POST['descripcionFaseU'] : ''
);
echo $obj->actualizar($datos) ? '1' : '0';
