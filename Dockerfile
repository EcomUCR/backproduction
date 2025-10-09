FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev zip libonig-dev libpng-dev libxml2-dev libssl-dev default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE $PORT

CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan optimize:clear && \
    php artisan storage:link && \
    php artisan serve --host=0.0.0.0 --port=${PORT}
