# Expose parc.test via Cloudflare Quick Tunnel (HTTPS public).
# Usage from app/:
#   powershell -ExecutionPolicy Bypass -File .\scripts\demo-public.ps1
#
# Keep this window open. Ctrl+C stops the tunnel.
# Laptop must stay on (Herd + PostgreSQL + optional Ollama).

$ErrorActionPreference = 'Stop'
$BinDir = Join-Path $PSScriptRoot 'bin'
$Cloudflared = Join-Path $BinDir 'cloudflared.exe'
$DownloadUrl = 'https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe'

Write-Host '=== GPSI - URL publique de demo ===' -ForegroundColor Green

try {
    $probe = Invoke-WebRequest -Uri 'http://parc.test' -UseBasicParsing -TimeoutSec 8
    if ($probe.StatusCode -ne 200) { throw "HTTP $($probe.StatusCode)" }
    Write-Host "parc.test OK (HTTP $($probe.StatusCode))"
} catch {
    Write-Host 'parc.test ne repond pas. Demarre Herd, puis : herd link parc' -ForegroundColor Yellow
    exit 1
}

if (-not (Test-Path $Cloudflared)) {
    New-Item -ItemType Directory -Force -Path $BinDir | Out-Null
    Write-Host 'Telechargement de cloudflared...'
    Invoke-WebRequest -Uri $DownloadUrl -OutFile $Cloudflared -UseBasicParsing
}

Write-Host ''
Write-Host 'Tunnel en cours. Une URL https://xxxx.trycloudflare.com va s afficher.' -ForegroundColor Cyan
Write-Host 'Ouvre cette URL sur le projecteur. Laisse cette fenetre ouverte.' -ForegroundColor Cyan
Write-Host ''

& $Cloudflared @('tunnel', '--url', 'http://parc.test', '--http-host-header', 'parc.test', '--no-autoupdate')
