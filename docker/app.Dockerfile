FROM php:8.3-cli-bookworm

# Paquetes del sistema y cliente de Postgres
RUN apt-get update && apt-get upgrade -y && apt-get install -y --no-install-recommends \
    git unzip zip libpq-dev postgresql-client \
 && docker-php-ext-install pdo_pgsql \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

 # --- Imagenes: GD (jpeg/png/webp) + EXIF + ZIP (para algunos paquetes)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libjpeg62-turbo-dev libpng-dev libwebp-dev libfreetype6-dev libzip-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd exif zip \
 && docker-php-ext-enable exif \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# --- Límites de subida razonables para imágenes (2–32 MB según lo que suben)
RUN printf "upload_max_filesize=32M\npost_max_size=32M\nmemory_limit=512M\n" \
  > /usr/local/etc/php/conf.d/uploads.ini

# --- OPCIONAL: Imagick (mejores conversiones y soporte PDF)
 RUN apt-get update && apt-get install -y --no-install-recommends libmagickwand-dev ghostscript \
 && pecl install imagick \
 && docker-php-ext-enable imagick \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

# BCMath (Laravel lo usa para algunas cosas)
RUN docker-php-ext-install -j"$(nproc)" bcmath \
 && docker-php-ext-enable bcmath

# Redis extension for Laravel queue/cache
RUN pecl install redis \
 && docker-php-ext-enable redis

# PCNTL needed by Laravel Horizon
RUN docker-php-ext-install pcntl

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
