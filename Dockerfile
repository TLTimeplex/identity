# PHP-Basis mit Erweiterungen
FROM php:8.5-apache AS php-base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        pdo_pgsql \
        zip \
    && a2enmod rewrite headers \
    && sed -ri \
        -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -ri \
        -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*


# Build-Umgebung: PHP, Composer und Node
FROM php-base AS build

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_24.x -o /tmp/nodesource_setup.sh \
    && bash /tmp/nodesource_setup.sh \
    && apt-get update \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/* /tmp/nodesource_setup.sh

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN rm -f bootstrap/cache/packages.php \
          bootstrap/cache/services.php \
    && php artisan package:discover --ansi

# Wayfinder benötigt PHP und das installierte vendor-Verzeichnis.
RUN php artisan wayfinder:generate --with-form

RUN npm run build


# Produktionsimage: kein Node und kein Composer
FROM php-base AS app

WORKDIR /var/www/html

COPY . .
COPY --from=build /app/vendor ./vendor
COPY --from=build /app/public/build ./public/build

RUN rm -f bootstrap/cache/packages.php \
          bootstrap/cache/services.php \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]
