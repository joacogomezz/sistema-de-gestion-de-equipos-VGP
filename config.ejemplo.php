<?php
/**
 * EJEMPLO DE CONFIGURACIÓN
 * Copia este archivo como config.php y ajusta los valores.
 * Si copias el proyecto a otra carpeta (ej. Escritorio), asegúrate de que config.php
 * tenga la contraseña correcta de MariaDB (DB_PASS), sino verás "Access denied (using password: NO)".
 */

// ========== BASE DE DATOS ==========
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'PON_AQUI_LA_CONTRASEÑA_DE_MARIADB');  // ¡Obligatorio si root tiene contraseña!
define('DB_NAME', 'hotel_equipos');

// ========== RUTA DEL PROYECTO ==========
define('RUTA_BASE', '');  // '' si entras por http://localhost:8000
