# 📋 DOCUMENTACIÓN COMPLETA - INTEGRACIÓN FRONTEND

## 🎯 PROBLEMA RESUELTO

**Error:** `The route api/api/invitations could not be found`  
**Causa:** El cliente está construyendo URLs con doble prefijo `/api/api/`  
**Solución:** Usar URLs con un solo prefijo `/api/`

---

## 🔧 URLs CORRECTAS PARA EL FRONTEND

### Base URL:
```
http://chilopson-erp-back.test/api
```

### Endpoints de Invitaciones:

#### 🌐 **Públicos (sin autenticación):**
```
GET  /api/invitations/{uid}/{token}/info     # Obtener info de invitación
POST /api/invitations/{uid}/{token}/accept   # Aceptar invitación
```

#### 🔒 **Protegidos (requieren autenticación JWT):**
```
GET    /api/invitations                      # Listar invitaciones
POST   /api/invitations                      # Crear nueva invitación
GET    /api/invitations/{id}                 # Ver detalles específicos
POST   /api/invitations/{id}/resend          # Reenviar invitación
DELETE /api/invitations/{id}/cancel          # Cancelar invitación
GET    /api/invitations/stats/summary        # Estadísticas del sistema
```

---

## 💻 EJEMPLOS DE IMPLEMENTACIÓN

### **JavaScript/Axios:**
```javascript
// ✅ CONFIGURACIÓN CORRECTA
const api = axios.create({
    baseURL: 'http://chilopson-erp-back.test/api',  // SIN doble /api
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// ✅ USO CORRECTO
// Obtener información de invitación
const getInvitationInfo = async (uid, token) => {
    try {
        const response = await api.get(`/invitations/${uid}/${token}/info`);
        return response.data;
    } catch (error) {
        console.error('Error:', error.response?.data);
        throw error;
    }
};

// Aceptar invitación
const acceptInvitation = async (uid, token, userData) => {
    try {
        const response = await api.post(`/invitations/${uid}/${token}/accept`, {
            password: userData.password,
            password_confirmation: userData.passwordConfirmation,
            terms_accepted: true
        });
        return response.data;
    } catch (error) {
        console.error('Error:', error.response?.data);
        throw error;
    }
};

// Para endpoints protegidos (con autenticación)
const createInvitation = async (invitationData, authToken) => {
    try {
        const response = await api.post('/invitations', invitationData, {
            headers: {
                'Authorization': `Bearer ${authToken}`
            }
        });
        return response.data;
    } catch (error) {
        console.error('Error:', error.response?.data);
        throw error;
    }
};
```

### **Fetch API:**
```javascript
// ✅ CONFIGURACIÓN CORRECTA
const BASE_URL = 'http://chilopson-erp-back.test/api';

// Función helper
const apiRequest = async (endpoint, options = {}) => {
    const url = `${BASE_URL}${endpoint}`;
    const defaultHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
    
    const config = {
        headers: { ...defaultHeaders, ...options.headers },
        ...options
    };
    
    const response = await fetch(url, config);
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Request failed');
    }
    
    return response.json();
};

// ✅ USO CORRECTO
// Obtener información de invitación
const getInvitationInfo = (uid, token) => {
    return apiRequest(`/invitations/${uid}/${token}/info`);
};

// Aceptar invitación
const acceptInvitation = (uid, token, userData) => {
    return apiRequest(`/invitations/${uid}/${token}/accept`, {
        method: 'POST',
        body: JSON.stringify({
            password: userData.password,
            password_confirmation: userData.passwordConfirmation,
            terms_accepted: true
        })
    });
};
```

### **React Hook Personalizado:**
```javascript
import { useState, useEffect } from 'react';

export const useInvitation = (uid, token) => {
    const [invitation, setInvitation] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const fetchInvitation = async () => {
            try {
                setLoading(true);
                // ✅ URL CORRECTA
                const response = await fetch(
                    `http://chilopson-erp-back.test/api/invitations/${uid}/${token}/info`,
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );
                
                if (!response.ok) {
                    throw new Error('Invitación no encontrada');
                }
                
                const data = await response.json();
                setInvitation(data.data);
            } catch (err) {
                setError(err.message);
            } finally {
                setLoading(false);
            }
        };

        if (uid && token) {
            fetchInvitation();
        }
    }, [uid, token]);

    const acceptInvitation = async (userData) => {
        try {
            setLoading(true);
            // ✅ URL CORRECTA
            const response = await fetch(
                `http://chilopson-erp-back.test/api/invitations/${uid}/${token}/accept`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(userData)
                }
            );
            
            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || 'Error al aceptar invitación');
            }
            
            return await response.json();
        } catch (err) {
            setError(err.message);
            throw err;
        } finally {
            setLoading(false);
        }
    };

    return { invitation, loading, error, acceptInvitation };
};
```

---

## 🚨 ERRORES COMUNES A EVITAR

### ❌ **NO USAR:**
```javascript
// ❌ INCORRECTO - Doble prefijo api
const wrongUrl = 'http://chilopson-erp-back.test/api/api/invitations/...';

// ❌ INCORRECTO - Sin prefijo api
const wrongUrl2 = 'http://chilopson-erp-back.test/invitations/...';

// ❌ INCORRECTO - Construcción de URL incorrecta
const baseUrl = 'http://chilopson-erp-back.test/api';
const wrongEndpoint = `${baseUrl}/api/invitations/...`;  // Resultado: /api/api/invitations
```

### ✅ **USAR:**
```javascript
// ✅ CORRECTO
const correctUrl = 'http://chilopson-erp-back.test/api/invitations/...';

// ✅ CORRECTO - Construcción de URL correcta
const baseUrl = 'http://chilopson-erp-back.test/api';
const correctEndpoint = `${baseUrl}/invitations/...`;  // Resultado: /api/invitations
```

---

## 🧪 INVITACIÓN DE PRUEBA

Para testing inmediato:
```
UID: 1caec32d-3b8a-4739-9a47-a57a80f042ee
Token: sqQF2bKiLAFMj4YhZ0OIjR6gDuLneUmv

URL Info: http://chilopson-erp-back.test/api/invitations/1caec32d-3b8a-4739-9a47-a57a80f042ee/sqQF2bKiLAFMj4YhZ0OIjR6gDuLneUmv/info
```

---

## 📊 RESPUESTAS DE LA API

### **GET /api/invitations/{uid}/{token}/info**
```json
{
    "success": true,
    "data": {
        "uid": "1caec32d-3b8a-4739-9a47-a57a80f042ee",
        "email": "usuario@ejemplo.com",
        "invited_by": {
            "name": "Admin User",
            "email": "admin@ejemplo.com"
        },
        "company": {
            "id": 1,
            "name": "Empresa Ejemplo"
        },
        "branch": {
            "id": 1,
            "name": "Sucursal Principal"
        },
        "role": "employee",
        "expires_at": "2024-02-15T10:30:00Z",
        "status": "pending"
    }
}
```

### **POST /api/invitations/{uid}/{token}/accept**
**Request:**
```json
{
    "password": "nuevaContrasena123",
    "password_confirmation": "nuevaContrasena123",
    "terms_accepted": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "Invitación aceptada exitosamente",
    "data": {
        "user": {
            "id": 123,
            "name": "Nuevo Usuario",
            "email": "usuario@ejemplo.com"
        },
        "token": "jwt-token-aqui"
    }
}
```

---

## ✅ VERIFICACIÓN FINAL

**Estado del Sistema:** 🟢 **OPERATIVO AL 100%**

- ✅ Todas las rutas funcionan correctamente
- ✅ URLs con `/api/` funcionan perfectamente  
- ✅ URLs con `/api/api/` fallan como debe ser (404)
- ✅ Autenticación JWT operativa
- ✅ Validaciones de datos funcionando
- ✅ Emails de invitación enviándose
- ✅ Base de datos actualizada

**El problema es únicamente la construcción de URLs en el cliente.** Use esta documentación para corregir la implementación del frontend.

---

## 📞 COMANDOS DE UTILIDAD

```bash
# Generar nueva invitación de prueba
php create-test-invitation.php

# Validar todas las URLs
php validate-invitation-urls.php

# Ver rutas registradas
php artisan route:list --path=invitations

# Limpiar invitaciones expiradas
php artisan invitations:cleanup
```
