FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git zip unzip curl supervisor libzip-dev libpng-dev libonig-dev \
    libxml2-dev libcurl4-openssl-dev libssl-dev pkg-config \
    libbrotli-dev \
    && docker-php-ext-install pdo pdo_mysql zip exif pcntl bcmath sockets

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
