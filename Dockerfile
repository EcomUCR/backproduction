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

# Render necesita un puerto fijo declarado, no una variable
EXPOSE 8000

CMD php artisan config:clear || true \
    && php artisan cache:clear || true \
    && php artisan route:clear || true \
    && php artisan view:clear || true \
    && php artisan optimize:clear || true \
    && php artisan storage:link || true \
    && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
