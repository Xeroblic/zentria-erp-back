<?php

namespace App\Services;

use App\Models\User;
use App\Models\ScopeRole;
use App\Models\Company;
use App\Models\Subsidiary;
use App\Models\Branch;

class ContextualRoleService
{
    /**
     * Asignar un usuario a una empresa con un rol específico
     */
    public function assignUserToCompany(User $user, Company $company, string $role = 'employee', string $position = null)
    {
        // Crear relación user-company
        $user->companies()->syncWithoutDetaching([
            $company->id => [
                'is_primary' => $user->companies()->count() === 0, // Primera empresa es primaria
                'position_in_company' => $position,
                'joined_at' => now(),
            ]
        ]);

        // Asignar rol contextual si no es employee básico
        if ($role !== 'employee') {
            ScopeRole::assignContextRole($user->id, $role, 'company', $company->id);
        }

        // Crear personalización por empresa
        $user->getPersonalizationForCompany($company->id);

        return true;
    }

    /**
     * Asignar usuario a sucursal con rol específico
     */
    public function assignUserToSubsidiary(User $user, Subsidiary $subsidiary, string $role = 'employee')
    {
        // Verificar que el usuario pertenece a la empresa padre
        if (!$user->companies->contains('id', $subsidiary->company_id)) {
            throw new \Exception('User must belong to parent company first');
        }

        if ($role !== 'employee') {
            ScopeRole::assignContextRole($user->id, $role, 'subsidiary', $subsidiary->id);
        }

        return true;
    }

    /**
     * Asignar usuario a sucursal 
     */
    public function assignUserToBranch(User $user, Branch $branch, string $position = null, bool $isPrimary = false)
    {
        // Verificar que pertenece a la empresa
        if (!$user->companies->contains('id', $branch->subsidiary->company_id)) {
            throw new \Exception('User must belong to parent company first');
        }

        // Crear relación user-branch
        $user->branches()->syncWithoutDetaching([
            $branch->id => [
                'is_primary' => $isPrimary,
                'position' => $position,
            ]
        ]);

        // Si es primaria, actualizar en user
        if ($isPrimary) {
            $user->update(['primary_branch_id' => $branch->id]);
        }

        return true;
    }

    /**
     * Obtener jerarquía de acceso del usuario
     */
    public function getUserAccessHierarchy(User $user)
    {
        if ($user->hasRole('super-admin')) {
            return [
                'level' => 'super-admin',
                'companies' => Company::all(),
                'subsidiaries' => Subsidiary::all(),
                'branches' => Branch::all(),
            ];
        }

        $hierarchy = [
            'level' => 'user',
            'companies' => $user->companies,
            'subsidiaries' => collect(),
            'branches' => $user->branches,
        ];

        // Agregar subsidiarias donde tiene roles
        $subsidiaryRoles = $user->scopeRoles()
            ->where('scope_type', 'subsidiary')
            ->with('scopeEntity')
            ->get();

        foreach ($subsidiaryRoles as $role) {
            $hierarchy['subsidiaries']->push($role->scopeEntity);
        }

        return $hierarchy;
    }

    /**
     * Verificar si un usuario puede realizar una acción en un contexto
     */
    public function userCanPerformAction(User $user, string $action, string $entityType, int $entityId)
    {
        // Super admin puede todo
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Verificar permiso básico
        if (!$user->can($action)) {
            return false;
        }

        // Verificar acceso contextual
        return $user->canAccessEntity($entityType, $entityId);
    }

    /**
     * Promover usuario a administrador de empresa
     */
    public function promoteToCompanyAdmin(User $user, Company $company)
    {
        if (!$user->companies->contains('id', $company->id)) {
            throw new \Exception('User must belong to company first');
        }

        ScopeRole::assignContextRole($user->id, 'company-admin', 'company', $company->id);
        
        return true;
    }

    /**
     * Promover usuario a administrador de subsidiaria
     */
    public function promoteToSubsidiaryAdmin(User $user, Subsidiary $subsidiary)
    {
        if (!$user->companies->contains('id', $subsidiary->company_id)) {
            throw new \Exception('User must belong to parent company first');
        }

        ScopeRole::assignContextRole($user->id, 'subsidiary-admin', 'subsidiary', $subsidiary->id);
        
        return true;
    }

    /**
     * Promover usuario a administrador de sucursal
     */
    public function promoteToBranchAdmin(User $user, Branch $branch)
    {
        if (!$user->branches->contains('id', $branch->id)) {
            throw new \Exception('User must belong to branch first');
        }

        ScopeRole::assignContextRole($user->id, 'branch-admin', 'branch', $branch->id);
        
        return true;
    }
}
