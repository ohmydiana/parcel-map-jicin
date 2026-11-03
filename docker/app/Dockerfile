FROM php:8.5-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY apache-site.conf /etc/apache2/sites-available/000-default.conf