<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\ContextualRoleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminController extends Controller
{
    protected $contextualRoleService;

    public function __construct(ContextualRoleService $contextualRoleService)
    {
        $this->contextualRoleService = $contextualRoleService;
    }

    /**
     * Obtener lista de usuarios con paginación, filtros y acceso jerárquico
     * - Super Admin: Ve todos los usuarios
     * - Company Admin: Ve usuarios de sus empresas
     * - Subsidiary Admin: Ve usuarios de sus subsidiarias
     * - Branch Admin: Ve usuarios de sus sucursales
     */
    public function getUsers(Request $request): JsonResponse
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            
            // Aplicar filtrado jerárquico automático
            if ($currentUser->hasRole('super-admin')) {
                // Super admin ve todos los usuarios
                $query = User::with(['companies', 'roles', 'permissions', 'branches.subsidiary.company', 'scopeRoles.role']);
            } else {
                // Obtener usuarios en el scope del usuario actual
                $usersInScope = $currentUser->getUsersInScope();
                $userIds = $usersInScope->pluck('id');
                
                $query = User::whereIn('id', $userIds)
                    ->with(['companies', 'roles', 'permissions', 'branches.subsidiary.company', 'scopeRoles.role']);
            }
            
            // Filtros adicionales
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('rut', 'like', "%{$search}%");
                });
            }

            if ($request->has('company_id') && !empty($request->company_id)) {
                $query->whereHas('companies', function($q) use ($request) {
                    $q->where('companies.id', $request->company_id);
                });
            }

            if ($request->has('role') && !empty($request->role)) {
                $query->whereHas('roles', function($q) use ($request) {
                    $q->where('name', $request->role);
                });
            }

            // Ordenar por nombre por defecto
            $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');

            // Paginación
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            // Formatear datos para el frontend con información completa de permisos
            $users->getCollection()->transform(function ($user) use ($currentUser) {
                // Obtener roles contextuales (por empresa/subsidiaria/sucursal)
                $contextualRoles = $user->scopeRoles->map(function($scopeRole) {
                    return [
                        'role' => $scopeRole->role->name,
                        'scope_type' => $scopeRole->scope_type,
                        'scope_id' => $scopeRole->scope_id,
                        'scope_name' => $this->getScopeName($scopeRole->scope_type, $scopeRole->scope_id)
                    ];
                });

                // Verificar si puede ser editado por el usuario actual
                $canEdit = $currentUser->hasRole('super-admin') || 
                          ($user->id !== $currentUser->id && !$user->hasRole('super-admin'));
                
                return [
                    'id' => $user->id,
                    'pk' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'second_name' => $user->second_name ?? $user->middle_name,
                    'last_name' => $user->last_name,
                    'second_last_name' => $user->second_last_name,
                    'rut' => $user->rut,
                    'celular' => $user->celular ?? $user->phone_number,
                    'cargo' => $user->cargo ?? $user->position,
                    'fecha_nacimiento' => $user->date_of_birth,
                    'is_staff' => $user->is_staff,
                    'is_active' => $user->is_active ?? true,
                    'can_edit' => $canEdit,
                    'is_super_admin' => $user->hasRole('super-admin'),
                    
                    // Información de empresas
                    'companies' => $user->companies->map(function($company) {
                        return [
                            'id' => $company->id,
                            'name' => $company->company_name,
                            'is_primary' => $company->pivot->is_primary ?? false,
                            'position' => $company->pivot->position_in_company ?? null
                        ];
                    }),
                    
                    // Roles globales
                    'global_roles' => $user->roles->pluck('name'),
                    
                    // Roles contextuales 
                    'contextual_roles' => $contextualRoles,
                    
                    // Permisos (directos + via roles)
                    'direct_permissions' => $user->permissions->pluck('name'),
                    'role_permissions' => $user->getPermissionsViaRoles()->pluck('name'),
                    'all_permissions' => $user->getAllPermissions()->pluck('name'),
                    
                    // Información de sucursal/subsidiaria/empresa
                    'branch' => $user->branches->first() ? [
                        'id' => $user->branches->first()->id,
                        'branch_name' => $user->branches->first()->branch_name,
                        'is_primary' => $user->branches->first()->pivot->is_primary ?? false,
                        'position' => $user->branches->first()->pivot->position ?? null,
                        'subsidiary' => [
                            'id' => $user->branches->first()->subsidiary->id,
                            'subsidiary_name' => $user->branches->first()->subsidiary->subsidiary_name,
                            'company' => [
                                'id' => $user->branches->first()->subsidiary->company->id,
                                'company_name' => $user->branches->first()->subsidiary->company->company_name
                            ]
                        ]
                    ] : null,
                    
                    // Metadatos adicionales
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $users->items(),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
                'user_context' => [
                    'current_user_id' => $currentUser->id,
                    'is_super_admin' => $currentUser->hasRole('super-admin'),
                    'can_manage_users' => $currentUser->can('edit-users'),
                    'access_level' => $this->getUserAccessLevel($currentUser)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un usuario individual con toda su información
     */
    public function getUser($id): JsonResponse
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            
            // Buscar el usuario con todas las relaciones
            $user = User::with(['companies', 'roles', 'permissions', 'branches.subsidiary.company', 'scopeRoles.role'])
                ->findOrFail($id);
            
            // Verificar que el usuario actual puede ver este usuario
            if (!$currentUser->hasRole('super-admin')) {
                $usersInScope = $currentUser->getUsersInScope();
                if (!$usersInScope->contains('id', $user->id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para ver este usuario'
                    ], 403);
                }
            }
            
            // Obtener roles contextuales
            $contextualRoles = $user->scopeRoles->map(function($scopeRole) {
                return [
                    'role' => $scopeRole->role->name,
                    'scope_type' => $scopeRole->scope_type,
                    'scope_id' => $scopeRole->scope_id,
                    'scope_name' => $this->getScopeName($scopeRole->scope_type, $scopeRole->scope_id)
                ];
            });

            // Verificar si puede ser editado por el usuario actual
            $canEdit = $currentUser->hasRole('super-admin') || 
                      ($user->id !== $currentUser->id && !$user->hasRole('super-admin'));
            
            $userData = [
                'id' => $user->id,
                'pk' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'second_name' => $user->second_name ?? $user->middle_name,
                'last_name' => $user->last_name,
                'second_last_name' => $user->second_last_name,
                'rut' => $user->rut,
                'celular' => $user->celular ?? $user->phone_number,
                'cargo' => $user->cargo ?? $user->position,
                'fecha_nacimiento' => $user->date_of_birth,
                'is_staff' => $user->is_staff,
                'is_active' => $user->is_active ?? true,
                'can_edit' => $canEdit,
                'is_super_admin' => $user->hasRole('super-admin'),
                
                // Información de empresas
                'companies' => $user->companies->map(function($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->company_name,
                        'is_primary' => $company->pivot->is_primary ?? false,
                        'position' => $company->pivot->position_in_company ?? null
                    ];
                }),
                
                // Roles globales
                'global_roles' => $user->roles->pluck('name'),
                
                // Roles contextuales 
                'contextual_roles' => $contextualRoles,
                
                // Permisos (directos + via roles)
                'direct_permissions' => $user->permissions->pluck('name'),
                'role_permissions' => $user->getPermissionsViaRoles()->pluck('name'),
                'all_permissions' => $user->getAllPermissions()->pluck('name'),
                
                // Información de sucursal/subsidiaria/empresa
                'branch' => $user->branches->first() ? [
                    'id' => $user->branches->first()->id,
                    'branch_name' => $user->branches->first()->branch_name,
                    'is_primary' => $user->branches->first()->pivot->is_primary ?? false,
                    'position' => $user->branches->first()->pivot->position ?? null,
                    'subsidiary' => [
                        'id' => $user->branches->first()->subsidiary->id,
                        'subsidiary_name' => $user->branches->first()->subsidiary->subsidiary_name,
                        'company' => [
                            'id' => $user->branches->first()->subsidiary->company->id,
                            'company_name' => $user->branches->first()->subsidiary->company->company_name
                        ]
                    ]
                ] : null,
                
                // Metadatos adicionales
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            return response()->json([
                'success' => true,
                'data' => $userData,
                'user_context' => [
                    'current_user_id' => $currentUser->id,
                    'is_super_admin' => $currentUser->hasRole('super-admin'),
                    'can_manage_users' => $currentUser->can('edit-users'),
                    'access_level' => $this->getUserAccessLevel($currentUser)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método auxiliar para obtener el nombre del scope
     */
    private function getScopeName($scopeType, $scopeId)
    {
        try {
            switch ($scopeType) {
                case 'company':
                    $company = \App\Models\Company::find($scopeId);
                    return $company ? $company->company_name : 'Empresa no encontrada';
                    
                case 'subsidiary':
                    $subsidiary = \App\Models\Subsidiary::find($scopeId);
                    return $subsidiary ? $subsidiary->subsidiary_name : 'Subsidiaria no encontrada';
                    
                case 'branch':
                    $branch = \App\Models\Branch::find($scopeId);
                    return $branch ? $branch->branch_name : 'Sucursal no encontrada';
                    
                default:
                    return 'Scope desconocido';
            }
        } catch (\Exception $e) {
            return 'Error al obtener nombre';
        }
    }

    /**
     * Método auxiliar para determinar el nivel de acceso del usuario
     */
    private function getUserAccessLevel($user)
    {
        if ($user->hasRole('super-admin')) {
            return 'super-admin';
        } elseif ($user->hasContextRole('company-admin', 'company', null) || 
                  $user->roles->contains('name', 'company-admin')) {
            return 'company-admin';
        } elseif ($user->scopeRoles->where('scope_type', 'subsidiary')->isNotEmpty()) {
            return 'subsidiary-admin';
        } elseif ($user->scopeRoles->where('scope_type', 'branch')->isNotEmpty()) {
            return 'branch-admin';
        } else {
            return 'employee';
        }
    }

    /**
     * Obtener todos los permisos disponibles
     */
    public function getPermissions(): JsonResponse
    {
        try {
            $permissions = Permission::orderBy('name')->get(['id', 'name', 'guard_name', 'created_at']);
            
            // Estructura para fácil uso del frontend
            $permissionsFormatted = $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => ucwords(str_replace('-', ' ', $permission->name)),
                    'guard_name' => $permission->guard_name
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $permissionsFormatted,
                'meta' => [
                    'total' => $permissions->count(),
                    'note' => 'Use "name" field when assigning permissions, not "id"'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los roles disponibles
     */
    public function getRoles(): JsonResponse
    {
        try {
            $roles = Role::with('permissions')->orderBy('name')->get();
            
            $rolesData = $roles->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => ucwords(str_replace('-', ' ', $role->name)),
                    'guard_name' => $role->guard_name,
                    'permissions' => $role->permissions->pluck('name'),
                    'permissions_count' => $role->permissions->count(),
                    'created_at' => $role->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $rolesData,
                'meta' => [
                    'total' => $roles->count(),
                    'note' => 'Use "name" field when assigning roles, not "id"'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo usuario
     */
    public function createUser(StoreUserRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $userData = $request->validated();
            $userData['password'] = Hash::make($userData['password']);

            $user = User::create($userData);

            // Asignar roles si se proporcionaron
            if ($request->has('roles') && is_array($request->roles)) {
                $user->assignRole($request->roles);
            }

            // Asignar permisos específicos si se proporcionaron
            if ($request->has('permissions') && is_array($request->permissions)) {
                $user->givePermissionTo($request->permissions);
            }

            // Asignar a empresas si se proporcionaron
            if ($request->has('companies') && is_array($request->companies)) {
                foreach ($request->companies as $companyData) {
                    $this->contextualRoleService->assignUserToCompany(
                        $user, 
                        $companyData['id'], 
                        $companyData['role'] ?? 'employee',
                        $companyData['position'] ?? null
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => $user->load(['roles', 'permissions', 'companies'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar usuario existente
     */
    public function updateUser(UpdateUserRequest $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = User::findOrFail($id);
            $userData = $request->validated();

            // Actualizar password solo si se proporciona
            if (isset($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            } else {
                unset($userData['password']);
            }

            $user->update($userData);

            // Actualizar roles
            if ($request->has('roles')) {
                $user->syncRoles($request->roles);
            }

            // Actualizar permisos específicos
            if ($request->has('permissions')) {
                $user->syncPermissions($request->permissions);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => $user->load(['roles', 'permissions', 'companies'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar usuario
     */
    public function deleteUser($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Verificar que no sea el usuario actual
            $currentUserId = JWTAuth::parseToken()->authenticate()->id;
            if ($user->id === $currentUserId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propia cuenta'
                ], 400);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar/Desactivar usuario
     */
    public function toggleUserStatus($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            $user->is_active = !($user->is_active ?? true);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => $user->is_active ? 'Usuario activado' : 'Usuario desactivado',
                'data' => ['is_active' => $user->is_active]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar estado del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener permisos de un usuario específico
     */
    public function getUserPermissions($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'direct_permissions' => $user->permissions->pluck('name'),
                    'role_permissions' => $user->getPermissionsViaRoles()->pluck('name'),
                    'all_permissions' => $user->getAllPermissions()->pluck('name')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar permisos a usuario (acepta tanto IDs como nombres)
     */
    public function assignPermissionsToUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Obtener datos del request - manejar tanto form data como JSON
            $requestData = $request->all();
            
            // Si el request está vacío, intentar parsear JSON del body
            if (empty($requestData) && $request->getContent()) {
                $jsonData = json_decode($request->getContent(), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $requestData = $jsonData;
                }
            }
            
            // Debug: Loggear lo que recibimos
            Log::info('Assign Permissions Request', [
                'user_id' => $id,
                'request_all' => $request->all(),
                'parsed_data' => $requestData,
                'raw_content' => $request->getContent(),
                'content_type' => $request->header('Content-Type')
            ]);
            
            // Validación básica
            $validator = Validator::make($requestData, [
                'permissions' => 'required|array',
                'permissions.*' => 'required'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación: el campo permissions es requerido y debe ser un array',
                    'errors' => $validator->errors(),
                    'received_data' => $requestData,
                    'debug_info' => [
                        'request_all' => $request->all(),
                        'raw_content' => $request->getContent(),
                        'content_type' => $request->header('Content-Type'),
                        'help' => 'El frontend debe enviar: {"permissions": ["view-user", "edit-user"]} - usar NOMBRES no IDs'
                    ]
                ], 422);
            }

            // Convertir IDs a nombres si es necesario (para compatibilidad con frontend)
            $permissions = collect($requestData['permissions'])->map(function ($permission) {
                if (is_numeric($permission)) {
                    // Es un ID, convertir a nombre
                    $permissionModel = Permission::find($permission);
                    if (!$permissionModel) {
                        throw new \Exception("Permiso con ID {$permission} no encontrado");
                    }
                    return $permissionModel->name;
                } else {
                    // Es un nombre, verificar que existe
                    $permissionModel = Permission::where('name', $permission)->where('guard_name', 'api')->first();
                    if (!$permissionModel) {
                        throw new \Exception("Permiso '{$permission}' no encontrado");
                    }
                    return $permission;
                }
            })->toArray();

            $user->givePermissionTo($permissions);

            return response()->json([
                'success' => true,
                'message' => 'Permisos asignados exitosamente',
                'data' => $user->getAllPermissions()->pluck('name'),
                'assigned_permissions' => $permissions
            ]);

        } catch (\Exception $e) {
            Log::error('Error assigning permissions', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar permisos',
                'error' => $e->getMessage(),
                'help' => 'Asegúrate de enviar: {"permissions": ["view-user", "edit-user"]} usando nombres de permisos, no IDs'
            ], 500);
        }
    }

    /**
     * Revocar permisos de usuario
     */
    public function revokePermissionsFromUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'string|exists:permissions,name'
            ]);

            $user->revokePermissionTo($request->permissions);

            return response()->json([
                'success' => true,
                'message' => 'Permisos revocados exitosamente',
                'data' => $user->getAllPermissions()->pluck('name')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al revocar permisos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener roles de un usuario específico
     */
    public function getUserRoles($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $user->roles->pluck('name')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener roles del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asignar roles a usuario (acepta tanto IDs como nombres ojaio)
     */
    public function assignRolesToUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            // Obtener datos del request - manejar tanto form data como JSON
            $requestData = $request->all();
            
            // Si el request está vacío, intentar parsear JSON del body
            if (empty($requestData) && $request->getContent()) {
                $jsonData = json_decode($request->getContent(), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                    $requestData = $jsonData;
                }
            }
            
            // Debug: Loggear lo que recibimos
            Log::info('Assign Roles Request', [
                'user_id' => $id,
                'request_all' => $request->all(),
                'parsed_data' => $requestData,
                'raw_content' => $request->getContent(),
                'content_type' => $request->header('Content-Type')
            ]);
            
            // Validación básica
            $validator = Validator::make($requestData, [
                'roles' => 'required|array',
                'roles.*' => 'required'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación: el campo roles es requerido y debe ser un array',
                    'errors' => $validator->errors(),
                    'received_data' => $requestData,
                    'debug_info' => [
                        'request_all' => $request->all(),
                        'raw_content' => $request->getContent(),
                        'content_type' => $request->header('Content-Type'),
                        'help' => 'El frontend debe enviar: {"roles": ["company-admin", "manager"]} - usar NOMBRES no IDs'
                    ]
                ], 422);
            }

            // Convertir IDs a nombres si es necesario (para compatibilidad con frontend)
            $roles = collect($requestData['roles'])->map(function ($role) {
                if (is_numeric($role)) {
                    // Es un ID, convertir a nombre
                    $roleModel = Role::find($role);
                    if (!$roleModel) {
                        throw new \Exception("Rol con ID {$role} no encontrado");
                    }
                    return $roleModel->name;
                } else {
                    // Es un nombre, verificar que existe
                    $roleModel = Role::where('name', $role)->where('guard_name', 'api')->first();
                    if (!$roleModel) {
                        throw new \Exception("Rol '{$role}' no encontrado");
                    }
                    return $role;
                }
            })->toArray();

            $user->assignRole($roles);

            return response()->json([
                'success' => true,
                'message' => 'Roles asignados exitosamente',
                'data' => $user->roles->pluck('name'),
                'assigned_roles' => $roles
            ]);

        } catch (\Exception $e) {
            Log::error('Error assigning roles', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar roles',
                'error' => $e->getMessage(),
                'help' => 'Asegúrate de enviar: {"roles": ["company-admin", "manager"]} usando nombres de roles, no IDs'
            ], 500);
        }
    }

    /**
     * Revocar roles de usuario
     */
    public function revokeRolesFromUser(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            
            $request->validate([
                'roles' => 'required|array',
                'roles.*' => 'string|exists:roles,name'
            ]);

            $user->removeRole($request->roles);

            return response()->json([
                'success' => true,
                'message' => 'Roles revocados exitosamente',
                'data' => $user->roles->pluck('name')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al revocar roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revocar un rol específico de un usuario
     */
    public function revokeSpecificRoleFromUser($userId, $roleId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            // Convertir ID a nombre si es necesario
            if (is_numeric($roleId)) {
                $role = Role::find($roleId);
                if (!$role) {
                    return response()->json([
                        'success' => false,
                        'message' => "Rol con ID {$roleId} no encontrado"
                    ], 404);
                }
                $roleName = $role->name;
            } else {
                $roleName = $roleId;
            }

            // Verificar si el usuario tiene el rol
            if (!$user->hasRole($roleName)) {
                return response()->json([
                    'success' => false,
                    'message' => "El usuario no tiene el rol '{$roleName}'"
                ], 400);
            }

            $user->removeRole($roleName);

            Log::info('Role removed from user', [
                'user_id' => $userId,
                'role_removed' => $roleName,
                'remaining_roles' => $user->roles->pluck('name')->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Rol '{$roleName}' revocado exitosamente",
                'data' => $user->roles->pluck('name'),
                'removed_role' => $roleName
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing specific role', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al revocar el rol',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revocar un permiso específico de un usuario
     */
    public function revokeSpecificPermissionFromUser($userId, $permissionId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            // Convertir ID a nombre si es necesario
            if (is_numeric($permissionId)) {
                $permission = Permission::find($permissionId);
                if (!$permission) {
                    return response()->json([
                        'success' => false,
                        'message' => "Permiso con ID {$permissionId} no encontrado"
                    ], 404);
                }
                $permissionName = $permission->name;
            } else {
                $permissionName = $permissionId;
            }

            // Verificar si el usuario tiene el permiso directo
            if (!$user->hasDirectPermission($permissionName)) {
                return response()->json([
                    'success' => false,
                    'message' => "El usuario no tiene el permiso directo '{$permissionName}'"
                ], 400);
            }

            $user->revokePermissionTo($permissionName);

            Log::info('Permission removed from user', [
                'user_id' => $userId,
                'permission_removed' => $permissionName,
                'remaining_direct_permissions' => $user->getDirectPermissions()->pluck('name')->toArray()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Permiso '{$permissionName}' revocado exitosamente",
                'data' => $user->getAllPermissions()->pluck('name'),
                'removed_permission' => $permissionName
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing specific permission', [
                'user_id' => $userId,
                'permission_id' => $permissionId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al revocar el permiso',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
