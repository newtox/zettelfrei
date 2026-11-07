#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/database

if [ "$DB_CONNECTION" = "sqlite" ] && [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
fi

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY fehlt. Erzeuge einen mit: docker run --rm --entrypoint php ghcr.io/newtox/zettelfrei artisan key:generate --show" >&2
    exit 1
fi

php artisan migrate --force
php artisan optimize

if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then
    php artisan user:create "$ADMIN_EMAIL" --admin --password="$ADMIN_PASSWORD" >/dev/null 2>&1 || true
fi

chown -R www-data:www-data storage bootstrap/cache

(
    while true; do
        su -s /bin/sh www-data -c "php artisan schedule:run --no-interaction" >/dev/null 2>&1 || true
        sleep 60
    done
) &

exec "$@"
