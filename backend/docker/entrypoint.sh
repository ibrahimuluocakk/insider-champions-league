#!/bin/bash

composer install --no-interaction --prefer-dist

# ENV dosyası kontrolü
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Key generate gerekiyorsa yap
if ! grep -q "APP_KEY=base64" .env; then
    php artisan key:generate
fi

# MySQL'in hazır olmasını bekle
echo "Waiting for MySQL to be ready..."
maxTries=60
while [ "$maxTries" -gt 0 ] && ! php artisan db:monitor > /dev/null 2>&1; do
    maxTries=$(( maxTries - 1 ))
    sleep 1
done

if [ "$maxTries" -le 0 ]; then
    echo >&2 'Error: MySQL did not become ready in time'
    exit 1
fi

echo "MySQL is ready!"

# Migration varsa çalıştır
php artisan migrate --force

echo "Running database seeds..."
php artisan db:seed --force || true

exec php-fpm
