<?php
define('PDF_OUTPUT', true);
ob_start();

use Dompdf\Dompdf;
use Dompdf\Options;

require_once "../seguridad.php";
requerirAutenticacion();

require_once "../clases/Conexion.php";
require_once "../clases/Resguardos.php";
require_once "../librerias/dompdf/autoload.inc.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
	die('ID de resguardo no válido.');
}

$obj = new Resguardos();
$d = $obj->obtenerDatosResguardo($id);
if (!$d) {
	die('Resguardo no encontrado.');
}

$esc = function($v) {
	if ($v === null || $v === '') return '';
	return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
};

// Logo LOGO-BAJA (misma lógica que reporte de bajas), respaldo hotel.jpg
$logoHtml = '';
$baseImg = '../img/';
$rutaLogo = null;
foreach (array('LOGO-BAJA.png', 'LOGO-BAJA.jpg', 'LOGO-BAJA.jpeg', 'hotel.jpg') as $archivo) {
	if (file_exists($baseImg . $archivo)) {
		$rutaLogo = $baseImg . $archivo;
		break;
	}
}
if ($rutaLogo) {
	$tipoLogo = strtolower(pathinfo($rutaLogo, PATHINFO_EXTENSION));
	if ($tipoLogo === 'jpeg') $tipoLogo = 'jpg';
	$datosLogo = @file_get_contents($rutaLogo);
	if ($datosLogo !== false) {
		$base64Logo = base64_encode($datosLogo);
		$logoHtml = '<img src="data:image/' . $tipoLogo . ';base64,' . $base64Logo . '" alt="Logo" style="height: 70px; max-width: 200px;">';
	}
}
if ($logoHtml === '') {
	$logoHtml = '<div style="height: 70px; width: 180px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 1px solid #ccc; font-size: 10px;">The Villa Group</div>';
}

$fechaResguardo = $d['fecha_resguardo'] ? date('Y-m-d', strtotime($d['fecha_resguardo'])) : '';
$departamentoSistemas = 'DEPARTAMENTO DE SISTEMAS';

$observacionesTexto = '
<ol style="margin: 8px 0 0 18px; padding: 0; font-size: 9.5px; line-height: 1.32;">
<li>No insertar software sin licencia, sin antes obtener la autorización por escrito del Departamento de Sistemas de Operadora Palmar Cabo, S.A. de C.V. (Villa del Palmar) perteneciente a The Villagroup. En caso contrario seré responsable de cualquier acto legal que se realice en contra de la empresa Villa del Palmar por el hecho de instalar software no licenciado legalmente.</li>
<li>El equipo se asigna de manera unipersonal y es intransferible.</li>
<li>En caso de robo o daño del equipo por descuido o mal uso de mi parte, me comprometo a cubrir el valor comercial total del mismo. Para tal efecto me obligo a levantar el acta de denuncia correspondiente ante la autoridad respectiva, pues estoy consciente de que para la tramitación del pago ante la compañía de seguros se requiere la presentación previa del acta de denuncia ya mencionada. Y realizar todos los trámites internos que esta acción conlleve.</li>
<li>El equipo, software y la información que contiene son propiedad de Operadora Palmar Cabo, S.A. de C.V. (Villa del Palmar) perteneciente a The Villagroup; dicha información es de carácter estrictamente confidencial, por lo que no se debe reproducir o copiar por cualquier medio, modo, vía o conducto de comunicación para usos ajenos a las empresas mencionadas sin autorización expresada y escrita del Director de mi área; en caso de falta a lo antes mencionado me haré acreedor a las sanciones que señalan en el código penal y las leyes respectivas.</li>
<li>Para el caso de entrega del equipo, respecto a la información que se encuentra almacenada en las unidades físicas del mismo, expreso mi conformidad que aquella información que sea de interés para Operadora Palmar Cabo, S.A. de C.V. (Villa del Palmar) deberá ser respaldada, y que en un término de tres días a partir de la entrega del equipo, esta información podrá ser eliminada y no podrá ser recuperada, liberando desde ahora cualquier responsabilidad al departamento de Sistemas de Operadora Palmar Cabo, S.A. de C.V. (Villa del Palmar).</li>
<li>En caso de haber realizado respaldo de información:<br>La restauración del respaldo de mi información personal en el nuevo equipo está completa y certifico que no falta ningún archivo, lo cual autoriza al área de Sistemas a eliminar la información del equipo que se retira.</li>
</ol>';

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resguardo de Equipo</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; line-height: 1.35; color: #333; }
        .header-wrap { position: relative; width: 100%; min-height: 75px; margin-bottom: 91px; }
        .header-titulo-centro { width: 100%; text-align: center; }
        .header-logo-izq { position: absolute; left: 0; top: 0; }
        .titulo-principal { font-size: 16px; font-weight: bold; margin: 0 0 2px 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .subtitulo-depto { font-size: 11px; font-weight: bold; margin: 0; text-transform: uppercase; }
        .info-general { margin: 18px 0 12px 0; }
        .info-general .fila { margin: 6px 0; }
        .info-general .etiqueta { font-weight: bold; }
        .info-linea-uno { width: 100%; border: none; border-collapse: collapse; margin: 0; padding: 0; font-size: 11px; line-height: 1.35; }
        .info-linea-uno td { border: none; padding: 0 15px 4px 0; vertical-align: baseline; }
        .info-linea-uno td.col-1 { width: 33%; }
        .info-linea-uno td.col-2 { width: 34%; }
        .info-linea-uno td.col-3 { width: 33%; }
        .tabla-equipo { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 10px; }
        .tabla-equipo th, .tabla-equipo td { border: 1px solid #333; padding: 8px; text-align: left; }
        .tabla-equipo th { background: #e8e8e8; font-weight: bold; }
        .obs-block { margin-top: 2.5cm; }
        .obs-titulo { font-weight: bold; margin: 18px 0 6px 0; font-size: 11px; }
        .firmas { margin-top: 2cm; clear: both; }
        .firmas table { width: 100%; border-collapse: collapse; }
        .firmas td { vertical-align: top; text-align: center; font-size: 9px; }
        .firmas .linea-firma { border-bottom: 1px solid #000; height: 45px; width: 45%; }
        .firmas .separador { width: 10%; border: none; }
        .firmas .texto-firma { padding: 8px 15px 0; width: 45%; }
        .firmas .nombre-usuario { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header-wrap">
        <div class="header-titulo-centro"><div class="titulo-principal">Resguardo de equipo</div><div class="subtitulo-depto">' . $departamentoSistemas . '</div></div>
        <div class="header-logo-izq">' . $logoHtml . '</div>
    </div>

    <div class="info-general">
        <table class="info-linea-uno">
        <tr>
            <td class="col-1"><span class="etiqueta">USUARIO:</span> ' . $esc($d['usuario']) . '</td>
            <td class="col-2"><span class="etiqueta">Fecha:</span> ' . $esc($fechaResguardo) . '</td>
            <td class="col-3"><span class="etiqueta">Factura:</span> ' . $esc($d['numero_factura']) . '</td>
        </tr>
        <tr>
            <td class="col-1"><span class="etiqueta">Área:</span> ' . $esc($d['area_destino']) . '</td>
            <td class="col-2"></td>
            <td class="col-3"><span class="etiqueta">Puesto:</span> ' . $esc($d['puesto'] ?? '') . '</td>
        </tr>
        </table>
    </div>

    <table class="tabla-equipo">
        <tr>
            <th style="width: 25%;">Tipo</th>
            <th style="width: 25%;">Marca</th>
            <th style="width: 25%;">Modelo</th>
            <th style="width: 25%;">Serie</th>
        </tr>
        <tr>
            <td>' . $esc($d['tipo_equipo']) . '</td>
            <td>' . $esc($d['marca'] ?? '') . '</td>
            <td>' . $esc($d['modelo']) . '</td>
            <td>' . $esc($d['serie']) . '</td>
        </tr>
    </table>

    <div class="obs-block">
    <div class="obs-titulo">Observaciones:</div>
    ' . $observacionesTexto . '
    </div>

    <div class="firmas">
        <table>
            <tr>
                <td class="linea-firma"></td>
                <td class="separador"></td>
                <td class="linea-firma"></td>
            </tr>
            <tr>
                <td class="texto-firma">NOMBRE Y FIRMA DEL USUARIO DE QUIEN RECIBE EL EQUIPO<br><span class="nombre-usuario">' . $esc($d['usuario']) . '</span></td>
                <td class="separador"></td>
                <td class="texto-firma">FIRMA DEPARTAMENTO DE SISTEMAS</td>
            </tr>
        </table>
    </div>
</body>
</html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nombreArchivo = 'Resguardo_' . $esc($d['codigo_equipo']) . '_' . date('Y-m-d') . '.pdf';
ob_end_clean();
$dompdf->stream($nombreArchivo, array("Attachment" => empty($_GET['ver'])));
