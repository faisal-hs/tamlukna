FROM php:8.4-fpm

ARG HOST_UID=1000
ARG HOST_GID=1000

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions pdo_mysql bcmath intl zip gd exif pcntl opcache redis \
 && apt-get update \
 && apt-get install -y --no-install-recommends git unzip \
 && rm -rf /var/lib/apt/lists/* \
 && groupmod -o -g "${HOST_GID}" www-data \
 && usermod  -o -u "${HOST_UID}" -g "${HOST_GID}" -d /home/www-data www-data \
 && mkdir -p /home/www-data \
 && chown "${HOST_UID}:${HOST_GID}" /home/www-data

COPY conf.d/tamlukna.ini /usr/local/etc/php/conf.d/zz-tamlukna.ini

ENV HOME=/home/www-data \
    COMPOSER_HOME=/home/www-data/.composer
WORKDIR /var/www/html
USER www-data
