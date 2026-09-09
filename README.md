# Parc Informatique — Application

Application de gestion de parc informatique et de service desk (voir `../PRESENTATION.md`).

## Stack

- **Laravel 13** (PHP 8.5) + **Inertia.js** + **React 18** + **TypeScript**
- **PostgreSQL 18** (service Windows)
- **Tailwind CSS 3** + Headless UI
- Environnement de dev : **Laravel Herd** (Nginx)

## Démarrage

Voir le [README racine](../README.md) pour la procédure complète (Herd, migrations, backup, audit).

```powershell
# Depuis ce dossier (app/)
herd link parc          # une fois
herd init               # applique herd.yml si besoin
npm install && npm run build
herd open               # http://parc.test
```

## Scripts locaux

| Script | Rôle |
|--------|------|
| `scripts/setup-local-db.ps1` | Créer rôle / base Postgres |
| `scripts/backup-postgres.ps1` | Dump + rétention |
| `scripts/reset-postgres-password.ps1` | Reset superuser (Admin) |

## Piège Windows

Les variables d’environnement `DB_*` du système écrasent le `.env` Laravel. Pour une session :

```powershell
Remove-Item Env:DB_HOST, Env:DB_PASSWORD, Env:DB_USERNAME, Env:DB_PORT -ErrorAction SilentlyContinue
```
