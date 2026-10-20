# Vigilante del Sistema Villa Group: lo ejecuta una tarea programada (SYSTEM) cada 5 minutos.
# Si el sitio no responde, reinicia los servicios y lo deja anotado en logs\watchdog.log.

$ErrorActionPreference = 'Continue'
$ServicioWeb = 'VillaGroupWeb'
$ServicioBD  = 'MariaDB'
$Url         = 'http://127.0.0.1:8000/index.php'
$Log         = Join-Path $PSScriptRoot 'logs\watchdog.log'

function Escribir($msg) {
    if ((Test-Path $Log) -and (Get-Item $Log).Length -gt 5MB) {
        Move-Item $Log "$Log.1" -Force
    }
    Add-Content -Path $Log -Value ("{0}  {1}" -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $msg) -Encoding UTF8
}

function Asegurar-Servicio($nombre) {
    $s = Get-Service -Name $nombre -ErrorAction SilentlyContinue
    if (-not $s) { Escribir "ERROR: no existe el servicio $nombre"; return }
    if ($s.Status -ne 'Running') {
        Escribir "Servicio $nombre estaba '$($s.Status)'. Iniciando..."
        Start-Service -Name $nombre -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 10
    }
}

function Sitio-Responde {
    try {
        $r = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 20
        return ($r.StatusCode -eq 200)
    } catch {
        return $false
    }
}

Asegurar-Servicio $ServicioBD
Asegurar-Servicio $ServicioWeb

if (Sitio-Responde) { exit 0 }

Start-Sleep -Seconds 15
if (Sitio-Responde) { exit 0 }

Escribir "El sitio no responde en $Url. Reiniciando $ServicioWeb..."
Restart-Service -Name $ServicioWeb -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 15

if (Sitio-Responde) {
    Escribir "Recuperado tras reiniciar $ServicioWeb."
    exit 0
}

Escribir "Sigue sin responder. Reiniciando $ServicioBD y $ServicioWeb..."
Restart-Service -Name $ServicioBD -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 15
Restart-Service -Name $ServicioWeb -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 15

if (Sitio-Responde) { Escribir "Recuperado tras reiniciar ambos servicios." }
else { Escribir "ERROR: el sitio sigue sin responder. Revisar logs\apache-error.log" }
