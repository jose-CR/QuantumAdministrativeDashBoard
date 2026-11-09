FROM php:8.4-apache

# Extensiones que Laravel + Filament 4 + Postgres necesitan.
# python3 + pandas + openpyxl: los exports arman el ZIP (Excel + adjuntos) con
# python/scripts/cash_report.py. Sin esto, la descarga del export falla con
# "python: not found" y no baja ningún archivo.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        unzip git python3 python3-pandas python3-openpyxl \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_pgsql pgsql intl zip gd bcmath exif \
 && rm -rf /var/lib/apt/lists/*

# Composer (la imagen oficial no lo trae)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Laravel sirve desde public/, no desde la raíz
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}/!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
 && a2enmod rewrite

WORKDIR /var/www/html
