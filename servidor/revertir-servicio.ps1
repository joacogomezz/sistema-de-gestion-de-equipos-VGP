# Deshace instalar-servicio.ps1 y vuelve al esquema anterior (php -S al iniciar sesion).
# Ejecutar como Administrador. No toca la base de datos ni archivos\.

$ErrorActionPreference = 'Continue'
$Httpd    = 'C:\xampp\apache\bin\httpd.exe'
$Servicio = 'VillaGroupWeb'

$esAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $esAdmin) { throw 'Este script debe ejecutarse como Administrador.' }

Unregister-ScheduledTask -TaskName 'VillaGroup Watchdog' -Confirm:$false -ErrorAction SilentlyContinue
if (Get-Service $Servicio -ErrorAction SilentlyContinue) {
    Stop-Service $Servicio -Force
    & $Httpd -k uninstall -n $Servicio
}
Get-NetFirewallRule -DisplayName 'Villa Group Web 8000' -ErrorAction SilentlyContinue | Remove-NetFirewallRule
Enable-ScheduledTask -TaskName 'AutoInicio VillaGroup' -ErrorAction SilentlyContinue | Out-Null
Start-ScheduledTask -TaskName 'AutoInicio VillaGroup' -ErrorAction SilentlyContinue

Write-Host 'Revertido: se reactivo la tarea anterior (AutoInicio VillaGroup).' -ForegroundColor Yellow
