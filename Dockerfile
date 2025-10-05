FROM php:8.2-cli

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    git curl unzip libpq-dev libzip-dev zip libsqlite3-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copia Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 1. Copia sólo composer.json y composer.lock
COPY composer.json composer.lock ./

# 2. Instala dependencias, PERO DESACTIVA SCRIPTS porque artisan aún no existe
RUN composer install --no-dev --optimize-autoloader --no-scripts

# 3. Ahora sí, copia todo el código (incluye artisan y el resto)
COPY . .

# 4. Corre los scripts necesarios de composer/artisan (ahora artisan sí existe)
RUN composer run-script post-autoload-dump \
    && php artisan package:discover --ansi \
    && php artisan config:clear \
    && php artisan cache:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && php artisan optimize:clear \
    && php artisan storage:link

# 5. Permisos (opcional, si tu entorno lo exige)
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE $PORT

# 6. Arranca el servidor php artisan
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=${PORT}"]