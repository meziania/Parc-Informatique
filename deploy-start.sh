#!/bin/sh
set -e

echo "===== GPSI boot ====="
php artisan config:clear || true
php artisan storage:link --force || true

echo "----- migrate -----"
php artisan migrate --force

echo "----- seed -----"
php artisan db:seed --force || echo "seed ignore (donnees deja presentes)"

echo "----- comptes -----"
php artisan gpsi:demo-users

echo "----- serve -----"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
