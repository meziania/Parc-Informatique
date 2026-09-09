# Setup base PostgreSQL locale (sans Docker)
# Usage: .\scripts\setup-local-db.ps1

param(
    [string]$PostgresBin = "C:\Program Files\PostgreSQL\18\bin",
    [string]$AdminUser = "postgres",
    [string]$DbName = "parc_informatique",
    [string]$AppUser = "parc",
    [string]$AppPassword = "password",
    [int]$Port = 5432
)

$ErrorActionPreference = "Stop"
$psql = Join-Path $PostgresBin "psql.exe"

if (-not (Test-Path $psql)) {
    Write-Error "psql introuvable: $psql"
}

function Invoke-Psql {
    param([string[]]$SqlArgs)
    & $psql @SqlArgs
    if ($LASTEXITCODE -ne 0) {
        throw "psql a echoue (code $LASTEXITCODE). Mot de passe incorrect ou PostgreSQL inaccessible."
    }
}

$secure = Read-Host "Mot de passe PostgreSQL pour l'utilisateur '$AdminUser'" -AsSecureString
$BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
$env:PGPASSWORD = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)

try {
    Write-Host "Test de connexion..."
    Invoke-Psql @("-U", $AdminUser, "-h", "127.0.0.1", "-p", "$Port", "-v", "ON_ERROR_STOP=1", "-c", "SELECT 1;") | Out-Null
    Write-Host "Connexion OK."

    $existsRole = & $psql -U $AdminUser -h 127.0.0.1 -p $Port -tAc "SELECT 1 FROM pg_roles WHERE rolname='$AppUser'"
    if ($LASTEXITCODE -ne 0) { throw "Impossible de lister les roles." }

    if (($existsRole | Out-String).Trim() -ne "1") {
        Invoke-Psql @("-U", $AdminUser, "-h", "127.0.0.1", "-p", "$Port", "-v", "ON_ERROR_STOP=1", "-c", "CREATE USER $AppUser WITH PASSWORD '$AppPassword';") | Out-Null
        Write-Host "Utilisateur $AppUser cree."
    } else {
        Invoke-Psql @("-U", $AdminUser, "-h", "127.0.0.1", "-p", "$Port", "-v", "ON_ERROR_STOP=1", "-c", "ALTER USER $AppUser WITH PASSWORD '$AppPassword';") | Out-Null
        Write-Host "Utilisateur $AppUser deja present (mot de passe mis a jour)."
    }

    $existsDb = & $psql -U $AdminUser -h 127.0.0.1 -p $Port -tAc "SELECT 1 FROM pg_database WHERE datname='$DbName'"
    if ($LASTEXITCODE -ne 0) { throw "Impossible de lister les bases." }

    if (($existsDb | Out-String).Trim() -ne "1") {
        Invoke-Psql @("-U", $AdminUser, "-h", "127.0.0.1", "-p", "$Port", "-v", "ON_ERROR_STOP=1", "-c", "CREATE DATABASE $DbName OWNER $AppUser;") | Out-Null
        Write-Host "Base $DbName creee."
    } else {
        Write-Host "Base $DbName deja presente."
    }

    $testingDb = "testing"
    $existsTesting = & $psql -U $AdminUser -h 127.0.0.1 -p $Port -tAc "SELECT 1 FROM pg_database WHERE datname='$testingDb'"
    if ($LASTEXITCODE -ne 0) { throw "Impossible de lister les bases." }
    if (($existsTesting | Out-String).Trim() -ne "1") {
        Invoke-Psql @("-U", $AdminUser, "-h", "127.0.0.1", "-p", "$Port", "-v", "ON_ERROR_STOP=1", "-c", "CREATE DATABASE $testingDb OWNER $AppUser;") | Out-Null
        Write-Host "Base $testingDb creee (phpunit)."
    } else {
        Write-Host "Base $testingDb deja presente."
    }

    foreach ($database in @($DbName, $testingDb)) {
        Invoke-Psql @("-U", $AdminUser, "-h", "127.0.0.1", "-p", "$Port", "-d", $database, "-v", "ON_ERROR_STOP=1", "-c", "GRANT ALL ON SCHEMA public TO $AppUser; ALTER SCHEMA public OWNER TO $AppUser;") | Out-Null
    }

    Write-Host ""
    Write-Host "OK. .env attendu :"
    Write-Host "DB_HOST=127.0.0.1 / DB_DATABASE=$DbName / DB_USERNAME=$AppUser / DB_PASSWORD=$AppPassword"
}
finally {
    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
}
