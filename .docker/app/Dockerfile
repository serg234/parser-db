FROM php:8.1-fpm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        default-mysql-client \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Increase upload limits for multiple SQL dumps
RUN { \
        echo "upload_max_filesize=64M"; \
        echo "post_max_size=64M"; \
    } > /usr/local/etc/php/conf.d/uploads.ini

RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

