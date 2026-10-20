<?php
// Búsqueda de equipos (usa la conexión principal del sistema)

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Iniciar sesión
session_start();

// Verificar autenticación
if (!isset($_SESSION['iduser'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Verificar que se recibió el código
$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : (isset($_GET['codigo']) ? trim($_GET['codigo']) : '');

if (empty($codigo)) {
    echo json_encode([]);
    exit;
}

// Usar la conexión principal del sistema (hotel_equipos)
require_once __DIR__ . '/../../clases/Conexion.php';
$c = new conectar();
$conexion = $c->conexion();

if (!$conexion) {
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Escapar el código para prevenir inyección SQL
$codigo = mysqli_real_escape_string($conexion, $codigo);

// Super Admin (id=1) ve todos; el resto filtra por su hotel
$es_super_admin = isset($_SESSION['iduser']) && (int)$_SESSION['iduser'] === 1;
$id_hotel = $es_super_admin ? null : (isset($_SESSION['id_hotel']) ? (int) $_SESSION['id_hotel'] : null);
$joinHotel = '';
$whereHotel = '';
if ($id_hotel !== null) {
    $chk = @mysqli_query($conexion, "SHOW COLUMNS FROM departamentos LIKE 'id_hotel'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $joinHotel = " INNER JOIN departamentos d ON e.id_departamento = d.id_departamento ";
        $whereHotel = " AND d.id_hotel = '" . $id_hotel . "' ";
    }
}
$sql = "SELECT e.id_equipo, e.codigo_equipo, e.descripcion, e.marca, e.modelo, 
                e.valor_adquisicion, e.tipo_moneda, e.estado, e.ubicacion
        FROM equipos e $joinHotel
        WHERE e.estado != 'Dado de Baja' 
        AND (e.codigo_equipo LIKE '%$codigo%' OR e.codigo_equipo = '$codigo') $whereHotel
        ORDER BY e.codigo_equipo
        LIMIT 10";

$result = mysqli_query($conexion, $sql);

$equipos = [];

if ($result && mysqli_num_rows($result) > 0) {
    while ($equipo = mysqli_fetch_assoc($result)) {
        $equipos[] = [
            'id_equipo' => (int)$equipo['id_equipo'],
            'codigo_equipo' => $equipo['codigo_equipo'],
            'descripcion' => $equipo['descripcion'],
            'marca' => $equipo['marca'],
            'modelo' => $equipo['modelo'],
            'valor_adquisicion' => (float)$equipo['valor_adquisicion'],
            'tipo_moneda' => $equipo['tipo_moneda'],
            'estado' => $equipo['estado'],
            'ubicacion' => $equipo['ubicacion']
        ];
    }
}

// Devolver JSON
echo json_encode($equipos, JSON_UNESCAPED_UNICODE);
exit;
?>
