# Reinitialise le mot de passe du superuser postgres (Windows)
# Necessite PowerShell en Administrateur.
# Usage: .\scripts\reset-postgres-password.ps1 -NewPassword "tonMotDePasse"

param(
    [Parameter(Mandatory = $true)]
    [string]$NewPassword,
    [string]$PgData = "C:\Program Files\PostgreSQL\18\data",
    [string]$PgBin = "C:\Program Files\PostgreSQL\18\bin",
    [string]$ServiceName = "postgresql-x64-18"
)

$ErrorActionPreference = "Stop"
$pgHba = Join-Path $PgData "pg_hba.conf"
$psql = Join-Path $PgBin "psql.exe"

if (-not ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Error "Relance ce script en PowerShell Administrateur."
}

if (-not (Test-Path $pgHba)) {
    Write-Error "pg_hba.conf introuvable: $pgHba"
}

Copy-Item $pgHba "$pgHba.bak-$(Get-Date -Format yyyyMMddHHmmss)" -Force
$original = Get-Content $pgHba -Raw

# Autoriser temporairement les connexions locales sans mot de passe
$trust = $original -replace '(?m)^(host\s+all\s+all\s+127\.0\.0\.1/32\s+)\S+', '${1}trust'
$trust = $trust -replace '(?m)^(host\s+all\s+all\s+::1/128\s+)\S+', '${1}trust'
Set-Content -Path $pgHba -Value $trust -NoNewline

try {
    Restart-Service $ServiceName -Force
    Start-Sleep -Seconds 3

    $env:PGPASSWORD = $null
    & $psql -U postgres -h 127.0.0.1 -d postgres -v ON_ERROR_STOP=1 -c "ALTER USER postgres WITH PASSWORD '$NewPassword';"
    if ($LASTEXITCODE -ne 0) { throw "ALTER USER a echoue." }

    Write-Host "Mot de passe postgres mis a jour."
}
finally {
    Set-Content -Path $pgHba -Value $original -NoNewline
    Restart-Service $ServiceName -Force
    Write-Host "pg_hba.conf restaure. Service redemarre."
}

Write-Host ""
Write-Host "Ensuite :"
Write-Host "  cd app"
Write-Host "  .\scripts\setup-local-db.ps1"
Write-Host "(utilise le nouveau mot de passe postgres)"
