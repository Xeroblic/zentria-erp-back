# Notificaciones ERP — Guía Completa (Laravel 12 + PostgreSQL + Redis + Horizon + React)

- Objetivo: sistema de notificaciones multi-empresa/sucursal con bundles por rol, overrides por usuario, prioridades P1/P2/P3 y canales in-app, email (digest) y webhook (futuro). Incluye deduplicación, rate limit, silencios por horario, auditoría y métricas.

## Arquitectura

- Backend: Laravel 12 API con `tymon/jwt-auth`, Spatie v6 (sin teams), PostgreSQL.
- Alcance: Company > Subsidiary > Branch usando `scope_roles` y `branch_user`.
- Realtime: SSE inicial; WebSockets opcional a futuro.
- Colas: Redis + Horizon. Digest y envíos por colas dedicadas.
- Frontend: React + TS + Vite + Tailwind + Redux Toolkit.

## Modelo De Datos (PostgreSQL)

### Tablas

- `notification_types`
  - Campos: `id`, `key` (única, kebab-case), `module`, `description`, `default_priority CHAR(2) CHECK IN ('P1','P2','P3')`, `default_channels JSONB`, `critical BOOL`, `enabled_global BOOL DEFAULT true`, timestamps.
  - Índices: `UNIQUE (key)`.

- `role_notification_defaults`
  - Campos: `id`, `role_id` (FK Spatie), `notification_type_id` (FK), `allowed BOOL`, `channels JSONB NULL`, `priority_override CHAR(2) NULL`.
  - Índices: `(role_id, notification_type_id) UNIQUE`.

- `user_notification_preferences`
  - Campos: `id`, `user_id`, `notification_type_id`, `allowed BOOL`, `channels JSONB NULL`, `snooze_until TIMESTAMP NULL`, `quiet_hours JSONB {from,to,days} NULL`.
  - Índices: `(user_id, notification_type_id) UNIQUE`.

- `notification_events`
  - Campos: `id`, `type_id FK`, `entity_type`, `entity_id`, `company_id`, `subsidiary_id`, `branch_id`, `priority CHAR(2)`, `payload JSONB`, `dedup_key TEXT`, `occurred_at TIMESTAMP`, timestamps.
  - Índices: `(dedup_key, occurred_at DESC)`, `(occurred_at)`, GIN opcional sobre `payload`.

- `user_notifications`
  - Campos: `id`, `user_id`, `event_id`, `status VARCHAR CHECK IN ('unread','read','ack','assigned')`, `assigned_to BIGINT NULL`, `delivered_channels JSONB`, `read_at TIMESTAMP NULL`, `ack_at TIMESTAMP NULL`, `aggregate_count INT DEFAULT 1`, `last_occurred_at TIMESTAMP NULL`, timestamps.
  - Índices: `(user_id, status)`, `(event_id)`, `(read_at)`.

- `notification_delivery_logs`
  - Campos: `id`, `user_notification_id`, `channel VARCHAR CHECK IN ('inapp','email','webhook')`, `delivered_at TIMESTAMP`, `status`, `error TEXT NULL`.
  - Índices: `(user_notification_id, channel)`.

### Extras recomendados

- `users.timezone VARCHAR` (IANA, ej. `America/Santiago`) para digest/quiet hours.

## Precedencia Y Elegibilidad

- Resolución efectiva: `effective = notification_types ⊕ role_notification_defaults ⊕ user_notification_preferences ⊕ emergency_rules(P1)`.
  - P1: siempre `allowed=true`, canal `inapp` inmediato; ignora quiet hours; opcional email instantáneo.
  - Exponer `origin: "global"|"role"|"user"` por tipo en API de preferencias.
- Scope-aware (Company > Subsidiary > Branch):
  - Entregar solo a usuarios que cumplan pertenencia directa (`branch_user`) o heredada por `scope_roles` (como en `Branch::scopeVisibleTo`, `Subsidiary::scopeVisibleTo`, `Company::scopeVisibleTo`).
  - Policies existentes refuerzan acceso a leer/ack/asignar.

## Servicios Backend (App\Services\Notifications\...)

- `NotificationPolicyService`
  - Verifica pertenencia/permiso por evento (branch/subsidiary/company).
  - Calcula `allowed`, `channels`, `priority` y `origin` aplicando precedencia; bloquea P1.

- `NotificationRouter`
  - Dado un `notification_event`, resuelve destinatarios elegibles y crea `user_notifications` por usuario.
  - Aplica deduplicación y rate limit antes de insertar; actualiza `aggregate_count`/`last_occurred_at`.

- `DeduplicationService`
  - Redis: `SETNX dedup:{key} 1 EX TTL` para cortocircuitar duplicados.
  - DB: lookup en `notification_events` por `dedup_key` y ventana `TTL` para agrupación y métricas.

- `RateLimitService`
  - Redis: contadores por ventana `rate:{type}:{entity}:{branch}` con `EX`.
  - DB alternativa: tabla de contadores por “bucket” temporal y upsert.

- `QuietHoursService`
  - Evalúa por TZ usuario; P1 no aplica; P2/P3 mueven canal `email` a digest.

- `DigestService`
  - Toma P2/P3 pendientes por usuario en horarios de `.env`, genera email resumen; marca entregas en logs.

- `RealtimeService`
  - Emite SSE al crear `user_notifications` (eventos mínimos: id, title, priority, created_at, aggregate_count).

## Canales Y Reglas

- P1: in-app inmediato, opcional email instantáneo; no desactivable.
- P2: in-app inmediato + digest.
- P3: inbox y digest por defecto.
- Webhook: reservado para futuro, con reintentos y DLQ.

## API (JSON)

Rutas en `routes/apis/notifications.php` (protegidas con `auth:api`):

- GET `/me/notifications?status=&priority=&module=&branch_id=&page=`
  - Devuelve inbox paginado con `origin`, `aggregate_count`, `delivered_channels`, `assigned_to`.
- POST `/notifications/{id}/read`
- POST `/notifications/{id}/ack`
- POST `/notifications/{id}/assign` body: `{ "user_id": number }`
- GET `/me/notification-preferences`
- PUT `/me/notification-preferences` (bulk upsert; respetar lock P1)
- GET `/admin/role-bundles/{role_id}`
- PUT `/admin/role-bundles/{role_id}` (solo admin; scope empresa si aplica)
- POST `/events/test` (solo dev) crea un `notification_event` fake para QA.
- GET `/me/notifications/stream?access_token=<jwt>` SSE con reconexión.

## Seeds Iniciales

- `notification_types` (10–12):
  - `transfer.sent` (P2), `transfer.receipt-discrepancy` (P1, critical)
  - `quote.expiring-soon` (P3), `quote.converted` (P2)
  - `sale.delivery-due-today` (P2), `payment.confirmed` (P2)
  - `system.sequence-threshold` (P1), `system.sync-failed` (P1)
  - +2–4 según módulos activos.

- `role_notification_defaults` para roles existentes:
  - `company-admin`, `subsidiary-admin`, `branch-admin`, `company-member`, `subsidiary-member`.
  - Canales y prioridades sensatas por rol; P1 inalterable.

## Jobs, Colas Y Horizon

### Colas sugeridas

- `notifications.p1` (in-app inmediato)
- `notifications.default` (P2)
- `notifications.email`
- `notifications.digest`
- `notifications.webhook`

### Diseño de jobs

- Idempotencia: pasar IDs, verificar existencia/estado antes de actuar; usar locks Redis (`withoutOverlapping`).
- Reintentos/backoff: exponencial con jitter; DLQ en `failed_jobs` o SQS futuro.
- Rate limit middleware: Redis si está disponible; fallback DB.
- Tagging: `public function tags()` por `type`, `company_id`, `branch_id`, `priority`.

### Operación con Horizon

- Pools separados por cola con concurrencias distintas: P1 prioritaria.
- Rotación por memoria/tiempo: `--max-jobs`, `--max-time`, `--memory`.
- Dashboard Horizon para monitoreo y retry.

## Realtime (SSE)

- Endpoint: `GET /me/notifications/stream?access_token=<jwt>&lastEventId=<id>`
- Seguridad: validar JWT; TTL corto recomendado para tokens de stream; soportar `Last-Event-ID`.
- Reintentos: exponencial del lado del cliente; fallback a polling si 401.

## Métricas

- Entregas: `notification_delivery_logs` por canal.
- Lecturas: `user_notifications.read_at`.
- Acks: `user_notifications.ack_at`.
- MTTA: `ack_at - delivered_at(inapp)` para P1/P2 (usar primera entrega in-app del log).
- Reportes: vistas/materialized views opcionales; índices sobre fechas.

## Configuración (.env)

- Notificaciones:
  - `NOTIF_DEDUP_TTL=7200`
  - `NOTIF_DIGEST_TIMES=["07:00","16:00"]`
  - `NOTIF_EMAIL_FROM=no-reply@...`
- Redis/Horizon:
  - `QUEUE_CONNECTION=redis`
  - `REDIS_HOST=redis`
  - `REDIS_PORT=6379`
  - `REDIS_PASSWORD=` (si aplica)
- JWT:
  - `JWT_SECRET=...`
- Mailer:
  - `MAIL_*` según entorno (Mailpit en DEV)
- Opcional:
  - `BROADCAST_DRIVER=redis` (si migran a websockets)

## Docker Compose (Servicios Nuevos)

### Resumen de servicios

- Redis: imagen oficial, persistencia opcional.
- Horizon: correr `php artisan horizon` (o via Supervisor en el contenedor de app).

### Ejemplo mínimo (fragmento)

```yaml
services:
  redis:
    image: redis:7-alpine
    ports: ["6379:6379"]
    volumes: ["redis:/data"]

  app:
    depends_on: [redis]
    environment:
      QUEUE_CONNECTION: redis
      REDIS_HOST: redis

  horizon:
    build: .
    command: php artisan horizon
    depends_on: [app, redis]
    environment:
      QUEUE_CONNECTION: redis
      REDIS_HOST: redis

volumes:
  redis: {}
```

## Pasos De Despliegue

1. Añadir servicios a `docker-compose` (redis + horizon) y levantar: `docker compose up -d`.
2. Actualizar `.env`:
   - `QUEUE_CONNECTION=redis`, `REDIS_*`, `NOTIF_*`.
3. Instalar Horizon y migraciones de colas:
   - `composer require laravel/horizon`
   - `php artisan vendor:publish --tag=horizon-config`
   - `php artisan migrate`
4. Programar scheduler (host/cron): `* * * * * php artisan schedule:run`.
5. Semillas:
   - `php artisan db:seed --class=NotificationTypesSeeder`
   - `php artisan db:seed --class=RoleNotificationDefaultsSeeder`
6. Verificar:
   - `php artisan horizon` o panel Horizon.
   - Probar `POST /events/test` y SSE `/me/notifications/stream`.

## Frontend (React + Redux Toolkit)

- Slice `notificationsSlice`:
  - State: `items`, `unreadCount`, `filters`, `loading`, `error`.
  - Thunks: `fetchNotifications`, `markRead`, `ack`, `assign`, `fetchPrefs`, `updatePrefs`.
- Realtime:
  - `EventSource` con `?access_token=<jwt>`; reconexión y actualización de `unreadCount`.
- Componentes:
  - `NotificationBell`: contador + últimas 10.
  - `NotificationInbox`: tabs por tipo, filtros por `module/branch/priority`, tarjetas con CTA.
  - `NotificationPreferences`: tabla por `notification_type` con `Allowed`, `Channels`, `Priority`, `Origin` (Role/User/Global), `Lock` (P1); botones “Volver a valores del rol” y presets.

## Pruebas

- PHPUnit:
  - Precedencia: global < rol < usuario; bloqueo P1.
  - Elegibilidad por alcance (branch/subsidiary/company) con fixtures `branch_user`/`scope_roles`.
  - Deduplicación por `dedup_key` y rate limit.
  - Digest: generación por horarios y TZ; quiet hours aplicadas.
- Front:
  - Reducers y render de `NotificationBell/Inbox/Preferences`.
  - Listener SSE: incrementa `unreadCount`.

## Buenas Prácticas Y Decisiones Clave

- Idempotencia y locks: imprescindibles en jobs (evitar duplicados por reintentos).
- P1 no desactivables: forzar en PolicyService y bloquear en UI.
- PostgreSQL JSONB y CHECKs: mejor que ENUM nativo para flexibilidad.
- Redis + Horizon: estándar Laravel, escalable y observable.
- Origen (`origin`) y `aggregate_count`: necesarios para UX y auditoría.

## Rutas Y Archivos Sugeridos

- Rutas: `routes/apis/notifications.php` (incluida desde `routes/api.php`).
- Modelos: `app/Models/Notifications/*` para Eloquent de nuevas tablas.
- Servicios: `app/Services/Notifications/*` (Policy, Router, Dedup, Rate, QuietHours, Digest, Realtime).
- Controladores: `app/Http/Controllers/Notifications/*`.
- Jobs: `app/Jobs/Notifications/*`.
- Seeds: `database/seeders/NotificationTypesSeeder.php`, `RoleNotificationDefaultsSeeder.php`.

---

Si se mueve de máquina: clonar repo, asegurar Docker con redis/horizon, configurar `.env` (`QUEUE_CONNECTION=redis`, `REDIS_*`, `NOTIF_*`), correr migraciones y seeds, levantar Horizon, probar `/events/test` + SSE y verificar colas en el dashboard.

