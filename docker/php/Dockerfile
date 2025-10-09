FROM php:8.4-fpm-alpine AS app

ARG UID
ARG GID

ENV UID=${UID}
ENV GID=${GID}

RUN mkdir -p /var/www/html

WORKDIR /var/www/html

# MacOS staff group's gid is 20, so is the dialout group in alpine linux. We're not using it, let's just remove it.
RUN delgroup dialout

RUN addgroup -g ${GID} --system laravel
RUN adduser -G laravel --system -D -s /bin/sh -u ${UID} laravel

RUN sed -i "s/user = www-data/user = laravel/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group = www-data/group = laravel/g" /usr/local/etc/php-fpm.d/www.conf
RUN echo "php_admin_flag[log_errors] = on" >> /usr/local/etc/php-fpm.d/www.conf

RUN echo ${TZ} > /etc/timezone
RUN set -eux; apk add --no-cache curl postgresql-dev libzip-dev zip supervisor git git-lfs gpg less openssh patch
RUN git lfs install

RUN docker-php-ext-configure pcntl --enable-pcntl
RUN docker-php-ext-install pdo_mysql pdo_pgsql pcntl zip

ARG PHPEXT_REDIS_VERSION=6.1.0
RUN set -eux; \
    pecl bundle -d /usr/src/php/ext redis-${PHPEXT_REDIS_VERSION}; \
    docker-php-ext-install -j$(nproc) redis; \
    \
    docker-php-source delete; \
    rm -rf /tmp/* /var/tmp/*

RUN echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini && \
    echo "max_file_uploads = 20" >> /usr/local/etc/php/conf.d/uploads.ini

RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN rm -rf composer-setup.php

USER laravel

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]

FROM app AS horizon

USER root

COPY supervisord.conf /etc/supervisord.conf

CMD ["/usr/bin/supervisord", "-n", "-c",  "/etc/supervisord.conf"]
