# Guía completa: Iniciar el sistema con MariaDB + PHP (sin XAMPP)

Esta guía incluye todos los pasos para poner en marcha el sistema sin XAMPP: instalación de MariaDB y PHP, configuración, posibles errores y cómo iniciar el servidor.

**Índice general del proyecto:** `README.md`. Para exponer el sistema por internet (ngrok) o inicio automático al encender el PC: **`DESPLIEGUE_Y_ACCESO_REMOTO.md`**.

---

## 1. Requisitos previos

- **MariaDB** instalado y en ejecución.
- **PHP** instalado (con extensión `mysqli` para conectar a MariaDB).
- El proyecto en una carpeta (ej. `C:\Users\sistemas\Desktop\ventasAlmacen22` o `C:\xampp\htdocs\ventasAlmacen22`).

---

## 2. Crear la base de datos (si aún no existe)

1. Abre **MariaDB** (consola `mysql`, HeidiSQL, DBeaver, etc.) y conéctate como administrador (usuario `root`).
2. Crea la base e importa las tablas ejecutando el script del proyecto:
   - Desde **consola** (sustituye usuario y ruta):
     ```bash
     mysql -u root -p < "C:\ruta\al\proyecto\bd\hotel_equipos.sql"
     ```
   - O desde **HeidiSQL/DBeaver**: abre el archivo **`bd/hotel_equipos.sql`** del proyecto y ejecútalo.
3. Comprueba que exista la base **`hotel_equipos`** y que tenga las tablas (usuarios, departamentos, equipos, resguardos, etc.).

---

## 3. Configurar `config.php`

Edita el archivo **`config.php`** que está en la **misma carpeta desde la que vas a ejecutar el servidor** (si tienes el proyecto en el Escritorio, edita el `config.php` del Escritorio).

### Base de datos

```php
// ========== BASE DE DATOS ==========
define('DB_HOST', 'localhost');      // Siempre "localhost" o "127.0.0.1" si MariaDB está en tu PC
define('DB_USER', 'root');          // Usuario de MariaDB (normalmente "root")
define('DB_PASS', 'TU_CONTRASEÑA'); // La contraseña de ese usuario en MariaDB
define('DB_NAME', 'hotel_equipos');
```

- **DB_HOST:** Si MariaDB está en tu misma PC, usa `localhost` o `127.0.0.1`. No se “saca” de ningún sitio; es la dirección del servidor de base de datos.
- **DB_USER:** El usuario con el que te conectas a MariaDB (ej. `root`).
- **DB_PASS:** La contraseña de ese usuario. Debe ser **exactamente** la misma que en MariaDB. Si el usuario no tiene contraseña, deja `''`.
- **DB_NAME:** Nombre de la base: `hotel_equipos`.

### Ruta del proyecto (RUTA_BASE)

Si entras al sistema por **http://localhost:8000** (sin subcarpeta en la URL), deja:

```php
define('RUTA_BASE', '');
```

Guarda **`config.php`**.

---

## 4. Corregir error de autenticación en MariaDB (auth_gssapi_client)

Si al abrir la página aparece:

**`The server requested authentication method unknown to the client [auth_gssapi_client]`**

significa que el usuario de MariaDB está usando un plugin de autenticación que PHP no soporta. Hay que cambiar a **mysql_native_password**.

1. Conéctate a MariaDB como administrador (consola o HeidiSQL).
2. Ejecuta (cambia la contraseña si usas otra):

   **Si `root` tiene contraseña:**
   ```sql
   ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD('tu_contraseña');
   FLUSH PRIVILEGES;
   ```

   **Si `root` no tiene contraseña:**
   ```sql
   ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password;
   FLUSH PRIVILEGES;
   ```

3. La contraseña que pongas aquí debe ser la misma que luego uses en **`config.php`** en `DB_PASS`.

---

## 5. Corregir error "Access denied (using password: NO)"

Si aparece:

**`Access denied for user 'root'@'localhost' (using password: NO)`**

significa que la aplicación está intentando conectarse **sin contraseña**, pero tu usuario en MariaDB sí tiene (o exige) contraseña.

1. **Revisa que estés editando el `config.php` correcto:** el que está en la carpeta desde la que ejecutas `php -S localhost:8000`. Si el proyecto está en el Escritorio, edita `C:\Users\...\Desktop\ventasAlmacen22\config.php`.
2. En ese **`config.php`** pon en **`DB_PASS`** la contraseña real del usuario (ej. `root`) en MariaDB:
   ```php
   define('DB_PASS', 'TU_CONTRASEÑA');  // La misma que en MariaDB
   ```
3. Si en MariaDB el usuario no tiene contraseña, en `config.php` deja:
   ```php
   define('DB_PASS', '');
   ```
4. Guarda y vuelve a cargar **http://localhost:8000**.

Para asegurar que la contraseña de `root` en MariaDB sea la que quieres, puedes ejecutar en MariaDB:

```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'TU_CONTRASEÑA';
FLUSH PRIVILEGES;
```

(y usar `TU_CONTRASEÑA` en `DB_PASS` de `config.php`).

---

## 6. Iniciar el servidor PHP

Tienes que ejecutar PHP desde la **carpeta del proyecto** (la que contiene `index.php`, `config.php`, etc.).

### Opción A – Línea de comandos

1. Abre **PowerShell** o **Símbolo del sistema**.
2. Ve a la carpeta del proyecto, por ejemplo:
   ```bash
   cd C:\Users\sistemas\Desktop\ventasAlmacen22
   ```
   (o la ruta donde tengas el proyecto).
3. Ejecuta:
   ```bash
   php -S localhost:8000
   ```
4. Abre el navegador en: **http://localhost:8000**

Si el sistema no encuentra `php`, usa la ruta completa del ejecutable de PHP, por ejemplo:
```bash
"C:\php\php.exe" -S localhost:8000
```

### Opción B – Script por lotes (Windows)

Haz doble clic en **`iniciar-servidor.bat`** (debe estar en la raíz del proyecto). Ese script abre la consola, entra en la carpeta del proyecto y ejecuta `php -S localhost:8000`. Luego abre **http://localhost:8000** en el navegador.

---

## 7. Primer uso del sistema

- Si la base está recién creada y **no hay usuarios**, entra primero a **http://localhost:8000/registro.php** y crea el primer usuario (ese será el administrador).
- Después inicia sesión en **http://localhost:8000** con ese usuario.

---

## 8. Detener el servidor

En la ventana donde está corriendo `php -S localhost:8000`, pulsa **Ctrl + C** o cierra la ventana.

---

## Resumen de pasos en orden

| Paso | Acción |
|------|--------|
| 1 | Tener MariaDB y PHP instalados. |
| 2 | Crear la base `hotel_equipos` e importar **`bd/hotel_equipos.sql`**. |
| 3 | Editar **`config.php`** (DB_HOST, DB_USER, DB_PASS, DB_NAME, RUTA_BASE) en la carpeta desde la que vas a iniciar el servidor. |
| 4 | Si aparece **auth_gssapi_client**, en MariaDB ejecutar `ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password ...` y `FLUSH PRIVILEGES`. |
| 5 | Si aparece **Access denied (using password: NO)**, revisar que `DB_PASS` en `config.php` coincida con la contraseña del usuario en MariaDB. |
| 6 | Desde la carpeta del proyecto ejecutar: `php -S localhost:8000`. |
| 7 | Abrir **http://localhost:8000**; si no hay usuarios, ir a **registro.php** y crear el primero. |

---

**Nota:** El servidor incorporado de PHP (`php -S`) es adecuado para desarrollo y pruebas. En producción se recomienda usar **Apache** o **Nginx** con PHP.
