#!/bin/sh
set -e

if [ ! -f /app/database/database.sqlite ]; then
  touch /app/database/database.sqlite
  php artisan migrate --force --seed
else
  php artisan migrate --force
fi

php artisan storage:link || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
