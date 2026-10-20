@echo off
rem Clic derecho -> "Ejecutar como administrador"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0instalar-servicio.ps1"
echo.
pause
