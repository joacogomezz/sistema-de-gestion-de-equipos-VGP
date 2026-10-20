@echo off
title Sistema Villa Group - Servidor PHP
echo.
echo ========================================
echo   Sistema de Gestion de Equipos
echo   Hotel Villa Group
echo ========================================
echo.
sc query VillaGroupWeb >nul 2>&1
if not errorlevel 1 (
    echo El sistema ya corre como servicio de Windows ^(VillaGroupWeb^).
    echo No es necesario mantener esta ventana abierta.
    sc start VillaGroupWeb >nul 2>&1
    start "" "http://localhost:8000"
    timeout /t 5 >nul
    exit /b 0
)

echo Verificando dependencias...
echo.
where php >nul 2>&1
if errorlevel 1 (
    echo No se encontro 'php' en el PATH.
    echo Instala PHP o agrega la ruta de PHP al PATH del sistema.
    echo Ejemplo con ruta completa: "C:\php\php.exe" -S 0.0.0.0:8000
    echo.
    pause
    exit /b 1
)

echo Dependencias OK.
echo.
echo Modo LAN habilitado (sin ngrok obligatorio).
echo.
echo Servidor local: http://localhost:8000
echo En otros equipos de la misma red: http://TU_IPV4:8000
echo.
echo Tu(s) IPv4 detectada(s):
ipconfig | findstr /R /C:"IPv4"
echo.
echo Abriendo navegador en http://localhost:8000 ...
start "" "http://localhost:8000"
echo.
echo Para detener el servidor, cierra esta ventana.
echo.

cd /d "%~dp0"
php -S 0.0.0.0:8000
