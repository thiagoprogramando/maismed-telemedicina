#!/bin/sh
set -eu

if [ ! -f .env ]; then
    cp .env.example .env
fi

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

database_path="${DB_DATABASE:-/var/www/html/storage/database.sqlite}"
touch "$database_path"

if grep -q '^APP_KEY=$' .env; then
    php artisan key:generate --force --no-interaction
fi

php artisan migrate --force --no-interaction

if [ "${APP_SEED_DATABASE:-false}" = "true" ]; then
    php artisan db:seed --force --no-interaction
fi

php artisan optimize:clear --no-interaction

exec "$@"
