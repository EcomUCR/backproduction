# Imagen base con PHP 8.2 y extensiones necesarias
FROM php:8.2-cli

# Instalar dependencias del sistema necesarias para Laravel + PostgreSQL
RUN apt-get update && apt-get install -y \
    git curl unzip libzip-dev zip libonig-dev libpng-dev libxml2-dev libssl-dev libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar el proyecto completo
COPY . .

# Instalar dependencias de Laravel sin las de desarrollo
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Crear enlaces simbólicos y asegurar permisos correctos
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Exponer el puerto dinámico asignado por Render
EXPOSE 10000

# Comando de arranque
CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan optimize:clear && \
    php artisan storage:link && \
    php artisan serve --host=0.0.0.0 --port=10000