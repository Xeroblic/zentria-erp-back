# Documentación de API - Sistema Multi-Empresa

## Rutas de Autenticación

### POST /api/login
Iniciar sesión de usuario
```json
{
  "email": "usuario@example.com",
  "password": "password123"
}
```

### POST /api/register  
Registrar nuevo usuario
```json
{
  "first_name": "Juan",
  "last_name": "Pérez", 
  "email": "juan@example.com",
  "password": "password123"
}
```

### GET /api/available-companies
Obtener empresas disponibles para el usuario autenticado
**Requiere:** Token JWT en header `Authorization: Bearer {token}`

## Rutas de Administración de Usuarios

**Todas las rutas admin requieren autenticación JWT**

### GET /api/admin/users
Obtener lista de usuarios con paginación
**Parámetros de consulta:**
- `search`: Búsqueda por nombre, email o RUT
- `company_id`: Filtrar por empresa
- `role`: Filtrar por rol
- `per_page`: Elementos por página (default: 15)

### POST /api/admin/users
Crear nuevo usuario
```json
{
  "email": "nuevo@example.com",
  "first_name": "Nombre",
  "last_name": "Apellido",
  "rut": "12345678-9",
  "password": "password123",
  "company_id": 1,
  "position": "Empleado"
}
```

### PUT /api/admin/users/{id}
Actualizar usuario existente
```json
{
  "email": "actualizado@example.com",
  "first_name": "Nombre Actualizado",
  "last_name": "Apellido Actualizado",
  "position": "Gerente",
  "is_active": true
}
```

### DELETE /api/admin/users/{id}
Eliminar usuario

### PATCH /api/admin/users/{id}/toggle-status
Cambiar estado activo/inactivo del usuario

## Rutas de Gestión de Permisos

### GET /api/admin/permissions
Obtener todos los permisos disponibles

### GET /api/admin/users/{id}/permissions
Obtener permisos específicos de un usuario

### POST /api/admin/users/{id}/permissions
Asignar permiso a usuario
```json
{
  "permission": "edit-users"
}
```

### DELETE /api/admin/users/{id}/permissions/{permissionId}
Revocar permiso específico de usuario

## Rutas de Gestión de Roles

### GET /api/admin/roles
Obtener todos los roles disponibles

### POST /api/admin/users/{id}/roles
Asignar rol a usuario
```json
{
  "role": "admin"
}
```

### DELETE /api/admin/users/{id}/roles
Revocar roles de usuario
```json
{
  "roles": ["admin", "manager"]
}
```

## Códigos de Respuesta

- **200**: Operación exitosa
- **201**: Recurso creado exitosamente
- **400**: Error en la solicitud
- **401**: No autenticado
- **403**: Sin permisos
- **404**: Recurso no encontrado
- **422**: Error de validación
- **500**: Error interno del servidor

## Estructura de Respuesta Estándar

### Éxito
```json
{
  "success": true,
  "message": "Operación completada",
  "data": { /* datos */ }
}
```

### Error
```json
{
  "success": false,
  "message": "Descripción del error",
  "error": "Detalles técnicos"
}
```

## Autenticación JWT

Todas las rutas protegidas requieren el header:
```
Authorization: Bearer {jwt_token}
```

El token se obtiene del endpoint `/api/login` y debe renovarse usando `/api/refresh`.
