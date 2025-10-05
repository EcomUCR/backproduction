FROM php:8.2-cli

# Instala dependencias de sistema
RUN apt-get update && apt-get install -y \
    git curl unzip libpq-dev libzip-dev zip libsqlite3-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 1. Copia SÓLO composer files primero
COPY composer.json composer.lock ./

# 2. Instala dependencias (NO ejecutará el post-install ni artisan scripts porque no hay artisan aún, y eso es correcto)
RUN composer install --no-dev --optimize-autoloader

# 3. Ahora sí, copia el resto del código (incluye artisan y todo lo demás)
COPY . .

# 4. Permisos (opcional)
RUN chown -R www-data:www-data storage bootstrap/cache

# 5. Ahora sí, ejecuta comandos artisan
RUN php artisan config:clear \
    && php artisan cache:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && php artisan optimize:clear \
    && php artisan storage:link

EXPOSE $PORT

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=${PORT}"]