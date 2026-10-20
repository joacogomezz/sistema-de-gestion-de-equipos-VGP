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

// Obtener equipos activos (del hotel del usuario o todos si Super Admin)
$sql_equipos_activos = "SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
                               e.numero_serie, e.estado, e.ubicacion, e.fecha_ingreso, 
                               e.valor_adquisicion, e.tipo_moneda, e.fecha_registro,
                               d.nombre as departamento,
                               u.nombre as usuario_nombre, u.apellido as usuario_apellido
                        FROM equipos e
                        LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                        LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
                        WHERE e.estado = 'Activo' " . $whereHotel . "
                        ORDER BY e.fecha_ingreso DESC, e.id_equipo DESC";

$result_equipos_activos = mysqli_query($conexion, $sql_equipos_activos);

// Obtener estadísticas por departamento
$sql_estadisticas = "SELECT d.nombre as departamento, 
                            COUNT(e.id_equipo) as total_equipos,
                            SUM(e.valor_adquisicion) as valor_total
                     FROM equipos e
                     LEFT JOIN departamentos d ON e.id_departamento = d.id_departamento
                     WHERE e.estado = 'Activo' " . $whereHotel . "
                     GROUP BY d.id_departamento, d.nombre
                     ORDER BY total_equipos DESC";

$result_estadisticas = mysqli_query($conexion, $sql_estadisticas);

$tituloAlcance = $es_super_admin ? 'DEL SISTEMA' : 'DEL HOTEL';
if (!$es_super_admin && $id_hotel && isset($_SESSION['nombre_hotel'])) {
    $tituloAlcance = strtoupper(htmlspecialchars($_SESSION['nombre_hotel']));
}

// Función para escapar salida en PDF
$esc = function($v) {
    if ($v === null || $v === '') return '';
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
};

// Procesar logo
$logoHtml = '';
$rutaLogo = '../img/hotel.jpg';
if(file_exists($rutaLogo)) {
    try {
        $tipoLogo = strtolower(pathinfo($rutaLogo, PATHINFO_EXTENSION));
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

// Generar tabla de equipos activos
$tablaEquiposActivos = '';
$totalEquiposActivos = 0;
$valorTotalEquiposActivos = 0;

while($equipo = mysqli_fetch_assoc($result_equipos_activos)){
    $totalEquiposActivos++;
    $valorTotalEquiposActivos += floatval($equipo['valor_adquisicion'] ? $equipo['valor_adquisicion'] : 0);
    
    $tablaEquiposActivos .= '
    <tr>
        <td style="text-align: center; font-size: 9px;">' . $esc($equipo['id_equipo']) . '</td>
        <td style="font-size: 9px;">' . $esc($equipo['codigo_equipo']) . '</td>
        <td style="font-size: 9px;">' . $esc($equipo['descripcion']) . '</td>
        <td style="font-size: 9px;">' . $esc($equipo['marca']) . '</td>
        <td style="font-size: 9px;">' . $esc($equipo['modelo']) . '</td>
        <td style="font-size: 9px;">' . $esc($equipo['numero_serie'] ? $equipo['numero_serie'] : 'N/A') . '</td>
        <td style="font-size: 9px;">' . $esc($equipo['estado']) . '</td>
        <td style="font-size: 9px;">' . $esc($equipo['departamento'] ? $equipo['departamento'] : 'N/A') . '</td>
        <td style="font-size: 9px;">' . $esc($equipo['ubicacion'] ? $equipo['ubicacion'] : 'N/A') . '</td>
        <td style="font-size: 9px;">' . ($equipo['fecha_ingreso'] ? date('d/m/Y', strtotime($equipo['fecha_ingreso'])) : 'N/A') . '</td>
        <td style="text-align: right; font-size: 9px;">$' . number_format(floatval($equipo['valor_adquisicion']), 2) . '</td>
        <td style="font-size: 9px;">' . $esc($equipo['tipo_moneda']) . '</td>
        <td style="font-size: 9px;">' . $esc($equipo['usuario_nombre'] . ' ' . $equipo['usuario_apellido']) . '</td>
    </tr>';
}
if ($tablaEquiposActivos === '') {
    $tablaEquiposActivos = '<tr><td colspan="13" style="text-align: center; font-size: 10px; padding: 15px;">No hay equipos activos registrados.</td></tr>';
}

// Generar tabla de estadísticas por departamento
$tablaEstadisticas = '';
while($estadistica = mysqli_fetch_assoc($result_estadisticas)){
    $tablaEstadisticas .= '
    <tr>
        <td style="font-size: 9px;">' . $esc($estadistica['departamento'] ? $estadistica['departamento'] : 'Sin Departamento') . '</td>
        <td style="text-align: center; font-size: 9px;">' . $esc($estadistica['total_equipos']) . '</td>
        <td style="text-align: right; font-size: 9px;">$' . number_format(floatval($estadistica['valor_total']), 2) . '</td>
    </tr>';
}
if ($tablaEstadisticas === '') {
    $tablaEstadisticas = '<tr><td colspan="3" style="text-align: center; font-size: 10px; padding: 15px;">No hay datos por departamento.</td></tr>';
}

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Equipos Activos del Sistema</title>
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
        .estadisticas {
            background-color: #e8f5e8;
            border: 2px solid #28a745;
            padding: 10px;
            margin: 20px 0;
        }
        .estadisticas h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #155724;
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

    <div class="titulo">REPORTE DE EQUIPOS ACTIVOS - ' . $tituloAlcance . '</div>

    <div class="resumen">
        <h3>📊 RESUMEN EJECUTIVO</h3>
        <p><strong>Total de Equipos Activos:</strong> ' . $totalEquiposActivos . '</p>
        <p><strong>Valor Total de Equipos Activos:</strong> $' . number_format($valorTotalEquiposActivos, 2) . '</p>
        <p><strong>Valor Promedio por Equipo:</strong> $' . ($totalEquiposActivos > 0 ? number_format($valorTotalEquiposActivos / $totalEquiposActivos, 2) : '0.00') . '</p>
        <p><strong>Fecha de Generación:</strong> ' . date('d/m/Y H:i:s') . '</p>
    </div>

    <div class="estadisticas">
        <h3>📈 ESTADÍSTICAS POR DEPARTAMENTO</h3>
        <table style="width: 50%; margin: 0 auto;">
            <tr>
                <th width="60%">DEPARTAMENTO</th>
                <th width="20%">TOTAL EQUIPOS</th>
                <th width="20%">VALOR TOTAL</th>
            </tr>
            ' . $tablaEstadisticas . '
        </table>
    </div>

    <div class="subtitulo">📋 INVENTARIO DE EQUIPOS ACTIVOS</div>
    <table>
        <tr>
            <th width="4%">ID</th>
            <th width="8%">CÓDIGO</th>
            <th width="14%">DESCRIPCIÓN</th>
            <th width="7%">MARCA</th>
            <th width="7%">MODELO</th>
            <th width="7%">No. SERIE</th>
            <th width="6%">ESTADO</th>
            <th width="9%">DEPARTAMENTO</th>
            <th width="8%">UBICACIÓN FÍSICA</th>
            <th width="7%">F. INGRESO</th>
            <th width="7%">VALOR</th>
            <th width="5%">MONEDA</th>
            <th width="8%">REGISTRADO POR</th>
        </tr>
        ' . $tablaEquiposActivos . '
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
$nombreArchivo = 'Reporte_Equipos_Activos_' . date('Y-m-d_H-i-s') . '.pdf';

// Descargar el PDF
$dompdf->stream($nombreArchivo, array("Attachment" => true));
?>
