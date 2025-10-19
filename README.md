**Zentria ERP Back**

- Laravel 12 API backend con JWT y control de acceso por roles/permisos (Spatie).
- Datos geográficos de Chile (regiones, provincias, comunas) + endpoints de consulta.
- Gestión de empresas, subsidiarias y sucursales con relación a comuna.
- Docker Compose para levantar el entorno; tests se ejecutan automáticamente al iniciar.

**Requisitos**
- Docker y Docker Compose (recomendado)
- Alternativa sin Docker: PHP 8.2+, Composer, Postgres 16+, extensiones pdo_pgsql, gd, zip, bcmath

**Stack principal**
- Autenticación: `tymon/jwt-auth`
- Roles y permisos: `spatie/laravel-permission`
- Media (sucursales): `spatie/laravel-medialibrary`

**Correr con Docker (recomendado)**
- Configura `.env` (se copia de `.env.example` al iniciar si no existe). Variables relevantes ya están en `docker-compose.yml`:
  - `DB_*` apuntan al servicio `db`
  - `RUN_MIGRATIONS=true`, `RUN_SEED=true`, `RUN_TESTS=true`
- Levantar servicios:
  - `docker compose up -d`
- ¿Qué hace el entrypoint del servicio `app`?
  - Espera a Postgres → instala dependencias → limpia cachés
  - Genera `APP_KEY` y `JWT_SECRET` si faltan
  - Ejecuta migraciones (`php artisan migrate --force`)
  - Ejecuta seeders (`php artisan db:seed --force`) si `RUN_SEED=true`
  - Ejecuta tests (`php artisan test`) si `RUN_TESTS=true`
    - Mensajes:
      - Éxito: `✅ X tests probados y ejecutados correctamente.`
      - Error: `❌ Tests fallaron. Resumen: ...` y detiene el contenedor
  - Levanta servidor embebido en `http://localhost:8000`

**Usuarios de ejemplo (seed)**
- Super admin (siempre cámbialo en producción):
  - Email: `rbarrientos@tikinet.cl`
  - Password: `Hola2025!`

**Migraciones y Seeders**
- Estructura geográfica: `regions`, `provinces`, `communes`
- Empresas y organización: `companies`, `subsidiaries`, `branches`, `users`
- Seeders clave (ordenados en `DatabaseSeeder`):
  - `RegionSeeder`, `ProvinceSeeder`, `CommuneSeeder`
  - `RolesAndPermissionsSeeder`, `FixPermissionGuardSeeder`
  - `EmpresaSeeder`, `SuperAdminSeeder`, `UsuarioBasicoSeeder`
  - Demo: `MultiCompanyExampleSeeder`, `DemoCatalogSeeder`

**Autenticación**
- Registro/login via JWT (`/api/auth/register`, `/api/auth/login`)
- Perfil: `/api/auth/perfil` incluye `commune.province.region`
- Token refresh: `/api/auth/refresh` (middleware `auth:api`)

**Permisos y roles (Spatie)**
- Guard principal `api`
- Middlewares disponibles:
  - `auth:api`, `can:<permiso>`, `role:<rol>`, `permission:<permiso>`
- Ejemplos de permisos: `view-company`, `edit-company`, `create-branch`, `view-user`

**Ubicación: comunas**
- Endpoints públicos (solo GET):
  - `GET /api/locations/communes?province_id=...&with=province,province.region`
  - `GET /api/locations/communes/{id}?with=province.region`
- Usuarios pueden actualizar su comuna:
  - `PATCH /api/users/{id}/commune` (propio usuario o permiso `user.edit`)
  - `PATCH /api/me/commune`

**Empresas, subsidiarias y sucursales**
- POST/PUT aceptan `commune_id` como opcional.
- Endpoints aislados para actualizar solo la comuna:
  - `PATCH /api/companies/{id}/commune` (permiso `edit-company`)
  - `PATCH /api/subsidiaries/{id}/commune` (permiso `edit-subsidiary`)
  - `PATCH /api/branches/{id}/commune` (permiso `edit-branch`)
- Parámetros comunes:
  - Body: `{ "commune_id": 13101 }` o `null` para limpiar
  - Query `?with=commune` para incluir objeto relacionado

**Probar sin Docker (local)**
- Requisitos instalados + Postgres accesible
- Pasos:
  - `cp .env.example .env` y configura `DB_*`
  - `composer install`
  - `php artisan key:generate`
  - `php artisan jwt:secret`
  - `php artisan migrate --seed`
  - `php artisan serve`

**Tests**
- Ejecutar manualmente: `php artisan test`
- Base de datos de test: SQLite en memoria (ver `phpunit.xml`)
- Suites agregadas:
  - `tests/Feature/PermissionsTest.php`
  - `tests/Feature/UpdateCommuneEndpointsTest.php`

**Estructura de carpetas**
- `app/` Código de aplicación (controllers, models, policies, resources)
- `routes/` Rutas API y módulos
- `database/` Migraciones y seeders
- `docker/` Dockerfile, entrypoint y config PHP
- `miscelaneo/` Scripts y documentación auxiliar no esenciales para runtime

**Notas y troubleshooting**
- Si tu IDE marca “Middleware [auth:api] not found”, es un falso positivo en Laravel 12. En runtime funciona; los alias están en `bootstrap/app.php`.
- Limpieza de cachés al cambiar permisos/rutas:
  - `php artisan optimize:clear && php artisan route:clear && php artisan config:clear`
- Si los tests fallan al levantar con Docker, revisa logs del servicio `app`:
  - `docker compose logs -f app`

**Licencia**
- MIT
