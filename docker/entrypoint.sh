#!/usr/bin/env bash
set -e

cd /var/www

# Si no hay .env, lo crea desde el ejemplo
if [ ! -f .env ] && [ -f .env.example ]; then
  cp .env.example .env
fi

# Espera a PostgreSQL
echo "Esperando a PostgreSQL en ${DB_HOST}:${DB_PORT:-5432}..."
until pg_isready -h "${DB_HOST}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" >/dev/null 2>&1; do
  sleep 1
done
echo "PostgreSQL disponible."

# Instala dependencias si falta vendor
if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f vendor/autoload.php ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi
php artisan package:discover --ansi || true
php artisan optimize:clear || true
exec "$@"

# Claves de la app y JWT
php artisan key:generate --force || true
if ! grep -q "^JWT_SECRET=" .env 2>/dev/null; then
  php artisan jwt:secret --force || true
fi

# Limpia/optimiza y migra
php artisan optimize:clear || true
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force
fi
if [ "${RUN_SEED:-false}" = "true" ]; then
  php artisan db:seed --force
fi

# Ejecutar tests si está habilitado
if [ "${RUN_TESTS:-true}" = "true" ]; then
  echo "Ejecutando test suite..."
  set +e
  TEST_OUTPUT=$(php artisan test --ansi 2>&1)
  TEST_STATUS=$?
  # Imprimir salida original de PHPUnit/Laravel test
  echo "$TEST_OUTPUT"
  # Extraer resumen "Tests: X, Assertions: Y, ..." si existe
  TESTS_LINE=$(echo "$TEST_OUTPUT" | grep -E "Tests:\s*[0-9]+" | tail -1)
  TESTS_COUNT=$(echo "$TESTS_LINE" | sed -n 's/.*Tests:\s*\([0-9]\+\).*/\1/p')
  set -e
  if [ $TEST_STATUS -ne 0 ]; then
    if [ -n "$TESTS_LINE" ]; then
      echo "❌ Tests fallaron. Resumen: $TESTS_LINE" >&2
    else
      echo "❌ Tests fallaron." >&2
    fi
    exit 1
  else
    if [ -n "$TESTS_COUNT" ]; then
      echo "✅ $TESTS_COUNT tests probados y ejecutados correctamente."
    else
      echo "✅ Tests ejecutados correctamente."
    fi
  fi
fi

# imagenes spatie
mkdir -p storage/app/public
php artisan storage:link || true

# Permisos
chown -R www-data:www-data storage bootstrap/cache || true

# Arranca el servidor embebido
exec php artisan serve --host=0.0.0.0 --port=${APP_PORT:-8000}
