<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class InvitationController extends Controller
{
    protected $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
        $this->middleware('auth:api')->except(['getInvitationInfo', 'accept']);
    }

    /**
     * Listar invitaciones con filtros
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('invite-users');
        
        $currentUser = JWTAuth::parseToken()->authenticate();
        
        $query = Invitation::with(['invitedBy', 'company', 'branch'])
            ->orderBy('created_at', 'desc');
        
        // Filtros de acceso según el rol del usuario
        if (!$currentUser->hasRole('super-admin')) {
            if ($currentUser->hasRole('company-admin')) {
                // Admin de empresa: solo invitaciones de sus empresas
                $companyIds = $currentUser->companies->pluck('id');
                $query->whereIn('company_id', $companyIds);
            } elseif ($currentUser->hasRole('branch-admin')) {
                // Admin de sucursal: solo invitaciones de sus sucursales
                $branchIds = $currentUser->branches->pluck('id');
                $query->whereIn('branch_id', $branchIds);
            } else {
                // Usuarios normales: solo sus propias invitaciones
                $query->where('invited_by', $currentUser->id);
            }
        }
        
        // Filtros de parámetros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        
        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }
        
        $invitations = $query->paginate($request->get('per_page', 15));
        
        return response()->json([
            'success' => true,
            'data' => $invitations->items(),
            'meta' => [
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
                'per_page' => $invitations->perPage(),
                'total' => $invitations->total(),
            ]
        ]);
    }

    /**
     * Crear nueva invitación
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('invite-users');
        
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'rut' => 'nullable|string|unique:users,rut',
                'position' => 'nullable|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'company_id' => 'required|exists:companies,id',
                'subsidiary_id' => 'nullable|exists:subsidiaries,id',
                'branch_id' => 'required|exists:branches,id',
                'role_name' => 'required|exists:roles,name',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:permissions,name',
                'send_immediately' => 'boolean'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // Validar permisos de acceso a la empresa/sucursal
            $this->validateUserAccess($currentUser, $request->company_id, $request->branch_id);
            
            $invitationData = $request->all();
            $invitationData['invited_by'] = $currentUser->id;
            
            $invitation = $this->invitationService->createInvitation($invitationData);
            
            // Enviar inmediatamente si se solicita
            if ($request->get('send_immediately', true)) {
                $sent = $this->invitationService->sendInvitation($invitation);
                if (!$sent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invitación creada pero falló el envío de email'
                    ], 500);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Invitación creada y enviada exitosamente',
                'data' => $invitation->load(['company', 'branch', 'invitedBy'])
            ], 201);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Ver detalles de invitación
     */
    public function show($id): JsonResponse
    {
        try {
            $invitation = Invitation::with(['invitedBy', 'company', 'subsidiary', 'branch'])
                ->findOrFail($id);
            
            $currentUser = JWTAuth::parseToken()->authenticate();
            
            // Verificar acceso
            $this->validateUserAccessToInvitation($currentUser, $invitation);
            
            return response()->json([
                'success' => true,
                'data' => $invitation
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Reenviar invitación
     */
    public function resend($id): JsonResponse
    {
        try {
            $invitation = Invitation::findOrFail($id);
            $currentUser = JWTAuth::parseToken()->authenticate();
            
            $this->validateUserAccessToInvitation($currentUser, $invitation);
            
            $sent = $this->invitationService->resendInvitation($invitation);
            
            if ($sent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invitación reenviada exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al reenviar la invitación'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Cancelar invitación
     */
    public function cancel($id): JsonResponse
    {
        try {
            $invitation = Invitation::findOrFail($id);
            $currentUser = JWTAuth::parseToken()->authenticate();
            
            $this->validateUserAccessToInvitation($currentUser, $invitation);
            
            $this->invitationService->cancelInvitation($invitation);
            
            return response()->json([
                'success' => true,
                'message' => 'Invitación cancelada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Aceptar invitación (endpoint público)
     */
    public function accept(Request $request, $uid, $token): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8|confirmed',
                'terms_accepted' => 'required|boolean|accepted'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $user = $this->invitationService->acceptInvitation($uid, $token, $request->all());
            
            return response()->json([
                'success' => true,
                'message' => 'Cuenta activada exitosamente. Ya puedes iniciar sesión.',
                'data' => [
                    'user_email' => $user->email,
                    'redirect_url' => config('app.frontend_url') . '/login'
                ]
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener información de invitación (para formulario de aceptación)
     */
    public function getInvitationInfo($uid, $token): JsonResponse
    {
        try {
            $invitation = Invitation::findByUidAndToken($uid, $token);
            
            if (!$invitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invitación no válida o expirada'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'email' => $invitation->email,
                    'first_name' => $invitation->first_name,
                    'last_name' => $invitation->last_name,
                    'position' => $invitation->position,
                    'company_name' => $invitation->company->company_name,
                    'branch_name' => $invitation->branch->branch_name,
                    'role_name' => $invitation->role_name,
                    'expires_at' => $invitation->expires_at,
                    'is_valid' => $invitation->isValid()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información de la invitación'
            ], 500);
        }
    }

    /**
     * Estadísticas de invitaciones
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('invite-users');
        
        $currentUser = JWTAuth::parseToken()->authenticate();
        $companyId = null;
        
        // Si no es super admin, limitar estadísticas a sus empresas
        if (!$currentUser->hasRole('super-admin')) {
            $companyId = $request->get('company_id');
            if ($companyId && !$currentUser->companies->contains('id', $companyId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes acceso a esta empresa'
                ], 403);
            }
        }
        
        $stats = $this->invitationService->getInvitationStats($companyId);
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Validaciones privadas
     */
    private function validateUserAccess($user, $companyId, $branchId): void
    {
        if ($user->hasRole('super-admin')) {
            return; // Super admin tiene acceso total
        }
        
        if ($user->hasRole('company-admin')) {
            if (!$user->companies->contains('id', $companyId)) {
                throw new \Exception('No tienes acceso a esta empresa');
            }
        } elseif ($user->hasRole('branch-admin')) {
            if (!$user->branches->contains('id', $branchId)) {
                throw new \Exception('No tienes acceso a esta sucursal');
            }
        } else {
            throw new \Exception('No tienes permisos para crear invitaciones');
        }
    }
    
    private function validateUserAccessToInvitation($user, Invitation $invitation): void
    {
        if ($user->hasRole('super-admin')) {
            return; // Super admin tiene acceso total
        }
        
        if ($user->hasRole('company-admin')) {
            if (!$user->companies->contains('id', $invitation->company_id)) {
                throw new \Exception('No tienes acceso a esta invitación');
            }
        } elseif ($user->hasRole('branch-admin')) {
            if (!$user->branches->contains('id', $invitation->branch_id)) {
                throw new \Exception('No tienes acceso a esta invitación');
            }
        } else {
            if ($invitation->invited_by !== $user->id) {
                throw new \Exception('No tienes acceso a esta invitación');
            }
        }
    }
}
