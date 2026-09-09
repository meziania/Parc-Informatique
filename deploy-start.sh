#!/bin/sh
set -e

if [ -n "$DATABASE_URL" ]; then
  export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
  export DB_URL="${DB_URL:-$DATABASE_URL}"
fi

export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export CACHE_STORE="${CACHE_STORE:-file}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"

php artisan config:clear || true
php artisan storage:link --force || true

php artisan migrate --force
php artisan gpsi:demo-users
php artisan db:seed --force || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
