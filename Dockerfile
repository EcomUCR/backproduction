FROM php:8.2-cli

# Instala dependencias del sistema necesarias
RUN apt-get update && apt-get install -y \
    git curl unzip libpq-dev libzip-dev zip libsqlite3-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copia Composer desde el contenedor oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copia SÓLO los archivos necesarios para instalar dependencias primero (cache eficiente de Docker)
COPY composer.json composer.lock ./

# Limpia vendor antes de instalar (evita vendor cacheado)
RUN rm -rf vendor/ && \
    composer install --no-dev --optimize-autoloader

# Copia el resto del código (después del vendor para el cache de capas)
COPY . .

# Permisos - SÓLO para quien lo necesite, usar en desarrollo o si app lo requiere
RUN chown -R www-data:www-data storage bootstrap/cache

# Limpia todos los caches y publica enlaces antes de arrancar
RUN php artisan config:clear \
    && php artisan cache:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && php artisan optimize:clear \
    && php artisan storage:link

EXPOSE $PORT

# Ejecuta el servidor
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=${PORT}"]