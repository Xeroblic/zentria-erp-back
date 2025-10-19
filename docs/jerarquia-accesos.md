# Jerarquía de Accesos y Control Contextual

Este documento resume la solución implementada para acceso jerárquico y granular en Company → Subsidiary → Branch, los cambios clave en modelos, policies, controladores y rutas, más los endpoints para gestionar accesos de usuarios. (El tema de avatar quedó fuera de este documento.)

## Objetivos

- Herencia de acceso por jerarquía: company > subsidiary > branch.
- Granularidad: permitir accesos directos a múltiples subsidiaries y branches, combinables.
- Lectura basada en contexto (no en permisos “globales”): listados y vistas filtran por lo que el usuario puede ver.
- Escritura protegida por permisos/roles administrativos y alcance contextual.
- Endpoints para administrar accesos (add/remove/sync) sin materializar herencia.

---

## Modelo de datos (reutilizado)

- `scope_roles` (ya existía): se usa para roles contextuales y ahora también para “accesos” tipo member.
  - Roles nuevos (de datos): `company-member`, `subsidiary-member`.
  - Se usan para heredar acceso a niveles inferiores (no se crean filas hijas).

- `branch_user` (ya existía): pivot M:N para accesos directos a branches.

- `company_user` (ya existía): pivot M:N para membresía directa a empresas.

### Índices y migraciones añadidos

- `database/migrations/2025_10_19_000900_add_indexes_to_scope_roles_and_branch_user.php`
  - scope_roles: unique `(user_id, role_id, scope_type, scope_id)` + índices `(user_id, scope_type)`, `(scope_type, scope_id)`.
  - branch_user: índice en `branch_id`.

- `database/migrations/2025_10_19_001000_seed_member_roles.php`
  - Crea roles `company-member` y `subsidiary-member` (guard `api`).

---

## Scopes de visibilidad (lectura)

Se añadieron scopes Eloquent que devuelven sólo lo visible para un usuario, uniendo acceso directo ∪ acceso heredado:

- `App\Models\Branch::visibleTo(User $user)`
  - Acceso directo: `branch_user`.
  - Acceso heredado: `subsidiary-member` de su padre o `company-member` del abuelo (via `scope_roles`).

- `App\Models\Subsidiary::visibleTo(User $user)`
  - Acceso por branch directa en la subsidiary.
  - `subsidiary-member` (directo) o `company-member` (heredado).

- `App\Models\Company::visibleTo(User $user)`
  - `company-member` directo o que el usuario tenga branches en esa empresa.

---

## Policies (lectura y escritura)

- BranchPolicy@view / SubsidiaryPolicy@view
  - Lectura sin “permiso global”: se decide por acceso directo u roles contextuales (`*-member` o `*-admin`) o pertenencia.

- viewAny
  - Se agregaron `viewAny` en BranchPolicy y SubsidiaryPolicy para permitir listados cuando haya acceso contextual.

- CompanyPolicy
  - Ajustes para usar slugs correctos (`view-company`, etc.).
  - `view` permite por pertenencia/contexto (companies del usuario, company-member, company-admin).
  - `viewAny` opcional para listados.

- Escritura (create/update/delete)
  - Sigue requiriendo permisos de acción (`create-*`, `edit-*`, `delete-*`) y/o roles admin contextuales.

---

## Controladores y rutas clave

- CompanyController
  - `subsidiaries($id)` y `myCompanySubsidiaries()` ahora usan `->visibleTo($user)` para filtrar.
  - Respuesta resumida con `App\Http\Resources\SubsidiaryBriefResource` (id, name) y `BranchResource` (branches completas).

- SubsidiaryController
  - `index()` autoriza con `authorize('viewAny', Subsidiary::class)` y filtra con `visibleTo`.

- BranchController
  - `index()` autoriza con `authorize('viewAny', Branch::class)` y filtra con `visibleTo`.

- Rutas GET
  - Se reemplazó middleware `can:view-*` por `can:viewAny,Model` en listados (o autorización en controller), evitando depender de permisos globales.

---

## Endpoints de gestión de accesos

Se añadieron endpoints para gestionar accesos, con modo `add | remove | sync`. Los tres requieren que el actor sea `super-admin` o tenga permiso `edit-users`, y además que tenga alcance contextual para otorgar/revocar cada ID (company-admin/subsidiary-admin según corresponda).

Archivo: `routes/apis/access.php` → `UserAccessController`

### 1) Subsidiaries (subsidiary-member via scope_roles)

- `POST /api/users/{user}/access/subsidiaries`

Body:

```json
{ "ids": [10,11], "mode": "sync" }
```

Semántica:
- add: agrega los `ids` concedibles; no quita otros.
- remove: quita los `ids` concedibles; no agrega otros.
- sync: deja exactamente esos `ids` como accesos directos a subsidiaries (concedibles), ni más ni menos.
- No afecta branches directas ni accesos heredados.

### 2) Branches (pivot branch_user)

- `POST /api/users/{user}/access/branches`

Body:

```json
{ "ids": [5,7,9], "mode": "sync" }
```

Semántica igual a la de subsidiaries, pero sólo para accesos directos a sucursales.

### 3) Companies (pivot company_user + company-member en scope_roles)

- `POST /api/users/{user}/access/companies`

Body:

```json
{ "ids": [1,2], "mode": "add" }
```

Agrega membresía en `company_user` y rol contextual `company-member` para cada company concedible.

### Respuesta tipo

```json
{
  "attached": [ ... ],
  "detached": [ ... ],
  "skipped": { "forbidden": [ ... ] }
}
```

> Nota sobre `sync`: es por tipo de acceso del endpoint; no “limpia” otros tipos ni accesos heredados. Si deseas alinear branches con un set específico, ejecuta luego el `sync` de branches.

---

## Datos expuestos para Front (usuarios)

En `AdminController` se incorporaron bloques para que el front pueda mostrar y editar accesos:

- `access.subsidiaries`: subsidiaries directas (de `subsidiary-member`) con `{ id, name, company: { id, name }, source: 'direct' }`.
- `access.branches`: branches directas (de pivot) con `{ id, name, subsidiary: { id, name }, source: 'direct', is_primary, position }`.
- `visible.subsidiaries`: todas las que puede ver (directas ∪ heredadas) con `{ id, name, company: { id, name } }`.
- `visible.branches`: todas las que puede ver (directas ∪ heredadas) con `{ id, name, subsidiary: { id, name } }`.

Esto está disponible en:
- `AdminController@getUsers` (cada ítem del paginado)
- `AdminController@getUser` (usuario individual)

---

## Seeders de demo

- `Database\Seeders\AccessContextDemoSeeder`
  - `empleado@ecotech.cl` → una subsidiary (subsidiary-member)
  - `tecnico@ecotech.cl` → 2 branches directas (pivot)
  - `bodega@ecotech.cl` → 2 subsidiaries (subsidiary-member)

> Correr: `php artisan migrate && php artisan db:seed`

---

## Pruebas rápidas

1) Lectura filtrada
  - `GET /api/subsidiaries` → sólo visibles.
  - `GET /api/branches` → sólo visibles.
  - `GET /api/my-company/subsidiaries` → sólo visibles dentro de la empresa del usuario.

2) Policies de detalle
  - `GET /api/subsidiaries/{id}` o `GET /api/branches/{id}` → 403 si no hay acceso contextual.

3) Gestión de accesos
  - `POST /api/users/{u}/access/subsidiaries { ids, mode }` → ajustar subsidiaries.
  - `POST /api/users/{u}/access/branches { ids, mode }` → ajustar branches.
  - `POST /api/users/{u}/access/companies { ids, mode }` → ajustar companies.

4) Dinámico por herencia
  - Concede `company-member` o `subsidiary-member` y crea una branch nueva en esa jerarquía → el usuario la ve de inmediato en listados `visibleTo`.

---

## Consideraciones de rendimiento

- Scopes con `exists`/joins e índices específicos en pivots/roles.
- Evitar materializar herencia en BD.
- Posible cache de `visible*Ids` por usuario + invalidación al cambiar `scope_roles`/`branch_user`.

---

## Extensiones posibles

- Parámetro opcional `cascade` en `syncSubsidiaries` para recortar branches directas fuera del set de subsidiaries (hoy se hace con `sync` de branches aparte).
- GET para “estado de accesos” del usuario (si se quiere desacoplar de `AdminController`).

