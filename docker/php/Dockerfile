FROM php:8.4-fpm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip nginx supervisor libpng-dev libonig-dev libzip-dev libsqlite3-dev default-mysql-client \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html

COPY src/ ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader
RUN rm -f /etc/nginx/sites-enabled/default
RUN sed -i 's|listen = 9000|listen = 127.0.0.1:9001|' /usr/local/etc/php-fpm.d/docker.conf

FROM node:22-alpine AS frontend

WORKDIR /var/www/html

COPY src/package.json src/package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY --from=app /var/www/html/resources ./resources
COPY --from=app /var/www/html/vite.config.js ./vite.config.js
COPY --from=app /var/www/html/vendor/tallstackui ./vendor/tallstackui
RUN npm run build

FROM app

COPY --from=frontend /var/www/html/public/build ./public/build

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
