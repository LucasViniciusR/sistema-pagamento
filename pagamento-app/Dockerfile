FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    libsasl2-dev \
    librdkafka-dev \
    build-essential \
    && docker-php-ext-install pdo pdo_mysql mbstring gd

RUN pecl install mongodb rdkafka xdebug \
    && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini \
    && docker-php-ext-enable rdkafka \
    && docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /var/www/html

COPY . /var/www/html
RUN composer install --no-interaction --prefer-dist || true

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000

CMD ["php-fpm"]
