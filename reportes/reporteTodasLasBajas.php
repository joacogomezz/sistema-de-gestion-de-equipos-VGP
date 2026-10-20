<?php
use Dompdf\Dompdf;
use Dompdf\Options;

// Incluir archivo de seguridad
require_once "../seguridad.php";

// Requerir autenticación
requerirAutenticacion();

require_once "../clases/Conexion.php";
require_once "../librerias/dompdf/autoload.inc.php";

$c = new conectar();
$conexion = $c->conexion();

// Super Admin (id=1) ve todo el sistema; el resto solo su hotel
$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int)$_SESSION['id_hotel'] : null);
$whereHotel = '';
if ($id_hotel !== null) {
    $chk = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $whereHotel = " AND d.id_hotel = '" . $id_hotel . "'";
    }
}

// Obtener bajas (del hotel del usuario o todas si Super Admin)
$sql_bajas_individuales = "SELECT b.id_baja, b.fecha_baja, b.tipo_baja, b.motivo_baja, 
                                  b.valor_residual, b.destino_equipo, b.observaciones,
                                  e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
                                  e.valor_adquisicion, e.tipo_moneda,
                                  d.nombre as departamento,
                                  u.nombre as usuario_nombre, u.apellido as usuario_apellido
                           FROM bajas_equipos b
                           INNER JOIN equipos e ON b.id_equipo = e.id_equipo
                           LEFT JOIN departamentos d ON b.id_departamento = d.id_departamento
                           LEFT JOIN usuarios u ON b.id_usuario = u.id_usuario
                           WHERE 1=1 " . $whereHotel . "
                           ORDER BY b.fecha_baja DESC, b.id_baja DESC";

$result_bajas_individuales = mysqli_query($conexion, $sql_bajas_individuales);

// Bajas masivas (filtrar por departamento -> hotel)
$sql_bajas_masivas = "SELECT bm.id_baja_masiva, bm.fecha_baja, bm.motivo_baja,
                             bm.valor_residual_total, bm.destino_equipo, bm.observaciones,
                             bm.total_equipos, bm.hotel_area,
                             u.nombre as usuario_nombre, u.apellido as usuario_apellido,
                             d.nombre as departamento
                      FROM bajas_masivas bm
                      LEFT JOIN usuarios u ON bm.id_usuario = u.id_usuario
                      LEFT JOIN departamentos d ON bm.id_departamento = d.id_departamento
                      WHERE 1=1 " . $whereHotel . "
                      ORDER BY bm.fecha_baja DESC, bm.id_baja_masiva DESC";

$result_bajas_masivas = mysqli_query($conexion, $sql_bajas_masivas);

$tituloAlcance = $es_super_admin ? 'DEL SISTEMA' : 'DEL HOTEL';
if (!$es_super_admin && $id_hotel && isset($_SESSION['nombre_hotel'])) {
    $tituloAlcance = strtoupper(htmlspecialchars($_SESSION['nombre_hotel']));
}

// Función para escapar salida en PDF y evitar caracteres problemáticos
$esc = function($v) {
    if ($v === null || $v === '') return '';
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
};

// Procesar logo (Bajas Masivas: usar LOGO-BAJA en img/, respaldo hotel.jpg)
$logoHtml = '';
$baseImg = '../img/';
$rutaLogo = null;
foreach (array('LOGO-BAJA.png', 'LOGO-BAJA.jpg', 'LOGO-BAJA.jpeg', 'hotel.jpg') as $archivo) {
    if (file_exists($baseImg . $archivo)) {
        $rutaLogo = $baseImg . $archivo;
        break;
    }
}
if($rutaLogo) {
    try {
        $tipoLogo = strtolower(pathinfo($rutaLogo, PATHINFO_EXTENSION));
        if ($tipoLogo === 'jpeg') $tipoLogo = 'jpg';
        $datosLogo = file_get_contents($rutaLogo);
        if($datosLogo !== false) {
            $base64Logo = base64_encode($datosLogo);
            $logoHtml = '<img src="data:image/' . $tipoLogo . ';base64,' . $base64Logo . '" alt="Logo" style="height: 70px; max-width: 180px;">';
        } else {
            $logoHtml = '<div style="height: 70px; width: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; font-size: 8px;">The Villa Group</div>';
        }
    } catch (Exception $e) {
        $logoHtml = '<div style="height: 70px; width: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; font-size: 8px;">The Villa Group</div>';
    }
} else {
    $logoHtml = '<div style="height: 70px; width: 180px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; font-size: 8px;">The Villa Group</div>';
}

// Generar tabla de bajas individuales
$tablaBajasIndividuales = '';
$totalBajasIndividuales = 0;
$valorTotalBajasIndividuales = 0;

while($baja = mysqli_fetch_assoc($result_bajas_individuales)){
    $totalBajasIndividuales++;
    $valorTotalBajasIndividuales += floatval($baja['valor_residual'] ? $baja['valor_residual'] : 0);
    
    $tablaBajasIndividuales .= '
    <tr>
        <td style="text-align: center; font-size: 9px;">' . $esc($baja['id_baja']) . '</td>
        <td style="font-size: 9px;">' . date('d/m/Y', strtotime($baja['fecha_baja'])) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['tipo_baja']) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['codigo_equipo']) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['descripcion']) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['marca'] . ' ' . $baja['modelo']) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['departamento'] ? $baja['departamento'] : 'N/A') . '</td>
        <td style="text-align: right; font-size: 9px;">$' . number_format(floatval($baja['valor_adquisicion']), 2) . ' ' . $esc($baja['tipo_moneda']) . '</td>
        <td style="text-align: right; font-size: 9px;">$' . number_format(floatval($baja['valor_residual'] ? $baja['valor_residual'] : 0), 2) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['destino_equipo']) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['motivo_baja']) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['observaciones'] ? $baja['observaciones'] : '-') . '</td>
        <td style="font-size: 9px;">' . $esc($baja['usuario_nombre'] . ' ' . $baja['usuario_apellido']) . '</td>
    </tr>';
}
if ($tablaBajasIndividuales === '') {
    $tablaBajasIndividuales = '<tr><td colspan="13" style="text-align: center; font-size: 10px; padding: 15px;">No hay bajas individuales registradas.</td></tr>';
}

// Generar tabla de bajas masivas
$tablaBajasMasivas = '';
$totalBajasMasivas = 0;
$valorTotalBajasMasivas = 0;

while($baja = mysqli_fetch_assoc($result_bajas_masivas)){
    $totalBajasMasivas++;
    $valorTotalBajasMasivas += floatval($baja['valor_residual_total'] ? $baja['valor_residual_total'] : 0);
    
    $tablaBajasMasivas .= '
    <tr>
        <td style="text-align: center; font-size: 9px;">' . $esc($baja['id_baja_masiva']) . '</td>
        <td style="font-size: 9px;">' . date('d/m/Y', strtotime($baja['fecha_baja'])) . '</td>
        <td style="text-align: center; font-size: 9px;">' . $esc($baja['total_equipos']) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['hotel_area'] ? $baja['hotel_area'] : 'N/A') . '</td>
        <td style="font-size: 9px;">' . $esc($baja['departamento'] ? $baja['departamento'] : 'N/A') . '</td>
        <td style="text-align: right; font-size: 9px;">$' . number_format(floatval($baja['valor_residual_total'] ? $baja['valor_residual_total'] : 0), 2) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['destino_equipo']) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['motivo_baja']) . '</td>
        <td style="font-size: 9px;">' . $esc($baja['observaciones'] ? $baja['observaciones'] : '-') . '</td>
        <td style="font-size: 9px;">' . $esc($baja['usuario_nombre'] . ' ' . $baja['usuario_apellido']) . '</td>
    </tr>';
}
if ($tablaBajasMasivas === '') {
    $tablaBajasMasivas = '<tr><td colspan="10" style="text-align: center; font-size: 10px; padding: 15px;">No hay bajas masivas registradas.</td></tr>';
}

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Todas las Bajas del Sistema</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px; 
            margin: 15px;
            line-height: 1.2;
        }
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px;
        }
        .titulo { 
            text-align: center; 
            font-size: 16px; 
            font-weight: bold; 
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .subtitulo {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            color: #333;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 8px;
            margin-bottom: 20px;
        }
        table, th, td { 
            border: 1px solid #000; 
        }
        th, td { 
            padding: 3px; 
            vertical-align: top;
        }
        th { 
            background-color: #f2f2f2; 
            font-weight: bold;
            text-align: center;
        }
        .resumen {
            background-color: #f9f9f9;
            border: 2px solid #333;
            padding: 10px;
            margin: 20px 0;
        }
        .resumen h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #333;
        }
        .resumen p {
            margin: 5px 0;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>' . $logoHtml . '</div>
        <div style="text-align:right;">
            <strong>REPORTE EJECUTIVO</strong><br>
            Sistema de Gestión de Equipos<br>
            Fecha: ' . date('d/m/Y H:i:s') . '
        </div>
    </div>

    <div class="titulo">REPORTE DE BAJAS - ' . $tituloAlcance . '</div>

    <div class="resumen">
        <h3>📊 RESUMEN EJECUTIVO</h3>
        <p><strong>Total de Bajas Individuales:</strong> ' . $totalBajasIndividuales . '</p>
        <p><strong>Total de Bajas Masivas:</strong> ' . $totalBajasMasivas . '</p>
        <p><strong>Total General de Bajas:</strong> ' . ($totalBajasIndividuales + $totalBajasMasivas) . '</p>
        <p><strong>Valor Total de Bajas Individuales:</strong> $' . number_format($valorTotalBajasIndividuales, 2) . '</p>
        <p><strong>Valor Total de Bajas Masivas:</strong> $' . number_format($valorTotalBajasMasivas, 2) . '</p>
        <p><strong>Valor Total General:</strong> $' . number_format($valorTotalBajasIndividuales + $valorTotalBajasMasivas, 2) . '</p>
    </div>

    <div class="subtitulo">📋 BAJAS INDIVIDUALES</div>
    <table>
        <tr>
            <th width="4%">ID</th>
            <th width="7%">FECHA</th>
            <th width="6%">TIPO</th>
            <th width="8%">CÓDIGO</th>
            <th width="12%">DESCRIPCIÓN</th>
            <th width="10%">MARCA/MODELO</th>
            <th width="8%">DEPTO</th>
            <th width="7%">VALOR ORIG.</th>
            <th width="7%">VALOR RES.</th>
            <th width="7%">DESTINO</th>
            <th width="8%">MOTIVO</th>
            <th width="10%">OBSERVACIONES</th>
            <th width="7%">USUARIO</th>
        </tr>
        ' . $tablaBajasIndividuales . '
    </table>

    <div class="subtitulo">📋 BAJAS MASIVAS</div>
    <table>
        <tr>
            <th width="5%">ID</th>
            <th width="8%">FECHA</th>
            <th width="7%">TOTAL EQUIPOS</th>
            <th width="12%">HOTEL/ÁREA</th>
            <th width="10%">DEPARTAMENTO</th>
            <th width="10%">VALOR TOTAL</th>
            <th width="10%">DESTINO</th>
            <th width="12%">MOTIVO</th>
            <th width="13%">OBSERVACIONES</th>
            <th width="10%">USUARIO</th>
        </tr>
        ' . $tablaBajasMasivas . '
    </table>

    <div style="margin-top: 30px; text-align: center; font-size: 8px; color: #666;">
        <p>Reporte generado automáticamente el ' . date('d/m/Y') . ' a las ' . date('H:i:s') . '</p>
        <p>Sistema de Gestión de Equipos - The Villa Group</p>
    </div>
</body>
</html>';

// Configurar DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Generar nombre del archivo
$nombreArchivo = 'Reporte_Todas_Las_Bajas_' . date('Y-m-d_H-i-s') . '.pdf';

// Descargar el PDF
$dompdf->stream($nombreArchivo, array("Attachment" => true));
?>
