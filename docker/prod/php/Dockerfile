FROM php:8.4-fpm-alpine AS base

ARG UID=1000
ARG GID=1000

RUN apk add --no-cache \
    curl \
    libpq \
    libzip \
    zip \
    supervisor \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    postgresql-dev \
    libzip-dev \
    linux-headers

RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    pcntl \
    zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && docker-php-source delete

RUN delgroup dialout || true \
    && addgroup -g ${GID} --system app \
    && adduser -G app --system -D -s /bin/sh -u ${UID} app

COPY --from=composer /usr/bin/composer /usr/bin/composer

FROM base AS builder

WORKDIR /var/www/html
COPY --chown=app:app composer.json composer.lock ./

COPY --chown=app:app packages /var/www/packages

RUN composer install --no-dev --optimize-autoloader --no-scripts

FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

RUN npm run build

FROM base AS app

WORKDIR /var/www/html

COPY docker/prod/php/prod.ini /usr/local/etc/php/conf.d/prod.ini
COPY docker/prod/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/prod/php/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

COPY --chown=app:app . .
COPY --from=builder --chown=app:app /var/www/html/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN chmod -R 775 storage bootstrap/cache

USER app

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]

FROM app AS horizon

USER root

COPY docker/prod/php/supervisord.conf /etc/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]
