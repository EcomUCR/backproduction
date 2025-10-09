# Imagen base de PHP
FROM php:8.2-cli

# Instalar dependencias necesarias para Laravel y PostgreSQL
RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev zip libonig-dev libpng-dev libxml2-dev libssl-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copiar Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar todos los archivos del proyecto
COPY . .

# Instalar dependencias de Laravel (sin dev)
RUN composer install --no-dev --optimize-autoloader

# Dar permisos a las carpetas de cache y storage
RUN chown -R www-data:www-data storage bootstrap/cache

# Exponer el puerto que Render asigna automáticamente
EXPOSE $PORT

# Comandos de inicio del servidor Laravel
CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan optimize:clear && \
    php artisan storage:link && \
    php artisan serve --host=0.0.0.0 --port=${PORT}
