# Lance phpunit en neutralisant les variables DB_* Windows (souvent localhost + mauvais mdp).
# Usage:
#   .\scripts\run-tests.ps1
#   .\scripts\run-tests.ps1 --filter="AdminUserTest|AssetTest"

$ErrorActionPreference = "Stop"

$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" +
    [System.Environment]::GetEnvironmentVariable("Path", "User")

@(
    "DB_CONNECTION", "DB_HOST", "DB_PORT", "DB_DATABASE",
    "DB_USERNAME", "DB_PASSWORD", "DB_URL", "DATABASE_URL"
) | ForEach-Object {
    Remove-Item "Env:$_" -ErrorAction SilentlyContinue
}

$env:DB_CONNECTION = "pgsql"
$env:DB_HOST = "127.0.0.1"
$env:DB_PORT = "5432"
$env:DB_DATABASE = "testing"
$env:DB_USERNAME = "parc"
$env:DB_PASSWORD = "password"
$env:DB_URL = ""
$env:DATABASE_URL = ""

Set-Location (Split-Path $PSScriptRoot -Parent)

if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Error "php introuvable. Rafraichissez le PATH ou ouvrez un nouveau terminal."
}

Write-Host "DB -> $($env:DB_HOST)/$($env:DB_DATABASE) as $($env:DB_USERNAME)"
php artisan test @args
