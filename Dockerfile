FROM php:8.2-fpm as base

RUN apt-get update && apt-get install -y \
    git curl unzip libpq-dev libzip-dev zip libsqlite3-dev pkg-config zlib1g-dev libonig-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .
RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data storage bootstrap/cache
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache

FROM nginx:alpine
COPY --from=base /var/www /var/www
COPY --from=base /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
COPY ./default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]