<?php
/**
 * ARCHIVO DE SEGURIDAD CENTRALIZADO
 * Sistema de Gestión de Equipos - Hotel Villa Group
 * 
 * Este archivo contiene todas las funciones de seguridad
 * para proteger el sistema contra accesos no autorizados
 */

// ========== CONFIGURACIÓN DE SESIÓN (ajustar según necesidad) ==========
define('SESION_TIEMPO_MAXIMO_SEGUNDOS', 3600);   // 1 hora: tiempo máximo de vida de la sesión
define('SESION_INACTIVIDAD_SEGUNDOS', 1200);      // 10 minutos: cierre por inactividad
define('SESION_REGENERAR_ID_SEGUNDOS', 3600);     // 5 minutos: regenerar ID de sesión
// ======================================================================

// Configuración de sesiones seguras (solo si aún no está iniciada)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // 1 en producción con HTTPS
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.cookie_lifetime', 0);
    session_start();
}

/**
 * Función para validar si el usuario está autenticado
 * @return bool true si está autenticado, false si no
 */
function validarSesion() {
    // Verificar si la sesión existe
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['iduser'])) {
        return false;
    }
    // Asegurar id_hotel en sesión (multi-hotel)
    if (!isset($_SESSION['id_hotel'])) {
        $_SESSION['id_hotel'] = 1;
        $_SESSION['nombre_hotel'] = 'Hotel';
    }
    
    // Verificar si la sesión fue marcada como inválida
    if (isset($_SESSION['sesion_invalida']) && $_SESSION['sesion_invalida'] === true) {
        cerrarSesion();
        return false;
    }
    
    // Verificar que la sesión no sea muy antigua (tiempo máximo configurable)
    if (isset($_SESSION['tiempo_inicio'])) {
        $tiempo_sesion = time() - $_SESSION['tiempo_inicio'];
        if ($tiempo_sesion > SESION_TIEMPO_MAXIMO_SEGUNDOS) {
            cerrarSesion();
            return false;
        }
    } else {
        cerrarSesion();
        return false;
    }
    
    // Verificar tiempo de inactividad (configurable)
    if (isset($_SESSION['ultima_actividad'])) {
        $tiempo_inactivo = time() - $_SESSION['ultima_actividad'];
        if ($tiempo_inactivo > SESION_INACTIVIDAD_SEGUNDOS) {
            cerrarSesion();
            return false;
        }
    } else {
        cerrarSesion();
        return false;
    }
    
    // Validar que el usuario existe en la base de datos (consulta preparada para evitar inyección SQL)
    try {
        $dirSeguridad = dirname(__FILE__);
        require_once $dirSeguridad . "/clases/Conexion.php";
        $c = new conectar();
        $conexion = $c->conexion();
        
        $usuario = $_SESSION['usuario'];
        $iduser = (int) $_SESSION['iduser'];
        
        $stmt = mysqli_prepare($conexion, "SELECT id_usuario, email FROM usuarios WHERE email = ? AND id_usuario = ?");
        if (!$stmt) {
            cerrarSesion();
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $usuario, $iduser);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result || mysqli_num_rows($result) == 0) {
            mysqli_stmt_close($stmt);
            cerrarSesion();
            return false;
        }
        mysqli_stmt_close($stmt);
        // Actualizar id_hotel en sesión por si cambió en BD (multi-hotel)
        $chk = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'id_hotel'");
        if ($chk && mysqli_num_rows($chk) > 0) {
            $r = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT id_hotel FROM usuarios WHERE id_usuario = $iduser LIMIT 1"));
            if ($r && !empty($r['id_hotel'])) {
                $_SESSION['id_hotel'] = (int) $r['id_hotel'];
                require_once $dirSeguridad . "/clases/Hoteles.php";
                $hoteles = new Hoteles();
                $_SESSION['nombre_hotel'] = $hoteles->getNombre($r['id_hotel']);
            }
        }
        // Actualizar rol en sesión por si cambió en BD.
        $chkRol = mysqli_query($conexion, "SHOW COLUMNS FROM usuarios LIKE 'rol'");
        if ($chkRol && mysqli_num_rows($chkRol) > 0) {
            $rRol = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT rol FROM usuarios WHERE id_usuario = $iduser LIMIT 1"));
            $_SESSION['rol'] = (!empty($rRol['rol'])) ? strtoupper(trim((string)$rRol['rol'])) : 'ADMIN';
        } elseif (!isset($_SESSION['rol']) || trim((string)$_SESSION['rol']) === '') {
            $_SESSION['rol'] = 'ADMIN';
        }
        
    } catch (Exception $e) {
        cerrarSesion();
        return false;
    }
    
    // Actualizar última actividad
    $_SESSION['ultima_actividad'] = time();
    
    return true;
}

/**
 * Función para cerrar sesión de forma segura
 */
function cerrarSesion() {
    // Evitar session_destroy() duplicado en la misma petición (p. ej. validarSesion + requerirAutenticacion):
    // tras destruir la sesión, session_status() ya no es ACTIVE y un segundo intento emite warning y rompe los headers.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    // Limpiar todas las variables de sesión
    session_unset();
    $_SESSION = array();
    
    // Destruir la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
    
    // Limpiar cualquier salida previa
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Forzar limpieza de cache del navegador solo si los headers no fueron enviados
    if (!headers_sent()) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    }
}

/**
 * Función para regenerar ID de sesión
 */
function regenerarIdSesion() {
    // Regenerar ID de sesión para prevenir session fixation
    session_regenerate_id(true);
}

/**
 * Función para establecer headers de seguridad
 */
function establecerHeadersSeguridad() {
    // Prevenir clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevenir MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Habilitar XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy básico
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'");
    
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
}

/**
 * Función para redirigir a login si no está autenticado
 */
function requerirAutenticacion() {
    // Verificar si los headers ya fueron enviados
    if (headers_sent()) {
        // Si los headers ya fueron enviados, usar JavaScript para redirigir
        if (!validarSesion()) {
            echo '<script>window.location.href = "../index.php?mensaje=sesion_expirada";</script>';
            exit();
        }
        return;
    }
    
    // Forzar limpieza de cache del navegador
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    if (!validarSesion()) {
        // Limpiar cualquier salida previa
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Cerrar sesión completamente
        cerrarSesion();
        
        // Redirigir a login con parámetro de sesión expirada
        header('Location: ../index.php?mensaje=sesion_expirada');
        exit();
    }

    // Restricción por rol: TOALLEROS solo puede usar el módulo de toallas.
    $rolSesion = isset($_SESSION['rol']) ? strtoupper(trim((string)$_SESSION['rol'])) : 'ADMIN';
    if ($rolSesion === 'TOALLEROS') {
        $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
        $basename = strtolower((string)basename($script));
        $esVista = (strpos($script, '/vistas/') !== false);
        $permitidas = array('inicio.php', 'historialtoallas.php');
        $esPermitida = in_array($basename, $permitidas, true);
        if ($esVista && !$esPermitida) {
            // Ruta absoluta para evitar bucles por rutas relativas según carpeta actual.
            $basePath = dirname(dirname($script));
            if ($basePath === '\\' || $basePath === '.' || $basePath === '/') {
                $basePath = '';
            }
            $destino = rtrim((string)$basePath, '/') . '/vistas/inicio.php';
            header('Location: ' . $destino);
            exit();
        }
    }
}

/**
 * Función para validar token CSRF (opcional para futuras implementaciones)
 */
function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Función para validar token CSRF
 */
function validarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Función para manejar navegación hacia atrás
 */
function manejarNavegacionAtras() {
    // Si se detecta navegación hacia atrás, cerrar sesión
    if (isset($_SESSION['navegacion_atras'])) {
        cerrarSesion();
        header('Location: ../index.php?mensaje=sesion_finalizada');
        exit();
    }
    
    // Marcar que se puede detectar navegación hacia atrás
    $_SESSION['navegacion_atras'] = true;
}

/**
 * Función para invalidar sesión por navegación hacia atrás
 */
function invalidarSesionPorNavegacion() {
    // Marcar la sesión como inválida
    $_SESSION['sesion_invalida'] = true;
    cerrarSesion();
}


// No enviar headers de seguridad ni regenerar sesión cuando se genera PDF (evita conflictos con FPDF/Dompdf)
if (!defined('PDF_OUTPUT')) {
    establecerHeadersSeguridad();
    if (isset($_SESSION['iduser'])) {
        if (!isset($_SESSION['ultima_regeneracion'])) {
            $_SESSION['ultima_regeneracion'] = time();
        } else {
            $tiempo_desde_regeneracion = time() - $_SESSION['ultima_regeneracion'];
            if ($tiempo_desde_regeneracion > SESION_REGENERAR_ID_SEGUNDOS) {
                regenerarIdSesion();
                $_SESSION['ultima_regeneracion'] = time();
            }
        }
    }
}
?>
