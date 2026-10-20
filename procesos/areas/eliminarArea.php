<?php
session_start();
require_once "../../clases/Conexion.php";
require_once "../../clases/Areas.php";

$obj = new Areas();
$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$id_hotel = $es_super_admin ? null : ((isset($_SESSION['id_hotel']) && (int)$_SESSION['id_hotel'] > 0) ? (int)$_SESSION['id_hotel'] : null);
echo $obj->eliminar(isset($_POST['idArea']) ? $_POST['idArea'] : 0, $id_hotel) ? '1' : '0';
