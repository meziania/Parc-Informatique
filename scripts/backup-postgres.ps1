# Sauvegarde PostgreSQL (pg_dump -Fc) vers storage/backups/
# Usage: .\scripts\backup-postgres.ps1 [-Keep 14]

param(
    [string]$PostgresBin = "C:\Program Files\PostgreSQL\18\bin",
    [string]$EnvFile = "",
    [int]$Keep = 14,
    [string]$HostName = "",
    [int]$Port = 0,
    [string]$DbName = "",
    [string]$Username = "",
    [string]$Password = ""
)

$ErrorActionPreference = "Stop"

$appRoot = Split-Path -Parent $PSScriptRoot
if (-not $EnvFile) {
    $EnvFile = Join-Path $appRoot ".env"
}

function Get-DotEnvValue {
    param([string]$Path, [string]$Key)

    if (-not (Test-Path $Path)) {
        return $null
    }

    foreach ($line in Get-Content $Path) {
        $trimmed = $line.Trim()
        if ($trimmed -eq "" -or $trimmed.StartsWith("#")) {
            continue
        }
        if ($trimmed -match "^\s*$([regex]::Escape($Key))\s*=\s*(.*)$") {
            $value = $Matches[1].Trim()
            if (
                ($value.StartsWith('"') -and $value.EndsWith('"')) -or
                ($value.StartsWith("'") -and $value.EndsWith("'"))
            ) {
                $value = $value.Substring(1, $value.Length - 2)
            }
            return $value
        }
    }

    return $null
}

$pgDump = Join-Path $PostgresBin "pg_dump.exe"
if (-not (Test-Path $pgDump)) {
    Write-Error "pg_dump introuvable: $pgDump"
}

if (-not $HostName) {
    $HostName = Get-DotEnvValue -Path $EnvFile -Key "DB_HOST"
    if (-not $HostName -or $HostName -eq "localhost") {
        $HostName = "127.0.0.1"
    }
}
if ($Port -le 0) {
    $portValue = Get-DotEnvValue -Path $EnvFile -Key "DB_PORT"
    $Port = if ($portValue) { [int]$portValue } else { 5432 }
}
if (-not $DbName) {
    $DbName = Get-DotEnvValue -Path $EnvFile -Key "DB_DATABASE"
    if (-not $DbName) { $DbName = "parc_informatique" }
}
if (-not $Username) {
    $Username = Get-DotEnvValue -Path $EnvFile -Key "DB_USERNAME"
    if (-not $Username) { $Username = "parc" }
}
if (-not $Password) {
    $Password = Get-DotEnvValue -Path $EnvFile -Key "DB_PASSWORD"
    if (-not $Password) { $Password = "password" }
}

$backupDir = Join-Path $appRoot "storage\backups"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$placeholder = Join-Path $backupDir ".gitignore"
if (-not (Test-Path $placeholder)) {
    Set-Content -Path $placeholder -Value "*`n!.gitignore`n"
}

$stamp = Get-Date -Format "yyyyMMdd_HHmm"
$outFile = Join-Path $backupDir "${DbName}_${stamp}.dump"

Write-Host "Sauvegarde $DbName @ ${HostName}:${Port} -> $outFile"

$env:PGPASSWORD = $Password
try {
    & $pgDump `
        -h $HostName `
        -p $Port `
        -U $Username `
        -d $DbName `
        -Fc `
        -f $outFile

    if ($LASTEXITCODE -ne 0) {
        throw "pg_dump a echoue (code $LASTEXITCODE)."
    }
}
finally {
    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
}

$dumps = Get-ChildItem -Path $backupDir -Filter "*.dump" | Sort-Object LastWriteTime -Descending
if ($Keep -gt 0 -and $dumps.Count -gt $Keep) {
    $dumps | Select-Object -Skip $Keep | ForEach-Object {
        Write-Host "Suppression ancienne sauvegarde: $($_.Name)"
        Remove-Item $_.FullName -Force
    }
}

Write-Host "OK: $outFile"
