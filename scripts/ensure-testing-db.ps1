# Cree la base PostgreSQL "testing" pour phpunit (necessite le superuser postgres).
# Usage:
#   .\scripts\ensure-testing-db.ps1
#   .\scripts\ensure-testing-db.ps1 -Password "votre_mdp_postgres"

param(
    [string]$PostgresBin = "C:\Program Files\PostgreSQL\18\bin",
    [string]$AdminUser = "postgres",
    [string]$AppUser = "parc",
    [string]$TestingDb = "testing",
    [string]$AppPassword = "password",
    [int]$Port = 5432,
    [string]$Password = ""
)

$ErrorActionPreference = "Stop"
$psql = Join-Path $PostgresBin "psql.exe"

if (-not (Test-Path $psql)) {
    Write-Error "psql introuvable: $psql"
}

if ([string]::IsNullOrWhiteSpace($Password)) {
    $secure = Read-Host "Mot de passe PostgreSQL pour l'utilisateur '$AdminUser'" -AsSecureString
    if ($null -eq $secure -or $secure.Length -eq 0) {
        Write-Error "Mot de passe vide. Relancez et saisissez le mot de passe de '$AdminUser', ou: .\scripts\ensure-testing-db.ps1 -Password 'votre_mdp'"
    }
    $BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try {
        $Password = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)
    }
    finally {
        [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($BSTR) | Out-Null
    }
}

if ([string]::IsNullOrWhiteSpace($Password)) {
    Write-Error "Mot de passe vide."
}

$env:PGPASSWORD = $Password

try {
    $exists = & $psql -U $AdminUser -h 127.0.0.1 -p $Port -tAc "SELECT 1 FROM pg_database WHERE datname='$TestingDb'"
    if ($LASTEXITCODE -ne 0) {
        throw "Connexion postgres echouee (mauvais mot de passe ?)."
    }

    if (($exists | Out-String).Trim() -ne "1") {
        & $psql -U $AdminUser -h 127.0.0.1 -p $Port -v ON_ERROR_STOP=1 -c "CREATE DATABASE $TestingDb OWNER $AppUser;"
        if ($LASTEXITCODE -ne 0) { throw "CREATE DATABASE a echoue." }
        Write-Host "Base $TestingDb creee."
    } else {
        Write-Host "Base $TestingDb deja presente."
    }

    & $psql -U $AdminUser -h 127.0.0.1 -p $Port -v ON_ERROR_STOP=1 -c "ALTER USER $AppUser WITH PASSWORD '$AppPassword';"
    if ($LASTEXITCODE -ne 0) { throw "ALTER USER a echoue." }

    & $psql -U $AdminUser -h 127.0.0.1 -p $Port -d $TestingDb -v ON_ERROR_STOP=1 -c "GRANT ALL ON SCHEMA public TO $AppUser; ALTER SCHEMA public OWNER TO $AppUser;"
    if ($LASTEXITCODE -ne 0) { throw "GRANT a echoue." }

    Write-Host "OK. Mot de passe '$AppUser' aligne sur '$AppPassword'."
    Write-Host "Ensuite (recommandé — ignore les DB_* Windows) :"
    Write-Host '  .\scripts\run-tests.ps1 --filter="AdminUserTest|AssetTest"'
}
finally {
    Remove-Item Env:PGPASSWORD -ErrorAction SilentlyContinue
}
