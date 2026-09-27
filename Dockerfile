FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libzip-dev libpng-dev \
    && docker-php-ext-install pdo_mysql zip gd \
    && a2enmod rewrite \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && rm -rf /var/lib/apt/lists/*
    
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80