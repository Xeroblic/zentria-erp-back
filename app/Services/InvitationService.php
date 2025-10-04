<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Mail\InvitacionMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    /**
     * Crear nueva invitación
     */
    public function createInvitation(array $data): Invitation
    {
        $this->validateInvitationData($data);
        
        // Verificar que no existe usuario con este email
        if (User::where('email', $data['email'])->exists()) {
            throw new \Exception('Ya existe un usuario con este email');
        }
        
        // Verificar que no hay invitación pendiente
        $existingInvitation = Invitation::where('email', $data['email'])
            ->valid()
            ->first();
            
        if ($existingInvitation) {
            throw new \Exception('Ya existe una invitación pendiente para este email');
        }
        
        // Validar relaciones organizacionales
        $this->validateOrganizationalData($data);
        
        return DB::transaction(function () use ($data) {
            return Invitation::create([
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'rut' => $data['rut'] ?? null,
                'position' => $data['position'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'address' => $data['address'] ?? null,
                'invited_by' => $data['invited_by'],
                'company_id' => $data['company_id'],
                'subsidiary_id' => $data['subsidiary_id'] ?? null,
                'branch_id' => $data['branch_id'],
                'role_name' => $data['role_name'],
                'permissions' => $data['permissions'] ?? [],
                'data' => $data['additional_data'] ?? []
            ]);
        });
    }
    
    /**
     * Enviar invitación por email
     */
    public function sendInvitation(Invitation $invitation): bool
    {
        try {
            if ($invitation->status !== Invitation::STATUS_PENDING) {
                throw new \Exception('La invitación no está en estado pendiente');
            }
            
            if (!$invitation->isValid()) {
                throw new \Exception('La invitación ha expirado');
            }
            
            // Enviar email
            Mail::to($invitation->email)->send(new InvitacionMail($invitation));
            
            // Marcar como enviada
            $invitation->markAsSent();
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error enviando invitación: ' . $e->getMessage(), [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email
            ]);
            
            return false;
        }
    }
    
    /**
     * Aceptar invitación y crear usuario
     */
    public function acceptInvitation(string $uid, string $token, array $userData): User
    {
        $invitation = Invitation::findByUidAndToken($uid, $token);
        
        if (!$invitation) {
            throw new \Exception('Invitación no válida o expirada');
        }
        
        if ($invitation->status === Invitation::STATUS_ACCEPTED) {
            throw new \Exception('Esta invitación ya ha sido aceptada');
        }
        
        // Validar datos del usuario
        $validator = Validator::make($userData, [
            'password' => 'required|string|min:8|confirmed',
            'terms_accepted' => 'required|boolean|accepted'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return DB::transaction(function () use ($invitation, $userData) {
            // Crear usuario
            $user = User::create([
                'first_name' => $invitation->first_name,
                'last_name' => $invitation->last_name,
                'email' => $invitation->email,
                'rut' => $invitation->rut,
                'position' => $invitation->position,
                'phone_number' => $invitation->phone_number,
                'address' => $invitation->address,
                'password' => Hash::make($userData['password']),
                'is_active' => true,
                'primary_branch_id' => $invitation->branch_id
            ]);
            
            // Asignar rol principal
            $user->assignRole($invitation->role_name);
            
            // Asignar permisos adicionales si los hay
            if (!empty($invitation->permissions)) {
                $user->givePermissionTo($invitation->permissions);
            }
            
            // Asignar a empresa y sucursal usando el servicio contextual
            $contextualService = app(\App\Services\ContextualRoleService::class);
            $contextualService->assignUserToCompany(
                $user, 
                $invitation->company,
                $invitation->role_name,
                $invitation->position
            );
            
            if ($invitation->branch) {
                $contextualService->assignUserToBranch(
                    $user,
                    $invitation->branch,
                    $invitation->position,
                    true // Es sucursal primaria
                );
            }
            
            // Marcar invitación como aceptada
            $invitation->markAsAccepted();
            
            return $user;
        });
    }
    
    /**
     * Reenviar invitación
     */
    public function resendInvitation(Invitation $invitation): bool
    {
        if ($invitation->status === Invitation::STATUS_ACCEPTED) {
            throw new \Exception('No se puede reenviar una invitación ya aceptada');
        }
        
        // Regenerar tokens y extender expiración
        $invitation->generateTokens();
        $invitation->status = Invitation::STATUS_PENDING;
        $invitation->sent_at = null;
        $invitation->save();
        
        return $this->sendInvitation($invitation);
    }
    
    /**
     * Cancelar invitación
     */
    public function cancelInvitation(Invitation $invitation): bool
    {
        if ($invitation->status === Invitation::STATUS_ACCEPTED) {
            throw new \Exception('No se puede cancelar una invitación ya aceptada');
        }
        
        $invitation->cancel();
        return true;
    }
    
    /**
     * Limpiar invitaciones expiradas
     */
    public function cleanupExpiredInvitations(): int
    {
        $expiredCount = Invitation::expired()->count();
        
        Invitation::expired()->update(['status' => Invitation::STATUS_EXPIRED]);
        
        return $expiredCount;
    }
    
    /**
     * Obtener estadísticas de invitaciones
     */
    public function getInvitationStats(int $companyId = null): array
    {
        $query = Invitation::query();
        
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return [
            'total' => $query->count(),
            'pending' => $query->where('status', Invitation::STATUS_PENDING)->count(),
            'sent' => $query->where('status', Invitation::STATUS_SENT)->count(),
            'accepted' => $query->where('status', Invitation::STATUS_ACCEPTED)->count(),
            'expired' => $query->where('status', Invitation::STATUS_EXPIRED)->count(),
            'cancelled' => $query->where('status', Invitation::STATUS_CANCELLED)->count(),
        ];
    }
    
    /**
     * Validaciones privadas
     */
    private function validateInvitationData(array $data): void
    {
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'rut' => 'nullable|string|unique:users,rut',
            'position' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'invited_by' => 'required|exists:users,id',
            'company_id' => 'required|exists:companies,id',
            'subsidiary_id' => 'nullable|exists:subsidiaries,id',
            'branch_id' => 'required|exists:branches,id',
            'role_name' => 'required|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name'
        ]);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
    
    private function validateOrganizationalData(array $data): void
    {
        $company = Company::find($data['company_id']);
        $branch = Branch::find($data['branch_id']);
        
        if (!$company || !$branch) {
            throw new \Exception('Empresa o sucursal no válida');
        }
        
        // Verificar que la sucursal pertenece a la empresa
        if ($branch->subsidiary->company_id !== $company->id) {
            throw new \Exception('La sucursal no pertenece a la empresa especificada');
        }
        
        // Si se especifica subsidiary, validar coherencia
        if (!empty($data['subsidiary_id'])) {
            if ($branch->subsidiary_id !== $data['subsidiary_id']) {
                throw new \Exception('La sucursal no pertenece a la filial especificada');
            }
        }
    }
}
