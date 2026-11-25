#!/bin/sh
# Garante dependências e permissões quando o código é montado como volume
# (docker-compose de desenvolvimento usa ".:/var/www/html").
set -e
cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    echo "[farmview] vendor/ ausente: executando composer install..."
    composer install --no-dev --no-interaction --prefer-dist
fi

mkdir -p storage/logs storage/videos storage/cache Public/uploadImages Public/templates/Images
chown -R www-data:www-data storage Public/uploadImages Public/templates/Images 2>/dev/null || true

exec "$@"
