#!/bin/sh
set -e

cd /var/www/html

[ -f .env ] || cp .env.example .env

mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache

# Only one container should install dependencies or generate the app key at a time.
exec 9>/tmp/supportflow-init.lock
flock 9

if [ ! -d vendor ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --no-progress
fi

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --ansi
fi

php artisan storage:link --force --ansi

flock -u 9

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache

if [ "${DB_CONNECTION:-}" = "mysql" ]; then
    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
    until php -r '
        try {
            new PDO(
                "mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT"),
                getenv("DB_USERNAME"),
                getenv("DB_PASSWORD")
            );
        } catch (Throwable $e) {
            exit(1);
        }
    '; do
        sleep 2
    done
fi

# Workers and the scheduler must not race the app container on migrations.
if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
    php artisan migrate --force --ansi
fi

exec "$@"
