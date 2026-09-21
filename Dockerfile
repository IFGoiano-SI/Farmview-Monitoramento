# Aplicação web PHP (Farmview) servida por Apache + mod_php
FROM php:8.2-apache

# Extensão necessária para o banco (PDO MySQL). curl e openssl já vêm habilitados na imagem oficial.
RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# VirtualHost com AllowOverride All para que o .htaccess (rewrite de rotas) funcione
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# Copia o código da aplicação (o .dockerignore mantém a imagem enxuta)
COPY . /var/www/html

# Permissões para o Apache poder gravar uploads de imagens de perfil
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
