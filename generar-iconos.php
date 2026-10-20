<?php
/**
 * Generador de Iconos PWA
 * Crea automáticamente los iconos necesarios desde img/hotel.jpg
 */

// Verificar que existe la imagen fuente
$sourceImage = 'img/hotel.jpg';
if (!file_exists($sourceImage)) {
    die('Error: No se encontró la imagen ' . $sourceImage);
}

// Tamaños de iconos requeridos
$sizes = [
    192 => 'icon-192x192.png',
    512 => 'icon-512x512.png',
    180 => 'apple-touch-icon.png'
];

// Obtener información de la imagen
$imageInfo = getimagesize($sourceImage);
$mimeType = $imageInfo['mime'];

// Crear imagen desde archivo según tipo
switch ($mimeType) {
    case 'image/jpeg':
        $source = imagecreatefromjpeg($sourceImage);
        break;
    case 'image/png':
        $source = imagecreatefrompng($sourceImage);
        break;
    case 'image/gif':
        $source = imagecreatefromgif($sourceImage);
        break;
    default:
        die('Error: Formato de imagen no soportado. Use JPG, PNG o GIF.');
}

if (!$source) {
    die('Error: No se pudo cargar la imagen fuente.');
}

$sourceWidth = imagesx($source);
$sourceHeight = imagesy($source);

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Generador de Iconos PWA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #1a1a1a;
            color: #ffffff;
        }
        .success {
            background: #28a745;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background: #17a2b8;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .icon-preview {
            display: inline-block;
            margin: 10px;
            text-align: center;
            background: #2a2a2a;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #c29a4b;
        }
        .icon-preview img {
            border: 2px solid #c29a4b;
            border-radius: 5px;
            margin: 5px;
        }
        button {
            background: #c29a4b;
            color: #1a1a1a;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin: 5px;
        }
        button:hover {
            background: #d4a85c;
        }
    </style>
</head>
<body>
    <h1>🎨 Generador de Iconos PWA</h1>";

$generated = [];
$errors = [];

foreach ($sizes as $size => $filename) {
    // Crear imagen destino
    $dest = imagecreatetruecolor($size, $size);
    
    // Hacer fondo blanco (para transparencia en PNG)
    $white = imagecolorallocate($dest, 255, 255, 255);
    imagefill($dest, 0, 0, $white);
    
    // Calcular dimensiones manteniendo proporción
    $ratio = min($size / $sourceWidth, $size / $sourceHeight);
    $newWidth = $sourceWidth * $ratio;
    $newHeight = $sourceHeight * $ratio;
    
    // Centrar la imagen
    $x = ($size - $newWidth) / 2;
    $y = ($size - $newHeight) / 2;
    
    // Redimensionar con alta calidad
    imagecopyresampled(
        $dest, $source,
        $x, $y, 0, 0,
        $newWidth, $newHeight,
        $sourceWidth, $sourceHeight
    );
    
    // Guardar como PNG
    $filepath = 'img/' . $filename;
    if (imagepng($dest, $filepath, 9)) {
        $generated[] = [
            'file' => $filename,
            'path' => $filepath,
            'size' => $size,
            'url' => $filepath
        ];
        imagedestroy($dest);
    } else {
        $errors[] = "Error al generar: $filename";
    }
}

imagedestroy($source);

// Mostrar resultados
if (count($generated) > 0) {
    echo "<div class='success'>
        <h2>✅ Iconos Generados Exitosamente</h2>
        <p>Se generaron " . count($generated) . " iconos correctamente.</p>
    </div>";
    
    echo "<div class='info'>
        <h3>📱 Iconos Generados:</h3>";
    
    foreach ($generated as $icon) {
        echo "<div class='icon-preview'>
            <img src='{$icon['url']}' alt='{$icon['file']}' style='width: {$icon['size']}px; height: {$icon['size']}px;'>
            <p><strong>{$icon['file']}</strong></p>
            <p>{$icon['size']} x {$icon['size']} píxeles</p>
        </div>";
    }
    
    echo "</div>";
    
    echo "<div class='info'>
        <h3>📝 Próximos Pasos:</h3>
        <ol>
            <li>Los iconos ya están guardados en la carpeta <code>img/</code></li>
            <li>El archivo <code>manifest.json</code> se actualizará automáticamente</li>
            <li>Recarga la página para ver los cambios</li>
            <li>Prueba la PWA en: <a href='test-pwa.html' style='color: #c29a4b;'>test-pwa.html</a></li>
        </ol>
    </div>";
    
    // Actualizar manifest.json automáticamente
    $manifestPath = 'manifest.json';
    if (file_exists($manifestPath)) {
        $manifest = json_decode(file_get_contents($manifestPath), true);
        
        if ($manifest) {
            $manifest['icons'] = [
                [
                    'src' => 'img/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => 'img/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ]
            ];
            
            // Actualizar también el apple-touch-icon en el HTML
            file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            
            echo "<div class='success'>
                <h3>✅ manifest.json Actualizado</h3>
                <p>El archivo manifest.json ha sido actualizado automáticamente con los nuevos iconos.</p>
            </div>";
        }
    }
    
} else {
    echo "<div class='error'>
        <h2>❌ Error al Generar Iconos</h2>
        <p>No se pudieron generar los iconos. Verifica los permisos de la carpeta img/</p>
    </div>";
}

if (count($errors) > 0) {
    echo "<div class='error'>
        <h3>Errores:</h3>
        <ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul></div>";
}

echo "<div style='margin-top: 30px;'>
    <button onclick='window.location.reload()'>🔄 Regenerar Iconos</button>
    <button onclick='window.location.href=\"test-pwa.html\"'>🧪 Probar PWA</button>
    <button onclick='window.location.href=\"index.php\"'>🏠 Ir al Sistema</button>
</div>";

echo "</body></html>";
?>

