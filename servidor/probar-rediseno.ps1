# Levanta (o reinicia) la copia del rediseno en http://localhost:8001 sin tocar el sistema real del 8000.
$conf  = Join-Path $PSScriptRoot 'httpd-villa.conf'
$copia = 'C:/Users/sistemas/Desktop/ventasAlmacen22_rediseno'
New-Item -ItemType Directory -Force "$copia/servidor/logs" | Out-Null
Get-CimInstance Win32_Process -Filter "name='httpd.exe'" | Where-Object CommandLine -match 'VILLA_PORT 8001' |
    ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }
Start-Sleep -Seconds 1
Start-Process 'C:\xampp\apache\bin\httpd.exe' -WindowStyle Hidden -ArgumentList @(
    '-f', "`"$conf`"",
    '-C', "`"Define VILLA_ROOT $copia`"",
    '-C', '"Define VILLA_PORT 8001"',
    '-c', "`"PidFile $copia/servidor/logs/httpd.pid`""
)
Start-Sleep -Seconds 3
(Invoke-WebRequest 'http://127.0.0.1:8001/index.php' -UseBasicParsing -TimeoutSec 15).StatusCode
