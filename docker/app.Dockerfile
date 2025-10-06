FROM php:8.3.16-cli-bookworm

# Paquetes del sistema y cliente de Postgres
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip zip libpq-dev postgresql-client \
 && docker-php-ext-install pdo_pgsql \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# (Opcional) instala deps si existen los archivos de composer
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --no-progress || true

# Copia el proyecto
COPY . .

# Permisos básicos
RUN chown -R www-data:www-data storage bootstrap/cache

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000
CMD ["/entrypoint.sh"]
