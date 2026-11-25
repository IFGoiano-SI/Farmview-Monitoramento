FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip libzip-dev libpng-dev \
    && docker-php-ext-install pdo_mysql zip gd \
    && a2enmod rewrite headers \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'expose_php = Off'; \
        echo 'upload_max_filesize = 4M'; \
        echo 'post_max_size = 8M'; \
        echo 'session.cookie_httponly = 1'; \
        echo 'session.use_strict_mode = 1'; \
    } > "$PHP_INI_DIR/conf.d/farmview.ini" \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/app-entrypoint.sh /usr/local/bin/app-entrypoint.sh

WORKDIR /var/www/html
COPY . /var/www/html

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/logs storage/videos storage/cache Public/uploadImages Public/templates/Images \
    && chown -R www-data:www-data storage Public \
    && sed -i 's/\r$//' /usr/local/bin/app-entrypoint.sh \
    && chmod +x /usr/local/bin/app-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["app-entrypoint.sh"]
CMD ["apache2-foreground"]
