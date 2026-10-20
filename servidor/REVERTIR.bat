@echo off
rem Clic derecho -> "Ejecutar como administrador"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0revertir-servicio.ps1"
echo.
pause
