FROM php:8.3-cli-bookworm

# Paquetes del sistema y cliente de Postgres
RUN apt-get update && apt-get upgrade -y && apt-get install -y --no-install-recommends \
    git unzip zip libpq-dev postgresql-client \
 && docker-php-ext-install pdo_pgsql \
 && apt-get clean && rm -rf /var/lib/apt/lists/*


 # Xdebug deshabilitado
 # Por si la imagen base/parent lo traía habilitado, lo “extirpamos”:
RUN rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && rm -f /usr/local/etc/php/conf.d/*xdebug*.ini || true \
    && (pecl uninstall xdebug || true)

    # Si alguien setea la env por error, igual queda apagado
ENV XDEBUG_MODE=off
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

# Opcache
RUN docker-php-ext-install opcache
COPY docker/php-opcache.ini /usr/local/etc/php/conf.d/opcache.ini

EXPOSE 8000
CMD ["/entrypoint.sh"]
