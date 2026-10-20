# Instala el Sistema Villa Group como servicio permanente de Windows.
# Ejecutar UNA VEZ como Administrador:
#   clic derecho en INSTALAR.bat -> "Ejecutar como administrador"
# No toca la base de datos ni la carpeta archivos\. Para deshacer: revertir-servicio.ps1

$ErrorActionPreference = 'Stop'
$Proyecto   = Split-Path $PSScriptRoot -Parent
$Conf       = Join-Path $PSScriptRoot 'httpd-villa.conf'
$Httpd      = 'C:\xampp\apache\bin\httpd.exe'
$Servicio   = 'VillaGroupWeb'
$ServicioBD = 'MariaDB'
$TareaVieja = 'AutoInicio VillaGroup'
$TareaWatch = 'VillaGroup Watchdog'
$Puerto     = 8000

function Paso($t) { Write-Host ""; Write-Host "==> $t" -ForegroundColor Cyan }
function Ok($t)   { Write-Host "    OK: $t" -ForegroundColor Green }

$esAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $esAdmin) { throw 'Este script debe ejecutarse como Administrador.' }

New-Item -ItemType Directory -Force (Join-Path $PSScriptRoot 'logs') | Out-Null
Start-Transcript -Path (Join-Path $PSScriptRoot 'logs\instalacion.log') -Append | Out-Null

Paso 'Validando configuracion de Apache'
& $Httpd -f $Conf -t
if ($LASTEXITCODE -ne 0) { throw 'La configuracion de Apache tiene errores. No se cambio nada.' }
Ok 'Sintaxis correcta'

Paso 'Verificando base de datos'
if (-not (Get-Service $ServicioBD -ErrorAction SilentlyContinue)) { throw "No existe el servicio $ServicioBD" }
Set-Service $ServicioBD -StartupType Automatic
if ((Get-Service $ServicioBD).Status -ne 'Running') { Start-Service $ServicioBD }
sc.exe failure $ServicioBD reset= 86400 actions= restart/10000/restart/30000/restart/60000 | Out-Null
sc.exe failureflag $ServicioBD 1 | Out-Null
Ok "$ServicioBD en automatico y con reinicio ante fallos"

Paso 'Desactivando el arranque anterior (php -S)'
$t = Get-ScheduledTask -TaskName $TareaVieja -ErrorAction SilentlyContinue
if ($t) { Disable-ScheduledTask -TaskName $TareaVieja | Out-Null; Ok "Tarea '$TareaVieja' desactivada (no borrada)" }
# Se cierra primero la ventana cmd que lanzo php -S; si no, la consola vuelve a ejecutarlo.
for ($i = 0; $i -lt 5; $i++) {
    $pids = Get-NetTCPConnection -LocalPort $Puerto -State Listen -ErrorAction SilentlyContinue | Select-Object -ExpandProperty OwningProcess -Unique
    $phps = foreach ($id in $pids) { Get-CimInstance Win32_Process -Filter "ProcessId=$id" | Where-Object Name -eq 'php.exe' }
    if (-not $phps) { break }
    foreach ($p in $phps) {
        $padre = Get-CimInstance Win32_Process -Filter "ProcessId=$($p.ParentProcessId)" -ErrorAction SilentlyContinue
        if ($padre -and $padre.Name -eq 'cmd.exe') {
            Stop-Process -Id $padre.ProcessId -Force -ErrorAction SilentlyContinue
            Ok "Cerrada la ventana del servidor anterior (cmd PID $($padre.ProcessId))"
        }
        Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue
        Ok "Detenido php.exe (PID $($p.ProcessId)) que ocupaba el puerto $Puerto"
    }
    Start-Sleep -Seconds 3
}
$ocupado = Get-NetTCPConnection -LocalPort $Puerto -State Listen -ErrorAction SilentlyContinue
if ($ocupado) {
    $quien = (Get-Process -Id $ocupado[0].OwningProcess -ErrorAction SilentlyContinue).ProcessName
    if ($quien -ne 'httpd') { throw "El puerto $Puerto sigue ocupado por '$quien'. Cierralo y vuelve a ejecutar." }
}

Paso 'Instalando servicio de Apache'
if (Get-Service $Servicio -ErrorAction SilentlyContinue) {
    Stop-Service $Servicio -Force -ErrorAction SilentlyContinue
    & $Httpd -k uninstall -n $Servicio | Out-Null
    Start-Sleep -Seconds 2
}
& $Httpd -k install -n $Servicio -f $Conf
if ($LASTEXITCODE -ne 0) { throw 'No se pudo instalar el servicio de Apache.' }
sc.exe config $Servicio start= auto depend= Tcpip/Afd/$ServicioBD DisplayName= "Villa Group - Sistema de Equipos (Apache)" | Out-Null
sc.exe description $Servicio "Servidor web del Sistema de Gestion de Equipos (puerto $Puerto). Configuracion: $Conf" | Out-Null
sc.exe failure $Servicio reset= 86400 actions= restart/5000/restart/10000/restart/30000 | Out-Null
sc.exe failureflag $Servicio 1 | Out-Null
Ok "Servicio '$Servicio' instalado: arranque automatico, depende de $ServicioBD, se reinicia solo si falla"

Paso 'Firewall'
Get-NetFirewallRule -DisplayName 'Villa Group Web 8000' -ErrorAction SilentlyContinue | Remove-NetFirewallRule
New-NetFirewallRule -DisplayName 'Villa Group Web 8000' -Direction Inbound -Action Allow -Protocol TCP -LocalPort $Puerto `
    -Program 'C:\xampp\apache\bin\httpd.exe' -Profile Any `
    -RemoteAddress @('LocalSubnet','10.0.0.0/8','172.16.0.0/12','192.168.0.0/16') | Out-Null
Ok "Puerto $Puerto abierto solo para la red interna"

Paso 'Iniciando servicio'
Start-Service $Servicio
Start-Sleep -Seconds 4
$r = Invoke-WebRequest -Uri "http://127.0.0.1:$Puerto/index.php" -UseBasicParsing -TimeoutSec 20
if ($r.StatusCode -ne 200) { throw "El sitio respondio $($r.StatusCode)" }
Ok "El sistema responde en http://localhost:$Puerto"

Paso 'Registrando vigilante (cada 5 minutos)'
$accion   = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument "-NoProfile -NonInteractive -ExecutionPolicy Bypass -File `"$PSScriptRoot\watchdog.ps1`""
$tInicio  = New-ScheduledTaskTrigger -AtStartup
$tRepetir = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 5)
$config   = New-ScheduledTaskSettingsSet -ExecutionTimeLimit (New-TimeSpan -Minutes 3) -MultipleInstances IgnoreNew `
            -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
$usuario  = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
Register-ScheduledTask -TaskName $TareaWatch -Action $accion -Trigger @($tInicio, $tRepetir) -Settings $config -Principal $usuario -Force | Out-Null
Ok "Tarea '$TareaWatch' registrada (corre como SYSTEM, sin necesidad de iniciar sesion)"

Paso 'Energia: evitar que la PC se suspenda conectada a corriente'
powercfg /change standby-timeout-ac 0
powercfg /change hibernate-timeout-ac 0
Ok 'Suspension e hibernacion desactivadas con corriente'

Write-Host ""
Write-Host "LISTO. El sistema ya corre como servicio de Windows." -ForegroundColor Green
Write-Host "  Local:  http://localhost:$Puerto"
Write-Host "  Red:    http://<IP de esta PC>:$Puerto  (misma direccion de siempre)"
Write-Host "  Logs:   $PSScriptRoot\logs"
Stop-Transcript | Out-Null
