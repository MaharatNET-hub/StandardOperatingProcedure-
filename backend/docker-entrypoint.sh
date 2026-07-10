#!/bin/sh
set -e

if [ ! -f /app/database/database.sqlite ]; then
  touch /app/database/database.sqlite
  php artisan migrate --force --seed
else
  php artisan migrate --force
  # Roles/demo accounts are matched by key/role, so re-running these on every
  # boot keeps them in sync with the source without wiping project data.
  php artisan db:seed --class=RoleSeeder --force
  php artisan db:seed --class=UserSeeder --force
fi

php artisan storage:link || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
