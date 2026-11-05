FROM composer:2 AS dependencies

WORKDIR /var/www/html

COPY src/composer.json src/composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --no-scripts \
    --ignore-platform-reqs

FROM node:22-alpine AS assets

WORKDIR /var/www/html

COPY src/package.json src/package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY src/ ./
COPY --from=dependencies /var/www/html/vendor ./vendor
RUN npm run build

FROM nginx:alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY src/public /var/www/html/public
COPY --from=assets /var/www/html/public/build /var/www/html/public/build