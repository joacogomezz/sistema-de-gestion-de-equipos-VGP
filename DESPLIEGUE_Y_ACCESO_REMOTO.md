# Despliegue, red, ngrok y acceso remoto

Guía única para exponer el sistema (HTTPS con ngrok), acceder desde el celular o otra red, y opcionalmente arrancar PHP + ngrok al iniciar sesión en Windows.

---

## 1. Puertos: no confundir 80, 8000 ni 3000

| Entorno | Servidor | Comando ngrok |
|---------|----------|----------------|
| XAMPP (Apache) | Puerto **80** | `ngrok http 80` |
| Solo PHP (`php -S localhost:8000`) | Puerto **8000** | `ngrok http 8000` |

Este proyecto **no** usa el puerto 3000. Si ngrok muestra **ERR_NGROK_8012** (“failed to connect to upstream at localhost:3000”), estás apuntando al puerto equivocado: corrige el puerto según la tabla anterior.

---

## 2. Instalar ngrok en Windows

1. Descarga **ngrok** desde [https://ngrok.com/download](https://ngrok.com/download).
2. Extrae `ngrok.exe` en una carpeta fija (por ejemplo la raíz del proyecto o `C:\ngrok\`).
3. Una sola vez, configura tu cuenta (token desde el panel de ngrok):
   ```cmd
   ngrok config add-authtoken TU_TOKEN
   ```
4. Opcional: agrega la carpeta de `ngrok.exe` al **PATH** de Windows para invocar `ngrok` desde cualquier sitio.

**Chocolatey** (si lo usas): `choco install ngrok`

---

## 3. Flujo con XAMPP (Apache en 80)

1. Inicia **Apache** en el panel de XAMPP.
2. En CMD o PowerShell:
   ```cmd
   ngrok http 80
   ```
3. Copia la URL **https://…** que muestra ngrok.
4. En el navegador o celular abre, por ejemplo:  
   `https://TU-SUBDOMINIO.ngrok-free.app/ventasAlmacen22/`  
   (ajusta `/ventasAlmacen22` si tu carpeta en `htdocs` tiene otro nombre.)

---

## 4. Flujo sin XAMPP (PHP embebido en 8000)

1. En una ventana, desde la carpeta del proyecto:
   ```cmd
   php -S localhost:8000
   ```
   O usa `iniciar-servidor.bat` en la raíz del proyecto.
2. En **otra** ventana:
   ```cmd
   ngrok http 8000
   ```
3. Abre la URL HTTPS de ngrok. Si el servidor PHP sirve la raíz del proyecto, suele bastar la URL base sin subcarpeta (según dónde tengas el proyecto).

Más detalles de MariaDB y PHP en **`INICIAR-SIN-XAMPP.md`**.

---

## 5. Misma red Wi‑Fi (sin ngrok)

1. En Windows: `Win + R` → `cmd` → `ipconfig` → anota la **IPv4** (ej. `192.168.1.100`).
2. Celular en la **misma Wi‑Fi**.
3. En el navegador del celular:  
   `http://192.168.1.100/ventasAlmacen22/` (XAMPP)  
   o la IP y puerto que uses.

Puede ser necesario permitir el puerto en el **firewall** de Windows. Para HTTPS y acceso fuera de la red local, ngrok o un servidor con SSL es más adecuado.

---

## 6. Instalar la app en el celular (PWA)

- **Android (Chrome):** abre la URL del sistema; menú → **Instalar app** o el aviso de instalación.
- **iOS (Safari):** Compartir → **Agregar a pantalla de inicio**.

Para requisitos (HTTPS, iconos, manifest, service worker) y solución de problemas, lee **`PWA.md`**.

---

## 7. Inicio automático al encender el PC (Windows)

Objetivo: que al iniciar sesión se abran **PHP** y **ngrok** sin escribir comandos a mano.

### 7.1 Script por lotes (ejemplo)

Crea `auto-inicio-sistema.bat` (rutas **ajustables** a tu PC):

```bat
@echo off
title Auto inicio — Sistema Villa Group

set PROYECTO=C:\xampp\htdocs\ventasAlmacen22
set PHP_EXE=C:\xampp\php\php.exe
set NGROK_EXE=%PROYECTO%\ngrok.exe

start "PHP Server" cmd /k "cd /d %PROYECTO% && %PHP_EXE% -S localhost:8000"
timeout /t 3 /nobreak >nul
start "Ngrok" cmd /k "cd /d %PROYECTO% && %NGROK_EXE% http 8000"
```

Si usas **XAMPP** en el 80, omite la línea de PHP y usa solo `ngrok http 80` en la última línea, o inicia Apache desde XAMPP y un solo `start` para ngrok.

### 7.2 Programador de tareas

1. Abre **Programador de tareas** → **Crear tarea**.
2. **General:** nombre ej. `VillaGroup PHP y Ngrok`; opcional “Ejecutar con privilegios elevados”.
3. **Desencadenadores:** **Al iniciar sesión** (tu usuario).
4. **Acciones:** **Iniciar un programa** → programa: `cmd.exe` → argumentos:
   ```text
   /c "C:\ruta\completa\auto-inicio-sistema.bat"
   ```
5. Aceptar.

Así cada inicio de sesión lanza las ventanas necesarias. Revisa que no queden procesos duplicados si ya tenías PHP/ngrok abiertos.

---

## 8. Panel de inspección de ngrok

Con ngrok en ejecución: [http://127.0.0.1:4040](http://127.0.0.1:4040) — historial de peticiones al túnel.

---

## 9. Referencia rápida de comandos

```text
XAMPP:     ngrok http 80
PHP -S:    ngrok http 8000
NO usar:   ngrok http 3000   (no corresponde a este proyecto)
```

---

*Abril 2026 — sustituye a MANUAL-NGROK.md, instalar-ngrok.md y NGROK-COMANDOS.txt.*
