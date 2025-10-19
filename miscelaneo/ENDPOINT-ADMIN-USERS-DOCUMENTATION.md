# 🎯 ENDPOINT CONSOLIDADO PARA ADMINISTRACIÓN DE USUARIOS Y PERMISOS

## 📍 Endpoint Principal

**URL:** `GET /api/admin/users`

**Descripción:** Endpoint unificado que devuelve usuarios con información completa de permisos, roles y estructura jerárquica, respetando automáticamente los niveles de acceso del usuario autenticado.

---

## 🔐 Niveles de Acceso Jerárquico (Automático)

### 1. **Super Admin** (`super-admin`)
- ✅ **Acceso:** TODOS los usuarios del sistema
- ✅ **Puede editar:** Cualquier usuario (excepto otros super-admins)
- ✅ **Ve:** Información completa de todos los usuarios

### 2. **Company Admin** (`company-admin`)
- ✅ **Acceso:** Usuarios de SUS empresas únicamente
- ✅ **Puede editar:** Usuarios de sus empresas (excepto super-admins)
- ✅ **Ve:** Usuarios de empresas donde tiene rol `company-admin`

### 3. **Subsidiary Admin** (`subsidiary-admin`)
- ✅ **Acceso:** Usuarios de SUS subsidiarias únicamente
- ✅ **Puede editar:** Usuarios de sus subsidiarias (excepto super-admins y company-admins)
- ✅ **Ve:** Usuarios de subsidiarias donde tiene rol `subsidiary-admin`

### 4. **Branch Admin** (`branch-admin`)
- ✅ **Acceso:** Usuarios de SUS sucursales únicamente
- ✅ **Puede editar:** Usuarios de sus sucursales (excepto niveles superiores)
- ✅ **Ve:** Usuarios de sucursales donde tiene rol `branch-admin`

### 5. **Employee** (`employee`)
- ✅ **Acceso:** Solo usuarios de su mismo nivel o inferior
- ❌ **No puede editar:** Usuarios con roles administrativos
- ✅ **Ve:** Empleados de su misma sucursal/área

---

## 📊 Estructura de Respuesta

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "pk": 1,
      "email": "usuario@empresa.com",
      "first_name": "Juan",
      "second_name": "Carlos",
      "last_name": "Pérez",
      "second_last_name": "González",
      "rut": "12345678-9",
      "celular": "999999999",
      "cargo": "Desarrollador Senior",
      "is_staff": false,
      "is_active": true,
      "can_edit": true,
      "is_super_admin": false,
      
      // 🏢 INFORMACIÓN DE EMPRESAS
      "companies": [
        {
          "id": 1,
          "name": "EcoTech SPA",
          "is_primary": true,
          "position": "Desarrollador"
        }
      ],
      
      // 👥 ROLES GLOBALES (Spatie)
      "global_roles": ["employee", "technician"],
      
      // 🎯 ROLES CONTEXTUALES (Por empresa/subsidiaria/sucursal)
      "contextual_roles": [
        {
          "role": "branch-admin",
          "scope_type": "branch",
          "scope_id": 5,
          "scope_name": "Sucursal Centro"
        }
      ],
      
      // 🔐 PERMISOS DETALLADOS
      "direct_permissions": ["view-reports"],
      "role_permissions": ["view-users", "edit-users", "view-branches"],
      "all_permissions": ["view-reports", "view-users", "edit-users", "view-branches"],
      
      // 📍 INFORMACIÓN DE UBICACIÓN
      "branch": {
        "id": 5,
        "branch_name": "Sucursal Centro",
        "is_primary": true,
        "position": "Desarrollador",
        "subsidiary": {
          "id": 3,
          "subsidiary_name": "EcoTech Centro",
          "company": {
            "id": 1,
            "company_name": "EcoTech SPA"
          }
        }
      },
      
      "created_at": "2025-08-01T21:20:24.000000Z",
      "updated_at": "2025-08-01T21:20:24.000000Z"
    }
  ],
  
  // 📄 METADATOS DE PAGINACIÓN
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 9,
    "from": 1,
    "to": 9
  },
  
  // 🔐 CONTEXTO DEL USUARIO AUTENTICADO
  "user_context": {
    "current_user_id": 1,
    "is_super_admin": true,
    "can_manage_users": true,
    "access_level": "super-admin"
  }
}
```

---

## 🔍 Parámetros de Filtrado

### Query Parameters Disponibles:

- **`search`** _(string)_: Busca en nombre, apellido, email o RUT
- **`company_id`** _(int)_: Filtra usuarios de una empresa específica
- **`role`** _(string)_: Filtra usuarios con un rol específico
- **`per_page`** _(int)_: Elementos por página (default: 15)
- **`page`** _(int)_: Número de página

### Ejemplos de Uso:

```bash
# Buscar usuarios
GET /api/admin/users?search=juan

# Filtrar por empresa
GET /api/admin/users?company_id=1

# Filtrar por rol
GET /api/admin/users?role=branch-admin

# Paginación
GET /api/admin/users?per_page=10&page=2

# Combinado
GET /api/admin/users?search=admin&company_id=1&per_page=5
```

---

## 🛡️ Protecciones Implementadas

### ✅ **Super Admin Protection**
- Los super-admins **NO pueden ser editados** por otros usuarios
- Solo aparecen como `"can_edit": false` para usuarios no super-admin
- El propio super-admin puede editarse a sí mismo

### ✅ **Filtrado Jerárquico Automático**
- Cada usuario **solo ve usuarios en su scope de acceso**
- No requiere parámetros adicionales
- Se aplica automáticamente según el rol del usuario autenticado

### ✅ **Información Sensible**
- Passwords nunca se exponen
- Solo se muestra información relevante para administración
- Contexto del usuario autenticado para controles del frontend

---

## 🚀 Endpoints Complementarios

### 📋 **Obtener Roles Disponibles**
```bash
GET /api/admin/roles
```

### 📋 **Obtener Permisos Disponibles**
```bash
GET /api/admin/permissions
```

### 👤 **Gestión de Roles de Usuario**
```bash
# Ver roles de un usuario
GET /api/admin/users/{id}/roles

# Asignar roles
POST /api/admin/users/{id}/roles
{
  "roles": ["branch-admin", "manager"]
}

# Revocar roles
DELETE /api/admin/users/{id}/roles
{
  "roles": ["manager"]
}
```

### 🔐 **Gestión de Permisos de Usuario**
```bash
# Ver permisos de un usuario
GET /api/admin/users/{id}/permissions

# Asignar permisos directos
POST /api/admin/users/{id}/permissions
{
  "permissions": ["view-reports", "export-data"]
}

# Revocar permisos
DELETE /api/admin/users/{id}/permissions
{
  "permissions": ["export-data"]
}
```

---

## 📱 Implementación Frontend

### 🎯 **Uso Recomendado:**

1. **Cargar usuarios con contexto completo:**
   ```javascript
   const response = await fetch('/api/admin/users', {
     headers: { 'Authorization': `Bearer ${token}` }
   });
   const { data: users, user_context, meta } = await response.json();
   ```

2. **Controlar UI según contexto:**
   ```javascript
   // Mostrar/ocultar botones según permisos
   const canEdit = user_context.can_manage_users;
   const isSuperAdmin = user_context.is_super_admin;
   
   // Deshabilitar edición de super-admins
   users.forEach(user => {
     if (user.is_super_admin && !isSuperAdmin) {
       // Deshabilitar controles de edición
     }
   });
   ```

3. **Mostrar información jerárquica:**
   ```javascript
   // Mostrar roles contextuales
   user.contextual_roles.forEach(role => {
     console.log(`${role.role} en ${role.scope_name}`);
   });
   
   // Mostrar estructura de empresa
   if (user.branch) {
     const path = `${user.branch.subsidiary.company.company_name} > 
                   ${user.branch.subsidiary.subsidiary_name} > 
                   ${user.branch.branch_name}`;
   }
   ```

---

## 🎉 Beneficios del Sistema

### ✅ **Consolidación Total**
- **UN SOLO ENDPOINT** para administración de usuarios
- Información completa: usuarios + roles + permisos + contexto
- No necesitas múltiples llamadas API

### ✅ **Seguridad Automática**
- Filtrado jerárquico transparente
- Protección de super-admins
- Contexto de autorización incluido

### ✅ **Flexibilidad Máxima**
- Roles globales (Spatie) + roles contextuales
- Permisos directos + permisos por roles
- Estructura multi-empresa completa

### ✅ **Frontend Ready**
- Toda la información necesaria en una sola respuesta
- Metadatos para controles UI
- Paginación y filtros integrados

---

## 🔧 Siguientes Pasos para el Frontend

1. **Reemplazar endpoint actual** `/admin/users` con este consolidado
2. **Actualizar interfaz** para mostrar roles contextuales
3. **Implementar controles** de edición basados en `can_edit`
4. **Mostrar jerarquía** empresa > subsidiaria > sucursal
5. **Usar `user_context`** para controles de autorización

El sistema está **100% listo** para administración completa de permisos con control jerárquico automático. 🚀
